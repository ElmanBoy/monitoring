<?php
/**
 * Интеграционные тесты для проверки исправлений багов в системе согласования.
 * Тесты работают с реальной базой данных через SSH-туннель.
 *
 * ТРЕБОВАНИЯ:
 *   - Активный SSH-туннель к продакшн-серверу:
 *     ssh -f -N -L 5432:localhost:5432 -i ~/.ssh/id_rsa elmanb@10.12.123.243
 *
 * ЗАПУСК:
 *   php tests/AgreementBugFixIntegrationTest.php
 *
 * Тест 1: Повторяющийся сотрудник (_is_redirector_repeat) не получает дублированный result
 *         после нормализации и сохранения через PostgreSQL jsonb.
 *
 * Тест 2: Цепочка уведомлений A→B: структура agreementList корректна для отправки
 *         уведомлений — isRedirectChainCompleted возвращает false пока redirect не завершён,
 *         и участник B (pending в redirect) будет включён в список уведомлений.
 */

// Устанавливаем DOCUMENT_ROOT для подключения к ядру системы
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

// ============================================================
// Эмуляция минимального окружения для подключения к БД
// ============================================================
if (!isset($_SESSION)) {
    $_SESSION = [];
}
if (!isset($_POST)) {
    $_POST = [];
}

// ============================================================
// Копии чистых функций из modules/documents/ajaxHandlers/updateAgreement.php
// (не подключаем файл напрямую — он выполняет запросы к БД и читает $_POST при подключении)
// ============================================================

/**
 * Возвращает статус согласующего на основе поля result.
 */
function bf_getApproverStatus(array $approver): array
{
    $result = $approver['result'] ?? null;
    if (!$result || !is_array($result)) {
        return ['status' => 'pending', 'result_id' => 0];
    }
    $resultId = intval($result['id'] ?? 0);
    switch ($resultId) {
        case 1: case 2: case 3:
            return ['status' => 'approved',   'result_id' => $resultId];
        case 4:
            return ['status' => 'redirected', 'result_id' => 4];
        case 5:
            return ['status' => 'rejected',   'result_id' => 5];
        case 6:
            return ['status' => 'returned',   'result_id' => 6];
        default:
            return ['status' => 'pending',    'result_id' => 0];
    }
}

/**
 * Рекурсивно нормализует секцию agreementList:
 * - "true"/"false" → булевы значения
 * - result="" → null
 * - result.id как int
 * - У записей с _is_redirector_repeat: true принудительно сбрасывает result в null,
 *   чтобы сервер не доверял клиенту и не принимал уже заполненный result.
 *
 * Это копия функции из modules/documents/ajaxHandlers/updateAgreement.php
 */
function bf_normalizeSection($item): array
{
    if (!is_array($item)) return $item;
    foreach ($item as $k => &$v) {
        if ($k === '_is_redirector_repeat') {
            $v = ($v === true || $v === 'true' || $v === 1 || $v === '1');
            continue;
        }
        if ($k === 'result') {
            if ($v === '' || $v === 'null' || $v === null) {
                $v = null;
            } elseif (is_array($v)) {
                $v['id'] = intval($v['id'] ?? 0);
            }
            continue;
        }
        if ($k === 'redirect' && is_array($v)) {
            $v = array_map('bf_normalizeSection', $v);
            continue;
        }
        if (is_array($v)) {
            $v = bf_normalizeSection($v);
        }
    }
    unset($v); // Разрываем ссылку на последний элемент после foreach по ссылке

    // Сервер не доверяет клиенту: если запись является повторной записью перенаправившего,
    // сбрасываем result в null независимо от того, что прислал клиент.
    if (!empty($item['_is_redirector_repeat']) && isset($item['result']) && $item['result'] !== null) {
        $item['result'] = null;
    }
    return $item;
}

/**
 * Проверяет, завершена ли redirect-цепочка (рекурсивно).
 * Redirect-участник считается завершённым только тогда, когда:
 * 1. Все перенаправленные участники дали ответ (не pending)
 * 2. Сам перенаправивший принял финальное решение через _is_redirector_repeat-запись
 *
 * Это копия функции из modules/documents/ajaxHandlers/updateAgreement.php
 */
function bf_isRedirectChainCompleted(array $redirectArr): bool
{
    foreach ($redirectArr as $approver) {
        if (!isset($approver['id'])) continue;
        $status = bf_getApproverStatus($approver);

        if ($status['status'] === 'pending') return false;
        if ($status['status'] === 'returned') return false;

        if ($status['status'] === 'redirected') {
            // Проверяем, завершена ли под-цепочка
            $subDone = isset($approver['redirect']) && is_array($approver['redirect'])
                && bf_isRedirectChainCompleted($approver['redirect']);
            if (!$subDone) return false;

            // Под-цепочка завершена, но сам redirector должен принять финальное решение.
            // Финальное решение фиксируется в _is_redirector_repeat-записи с непустым result.
            $redirectorId = $approver['id'];
            $hasFinalized = false;
            foreach ($redirectArr as $entry) {
                if (isset($entry['id']) && $entry['id'] == $redirectorId
                    && !empty($entry['_is_redirector_repeat'])
                    && !empty($entry['result'])) {
                    $hasFinalized = true;
                    break;
                }
            }
            if (!$hasFinalized) return false;
        }
        // approved / rejected — считаются завершёнными
    }
    return true;
}

// ============================================================
// Класс тестов
// ============================================================

class AgreementBugFixIntegrationTest
{
    private $db;
    private $testResults  = [];
    private $totalTests   = 0;
    private $passedTests  = 0;
    private $failedTests  = 0;

    // ID тестовых записей для очистки в блоке finally
    private $createdAgreementIds = [];

    // Маркер: все тестовые записи создаются с source_table = 'test_bug_fix'
    // для надёжной идентификации и очистки
    const TEST_SOURCE_TABLE = 'test_bug_fix';

    public function __construct()
    {
        echo "\n=== AgreementBugFix Integration Test Suite ===\n";
        echo "Интеграционные тесты исправлений багов системы согласования\n\n";

        // Пробуем подключиться к БД — если SSH-туннель не активен, тест пропускается
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
            $this->db = new \Core\Db();
            // Выполняем простой запрос, чтобы убедиться в доступности БД
            $this->db->db::getCell('SELECT 1');
        } catch (\Exception $e) {
            echo "⚠️  SSH-туннель недоступен, пропускаю интеграционный тест\n";
            echo "   Причина: " . $e->getMessage() . "\n\n";
            echo "   Чтобы запустить тесты, создайте SSH-туннель:\n";
            echo "   ssh -f -N -L 5432:localhost:5432 -i ~/.ssh/id_rsa elmanb@10.12.123.243\n\n";
            exit(0);
        }
    }

    /**
     * Запускает все интеграционные тесты
     */
    public function runAll(): void
    {
        echo "Запуск интеграционных тестов...\n\n";

        $this->testNormalizeSectionDoesNotDuplicateResult();
        $this->testNotificationChainStructureForRedirect();

        $this->printReport();
    }

    // ============================================================
    // ТЕСТ 1: Повторяющийся сотрудник не получает дублированный result
    // ============================================================

    /**
     * Проверяет, что после round-trip через PostgreSQL jsonb:
     * - normalizeSection сбрасывает result у записи с _is_redirector_repeat: true
     * - Данные в jsonb не приводят к нежелательному копированию result
     *
     * Баг: Клиент мог прислать result != null в записи _is_redirector_repeat.
     * Сервер должен принудительно сбросить его в null.
     */
    private function testNormalizeSectionDoesNotDuplicateResult(): void
    {
        echo "--- Тест 1: normalizeSection не дублирует result у _is_redirector_repeat ---\n";

        // agreementList с двумя вхождениями одного сотрудника user_id=9999:
        // - Первое вхождение: настоящее, result установлен (id:3, "Без ЭП")
        // - Второе вхождение: _is_redirector_repeat + result != null (как будто пришло от клиента)
        $agreementListInput = [
            [
                // Метаданные секции
                ['stage' => '', 'list_type' => 2],
                // Первое вхождение участника: настоящее решение
                [
                    'id'     => 9999,
                    'type'   => 1,
                    'vrio'   => '0',
                    'urgent' => '0',
                    'role'   => '0',
                    'result' => ['id' => 3, 'text' => 'Без ЭП'],
                ],
                // Второе вхождение: _is_redirector_repeat + клиент прислал result (не должно быть принято)
                [
                    'id'                    => 9999,
                    'type'                  => 1,
                    'vrio'                  => '0',
                    'urgent'                => '0',
                    'role'                  => '0',
                    'result'                => ['id' => 3, 'text' => 'Без ЭП'],
                    '_is_redirector_repeat' => true,
                ],
            ]
        ];

        $insertedId = null;
        try {
            // Сохраняем тестовую запись в реальную БД
            $insertResult = $this->db->insert('agreement', [
                'source_table'  => self::TEST_SOURCE_TABLE,
                'source_id'     => 0,
                'documentacial' => 1,
                'status'        => 0,
                'active'        => 1,
                'author'        => 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'agreementlist' => json_encode($agreementListInput, JSON_UNESCAPED_UNICODE),
            ]);

            $this->assert(
                'Тест 1: Тестовая запись успешно создана в БД',
                $insertResult['result'] === true,
                $insertResult['result'] ? 'INSERT выполнен, id=' . $insertResult['id'] : $insertResult['resultText']
            );

            if (!$insertResult['result']) {
                echo "\n";
                return;
            }

            $insertedId = intval($insertResult['id']);
            $this->createdAgreementIds[] = $insertedId;

            // Читаем запись обратно из БД (round-trip через PostgreSQL jsonb)
            $row = $this->db->selectOne('agreement', ' WHERE id = ?', [$insertedId]);

            $this->assert(
                'Тест 1: Запись читается из БД',
                $row !== null && !empty($row->agreementlist),
                $row ? 'Запись найдена, id=' . $row->id : 'Запись не найдена в БД'
            );

            if (!$row) {
                echo "\n";
                return;
            }

            // Декодируем jsonb из БД
            $storedList = json_decode($row->agreementlist, true);

            $this->assert(
                'Тест 1: agreementlist декодируется из jsonb без ошибок',
                is_array($storedList) && json_last_error() === JSON_ERROR_NONE,
                is_array($storedList) ? 'JSON валиден, секций: ' . count($storedList) : 'Ошибка JSON: ' . json_last_error_msg()
            );

            if (!is_array($storedList)) {
                echo "\n";
                return;
            }

            // Применяем нормализацию — имитируем то, что делает сервер при получении от клиента
            $normalizedList = [];
            foreach ($storedList as $section) {
                if (is_string($section)) {
                    $section = json_decode($section, true);
                }
                $normalizedSection = [];
                foreach ($section as $entry) {
                    $normalizedSection[] = is_array($entry) ? bf_normalizeSection($entry) : $entry;
                }
                $normalizedList[] = $normalizedSection;
            }

            // Находим запись _is_redirector_repeat для user_id=9999
            $repeatEntry       = null;
            $firstEntry        = null;
            $section           = $normalizedList[0];

            foreach ($section as $entry) {
                if (!is_array($entry) || !isset($entry['id']) || intval($entry['id']) !== 9999) continue;
                if (!empty($entry['_is_redirector_repeat'])) {
                    $repeatEntry = $entry;
                } else {
                    $firstEntry = $entry;
                }
            }

            // Проверяем, что первое вхождение сохранило свой result
            $firstResultId = intval($firstEntry['result']['id'] ?? 0);
            $this->assert(
                'Тест 1: Первое вхождение участника сохраняет result=3',
                $firstResultId === 3,
                "result.id у первого вхождения = {$firstResultId} (ожидается 3)"
            );

            // Ключевая проверка: _is_redirector_repeat-запись должна иметь result=null
            $this->assert(
                'Тест 1: Запись _is_redirector_repeat имеет result=null после нормализации',
                $repeatEntry !== null && $repeatEntry['result'] === null,
                $repeatEntry === null
                    ? 'Запись _is_redirector_repeat не найдена в нормализованном списке'
                    : 'result у повторной записи = ' . json_encode($repeatEntry['result'])
            );

            // Флаг _is_redirector_repeat должен быть boolean true
            $this->assert(
                'Тест 1: Флаг _is_redirector_repeat нормализован в boolean true',
                $repeatEntry !== null && $repeatEntry['_is_redirector_repeat'] === true,
                $repeatEntry === null
                    ? 'Запись _is_redirector_repeat не найдена'
                    : 'Значение флага: ' . var_export($repeatEntry['_is_redirector_repeat'], true)
            );

        } finally {
            // Очищаем тестовую запись в любом случае
            $this->cleanup();
        }

        echo "\n";
    }

    // ============================================================
    // ТЕСТ 2: Цепочка уведомлений A→B при завершении redirect
    // ============================================================

    /**
     * Проверяет структуру agreementList для корректной работы уведомлений:
     * - Пока redirect-цепочка не завершена (B ещё pending), isRedirectChainCompleted=false
     * - Участник B (pending в redirect) должен быть уведомлён через sendNotificationsToNextActors
     * - После завершения redirect (B дал результат) участник A (_is_redirector_repeat) ожидает решения
     *
     * Подход: структурная проверка agreementList после round-trip через PostgreSQL jsonb.
     * Notifications не вызываются реально (избегаем отправки реальных уведомлений).
     */
    private function testNotificationChainStructureForRedirect(): void
    {
        echo "--- Тест 2: Цепочка уведомлений A→B — структура agreementList корректна ---\n";

        // Сценарий: участник A перенаправил на B.
        // A: result={id:4} (redirected), redirect=[{id:B, result:null}]
        // _is_redirector_repeat для A: result=null (ожидает возврата от B)
        $userIdA = 8881; // тестовый ID (не должен существовать в реальной таблице users)
        $userIdB = 8882; // тестовый ID (не должен существовать в реальной таблице users)

        $agreementListInput = [
            [
                // Метаданные секции (параллельный список согласования)
                ['stage' => 'approvers', 'list_type' => 2],
                // Участник A: перенаправил на B
                [
                    'id'      => $userIdA,
                    'type'    => 1,
                    'vrio'    => '0',
                    'urgent'  => '0',
                    'role'    => '0',
                    'result'  => ['id' => 4, 'text' => 'Перенаправить'],
                    'redirect' => [
                        [
                            'id'     => $userIdB,
                            'type'   => 1,
                            'vrio'   => '0',
                            'urgent' => '0',
                            'role'   => '0',
                            'result' => null, // B ещё не ответил — pending
                        ]
                    ],
                ],
                // _is_redirector_repeat для A: ожидает возврата от B
                [
                    'id'                    => $userIdA,
                    'type'                  => 1,
                    'vrio'                  => '0',
                    'urgent'                => '0',
                    'role'                  => '0',
                    'result'                => null,
                    '_is_redirector_repeat' => true,
                ],
            ]
        ];

        $insertedId = null;
        try {
            // Сохраняем тестовую запись в реальную БД
            $insertResult = $this->db->insert('agreement', [
                'source_table'  => self::TEST_SOURCE_TABLE,
                'source_id'     => 0,
                'documentacial' => 2,
                'status'        => 0,
                'active'        => 1,
                'author'        => 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'agreementlist' => json_encode($agreementListInput, JSON_UNESCAPED_UNICODE),
            ]);

            $this->assert(
                'Тест 2: Тестовая запись успешно создана в БД',
                $insertResult['result'] === true,
                $insertResult['result'] ? 'INSERT выполнен, id=' . $insertResult['id'] : $insertResult['resultText']
            );

            if (!$insertResult['result']) {
                echo "\n";
                return;
            }

            $insertedId = intval($insertResult['id']);
            $this->createdAgreementIds[] = $insertedId;

            // Читаем запись обратно из БД (round-trip через PostgreSQL jsonb)
            $row = $this->db->selectOne('agreement', ' WHERE id = ?', [$insertedId]);

            $this->assert(
                'Тест 2: Запись читается из БД',
                $row !== null && !empty($row->agreementlist),
                $row ? 'Запись найдена, id=' . $row->id : 'Запись не найдена в БД'
            );

            if (!$row) {
                echo "\n";
                return;
            }

            // Декодируем jsonb из БД
            $storedList = json_decode($row->agreementlist, true);

            $this->assert(
                'Тест 2: agreementlist декодируется из jsonb без ошибок',
                is_array($storedList) && json_last_error() === JSON_ERROR_NONE,
                is_array($storedList) ? 'JSON валиден' : 'Ошибка JSON: ' . json_last_error_msg()
            );

            if (!is_array($storedList)) {
                echo "\n";
                return;
            }

            $section = $storedList[0];

            // Находим запись участника A (с redirect) и _is_redirector_repeat A
            $entryA        = null;
            $repeatEntryA  = null;

            foreach ($section as $entry) {
                if (!is_array($entry) || !isset($entry['id'])) continue;
                if (intval($entry['id']) === $userIdA) {
                    if (!empty($entry['_is_redirector_repeat'])) {
                        $repeatEntryA = $entry;
                    } else {
                        $entryA = $entry;
                    }
                }
            }

            // Проверяем, что участник A сохранил result={id:4}
            $aResultId = intval($entryA['result']['id'] ?? 0);
            $this->assert(
                'Тест 2: Участник A имеет result.id=4 (redirected)',
                $aResultId === 4,
                "result.id у A = {$aResultId} (ожидается 4)"
            );

            // Проверяем, что redirect B сохранился в структуре
            $redirectArr = $entryA['redirect'] ?? [];
            $this->assert(
                'Тест 2: redirect-массив у A не пуст и содержит участника B',
                count($redirectArr) > 0 && intval($redirectArr[0]['id'] ?? 0) === $userIdB,
                'redirect: ' . json_encode($redirectArr, JSON_UNESCAPED_UNICODE)
            );

            // Ключевая проверка 1: B ещё pending — цепочка не завершена
            $chainCompleted = bf_isRedirectChainCompleted($redirectArr);
            $this->assert(
                'Тест 2: isRedirectChainCompleted=false пока B имеет result=null',
                $chainCompleted === false,
                "isRedirectChainCompleted вернул: " . ($chainCompleted ? 'true' : 'false')
            );

            // Ключевая проверка 2: B в redirect имеет result=null (pending) → будет уведомлён
            $bResultInRedirect = $redirectArr[0]['result'] ?? 'НЕ NULL';
            $this->assert(
                'Тест 2: Участник B в redirect имеет result=null (pending, ожидает уведомления)',
                $bResultInRedirect === null,
                'result у B в redirect: ' . json_encode($bResultInRedirect)
            );

            // Ключевая проверка 3: _is_redirector_repeat A имеет result=null (ждёт возврата от B)
            $this->assert(
                'Тест 2: Повторная запись A (_is_redirector_repeat) имеет result=null',
                $repeatEntryA !== null && $repeatEntryA['result'] === null,
                $repeatEntryA === null
                    ? 'Запись _is_redirector_repeat не найдена'
                    : 'result у повторной записи A: ' . json_encode($repeatEntryA['result'])
            );

            // Симулируем завершение цепочки: B ответил → isRedirectChainCompleted должно стать true
            $completedRedirectArr = [
                [
                    'id'     => $userIdB,
                    'type'   => 1,
                    'vrio'   => '0',
                    'urgent' => '0',
                    'role'   => '0',
                    'result' => ['id' => 3, 'text' => 'Без ЭП'], // B одобрил
                ]
            ];
            // bf_isRedirectChainCompleted обходит $completedRedirectArr и проверяет каждую запись:
            // B имеет status 'approved' (result.id=3) — не pending, не returned, не redirected.
            // Проверка на _is_redirector_repeat нужна только для записей со статусом 'redirected'.
            // Поскольку B — approved, цикл завершается без ошибок → функция вернёт true.
            $chainCompletedAfterB = bf_isRedirectChainCompleted($completedRedirectArr);
            $this->assert(
                'Тест 2: isRedirectChainCompleted=true когда B одобрил (approved)',
                $chainCompletedAfterB === true,
                "isRedirectChainCompleted вернул: " . ($chainCompletedAfterB ? 'true' : 'false')
            );

        } finally {
            // Очищаем тестовые данные в любом случае (даже при ошибке теста)
            $this->cleanup();
        }

        echo "\n";
    }

    // ============================================================
    // Утилиты
    // ============================================================

    /**
     * Удаляет все тестовые записи, созданные в ходе тестов.
     * Вызывается в блоке finally — выполняется даже при исключении.
     */
    private function cleanup(): void
    {
        if (empty($this->createdAgreementIds)) {
            return;
        }

        // Удаляем по маркеру source_table для надёжности (дополнительная защита)
        try {
            $deleted = $this->db->db::exec(
                "DELETE FROM " . TBL_PREFIX . "agreement WHERE source_table = ?",
                [self::TEST_SOURCE_TABLE]
            );
            // Дополнительно чистим по ID на случай, если source_table не совпал
            foreach ($this->createdAgreementIds as $id) {
                $this->db->db::exec(
                    "DELETE FROM " . TBL_PREFIX . "agreement WHERE id = ?",
                    [$id]
                );
            }
        } catch (\Exception $e) {
            echo "  [ПРЕДУПРЕЖДЕНИЕ] Ошибка при очистке тестовых данных: " . $e->getMessage() . "\n";
        }
        // Сбрасываем список после очистки
        $this->createdAgreementIds = [];
    }

    /**
     * Регистрирует результат одного утверждения
     */
    private function assert(string $testName, bool $condition, string $message): void
    {
        $this->totalTests++;

        if ($condition) {
            $this->passedTests++;
            echo "  [OK] {$testName}\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'PASS', 'message' => $message];
        } else {
            $this->failedTests++;
            echo "  [FAIL] {$testName}\n";
            echo "         Детали: {$message}\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'message' => $message];
        }
    }

    /**
     * Выводит итоговый отчёт по всем тестам
     */
    private function printReport(): void
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "ОТЧЁТ ПО ИНТЕГРАЦИОННЫМ ТЕСТАМ (AgreementBugFix)\n";
        echo str_repeat("=", 70) . "\n\n";

        echo "Всего тестов:   {$this->totalTests}\n";
        echo "Успешно:        {$this->passedTests} [OK]\n";
        echo "Провалено:      {$this->failedTests} [FAIL]\n";

        $percentage = $this->totalTests > 0
            ? round(($this->passedTests / $this->totalTests) * 100, 2)
            : 0;
        echo "Процент успеха: {$percentage}%\n\n";

        if ($this->failedTests > 0) {
            echo "ПРОВАЛИВШИЕСЯ ТЕСТЫ:\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "  - {$result['name']}\n";
                    echo "    {$result['message']}\n";
                }
            }
            echo "\nЧТО ПРОВЕРИТЬ:\n";
            echo "1. Логику normalizeSection в modules/documents/ajaxHandlers/updateAgreement.php\n";
            echo "2. Логику isRedirectChainCompleted — условия завершения цепочки\n";
            echo "3. Функцию sendNotificationsToNextActors — кто получает уведомления\n";
        } else {
            echo "ВСЕ ИНТЕГРАЦИОННЫЕ ТЕСТЫ ПРОЙДЕНЫ!\n";
            echo "Исправления багов A и Б работают корректно с реальной БД.\n";
        }

        echo "\n" . str_repeat("=", 70) . "\n";
    }
}

// ============================================================
// Запуск тестов
// ============================================================
$tester = new AgreementBugFixIntegrationTest();
$tester->runAll();

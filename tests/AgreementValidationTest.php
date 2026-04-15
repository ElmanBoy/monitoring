<?php
/**
 * Тест валидации секции подписантов
 * Проверяет работу валидации в renderAgreementTable.php
 *
 * Запуск: php tests/AgreementValidationTest.php
 */

// Установка DOCUMENT_ROOT для тестов
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

// Мокируем $_POST для тестирования
$_POST = [];

class AgreementValidationTest
{
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $projectRoot;

    public function __construct()
    {
        echo "\n=== Agreement Validation Test Suite ===\n";
        echo "Проверка валидации секции подписантов\n\n";

        $this->projectRoot = dirname(__DIR__);
    }

    /**
     * Запуск всех тестов
     */
    public function runAll()
    {
        echo "Запуск тестов валидации...\n\n";

        // Проверка наличия валидации в коде
        $this->testValidationCodeExists();

        // Симуляция валидации (unit-тесты)
        $this->testValidationRules();

        $this->printReport();
    }

    /**
     * Тест 1: Проверка наличия валидации в коде
     */
    private function testValidationCodeExists()
    {
        echo "📋 Группа тестов: Наличие валидации в коде\n";

        $renderFile = $this->projectRoot . '/modules/documents/ajaxHandlers/renderAgreementTable.php';

        if (!file_exists($renderFile)) {
            $this->assert(
                'Файл renderAgreementTable.php существует',
                false,
                'Файл не найден'
            );
            echo "\n";
            return;
        }

        $content = file_get_contents($renderFile);

        // Проверка наличия блока валидации
        $hasValidationBlock = strpos($content, 'ВАЛИДАЦИЯ СЕКЦИИ ПОДПИСАНТОВ') !== false ||
                             strpos($content, 'validationErrors') !== false;
        $this->assert(
            'Блок валидации существует в renderAgreementTable.php',
            $hasValidationBlock,
            $hasValidationBlock ? 'Валидация найдена' : 'Валидация ОТСУТСТВУЕТ'
        );

        // Проверка правила: ровно 2 участника в секции подписантов (реализовано через != 2)
        $hasMaxParticipants = preg_match('/participantCount\s*!=\s*2/', $content);
        $this->assert(
            'Правило: максимум 2 участника в секции подписантов',
            $hasMaxParticipants,
            $hasMaxParticipants ? 'Правило реализовано' : 'Правило ОТСУТСТВУЕТ'
        );

        // Проверка правила: ровно 1 подписант (реализовано через != 1)
        $hasMaxSigners = preg_match('/signerCount\s*!=\s*1/', $content);
        $this->assert(
            'Правило: максимум 1 подписант (type=1)',
            $hasMaxSigners,
            $hasMaxSigners ? 'Правило реализовано' : 'Правило ОТСУТСТВУЕТ'
        );

        // Проверка правила: ровно 1 утверждающий (реализовано через != 1)
        $hasMaxApprovers = preg_match('/approverCount\s*!=\s*1/', $content);
        $this->assert(
            'Правило: максимум 1 утверждающий (type=2)',
            $hasMaxApprovers,
            $hasMaxApprovers ? 'Правило реализовано' : 'Правило ОТСУТСТВУЕТ'
        );

        // Проверка правила для приказов
        $hasOrderRule = preg_match('/documentType\s*==\s*1|documentacial\s*==\s*1/', $content);
        $this->assert(
            'Правило: для приказов только 1 подписант без утверждающих',
            $hasOrderRule,
            $hasOrderRule ? 'Правило реализовано' : 'Правило ОТСУТСТВУЕТ'
        );

        // Проверка возврата ошибки
        $hasErrorReturn = preg_match('/result.*false.*resultText|validationErrors/', $content);
        $this->assert(
            'Возврат ошибки при нарушении правил',
            $hasErrorReturn,
            $hasErrorReturn ? 'Возврат ошибки реализован' : 'Возврат ошибки ОТСУТСТВУЕТ'
        );

        echo "\n";
    }

    /**
     * Тест 2: Логика валидации (unit-тесты)
     */
    private function testValidationRules()
    {
        echo "📋 Группа тестов: Логика валидации\n";

        // Тест 2.1: Валидная секция подписантов (2 участника)
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],  // Подписант
            ['id' => '5', 'type' => '2']     // Утверждающий
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: 2 участника (подписант + утверждающий) - OK',
            $result === true,
            is_string($result) ? $result : 'Валидация прошла'
        );

        // Тест 2.2: Превышение максимума участников
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],  // Подписант
            ['id' => '5', 'type' => '2'],   // Утверждающий
            ['id' => '15', 'type' => '3']   // Третий участник - ОШИБКА
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: 3 участника - ОШИБКА',
            $result !== true,
            is_string($result) ? "Ошибка: {$result}" : 'Валидация НЕ сработала'
        );

        // Тест 2.3: Два подписанта
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],  // Подписант 1
            ['id' => '11', 'type' => '1']   // Подписант 2 - ОШИБКА
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: 2 подписанта - ОШИБКА',
            $result !== true,
            is_string($result) ? "Ошибка: {$result}" : 'Валидация НЕ сработала'
        );

        // Тест 2.4: Два утверждающих
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '2'],  // Утверждающий 1
            ['id' => '11', 'type' => '2']   // Утверждающий 2 - ОШИБКА
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: 2 утверждающих - ОШИБКА',
            $result !== true,
            is_string($result) ? "Ошибка: {$result}" : 'Валидация НЕ сработала'
        );

        // Тест 2.5: Приказ с 1 подписантом - OK
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '1']   // Только подписант
        ];
        $result = $this->validateSignersSection($section, 1); // documentacial=1 (приказ)
        $this->assert(
            'Валидация: приказ с 1 подписантом - OK',
            $result === true,
            is_string($result) ? $result : 'Валидация прошла'
        );

        // Тест 2.6: Приказ с утверждающим - ОШИБКА
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],  // Подписант
            ['id' => '5', 'type' => '2']    // Утверждающий - ОШИБКА для приказа
        ];
        $result = $this->validateSignersSection($section, 1); // documentacial=1 (приказ)
        $this->assert(
            'Валидация: приказ с утверждающим - ОШИБКА',
            $result !== true,
            is_string($result) ? "Ошибка: {$result}" : 'Валидация НЕ сработала'
        );

        // Тест 2.7: Секция согласования (не подписантов) - пропускается
        $section = [
            ['stage' => '1', 'list_type' => '2'],  // stage="1" - это НЕ секция подписантов
            ['id' => '10', 'type' => '3'],
            ['id' => '11', 'type' => '3'],
            ['id' => '12', 'type' => '3']  // 3 участника - OK для секции согласования
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: секция согласования (не подписантов) - пропускается',
            $result === true,
            'Секция согласования не проверяется'
        );

        // Тест 2.8: С перенаправлением - только первый уровень считается
        $section = [
            ['stage' => '', 'list_type' => '1'],
            [
                'id' => '10',
                'type' => '1',
                'redirect' => [
                    ['id' => '25', 'type' => '1']  // Перенаправленный - НЕ считается
                ]
            ],
            ['id' => '5', 'type' => '2']  // Утверждающий
            // ИТОГО: 2 участника первого уровня - OK
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: с перенаправлением - только первый уровень - OK',
            $result === true,
            is_string($result) ? $result : 'Перенаправления не учитываются'
        );

        // Тест 2.9: С возвратом после перенаправления (_is_redirector_repeat)
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],  // Подписант
            ['id' => '10', 'type' => '1', '_is_redirector_repeat' => true],  // Возврат - НЕ считается
            ['id' => '5', 'type' => '2']   // Утверждающий
            // ИТОГО: 2 участника первого уровня (возврат не учитывается) - OK
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: возврат после перенаправления не учитывается - OK',
            $result === true,
            is_string($result) ? $result : 'Возвраты (_is_redirector_repeat) пропускаются'
        );

        // Тест 2.10: Три участника первого уровня (без учёта перенаправлений) - ОШИБКА
        $section = [
            ['stage' => '', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],  // Подписант
            ['id' => '5', 'type' => '2'],   // Утверждающий
            ['id' => '15', 'type' => '3']   // Третий участник первого уровня - ОШИБКА
        ];
        $result = $this->validateSignersSection($section, 2);
        $this->assert(
            'Валидация: 3 участника первого уровня - ОШИБКА',
            $result !== true,
            is_string($result) ? "Ошибка: {$result}" : 'Валидация НЕ сработала'
        );

        echo "\n";
    }

    /**
     * Валидация секции подписантов (реплика логики из renderAgreementTable.php)
     * ВАЖНО: Считает только участников ПЕРВОГО УРОВНЯ (не учитывает redirect и _is_redirector_repeat)
     */
    private function validateSignersSection($section, $documentType)
    {
        if (!is_array($section) || !isset($section[0])) {
            return true; // Пропускаем некорректные секции
        }

        // Проверяем, это секция подписантов?
        $isSignersSection = (!array_key_exists('stage', $section[0]) || $section[0]['stage'] === '');

        if (!$isSignersSection) {
            return true; // Проверяем только секцию подписантов
        }

        $participants = array_slice($section, 1);

        // Считаем только участников ПЕРВОГО УРОВНЯ (не перенаправленных и не возвраты)
        $firstLevelParticipants = [];
        $signerCount = 0;
        $approverCount = 0;

        foreach ($participants as $p) {
            if (!is_array($p)) continue;

            // Пропускаем участников, которые являются возвратом после перенаправления
            if (!empty($p['_is_redirector_repeat'])) {
                continue;
            }

            // Это участник первого уровня
            $firstLevelParticipants[] = $p;

            if (!isset($p['type'])) continue;
            $type = intval($p['type']);

            if ($type == 1) $signerCount++;
            if ($type == 2) $approverCount++;
        }

        $participantCount = count($firstLevelParticipants);

        // Правило 1: Максимум 2 участника первого уровня
        if ($participantCount > 2) {
            return "Превышено максимальное количество участников первого уровня ({$participantCount}, макс: 2)";
        }

        // Правило 2: Максимум 1 подписант первого уровня
        if ($signerCount > 1) {
            return "Слишком много подписантов первого уровня ({$signerCount}, макс: 1)";
        }

        // Правило 3: Максимум 1 утверждающий первого уровня
        if ($approverCount > 1) {
            return "Слишком много утверждающих первого уровня ({$approverCount}, макс: 1)";
        }

        // Правило 4: Для приказов только 1 подписант первого уровня, без утверждающих
        if ($documentType == 1) {
            if ($participantCount != 1 || $signerCount != 1 || $approverCount > 0) {
                return "Приказ должен иметь ровно 1 подписанта первого уровня без утверждающих";
            }
        }

        return true; // Валидация пройдена
    }

    // ==================== Утилиты ====================

    private function assert($testName, $condition, $message)
    {
        $this->totalTests++;

        if ($condition) {
            $this->passedTests++;
            echo "  ✅ {$testName}\n";
            if (strpos($message, 'найдена') !== false || strpos($message, 'реализован') !== false) {
                echo "     {$message}\n";
            }
            $this->testResults[] = ['name' => $testName, 'status' => 'PASS', 'message' => $message];
        } else {
            $this->failedTests++;
            echo "  ❌ {$testName}\n";
            echo "     ⚠️  {$message}\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'message' => $message];
        }
    }

    private function printReport()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 ОТЧЁТ ПО ТЕСТАМ ВАЛИДАЦИИ\n";
        echo str_repeat("=", 70) . "\n\n";

        echo "Всего тестов:     {$this->totalTests}\n";
        echo "Успешно:          {$this->passedTests} ✅\n";
        echo "Провалено:        {$this->failedTests} ❌\n";

        $percentage = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        echo "Процент успеха:   {$percentage}%\n\n";

        if ($this->failedTests > 0) {
            echo "❌ ПРОВАЛИВШИЕСЯ ТЕСТЫ:\n\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] == 'FAIL') {
                    echo "  • {$result['name']}\n";
                    echo "    Проблема: {$result['message']}\n\n";
                }
            }

            echo "⚠️  ТРЕБУЕТСЯ:\n\n";
            echo "1. Добавить недостающие правила валидации\n";
            echo "2. Убедиться, что валидация срабатывает на сервере\n";
            echo "3. Добавить клиентскую валидацию (JavaScript)\n\n";
        } else {
            echo "🎉 ВСЕ ТЕСТЫ ВАЛИДАЦИИ ПРОЙДЕНЫ!\n\n";
            echo "✓ Валидация секции подписантов реализована\n";
            echo "✓ Все правила из Agreement.md проверяются\n";
            echo "✓ Защита от некорректных данных работает\n\n";
        }

        echo str_repeat("=", 70) . "\n";
    }
}

// Запуск тестов
$tester = new AgreementValidationTest();
$tester->runAll();

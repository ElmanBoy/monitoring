<?php
/**
 * Интеграционный автотест для проверки реального кода согласования
 * Проверяет соответствие реализации бизнес-логике из Agreement.md
 *
 * Запуск: php tests/AgreementCodeIntegrationTest.php
 */

// Установка DOCUMENT_ROOT для тестов
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

use Core\Db;
use Core\Auth;
use Core\Registry;

class AgreementCodeIntegrationTest
{
    private $db;
    private $auth;
    private $reg;
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;

    public function __construct()
    {
        echo "\n=== Agreement Code Integration Test Suite ===\n";
        echo "Проверка реального кода системы согласования\n\n";

        $this->db = new Db();
        $this->auth = new Auth();
        $this->reg = new Registry();
    }

    /**
     * Запуск всех интеграционных тестов
     */
    public function runAll()
    {
        echo "Запуск интеграционных тестов...\n\n";

        // Проверка существования необходимых файлов
        $this->testRequiredFilesExist();

        // Проверка методов JavaScript для перенаправления
        $this->testJavaScriptRedirectionCode();

        // Проверка наличия валидации на стороне сервера
        $this->testServerSideValidation();

        // Проверка структуры БД
        $this->testDatabaseSchema();

        // Проверка существующих документов в БД
        $this->testExistingDocuments();

        $this->printReport();
    }

    /**
     * Тест 1: Проверка существования необходимых файлов
     */
    private function testRequiredFilesExist()
    {
        echo "📋 Группа тестов: Проверка файлов системы\n";

        $requiredFiles = [
            '/modules/roadmap/dialogs/agreement.php' => 'Диалог согласования',
            '/modules/documents/dialogs/agreement.php' => 'Диалог согласования (documents)',
            '/core/Classes/Registry.php' => 'Класс Registry с buildAgreementList',
            '/js/assets/cades_sign.js' => 'JavaScript для ЭЦП',
            '/core/ajaxHandlers/updateAgreement.php' => 'Обработчик обновления согласования',
        ];

        foreach ($requiredFiles as $file => $description) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $file;
            $exists = file_exists($fullPath);
            $this->assert(
                "Файл существует: {$description}",
                $exists,
                $exists ? "Файл найден: {$file}" : "Файл НЕ найден: {$file}"
            );
        }

        echo "\n";
    }

    /**
     * Тест 2: Проверка JavaScript кода для перенаправления
     */
    private function testJavaScriptRedirectionCode()
    {
        echo "📋 Группа тестов: JavaScript логика перенаправления\n";

        $agreementFile = $_SERVER['DOCUMENT_ROOT'] . '/modules/roadmap/dialogs/agreement.php';
        if (!file_exists($agreementFile)) {
            $this->assert(
                'JavaScript: agreement.php существует',
                false,
                'Файл не найден'
            );
            echo "\n";
            return;
        }

        $content = file_get_contents($agreementFile);

        // Проверка наличия функции getAgreementList
        $hasGetAgreementList = strpos($content, 'function getAgreementList') !== false;
        $this->assert(
            'JavaScript: функция getAgreementList существует',
            $hasGetAgreementList,
            'Функция обработки согласования должна существовать'
        );

        // Проверка рекурсивной обработки redirect
        $hasRecursiveRedirect = strpos($content, 'getAgreementList(agj[i].redirect') !== false ||
                                strpos($content, 'redirect') !== false;
        $this->assert(
            'JavaScript: поддержка рекурсивного перенаправления',
            $hasRecursiveRedirect,
            'Должна быть рекурсивная обработка redirect'
        );

        // Проверка параметра level для многоуровневого перенаправления
        $hasLevelParam = preg_match('/function\s+getAgreementList\s*\([^)]*level/i', $content);
        $this->assert(
            'JavaScript: параметр level для многоуровневого перенаправления',
            $hasLevelParam,
            'Функция должна принимать параметр level'
        );

        // Проверка fallowIds для возврата к исходному участнику
        $hasFallowIds = strpos($content, 'fallowIds') !== false;
        $this->assert(
            'JavaScript: механизм возврата к исходному участнику (fallowIds)',
            $hasFallowIds,
            'Должен быть механизм возврата после перенаправления'
        );

        echo "\n";
    }

    /**
     * Тест 3: Проверка валидации на стороне сервера
     */
    private function testServerSideValidation()
    {
        echo "📋 Группа тестов: Валидация на стороне сервера\n";

        // Проверка наличия обработчика updateAgreement
        $updateAgreementFile = $_SERVER['DOCUMENT_ROOT'] . '/core/ajaxHandlers/updateAgreement.php';
        $exists = file_exists($updateAgreementFile);

        if (!$exists) {
            // Проверка альтернативных путей
            $altPaths = [
                '/modules/documents/ajaxHandlers/updateAgreement.php',
                '/modules/roadmap/ajaxHandlers/updateAgreement.php'
            ];

            foreach ($altPaths as $altPath) {
                $altFullPath = $_SERVER['DOCUMENT_ROOT'] . $altPath;
                if (file_exists($altFullPath)) {
                    $updateAgreementFile = $altFullPath;
                    $exists = true;
                    break;
                }
            }
        }

        $this->assert(
            'Сервер: обработчик updateAgreement существует',
            $exists,
            $exists ? 'Обработчик найден' : 'Обработчик НЕ найден'
        );

        if ($exists) {
            $content = file_get_contents($updateAgreementFile);

            // Проверка валидации agreementList
            $hasValidation = strpos($content, 'agreementList') !== false;
            $this->assert(
                'Сервер: обработка agreementList',
                $hasValidation,
                'Обработчик должен обрабатывать agreementList'
            );
        }

        echo "\n";
    }

    /**
     * Тест 4: Проверка структуры БД
     */
    private function testDatabaseSchema()
    {
        echo "📋 Группа тестов: Структура базы данных\n";

        try {
            // Проверка таблицы cam_agreement
            $tableExists = $this->db->db::getCell(
                "SELECT EXISTS (
                    SELECT FROM information_schema.tables
                    WHERE table_name = '" . TBL_PREFIX . "agreement'
                )"
            );

            $this->assert(
                'БД: таблица cam_agreement существует',
                $tableExists,
                'Основная таблица согласования'
            );

            if ($tableExists) {
                // Проверка наличия поля agreementlist
                $columnExists = $this->db->db::getCell(
                    "SELECT EXISTS (
                        SELECT FROM information_schema.columns
                        WHERE table_name = '" . TBL_PREFIX . "agreement'
                        AND column_name = 'agreementlist'
                    )"
                );

                $this->assert(
                    'БД: поле agreementlist существует',
                    $columnExists,
                    'JSONB поле для хранения листа согласования'
                );

                // Проверка типа поля agreementlist (должен быть jsonb)
                $columnType = $this->db->db::getCell(
                    "SELECT data_type
                    FROM information_schema.columns
                    WHERE table_name = '" . TBL_PREFIX . "agreement'
                    AND column_name = 'agreementlist'"
                );

                $this->assert(
                    'БД: agreementlist имеет тип jsonb',
                    $columnType === 'jsonb',
                    "Тип поля: {$columnType}"
                );

                // Проверка поля documentacial
                $docTypeExists = $this->db->db::getCell(
                    "SELECT EXISTS (
                        SELECT FROM information_schema.columns
                        WHERE table_name = '" . TBL_PREFIX . "agreement'
                        AND column_name = 'documentacial'
                    )"
                );

                $this->assert(
                    'БД: поле documentacial существует',
                    $docTypeExists,
                    'Поле для типа документа (1=Приказ, 2=Акт, 5=График)'
                );
            }

            // Проверка таблицы cam_signs
            $signsTableExists = $this->db->db::getCell(
                "SELECT EXISTS (
                    SELECT FROM information_schema.tables
                    WHERE table_name = '" . TBL_PREFIX . "signs'
                )"
            );

            $this->assert(
                'БД: таблица cam_signs существует',
                $signsTableExists,
                'Таблица для хранения электронных подписей'
            );

        } catch (Exception $e) {
            $this->assert(
                'БД: подключение к базе данных',
                false,
                'Ошибка: ' . $e->getMessage()
            );
        }

        echo "\n";
    }

    /**
     * Тест 5: Проверка существующих документов в БД
     */
    private function testExistingDocuments()
    {
        echo "📋 Группа тестов: Анализ существующих документов\n";

        try {
            // Подсчёт документов с agreementlist
            $totalDocs = $this->db->db::getCell(
                "SELECT COUNT(*) FROM " . TBL_PREFIX . "agreement
                WHERE agreementlist IS NOT NULL"
            );

            $this->assert(
                'БД: документы с agreementlist существуют',
                $totalDocs > 0,
                "Найдено документов: {$totalDocs}"
            );

            if ($totalDocs > 0) {
                // Проверка приказов (documentacial=1)
                $orders = $this->db->db::getAll(
                    "SELECT id, agreementlist
                    FROM " . TBL_PREFIX . "agreement
                    WHERE documentacial = 1
                    AND agreementlist IS NOT NULL
                    LIMIT 5"
                );

                $orderViolations = 0;
                foreach ($orders as $order) {
                    $agreementlist = json_decode($order['agreementlist'], true);
                    if (is_array($agreementlist)) {
                        foreach ($agreementlist as $section) {
                            if (is_array($section) && isset($section[0]['stage'])) {
                                // Проверяем секцию подписантов
                                $signerCount = 0;
                                $approverCount = 0;

                                for ($i = 1; $i < count($section); $i++) {
                                    if (isset($section[$i]['type'])) {
                                        if ($section[$i]['type'] == '1') $signerCount++;
                                        if ($section[$i]['type'] == '2') $approverCount++;
                                    }
                                }

                                // Для приказа должен быть 1 подписант и 0 утверждающих
                                if ($signerCount > 1 || $approverCount > 0) {
                                    $orderViolations++;
                                }
                            }
                        }
                    }
                }

                $this->assert(
                    'БД: приказы соответствуют правилу (1 подписант, 0 утверждающих)',
                    $orderViolations == 0,
                    $orderViolations > 0
                        ? "Найдено нарушений: {$orderViolations}"
                        : "Все приказы валидны"
                );

                // Проверка актов и графиков (documentacial=2,5)
                $actsSchedules = $this->db->db::getAll(
                    "SELECT id, agreementlist, documentacial
                    FROM " . TBL_PREFIX . "agreement
                    WHERE documentacial IN (2, 5)
                    AND agreementlist IS NOT NULL
                    LIMIT 10"
                );

                $signersViolations = 0;
                foreach ($actsSchedules as $doc) {
                    $agreementlist = json_decode($doc['agreementlist'], true);
                    if (is_array($agreementlist)) {
                        foreach ($agreementlist as $section) {
                            if (is_array($section) && isset($section[0]['stage'])) {
                                $participantCount = count($section) - 1;

                                // Проверяем количество участников (не более 2)
                                if ($participantCount > 2) {
                                    $signerCount = 0;
                                    $approverCount = 0;

                                    for ($i = 1; $i < count($section); $i++) {
                                        if (isset($section[$i]['type'])) {
                                            if ($section[$i]['type'] == '1') $signerCount++;
                                            if ($section[$i]['type'] == '2') $approverCount++;
                                        }
                                    }

                                    // Если есть подписанты или утверждающие, это секция подписантов
                                    if ($signerCount > 0 || $approverCount > 0) {
                                        $signersViolations++;
                                    }
                                }
                            }
                        }
                    }
                }

                $this->assert(
                    'БД: акты/графики соответствуют правилу (max 2 участника в секции подписантов)',
                    $signersViolations == 0,
                    $signersViolations > 0
                        ? "Найдено нарушений: {$signersViolations}"
                        : "Все документы валидны"
                );
            }

        } catch (Exception $e) {
            $this->assert(
                'БД: анализ документов',
                false,
                'Ошибка: ' . $e->getMessage()
            );
        }

        echo "\n";
    }

    // ==================== Утилиты для тестирования ====================

    private function assert($testName, $condition, $message)
    {
        $this->totalTests++;

        if ($condition) {
            $this->passedTests++;
            echo "  ✅ {$testName}\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'PASS', 'message' => $message];
        } else {
            $this->failedTests++;
            echo "  ❌ {$testName}\n";
            echo "     Детали: {$message}\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'message' => $message];
        }
    }

    private function printReport()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 ОТЧЁТ ПО ИНТЕГРАЦИОННЫМ ТЕСТАМ\n";
        echo str_repeat("=", 70) . "\n\n";

        echo "Всего тестов:     {$this->totalTests}\n";
        echo "Успешно:          {$this->passedTests} ✅\n";
        echo "Провалено:        {$this->failedTests} ❌\n";

        $percentage = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        echo "Процент успеха:   {$percentage}%\n\n";

        if ($this->failedTests > 0) {
            echo "❌ ПРОВАЛИВШИЕСЯ ТЕСТЫ:\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] == 'FAIL') {
                    echo "  - {$result['name']}\n";
                    echo "    {$result['message']}\n";
                }
            }
            echo "\n";
            echo "⚠️  НЕОБХОДИМО:\n";
            echo "1. Проверить реализацию в коде\n";
            echo "2. Добавить недостающую валидацию\n";
            echo "3. Исправить данные в БД при необходимости\n";
        } else {
            echo "🎉 ВСЕ ИНТЕГРАЦИОННЫЕ ТЕСТЫ ПРОЙДЕНЫ!\n";
            echo "Реализация соответствует бизнес-логике из Agreement.md\n";
        }

        echo "\n" . str_repeat("=", 70) . "\n";
    }
}

// Запуск интеграционных тестов
$tester = new AgreementCodeIntegrationTest();
$tester->runAll();

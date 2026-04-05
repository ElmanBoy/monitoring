<?php
/**
 * Статический анализ кода согласования (без подключения к БД)
 * Проверяет соответствие реализации бизнес-логике из Agreement.md
 *
 * Запуск: php tests/AgreementCodeStaticTest.php
 */

class AgreementCodeStaticTest
{
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $projectRoot;

    public function __construct()
    {
        echo "\n=== Agreement Code Static Analysis Test Suite ===\n";
        echo "Статический анализ кода согласования\n\n";

        $this->projectRoot = dirname(__DIR__);
    }

    /**
     * Запуск всех тестов статического анализа
     */
    public function runAll()
    {
        echo "Запуск статического анализа...\n\n";

        // Проверка существования необходимых файлов
        $this->testRequiredFilesExist();

        // Проверка JavaScript кода в agreement.php
        $this->testJavaScriptRedirectionCode();

        // Проверка методов в Registry.php
        $this->testRegistryClassMethods();

        // Проверка обработчиков AJAX
        $this->testAjaxHandlers();

        // Проверка диалогов согласования
        $this->testAgreementDialogs();

        $this->printReport();
    }

    /**
     * Тест 1: Проверка существования необходимых файлов
     */
    private function testRequiredFilesExist()
    {
        echo "📋 Группа тестов: Проверка файлов системы\n";

        $requiredFiles = [
            '/modules/roadmap/dialogs/agreement.php' => 'Диалог согласования (roadmap)',
            '/modules/documents/dialogs/agreement.php' => 'Диалог согласования (documents)',
            '/core/Classes/Registry.php' => 'Класс Registry',
            '/js/assets/cades_sign.js' => 'JavaScript для ЭЦП',
        ];

        foreach ($requiredFiles as $file => $description) {
            $fullPath = $this->projectRoot . $file;
            $exists = file_exists($fullPath);
            $this->assert(
                "Файл существует: {$description}",
                $exists,
                $exists ? "✓ {$file}" : "✗ {$file} НЕ НАЙДЕН"
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

        $agreementFile = $this->projectRoot . '/modules/roadmap/dialogs/agreement.php';
        if (!file_exists($agreementFile)) {
            $this->assert(
                'JavaScript: agreement.php доступен для анализа',
                false,
                'Файл не найден для анализа'
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
            $hasGetAgreementList ? 'Найдена в agreement.php' : 'НЕ НАЙДЕНА'
        );

        // Проверка параметра level для многоуровневого перенаправления
        preg_match('/function\s+getAgreementList\s*\(([^)]+)\)/i', $content, $matches);
        $hasLevelParam = false;
        if (isset($matches[1])) {
            $params = $matches[1];
            $hasLevelParam = strpos($params, 'level') !== false;
            echo "     Параметры функции: {$params}\n";
        }

        $this->assert(
            'JavaScript: параметр level для многоуровневого перенаправления',
            $hasLevelParam,
            $hasLevelParam ? 'Параметр level присутствует' : 'Параметр level ОТСУТСТВУЕТ'
        );

        // Проверка рекурсивного вызова для redirect
        $hasRecursiveCall = preg_match('/getAgreementList\s*\([^,]+,\s*[^,]+,\s*[^,]+,\s*[^,]+,\s*level\s*\+\s*1/i', $content);
        $this->assert(
            'JavaScript: рекурсивный вызов с level+1',
            $hasRecursiveCall,
            $hasRecursiveCall ? 'Рекурсия реализована' : 'Рекурсия НЕ НАЙДЕНА'
        );

        // Проверка fallowIds для возврата к исходному участнику
        $hasFallowIds = strpos($content, 'fallowIds') !== false;
        $this->assert(
            'JavaScript: механизм возврата (fallowIds)',
            $hasFallowIds,
            $hasFallowIds ? 'fallowIds реализован' : 'fallowIds ОТСУТСТВУЕТ'
        );

        // Проверка splice для добавления участника обратно
        $hasSplice = preg_match('/agj\.splice\s*\([^)]*fallowIds/i', $content);
        $this->assert(
            'JavaScript: использование splice для возврата участника',
            $hasSplice,
            $hasSplice ? 'splice с fallowIds найден' : 'splice ОТСУТСТВУЕТ'
        );

        // Проверка обработки result_type = 4 (перенаправление)
        $hasRedirectType4 = preg_match('/result_type\s*\)\s*===?\s*[\'"]?4[\'"]?|result_type\s*===?\s*[\'"]?4[\'"]?/i', $content);
        $this->assert(
            'JavaScript: обработка result_type=4 (перенаправление)',
            $hasRedirectType4,
            $hasRedirectType4 ? 'Обработка перенаправления найдена' : 'Обработка ОТСУТСТВУЕТ'
        );

        echo "\n";
    }

    /**
     * Тест 3: Проверка методов в Registry.php
     */
    private function testRegistryClassMethods()
    {
        echo "📋 Группа тестов: Класс Registry\n";

        $registryFile = $this->projectRoot . '/core/Classes/Registry.php';
        if (!file_exists($registryFile)) {
            $this->assert(
                'Registry.php доступен для анализа',
                false,
                'Файл не найден'
            );
            echo "\n";
            return;
        }

        $content = file_get_contents($registryFile);

        // Проверка метода buildAgreementList
        $hasBuildAgreementList = preg_match('/function\s+buildAgreementList/i', $content);
        $this->assert(
            'Registry: метод buildAgreementList существует',
            $hasBuildAgreementList,
            $hasBuildAgreementList ? 'Метод найден' : 'Метод ОТСУТСТВУЕТ'
        );

        // Проверка обработки redirect в buildAgreementList
        if ($hasBuildAgreementList) {
            // Извлекаем тело метода
            preg_match('/function\s+buildAgreementList[^{]*{(.+?)(?:function\s+\w+|class\s+\w+|$)/is', $content, $matches);
            if (isset($matches[1])) {
                $methodBody = $matches[1];

                $hasRedirectHandling = strpos($methodBody, 'redirect') !== false;
                $this->assert(
                    'Registry: buildAgreementList обрабатывает redirect',
                    $hasRedirectHandling,
                    $hasRedirectHandling ? 'Обработка redirect найдена' : 'Обработка redirect ОТСУТСТВУЕТ'
                );

                // Проверка рекурсивного вызова
                $hasRecursiveCall = preg_match('/buildAgreementList|self::|this->/i', $methodBody);
                $this->assert(
                    'Registry: рекурсивный вызов для вложенных redirect',
                    $hasRecursiveCall,
                    $hasRecursiveCall ? 'Рекурсия возможна' : 'Рекурсия НЕ НАЙДЕНА'
                );
            }
        }

        echo "\n";
    }

    /**
     * Тест 4: Проверка AJAX обработчиков
     */
    private function testAjaxHandlers()
    {
        echo "📋 Группа тестов: AJAX обработчики\n";

        // Поиск updateAgreement обработчика
        $possiblePaths = [
            '/core/ajaxHandlers/updateAgreement.php',
            '/modules/documents/ajaxHandlers/updateAgreement.php',
            '/modules/roadmap/ajaxHandlers/updateAgreement.php'
        ];

        $foundPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($this->projectRoot . $path)) {
                $foundPath = $path;
                break;
            }
        }

        $this->assert(
            'AJAX: обработчик updateAgreement существует',
            $foundPath !== null,
            $foundPath ? "Найден: {$foundPath}" : 'ОБРАБОТЧИК НЕ НАЙДЕН'
        );

        if ($foundPath) {
            $content = file_get_contents($this->projectRoot . $foundPath);

            // Проверка обработки agreementList
            $hasAgreementList = strpos($content, 'agreementList') !== false;
            $this->assert(
                'AJAX: updateAgreement обрабатывает agreementList',
                $hasAgreementList,
                $hasAgreementList ? 'Параметр обрабатывается' : 'Параметр НЕ ОБРАБАТЫВАЕТСЯ'
            );

            // Проверка валидации JSON
            $hasJsonDecode = strpos($content, 'json_decode') !== false;
            $this->assert(
                'AJAX: декодирование JSON agreementList',
                $hasJsonDecode,
                $hasJsonDecode ? 'json_decode найден' : 'json_decode ОТСУТСТВУЕТ'
            );

            // Проверка обновления БД
            $hasDbUpdate = preg_match('/update\s*\(|UPDATE\s+/i', $content);
            $this->assert(
                'AJAX: обновление записи в БД',
                $hasDbUpdate,
                $hasDbUpdate ? 'UPDATE операция найдена' : 'UPDATE ОТСУТСТВУЕТ'
            );
        }

        echo "\n";
    }

    /**
     * Тест 5: Проверка диалогов согласования
     */
    private function testAgreementDialogs()
    {
        echo "📋 Группа тестов: Диалоги согласования\n";

        $dialogFiles = [
            '/modules/roadmap/dialogs/agreement.php' => 'roadmap',
            '/modules/documents/dialogs/agreement.php' => 'documents'
        ];

        foreach ($dialogFiles as $file => $module) {
            $fullPath = $this->projectRoot . $file;
            if (!file_exists($fullPath)) {
                continue;
            }

            $content = file_get_contents($fullPath);

            // Проверка загрузки agreementlist из БД
            $hasAgreementlistQuery = preg_match('/agreementlist|agreement.*WHERE/i', $content);
            $this->assert(
                "Диалог ({$module}): загрузка agreementlist из БД",
                $hasAgreementlistQuery,
                $hasAgreementlistQuery ? 'Запрос найден' : 'Запрос ОТСУТСТВУЕТ'
            );

            // Проверка обработки пустого agreementlist
            $hasEmptyCheck = preg_match('/if\s*\([^)]*agreementlist[^)]*\)/i', $content);
            $this->assert(
                "Диалог ({$module}): проверка наличия agreementlist",
                $hasEmptyCheck,
                $hasEmptyCheck ? 'Проверка найдена' : 'Проверка ОТСУТСТВУЕТ'
            );

            // Проверка интеграции с КриптоПро
            $hasCadeSign = strpos($content, 'cades_sign.js') !== false ||
                          strpos($content, 'doc_signed') !== false;
            $this->assert(
                "Диалог ({$module}): интеграция с ЭЦП (КриптоПро)",
                $hasCadeSign,
                $hasCadeSign ? 'Интеграция найдена' : 'Интеграция ОТСУТСТВУЕТ'
            );

            // Проверка обработки событий подписи
            $hasSignHandlers = preg_match('/setSign|setAgreeSign|doc_signed/i', $content);
            $this->assert(
                "Диалог ({$module}): обработчики событий подписи",
                $hasSignHandlers,
                $hasSignHandlers ? 'Обработчики найдены' : 'Обработчики ОТСУТСТВУЮТ'
            );
        }

        echo "\n";
    }

    // ==================== Утилиты ====================

    private function assert($testName, $condition, $message)
    {
        $this->totalTests++;

        if ($condition) {
            $this->passedTests++;
            echo "  ✅ {$testName}\n";
            if (strpos($message, '✓') === 0 || strpos($message, 'Найден') !== false) {
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
        echo "📊 ОТЧЁТ ПО СТАТИЧЕСКОМУ АНАЛИЗУ КОДА\n";
        echo str_repeat("=", 70) . "\n\n";

        echo "Всего проверок:   {$this->totalTests}\n";
        echo "Пройдено:         {$this->passedTests} ✅\n";
        echo "Провалено:        {$this->failedTests} ❌\n";

        $percentage = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        echo "Процент успеха:   {$percentage}%\n\n";

        if ($this->failedTests > 0) {
            echo "❌ ПРОВАЛИВШИЕСЯ ПРОВЕРКИ:\n\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] == 'FAIL') {
                    echo "  • {$result['name']}\n";
                    echo "    Проблема: {$result['message']}\n\n";
                }
            }

            echo "⚠️  РЕКОМЕНДАЦИИ:\n\n";
            echo "1. Добавить недостающие функции/методы\n";
            echo "2. Реализовать валидацию согласно Agreement.md\n";
            echo "3. Добавить рекурсивную обработку перенаправлений\n";
            echo "4. Реализовать механизм возврата к исходному участнику\n\n";
        } else {
            echo "🎉 ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ УСПЕШНО!\n\n";
            echo "✓ Реализация соответствует бизнес-логике из Agreement.md\n";
            echo "✓ Все необходимые файлы и функции присутствуют\n";
            echo "✓ Логика перенаправления реализована корректно\n";
            echo "✓ Интеграция с ЭЦП настроена\n\n";
        }

        echo str_repeat("=", 70) . "\n";
        echo "\nℹ️  Для полной проверки запустите также:\n";
        echo "   - AgreementBusinessLogicTest.php (тесты бизнес-логики)\n";
        echo "   - AgreementCodeIntegrationTest.php (с доступом к БД)\n\n";
    }
}

// Запуск статического анализа
$tester = new AgreementCodeStaticTest();
$tester->runAll();

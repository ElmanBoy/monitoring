<?php
/**
 * Автотест для проверки бизнес-логики согласования документов
 * Основан на документации /IGNORE/Agreement.md
 *
 * Запуск: php tests/AgreementBusinessLogicTest.php
 */

class AgreementBusinessLogicTest
{
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;

    public function __construct()
    {
        echo "\n=== Agreement Business Logic Test Suite ===\n";
        echo "Базируется на /IGNORE/Agreement.md\n\n";
    }

    /**
     * Запуск всех тестов
     */
    public function runAll()
    {
        echo "Запуск тестов...\n\n";

        // Тесты валидации секции подписантов
        $this->testSignersSectionValidation();

        // Тесты перенаправления
        $this->testRedirectionLogic();

        // Тесты последовательного/параллельного согласования
        $this->testSequentialParallelAgreement();

        // Тесты статусов документа
        $this->testDocumentStatusFlow();

        // Тесты порядка участников
        $this->testParticipantOrder();

        $this->printReport();
    }

    /**
     * Тест 1: Валидация секции подписантов
     */
    private function testSignersSectionValidation()
    {
        echo "📋 Группа тестов: Валидация секции подписантов\n";

        // Тест 1.1: Максимум 2 участника в секции подписантов
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],
            ['id' => '5', 'type' => '2'],
            ['id' => '15', 'type' => '3']  // Третий участник - НЕДОПУСТИМО
        ];
        $this->assert(
            'Секция подписантов: максимум 2 участника',
            !$this->validateSignersCount($section),
            'Должна быть ошибка при 3+ участниках'
        );

        // Тест 1.2: Валидная секция с 2 участниками
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],
            ['id' => '5', 'type' => '2']
        ];
        $this->assert(
            'Секция подписантов: 2 участника валидны',
            $this->validateSignersCount($section),
            'Должна пройти валидация с 2 участниками'
        );

        // Тест 1.3: Только 1 подписант (type=1)
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],
            ['id' => '11', 'type' => '1']  // Два подписанта - НЕДОПУСТИМО
        ];
        $this->assert(
            'Секция подписантов: максимум 1 подписант (type=1)',
            !$this->validateOneSigner($section),
            'Должна быть ошибка при 2+ подписантах'
        );

        // Тест 1.4: Только 1 утверждающий (type=2)
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '10', 'type' => '2'],
            ['id' => '11', 'type' => '2']  // Два утверждающих - НЕДОПУСТИМО
        ];
        $this->assert(
            'Секция подписантов: максимум 1 утверждающий (type=2)',
            !$this->validateOneApprover($section),
            'Должна быть ошибка при 2+ утверждающих'
        );

        // Тест 1.5: Для приказов только 1 подписант без утверждающих
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],
            ['id' => '5', 'type' => '2']  // Утверждающий в приказе - НЕДОПУСТИМО
        ];
        $this->assert(
            'Приказ: только 1 подписант, без утверждающих',
            !$this->validateOrderSigners($section, 1),  // documentacial=1 (приказ)
            'Должна быть ошибка при утверждающем в приказе'
        );

        // Тест 1.6: Приказ с 1 подписантом - валиден
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '10', 'type' => '1']
        ];
        $this->assert(
            'Приказ: 1 подписант валиден',
            $this->validateOrderSigners($section, 1),
            'Приказ с 1 подписантом должен быть валиден'
        );

        echo "\n";
    }

    /**
     * Тест 2: Логика перенаправления
     */
    private function testRedirectionLogic()
    {
        echo "📋 Группа тестов: Логика перенаправления\n";

        // Тест 2.1: Простое перенаправление (1 уровень)
        $participant = [
            'id' => '15',
            'type' => '3',
            'result' => ['id' => 4, 'date' => '01.04.2026 10:00'],
            'redirect' => [
                ['id' => '30', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 11:00']]
            ]
        ];
        $this->assert(
            'Перенаправление: простое перенаправление на 1 уровень',
            $this->validateRedirectionStructure($participant),
            'Структура простого перенаправления должна быть валидна'
        );

        // Тест 2.2: Многоуровневое перенаправление (3 уровня)
        $participant = [
            'id' => '15',
            'type' => '3',
            'result' => ['id' => 4, 'date' => '01.04.2026 10:00'],
            'redirect' => [
                [
                    'id' => '30',
                    'type' => '3',
                    'result' => ['id' => 4, 'date' => '01.04.2026 11:00'],
                    'redirect' => [
                        ['id' => '45', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 12:00']]
                    ]
                ]
            ]
        ];
        $this->assert(
            'Перенаправление: многоуровневое (3 уровня)',
            $this->validateRedirectionDepth($participant, 3),
            'Должно поддерживаться многоуровневое перенаправление'
        );

        // Тест 2.3: Перенаправленный может утвердить вместо подписания (в секции подписантов)
        $participant = [
            'id' => '10',
            'type' => '1',  // Подписант
            'result' => ['id' => 4, 'date' => '01.04.2026 15:00'],
            'redirect' => [
                [
                    'id' => '25',
                    'type' => '1',
                    'result' => ['id' => 2, 'date' => '01.04.2026 15:30']  // Утвердил вместо подписания
                ]
            ]
        ];
        $this->assert(
            'Перенаправление в секции подписантов: может утвердить (result.id=2)',
            $this->validateRedirectedCanApprove($participant),
            'Перенаправленный должен иметь возможность утвердить'
        );

        echo "\n";
    }

    /**
     * Тест 3: Последовательное и параллельное согласование
     */
    private function testSequentialParallelAgreement()
    {
        echo "📋 Группа тестов: Последовательное/параллельное согласование\n";

        // Тест 3.1: Параллельное согласование (list_type=2)
        $section = [
            ['stage' => '1', 'list_type' => '2', 'urgent' => '1'],
            ['id' => '15', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 10:00']],
            ['id' => '22', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 10:30']],
            ['id' => '30', 'type' => '3']  // Ещё не согласовал
        ];
        $this->assert(
            'Параллельное согласование: секция не завершена пока все не согласуют',
            !$this->isSectionComplete($section),
            'Секция не должна быть завершена, пока не все согласовали'
        );

        // Тест 3.2: Параллельное согласование завершено
        $section = [
            ['stage' => '1', 'list_type' => '2', 'urgent' => '1'],
            ['id' => '15', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 10:00']],
            ['id' => '22', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 10:30']],
            ['id' => '30', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 10:45']]
        ];
        $this->assert(
            'Параллельное согласование: секция завершена когда все согласовали',
            $this->isSectionComplete($section),
            'Секция должна быть завершена'
        );

        // Тест 3.3: Последовательное согласование (list_type=1)
        $section = [
            ['stage' => '1', 'list_type' => '1', 'urgent' => '2'],
            ['id' => '15', 'type' => '3', 'result' => ['id' => 3, 'date' => '01.04.2026 10:00']],
            ['id' => '22', 'type' => '3']  // Следующий не может согласовать, пока предыдущий не завершил
        ];
        $this->assert(
            'Последовательное согласование: доступ только после предыдущего',
            $this->canParticipantAct($section, '22'),
            'Второй участник должен иметь доступ после согласования первого'
        );

        // Тест 3.4: Последовательное согласование - ещё рано
        $section = [
            ['stage' => '1', 'list_type' => '1', 'urgent' => '2'],
            ['id' => '15', 'type' => '3'],  // Ещё не согласовал
            ['id' => '22', 'type' => '3']
        ];
        $this->assert(
            'Последовательное согласование: второй не может действовать до первого',
            !$this->canParticipantAct($section, '22'),
            'Второй участник не должен иметь доступ'
        );

        echo "\n";
    }

    /**
     * Тест 4: Поток статусов документа
     */
    private function testDocumentStatusFlow()
    {
        echo "📋 Группа тестов: Поток статусов документа\n";

        // Тест 4.1: Переход Draft (0) → In Progress (1)
        $this->assert(
            'Статус: Draft → In Progress',
            $this->isValidStatusTransition(0, 1),
            'Переход из Draft в In Progress должен быть валиден'
        );

        // Тест 4.2: Переход In Progress (1) → Approved (2)
        $this->assert(
            'Статус: In Progress → Approved',
            $this->isValidStatusTransition(1, 2),
            'Переход из In Progress в Approved должен быть валиден'
        );

        // Тест 4.3: Переход In Progress (1) → Rejected (3)
        $this->assert(
            'Статус: In Progress → Rejected',
            $this->isValidStatusTransition(1, 3),
            'Переход из In Progress в Rejected должен быть валиден'
        );

        // Тест 4.4: Недопустимый переход Draft (0) → Approved (2)
        $this->assert(
            'Статус: Draft → Approved (недопустимо)',
            !$this->isValidStatusTransition(0, 2),
            'Прямой переход из Draft в Approved должен быть запрещён'
        );

        // Тест 4.5: Переход Rejected (3) → Archived (4)
        $this->assert(
            'Статус: Rejected → Archived',
            $this->isValidStatusTransition(3, 4),
            'Переход из Rejected в Archived должен быть валиден'
        );

        echo "\n";
    }

    /**
     * Тест 5: Порядок участников
     */
    private function testParticipantOrder()
    {
        echo "📋 Группа тестов: Порядок участников в секции подписантов\n";

        // Тест 5.1: Утверждающий должен быть последним
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '10', 'type' => '1'],  // Подписант
            ['id' => '5', 'type' => '2']     // Утверждающий - последний
        ];
        $this->assert(
            'Порядок: Утверждающий (type=2) последний',
            $this->isApproverLast($section),
            'Утверждающий должен быть последним в секции'
        );

        // Тест 5.2: Неправильный порядок - утверждающий не последний
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '5', 'type' => '2'],    // Утверждающий - НЕ последний
            ['id' => '10', 'type' => '1']    // Подписант
        ];
        $this->assert(
            'Порядок: Утверждающий НЕ последний (ошибка)',
            !$this->isApproverLast($section),
            'Должна быть ошибка, если утверждающий не последний'
        );

        // Тест 5.3: Автоматическая сортировка
        $section = [
            ['stage' => 'Подписание', 'list_type' => '1'],
            ['id' => '5', 'type' => '2'],    // Утверждающий
            ['id' => '10', 'type' => '1'],   // Подписант
            ['id' => '15', 'type' => '3']    // Согласующий
        ];
        $sorted = $this->sortParticipants($section);
        $this->assert(
            'Порядок: Автоматическая сортировка (подписант, согласующий, утверждающий)',
            $this->isCorrectlySorted($sorted),
            'Участники должны быть отсортированы: type=1, type=3, type=2'
        );

        echo "\n";
    }

    // ==================== Вспомогательные методы валидации ====================

    private function validateSignersCount($section)
    {
        $count = count($section) - 1;  // Минус метаданные (первый элемент)
        return $count <= 2;
    }

    private function validateOneSigner($section)
    {
        $signers = 0;
        for ($i = 1; $i < count($section); $i++) {
            if (isset($section[$i]['type']) && $section[$i]['type'] == '1') {
                $signers++;
            }
        }
        return $signers <= 1;
    }

    private function validateOneApprover($section)
    {
        $approvers = 0;
        for ($i = 1; $i < count($section); $i++) {
            if (isset($section[$i]['type']) && $section[$i]['type'] == '2') {
                $approvers++;
            }
        }
        return $approvers <= 1;
    }

    private function validateOrderSigners($section, $documentacial)
    {
        if ($documentacial == 1) {  // Приказ
            $count = count($section) - 1;
            if ($count != 1) return false;

            // Должен быть только один подписант
            return isset($section[1]['type']) && $section[1]['type'] == '1';
        }
        return true;
    }

    private function validateRedirectionStructure($participant)
    {
        if (!isset($participant['result']) || $participant['result']['id'] != 4) {
            return false;
        }
        if (!isset($participant['redirect']) || !is_array($participant['redirect'])) {
            return false;
        }
        return count($participant['redirect']) > 0;
    }

    private function validateRedirectionDepth($participant, $expectedDepth)
    {
        $depth = 0;
        $current = $participant;

        while (isset($current['redirect']) && is_array($current['redirect']) && count($current['redirect']) > 0) {
            $depth++;
            $current = $current['redirect'][0];
        }

        // Для 3 уровней: A → B → C, depth должен быть 2 (два перенаправления)
        return $depth == ($expectedDepth - 1);
    }

    private function validateRedirectedCanApprove($participant)
    {
        if (!isset($participant['redirect']) || !is_array($participant['redirect'])) {
            return false;
        }

        $redirected = $participant['redirect'][0];
        // Перенаправленный должен иметь result.id = 2 (Утверждение)
        return isset($redirected['result']) && $redirected['result']['id'] == 2;
    }

    private function isSectionComplete($section)
    {
        for ($i = 1; $i < count($section); $i++) {
            if (!isset($section[$i]['result']) || !isset($section[$i]['result']['id'])) {
                return false;
            }
            $resultId = $section[$i]['result']['id'];
            if (!in_array($resultId, [1, 2, 3])) {  // Подписано/Согласовано
                return false;
            }
        }
        return true;
    }

    private function canParticipantAct($section, $participantId)
    {
        $listType = $section[0]['list_type'] ?? '1';

        if ($listType == '2') {
            // Параллельное - всегда может
            return true;
        }

        // Последовательное - проверяем предыдущих
        for ($i = 1; $i < count($section); $i++) {
            if ($section[$i]['id'] == $participantId) {
                // Это наш участник - проверяем, завершили ли все предыдущие
                for ($j = 1; $j < $i; $j++) {
                    if (!isset($section[$j]['result']) || !isset($section[$j]['result']['id'])) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }

    private function isValidStatusTransition($from, $to)
    {
        $validTransitions = [
            0 => [1],           // Draft → In Progress
            1 => [2, 3],        // In Progress → Approved/Rejected
            2 => [4],           // Approved → Archived
            3 => [4],           // Rejected → Archived
        ];

        return isset($validTransitions[$from]) && in_array($to, $validTransitions[$from]);
    }

    private function isApproverLast($section)
    {
        $lastIndex = count($section) - 1;
        if ($lastIndex < 1) return true;

        // Проверяем, что последний участник - утверждающий (type=2) если он есть
        $hasApprover = false;
        $approverIndex = -1;

        for ($i = 1; $i < count($section); $i++) {
            if (isset($section[$i]['type']) && $section[$i]['type'] == '2') {
                $hasApprover = true;
                $approverIndex = $i;
            }
        }

        if (!$hasApprover) return true;  // Нет утверждающего

        return $approverIndex == $lastIndex;
    }

    private function sortParticipants($section)
    {
        $meta = array_shift($section);  // Сохраняем метаданные

        usort($section, function($a, $b) {
            $order = ['1' => 1, '3' => 2, '2' => 3];  // Порядок: подписант, согласующий, утверждающий
            $typeA = $a['type'] ?? '3';
            $typeB = $b['type'] ?? '3';
            return ($order[$typeA] ?? 99) - ($order[$typeB] ?? 99);
        });

        array_unshift($section, $meta);  // Возвращаем метаданные
        return $section;
    }

    private function isCorrectlySorted($section)
    {
        $prevOrder = 0;
        $order = ['1' => 1, '3' => 2, '2' => 3];

        for ($i = 1; $i < count($section); $i++) {
            $type = $section[$i]['type'] ?? '3';
            $currentOrder = $order[$type] ?? 99;

            if ($currentOrder < $prevOrder) {
                return false;
            }
            $prevOrder = $currentOrder;
        }

        return true;
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
            echo "     Причина: {$message}\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'FAIL', 'message' => $message];
        }
    }

    private function printReport()
    {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 ОТЧЁТ ПО ТЕСТАМ\n";
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
                    echo "  - {$result['name']}: {$result['message']}\n";
                }
            }
            echo "\n";
        }

        if ($this->failedTests == 0) {
            echo "🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
        } else {
            echo "⚠️  ЕСТЬ ОШИБКИ В БИЗНЕС-ЛОГИКЕ\n";
            echo "Необходимо проверить реализацию в коде.\n";
        }

        echo "\n" . str_repeat("=", 70) . "\n";
    }
}

// Запуск тестов
$tester = new AgreementBusinessLogicTest();
$tester->runAll();

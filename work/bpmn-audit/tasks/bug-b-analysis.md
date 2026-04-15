# Анализ Бага Б: многоуровневое перенаправление A→B→C

Файл: `modules/documents/ajaxHandlers/updateAgreement.php`

---

## Ожидаемая логика по BPMN

1. A перенаправляет на B: у A появляется `result.id=4`, `redirect: [{id: B, result: null}]`. A получает статус `redirected`.
2. B перенаправляет на C: у B в `redirect` появляется `redirect: [{id: C, result: null}]`.
3. C согласует (`result.id=1`).
4. B получает уведомление, что цепочка вернулась к нему, и должен принять своё финальное решение.
5. B согласует (`result.id=1`). Так как B завершил цепочку своего перенаправления, функция `insertRedirectorRepeatEntry` добавляет повторную запись A с `result: null`.
6. A получает уведомление и принимает финальное решение.

Итоговая структура секции при правильной работе:
```
section = [
  {stage, list_type},
  {id: A, result: {id:4}, redirect: [
    {id: B, result: {id:4}, redirect: [
      {id: C, result: {id:1}}
    ]},
    {id: B, _is_redirector_repeat: true, result: null}  // повторная запись B внутри redirect A
  ]},
  {id: A, _is_redirector_repeat: true, result: null}  // повторная запись A на верхнем уровне
]
```

---

## Реализация в коде (пошагово)

### Шаг 1: C согласует. Вызывается `getApproverStatus(C)` → `approved`.

Функция `getApproverStatus` (строки 162–181): корректно возвращает `{status: 'approved', result_id: 1}` для `result.id=1`.

### Шаг 2: Должен ли `isRedirectChainCompleted` дождаться решения B?

Функция `isRedirectChainCompleted` (строки 186–202):

```php
if ($status['status'] === 'redirected') {
    if (isset($approver['redirect']) && is_array($approver['redirect'])) {
        if (!isRedirectChainCompleted($approver['redirect'])) return false;
    } else {
        return false;
    }
}
```

Когда функция проверяет `A.redirect = [B]`, она получает статус B = `redirected` и рекурсивно вызывает `isRedirectChainCompleted(B.redirect = [C])`. C имеет статус `approved`, поэтому рекурсия возвращает `true`. После этого проверка B считается завершённой и функция возвращает `true`.

**Проблема (Баг 1):** Статус `redirected + sub-chain completed` приравнивается к `done`. B никогда не получит шанса принять своё финальное решение. Цепочка перепрыгивает C→A, минуя B.

### Шаг 3: Вставляет ли `insertRedirectorRepeatEntry` повторную запись B?

Функция `insertRedirectorRepeatEntry` (строки 208–248) итерирует записи `$section[$j]` — это только верхний уровень секции (`[A, _repeat_A, ...]`). B находится внутри `A.redirect[]` и в этот цикл не попадает.

**Проблема (Баг 2):** Для B повторная запись внутри `A.redirect[]` никогда не создаётся. Механизм «возврата» к B после решения C отсутствует полностью.

### Шаг 4: Вызывается ли `insertRedirectorRepeatEntry` на сервере вообще?

Строка 544:
```php
// Повторная запись перенаправившего вставляется на клиенте (agreement.php),
// поэтому insertRedirectorRepeatEntry здесь не вызывается во избежание дублирования.
```

**Проблема (Баг 3):** Серверная функция `insertRedirectorRepeatEntry` намеренно не вызывается. Логика вынесена на клиент. Это означает, что при A→B→C оба бага выше присутствуют и на клиентской стороне, либо клиентский код также не поддерживает уровень глубины > 1.

### Шаг 5: Учитывает ли `collectGlobalStats` статусы C и B?

Функция `collectGlobalStats` (строки 253–275): итерирует `$section[$i]['redirect']`, но не рекурсирует в `$rd['redirect']`:

```php
foreach ($section[$i]['redirect'] as $rd) {   // ← первый уровень redirect (B)
    // ... считаем статус B
    // НЕТ рекурсии в $rd['redirect'] (C)
}
```

**Проблема (Баг 4):** Статус C никогда не попадает в глобальную статистику. Это может привести к тому, что документ будет считаться "завершённым" (все pending = 0), хотя C ещё не принял решение.

### Шаг 6: Применяются ли подписи C из `cam_signs` к структуре?

Функция `applySignsToAgreementList` (строки 470–539):
- Строки 476–486 собирают `$signedViaRedirect` только из первого уровня `$section[$i]['redirect']` (уровень B), не из `B['redirect']` (уровень C).
- Строки 520–536 применяют подписи из `cam_signs` к записям `redirect[]`, но цикл `for ($r = 0...)` проходит только по `$agreementList[$i][$j]['redirect']` — то есть только по уровню B.

**Проблема (Баг 5):** Подпись C из таблицы `cam_signs` никогда не будет применена к `C.result` при синхронизации.

### Шаг 7: Получает ли B уведомление после решения C?

`sendNotificationsToNextActors` использует рекурсивное замыкание `$processRedirects` (строки 347–380), которое корректно рекурсирует вглубь и отправляет уведомление первому `pending` в цепочке. C получит уведомление.

Однако обработка «возврата к перенаправившему» (строки 386–412) работает только для записей с `_is_redirector_repeat: true`. Так как такой записи для B внутри `A.redirect[]` нет (Баг 2), B не получит уведомление о решении C.

---

## Найденные расхождения / баги

| # | Тяжесть | Функция | Строки | Описание |
|---|---------|---------|--------|----------|
| 1 | Критический | `isRedirectChainCompleted` | 193–199 | Статус `redirected + sub-chain completed` трактуется как «участник завершил работу». B не принимает финального решения; цепочка A→B→C ломается на шаге C→B. |
| 2 | Критический | `insertRedirectorRepeatEntry` | 210–246 | Цикл обходит только верхний уровень секции. Повторная запись для B (вложенный redirector) никогда не создаётся ни в `A.redirect[]`, ни на верхнем уровне секции. |
| 3 | Высокий | Вызов функции | 543–544 | `insertRedirectorRepeatEntry` не вызывается на сервере. Вся логика делегирована клиенту. Если клиентский код также не поддерживает вложенность > 1, баги 1 и 2 воспроизводятся там. |
| 4 | Средний | `collectGlobalStats` | 264–270 | Рекурсия в `redirect[]` только на один уровень. Статус C не учитывается в глобальной статистике `pending`/`approved`. |
| 5 | Средний | `applySignsToAgreementList` | 476–536 | Синхронизация подписей из `cam_signs` работает только на один уровень `redirect[]`. Подпись C не применяется. |
| 6 | Низкий | `sendNotificationsToNextActors` | 386–412 | Уведомление «возврат к перенаправившему» работает только через `_is_redirector_repeat`, которой нет для B (следствие Бага 2). B не получит уведомление после решения C. |

---

## Предлагаемый фикс

### Фикс Бага 1 — `isRedirectChainCompleted`

Изменить логику: `redirected` с завершённой под-цепочкой — это НЕ «завершено», это «ожидает финального решения перенаправившего». Завершённым следует считать только статусы `approved`, `rejected`.

```php
function isRedirectChainCompleted(array $redirectArr): bool
{
    foreach ($redirectArr as $approver) {
        if (!isset($approver['id'])) continue;
        $status = getApproverStatus($approver);

        if ($status['status'] === 'pending') return false;
        if ($status['status'] === 'returned') return false;

        if ($status['status'] === 'redirected') {
            // Проверяем под-цепочку
            $subDone = isset($approver['redirect']) && is_array($approver['redirect'])
                && isRedirectChainCompleted($approver['redirect']);
            if (!$subDone) return false;

            // Под-цепочка завершена, но сам redirector должен иметь _is_redirector_repeat с result != null
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
```

### Фикс Бага 2 — `insertRedirectorRepeatEntry`

Сделать функцию рекурсивной: обходить не только верхний уровень секции, но и вложенные `redirect[]` массивы.

```php
function insertRedirectorRepeatEntry(array &$agreementList): void
{
    for ($i = 0; $i < count($agreementList); $i++) {
        $section    = &$agreementList[$i];
        $startIndex = isset($section[0]['stage']) ? 1 : 0;
        _insertRepeatInLevel($section, $startIndex);
    }
}

function _insertRepeatInLevel(array &$level, int $startIndex = 0): void
{
    for ($j = $startIndex; $j < count($level); $j++) {
        $approver = $level[$j];
        if (!isset($approver['id'])) continue;

        // Рекурсивно обрабатываем вложенный redirect[]
        if (!empty($level[$j]['redirect']) && is_array($level[$j]['redirect'])) {
            _insertRepeatInLevel($level[$j]['redirect'], 0);
        }

        $status = getApproverStatus($approver);
        if ($status['status'] !== 'redirected') continue;

        $userId = $approver['id'];
        $hasRepeat = false;
        for ($k = $j + 1; $k < count($level); $k++) {
            if (isset($level[$k]['id']) && $level[$k]['id'] == $userId
                && isset($level[$k]['_is_redirector_repeat'])) {
                $hasRepeat = true;
                break;
            }
        }

        if (!$hasRepeat) {
            $repeatEntry = [
                'id'                    => $userId,
                'type'                  => $approver['type']   ?? 1,
                'vrio'                  => $approver['vrio']   ?? '0',
                'urgent'                => $approver['urgent'] ?? '0',
                'role'                  => $approver['role']   ?? '0',
                'result'                => null,
                '_is_redirector_repeat' => true,
            ];
            array_splice($level, $j + 1, 0, [$repeatEntry]);
            $j++;
        }
    }
}
```

### Фикс Бага 4 — `collectGlobalStats`

Заменить неполный обход `redirect[]` на рекурсивный вспомогательный метод.

```php
function _countRedirectStats(array $redirectArr, array &$stats): void
{
    foreach ($redirectArr as $rd) {
        if (!isset($rd['id'])) continue;
        $rst = getApproverStatus($rd);
        $stats['total']++;
        $stats[$rst['status']]++;
        if (isset($rd['redirect']) && is_array($rd['redirect'])) {
            _countRedirectStats($rd['redirect'], $stats);
        }
    }
}
```

И заменить в `collectGlobalStats` строки 264–270 на вызов `_countRedirectStats($section[$i]['redirect'], $stats)`.

### Фикс Бага 5 — `applySignsToAgreementList`

Вынести рекурсивную обработку `redirect[]` (строки 519–536) в отдельную вспомогательную функцию, которая рекурсирует на любую глубину.

### Фикс Бага 3 — клиентский код

Проверить реализацию `insertRedirectorRepeatEntry` в клиентском JavaScript (файл, на который ссылается строка 544), убедиться что она также поддерживает вложенность > 1 уровня, и при необходимости применить аналогичную рекурсивную логику.

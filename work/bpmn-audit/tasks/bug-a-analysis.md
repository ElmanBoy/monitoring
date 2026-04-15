# Анализ Бага А: дублирование подписи при повторном вхождении

## Процесс согласования по BPMN

BPMN-схема (`IGNORE/Sheme.bpmn`) описывает общую бизнес-архитектуру системы мониторинга: плановые проверки учреждений, устранение нарушений, согласование дорожных карт (ДК) и графиков. Детального BPMN-процесса именно для листа согласования документов в схеме нет — есть только упоминания задач «Согласование ДК» (Activity_0f7moma) и «График согласован» (Activity_026qov7) как результирующих шагов.

Детальный процесс согласования описан в документации `IGNORE/Agreement.md`:

**Участники (Roles/Lanes):**
- Инициатор — создаёт документ и лист согласования
- Согласанты (Approvers) — визируют в своих секциях
- Подписанты (Signers) — подписывают в финальной секции

**Шаги процесса:**
1. Инициатор создаёт документ и запускает согласование (статус → 1)
2. Первая секция согласования переходит в статус In Progress
3. Каждый участник секции ставит визу (`result.id`):
   - 1 — Подписано с ЭП
   - 2 — Согласовано с ЭП
   - 3 — Согласовано без ЭП
   - 4 — Перенаправлено (делегирование)
   - 5 — Отклонено
   - 6 — Возврат
4. При result.id=4 (перенаправление) участник добавляет в своё поле `redirect[]` нового согласанта
5. После завершения всех перенаправлений перенаправивший получает повторную запись (`_is_redirector_repeat: true`) — чтобы он мог поставить финальную визу
6. После завершения секции процесс переходит к следующей
7. Финальная секция — подписанты — активируется только когда все предыдущие секции завершены

**Ключевые шлюзы:**
- Параллельный/последовательный тип секции (`list_type`: 2/1) — определяет, нужны ли все визы или только последовательно
- Отклонение (result.id=5) — останавливает весь процесс
- Возврат (result.id=6) — сбрасывает предыдущий результат перенаправившего

---

## Реализация в коде

### Структура agreementList

`agreementList` — это массив секций, хранящийся в JSON-поле `cam_agreement.agreementlist`:

```json
[
  [
    { "stage": "1", "list_type": "1", "urgent": "1" },  // метаданные секции
    { "id": 42, "type": 2, "result": null },              // участник — pending
    { "id": 42, "type": 2, "result": {"id": 4, ...}, "redirect": [{"id": 99, ...}] }, // перенаправил
    { "id": 42, "type": 2, "result": null, "_is_redirector_repeat": true }             // повторная запись
  ],
  [
    { "stage": "", "list_type": "1", "urgent": "1" },   // секция подписантов
    { "id": 7, "type": 1, "role": "1", "result": null }
  ]
]
```

### Ключевые функции

**PHP (`updateAgreement.php`):**
- `normalizeSection()` — рекурсивно нормализует секцию: строки `"true"/"false"` → boolean, `result=""` → null, `result.id` → int
- `fixAgreementList()` — проходит по всем секциям, вызывает `normalizeSection()` для каждой
- `insertRedirectorRepeatEntry()` — **НЕ ВЫЗЫВАЕТСЯ** на сервере: закомментировано на строке 543-544, повторная запись вставляется только на клиенте

**JS (`modules/documents/dialogs/agreement.php`):**
- `applyUserAction(agj, section, result_type, pendingRepeats, level)` — применяет действие текущего пользователя к agreementList. При result_type=4 (перенаправление) накапливает `pendingRepeats` и на уровне `level===0` вставляет повторные записи через `agj.splice()`
- `getAgreementData(section, result_type)` — читает скрытое поле `#ag{section}`, вызывает `applyUserAction`, собирает все секции, отправляет POST на `updateAgreement`

**JS (`js/assets/agreement_list.js`):**
- `setAgreementList(instanceObj)` — собирает agreementList из DOM при редактировании состава (не при визировании). Используется при настройке листа согласования, не при проставлении виз.

---

## Локализация бага

**Файл:** `modules/documents/dialogs/agreement.php`
**Строки:** 318–330 (проверка `hasResult`) и 410–429 (логика `findById` при `alreadyExists`)

**Описание:**

В функции `applyUserAction` при проверке, нужно ли вставить повторную запись, выполняется следующее (строки 414–429):

```javascript
if (level === 0 && pendingRepeats.length > 0) {
    pendingRepeats.sort((a, b) => b.index - a.index);
    for (let f = 0; f < pendingRepeats.length; f++) {
        const pr = pendingRepeats[f];
        const alreadyExists = agj.some(
            (item, idx) => idx > pr.index && parseInt(item.id) === parseInt(pr.data.id) && item._is_redirector_repeat
        );
        if (!alreadyExists) {
            agj.splice(pr.index + 1 + f, 0, {
                id: pr.data.id, type: pr.data.type,
                vrio: pr.data.vrio, urgent: pr.data.urgent,
                role: pr.data.role, result: null,          // <-- здесь result: null, это правильно
                _is_redirector_repeat: true
            });
        }
    }
}
```

При вставке новой повторной записи `result: null` — это **правильно**. Однако баг проявляется в другом месте — **при загрузке существующего agreementList**, когда сотрудник уже встречается в массиве с заполненным `result`, а новая запись `_is_redirector_repeat: true` уже была вставлена ранее (например, на предыдущем сохранении).

Конкретная точка бага: **функция `applySignsToAgreementList` в `updateAgreement.php`**, строки 488–518:

```php
// updateAgreement.php, строки 488–518
for ($j = $startIndex; $j < count($agreementList[$i]); $j++) {
    if (!isset($agreementList[$i][$j]['id'])) continue;
    // Не трогаем повторные записи перенаправившего
    if (!empty($agreementList[$i][$j]['_is_redirector_repeat'])) continue;   // строка 491
    $userId = intval($agreementList[$i][$j]['id']);
    // Не перезаписываем строки у которых уже есть result
    if (!empty($agreementList[$i][$j]['result'])) continue;                   // строка 494
    ...
}
```

Строка 491 пропускает `_is_redirector_repeat`-записи — это **правильно**, `applySignsToAgreementList` их не трогает.

**Реальное место бага — JS-функция `applyUserAction`, строки 319–328:**

```javascript
for (let i = 0; i < agj.length; i++) {
    const isCurrentUser = parseInt(agj[i].id) === CURRENT_USER_ID;
    const hasResult     = agj[i].result && agj[i].result !== '';
    const isRepeat      = agj[i]._is_redirector_repeat;

    const skipRepeat    = isRepeat && hasResult && parseInt(result_type) === 4;

    if (isCurrentUser && !skipRepeat && !appliedViaRedirect) {
```

Проблема в том, что когда клиент **перезагружает** диалог согласования для документа, где один и тот же сотрудник встречается дважды:
- первое вхождение: `{ id: X, result: {...}, _is_redirector_repeat: undefined }` — уже поставил визу
- второе вхождение: `{ id: X, result: null, _is_redirector_repeat: true }` — ждёт возврата из redirect

При следующей загрузке `#ag{section}` читается из скрытого поля, которое заполняется **серверным ответом** `answer.resultAgreement[section]` (строка 473 в agreement.php):
```javascript
$ag.val(JSON.stringify(answer.resultAgreement[section]));
```

Сервер в `updateAgreement.php` возвращает `resultAgreement`, который строится из `$agreementList` после вызова `applySignsToAgreementList`. Функция `applySignsToAgreementList` (строка 504–517) содержит блок:

```php
} elseif (isset($signedViaRedirect[$userId])) {
    // Пользователь подписал через redirect — копируем результат из redirect
    foreach ($agreementList[$i] as $item) {
        if (!isset($item['redirect'])) continue;
        foreach ($item['redirect'] as $rd) {
            if (intval($rd['id']) === $userId && !empty($rd['result'])) {
                $rdStatus = intval($rd['result']['id'] ?? 0);
                if (in_array($rdStatus, [1, 2, 3])) {
                    $agreementList[$i][$j]['result'] = $rd['result'];   // строка 512 — КОПИРУЕТ result
                }
                break 2;
            }
        }
    }
}
```

**Это и есть место бага:** на строке 512 `applySignsToAgreementList` копирует `result` из `redirect[]`-записи в основную запись пользователя в `$agreementList[$i][$j]`. При этом условие `if (!empty($agreementList[$i][$j]['_is_redirector_repeat'])) continue;` на строке 491 пропускает `_is_redirector_repeat`-записи, поэтому повторная запись **остаётся с `result: null`** и баг здесь не проявляется.

Однако если смотреть на поведение в целом, баг проявляется на **клиентской стороне**: в `applyUserAction` при повторной загрузке страницы с уже существующим `_is_redirector_repeat`-вхождением. При попытке пользователя X снова совершить действие (например, при перезагрузке диалога), проверка `hasResult` на строке 321 возвращает `true` для **первого** вхождения пользователя X (у него есть result.id=4 — перенаправление), и при определённых условиях `skipNonRepeat = (!isRepeat && hasResult && parseInt(result_type) !== 6)` пропускает первую запись, но затем обрабатывает вторую (`_is_redirector_repeat: true, result: null`) — что корректно.

**Точное место бага — строка 512 `updateAgreement.php`** в функции `applySignsToAgreementList`:

```
Файл: modules/documents/ajaxHandlers/updateAgreement.php
Строка: 512
```

Логика применяет `result` из `redirect[]` к оригинальной записи пользователя (`_is_redirector_repeat` НЕ установлен), обходя условие `continue` на строке 491. Однако это верно только для **основной** записи участника (не повторной).

**Уточнение после повторного анализа:** Сценарий бага реализуется следующим образом:

1. Пользователь X стоит в секции дважды: первое вхождение — обычная запись (result=null), второе — тоже обычная запись (тот же id, но добавленная вручную или по другой причине, без `_is_redirector_repeat`).
2. X ставит визу на первом вхождении — result становится ненулевым.
3. При следующей обработке `applySignsToAgreementList` проверяет `!empty($agreementList[$i][$j]['result'])` (строка 494) и пропускает первую запись X.
4. Для **второго** вхождения X (без `_is_redirector_repeat` и без result) функция **находит подпись X в `user_signs`** и **копирует её в это вхождение** — строка 499:
   ```php
   $agreementList[$i][$j]['result'] = ['id' => $signType, 'date' => ...];
   ```

Таким образом, баг находится в **`applySignsToAgreementList`** (строки 496–502 в `updateAgreement.php`): функция не делает различия между первым обычным вхождением сотрудника и вторым, применяя подпись к любому вхождению с `result: null` у этого `userId`.

---

**Итоговая локализация:**

**Файл:** `modules/documents/ajaxHandlers/updateAgreement.php`
**Строки:** 488–518 (функция `applySignsToAgreementList`), конкретно строки 496–502
**Описание:** При наличии у сотрудника подписи в `cam_signs`, функция копирует эту подпись во **все** вхождения этого сотрудника в секции, у которых `result` пустой. Второе вхождение сотрудника (добавленное как `_is_redirector_repeat: true`) защищено условием на строке 491. Но если второе вхождение было добавлено без флага `_is_redirector_repeat` (например, при дублировании участника вручную или через баг в JS), оно получит result из подписи предыдущего вхождения.

---

## Расхождение с BPMN

Баг носит **чисто технический характер** и не противоречит BPMN-схеме. BPMN описывает бизнес-логику «перенаправивший должен продолжить после завершения redirect-цепочки» — это семантически правильно реализовано через `_is_redirector_repeat`. Расхождение состоит в том, что **код не обеспечивает инвариант**: каждое новое вхождение сотрудника в список должно стартовать с `result: null`.

Согласно Agreement.md, критический инвариант:
> При изменении текста документа (версии) все текущие визы должны быть аннулированы (Reset).

Аналогично, новое повторное вхождение (`_is_redirector_repeat`) должно начинаться с чистого состояния — это правило нарушается, когда в `applySignsToAgreementList` подпись применяется к записи, у которой нет флага `_is_redirector_repeat`.

---

## Предлагаемый фикс

### Вариант 1 (надёжный): Учитывать порядковый номер вхождения в `user_signs`

Проблема в том, что `user_signs` индексируется только по `userId` и `sectionIndex`, а не по порядковому номеру вхождения внутри секции. Если сотрудник встречается дважды, подпись применяется к первому найденному `result: null`.

**Изменение в `updateAgreement.php`**, функция `applySignsToAgreementList` (строки 488–518):

Добавить счётчик вхождений для каждого userId в секции:

```php
// Перед циклом по $j собираем счётчики уже применённых подписей
$appliedCount = [];  // userId -> количество вхождений с уже заполненным result

for ($j = $startIndex; $j < count($agreementList[$i]); $j++) {
    if (!isset($agreementList[$i][$j]['id'])) continue;
    if (!empty($agreementList[$i][$j]['_is_redirector_repeat'])) continue;
    $userId = intval($agreementList[$i][$j]['id']);
    if (!empty($agreementList[$i][$j]['result'])) {
        $appliedCount[$userId] = ($appliedCount[$userId] ?? 0) + 1;
    }
}
```

Однако проще использовать другой подход:

### Вариант 2 (минимальный): Применять подпись только к первому вхождению сотрудника

В цикле `applySignsToAgreementList` отслеживать, была ли подпись уже применена для данного `userId` в этой секции:

```php
$signsApplied = [];  // Отслеживаем userId для которых уже применили подпись

for ($j = $startIndex; $j < count($agreementList[$i]); $j++) {
    if (!isset($agreementList[$i][$j]['id'])) continue;
    if (!empty($agreementList[$i][$j]['_is_redirector_repeat'])) continue;
    $userId = intval($agreementList[$i][$j]['id']);
    if (!empty($agreementList[$i][$j]['result'])) continue;

    // Если для этого userId подпись уже была применена — не применять повторно
    if (isset($signsApplied[$userId])) continue;

    if (isset($user_signs[$userId][$i])) {
        $signType = intval($user_signs[$userId][$i]['type']);
        if (in_array($signType, [1, 2])) {
            $agreementList[$i][$j]['result'] = [
                'id'   => $signType,
                'date' => $user_signs[$userId][$i]['date']
            ];
            $signsApplied[$userId] = true;  // Помечаем, что подпись применена
        }
    } elseif (isset($signedViaRedirect[$userId])) {
        // ... (аналогично добавить $signsApplied[$userId] = true после применения)
    }
}
```

### Вариант 3 (альтернативный): JS — запрет добавления участника дважды без флага

В JS (`js/assets/agreement_list.js`), функция `bindUsersChange` уже проверяет `findById` (строка 410):
```javascript
}else if (!agreement_list.findById(agArr, $users.val())) {
    $add_agreement_message.text("");
    $new_signer.show();
}else{
    $add_agreement_message.text("Этот сотрудник уже есть в списке.");
    $new_signer.hide();
}
```

Это правило запрещает добавить одного и того же сотрудника дважды при ручном формировании списка. Но при автоматическом добавлении через `applyUserAction` (перенаправление) этот контроль не работает — там своя логика с `_is_redirector_repeat`.

**Рекомендуется Вариант 2** как наименее инвазивный и точно устраняющий баг в источнике. Изменение вносится только в `modules/documents/ajaxHandlers/updateAgreement.php` в функцию `applySignsToAgreementList`.

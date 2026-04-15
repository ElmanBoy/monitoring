# Security Audit — bpmn-audit

**Файлы проверки:**
- `modules/documents/ajaxHandlers/updateAgreement.php`
- `modules/roadmap/ajaxHandlers/updateAgreement.php`
- `modules/documents/pages/index_ajax.php`

**Дата аудита:** 2026-04-11

---

## Критические уязвимости

### CRIT-1: Отсутствие авторизации на уровне строк (IDOR) — оба `updateAgreement.php`

**Файлы:** `documents/ajaxHandlers/updateAgreement.php`, `roadmap/ajaxHandlers/updateAgreement.php`

**Описание:**
Обработчик принимает `$_POST['docId']`, приводит к `int` и немедленно обновляет запись `agreement` по этому ID. Нет проверки, является ли текущий пользователь автором документа, участником согласования или имеет право редактировать эту конкретную запись.

```php
$docId = intval($_POST['docId']);
// ...
$result = $db->update('agreement', $docId, $updateArr);  // без проверки владельца
```

**Последствие:** Любой авторизованный пользователь системы может подменить `docId` в запросе и перезаписать `agreementlist` чужого документа — в том числе изменить статус согласования, вписать себя в список подписантов или сбросить результаты.

**Рекомендация:** После получения `$agr` проверить, что `$agr->author == $_SESSION['user_id']` или пользователь состоит в `agreementlist` документа, либо имеет роль администратора:
```php
if (intval($agr->author) !== $currentUserId && !$auth->isAdmin()) {
    echo json_encode(['result' => false, 'resultText' => 'Доступ запрещён.', 'errorFields' => []]);
    exit;
}
```

---

### CRIT-2: Отсутствие валидации структуры `agreementList` перед первой записью в БД — `documents/updateAgreement.php`

**Файл:** `modules/documents/ajaxHandlers/updateAgreement.php`, строки 150–155

**Описание:**
Проверка `is_array($_POST['agreementList'])` выполняется на строке 145, но ПОСЛЕ того как `fixAgreementList()` уже вызвана на строке 79 — а `fixAgreementList()` не проверяет, что `$_POST['agreementList']` вообще является массивом и не бросает исключение при строке. Важнее другое: первая запись в БД (`$db->update(...)`) происходит на строке 155 — до итоговой обработки, нормализации и пересчёта статуса. Это означает, что в поле `agreementlist` может быть записан произвольный JSON от клиента (поле `$_POST['agreementList']` прошло только базовую нормализацию, но не проверку допустимых ключей на верхнем уровне).

Частный случай: клиент может передать в `agreementList[i][j]` произвольные ключи (`__proto__`, нестандартные поля), которые будут сохранены в JSONB без фильтрации.

**Рекомендация:** Добавить whitelist допустимых ключей в `normalizeSection()` и выполнять проверку `is_array` ДО любых операций с БД.

---

## Важные замечания

### WARN-1: `agreementList` принимается напрямую из `$_POST` без проверки допустимых ключей

**Файлы:** оба `updateAgreement.php`

**Описание:**
Функция `normalizeSection()` в `documents/updateAgreement.php` нормализует только поля `_is_redirector_repeat`, `result`, `redirect`. Все остальные поля (`id`, `role`, `type`, `vrio`, `urgent`, произвольные пользовательские ключи) передаются без изменений. Клиент может добавить любые данные, которые будут сохранены в JSONB-поле `agreementlist`.

В `roadmap/updateAgreement.php` нормализации нет вообще — `$_POST['agreementList']` сохраняется напрямую через `json_encode` без обработки.

**Последствие:** Засорение JSONB произвольными данными; потенциально скрытое управление поведением (например, вписать `_is_redirector_repeat: true` для записи, у которой этого быть не должно, что повлияет на подсчёт голосов).

**Рекомендация:** Применить whitelist в `normalizeSection()`: разрешать только известные ключи (`id`, `role`, `type`, `vrio`, `urgent`, `result`, `redirect`, `_is_redirector_repeat`). В `roadmap/updateAgreement.php` — добавить аналогичную функцию нормализации.

---

### WARN-2: XSS через `$reg->name` в `index_ajax.php` (строка 1395)

**Файл:** `modules/documents/pages/index_ajax.php`, строка 1395

**Описание:**
Поле `$reg->name` выводится в HTML через `stripslashes()` без `htmlspecialchars()`:
```php
'<td class="group">' . stripslashes($reg->name) . ...
```
Поле `name` документа вводит пользователь и хранится в БД. Если пользователь с правом создания документов сохранит в `name` строку вида `<script>alert(1)</script>`, она будет выполнена в браузере другого пользователя.

**Рекомендация:**
```php
'<td class="group">' . htmlspecialchars(stripslashes($reg->name), ENT_QUOTES, 'UTF-8') . ...
```

---

### WARN-3: XSS через `$agrStatus['title']` в атрибутах HTML (строки 1320, 1327, 1347)

**Файл:** `modules/documents/pages/index_ajax.php`

**Описание:**
Поле `title` из `$agrStatus` содержит строки вроде `"Требуется ваша подпись (этап {$userInfo['stage']})"`. Значение `stage` происходит из поля `agreementlist` в БД — которое, в свою очередь, заполняется из `$_POST['agreementList']`. Поле подставляется напрямую в HTML-атрибут без экранирования:
```php
$title = ' title="' . $agrStatus['title'] . '"';
// ...
echo '<td class="status ..."' . $title . '>...';
```
Если в `agreementList[i][0]['stage']` передать `" onmouseover="alert(1)`, это значение попадёт в атрибут `title` тега без экранирования.

**Рекомендация:**
```php
$title = ' title="' . htmlspecialchars($agrStatus['title'], ENT_QUOTES, 'UTF-8') . '"';
```

---

### WARN-4: Небезопасный поиск министра по `roles LIKE '%2%'`

**Файл:** `modules/documents/ajaxHandlers/updateAgreement.php`, строка 712

**Описание:**
```php
$minister = $db->selectOne('users',
    " WHERE active = 1 AND roles LIKE '%2%' AND position LIKE '%Министр...'");
```
Поиск `LIKE '%2%'` по JSONB-полю `roles` ненадёжен: он совпадёт с любым пользователем, чей массив ролей содержит цифру "2" как подстроку (например, `[12]`, `[21]`, `[200]`). Это логическая ошибка, которая может привести к тому, что уведомление о подписи будет отправлено не тому пользователю.

**Рекомендация:** Использовать JSONB-оператор PostgreSQL:
```sql
WHERE active = 1 AND roles::jsonb @> '[2]'::jsonb AND position LIKE '%Министр%'
```

---

### WARN-5: Отладочный HTML-комментарий с данными сессии в production-коде

**Файл:** `modules/documents/pages/index_ajax.php`, строка 1308

**Описание:**
```php
if ($reg->id == 181) {
    echo "<!-- DOC 181 RENDER: User=" . $_SESSION['user_id'] . ", status_type=" . $agrStatus['status_type'] . " -->\n";
}
```
Комментарий раскрывает `user_id` текущего пользователя в HTML-источнике страницы для документа с ID 181. Это утечка внутренних данных и явный признак отладочного кода в продакшн-ветке.

**Рекомендация:** Удалить блок целиком.

---

### WARN-6: `$_POST['agreementList']` может быть `null` в `roadmap/updateAgreement.php`

**Файл:** `modules/roadmap/ajaxHandlers/updateAgreement.php`, строка 20

**Описание:**
```php
'agreementlist' => json_encode($_POST['agreementList'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
```
Если `$_POST['agreementList']` отсутствует или не является массивом, `json_encode` запишет в БД `null` или строку `"null"`, полностью затирая существующий список согласования документа. Проверки на существование ключа нет.

**Рекомендация:** Добавить guard перед обновлением:
```php
if (!isset($_POST['agreementList']) || !is_array($_POST['agreementList'])) {
    echo json_encode(['result' => false, 'resultText' => 'Некорректные данные.', 'errorFields' => []]);
    exit;
}
```

---

## Мелкие замечания

### MINOR-1: Небезопасный паттерн `implode` в SQL — приемлемый риск при текущем коде

**Файл:** `modules/documents/pages/index_ajax.php`, строки 1157, 1167

**Описание:**
```php
' WHERE documentacial = 8 AND source_id IN (' . implode(',', array_unique($_actIdsForReports)) . ')'
' WHERE id IN (' . implode(',', array_unique($_insIds)) . ')'
```
Данные в массивах `$_actIdsForReports` и `$_insIds` заполняются через `intval()` из БД-ответов (строки 1148, 1151), а не из `$_POST`. Прямой SQL-инъекции нет. Однако паттерн `implode` без параметризации считается плохой практикой — при рефакторинге легко занести уязвимость.

**Рекомендация:** Переходить на параметризованные IN-запросы через `R::getAll()` или ORM-метод.

---

### MINOR-2: Отсутствие `intval()` для поля `id` из `agreementList` в `roadmap/updateAgreement.php`

**Файл:** `modules/roadmap/ajaxHandlers/updateAgreement.php`, строка 66

**Описание:**
```php
if (isset($user_signs[$itemArr[$l]['id']][$i])
```
Поле `$itemArr[$l]['id']` используется как ключ массива без приведения к `int`. Если клиент передаст строковый ключ, поведение зависит от нестрогого сравнения PHP. Функциональной уязвимости нет, но код непредсказуем при нестандартных входных данных.

---

### MINOR-3: Предупреждение о количестве участников не блокирует сохранение

**Файл:** `modules/documents/ajaxHandlers/updateAgreement.php`, строки 128–135

**Описание:**
Переменная `$quantityWarning` заполняется при нарушении правил (например, приказ с двумя подписантами), но выполнение продолжается — документ всё равно сохраняется. Это скорее функциональное решение, но может приводить к некорректным данным в БД.

---

## Итоговый вывод

**Код НЕ готов к деплою** с точки зрения безопасности.

Два критических замечания требуют обязательного исправления до публикации:

1. **CRIT-1** (IDOR) — наиболее серьёзная уязвимость: любой авторизованный пользователь может изменить `agreementlist` чужого документа, подставив чужой `docId`. Это прямая угроза целостности данных системы согласования.

2. **CRIT-2** (отсутствие whitelist полей в `agreementList`) — в сочетании с CRIT-1 позволяет манипулировать логикой подсчёта голосов и статусами согласования через произвольные ключи JSONB.

Из важных замечаний особого внимания требуют **WARN-2** и **WARN-3** (два XSS-вектора через поля из БД) и **WARN-5** (утечка `user_id` в HTML-комментарии).

CSRF-защита, параметризация SQL-запросов через ORM и санитизация роутера реализованы корректно и замечаний не вызывают.

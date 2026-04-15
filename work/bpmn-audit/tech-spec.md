---
created: 2026-04-11
status: approved
branch: main
size: L
---

# Tech Spec: bpmn-audit

## Solution

Ревизия в три направления: (1) баг-фиксы в согласовании, (2) исправление цветовой индикации статусов в листинге документов, (3) анализ и фиксация расхождений модуля Roadmap со схемой.

Работа ведётся по принципу: читаем BPMN → читаем код → фиксируем расхождение → исправляем → пишем тест.

## Architecture

### What we're building/modifying

- **modules/documents/ajaxHandlers/updateAgreement.php** — логика согласования (баги А и Б)
- **modules/roadmap/ajaxHandlers/updateAgreement.php** — зеркальная логика для roadmap (баг А)
- **modules/documents/pages/index_ajax.php** — вычисление цветовой индикации статуса в листинге
- **tests/** — новые тесты: unit + интеграционные

### How it works

**Баг А — дублирование подписи при повторном вхождении сотрудника:**

Структура `agreementlist` (JSONB в `cam_agreement`) — массив секций, каждая секция — массив:
```
[{stage, list_type}, {id, role, result, redirect?, _is_redirector_repeat?}, ...]
```

При перенаправлении функция `insertRedirectorRepeatEntry()` (`updateAgreement.php:208`) добавляет повторную запись перенаправившего с `result: null` — это правильно.

**Проблема:** если сотрудник встречается в секции несколько раз (добавлен вручную дважды), и при первом вхождении уже поставил визу — при добавлении повторной записи (`_is_redirector_repeat: true`) нужно убедиться что `result: null`. По коду строка 240 уже ставит `'result' => null`, но нужно проверить сценарий: что если JS-клиент при сохранении формы копирует `result` от существующей записи сотрудника в новое вхождение.

Локализация: JS-сторона — `modules/documents/js/registry_items.js` (сборка `agreementList` перед отправкой в `updateAgreement`).

**Баг Б — многоуровневое перенаправление A→B→C:**

По схеме: C согласует → B принимает финальное решение → A принимает финальное решение (строго обратный порядок).

Реализация: redirect хранится вложенно — `section[i].redirect[]` — массив перенаправленных. При перенаправлении B→C: в записи B появляется `redirect: [{id: C, result: null, ...}]`. Функция `insertRedirectorRepeatEntry()` добавляет повторную запись B после C.

Нужно проверить: корректно ли функция `isRedirectChainCompleted()` (`updateAgreement.php:186`) рекурсивно проверяет завершённость цепочки A→B→C перед тем как вернуть управление A.

**Индикация статусов в листинге:**

В `modules/documents/pages/index_ajax.php` есть закомментированная функция `getDocumentStatusForIcon()` (строки 26-106). Это означает что текущая логика цветовой индикации либо отсутствует, либо реализована иначе. Нужно выяснить как сейчас рендерится цвет и исправить для случая с перенаправлениями.

Правило: **синий** — очередь текущего пользователя (есть `pending` запись с его `id` на текущем активном уровне), **серый** — не его очередь.

### Shared resources

| Resource | Owner | Consumers | Instance count |
|----------|-------|-----------|----------------|
| SSH-туннель к БД | пользователь | интеграционные тесты | 1 |
| `cam_agreement` (JSONB) | updateAgreement.php | renderAgreementTable.php, index_ajax.php | — |

## Decisions

### Decision 1: Баги А и Б — фикс на PHP-стороне, не JS
**Decision:** Исправляем серверную логику в `updateAgreement.php`, не клиентскую сборку `agreementList`.
**Rationale:** Сервер — единственная точка истины. JS может передать что угодно — сервер обязан игнорировать `result` из `_is_redirector_repeat`-записей при входящих данных.
**Alternatives considered:** Чинить JS-сборку — ненадёжно, JS-код сложнее тестировать unit-тестами.

### Decision 2: Цветовая индикация — восстановить закомментированную функцию
**Decision:** Раскомментировать и доработать `getDocumentStatusForIcon()` в `index_ajax.php`, добавив корректную обработку redirect-цепочек.
**Rationale:** Функция уже написана и содержит базовую логику. Причина комментирования неизвестна — нужно выяснить при чтении кода (возможно баг в ней же).
**Alternatives considered:** Писать с нуля — избыточно.

### Decision 3: Roadmap — фиксируем расхождения как задачи, не исправляем
**Decision:** По модулю Roadmap только анализируем и создаём задачи в `work/bpmn-audit/tasks/`. Исправления — отдельная итерация.
**Rationale:** Модуль в разработке, объём расхождений неизвестен заранее, риск дестабилизировать.
**Alternatives considered:** Исправлять по ходу — слишком широкий scope для одной итерации.

### Decision 4: Тесты — standalone PHP без фреймворка
**Decision:** Новые тесты пишем в `/tests/` в том же стиле что `AgreementBusinessLogicTest.php`.
**Rationale:** Существующий стек. Интеграционные тесты через SSH-туннель как `AgreementCodeIntegrationTest.php`.
**Alternatives considered:** PHPUnit — не установлен в проекте.

## Data Models

Изменений схемы БД не требуется. Структура `agreementlist` (JSONB) остаётся прежней:

```json
[
  [{"stage": "0", "list_type": 1}, {"id": 5, "role": 0, "result": null}],
  [{"stage": "",  "list_type": 2}, {"id": 3, "role": 1, "result": {"id": 1, "text": "Согласовано"}, "redirect": [{"id": 7, "result": null}]}, {"id": 3, "_is_redirector_repeat": true, "result": null}]
]
```

**Инвариант (новый):** при входящих POST-данных сервер обязан сбросить `result` для любой записи с `_is_redirector_repeat: true`.

## Dependencies

### New packages
Нет.

### Using existing (from project)
- `Core\Db` — запросы к `cam_agreement`, `cam_signs`
- `Core\Notifications` — уведомления следующим участникам
- SSH-туннель (DATABASE_CONNECTION.md) — интеграционные тесты

## Testing Strategy

**Feature size:** L

### Unit tests

Файл: `tests/AgreementBugFixTest.php`

- **Баг А, сценарий 1:** сотрудник встречается дважды в секции. Первое вхождение имеет `result.id=1`. Второе вхождение — `_is_redirector_repeat: true` с ненулевым `result` во входящих POST. После `fixAgreementList()` / нормализации второе вхождение должно иметь `result: null`.
- **Баг А, сценарий 2:** сотрудник добавлен вручную дважды (оба без `_is_redirector_repeat`). Первое имеет `result.id=1`. Второе — `result: null`. Убедиться что второе не получает result от первого.
- **Баг Б, сценарий 1:** цепочка A→B→C, C имеет `result.id=1`, B имеет `result.id=4` (redirected). `isRedirectChainCompleted()` для B должна вернуть `true` (C завершил).
- **Баг Б, сценарий 2:** цепочка A→B→C, C ещё `pending`. `isRedirectChainCompleted()` для B должна вернуть `false`.
- **Баг Б, сценарий 3:** после завершения C→B — повторная запись A (как `_is_redirector_repeat`) должна иметь `result: null`, не копировать предыдущий result A.
- **Индикация, сценарий 1:** пользователь 5 в секции имеет `pending` и это его очередь (предыдущие участники завершили) → функция возвращает `синий`.
- **Индикация, сценарий 2:** пользователь 5 в секции, но не его очередь (предыдущий участник ещё `pending`) → `серый`.
- **Индикация, сценарий 3:** пользователь 5 есть в redirect-цепочке (B перенаправил на него) → `синий`.
- **Индикация, сценарий 4:** пользователь 5 — перенаправивший (B), ждёт возврата от C → `серый` до завершения C.

### Integration tests

Файл: `tests/AgreementBugFixIntegrationTest.php`

- Создать документ в реальной БД → добавить agreementlist с повторяющимся сотрудником → вызвать updateAgreement → прочитать из БД → убедиться что дублирования result нет.
- Создать цепочку A→B→C → C согласует → проверить что B получает уведомление (запись в `cam_notifications`) → B согласует → A получает уведомление.

### E2E tests
Нет — отсутствует инструментарий.

## Agent Verification Plan

**Source:** user-spec "Как проверить" section.

### Verification approach

После unit-тестов — агент читает код и проверяет что инвариант соблюдён статически (grep по паттернам копирования result). После интеграционных тестов — агент проверяет что тесты проходят без ошибок.

### Tools required
- `bash` — запуск тестов: `php tests/AgreementBugFixTest.php`
- SSH-туннель — для интеграционных тестов

## Risks

| Risk | Mitigation |
|------|-----------|
| Закомментированная `getDocumentStatusForIcon()` была убрана из-за своего бага | Изучить git blame / diff, не раскомментировать слепо — сначала понять причину |
| Roadmap использует backup-файлы (`_13.03`, `_old`) — риск анализировать устаревший код | Ориентироваться только на файлы без дат/суффиксов |
| Баг А присутствует в обоих модулях (documents и roadmap) — разные файлы updateAgreement.php | Исправить независимо в обоих; roadmap/ajaxHandlers/updateAgreement.php значительно проще (нет insertRedirectorRepeatEntry) |
| Интеграционные тесты требуют SSH-туннель | Описано в DATABASE_CONNECTION.md; без туннеля тесты падают с connection error — это ожидаемо |

## User-Spec Deviations

- **Roadmap-исправления** (user-spec предполагал возможные фиксы): tech-spec ограничивает scope до анализа + задачи. Исправления roadmap — следующая итерация. → [APPROVED]
- **Added: сброс result для `_is_redirector_repeat` на сервере** (не упомянуто явно в user-spec). Reason: без этого баг А воспроизводим если JS передаёт грязные данные. → [APPROVED]

## Acceptance Criteria

- [ ] Баг А: unit-тест воспроизводит сценарий и проходит после фикса
- [ ] Баг Б: unit-тест на многоуровневое перенаправление проходит
- [ ] Индикация: unit-тесты на 4 сценария цвета проходят
- [ ] Фикс применён в `modules/documents/ajaxHandlers/updateAgreement.php`
- [ ] Фикс применён в `modules/roadmap/ajaxHandlers/updateAgreement.php` (там где применимо)
- [ ] Цветовая индикация в `modules/documents/pages/index_ajax.php` корректна при перенаправлениях
- [ ] Интеграционные тесты проходят через SSH-туннель
- [ ] По модулю Roadmap создан список задач в `work/bpmn-audit/tasks/`
- [ ] Все существующие тесты в `/tests/` не сломаны

## Implementation Tasks

### Wave 1 (независимые)

#### Task 1: Анализ BPMN + локализация Бага А
- **Description:** Прочитать `/IGNORE/Sheme.bpmn` — извлечь процесс согласования (шаги, шлюзы, условия). Прочитать `modules/documents/ajaxHandlers/updateAgreement.php` целиком и `modules/documents/js/registry_items.js`. Найти точное место где result копируется в повторное вхождение сотрудника при перенаправлении. Написать анализ в `work/bpmn-audit/tasks/bug-a-analysis.md`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer
- **Files to read:** `IGNORE/Sheme.bpmn`, `modules/documents/ajaxHandlers/updateAgreement.php`, `modules/documents/js/registry_items.js`

#### Task 2: Анализ и локализация Бага Б
- **Description:** На основании прочитанного кода из Task 1 проверить логику `isRedirectChainCompleted()` и `insertRedirectorRepeatEntry()` для цепочки A→B→C. Проверить порядок: C согласует → B получает очередь → B согласует → A получает очередь. Написать анализ в `work/bpmn-audit/tasks/bug-b-analysis.md`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer
- **Files to read:** `modules/documents/ajaxHandlers/updateAgreement.php` (функции `isRedirectChainCompleted`, `insertRedirectorRepeatEntry`, `sendNotificationsToNextActors`)

#### Task 3: Анализ индикации статусов в листинге
- **Description:** Прочитать `modules/documents/pages/index_ajax.php` полностью. Понять почему `getDocumentStatusForIcon()` закомментирована. Найти текущую логику рендера цвета статуса. Проверить корректность при перенаправлениях (синий/серый). Написать анализ в `work/bpmn-audit/tasks/status-indicator-analysis.md`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer
- **Files to read:** `modules/documents/pages/index_ajax.php`

#### Task 4: Анализ модуля Roadmap
- **Description:** Прочитать BPMN-блок дорожных карт. Прочитать `modules/roadmap/` (только файлы без суффиксов дат). Сравнить: что реализовано, что нет. Проверить наличие функции продления сроков ДК. Написать задачи в `work/bpmn-audit/tasks/roadmap-gaps.md`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer
- **Files to read:** `IGNORE/Sheme.bpmn`, `modules/roadmap/pages/index_ajax.php`, `modules/roadmap/ajaxHandlers/update_road.php`, `modules/roadmap/ajaxHandlers/add_road.php`, `modules/roadmap/js/registry_items.js`

### Wave 2 (зависит от Wave 1)

#### Task 5: Фикс Бага А — documents
- **Description:** На основании анализа из Task 1 исправить `modules/documents/ajaxHandlers/updateAgreement.php`: обеспечить сброс `result` для любой записи с `_is_redirector_repeat: true` при входящих POST-данных (в `fixAgreementList()` или `normalizeSection()`). Написать unit-тест в `tests/AgreementBugFixTest.php` покрывающий сценарии 1 и 2 из Testing Strategy.
- **Skill:** code-writing
- **Reviewers:** code-reviewer, test-reviewer
- **Verify-smoke:** `php tests/AgreementBugFixTest.php`
- **Files to modify:** `modules/documents/ajaxHandlers/updateAgreement.php`, `tests/AgreementBugFixTest.php`
- **Files to read:** `modules/documents/ajaxHandlers/updateAgreement.php` (функция `normalizeSection`, строки 38-67)

#### Task 6: Фикс Бага А — roadmap
- **Description:** Аналогично Task 5 для `modules/roadmap/ajaxHandlers/updateAgreement.php`. Файл проще (нет `insertRedirectorRepeatEntry`), но проверить наличие аналогичной уязвимости. Добавить тест в `tests/AgreementBugFixTest.php`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer, test-reviewer
- **Verify-smoke:** `php tests/AgreementBugFixTest.php`
- **Files to modify:** `modules/roadmap/ajaxHandlers/updateAgreement.php`, `tests/AgreementBugFixTest.php`

#### Task 7: Фикс Бага Б — многоуровневое перенаправление
- **Description:** На основании анализа из Task 2 исправить логику возврата цепочки C→B→A в `modules/documents/ajaxHandlers/updateAgreement.php`. Написать unit-тесты (сценарии 1-3 из Testing Strategy) в `tests/AgreementBugFixTest.php`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer, test-reviewer
- **Verify-smoke:** `php tests/AgreementBugFixTest.php`
- **Files to modify:** `modules/documents/ajaxHandlers/updateAgreement.php`, `tests/AgreementBugFixTest.php`

#### Task 8: Исправление цветовой индикации статусов
- **Description:** На основании анализа из Task 3 исправить или реализовать функцию вычисления цвета статуса в `modules/documents/pages/index_ajax.php`. Правило: синий = очередь текущего пользователя (включая redirect-цепочки), серый = не его очередь. Написать unit-тест (сценарии 1-4 из Testing Strategy) в `tests/AgreementBugFixTest.php`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer, test-reviewer
- **Verify-smoke:** `php tests/AgreementBugFixTest.php`
- **Files to modify:** `modules/documents/pages/index_ajax.php`, `tests/AgreementBugFixTest.php`

### Wave 3 (зависит от Wave 2)

#### Task 9: Интеграционные тесты
- **Description:** Написать `tests/AgreementBugFixIntegrationTest.php`. Два сценария из Testing Strategy (повторяющийся сотрудник + цепочка уведомлений). Использовать SSH-туннель как в `AgreementCodeIntegrationTest.php`.
- **Skill:** code-writing
- **Reviewers:** code-reviewer, test-reviewer
- **Verify-smoke:** `php tests/AgreementBugFixIntegrationTest.php` (требует активный SSH-туннель)
- **Files to modify:** `tests/AgreementBugFixIntegrationTest.php`
- **Files to read:** `tests/AgreementCodeIntegrationTest.php`, `DATABASE_CONNECTION.md`

### Audit Wave

#### Task 10: Code Audit
- **Description:** Full-feature code quality audit. Прочитать все изменённые файлы: `modules/documents/ajaxHandlers/updateAgreement.php`, `modules/roadmap/ajaxHandlers/updateAgreement.php`, `modules/documents/pages/index_ajax.php`, `tests/AgreementBugFixTest.php`, `tests/AgreementBugFixIntegrationTest.php`. Проверить: дублирование логики между documents и roadmap, согласованность нормализации agreementList, полноту покрытия тестами. Написать отчёт.
- **Skill:** code-reviewing
- **Reviewers:** none

#### Task 11: Security Audit
- **Description:** Full-feature security audit. Прочитать изменённые файлы. Анализировать: валидация входящих POST-данных (agreementList), JSONB-инъекции, права доступа к операциям согласования, CSRF. Написать отчёт.
- **Skill:** security-auditor
- **Reviewers:** none

#### Task 12: Test Audit
- **Description:** Full-feature test quality audit. Прочитать `tests/AgreementBugFixTest.php` и `tests/AgreementBugFixIntegrationTest.php`. Проверить покрытие всех сценариев из Testing Strategy, качество assertions, изоляцию unit-тестов от БД. Написать отчёт.
- **Skill:** test-master
- **Reviewers:** none

### Final Wave

#### Task 13: Pre-deploy QA
- **Description:** Запустить все тесты в `/tests/`. Проверить acceptance criteria из user-spec и tech-spec. Проверить что roadmap-задачи задокументированы в `work/bpmn-audit/tasks/`.
- **Skill:** pre-deploy-qa
- **Reviewers:** none

<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Registry;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$gui = new Gui;
$db = new Db;
$auth = new Auth();
$reg = new Registry();
$date = new Date();
$editData = [];
$act_number = '';

$taskId = intval($_POST['params']['taskId']);

$chStaff = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);

if ($chStaff == null) {
    echo '<script>alert("Задача не найдена. Возможно, она была удалена.");setTimeout(function (){$(".wrap_pop_up").remove();}, 2000);</script>';
    die();
}

$view_result = intval($_POST['params']['view_result']) == 1 || intval($chStaff->done) == 1;
$insId = $chStaff->institution;

//Получение данных адресанта
if ($chStaff->object_type == 1) {
    $ins = $db->selectOne('institutions', ' WHERE id = ?', [$insId]);
} else {
    $ins = $db->selectOne('persons', ' WHERE id = ?', [$insId]);
}
//$tasks = $db->getRegistry('tasks');
//Справочник ОУСР (если они задействованы)
$ousr = $db->getRegistry('ousr');

//Получение id чек-листов из задачи
$task = $db->selectOne('tasks', ' WHERE id = ?', [$chStaff->task_id]);
//Получение чек-листов
$checklist = $db->select('checklists', ' WHERE id IN (' . implode(', ', json_decode($task->sheet)) . ')');
//Получаем тип проверки для данного учреждения из плана
$plan_uid = $chStaff->check_uid;
$plan = $db->selectOne('checksplans', ' WHERE uid = ? ORDER BY version DESC LIMIT 1', [$plan_uid]);
$check_type_id = 0;
if ($plan) {
    $addInstitution = json_decode($plan->addinstitution, true) ?? [];
    foreach ($addInstitution as $ads) {
        if (intval($ads['institutions']) == $insId) {
            $check_type_id = intval($ads['check_types']);
            break;
        }
    }
}
//Справочник шаблонов актов — фильтруем по типу проверки, если он известен
if ($check_type_id > 0) {
    $orders = $db->getRegistry('documents', ' WHERE documentacial = 2 AND checks = ' . $check_type_id);
} else {
    $orders = $db->getRegistry('documents', ' WHERE documentacial = 2');
}
//Справочник пользователей (для выбора подписантов)
$users = $db->getRegistry('users', "where roles <> '2'", [], ['surname', 'name', 'middle_name']);


//echo '<pre>'.$taskId;print_r($editData);echo '</pre>';

//Если такой акт уже есть
$agreement_data = $db->selectOne('agreement', " WHERE documentacial = 2 AND 
source_table = 'checkinstitutions' AND source_id = " . $insId
);
$objections_data   = json_decode($agreement_data->objections ?? '{}', true);
$objections_text   = $objections_data['text']  ?? '';
$objections_date   = $objections_data['date']  ?? '';
$objections_files  = $objections_data['files'] ?? [];
$objections_count  = strlen($objections_text) > 0 ? 1 : 0;
$is_object_control = $auth->haveUserRole(5);
$act_signed        = intval($agreement_data->status ?? 0) === 1;
$act_id            = intval($agreement_data->id ?? 0);

if ($auth->isLogin()) {

    //Открываем транзакцию
    $busy = $db->transactionOpen('checkstaff', intval($_POST['params']));
    $trans_id = $busy['trans_id'];

    if ($busy != []) {
        ?>
        <style>
            #openlayers-container {
                position: relative;
                top: 0;
                left: 0;
                right: 0;
                bottom: 100px;
                height: 300px;
                width: 100%;
            }

            #map {
                width: 100%;
                height: 100%;
            }

            #status {
                position: absolute;
                top: 10px;
                left: 10px;
                background: rgba(255, 255, 255, 0.8);
                padding: 10px;
                border-radius: 5px;
                z-index: 2;
            }

            .checklist .behaviour {
                display: none !important;
            }
        </style>
        <div class='pop_up drag' style="width: 60vw;">
            <div class='title handle'>
                <div class='name'>Задача №<?= $taskId ?></div>
                <div class='button icon close'><span class='material-icons'>close</span></div>
            </div>
            <div class='pop_up_body'>
                <form class='ajaxFrm' id='view_task' onsubmit='return false'>
                    <input type='hidden' name='uid' value="<?= $plan_uid ?>">
                    <input type='hidden' name='task_id' value="<?= $taskId ?>">
                    <input type='hidden' name='ins' value="<?= $insId ?>">
                    <input type='hidden' name='path' value='calendar'>
                    <?
                    if (intval($chStaff->is_head) == 1) {
                        ?>
                        <ul class='tab-pane'>
                            <li id='tab_my' class='active'>Мой чек-лист</li>
                            <li id='tab_otherCheckLists'>Остальные чек-листы</li>
                            <li id='tab_act'>Акт</li>
                            <li id='tab_agreement'>Согласование</li>
                            <li id='tab_objections'>Возражения<?= $objections_count > 0 ? ' <span class="badge">'.$objections_count.'</span>' : '' ?></li>
                            <li id='tab_preview' style='display: none'>Предпросмотр</li>
                        </ul>
                        <?
                    }
                    ?>

                    <?
                    //Если просматривает руководитель проверки
                    if (intval($chStaff->is_head) == 1) {
                        ?>
                        <style>
                            #tab_otherCheckLists-panel .new_violation {
                                display: none;
                            }
                        </style>
                        <div class="tab-panel group" id="tab_otherCheckLists-panel" style="display: none">
                            <div class="group">
                                <h3 class="item">
                                    <strong>ОСТАЛЬНЫЕ ЧЕК-ЛИСТЫ</strong>
                                </h3>
                            </div>
                            <ul id="executors_list" style="margin-right: 30px;margin-left: auto;"></ul>
                            <?
                            $allTask = $db->select('checkstaff', ' WHERE 
                        check_uid = ? AND institution = ? AND id <> ?', [$chStaff->check_uid, $insId, $taskId]
                            );
                            $otherBlockNumber = 2;
                            foreach ($allTask as $id => $other) {
                                echo '<div class="item w_100 executor" id="executor' . $other->user . '"><strong>Проверяющий:</strong>&nbsp;<span>' .
                                    $users['array'][$other->user][0] . ' ' .
                                    $users['array'][$other->user][1] . ' ' .
                                    $users['array'][$other->user][2] .
                                    '</span></div>';
                                $otherTask = $db->selectOne('tasks', ' WHERE id = ?', [$other->task_id]);
                                $otherChecklist = $db->select('checklists', ' WHERE id IN (' . implode(', ',
                                        json_decode($otherTask->sheet)
                                    ) . ')'
                                );
                                $ovi = 0;
                                $otherVioArr = [];
                                foreach ($otherChecklist as $index => $ch) {
                                    $otherEditData = $db->selectOne($ch->table_name, ' WHERE id = ? LIMIT 1', [$other->record_id]);
                                    $otherViolations = $db->select('checksviolations',
                                        'WHERE tasks = ? AND checklist = ?', [$id, $index]
                                    );

                                    $voc = 0;
                                    foreach ($otherViolations as $ov) {
                                        $otherEditData->violations_text[$voc] = $ov->name;
                                        $otherEditData->violations_type[$voc] = $ov->violations;
                                        $otherEditData->violations_id[$voc] = $ov->id;

                                        $otherVioArr[$ovi] = [
                                            'name' => $ov->name,
                                            'violation' => $ov->violations,
                                            'id' => $ov->id,
                                            'otherAuthor' => $ov->author
                                        ];
                                        $voc++;
                                        $ovi++;
                                    }
                                    //echo $reg->buildAssignment($taskId, $view_result, (array)$otherEditData)['html'];
                                    echo '<a class="moveToTop"><span class="material-icons">arrow_upward</span> Вверх</a>';
                                    echo $reg->buildChecklist($ch->id, [], (array)$otherEditData,
                                        'result', $otherBlockNumber
                                    );
                                    $otherBlockNumber++;
                                }
                            }
                            ?>
                            <script>
                                $(document).ready(function () {
                                    let $executors = $('#tab_otherCheckLists-panel .executor'),
                                        $executors_list = $('#executors_list');
                                    $executors_list.html('');
                                    for (let e = 0; e < $executors.length; e++) {
                                        $executors_list.append('<li><a href="' + $($executors[e]).attr("id") + '">'
                                            + $($executors[e]).find('span').text() + '</a></li>');
                                    }
                                    $('#executors_list li a').off("click").on("click", function (e) {
                                        e.preventDefault();
                                        let objId = "#" + $(this).attr('href'),
                                            pos = $(objId).position().top;
                                        $('.pop_up').animate({'scrollTop': pos}, 'slow');
                                    });
                                    $(".moveToTop").off("click").on("click", function (e) {
                                        e.preventDefault();
                                        $('.pop_up').animate({'scrollTop': 0}, 'slow');
                                    })
                                });
                            </script>
                        </div>

                        <div class='tab-panel group' id='tab_act-panel' style='display: none'>
                            <div class='group'>
                                <h3 class='item'>
                                    <strong>АКТ ПРОВЕРКИ</strong>
                                </h3>
                            </div>
                            <div class='group'>
                                <?
                                // Если акт уже существует — показываем его номер и дату (readonly)
                                if (strlen($agreement_data->doc_number) > 0): ?>
                                    <div class='item w_50'>
                                        <div class='el_data'>
                                            <label>Исходящий номер</label>
                                            <input class='el_input' type='text' readonly
                                                   value="<?= htmlspecialchars($agreement_data->doc_number) ?>"
                                                   title="Номер присваивается автоматически после согласования">
                                        </div>
                                    </div>
                                    <div class='item w_50'>
                                        <div class='el_data'>
                                            <label>Дата акта</label>
                                            <input class='el_input' type='text' readonly
                                                   value="<?= $date->correctDateFormatFromMysql($agreement_data->docdate) ?>"
                                                   title="Дата присваивается автоматически после согласования">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class='item w_100'>
                                        <div class='greyText' style='font-size:85%'>
                                            <span class='material-icons' style='font-size:15px;vertical-align:middle'>info</span>
                                            Номер и дата акта будут присвоены автоматически после завершения согласования.
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?
                                $ordersCount = count($orders['result']);
                                if ($ordersCount == 1) {
                                    $onlyTemplate = reset($orders['result']);
                                    ?>
                                    <input type='hidden' name='document' value='<?= $onlyTemplate->id ?>'>
                                    <div class='item w_50'>
                                        <div class='el_data'>
                                            <label>Шаблон акта</label>
                                            <div><?= htmlspecialchars($onlyTemplate->name) ?></div>
                                        </div>
                                    </div>
                                    <?
                                } elseif ($ordersCount > 1) {
                                    ?>
                                    <div class='item w_50 required'>
                                        <select data-label='Шаблон акта' name='document' required>
                                            <?
                                            echo $gui->buildSelectFromRegistry($orders['result'], [$agreement_data->document], true);
                                            ?>
                                        </select>
                                    </div>
                                    <?
                                } else {
                                    ?>
                                    <div class='item w_100'>
                                        <div class='greyText' style='color:#c00'>
                                            <span class='material-icons' style='font-size:15px;vertical-align:middle'>warning</span>
                                            Не найден шаблон акта для данного типа проверки. Обратитесь к администратору.
                                        </div>
                                    </div>
                                    <?
                                }
                                ?>
                                <div style="height:150px">&nbsp;</div>
                            </div>
                        </div>

                        <div class='tab-panel agreement_block' id='tab_agreement-panel' style='display: none'>
                            <div class='group'>
                                <h3 class='item'>
                                    <strong>ЛИСТ СОГЛАСОВАНИЯ</strong>
                                </h3>
                            </div>
                            <?
                            $doc = $db->selectOne('agreement', " WHERE source_table = 'checkstaff' AND source_id = ?", [$taskId]);
                            echo $reg->buildForm(67, [], (array)$doc);
                            ?>
                        </div>
                        <?
                    }
                    ?>
                    <div class='tab-panel' id='tab_my-panel'>
                        <?
                        //Если это просмотр результата
                        //получаем данные заполненных чек-листов автора
                        //$index - это id чек-листа
                        foreach ($checklist as $index => $ch) {
                            $editData[$index] = $db->selectOne($ch->table_name, ' WHERE id = ? LIMIT 1', [$chStaff->record_id]);
                            $editData = (array)$editData;
                            $editData['file_ids'] = json_decode($chStaff->file_ids);
                            $t = 0;
                            //Подставляем нарушения из чек-листов сотрудников группы проверки
                            if (is_array($otherVioArr) && count($otherVioArr) > 0) {

                                foreach ($otherVioArr as $i => $vi) {
                                    $editData[$index]->violations_text[$t] = $vi['name'];
                                    $editData[$index]->violations_type[$t] = $vi['violation'];
                                    $editData[$index]->violations_id[$t] = $vi['id'];
                                    $editData[$index]->otherAuthor[$t] = $vi['otherAuthor'];
                                    $t++;
                                }
                            }

                            $violations = $db->select('checksviolations', ' WHERE tasks = ? AND checklist = ?',
                                [$taskId, $index]
                            );
                            if ($violations) {
                                $vc = $t;
                                foreach ($violations as $vi) {
                                    $editData[$index]->violations_text[$vc] = $vi->name;
                                    $editData[$index]->violations_type[$vc] = $vi->violations;
                                    $editData[$index]->violations_id[$vc] = $vi->id;
                                    $editData[$index]->otherAuthor[$vc] = '';
                                    $vc++;
                                }
                            }
                        }

                        echo $reg->buildAssignment($taskId, $view_result, $editData)['html'];
                        ?>
                    </div>
                    <div class='preview_block tab-panel' id='tab_preview-panel' style='display: none'>
                        <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
                    </div>
                    <div class='confirm'>
                        <?
                        if ($view_result == 0) {
                            ?>
                            <button class='button icon text' id='task_save'><span class='material-icons'>save</span>
                                Сохранить
                            </button>
                            <?
                            if (intval($chStaff->is_head) == 1) {
                                // Проверяем заполненность чек-листов всей группы
                                $groupTasks = $db->select('checkstaff',
                                    ' WHERE check_uid = ? AND institution = ?',
                                    [$chStaff->check_uid, $insId]
                                );
                                $groupTotal = count($groupTasks);
                                $groupFilled = 0;
                                $notFilledNames = [];
                                foreach ($groupTasks as $gt) {
                                    if (intval($gt->record_id) > 0) {
                                        $groupFilled++;
                                    } else {
                                        $userName = trim(
                                            ($users['array'][$gt->user][0] ?? '') . ' ' .
                                            ($users['array'][$gt->user][1] ?? '') . ' ' .
                                            ($users['array'][$gt->user][2] ?? '')
                                        );
                                        $notFilledNames[] = $userName ?: 'Пользователь #' . $gt->user;
                                    }
                                }
                                $signBlocked = $groupFilled < $groupTotal;
                                $signTitle = $signBlocked
                                    ? 'Не заполнены чек-листы: ' . implode(', ', $notFilledNames)
                                    : '';
                                ?>
                                <button class='button icon text green' id='sign'
                                    <?= $signBlocked ? 'disabled title="' . htmlspecialchars($signTitle) . '"' : '' ?>>
                                    <span class='material-icons'>verified</span>Подписать и сформировать акт
                                </button>
                                <?php if ($signBlocked): ?>
                                    <div class='greyText' style='font-size:85%; margin-top:6px'>
                                        <span class='material-icons'
                                              style='font-size:15px;vertical-align:middle'>info</span>
                                        Подписание заблокировано — не все члены группы заполнили чек-листы:<br>
                                        <strong><?= htmlspecialchars(implode(', ', $notFilledNames)) ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?
                            }
                        } else {
                            ?>
                            <button class='button icon text close'><span class='material-icons'>close</span>Закрыть
                            </button>
                            <?
                        }
                        ?>
                    </div>

                    <div class='tab-panel' id='tab_objections-panel' style='display:none'>
                        <div class='group'>
                            <h3 class='item'><strong>ВОЗРАЖЕНИЯ ОБЪЕКТА КОНТРОЛЯ</strong></h3>
                        </div>

                        <?php if (!$act_signed): ?>
                            <div class='group'>
                                <div class='item'>
                                    <div class='inform_block inform_warning'>
                                        <span class='material-icons'>info</span>
                                        Возражения можно подать только после подписания акта.
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($objections_count > 0): ?>
                            <!-- Уже поданы возражения — показываем всем -->
                            <div class='group'>
                                <div class='item w_50'>
                                    <div class='el_data'>
                                        <label>Дата подачи возражений</label>
                                        <div class='el_value'><?= htmlspecialchars($objections_date) ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class='group'>
                                <div class='item'>
                                    <div class='el_data'>
                                        <label>Текст возражений</label>
                                        <div class='el_value' style='white-space:pre-wrap'><?= htmlspecialchars($objections_text) ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php if (count($objections_files) > 0): ?>
                                <div class='group'>
                                    <div class='item'>
                                        <label>Прикреплённые файлы</label>
                                        <div id='objections_files_list'>
                                            <?php foreach ($objections_files as $fid): ?>
                                                <?php
                                                $fRec = $db->selectOne('files', ' WHERE id = ?', [intval($fid)]);
                                                if ($fRec):
                                                    ?>
                                                    <div class='file_item'>
                                                        <span class='material-icons'>attach_file</span>
                                                        <a href='/uploads/<?= htmlspecialchars($fRec->path) ?>'
                                                           target='_blank'><?= htmlspecialchars($fRec->name) ?></a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- ОК может редактировать свои возражения (если акт ещё не закрыт докладом) -->
                            <?php if ($is_object_control && is_null($agreement_data->report_id)): ?>
                                <div class='group'>
                                    <div class='item'>
                                        <button class='button icon text' id='btn_edit_objections'>
                                            <span class='material-icons'>edit</span>Редактировать возражения
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($is_object_control): ?>
                            <!-- ОК может подать возражения -->
                            <div class='group'>
                                <div class='item'>
                                    <div class='el_data'>
                                        <label>Текст возражений <span style='color:var(--color_04);font-size:12px'>(необязательно — можно только прикрепить файлы)</span></label>
                                        <textarea class='el_input' name='objections_text' rows='8'
                                                  style='width:100%;resize:vertical'
                                                  placeholder='Изложите ваши возражения по акту проверки...'></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class='group'>
                                <div class='item'>
                                    <label>Прикрепить файлы (возражения, пояснительные записки)</label>
                                    <div class='file_attach_area' id='objections_attach'>
                                        <input type='file' id='objections_files_input' name='objections_files[]'
                                               multiple style='display:none'>
                                        <button type='button' class='button icon text' id='btn_attach_objections'>
                                            <span class='material-icons'>attach_file</span>Прикрепить файлы
                                        </button>
                                        <div id='objections_files_preview'></div>
                                    </div>
                                </div>
                            </div>
                            <div class='group'>
                                <div class='item'>
                                    <button type='button' class='button icon text' id='btn_send_objections'
                                            data-act-id='<?= $act_id ?>'>
                                        <span class='material-icons'>send</span>Направить возражения
                                    </button>
                                    <span style='margin-left:12px;color:var(--color_04);font-size:12px'>
                    После отправки возражения поступят в министерство
                </span>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Министерство — возражений нет -->
                            <div class='group'>
                                <div class='item'>
                                    <div class='inform_block'>
                                        <span class='material-icons'>check_circle</span>
                                        Объект контроля не направил возражений по акту.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <!--script src="/modules/calendar/js/registry.js"></script-->
        <script src='/js/assets/agreement_list.js'></script>
        <script>
            $(document).trigger('task_viewed', [{taskId: <?=$taskId?>}]);
            $(document).ready(function () {
                el_app.mainInit();
                agreement_list.agreement_list_init();
                $('[name=initiator]').val("<?=$_SESSION['user_id']?>").trigger('chosen:updated');

                $('select[name=agreementtemplate]').off('change').on('change', function () {
                    $.post('/', {ajax: 1, action: 'getDocTemplate', temp_id: $(this).val()}, function (data) {
                        console.log(data);
                        let answer = JSON.parse(data),
                            agreementlist = JSON.parse(answer.agreementlist);
                        $('[name=brief]').val(answer.brief);
                        $('[name=initiator]').val(answer.initiator).trigger('chosen:updated');
                        $.post('/', {
                            ajax: 1,
                            action: 'buildAgreement',
                            agreementlist: answer.agreementlist
                        }, function (data) {
                            $('.agreement_list_group').html(data);
                            el_app.mainInit();
                            agreement_list.agreement_list_init();
                        });
                    });
                });

                // Валидация чек-листа перед сохранением
                function checklistFilled() {
                    let filled = false;
                    $('#tab_my-panel .checklist input, #tab_my-panel .checklist select, #tab_my-panel .checklist textarea').each(function () {
                        if ($(this).val() && $(this).val().toString().trim() !== '' && $(this).val() !== '0') {
                            filled = true;
                            return false; // break
                        }
                    });
                    return filled;
                }

                // Проверка что все чужие чек-листы заполнены (для руководителя)
                function otherChecklistsFilled() {
                    let allFilled = true;
                    $('#tab_otherCheckLists-panel .executor').each(function () {
                        let hasValue = false;
                        $(this).nextUntil('.executor').find('input, select, textarea').each(function () {
                            if ($(this).val() && $(this).val().toString().trim() !== '' && $(this).val() !== '0') {
                                hasValue = true;
                                return false;
                            }
                        });
                        if (!hasValue) {
                            allFilled = false;
                            return false;
                        }
                    });
                    return allFilled;
                }

                $('#task_save').on('click', function (e) {
                    if (!checklistFilled()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        el_tools.notify(false, 'Ошибка', 'Заполните хотя бы одно поле чек-листа перед сохранением.');
                        return false;
                    }
                });

                $('#sign').on('mousedown', function (e) {
                    <?php if (intval($chStaff->is_head) == 1): ?>
                    if (!checklistFilled()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        el_tools.notify(false, 'Ошибка', 'Заполните свой чек-лист перед подписанием.');
                        return false;
                    }
                    // Проверяем шаблон акта (если показан select)
                    let $docSelect = $('select[name=document]');
                    if ($docSelect.length > 0 && (!$docSelect.val() || $docSelect.val() == '0')) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        el_tools.notify(false, 'Ошибка', 'Выберите шаблон акта.');
                        $('#tab_act').trigger('click');
                        $docSelect.focus();
                        return false;
                    }
                    let $executors = $('#tab_otherCheckLists-panel .executor');
                    if ($executors.length > 0 && !otherChecklistsFilled()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        el_tools.notify(false, 'Ошибка', 'Не все члены группы заполнили свои чек-листы. Подписание невозможно.');
                        return false;
                    }
                    <?php endif; ?>
                });

                $('#task_save').on('mousedown keypress', function () {
                    if ($("[name=sign]").val() == "") {
                        //$("#tab_act-panel *, #tab_agreement-panel *, #tab_otherCheckLists-panel *").attr('disabled', true);
                    }
                });

                if ($('#map').is('div')) {
                    if (!navigator.geolocation) {
                        document.getElementById('status').textContent = 'Геолокация не поддерживается вашим браузером';
                    } else {
                        navigator.geolocation.getCurrentPosition(
                            successCallback,
                            errorCallback,
                            {enableHighAccuracy: true, timeout: 5000, maximumAge: 0}
                        );
                    }
                }

                bindSign('checkstaff', <?=$taskId?>, <?=$_SESSION['user_id']?>);

                <?
                if($view_result == 1) {
                $reg->insertTaskLog($taskId, 'Задача открыта для просмотра');
                ?>
                $(".checklist *").attr("disabled", true);
                <?
                }else {
                $reg->insertTaskLog($taskId, 'Задача открыта для редактирования');
            }
                ?>
                $('#view_task .close').on('click', function () {
                    $.post('/', {ajax: 1, action: 'transaction_close', id: <?=$trans_id?>});
                    $.post('/', {ajax: 1, action: 'task_close', task_id: <?=$taskId?>});
                });
                $(window).on('beforeunload', function () {
                    $.post('/', {ajax: 1, action: 'transaction_close', id: <?=$trans_id?>});
                    $.post('/', {ajax: 1, action: 'task_close', task_id: <?=$taskId?>});
                });

                /*$('#view_task').formBlockCloner({
                    sourceBlock: '.violation',
                    cloneButton: '.new_violation',
                    removeButton: 'button icon clear',
                    titleSelector: '.violation_caption',
                    titleText: 'Нарушение №',

                    onBeforeClone: function($sourceBlock) {
                        console.log('Клонирование начато', $sourceBlock);
                        // Можно вернуть false для отмены клонирования
                    },

                    onAfterClone: function($newBlock, $sourceBlock, blockNumber) {
                        console.log('Создан блок №' + blockNumber, $newBlock);
                    },

                    onBeforeRemove: function($block) {
                        console.log('Удаление блока', $block);
                        //return confirm('Вы уверены, что хотите удалить этот блок?');
                    },

                    onAfterRemove: function() {
                        console.log('Блок удален');
                    }
                });*/
                el_registry.create_init();

                let $document = $('[name=document]'),
                    $tab_preview = $('#tab_preview');
                $document.on('change', function () {
                    if ($(this).val() > 0) {
                        //$tab_preview.show();
                    } else {
                        $tab_preview.hide();
                    }
                });
                if ($document.val() > 0) {
                    $tab_preview.show();
                }
                $tab_preview.on('click', function () {
                    let formData = $('form#view_task').serialize();
                    $('.preloader').fadeIn('fast');
                    $.post('/', {ajax: 1, action: 'planPdf', data: formData}, function (data) {
                        if (data.length > 0) {
                            $('#pdf-viewer').attr('src', 'data:application/pdf;base64,' + data);
                            $('.preloader').fadeOut('fast');
                        }
                    })
                });
            });

            function successCallback(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                document.getElementById('status').textContent =
                    `Ваши координаты: ${latitude.toFixed(6)}, ${longitude.toFixed(6)} (точность: ${Math.round(accuracy)} метров)`;


                ymaps.ready(init);

                function init() {
                    var myMap = new ymaps.Map('map', {
                        center: [latitude, longitude],//["<?=$ins->geo_lat?>", "<?=$ins->geo_lon?>"],
                        zoom: 15,
                        controls: ['zoomControl', 'typeSelector', 'fullscreenControl']
                    }, {
                        searchControlProvider: 'yandex#search'
                    });

                    const placemark = new ymaps.Placemark([latitude, longitude], {
                        hintContent: 'Вы здесь',
                        balloonContent: `Точность определения: ${Math.round(accuracy)} метров`
                    }, {
                        preset: 'islands#redDotIcon'
                    });
                    objectManager = new ymaps.ObjectManager({
                        // Чтобы метки начали кластеризоваться, выставляем опцию.
                        clusterize: true,
                        // ObjectManager принимает те же опции, что и кластеризатор.
                        gridSize: 32,
                        clusterDisableClickZoom: true
                    });

                    // Чтобы задать опции одиночным объектам и кластерам,
                    // обратимся к дочерним коллекциям ObjectManager.
                    //objectManager.objects.options.set('preset', 'islands#greenDotIcon');
                    //objectManager.clusters.options.set('preset', 'islands#greenClusterIcons');
                    myMap.geoObjects.add(
                        new ymaps.Placemark([<?=$ins->geo_lat?>, <?=$ins->geo_lon?>], {
                            balloonContent: '<?=$ins->name?>',
                            iconCaption: '<?=$ins->short?>'
                        }, {
                            preset: 'islands#blueDotIconWithCaption'
                        })
                    ).add(
                        new ymaps.Placemark([latitude, longitude], {
                            hintContent: 'Вы здесь',
                            balloonContent: `Точность определения: ${Math.round(accuracy)} метров`
                        }, {
                            preset: 'islands#redDotIcon'
                        })
                    );

                    // Добавляем круг точности
                    if (accuracy < 1000) { // Показываем круг только если точность меньше 1 км
                        const circle = new ymaps.Circle([
                            [latitude, longitude],
                            accuracy
                        ], {}, {
                            fillColor: '#00a0df77',
                            strokeColor: '#00a0df',
                            strokeOpacity: 0.8,
                            strokeWidth: 2
                        });

                        map.geoObjects.add(circle);
                    }

                    /* $.ajax({
                         url: '/js/assets/data.json'
                     }).done(function (data) {
                         objectManager.add(data);
                     });*/

                }
            }

            function errorCallback(error) {
                let errorMessage;
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Доступ к геолокации запрещен. Разрешите доступ в настройках браузера.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Информация о Вашем местоположении недоступна.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Время ожидания истекло.';
                        break;
                    case error.UNKNOWN_ERROR:
                        errorMessage = 'Произошла неизвестная ошибка.';
                        break;
                }

                document.getElementById('status').textContent = errorMessage;
            }

            if ($("#map").is("div")) {
                // Инициализация карты с центром по умолчанию (Москва), если не удалось получить местоположение
                ymaps.ready(function () {
                    new ymaps.Map('map', {
                        center: [<?=$ins->geo_lat?>, <?=$ins->geo_lon?>], // Координаты Москвы
                        zoom: 15,
                        controls: ['zoomControl']
                    }).geoObjects.add(
                        new ymaps.Placemark([<?=$ins->geo_lat?>, <?=$ins->geo_lon?>], {
                            balloonContent: '<?=$ins->name?>',
                            iconCaption: '<?=$ins->short?>'
                        }, {
                            preset: 'islands#blueDotIconWithCaption'
                        })
                    );
                });
            }

            (function () {
                // ── Прикрепление файлов ──────────────────────────────────
                var selectedFiles = [];

                $('#btn_attach_objections').on('click', function () {
                    $('#objections_files_input').trigger('click');
                });

                $('#objections_files_input').on('change', function () {
                    var files = this.files;
                    var $preview = $('#objections_files_preview');
                    for (var i = 0; i < files.length; i++) {
                        selectedFiles.push(files[i]);
                        $preview.append(
                            '<div class="file_item" data-idx="' + (selectedFiles.length - 1) + '">' +
                            '<span class="material-icons">attach_file</span>' +
                            '<span>' + files[i].name + '</span>' +
                            '<span class="material-icons" style="cursor:pointer;color:var(--color_red)" ' +
                            'onclick="$(this).parent().remove()">close</span></div>'
                        );
                    }
                });

                // ── Отправка возражений ──────────────────────────────────
                $('#btn_send_objections').on('click', function () {
                    var actId = $(this).data('act-id');
                    var text  = $('textarea[name=objections_text]').val().trim();
                    var $remainFiles = $('#objections_files_preview .file_item');

                    if (text.length === 0 && $remainFiles.length === 0) {
                        inform('Введите текст возражений или прикрепите файлы.', false);
                        return;
                    }

                    var fd = new FormData();
                    fd.append('ajax', '1');
                    fd.append('path', 'calendar');
                    fd.append('action', 'save_objections');
                    fd.append('params[act_id]', actId);
                    fd.append('params[text]', text);

                    // Собираем только оставшиеся файлы
                    $remainFiles.each(function () {
                        var idx = parseInt($(this).data('idx'));
                        if (!isNaN(idx) && selectedFiles[idx]) {
                            fd.append('objections_files[]', selectedFiles[idx]);
                        }
                    });

                    $.ajax({
                        url: '/',
                        method: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        headers: {'X-Requested-With': 'XMLHttpRequest', 'x-csrf-token': $.cookie('CSRF-TOKEN')},
                        success: function (data) {
                            try {
                                var r = JSON.parse(data);
                                inform(r.resultText, r.result);
                                if (r.result) {
                                    setTimeout(function () { el_app.reloadMainContent(); }, 1500);
                                }
                            } catch (e) { inform(data, false); }
                        }
                    });
                });

                // ── Редактирование (показываем форму заново) ─────────────
                $('#btn_edit_objections').on('click', function () {
                    // Перезагружаем диалог с флагом edit
                    el_app.dialog_close();
                    // TODO: при необходимости открыть диалог повторно с параметром edit=1
                });
            })();

        </script>
        <?php
    } else {
        ?>
        <script>
            alert("Эта запись редактируется пользователем <?=$busy->user_name?>");
            el_app.dialog_close("role_edit");
        </script>
        <?
    }
    ?>
    <? /* script src='https://cdn.jsdelivr.net/npm/ol@v8.2.0/dist/ol.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Создаем карту
            const map = new ol.Map({
                target: 'map',
                view: new ol.View({
                    center: ol.proj.fromLonLat([37.6176, 55.7558]),
                    zoom: 10,
                    projection: 'EPSG:3857'
                })
            });

            // Добавляем слой OSM для проверки
            const osmLayer = new ol.layer.Tile({
                source: new ol.source.OSM(),
                visible: true
            });
            map.addLayer(osmLayer);

            // Конфигурация WMTS
            try {
                // Создаем WMTS источник
                const wmtsSource = new ol.source.WMTS({
                    url: 'https://int.rgis.mosreg.ru/wmts/m10',
                    layer: 'm10', // Имя слоя (уточните правильное название слоя)
                    matrixSet: 'EPSG:3857', // Система тайлов
                    format: 'image/png',
                    projection: 'EPSG:3857',
                    tileGrid: new ol.tilegrid.WMTS({
                        origin: ol.extent.getTopLeft(ol.proj.get('EPSG:3857').getExtent()),
                        resolutions: [
                            156543.03392804097,
                            78271.51696402048,
                            39135.75848201024,
                            19567.87924100512,
                            9783.93962050256,
                            4891.96981025128,
                            2445.98490512564,
                            1222.99245256282,
                            611.49622628141,
                            305.748113140705,
                            152.8740565703525,
                            76.43702828517625,
                            38.21851414258813,
                            19.109257071294063,
                            9.554628535647032,
                            4.777314267823516
                        ],
                        matrixIds: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]
                    }),
                    crossOrigin: 'anonymous',
                    requestEncoding: 'REST',
                    tileLoadFunction: function(tile, src) {
                        const img = tile.getImage();
                        img.crossOrigin = 'anonymous';

                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', src, true);
                        xhr.setRequestHeader('Authorization', 'Basic ' + btoa('wmts_minsoc:vn7d1b'));
                        xhr.responseType = 'blob';

                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                const url = URL.createObjectURL(xhr.response);
                                img.onload = function() {
                                    URL.revokeObjectURL(url);
                                };
                                img.src = url;
                            } else {
                                console.error('Ошибка загрузки тайла:', xhr.status);
                            }
                        };

                        xhr.onerror = function() {
                            console.error('Ошибка сети при загрузке тайла');
                        };

                        xhr.send();
                    }
                });

                const wmtsLayer = new ol.layer.Tile({
                    source: wmtsSource,
                    opacity: 0.7,
                    visible: true
                });

                map.addLayer(wmtsLayer);

                // Через 3 секунды скрываем OSM слой
                setTimeout(() => {
                    osmLayer.setVisible(false);
                }, 3000);

            } catch (e) {
                console.error('Ошибка при создании WMTS слоя:', e);
            }

            map.updateSize();
        });
    </script*/ ?>
    <?php
} else {
    echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
}
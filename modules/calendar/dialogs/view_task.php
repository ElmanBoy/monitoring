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
$view_result = intval($_POST['params']['view_result']) == 1;

$chStaff = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);

if($chStaff == null){
    echo '<script>alert("Задача не найдена. Возможно, она была удалена.");setTimeout(function (){$(".wrap_pop_up").remove();}, 2000);</script>';
    die();
}
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
$task = $db->selectOne("tasks", " WHERE id = ?", [$chStaff->task_id]);
//Получение чек-листов
$checklist = $db->select("checklists", " WHERE id IN (" . implode(', ', json_decode($task->sheet)) . ")");
//Справочник шаблонов актов (для выбора щаблона при создании акта)
$orders = $db->getRegistry('documents', ' WHERE documentacial = 2');
//Справочник пользователей (для выбора подписантов)
$users = $db->getRegistry('users', "where roles <> '2'", [], ['surname', 'name', 'middle_name']);



//echo '<pre>'.$taskId;print_r($editData);echo '</pre>';

//Если такой акт уже есть
$agreement_data = $db->selectOne('agreement', " WHERE documentacial = 2 AND 
source_table = 'checkinstitutions' AND source_id = " . $insId
);
if (strlen($agreement_data->doc_number) > 0) {
    $act_number = $agreement_data->doc_number;
}

if ($auth->isLogin()) {

    //Открываем транзакцию
    $busy = $db->transactionOpen('checkstaff', intval($_POST['params']));
    $trans_id = $busy['trans_id'];

    if($busy != []){
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
                            #tab_otherCheckLists-panel .new_violation{
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
                        $allTask = $db->select('checkstaff', " WHERE 
                        check_uid = ? AND institution = ? AND id <> ?", [$chStaff->check_uid, $insId, $taskId]);
                        $otherBlockNumber = 2;
                        foreach ($allTask as $id => $other) {
                            echo '<div class="item w_100 executor" id="executor'.$other->user.'"><strong>Проверяющий:</strong>&nbsp;<span>'.
                                $users['array'][$other->user][0].' '.
                                $users['array'][$other->user][1].' '.
                                $users['array'][$other->user][2].
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
                                    "WHERE tasks = ? AND checklist = ?", [$id, $index]);

                                $voc = 0;
                                foreach($otherViolations as $ov){
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
                            $(document).ready(function(){
                                let $executors = $('#tab_otherCheckLists-panel .executor'),
                                    $executors_list = $('#executors_list');
                                $executors_list.html('');
                                for (let e = 0; e < $executors.length; e++) {
                                    $executors_list.append('<li><a href="' + $($executors[e]).attr("id") + '">'
                                        + $($executors[e]).find('span').text() + '</a></li>');
                                }
                                $('#executors_list li a').off("click").on("click", function(e){
                                    e.preventDefault();
                                    let objId = "#" + $(this).attr('href'),
                                        pos = $(objId).position().top;
                                    $('.pop_up').animate({'scrollTop': pos}, 'slow');
                                });
                                $(".moveToTop").off("click").on("click", function(e){
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
                            <div class='item w_50 required'>
                                <div class='el_data'>
                                    <label>Исходящий номер</label>
                                    <input class='el_input' type='text' name='doc_number' required
                                           value="<?= $act_number ?>">
                                </div>
                            </div>
                            <div class='item w_50 required'>
                                <div class='el_data'>
                                    <label>Дата акта</label>
                                    <input class='el_input single_date' type='date' name='docdate' required
                                           value="<?= strlen($agreement_data->docdate) > 0 ? $agreement_data->docdate : date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class='item w_50 required'>
                                <select data-label='Шаблон акта' name='document' required>
                                    <?
                                    echo $gui->buildSelectFromRegistry($orders['result'], [$agreement_data->document], true);
                                    ?>
                                </select>
                            </div>
                            <div class='item w_50 required'>
                                <div class='el_data'>
                                    <label>Период проведения проверки</label>
                                    <input class='el_input range_date' type='date' name='check_period' required
                                           value="<?= strlen($agreement_data->docdate) > 0 ? $agreement_data->docdate : date('Y-m-d') ?>">
                                </div>
                            </div>
                            <?/*div class='item w_50 required'>
                                <select data-label='Подписанты акта' name='signers[]' multiple required>
                                    <?
                                    $signers = strlen($agreement_data->signators) > 0 ? json_decode($agreement_data->signators) : [];
                                    echo $gui->buildSelectFromRegistry($users['result'], $signers,
                                        true, ['surname', 'name', 'middle_name'], ' '
                                    );
                                    ?>
                                </select>
                            </div*/?>
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
                        if(is_array($otherVioArr) && count($otherVioArr) > 0) {

                            foreach ($otherVioArr as $i => $vi) {
                                $editData[$index]->violations_text[$t] = $vi['name'];
                                $editData[$index]->violations_type[$t] = $vi['violation'];
                                $editData[$index]->violations_id[$t] = $vi['id'];
                                $editData[$index]->otherAuthor[$t] = $vi['otherAuthor'];
                                $t++;
                            }
                        }

                        $violations = $db->select('checksviolations', ' WHERE tasks = ? AND checklist = ?',
                            [$taskId, $index]);
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
                            ?>
                            <button class='button icon text green' id='sign'><span
                                        class='material-icons'>verified</span>Подписать
                                и сформировать акт
                            </button>
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

            $('#task_save').on('mousedown keypress', function () {
                if($("[name=sign]").val() == "") {
                    //$("#tab_act-panel *, #tab_agreement-panel *, #tab_otherCheckLists-panel *").attr('disabled', true);
                }
            });

            if($('#map').is('div')) {
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
            }else{
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

    </script>
        <?php
    }else{
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
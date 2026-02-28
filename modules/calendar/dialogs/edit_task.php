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

$taskId = intval($_POST['params']['taskId']);
$view_result = intval($_POST['params']['view_result']);

$chStaff = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);

if($chStaff->object_type == 1) {
    $ins = $db->selectOne('institutions', ' WHERE id = ?', [$chStaff->institution]);
}else{
    $ins = $db->selectOne('persons', ' WHERE id = ?', [$chStaff->institution]);
}
/*$tasks = $db->getRegistry('tasks');
$ousr = $db->getRegistry('ousr');

$task = $db->selectOne("tasks", " WHERE id = ?", [$chStaff->task_id]);
$checklist = $db->selectOne("checklists", " WHERE id = ?", [$task->sheet]);

$dates = $chStaff->dates;*/

if ($auth->isLogin()) {

?>
    <style>
        #openlayers-container {
            position: relative;
            top: 0;
            left: 0;
            right: 0;
            bottom: 100px;
            height: 300px;
        }
        #map {
            width: 100%;
            height: 100%;
        }
    </style>
    <div class='pop_up drag' style='width: 60vw;'>
        <div class='title handle'>
            <div class='name'>Задача №<?=$taskId?></div>
            <div class='button icon close'><span class='material-icons'>close</span></div>
        </div>
        <div class='pop_up_body'>

            <div id='openlayers-container'>
                <div id='map'></div>
            </div>

            <form class='ajaxFrm' id='view_task' onsubmit='return false'>
                <input type='hidden' name='uid' value="<?= $plan_uid ?>">
                <input type='hidden' name='task_id' value="<?= $taskId ?>">
                <input type='hidden' name='ins' value="<?= $insId ?>">
                <input type='hidden' name='path' value='calendar'>


                <?
                echo $reg->buildAssignment($taskId, $view_result == 1)['html'];
                ?>
                <div class='confirm'>
                    <?
                    if($view_result == 0){
                    ?>
                    <button class='button icon text'><span class='material-icons'>save</span>Сохранить</button>
                    <?
                    }else{
                    ?>
                    <button class='button icon text close'><span class='material-icons'>close</span>Закрыть</button>
                    <?
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>
    <script src="/modules/calendar/js/registry.js"></script>
    <script>
        $(document).trigger('task_viewed', [{taskId: <?=$taskId?>}]);
    </script>

    <script>
        ymaps.ready(init);

        function init() {
            var myMap = new ymaps.Map('map', {
                    center: ["<?=$ins->geo_lat?>", "<?=$ins->geo_lon?>"],
                    zoom: 15,
                    controls: ['zoomControl', 'typeSelector',  'fullscreenControl']
                }, {
                    searchControlProvider: 'yandex#search'
                }),
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
            );

           /* $.ajax({
                url: '/js/assets/data.json'
            }).done(function (data) {
                objectManager.add(data);
            });*/

        }
    </script>
    <?/* script src='https://cdn.jsdelivr.net/npm/ol@v8.2.0/dist/ol.js'></script>
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
    </script*/?>
<?php
} else {
    echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
}
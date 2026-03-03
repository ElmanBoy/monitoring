<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Reports;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

/*if (isset($_GET['id']) && intval($_GET['id']) > 0 && !isset($_POST['params'])) {
	$regId = intval($_GET['id']);
} else {
	parse_str($_POST['params'], $paramArr);
	foreach ($paramArr as $name => $value) {
		$_GET[$name] = $value;
	}
	$regId = intval($_GET['id']);
	$_GET['url'] = $_POST['url'];
}*/
$regId = 42;

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$report = new Reports();

/*$table = $db->selectOne('registry', ' where id = ?', [$regId]);
$parent_item = $db->selectOne('documents', 'where parent=' . $regId . ' LIMIT 1');
$parents = $db->getRegistry('registry');
$items = $db->getRegistry($table->table_name);*/
$dashboard = $db->getRegistry('reports', ' WHERE place = 1 ORDER BY ordinal, id');

$subQuery = '';

$gui->set('module_id', 18);


//$regs = $gui->getTableData($table->table_name);
?>
<style>
    .ui-sortable-helper {
        box-shadow: var(--shadow-big);
    }

    .drag_handler {
        height: 25px;
    }
</style>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'Дашборд',
                //'registryList' => '',
                'renew' => 'Сбросить все фильтры',
                'logout' => 'Выйти'
            ]
        );
        ?>

        <? /*div class="button icon text" title="Журнал работ">
			<span class="material-icons">fact_check</span>Журнал работ
		</div*/ ?>
    </div>

</div>
<div class='scroll_wrap'>

    <ul class='group charts'>
        <li class='item w_41 block summary'>
            <small>За период с 01 января 2025г. по 30 июня 2025г.:</small><br>
            <span>119</span>
            проведено проверок.<br>
            <div style='padding-left:1rem'>
                <small>Из них:</small><br>
                <span style='color:green'>98</span>
                завершены.<br>
                <span style='color:orange'>15</span>
                требуют доработки.<br>
                <span style='color:red'>6</span>
                продолжаются.
            </div>
        </li>

        <?
        foreach ($dashboard['result'] as $chart) {
            $data = $report->getDataById($chart->id);
            if (is_array($data['data']) && count($data['data']) > 0) {
                ?>
                <li class='item w_41'>
                    <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
                    <?

                    if (count($data) > 0) {
                        try {
                            echo $report->buildCharByData($data['data']);
                        } catch (Exception $e) {
                        }
                    }
                    ?>
                </li>
                <?
            }
        }
        ?>
        <!--<li class='item w_41'>
            <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
            <div id='type_chart' style='width: 90vw;height:400px;margin:0 auto'></div>
        </li>
        <li class='item w_41'>
            <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
            <div id='result_chart' style='width: 90vw;height:400px;margin:0 auto'></div>
        </li>
        <li class='item w_41'>
            <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
            <div id='status_chart' style='width: 90vw;height:400px;margin:0 auto'></div>
        </li>
        <li class='item w_41'>
            <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
            <div id='department_chart' style='width: 90vw;height:800px;margin:0 auto'></div>
        </li>-->
        <li class='item w_41'>
            <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
            <div id='violation_chart' style='width: 90vw;height:400px;margin:0 auto'></div>
        </li>
        <!--<li class='item w_41'>
            <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
            <div id='inspector_chart' style='width: 90vw;height:800px;margin:0 auto'></div>
        </li>-->
        <li class='item w_41'>
            <span class='material-icons drag_handler' title='Переместить'>drag_handle</span>
            <div id='org_stats_chart' style='width: 90vw;height:600px;margin:0 auto'></div>
        </li>
    </ul>

    <script type='text/javascript'>
        function initCharts() {
            // График по типам проверок
            /* var typeChart = echarts.init(document.getElementById('type_chart'));
             typeChart.setOption({
                 title: {
                     text: 'Типы проведенных проверок',
                     subtext: 'Распределение по видам проверочных мероприятий',
                     left: 'center'
                 },
                 tooltip: {trigger: 'item'},
                 legend: {
                     type: 'scroll',
                     orient: 'vertical',
                     right: 0,
                     top: 80,
                     bottom: 20
                 },
                 toolbox: {
                     show: true,
                     feature: {
                         saveAsImage: {show: true}
                     }
                 },
                 series: [{
                     name: 'Типы проверок',
                     type: 'pie',
                     radius: ['40%', '70%'],
                     center: ['40%', '50%'],
                     itemStyle: {
                         borderRadius: 5,
                         borderColor: '#fff',
                         borderWidth: 2
                     },
                     data: [
                         {value: 45, name: 'Плановые проверки [45]'},
                         {value: 32, name: 'Внеплановые проверки [32]'},
                         {value: 22, name: 'Документарные проверки [22]'},
                         {value: 15, name: 'Выездные проверки [15]'},
                         {value: 5, name: 'Контрольные мероприятия [5]'}
                     ]
                 }]
             });

             // График по результатам проверок
             var resultChart = echarts.init(document.getElementById('result_chart'));
             resultChart.setOption({
                 title: {
                     text: 'Результаты проверок',
                     subtext: 'Выявленные нарушения по степени тяжести',
                     left: 'center'
                 },
                 tooltip: {trigger: 'item'},
                 legend: {
                     type: 'scroll',
                     orient: 'vertical',
                     right: 0,
                     top: 80,
                     bottom: 20
                 },
                 series: [{
                     name: 'Результаты',
                     type: 'pie',
                     radius: ['40%', '70%'],
                     center: ['40%', '50%'],
                     itemStyle: {
                         borderRadius: 5,
                         borderColor: '#fff',
                         borderWidth: 2
                     },
                     data: [
                         {value: 63, name: 'Без нарушений [63]', itemStyle: {color: '#28a745'}},
                         {value: 22, name: 'Незначительные нарушения [22]', itemStyle: {color: '#ffc107'}},
                         {value: 10, name: 'Существенные нарушения [10]', itemStyle: {color: '#fd7e14'}},
                         {value: 3, name: 'Грубые нарушения [3]', itemStyle: {color: '#dc3545'}}
                     ]
                 }]
             });

             // График по статусам проверок
             var statusChart = echarts.init(document.getElementById('status_chart'));
             statusChart.setOption({
                 title: {
                     text: 'Статусы проверок',
                     subtext: 'Текущее состояние проверочных мероприятий',
                     left: 'center'
                 },
                 tooltip: {trigger: 'item'},
                 series: [{
                     name: 'Статусы',
                     type: 'pie',
                     radius: ['40%', '70%'],
                     center: ['40%', '50%'],
                     itemStyle: {
                         borderRadius: 5,
                         borderColor: '#fff',
                         borderWidth: 2
                     },
                     data: [
                         {value: 98, name: 'Завершены [98]', itemStyle: {color: '#28a745'}},
                         {value: 15, name: 'Требуют доработки [15]', itemStyle: {color: '#ffc107'}},
                         {value: 6, name: 'В процессе [6]', itemStyle: {color: '#dc3545'}}
                     ]
                 }]
             });

             // График по подведомственным организациям
             var departmentChart = echarts.init(document.getElementById('department_chart'));
             departmentChart.setOption({
                 title: {text: 'Проверки по подведомственным организациям'},
                 tooltip: {
                     trigger: 'axis',
                     axisPointer: {type: 'shadow'}
                 },
                 grid: {
                     top: 80,
                     bottom: 30,
                     left: 250
                 },
                 xAxis: {
                     type: 'value',
                     position: 'top'
                 },
                 yAxis: {
                     type: 'category',
                     data: [
                         'ГБУ "Соцзащита" [18]',
                         'МФЦ Центрального округа [15]',
                         'ГБУ "Жилищник" [12]',
                         'ГАУ "Соцподдержка" [10]',
                         'МФЦ Северного округа [9]',
                         'ГБУ "Соцобслуживание" [8]',
                         'МФЦ Южного округа [7]',
                         'ГБУ "Пансионат Ветеран" [6]',
                         'МФЦ Западного округа [5]',
                         'ГБУ "Центр семьи" [5]',
                         'МФЦ Восточного округа [4]',
                         'ГБУ "Соцреабилитация" [3]',
                         'ГБУ "Детский дом" [2]',
                         'ГБУ "Геронтологический центр" [1]'
                     ]
                 },
                 series: [{
                     name: 'Количество проверок',
                     type: 'bar',
                     itemStyle: {
                         borderRadius: 2,
                         borderColor: '#fff',
                         borderWidth: 1
                     },
                     data: [18, 15, 12, 10, 9, 8, 7, 6, 5, 5, 4, 3, 2, 1]
                 }]
             });*/

            // График по видам нарушений
            var violationChart = echarts.init(document.getElementById('violation_chart'));
            violationChart.setOption({
                title: {text: 'Выявленные нарушения по категориям', left: 'center'},
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {type: 'shadow'}
                },
                grid: {
                    top: 80,
                    bottom: 30,
                    left: 300
                },
                xAxis: {type: 'value'},
                yAxis: {
                    type: 'category',
                    data: [
                        'Нарушение сроков оказания услуг [25]',
                        'Несоответствие отчетной документации [18]',
                        'Ненадлежащее ведение кадрового учета [15]',
                        'Нарушение финансовой дисциплины [12]',
                        'Нецелевое использование средств [8]',
                        'Нарушение условий договоров [6]',
                        'Несоблюдение санкционированного доступа [5]',
                        'Нарушение правил хранения документов [4]',
                        'Несоблюдение требований безопасности [3]'
                    ]
                },
                series: [{
                    name: 'Количество нарушений',
                    type: 'bar',
                    itemStyle: {
                        color: function (params) {
                            return params.value > 15 ? '#dc3545' :
                                params.value > 10 ? '#fd7e14' : '#ffc107';
                        },
                        borderRadius: 2,
                        borderColor: '#fff',
                        borderWidth: 1
                    },
                    data: [25, 18, 15, 12, 8, 6, 5, 4, 3]
                }]
            });

            // График по инспекторам
            /*var inspectorChart = echarts.init(document.getElementById('inspector_chart'));
            inspectorChart.setOption({
                title: {text: 'Проверки по инспекторам'},
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {type: 'shadow'}
                },
                grid: {
                    top: 80,
                    bottom: 30,
                    left: 250
                },
                xAxis: {type: 'value'},
                yAxis: {
                    type: 'category',
                    data: [
                        'Иванов А.С. [18]',
                        'Петрова Е.В. [15]',
                        'Сидоров И.П. [12]',
                        'Кузнецова О.Л. [10]',
                        'Васильев Д.М. [9]',
                        'Николаева Т.С. [8]',
                        'Смирнов П.А. [7]',
                        'Федорова Л.К. [6]',
                        'Павлов В.И. [5]',
                        'Громова А.Д. [5]'
                    ]
                },
                series: [{
                    name: 'Проведено проверок',
                    type: 'bar',
                    itemStyle: {
                        borderRadius: 2,
                        borderColor: '#fff',
                        borderWidth: 1
                    },
                    data: [18, 15, 12, 10, 9, 8, 7, 6, 5, 5]
                }]
            });*/

            var orgStatsChart = echarts.init(document.getElementById('org_stats_chart'));

            // Данные для графика
            var orgStatsData = [
                {
                    name: 'ГБУ "Соцзащита"',
                    checks: 18,
                    violations: 12,
                    critical: 3,
                    completed: 15,
                    inProgress: 3
                },
                {
                    name: 'ГАУСО МО «КЦСОиР»',
                    checks: 15,
                    violations: 8,
                    critical: 1,
                    completed: 12,
                    inProgress: 3
                },
                {
                    name: 'ГКУСО МО СЦПСиД «Созвездие»',
                    checks: 12,
                    violations: 9,
                    critical: 2,
                    completed: 10,
                    inProgress: 2
                },
                {
                    name: 'ГАУ "Соцподдержка"',
                    checks: 10,
                    violations: 5,
                    critical: 0,
                    completed: 8,
                    inProgress: 2
                },
                {
                    name: 'ГКУСО МО СЦПСиД «Маяк»',
                    checks: 9,
                    violations: 4,
                    critical: 0,
                    completed: 7,
                    inProgress: 2
                },
                {
                    name: 'ГБУ "Соцобслуживание"',
                    checks: 8,
                    violations: 6,
                    critical: 1,
                    completed: 6,
                    inProgress: 2
                }
            ];

            // Подготовка данных для ECharts
            var orgNames = orgStatsData.map(item => item.name);
            var checksData = orgStatsData.map(item => item.checks);
            var violationsData = orgStatsData.map(item => item.violations);
            var criticalData = orgStatsData.map(item => item.critical);
            var completedData = orgStatsData.map(item => item.completed);
            var inProgressData = orgStatsData.map(item => item.inProgress);

            // Настройки графика
            orgStatsChart.setOption({
                title: {
                    text: 'Статистика по подведомственным организациям',
                    subtext: 'Детализация проверочной деятельности по организациям',
                    left: 'center'
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {type: 'shadow'}
                },
                legend: {
                    data: ['Всего проверок', 'Выявлено нарушений', 'Критичных нарушений', 'Завершено проверок', 'Проверок в работе'],
                    top: 50
                },
                grid: {
                    top: '15%',
                    left: '3%',
                    right: '4%',
                    bottom: '0%',
                    containLabel: true
                },
                xAxis: {
                    type: 'value',
                    boundaryGap: [0, 0.01]
                },
                yAxis: {
                    type: 'category',
                    data: orgNames
                },
                series: [
                    {
                        name: 'Всего проверок',
                        type: 'bar',
                        data: checksData,
                        itemStyle: {color: '#5470C6'},
                        label: {
                            show: true,
                            position: 'right'
                        }
                    },
                    {
                        name: 'Выявлено нарушений',
                        type: 'bar',
                        data: violationsData,
                        itemStyle: {color: '#EE6666'},
                        label: {
                            show: true,
                            position: 'right'
                        }
                    },
                    {
                        name: 'Критичных нарушений',
                        type: 'bar',
                        data: criticalData,
                        itemStyle: {color: '#FF0000'},
                        label: {
                            show: true,
                            position: 'right'
                        }
                    },
                    {
                        name: 'Завершено проверок',
                        type: 'bar',
                        data: completedData,
                        itemStyle: {color: '#91CC75'},
                        label: {
                            show: true,
                            position: 'right'
                        }
                    },
                    {
                        name: 'Проверок в работе',
                        type: 'bar',
                        data: inProgressData,
                        itemStyle: {color: '#FAC858'},
                        label: {
                            show: true,
                            position: 'right'
                        }
                    }
                ],
                dataZoom: [
                    {
                        type: 'slider',
                        yAxisIndex: 0,
                        filterMode: 'filter'
                    }
                ]
            });


            // Обработка ресайза для всех графиков
            window.addEventListener('resize', function () {
                violationChart.resize();
                orgStatsChart.resize();
                /*typeChart.resize();
                resultChart.resize();
                statusChart.resize();
                departmentChart.resize();
                inspectorChart.resize();*/
            });
        }

        initCharts()

        $('.charts').nestedSortable({
            //axis: 'y',
            cursor: 'grabbing',
            listType: 'ul',
            handle: '.drag_handler',
            items: 'li',
            protectRoot: true,
            stop: function (event, ui) {
                console.log(event, ui);
                initCharts();
            }
        });
        //$("[title").tipsy()
    </script>

</div>
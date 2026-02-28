<?php

use Core\Gui;
use Core\Db;
use Core\Auth;

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

$table = $db->selectOne('registry', ' where id = ?', [$regId]);
$parent_item = $db->selectOne('documents', 'where parent=' . $regId . ' LIMIT 1');
$parents = $db->getRegistry('registry');
$items = $db->getRegistry($table->table_name);

$subQuery = '';

$gui->set('module_id', 18);


$regs = $gui->getTableData($table->table_name);
?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'Документы',
                //'registryList' => '',
                'renew' => 'Сбросить все фильтры',
                'create' => 'Новый шаблон',
                //'clone' => 'Копия записи',
                'delete' => 'Удалить выделенные',
                'filter_panel' => 'Открыть панель фильтров',
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
    <div class='group charts'>
        <div class='item w_41 block summary'>
            <small>За период с 01 мая 2025г. по 31 мая 2025г. :</small><br>
            <span>30 316</span>
            обращений всего получено.<br>
            <div style='padding-left:1rem'>
                <small>Из них:</small><br>
                <span style='color:green'>26 644</span>
                обращения решены.<br>
                <span style='color:red'>3 293</span>
                обращения являются
                претензиями.
                <br><small style='display: block; padding-left: 1rem'>Из них:<br>
                    <span style='color:green'>3 229</span> решены<br>
                    При среднем времени решения в
                    15 часов 6 минут 28 секунд.
                </small>
            </div>
        </div>
        <div class='item w_41'>
            <div id='main_chart'
                 style='width: 90vw; height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442906'>
                <div style='position: relative; width: 933px; height: 400px; padding: 0px; margin: 0px; border-width: 0px; cursor: default;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='800'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 400px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''
                     style='position: absolute; display: block; border-style: solid; white-space: nowrap; z-index: 9999999; box-shadow: rgba(0, 0, 0, 0.2) 1px 2px 10px; transition: opacity 0.2s cubic-bezier(0.23, 1, 0.32, 1), visibility 0.2s cubic-bezier(0.23, 1, 0.32, 1), transform 0.4s cubic-bezier(0.23, 1, 0.32, 1); background-color: rgb(255, 255, 255); border-width: 1px; border-radius: 4px; color: rgb(102, 102, 102); font: 14px / 21px sans-serif; padding: 10px; top: 0px; left: 0px; transform: translate3d(172px, 247px, 0px); border-color: rgb(238, 102, 102); pointer-events: none; visibility: hidden; opacity: 0;'>
                    <div style='margin: 0px 0 0;line-height:1;'>
                        <div style='font-size:14px;color:#666;font-weight:400;line-height:1;'>Обращения граждан по
                            категориям
                        </div>
                        <div style='margin: 10px 0 0;line-height:1;'>
                            <div style='margin: 0px 0 0;line-height:1;'><span
                                        style='display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:#ee6666;'></span><span
                                        style='font-size:14px;color:#666;font-weight:400;margin-left:2px'>САНКУР [3705]</span><span
                                        style='float:right;margin-left:20px;font-size:14px;color:#666;font-weight:900'>3,705</span>
                                <div style='clear:both'></div>
                            </div>
                            <div style='clear:both'></div>
                        </div>
                        <div style='clear:both'></div>
                    </div>
                </div>
            </div>
        </div>
        <div class='item w_41'>
            <div id='claim_chart'
                 style='width: 90vw; height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442907'>
                <div style='position: relative; width: 933px; height: 400px; padding: 0px; margin: 0px; border-width: 0px;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='800'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 400px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''></div>
            </div>
        </div>
        <div class='item w_41'>
            <div id='emo_chart'
                 style='width: 90vw; height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442908'>
                <div style='position: relative; width: 933px; height: 400px; padding: 0px; margin: 0px; border-width: 0px; cursor: default;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='800'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 400px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''></div>
            </div>
        </div>
        <div class='item w_41'>
            <div id='operators_chart'
                 style='width: 90vw; height: 800px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442911'>
                <div style='position: relative; width: 933px; height: 800px; padding: 0px; margin: 0px; border-width: 0px; cursor: default;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='1600'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 800px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''
                     style='position: absolute; display: block; border-style: solid; white-space: nowrap; z-index: 9999999; box-shadow: rgba(0, 0, 0, 0.2) 1px 2px 10px; transition: opacity 0.2s cubic-bezier(0.23, 1, 0.32, 1), visibility 0.2s cubic-bezier(0.23, 1, 0.32, 1), transform 0.4s cubic-bezier(0.23, 1, 0.32, 1); background-color: rgb(255, 255, 255); border-width: 1px; border-radius: 4px; color: rgb(102, 102, 102); font: 14px / 21px sans-serif; padding: 10px; top: 0px; left: 0px; transform: translate3d(546px, 687px, 0px); border-color: rgb(255, 255, 255); pointer-events: none; visibility: hidden; opacity: 0;'>
                    <div style='margin: 0px 0 0;line-height:1;'>
                        <div style='margin: 0px 0 0;line-height:1;'>
                            <div style='font-size:14px;color:#666;font-weight:400;line-height:1;'>Фадеев Федор Денисович
                                [2]
                            </div>
                            <div style='margin: 10px 0 0;line-height:1;'>
                                <div style='margin: 0px 0 0;line-height:1;'>
                                    <div style='margin: 0px 0 0;line-height:1;'><span
                                                style='display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:#5470c6;'></span><span
                                                style='font-size:14px;color:#666;font-weight:400;margin-left:2px'>Обращений</span><span
                                                style='float:right;margin-left:20px;font-size:14px;color:#666;font-weight:900'>2</span>
                                        <div style='clear:both'></div>
                                    </div>
                                    <div style='clear:both'></div>
                                </div>
                                <div style='clear:both'></div>
                            </div>
                            <div style='clear:both'></div>
                        </div>
                        <div style='clear:both'></div>
                    </div>
                </div>
            </div>
        </div>

        <div class='item w_41'>
            <div id='city_chart'
                 style='width: 90vw; height: 800px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442910'>
                <div style='position: relative; width: 933px; height: 800px; padding: 0px; margin: 0px; border-width: 0px; cursor: default;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='1600'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 800px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''
                     style='position: absolute; display: block; border-style: solid; white-space: nowrap; z-index: 9999999; box-shadow: rgba(0, 0, 0, 0.2) 1px 2px 10px; transition: opacity 0.2s cubic-bezier(0.23, 1, 0.32, 1), visibility 0.2s cubic-bezier(0.23, 1, 0.32, 1), transform 0.4s cubic-bezier(0.23, 1, 0.32, 1); background-color: rgb(255, 255, 255); border-width: 1px; border-radius: 4px; color: rgb(102, 102, 102); font: 14px / 21px sans-serif; padding: 10px; top: 0px; left: 0px; transform: translate3d(592px, 641px, 0px); border-color: rgb(255, 255, 255); pointer-events: none; visibility: hidden; opacity: 0;'>
                    <div style='margin: 0px 0 0;line-height:1;'>
                        <div style='margin: 0px 0 0;line-height:1;'>
                            <div style='font-size:14px;color:#666;font-weight:400;line-height:1;'>Другой регион [26]
                            </div>
                            <div style='margin: 10px 0 0;line-height:1;'>
                                <div style='margin: 0px 0 0;line-height:1;'>
                                    <div style='margin: 0px 0 0;line-height:1;'><span
                                                style='display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:#5470c6;'></span><span
                                                style='font-size:14px;color:#666;font-weight:400;margin-left:2px'>Обращений</span><span
                                                style='float:right;margin-left:20px;font-size:14px;color:#666;font-weight:900'>26</span>
                                        <div style='clear:both'></div>
                                    </div>
                                    <div style='clear:both'></div>
                                </div>
                                <div style='clear:both'></div>
                            </div>
                            <div style='clear:both'></div>
                        </div>
                        <div style='clear:both'></div>
                    </div>
                </div>
            </div>
        </div>

        <div class='item w_41'>
            <div id='rating_chart'
                 style='width: 90vw; height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442909'>
                <div style='position: relative; width: 933px; height: 400px; padding: 0px; margin: 0px; border-width: 0px; cursor: default;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='800'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 400px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''></div>
            </div>
        </div>

        <div class='item w_41'>
            <div id='gender_chart'
                 style='width: 90vw; height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442912'>
                <div style='position: relative; width: 933px; height: 400px; padding: 0px; margin: 0px; border-width: 0px; cursor: default;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='800'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 400px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''
                     style='position: absolute; display: block; border-style: solid; white-space: nowrap; z-index: 9999999; box-shadow: rgba(0, 0, 0, 0.2) 1px 2px 10px; transition: opacity 0.2s cubic-bezier(0.23, 1, 0.32, 1), visibility 0.2s cubic-bezier(0.23, 1, 0.32, 1), transform 0.4s cubic-bezier(0.23, 1, 0.32, 1); background-color: rgb(255, 255, 255); border-width: 1px; border-radius: 4px; color: rgb(102, 102, 102); font: 14px / 21px sans-serif; padding: 10px; top: 0px; left: 0px; transform: translate3d(347px, 128px, 0px); border-color: rgb(84, 112, 198); pointer-events: none; visibility: hidden; opacity: 0;'>
                    <div style='margin: 0px 0 0;line-height:1;'>
                        <div style='font-size:14px;color:#666;font-weight:400;line-height:1;'>Обращения граждан по
                            полу
                        </div>
                        <div style='margin: 10px 0 0;line-height:1;'>
                            <div style='margin: 0px 0 0;line-height:1;'><span
                                        style='display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:#5470c6;'></span><span
                                        style='font-size:14px;color:#666;font-weight:400;margin-left:2px'>Женский [23812]</span><span
                                        style='float:right;margin-left:20px;font-size:14px;color:#666;font-weight:900'>23,812</span>
                                <div style='clear:both'></div>
                            </div>
                            <div style='clear:both'></div>
                        </div>
                        <div style='clear:both'></div>
                    </div>
                </div>
            </div>
        </div>
        <div class='item w_41'>
            <div id='age_chart'
                 style='width: 90vw; height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442914'>
                <div style='position: relative; width: 933px; height: 400px; padding: 0px; margin: 0px; border-width: 0px;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='800'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 400px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''></div>
            </div>
        </div>

        <div class='item w_41'>
            <div id='benefits_chart'
                 style='width: 90vw; height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442913'>
                <div style='position: relative; width: 933px; height: 400px; padding: 0px; margin: 0px; border-width: 0px; cursor: pointer;'>
                    <canvas data-zr-dom-id='zr_0' width='1866' height='800'
                            style='position: absolute; left: 0px; top: 0px; width: 933px; height: 400px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''
                     style='position: absolute; display: block; border-style: solid; white-space: nowrap; z-index: 9999999; box-shadow: rgba(0, 0, 0, 0.2) 1px 2px 10px; transition: opacity 0.2s cubic-bezier(0.23, 1, 0.32, 1), visibility 0.2s cubic-bezier(0.23, 1, 0.32, 1), transform 0.4s cubic-bezier(0.23, 1, 0.32, 1); background-color: rgb(255, 255, 255); border-width: 1px; border-radius: 4px; color: rgb(102, 102, 102); font: 14px / 21px sans-serif; padding: 10px; top: 0px; left: 0px; transform: translate3d(100px, 237px, 0px); border-color: rgb(59, 162, 114); pointer-events: none; visibility: hidden; opacity: 0;'>
                    <div style='margin: 0px 0 0;line-height:1;'>
                        <div style='font-size:14px;color:#666;font-weight:400;line-height:1;'>Обращения граждан по
                            льготным категориям
                        </div>
                        <div style='margin: 10px 0 0;line-height:1;'>
                            <div style='margin: 0px 0 0;line-height:1;'><span
                                        style='display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:#3ba272;'></span><span
                                        style='font-size:14px;color:#666;font-weight:400;margin-left:2px'>Родитель/представитель ребенка-инвалида... [756]</span><span
                                        style='float:right;margin-left:20px;font-size:14px;color:#666;font-weight:900'>756</span>
                                <div style='clear:both'></div>
                            </div>
                            <div style='clear:both'></div>
                        </div>
                        <div style='clear:both'></div>
                    </div>
                </div>
            </div>
        </div>

        <div class='item w_91' style='width:85.22%'>
            <div id='top10' style='width: 90vw;min-height:400px;margin:0 auto'>
                <table class='topQuestions'>
                    <caption>Топ 10 популярных вопросов</caption>
                    <tbody>
                    <tr>
                        <th>Направление</th>
                        <th>Вопрос</th>
                        <th>Количество обращений</th>
                    </tr>
                    <tr>
                        <td>САНКУР</td>
                        <td>Номер очереди?</td>
                        <td>1709</td>
                    </tr>
                    <tr>
                        <td>ЖКУ, СУБСИДИИ</td>
                        <td>ЖКУ. Когда поступит компенсация?</td>
                        <td>1147</td>
                    </tr>
                    <tr>
                        <td>ЖКУ, СУБСИДИИ</td>
                        <td>ЖКУ. Как оформить\продлить компенсацию?</td>
                        <td>1103</td>
                    </tr>
                    <tr>
                        <td>ОКАЗАНИЕ МАТ ПОМОЩИ</td>
                        <td>РСД. Почему не поступила?</td>
                        <td>906</td>
                    </tr>
                    <tr>
                        <td>ОБЩЕЕ</td>
                        <td>Как связаться с СФР?</td>
                        <td>745</td>
                    </tr>
                    <tr>
                        <td>ЖКУ, СУБСИДИИ</td>
                        <td>СУБСИДИИ. Размер субсидии.</td>
                        <td>741</td>
                    </tr>
                    <tr>
                        <td>МЕРЫ СОЦ ПОДДЕРЖКИ СЕМЬЯМ И ДЕТЯМ</td>
                        <td>Единое пособие.</td>
                        <td>596</td>
                    </tr>
                    <tr>
                        <td>ОБЩЕЕ</td>
                        <td>Звонок прервался.</td>
                        <td>595</td>
                    </tr>
                    <tr>
                        <td>ЖКУ, СУБСИДИИ</td>
                        <td>СУБСИДИЯ. Статус заявления.</td>
                        <td>506</td>
                    </tr>
                    <tr>
                        <td>ОБЩЕЕ</td>
                        <td>МФЦ.Дистанционное консультирование граждан.</td>
                        <td>501</td>
                    </tr>
                    </tbody>
                </table>
                <!--<img src='/images/preloader.svg'>-->
            </div>
        </div>
        <div class='item w_91' style='width:85.22%'>
            <div id='top20' style='width: 90vw;min-height:400px;margin:0 auto'>
                <table class='topCallers'>
                    <caption>Топ 20 активно звонящих граждан</caption>
                    <tbody>
                    <tr>
                        <th>Ф.И.О.</th>
                        <th>Номер телефона</th>
                        <th>Количество звонков</th>
                    </tr>
                    <tr>
                        <td>Клава</td>
                        <td>+7 (917) 534-63-56</td>
                        <td>149</td>
                    </tr>
                    <tr>
                        <td>Клава Ивановна</td>
                        <td>+7 (917) 534-63-56</td>
                        <td>46</td>
                    </tr>
                    <tr>
                        <td>Копаев Дмитрий Викторович</td>
                        <td>+7 (925) 036-37-52</td>
                        <td>26</td>
                    </tr>
                    <tr>
                        <td>Ласунова Римма Петровна</td>
                        <td>+7 (917) 534-63-56</td>
                        <td>26</td>
                    </tr>
                    <tr>
                        <td>Мотуз Любовь Васильевна</td>
                        <td>+7 (985) 537-91-69</td>
                        <td>26</td>
                    </tr>
                    <tr>
                        <td>Пермякова Ольга Васильевна</td>
                        <td>+7 (903) 503-82-20</td>
                        <td>21</td>
                    </tr>
                    <tr>
                        <td>Сабирова Галина Андреевна</td>
                        <td>+7 (985) 039-58-99</td>
                        <td>17</td>
                    </tr>
                    <tr>
                        <td>-</td>
                        <td>+7 (917) 534-63-56</td>
                        <td>16</td>
                    </tr>
                    <tr>
                        <td>-</td>
                        <td>+7 (498) 602-12-80</td>
                        <td>16</td>
                    </tr>
                    <tr>
                        <td>Дворяшина Наталья Ивановна</td>
                        <td>+7 (915) 080-52-80</td>
                        <td>15</td>
                    </tr>
                    <tr>
                        <td>Шаропова Нигина Рахматовна</td>
                        <td>+7 (903) 539-47-07</td>
                        <td>15</td>
                    </tr>
                    <tr>
                        <td>Зелимханов Умар Ахьятович</td>
                        <td>+7 (999) 151-19-20</td>
                        <td>14</td>
                    </tr>
                    <tr>
                        <td>Воронков Александр Николаевич</td>
                        <td>+7 (901) 588-77-28</td>
                        <td>14</td>
                    </tr>
                    <tr>
                        <td>Клава Ивана Ивановна</td>
                        <td>+7 (917) 534-63-56</td>
                        <td>13</td>
                    </tr>
                    <tr>
                        <td>Агеева Татьяна Михайловна</td>
                        <td>+7 (985) 290-73-21</td>
                        <td>13</td>
                    </tr>
                    <tr>
                        <td>Пестрикова Светлана Михайловна</td>
                        <td>+7 (995) 113-59-31</td>
                        <td>13</td>
                    </tr>
                    <tr>
                        <td>Хапилина Надежда Николаевна</td>
                        <td>+7 (495) 555-94-74</td>
                        <td>13</td>
                    </tr>
                    <tr>
                        <td>Силищева Елена Владимировна</td>
                        <td>+7 (968) 437-23-42</td>
                        <td>13</td>
                    </tr>
                    <tr>
                        <td>Евдокимова Наталья Игоревна</td>
                        <td>+7 (989) 988-98-73</td>
                        <td>12</td>
                    </tr>
                    <tr>
                        <td>Мамедова Хумай Эльбрус Кызы</td>
                        <td>+7 (925) 551-64-35</td>
                        <td>12</td>
                    </tr>
                    </tbody>
                </table>
                <!--<img src='/images/preloader.svg'>-->
            </div>
        </div>
        <div class='item w_91' style='width:85.22%'>
            <div id='dynamic_chart'
                 style='width: 90vw; min-height: 400px; margin: 0px auto; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); position: relative;'
                 _echarts_instance_='ec_1751267442915'>
                <div style='position: relative; width: 1945px; height: 416px; padding: 0px; margin: 0px; border-width: 0px;'>
                    <canvas data-zr-dom-id='zr_0' width='3890' height='832'
                            style='position: absolute; left: 0px; top: 0px; width: 1945px; height: 416px; user-select: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); padding: 0px; margin: 0px; border-width: 0px;'></canvas>
                </div>
                <div class=''></div>
            </div>
            <ul id='dynamic_modeSwitch'>
                <li class='dynamicViewMode selected' data-mode='hour'>По часам
                </li>
                <li class='dynamicViewMode' data-mode='minute'>По минутам
                </li>
            </ul>
        </div>
    </div>

    <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main_chart'));

        option = {
            title: {
                text: 'Обращения граждан по направлениям',
                subtext: 'Количество обращений по направлениям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по категориям',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'multiple',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6950,
                            value: 6425,
                            name: 'ЖКУ, СУБСИДИИ [6425]'
                        },
                        {
                            id: 7466,
                            value: 4667,
                            name: 'ОБЩЕЕ [4667]'
                        },
                        {
                            id: 6952,
                            value: 4106,
                            name: 'ОКАЗАНИЕ МАТ ПОМОЩИ [4106]'
                        },
                        {
                            id: 7547,
                            value: 3705,
                            name: 'САНКУР [3705]'
                        },
                        {
                            id: 6948,
                            value: 3067,
                            name: 'МЕРЫ СОЦ ПОДДЕРЖКИ СЕМЬЯМ И ДЕТЯМ [3067]'
                        },
                        {
                            id: 6949,
                            value: 1949,
                            name: 'СКМО [1949]'
                        },
                        {
                            id: 7490,
                            value: 1169,
                            name: 'ВЕТЕРАНСКИЕ ВЫПЛАТЫ [1169]'
                        },
                        {
                            id: 6953,
                            value: 811,
                            name: 'ОПЕКА [811]'
                        },
                        {
                            id: 6954,
                            value: 785,
                            name: 'ПЕРЕВОД ЗВОНКА:МФЦ,МИНЗДРАВ,122-6 Москва, другое. [785]'
                        },
                        {
                            id: 7699,
                            value: 775,
                            name: 'ИНВАЛИД.РЕБЕНОК-ИНВАЛИД [775]'
                        },
                        {
                            id: 7879,
                            value: 753,
                            name: 'ОБРАТНЫЙ ЗВОНОК МИНИСТРА [753]'
                        },
                        {
                            id: 6951,
                            value: 750,
                            name: 'СОЦ.ОБСЛУЖИВАНИЕ [750]'
                        },
                        {
                            id: 7420,
                            value: 532,
                            name: 'УЧАСТНИКАМ СВО И ИХ СЕМЬЯМ [532]'
                        },
                        {
                            id: 7707,
                            value: 394,
                            name: 'ПРОТЕЗНО-ОРТОПЕДИЧЕСКАЯ ПОМОЩЬ [394]'
                        },
                        {
                            id: 7873,
                            value: 168,
                            name: 'ЗАГС [168]'
                        },
                        {
                            id: 6946,
                            value: 142,
                            name: 'ПОИСК РАБОТЫ [142]'
                        },
                        {
                            id: 7660,
                            value: 74,
                            name: 'ОПЕКА СОВЕРШЕННОЛЕТНИЕ [74]'
                        },
                        {
                            id: 6947,
                            value: 35,
                            name: 'БЕЖЕНЦЫ [35]'
                        },
                        {
                            id: 7414,
                            value: 7,
                            name: 'КОНТРАКТ С МИНОБОРОНОЙ [7]'
                        },
                        {
                            id: 7947,
                            value: 2,
                            name: 'НЕЛЕГАЛЬНАЯ ЗАНЯТОСТЬ [2]'
                        },
                    ]
                }
            ]
        };

        myChart.setOption(option);

        myChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'a.category');
        });
        window.addEventListener('resize', myChart.resize);

        var claimChart = echarts.init(document.getElementById('claim_chart'));

        option = {
            title: {
                text: 'Претензии граждан по направлениям',
                subtext: 'Количество претензий по направлениям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Претензии граждан по направлениям',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6950,
                            value: 800,
                            name: 'ЖКУ, СУБСИДИИ [800]'
                        },
                        {
                            id: 6952,
                            value: 571,
                            name: 'ОКАЗАНИЕ МАТ ПОМОЩИ [571]'
                        },
                        {
                            id: 6953,
                            value: 464,
                            name: 'ОПЕКА [464]'
                        },
                        {
                            id: 7547,
                            value: 445,
                            name: 'САНКУР [445]'
                        },
                        {
                            id: 6951,
                            value: 254,
                            name: 'СОЦ.ОБСЛУЖИВАНИЕ [254]'
                        },
                        {
                            id: 6948,
                            value: 215,
                            name: 'МЕРЫ СОЦ ПОДДЕРЖКИ СЕМЬЯМ И ДЕТЯМ [215]'
                        },
                        {
                            id: 6949,
                            value: 122,
                            name: 'СКМО [122]'
                        },
                        {
                            id: 7466,
                            value: 89,
                            name: 'ОБЩЕЕ [89]'
                        },
                        {
                            id: 7490,
                            value: 83,
                            name: 'ВЕТЕРАНСКИЕ ВЫПЛАТЫ [83]'
                        },
                        {
                            id: 7879,
                            value: 82,
                            name: 'ОБРАТНЫЙ ЗВОНОК МИНИСТРА [82]'
                        },
                        {
                            id: 7873,
                            value: 46,
                            name: 'ЗАГС [46]'
                        },
                        {
                            id: 7420,
                            value: 40,
                            name: 'УЧАСТНИКАМ СВО И ИХ СЕМЬЯМ [40]'
                        },
                        {
                            id: 7699,
                            value: 33,
                            name: 'ИНВАЛИД.РЕБЕНОК-ИНВАЛИД [33]'
                        },
                        {
                            id: 7707,
                            value: 25,
                            name: 'ПРОТЕЗНО-ОРТОПЕДИЧЕСКАЯ ПОМОЩЬ [25]'
                        },
                        {
                            id: 7660,
                            value: 24,
                            name: 'ОПЕКА СОВЕРШЕННОЛЕТНИЕ [24]'
                        },
                    ]
                }
            ]
        };

        claimChart.setOption(option);

        claimChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'a.claim_category');
        });
        window.addEventListener('resize', claimChart.resize);

        var emoChart = echarts.init(document.getElementById('emo_chart'));

        option = {
            title: {
                text: 'Эмоциональные состояния',
                subtext: 'Количество обращений по эмоциональным состояниям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Эмоциональные состояния',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6924,
                            value: 15730,
                            name: 'Положительное [15730]'
                        },
                        {
                            id: 6923,
                            value: 9964,
                            name: 'Нейтральное [9964]'
                        },
                        {
                            id: 6922,
                            value: 29,
                            name: 'Негативное [29]'
                        },
                    ]
                }
            ]
        };

        emoChart.setOption(option);

        emoChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.emotion');
        });
        window.addEventListener('resize', emoChart.resize);

        var ratingChart = echarts.init(document.getElementById('rating_chart'));

        option = {
            title: {
                text: 'Рейтинги ответов',
                subtext: 'Количество обращений по рейтингам ответов',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Рейтинги ответов',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 7307,
                            value: 1,
                            name: '1 [1]'
                        },
                    ]
                }
            ]
        };

        ratingChart.setOption(option);

        ratingChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'a.answer_rating');
        });
        window.addEventListener('resize', ratingChart.resize);


        var cityChart = echarts.init(document.getElementById('city_chart'));
        optionCity = {
            title: {
                text: 'Количество обращений по городским округам'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            grid: {
                top: 80,
                bottom: 30,
                left: 250
            },
            xAxis: {
                type: 'value',
                position: 'top',
                splitLine: {
                    lineStyle: {
                        type: 'dashed'
                    }
                }
            },
            yAxis: {
                type: 'category',
                axisLine: {show: true},
                axisLabel: {show: true, height: 20},
                axisTick: {show: true},
                splitLine: {show: true},
                data: [
                    'Восход [2]',
                    'Молодежный [2]',
                    'Ликино-Дулево [14]',
                    'Власиха [26]',
                    'Другой регион [26]',
                    'Озёры [33]',
                    'Протвино  [36]',
                    'Шаховская [55]',
                    'Черноголовка [58]',
                    'Лотошино [58]',
                    'Бронницы [68]',
                    'Ивантеевка [69]',
                    'Дзержинский [83]',
                    'Зарайск [94]',
                    'Дубна [94]',
                    'Краснознаменск [98]',
                    'Талдомский [99]',
                    'Видное [106]',
                    'Лыткарино [106]',
                    'Звенигород [145]',
                    'Серебряные Пруды [149]',
                    'Котельники [159]',
                    'Волоколамский [167]',
                    'Звонок Сорвался [183]',
                    'Фрязино [186]',
                    'Не из Московской области [195]',
                    'Жуковский [197]',
                    'Лобня [208]',
                    'Кашира [221]',
                    'Луховицкий [244]',
                    'МОСКВА [246]',
                    'Лосино-Петровский [249]',
                    'Можайск [258]',
                    'Ступино [271]',
                    'Реутов [290]',
                    'Шатура [311]',
                    'Клин [320]',
                    'Павловский Посад [325]',
                    'Рузский [326]',
                    'Долгопрудный [326]',
                    'Егорьевск [331]',
                    'Домодедово [414]',
                    'Истра [418]',
                    'Ленинский [430]',
                    'Серпухов [440]',
                    'Сергиево-Посадский [446]',
                    'Электросталь [541]',
                    'Солнечногорск [546]',
                    'Щелково [561]',
                    'Воскресенск [595]',
                    'Наро-Фоминский [624]',
                    'Чехов [628]',
                    'Химки [628]',
                    'Богородский [653]',
                    'Ногинск [653]',
                    'Мытищи [660]',
                    'Дмитровский [729]',
                    'Коломна [735]',
                    'Королев [735]',
                    'Орехово-Зуевский [799]',
                    'Раменский [893]',
                    'Красногорск [954]',
                    'Пушкинский [1073]',
                    'Подольск [1097]',
                    'Одинцовский [1331]',
                    'Люберцы [1515]',
                    'Балашиха [1656]',
                    'Московская область [2566]',
                ]
            },
            series: [
                {
                    name: 'Обращений',
                    type: 'bar',
                    stack: 'Total',
                    barWidth: 7,
                    label: {
                        show: false,
                        formatter: '{b}'
                    },
                    itemStyle: {
                        borderRadius: 2,
                        borderColor: '#fff',
                        borderWidth: 1
                    },
                    selectedMode: 'multiple',
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    data: [
                        {id: 7240, value: 2},
                        {id: 7270, value: 2},
                        {id: 8055, value: 14},
                        {id: 7239, value: 26},
                        {id: 7233, value: 26},
                        {id: 7996, value: 33},
                        {id: 8035, value: 36},
                        {id: 7297, value: 55},
                        {id: 7294, value: 58},
                        {id: 7264, value: 58},
                        {id: 7236, value: 68},
                        {id: 7251, value: 69},
                        {id: 7244, value: 83},
                        {id: 7249, value: 94},
                        {id: 7242, value: 94},
                        {id: 7260, value: 98},
                        {id: 7290, value: 99},
                        {id: 8001, value: 106},
                        {id: 7266, value: 106},
                        {id: 8016, value: 145},
                        {id: 7286, value: 149},
                        {id: 7257, value: 159},
                        {id: 7238, value: 167},
                        {id: 7417, value: 183},
                        {id: 7292, value: 186},
                        {id: 7399, value: 195},
                        {id: 7248, value: 197},
                        {id: 7262, value: 208},
                        {id: 7253, value: 221},
                        {id: 7265, value: 244},
                        {id: 7268, value: 246},
                        {id: 7263, value: 249},
                        {id: 7269, value: 258},
                        {id: 7289, value: 271},
                        {id: 7282, value: 290},
                        {id: 7296, value: 311},
                        {id: 7254, value: 320},
                        {id: 7276, value: 325},
                        {id: 7284, value: 326},
                        {id: 7246, value: 326},
                        {id: 7247, value: 331},
                        {id: 7243, value: 414},
                        {id: 7252, value: 418},
                        {id: 7261, value: 430},
                        {id: 7287, value: 440},
                        {id: 7285, value: 446},
                        {id: 7300, value: 541},
                        {id: 7288, value: 546},
                        {id: 7298, value: 561},
                        {id: 7241, value: 595},
                        {id: 7272, value: 624},
                        {id: 7295, value: 628},
                        {id: 7293, value: 628},
                        {id: 7235, value: 653},
                        {id: 7315, value: 653},
                        {id: 7271, value: 660},
                        {id: 7245, value: 729},
                        {id: 7255, value: 735},
                        {id: 7256, value: 735},
                        {id: 7275, value: 799},
                        {id: 7281, value: 893},
                        {id: 7259, value: 954},
                        {id: 7279, value: 1073},
                        {id: 7277, value: 1097},
                        {id: 7273, value: 1331},
                        {id: 7267, value: 1515},
                        {id: 7237, value: 1656},
                        {id: 7721, value: 2566},
                    ]
                }
            ]
        };
        cityChart.setOption(optionCity);
        cityChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.city');
        });
        window.addEventListener('resize', cityChart.resize);

        var operatorChart = echarts.init(document.getElementById('operators_chart'));
        optionOperator = {
            title: {
                text: 'Количество обращений по операторам'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            grid: {
                top: 80,
                bottom: 30,
                left: 250
            },
            xAxis: {
                type: 'value',
                position: 'top',
                splitLine: {
                    lineStyle: {
                        type: 'dashed'
                    }
                }
            },
            yAxis: {
                type: 'category',
                axisLine: {show: true},
                axisLabel: {show: true},
                axisTick: {show: true},
                splitLine: {show: true},
                data: [
                    'Фадеев Федор Денисович [2]',
                    'Власова Наталья Николаевна [60]',
                    'Мельникова Наталья Юрьевна [106]',
                    'Лев Ирина Васильевна [142]',
                    'Иванилова Лариса Николаевна [196]',
                    'Гайко Нина Анатольевна [492]',
                    'Фролова Екатерина Юрьевна [587]',
                    'Бабаева Евгения Евгеньевна [635]',
                    'Андронова Валентина Васильевна [673]',
                    'Головинская Татьяна Юрьевна [769]',
                    'Дятликович Галина Михайловна [791]',
                    'Мельникова Марина Евгеньевна [818]',
                    'Молоткова Алла Викторовна [893]',
                    'Тельнова Лидия Евгеньевна [897]',
                    'Левшина Татьяна Николаевна [913]',
                    'Коренева Ольга Сергеевна [926]',
                    'Карпунина Людмила Ивановна [938]',
                    'Жарова Ольга Владимировна [1018]',
                    'Маслакова Татьяна Александровна [1146]',
                    'Колышева Ирина Вячеславовна [1182]',
                    'Кац Елена Анатольевна [1241]',
                    'Ширинян Елена Николаевна [1388]',
                    'Аверина Светлана Георгиевна [1400]',
                    'Смолко Ирина Андреевна [1414]',
                    'Хамитова Альбина Шакировна [1445]',
                    'Столярова Ольга Сергеевна [1464]',
                    'Кондратьева Ольга Викторовна [1516]',
                    'Карцева Юлия Николаевна [1596]',
                    'Тиньгаева Наталья Владимировна [1726]',
                    'Салаева Наталия Дмитриевна [1787]',
                    'Егорова Кира Юрьевна [2155]',
                ]
            },
            series: [
                {
                    name: 'Обращений',
                    type: 'bar',
                    stack: 'Total',
                    label: {
                        show: false,
                        formatter: '{b}'
                    },
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    selectedMode: 'multiple',
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    data: [
                        {id: 111, value: 2},
                        {id: 69, value: 60},
                        {id: 73, value: 106},
                        {id: 106, value: 142},
                        {id: 77, value: 196},
                        {id: 137, value: 492},
                        {id: 103, value: 587},
                        {id: 72, value: 635},
                        {id: 136, value: 673},
                        {id: 23, value: 769},
                        {id: 112, value: 791},
                        {id: 98, value: 818},
                        {id: 83, value: 893},
                        {id: 122, value: 897},
                        {id: 121, value: 913},
                        {id: 119, value: 926},
                        {id: 70, value: 938},
                        {id: 32, value: 1018},
                        {id: 82, value: 1146},
                        {id: 86, value: 1182},
                        {id: 80, value: 1241},
                        {id: 22, value: 1388},
                        {id: 93, value: 1400},
                        {id: 87, value: 1414},
                        {id: 84, value: 1445},
                        {id: 120, value: 1464},
                        {id: 18, value: 1516},
                        {id: 85, value: 1596},
                        {id: 78, value: 1726},
                        {id: 130, value: 1787},
                        {id: 33, value: 2155},
                    ]
                }
            ]
        };
        operatorChart.setOption(optionOperator);
        operatorChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.operator_id');
        });
        window.addEventListener('resize', operatorChart.resize);

        var genderChart = echarts.init(document.getElementById('gender_chart'));

        optionGender = {
            title: {
                text: 'Обращения граждан по полу',
                subtext: 'Количество обращений по полу',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                type: 'scroll',
                orient: 'vertical',
                right: 20,
                top: 80,
                bottom: 20
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по полу',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['25%', '50%'],
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 'w',
                            value: 23812,
                            name: 'Женский [23812]'
                        },
                        {
                            id: 'm',
                            value: 6504,
                            name: 'Мужской [6504]'
                        },
                    ]
                }
            ]
        };

        genderChart.setOption(optionGender);
        genderChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.gender');
        });
        window.addEventListener('resize', genderChart.resize);

        var benefitsChart = echarts.init(document.getElementById('benefits_chart'));

        optionBenefits = {
            title: {
                text: 'Обращения граждан по льготным категориям',
                subtext: 'Количество обращений по льготным категориям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                right: 0,
                top: 60,
                bottom: 20,
                left: 320,
                type: 'scroll',
                orient: 'vertical',
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по льготным категориям',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '55%',
                    selectedMode: 'multiple',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6829,
                            value: 4125,
                            name: 'Пенсионер [4125]'
                        },
                        {
                            id: 6830,
                            value: 3860,
                            name: 'Инвалид [3860]'
                        },
                        {
                            id: 6935,
                            value: 2060,
                            name: 'Многодетный родитель [2060]'
                        },
                        {
                            id: 6934,
                            value: 1172,
                            name: 'Ветеран Труда [1172]'
                        },
                        {
                            id: 6945,
                            value: 885,
                            name: 'Другое [885]'
                        },
                        {
                            id: 6927,
                            value: 756,
                            name: 'Родитель/представитель ребенка-инвалида... [756]'
                        },
                        {
                            id: 6937,
                            value: 608,
                            name: 'Гражданин, имеющий право на субсидию по ЖКУ... [608]'
                        },
                        {
                            id: 6938,
                            value: 574,
                            name: 'Малообеспеченная семья [574]'
                        },
                        {
                            id: 6941,
                            value: 507,
                            name: 'Потеря кормильца [507]'
                        },
                        {
                            id: 7517,
                            value: 464,
                            name: 'Родитель/представитель ребенка [464]'
                        },
                        {
                            id: 6932,
                            value: 406,
                            name: 'Ветеран боевых действий [406]'
                        },
                        {
                            id: 7346,
                            value: 245,
                            name: 'Предпенсионер [245]'
                        },
                        {
                            id: 6928,
                            value: 198,
                            name: 'Родитель в декрете [198]'
                        },
                        {
                            id: 6940,
                            value: 155,
                            name: 'Сельский бюджетник [155]'
                        },
                        {
                            id: 7716,
                            value: 148,
                            name: 'Член семьи погибшего ветерана ВОВ\БД\УЧАСТНИКА СВО... [148]'
                        },
                        {
                            id: 7355,
                            value: 130,
                            name: 'Мобилизованный/Участник СВО [130]'
                        },
                        {
                            id: 7747,
                            value: 111,
                            name: 'Представитель получателя МСП [111]'
                        },
                        {
                            id: 7834,
                            value: 104,
                            name: 'Член семьи участника СВО/ВБД [104]'
                        },
                        {
                            id: 6926,
                            value: 96,
                            name: 'Безработный [96]'
                        },
                        {
                            id: 6920,
                            value: 90,
                            name: 'Пенсионер силовых ведомств [90]'
                        },
                        {
                            id: 7723,
                            value: 81,
                            name: 'Ветеран военной службы [81]'
                        },
                        {
                            id: 6936,
                            value: 69,
                            name: 'Донор [69]'
                        },
                        {
                            id: 7359,
                            value: 69,
                            name: 'Гражданин без льгот  [69]'
                        },
                        {
                            id: 7982,
                            value: 49,
                            name: 'Ребенок из многодетной семьи [49]'
                        },
                        {
                            id: 7352,
                            value: 45,
                            name: 'Соц. работник [45]'
                        },
                        {
                            id: 7345,
                            value: 44,
                            name: 'Вдова военнослужащего [44]'
                        },
                        {
                            id: 7412,
                            value: 38,
                            name: 'Беременная женщина [38]'
                        },
                        {
                            id: 7703,
                            value: 38,
                            name: 'Опекун\усыновитель [38]'
                        },
                        {
                            id: 6931,
                            value: 31,
                            name: 'ЧАЭС [31]'
                        },
                        {
                            id: 6943,
                            value: 26,
                            name: 'Мать-одиночка [26]'
                        },
                        {
                            id: 6930,
                            value: 25,
                            name: 'Беженец [25]'
                        },
                        {
                            id: 7718,
                            value: 25,
                            name: 'Гражданин, взявший на себя обязанности по погребению... [25]'
                        },
                        {
                            id: 7413,
                            value: 24,
                            name: 'Одиноко проживающий(ая) [24]'
                        },
                        {
                            id: 7356,
                            value: 20,
                            name: 'Семья мобилизованного [20]'
                        },
                        {
                            id: 7702,
                            value: 20,
                            name: 'Реабилитированный [20]'
                        },
                        {
                            id: 7722,
                            value: 19,
                            name: 'Труженик тыла [19]'
                        },
                        {
                            id: 7903,
                            value: 16,
                            name: 'пенсионер,65+, проживающий в МО не менее 10 лет.... [16]'
                        },
                        {
                            id: 7353,
                            value: 12,
                            name: 'Сотрудник МФЦ [12]'
                        },
                        {
                            id: 7870,
                            value: 10,
                            name: 'Супруга (супруг) умершего участника Великой Отечественной войны (21ст.)... [10]'
                        },
                        {
                            id: 7796,
                            value: 10,
                            name: 'Ребенок-сирота [10]'
                        },
                        {
                            id: 7708,
                            value: 10,
                            name: 'Сотрудник гос.орга... [10]'
                        },
                        {
                            id: 6939,
                            value: 10,
                            name: 'Студент [10]'
                        },
                        {
                            id: 7857,
                            value: 9,
                            name: 'Лица, не отмеченные государственными или ведомственными наградами, имеющие трудовой стаж 50 лет и более... [9]'
                        },
                        {
                            id: 7687,
                            value: 9,
                            name: 'Несовершеннолетний узник концлагеря... [9]'
                        },
                        {
                            id: 7878,
                            value: 7,
                            name: 'ЧАЭС - члены семьи [7]'
                        },
                        {
                            id: 6933,
                            value: 7,
                            name: 'Ветеран ВОВ [7]'
                        },
                        {
                            id: 7523,
                            value: 6,
                            name: 'Освободившийся из мест лишения свободы... [6]'
                        },
                        {
                            id: 7732,
                            value: 4,
                            name: 'Иностранный гражданин [4]'
                        },
                        {
                            id: 7519,
                            value: 3,
                            name: 'Юбиляр [3]'
                        },
                        {
                            id: 7518,
                            value: 2,
                            name: 'Одинокий родитель [2]'
                        },
                        {
                            id: 7804,
                            value: 2,
                            name: 'Военнослужащие и лица рядового и начальствующего состава органов внутренних дел, Государственной противопо... [2]'
                        },
                        {
                            id: 8054,
                            value: 1,
                            name: 'Работающий(ая) [1]'
                        },
                        {
                            id: 7794,
                            value: 1,
                            name: 'Работник бюджетной организации [1]'
                        },
                        {
                            id: 7725,
                            value: 1,
                            name: 'Инвалид ВОВ [1]'
                        },
                        {
                            id: 7861,
                            value: 1,
                            name: 'Граждане, находящиеся в трудной жизненной ситуации... [1]'
                        },
                    ]
                }
            ]
        };

        benefitsChart.setOption(optionBenefits);
        benefitsChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.reduced');
        });
        window.addEventListener('resize', benefitsChart.resize);

        var ageChart = echarts.init(document.getElementById('age_chart'));

        optionAge = {
            title: {
                text: 'Обращения граждан по возрасту',
                subtext: 'Количество обращений по возрасту',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
            legend: {
                type: 'scroll',
                orient: 'vertical',
                right: 10,
                top: 80,
                bottom: 20
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по возрасту',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['25%', '50%'],

                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 7318,
                            value: 966,
                            name: 'До 18 [966]'
                        },
                        {
                            id: 7319,
                            value: 2942,
                            name: 'С 18 до 35 [2942]'
                        },
                        {
                            id: 7320,
                            value: 6548,
                            name: 'С 36 до 55 [6548]'
                        },
                        {
                            id: 7321,
                            value: 12356,
                            name: '55+ [12356]'
                        },
                    ]
                }
            ]
        };

        ageChart.setOption(optionAge);
        ageChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.age');
        });
        window.addEventListener('resize', ageChart.resize);

        var dynChart = echarts.init(document.getElementById('dynamic_chart'));

        optionDyn = {
            title: {
                text: 'Динамика поступления обращений',
                subtext: 'Количество обращений по датам',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                position: function (pt) {
                    return [pt[0], '10%'];
                }
            },
            toolbox: {
                feature: {
                    dataZoom: {
                        yAxisIndex: 'none'
                    },
                    restore: {},
                    saveAsImage: {}
                }
            },
            xAxis: {
                type: 'category',
                boundaryGap: true,
                data: [
                    '02 мая 2025г.  08:00:00',
                    '02 мая 2025г.  09:00:00',
                    '02 мая 2025г.  10:00:00',
                    '02 мая 2025г.  11:00:00',
                    '02 мая 2025г.  12:00:00',
                    '02 мая 2025г.  13:00:00',
                    '02 мая 2025г.  14:00:00',
                    '02 мая 2025г.  15:00:00',
                    '02 мая 2025г.  16:00:00',
                    '02 мая 2025г.  17:00:00',
                    '02 мая 2025г.  18:00:00',
                    '02 мая 2025г.  19:00:00',
                    '03 мая 2025г.  08:00:00',
                    '03 мая 2025г.  09:00:00',
                    '03 мая 2025г.  10:00:00',
                    '03 мая 2025г.  11:00:00',
                    '03 мая 2025г.  12:00:00',
                    '03 мая 2025г.  13:00:00',
                    '03 мая 2025г.  14:00:00',
                    '03 мая 2025г.  15:00:00',
                    '03 мая 2025г.  16:00:00',
                    '03 мая 2025г.  17:00:00',
                    '03 мая 2025г.  18:00:00',
                    '03 мая 2025г.  19:00:00',
                    '04 мая 2025г.  08:00:00',
                    '04 мая 2025г.  09:00:00',
                    '04 мая 2025г.  10:00:00',
                    '04 мая 2025г.  11:00:00',
                    '04 мая 2025г.  12:00:00',
                    '04 мая 2025г.  13:00:00',
                    '04 мая 2025г.  14:00:00',
                    '04 мая 2025г.  15:00:00',
                    '04 мая 2025г.  16:00:00',
                    '04 мая 2025г.  17:00:00',
                    '04 мая 2025г.  18:00:00',
                    '04 мая 2025г.  19:00:00',
                    '05 мая 2025г.  08:00:00',
                    '05 мая 2025г.  09:00:00',
                    '05 мая 2025г.  10:00:00',
                    '05 мая 2025г.  11:00:00',
                    '05 мая 2025г.  12:00:00',
                    '05 мая 2025г.  13:00:00',
                    '05 мая 2025г.  14:00:00',
                    '05 мая 2025г.  15:00:00',
                    '05 мая 2025г.  16:00:00',
                    '05 мая 2025г.  17:00:00',
                    '05 мая 2025г.  18:00:00',
                    '05 мая 2025г.  19:00:00',
                    '05 мая 2025г.  20:00:00',
                    '06 мая 2025г.  08:00:00',
                    '06 мая 2025г.  09:00:00',
                    '06 мая 2025г.  10:00:00',
                    '06 мая 2025г.  11:00:00',
                    '06 мая 2025г.  12:00:00',
                    '06 мая 2025г.  13:00:00',
                    '06 мая 2025г.  14:00:00',
                    '06 мая 2025г.  15:00:00',
                    '06 мая 2025г.  16:00:00',
                    '06 мая 2025г.  17:00:00',
                    '06 мая 2025г.  18:00:00',
                    '06 мая 2025г.  19:00:00',
                    '07 мая 2025г.  08:00:00',
                    '07 мая 2025г.  09:00:00',
                    '07 мая 2025г.  10:00:00',
                    '07 мая 2025г.  11:00:00',
                    '07 мая 2025г.  12:00:00',
                    '07 мая 2025г.  13:00:00',
                    '07 мая 2025г.  14:00:00',
                    '07 мая 2025г.  15:00:00',
                    '07 мая 2025г.  16:00:00',
                    '07 мая 2025г.  17:00:00',
                    '07 мая 2025г.  18:00:00',
                    '07 мая 2025г.  19:00:00',
                    '08 мая 2025г.  08:00:00',
                    '08 мая 2025г.  09:00:00',
                    '08 мая 2025г.  10:00:00',
                    '08 мая 2025г.  11:00:00',
                    '08 мая 2025г.  12:00:00',
                    '08 мая 2025г.  13:00:00',
                    '08 мая 2025г.  14:00:00',
                    '08 мая 2025г.  15:00:00',
                    '08 мая 2025г.  16:00:00',
                    '08 мая 2025г.  17:00:00',
                    '08 мая 2025г.  18:00:00',
                    '08 мая 2025г.  19:00:00',
                    '10 мая 2025г.  08:00:00',
                    '10 мая 2025г.  09:00:00',
                    '10 мая 2025г.  10:00:00',
                    '10 мая 2025г.  11:00:00',
                    '10 мая 2025г.  12:00:00',
                    '10 мая 2025г.  13:00:00',
                    '10 мая 2025г.  14:00:00',
                    '10 мая 2025г.  15:00:00',
                    '10 мая 2025г.  16:00:00',
                    '10 мая 2025г.  17:00:00',
                    '10 мая 2025г.  18:00:00',
                    '10 мая 2025г.  19:00:00',
                    '11 мая 2025г.  08:00:00',
                    '11 мая 2025г.  09:00:00',
                    '11 мая 2025г.  10:00:00',
                    '11 мая 2025г.  11:00:00',
                    '11 мая 2025г.  12:00:00',
                    '11 мая 2025г.  13:00:00',
                    '11 мая 2025г.  14:00:00',
                    '11 мая 2025г.  15:00:00',
                    '11 мая 2025г.  16:00:00',
                    '11 мая 2025г.  17:00:00',
                    '11 мая 2025г.  18:00:00',
                    '11 мая 2025г.  19:00:00',
                    '12 мая 2025г.  08:00:00',
                    '12 мая 2025г.  09:00:00',
                    '12 мая 2025г.  10:00:00',
                    '12 мая 2025г.  11:00:00',
                    '12 мая 2025г.  12:00:00',
                    '12 мая 2025г.  13:00:00',
                    '12 мая 2025г.  14:00:00',
                    '12 мая 2025г.  15:00:00',
                    '12 мая 2025г.  16:00:00',
                    '12 мая 2025г.  17:00:00',
                    '12 мая 2025г.  18:00:00',
                    '12 мая 2025г.  19:00:00',
                    '13 мая 2025г.  08:00:00',
                    '13 мая 2025г.  09:00:00',
                    '13 мая 2025г.  10:00:00',
                    '13 мая 2025г.  11:00:00',
                    '13 мая 2025г.  12:00:00',
                    '13 мая 2025г.  13:00:00',
                    '13 мая 2025г.  14:00:00',
                    '13 мая 2025г.  15:00:00',
                    '13 мая 2025г.  16:00:00',
                    '13 мая 2025г.  17:00:00',
                    '13 мая 2025г.  18:00:00',
                    '13 мая 2025г.  19:00:00',
                    '14 мая 2025г.  08:00:00',
                    '14 мая 2025г.  09:00:00',
                    '14 мая 2025г.  10:00:00',
                    '14 мая 2025г.  11:00:00',
                    '14 мая 2025г.  12:00:00',
                    '14 мая 2025г.  13:00:00',
                    '14 мая 2025г.  14:00:00',
                    '14 мая 2025г.  15:00:00',
                    '14 мая 2025г.  16:00:00',
                    '14 мая 2025г.  17:00:00',
                    '14 мая 2025г.  18:00:00',
                    '14 мая 2025г.  19:00:00',
                    '14 мая 2025г.  20:00:00',
                    '15 мая 2025г.  08:00:00',
                    '15 мая 2025г.  09:00:00',
                    '15 мая 2025г.  10:00:00',
                    '15 мая 2025г.  11:00:00',
                    '15 мая 2025г.  12:00:00',
                    '15 мая 2025г.  13:00:00',
                    '15 мая 2025г.  14:00:00',
                    '15 мая 2025г.  15:00:00',
                    '15 мая 2025г.  16:00:00',
                    '15 мая 2025г.  17:00:00',
                    '15 мая 2025г.  18:00:00',
                    '15 мая 2025г.  19:00:00',
                    '16 мая 2025г.  08:00:00',
                    '16 мая 2025г.  09:00:00',
                    '16 мая 2025г.  10:00:00',
                    '16 мая 2025г.  11:00:00',
                    '16 мая 2025г.  12:00:00',
                    '16 мая 2025г.  13:00:00',
                    '16 мая 2025г.  14:00:00',
                    '16 мая 2025г.  15:00:00',
                    '16 мая 2025г.  16:00:00',
                    '16 мая 2025г.  17:00:00',
                    '16 мая 2025г.  18:00:00',
                    '16 мая 2025г.  19:00:00',
                    '17 мая 2025г.  08:00:00',
                    '17 мая 2025г.  09:00:00',
                    '17 мая 2025г.  10:00:00',
                    '17 мая 2025г.  11:00:00',
                    '17 мая 2025г.  12:00:00',
                    '17 мая 2025г.  13:00:00',
                    '17 мая 2025г.  14:00:00',
                    '17 мая 2025г.  15:00:00',
                    '17 мая 2025г.  16:00:00',
                    '17 мая 2025г.  17:00:00',
                    '17 мая 2025г.  18:00:00',
                    '17 мая 2025г.  19:00:00',
                    '18 мая 2025г.  08:00:00',
                    '18 мая 2025г.  09:00:00',
                    '18 мая 2025г.  10:00:00',
                    '18 мая 2025г.  11:00:00',
                    '18 мая 2025г.  12:00:00',
                    '18 мая 2025г.  13:00:00',
                    '18 мая 2025г.  14:00:00',
                    '18 мая 2025г.  15:00:00',
                    '18 мая 2025г.  16:00:00',
                    '18 мая 2025г.  17:00:00',
                    '18 мая 2025г.  18:00:00',
                    '18 мая 2025г.  19:00:00',
                    '19 мая 2025г.  08:00:00',
                    '19 мая 2025г.  09:00:00',
                    '19 мая 2025г.  10:00:00',
                    '19 мая 2025г.  11:00:00',
                    '19 мая 2025г.  12:00:00',
                    '19 мая 2025г.  13:00:00',
                    '19 мая 2025г.  14:00:00',
                    '19 мая 2025г.  15:00:00',
                    '19 мая 2025г.  16:00:00',
                    '19 мая 2025г.  17:00:00',
                    '19 мая 2025г.  18:00:00',
                    '19 мая 2025г.  19:00:00',
                    '20 мая 2025г.  08:00:00',
                    '20 мая 2025г.  09:00:00',
                    '20 мая 2025г.  10:00:00',
                    '20 мая 2025г.  11:00:00',
                    '20 мая 2025г.  12:00:00',
                    '20 мая 2025г.  13:00:00',
                    '20 мая 2025г.  14:00:00',
                    '20 мая 2025г.  15:00:00',
                    '20 мая 2025г.  16:00:00',
                    '20 мая 2025г.  17:00:00',
                    '20 мая 2025г.  18:00:00',
                    '20 мая 2025г.  19:00:00',
                    '21 мая 2025г.  08:00:00',
                    '21 мая 2025г.  09:00:00',
                    '21 мая 2025г.  10:00:00',
                    '21 мая 2025г.  11:00:00',
                    '21 мая 2025г.  12:00:00',
                    '21 мая 2025г.  13:00:00',
                    '21 мая 2025г.  14:00:00',
                    '21 мая 2025г.  15:00:00',
                    '21 мая 2025г.  16:00:00',
                    '21 мая 2025г.  17:00:00',
                    '21 мая 2025г.  18:00:00',
                    '21 мая 2025г.  19:00:00',
                    '22 мая 2025г.  08:00:00',
                    '22 мая 2025г.  09:00:00',
                    '22 мая 2025г.  10:00:00',
                    '22 мая 2025г.  11:00:00',
                    '22 мая 2025г.  12:00:00',
                    '22 мая 2025г.  13:00:00',
                    '22 мая 2025г.  14:00:00',
                    '22 мая 2025г.  15:00:00',
                    '22 мая 2025г.  16:00:00',
                    '22 мая 2025г.  17:00:00',
                    '22 мая 2025г.  18:00:00',
                    '22 мая 2025г.  19:00:00',
                    '23 мая 2025г.  08:00:00',
                    '23 мая 2025г.  09:00:00',
                    '23 мая 2025г.  10:00:00',
                    '23 мая 2025г.  11:00:00',
                    '23 мая 2025г.  12:00:00',
                    '23 мая 2025г.  13:00:00',
                    '23 мая 2025г.  14:00:00',
                    '23 мая 2025г.  15:00:00',
                    '23 мая 2025г.  16:00:00',
                    '23 мая 2025г.  17:00:00',
                    '23 мая 2025г.  18:00:00',
                    '23 мая 2025г.  19:00:00',
                    '24 мая 2025г.  08:00:00',
                    '24 мая 2025г.  09:00:00',
                    '24 мая 2025г.  10:00:00',
                    '24 мая 2025г.  11:00:00',
                    '24 мая 2025г.  12:00:00',
                    '24 мая 2025г.  13:00:00',
                    '24 мая 2025г.  14:00:00',
                    '24 мая 2025г.  15:00:00',
                    '24 мая 2025г.  16:00:00',
                    '24 мая 2025г.  17:00:00',
                    '24 мая 2025г.  18:00:00',
                    '24 мая 2025г.  19:00:00',
                    '25 мая 2025г.  08:00:00',
                    '25 мая 2025г.  09:00:00',
                    '25 мая 2025г.  10:00:00',
                    '25 мая 2025г.  11:00:00',
                    '25 мая 2025г.  12:00:00',
                    '25 мая 2025г.  13:00:00',
                    '25 мая 2025г.  14:00:00',
                    '25 мая 2025г.  15:00:00',
                    '25 мая 2025г.  16:00:00',
                    '25 мая 2025г.  17:00:00',
                    '25 мая 2025г.  18:00:00',
                    '25 мая 2025г.  19:00:00',
                    '26 мая 2025г.  08:00:00',
                    '26 мая 2025г.  09:00:00',
                    '26 мая 2025г.  10:00:00',
                    '26 мая 2025г.  11:00:00',
                    '26 мая 2025г.  12:00:00',
                    '26 мая 2025г.  13:00:00',
                    '26 мая 2025г.  14:00:00',
                    '26 мая 2025г.  15:00:00',
                    '26 мая 2025г.  16:00:00',
                    '26 мая 2025г.  17:00:00',
                    '26 мая 2025г.  18:00:00',
                    '26 мая 2025г.  19:00:00',
                    '27 мая 2025г.  07:00:00',
                    '27 мая 2025г.  08:00:00',
                    '27 мая 2025г.  09:00:00',
                    '27 мая 2025г.  10:00:00',
                    '27 мая 2025г.  11:00:00',
                    '27 мая 2025г.  12:00:00',
                    '27 мая 2025г.  13:00:00',
                    '27 мая 2025г.  14:00:00',
                    '27 мая 2025г.  15:00:00',
                    '27 мая 2025г.  16:00:00',
                    '27 мая 2025г.  17:00:00',
                    '27 мая 2025г.  18:00:00',
                    '27 мая 2025г.  19:00:00',
                    '27 мая 2025г.  20:00:00',
                    '28 мая 2025г.  08:00:00',
                    '28 мая 2025г.  09:00:00',
                    '28 мая 2025г.  10:00:00',
                    '28 мая 2025г.  11:00:00',
                    '28 мая 2025г.  12:00:00',
                    '28 мая 2025г.  13:00:00',
                    '28 мая 2025г.  14:00:00',
                    '28 мая 2025г.  15:00:00',
                    '28 мая 2025г.  16:00:00',
                    '28 мая 2025г.  17:00:00',
                    '28 мая 2025г.  18:00:00',
                    '28 мая 2025г.  19:00:00',
                    '29 мая 2025г.  08:00:00',
                    '29 мая 2025г.  09:00:00',
                    '29 мая 2025г.  10:00:00',
                    '29 мая 2025г.  11:00:00',
                    '29 мая 2025г.  12:00:00',
                    '29 мая 2025г.  13:00:00',
                    '29 мая 2025г.  14:00:00',
                    '29 мая 2025г.  15:00:00',
                    '29 мая 2025г.  16:00:00',
                    '29 мая 2025г.  17:00:00',
                    '29 мая 2025г.  18:00:00',
                    '29 мая 2025г.  19:00:00',
                    '30 мая 2025г.  08:00:00',
                    '30 мая 2025г.  09:00:00',
                    '30 мая 2025г.  10:00:00',
                    '30 мая 2025г.  11:00:00',
                    '30 мая 2025г.  12:00:00',
                    '30 мая 2025г.  13:00:00',
                    '30 мая 2025г.  14:00:00',
                    '30 мая 2025г.  15:00:00',
                    '30 мая 2025г.  16:00:00',
                    '30 мая 2025г.  17:00:00',
                    '30 мая 2025г.  18:00:00',
                    '30 мая 2025г.  19:00:00',
                    '31 мая 2025г.  08:00:00',
                    '31 мая 2025г.  09:00:00',
                    '31 мая 2025г.  10:00:00',
                    '31 мая 2025г.  11:00:00',
                    '31 мая 2025г.  12:00:00',
                    '31 мая 2025г.  13:00:00',
                    '31 мая 2025г.  14:00:00',
                    '31 мая 2025г.  15:00:00',
                    '31 мая 2025г.  16:00:00',
                    '31 мая 2025г.  17:00:00',
                    '31 мая 2025г.  18:00:00',
                    '31 мая 2025г.  19:00:00',
                ]
            },
            yAxis: {
                type: 'value',
                boundaryGap: [0, '100%']
            },
            dataZoom: [
                {
                    type: 'inside',
                    start: 0,
                    end: 20
                },
                {
                    start: 0,
                    end: 20
                }
            ],
            series: [
                {
                    name: 'Количество обращений',
                    type: 'line',
                    smooth: true,
                    symbol: 'emptyCircle',
                    data: [
                        8,
                        18,
                        19,
                        35,
                        39,
                        32,
                        27,
                        20,
                        16,
                        20,
                        11,
                        8,
                        6,
                        20,
                        24,
                        27,
                        33,
                        22,
                        17,
                        22,
                        19,
                        17,
                        7,
                        4,
                        3,
                        10,
                        8,
                        13,
                        11,
                        8,
                        3,
                        6,
                        6,
                        6,
                        5,
                        5,
                        79,
                        166,
                        154,
                        163,
                        146,
                        170,
                        145,
                        187,
                        159,
                        49,
                        37,
                        44,
                        1,
                        91,
                        146,
                        164,
                        180,
                        180,
                        177,
                        138,
                        214,
                        176,
                        77,
                        55,
                        21,
                        72,
                        152,
                        179,
                        204,
                        134,
                        152,
                        86,
                        149,
                        144,
                        60,
                        42,
                        37,
                        17,
                        23,
                        37,
                        31,
                        41,
                        30,
                        16,
                        17,
                        44,
                        11,
                        2,
                        3,
                        7,
                        22,
                        21,
                        28,
                        15,
                        29,
                        15,
                        24,
                        19,
                        13,
                        8,
                        13,
                        18,
                        11,
                        11,
                        17,
                        23,
                        21,
                        18,
                        5,
                        11,
                        8,
                        5,
                        3,
                        89,
                        166,
                        152,
                        168,
                        135,
                        155,
                        200,
                        176,
                        184,
                        57,
                        40,
                        47,
                        94,
                        168,
                        144,
                        158,
                        130,
                        130,
                        145,
                        177,
                        214,
                        46,
                        38,
                        46,
                        99,
                        170,
                        191,
                        217,
                        172,
                        154,
                        148,
                        179,
                        169,
                        79,
                        37,
                        43,
                        1,
                        79,
                        176,
                        217,
                        238,
                        219,
                        184,
                        181,
                        186,
                        179,
                        97,
                        47,
                        45,
                        80,
                        196,
                        167,
                        191,
                        172,
                        133,
                        154,
                        178,
                        134,
                        60,
                        40,
                        31,
                        24,
                        32,
                        39,
                        39,
                        44,
                        23,
                        31,
                        37,
                        27,
                        13,
                        4,
                        7,
                        5,
                        7,
                        10,
                        14,
                        8,
                        8,
                        12,
                        8,
                        6,
                        6,
                        5,
                        7,
                        66,
                        176,
                        211,
                        218,
                        176,
                        154,
                        175,
                        218,
                        190,
                        95,
                        35,
                        33,
                        79,
                        189,
                        179,
                        237,
                        172,
                        148,
                        150,
                        214,
                        205,
                        74,
                        45,
                        41,
                        84,
                        178,
                        197,
                        224,
                        189,
                        166,
                        154,
                        176,
                        163,
                        42,
                        30,
                        32,
                        83,
                        132,
                        181,
                        239,
                        183,
                        172,
                        172,
                        213,
                        169,
                        62,
                        56,
                        49,
                        73,
                        167,
                        182,
                        249,
                        202,
                        171,
                        210,
                        197,
                        154,
                        72,
                        42,
                        23,
                        12,
                        26,
                        29,
                        40,
                        49,
                        28,
                        41,
                        35,
                        33,
                        19,
                        13,
                        7,
                        2,
                        6,
                        12,
                        15,
                        15,
                        13,
                        12,
                        15,
                        1,
                        3,
                        8,
                        10,
                        64,
                        142,
                        145,
                        142,
                        128,
                        104,
                        158,
                        132,
                        149,
                        54,
                        27,
                        38,
                        1,
                        62,
                        163,
                        163,
                        172,
                        160,
                        138,
                        173,
                        158,
                        179,
                        66,
                        36,
                        23,
                        1,
                        72,
                        116,
                        141,
                        171,
                        137,
                        119,
                        138,
                        152,
                        151,
                        71,
                        29,
                        41,
                        44,
                        154,
                        147,
                        180,
                        142,
                        118,
                        130,
                        157,
                        128,
                        59,
                        27,
                        23,
                        47,
                        168,
                        160,
                        174,
                        119,
                        133,
                        163,
                        157,
                        111,
                        35,
                        38,
                        20,
                        8,
                        18,
                        51,
                        45,
                        40,
                        29,
                        33,
                        31,
                        22,
                        16,
                        11,
                        10,
                    ]
                }
            ]
        };

        dynChart.setOption(optionDyn);
        window.addEventListener('resize', dynChart.resize);
    </script>



    <script type='text/javascript'>
        var myChart = echarts.init(document.getElementById('main_chart'));

        option = {
            title: {
                text: 'Обращения граждан по направлениям',
                subtext: 'Количество обращений по направлениям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по категориям',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'multiple',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6950,
                            value: 6425,
                            name: 'ЖКУ, СУБСИДИИ [6425]'
                        },
                        {
                            id: 7466,
                            value: 4667,
                            name: 'ОБЩЕЕ [4667]'
                        },
                        {
                            id: 6952,
                            value: 4106,
                            name: 'ОКАЗАНИЕ МАТ ПОМОЩИ [4106]'
                        },
                        {
                            id: 7547,
                            value: 3705,
                            name: 'САНКУР [3705]'
                        },
                        {
                            id: 6948,
                            value: 3067,
                            name: 'МЕРЫ СОЦ ПОДДЕРЖКИ СЕМЬЯМ И ДЕТЯМ [3067]'
                        },
                        {
                            id: 6949,
                            value: 1949,
                            name: 'СКМО [1949]'
                        },
                        {
                            id: 7490,
                            value: 1169,
                            name: 'ВЕТЕРАНСКИЕ ВЫПЛАТЫ [1169]'
                        },
                        {
                            id: 6953,
                            value: 811,
                            name: 'ОПЕКА [811]'
                        },
                        {
                            id: 6954,
                            value: 785,
                            name: 'ПЕРЕВОД ЗВОНКА:МФЦ,МИНЗДРАВ,122-6 Москва, другое. [785]'
                        },
                        {
                            id: 7699,
                            value: 775,
                            name: 'ИНВАЛИД.РЕБЕНОК-ИНВАЛИД [775]'
                        },
                        {
                            id: 7879,
                            value: 753,
                            name: 'ОБРАТНЫЙ ЗВОНОК МИНИСТРА [753]'
                        },
                        {
                            id: 6951,
                            value: 750,
                            name: 'СОЦ.ОБСЛУЖИВАНИЕ [750]'
                        },
                        {
                            id: 7420,
                            value: 532,
                            name: 'УЧАСТНИКАМ СВО И ИХ СЕМЬЯМ [532]'
                        },
                        {
                            id: 7707,
                            value: 394,
                            name: 'ПРОТЕЗНО-ОРТОПЕДИЧЕСКАЯ ПОМОЩЬ [394]'
                        },
                        {
                            id: 7873,
                            value: 168,
                            name: 'ЗАГС [168]'
                        },
                        {
                            id: 6946,
                            value: 142,
                            name: 'ПОИСК РАБОТЫ [142]'
                        },
                        {
                            id: 7660,
                            value: 74,
                            name: 'ОПЕКА СОВЕРШЕННОЛЕТНИЕ [74]'
                        },
                        {
                            id: 6947,
                            value: 35,
                            name: 'БЕЖЕНЦЫ [35]'
                        },
                        {
                            id: 7414,
                            value: 7,
                            name: 'КОНТРАКТ С МИНОБОРОНОЙ [7]'
                        },
                        {
                            id: 7947,
                            value: 2,
                            name: 'НЕЛЕГАЛЬНАЯ ЗАНЯТОСТЬ [2]'
                        },
                    ]
                }
            ]
        };

        myChart.setOption(option);

        myChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'a.category');
        });
        window.addEventListener('resize', myChart.resize);

        var claimChart = echarts.init(document.getElementById('claim_chart'));

        option = {
            title: {
                text: 'Претензии граждан по направлениям',
                subtext: 'Количество претензий по направлениям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Претензии граждан по направлениям',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6950,
                            value: 800,
                            name: 'ЖКУ, СУБСИДИИ [800]'
                        },
                        {
                            id: 6952,
                            value: 571,
                            name: 'ОКАЗАНИЕ МАТ ПОМОЩИ [571]'
                        },
                        {
                            id: 6953,
                            value: 464,
                            name: 'ОПЕКА [464]'
                        },
                        {
                            id: 7547,
                            value: 445,
                            name: 'САНКУР [445]'
                        },
                        {
                            id: 6951,
                            value: 254,
                            name: 'СОЦ.ОБСЛУЖИВАНИЕ [254]'
                        },
                        {
                            id: 6948,
                            value: 215,
                            name: 'МЕРЫ СОЦ ПОДДЕРЖКИ СЕМЬЯМ И ДЕТЯМ [215]'
                        },
                        {
                            id: 6949,
                            value: 122,
                            name: 'СКМО [122]'
                        },
                        {
                            id: 7466,
                            value: 89,
                            name: 'ОБЩЕЕ [89]'
                        },
                        {
                            id: 7490,
                            value: 83,
                            name: 'ВЕТЕРАНСКИЕ ВЫПЛАТЫ [83]'
                        },
                        {
                            id: 7879,
                            value: 82,
                            name: 'ОБРАТНЫЙ ЗВОНОК МИНИСТРА [82]'
                        },
                        {
                            id: 7873,
                            value: 46,
                            name: 'ЗАГС [46]'
                        },
                        {
                            id: 7420,
                            value: 40,
                            name: 'УЧАСТНИКАМ СВО И ИХ СЕМЬЯМ [40]'
                        },
                        {
                            id: 7699,
                            value: 33,
                            name: 'ИНВАЛИД.РЕБЕНОК-ИНВАЛИД [33]'
                        },
                        {
                            id: 7707,
                            value: 25,
                            name: 'ПРОТЕЗНО-ОРТОПЕДИЧЕСКАЯ ПОМОЩЬ [25]'
                        },
                        {
                            id: 7660,
                            value: 24,
                            name: 'ОПЕКА СОВЕРШЕННОЛЕТНИЕ [24]'
                        },
                    ]
                }
            ]
        };

        claimChart.setOption(option);

        claimChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'a.claim_category');
        });
        window.addEventListener('resize', claimChart.resize);

        var emoChart = echarts.init(document.getElementById('emo_chart'));

        option = {
            title: {
                text: 'Эмоциональные состояния',
                subtext: 'Количество обращений по эмоциональным состояниям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Эмоциональные состояния',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6924,
                            value: 15730,
                            name: 'Положительное [15730]'
                        },
                        {
                            id: 6923,
                            value: 9964,
                            name: 'Нейтральное [9964]'
                        },
                        {
                            id: 6922,
                            value: 29,
                            name: 'Негативное [29]'
                        },
                    ]
                }
            ]
        };

        emoChart.setOption(option);

        emoChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.emotion');
        });
        window.addEventListener('resize', emoChart.resize);

        var ratingChart = echarts.init(document.getElementById('rating_chart'));

        option = {
            title: {
                text: 'Рейтинги ответов',
                subtext: 'Количество обращений по рейтингам ответов',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
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
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Рейтинги ответов',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '49%',
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 7307,
                            value: 1,
                            name: '1 [1]'
                        },
                    ]
                }
            ]
        };

        ratingChart.setOption(option);

        ratingChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'a.answer_rating');
        });
        window.addEventListener('resize', ratingChart.resize);


        var cityChart = echarts.init(document.getElementById('city_chart'));
        optionCity = {
            title: {
                text: 'Количество обращений по городским округам'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            grid: {
                top: 80,
                bottom: 30,
                left: 250
            },
            xAxis: {
                type: 'value',
                position: 'top',
                splitLine: {
                    lineStyle: {
                        type: 'dashed'
                    }
                }
            },
            yAxis: {
                type: 'category',
                axisLine: {show: true},
                axisLabel: {show: true, height: 20},
                axisTick: {show: true},
                splitLine: {show: true},
                data: [
                    'Восход [2]',
                    'Молодежный [2]',
                    'Ликино-Дулево [14]',
                    'Власиха [26]',
                    'Другой регион [26]',
                    'Озёры [33]',
                    'Протвино  [36]',
                    'Шаховская [55]',
                    'Черноголовка [58]',
                    'Лотошино [58]',
                    'Бронницы [68]',
                    'Ивантеевка [69]',
                    'Дзержинский [83]',
                    'Зарайск [94]',
                    'Дубна [94]',
                    'Краснознаменск [98]',
                    'Талдомский [99]',
                    'Видное [106]',
                    'Лыткарино [106]',
                    'Звенигород [145]',
                    'Серебряные Пруды [149]',
                    'Котельники [159]',
                    'Волоколамский [167]',
                    'Звонок Сорвался [183]',
                    'Фрязино [186]',
                    'Не из Московской области [195]',
                    'Жуковский [197]',
                    'Лобня [208]',
                    'Кашира [221]',
                    'Луховицкий [244]',
                    'МОСКВА [246]',
                    'Лосино-Петровский [249]',
                    'Можайск [258]',
                    'Ступино [271]',
                    'Реутов [290]',
                    'Шатура [311]',
                    'Клин [320]',
                    'Павловский Посад [325]',
                    'Рузский [326]',
                    'Долгопрудный [326]',
                    'Егорьевск [331]',
                    'Домодедово [414]',
                    'Истра [418]',
                    'Ленинский [430]',
                    'Серпухов [440]',
                    'Сергиево-Посадский [446]',
                    'Электросталь [541]',
                    'Солнечногорск [546]',
                    'Щелково [561]',
                    'Воскресенск [595]',
                    'Наро-Фоминский [624]',
                    'Чехов [628]',
                    'Химки [628]',
                    'Богородский [653]',
                    'Ногинск [653]',
                    'Мытищи [660]',
                    'Дмитровский [729]',
                    'Коломна [735]',
                    'Королев [735]',
                    'Орехово-Зуевский [799]',
                    'Раменский [893]',
                    'Красногорск [954]',
                    'Пушкинский [1073]',
                    'Подольск [1097]',
                    'Одинцовский [1331]',
                    'Люберцы [1515]',
                    'Балашиха [1656]',
                    'Московская область [2566]',
                ]
            },
            series: [
                {
                    name: 'Обращений',
                    type: 'bar',
                    stack: 'Total',
                    barWidth: 7,
                    label: {
                        show: false,
                        formatter: '{b}'
                    },
                    itemStyle: {
                        borderRadius: 2,
                        borderColor: '#fff',
                        borderWidth: 1
                    },
                    selectedMode: 'multiple',
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    data: [
                        {id: 7240, value: 2},
                        {id: 7270, value: 2},
                        {id: 8055, value: 14},
                        {id: 7239, value: 26},
                        {id: 7233, value: 26},
                        {id: 7996, value: 33},
                        {id: 8035, value: 36},
                        {id: 7297, value: 55},
                        {id: 7294, value: 58},
                        {id: 7264, value: 58},
                        {id: 7236, value: 68},
                        {id: 7251, value: 69},
                        {id: 7244, value: 83},
                        {id: 7249, value: 94},
                        {id: 7242, value: 94},
                        {id: 7260, value: 98},
                        {id: 7290, value: 99},
                        {id: 8001, value: 106},
                        {id: 7266, value: 106},
                        {id: 8016, value: 145},
                        {id: 7286, value: 149},
                        {id: 7257, value: 159},
                        {id: 7238, value: 167},
                        {id: 7417, value: 183},
                        {id: 7292, value: 186},
                        {id: 7399, value: 195},
                        {id: 7248, value: 197},
                        {id: 7262, value: 208},
                        {id: 7253, value: 221},
                        {id: 7265, value: 244},
                        {id: 7268, value: 246},
                        {id: 7263, value: 249},
                        {id: 7269, value: 258},
                        {id: 7289, value: 271},
                        {id: 7282, value: 290},
                        {id: 7296, value: 311},
                        {id: 7254, value: 320},
                        {id: 7276, value: 325},
                        {id: 7284, value: 326},
                        {id: 7246, value: 326},
                        {id: 7247, value: 331},
                        {id: 7243, value: 414},
                        {id: 7252, value: 418},
                        {id: 7261, value: 430},
                        {id: 7287, value: 440},
                        {id: 7285, value: 446},
                        {id: 7300, value: 541},
                        {id: 7288, value: 546},
                        {id: 7298, value: 561},
                        {id: 7241, value: 595},
                        {id: 7272, value: 624},
                        {id: 7295, value: 628},
                        {id: 7293, value: 628},
                        {id: 7235, value: 653},
                        {id: 7315, value: 653},
                        {id: 7271, value: 660},
                        {id: 7245, value: 729},
                        {id: 7255, value: 735},
                        {id: 7256, value: 735},
                        {id: 7275, value: 799},
                        {id: 7281, value: 893},
                        {id: 7259, value: 954},
                        {id: 7279, value: 1073},
                        {id: 7277, value: 1097},
                        {id: 7273, value: 1331},
                        {id: 7267, value: 1515},
                        {id: 7237, value: 1656},
                        {id: 7721, value: 2566},
                    ]
                }
            ]
        };
        cityChart.setOption(optionCity);
        cityChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.city');
        });
        window.addEventListener('resize', cityChart.resize);

        var operatorChart = echarts.init(document.getElementById('operators_chart'));
        optionOperator = {
            title: {
                text: 'Количество обращений по операторам'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            grid: {
                top: 80,
                bottom: 30,
                left: 250
            },
            xAxis: {
                type: 'value',
                position: 'top',
                splitLine: {
                    lineStyle: {
                        type: 'dashed'
                    }
                }
            },
            yAxis: {
                type: 'category',
                axisLine: {show: true},
                axisLabel: {show: true},
                axisTick: {show: true},
                splitLine: {show: true},
                data: [
                    'Фадеев Федор Денисович [2]',
                    'Власова Наталья Николаевна [60]',
                    'Мельникова Наталья Юрьевна [106]',
                    'Лев Ирина Васильевна [142]',
                    'Иванилова Лариса Николаевна [196]',
                    'Гайко Нина Анатольевна [492]',
                    'Фролова Екатерина Юрьевна [587]',
                    'Бабаева Евгения Евгеньевна [635]',
                    'Андронова Валентина Васильевна [673]',
                    'Головинская Татьяна Юрьевна [769]',
                    'Дятликович Галина Михайловна [791]',
                    'Мельникова Марина Евгеньевна [818]',
                    'Молоткова Алла Викторовна [893]',
                    'Тельнова Лидия Евгеньевна [897]',
                    'Левшина Татьяна Николаевна [913]',
                    'Коренева Ольга Сергеевна [926]',
                    'Карпунина Людмила Ивановна [938]',
                    'Жарова Ольга Владимировна [1018]',
                    'Маслакова Татьяна Александровна [1146]',
                    'Колышева Ирина Вячеславовна [1182]',
                    'Кац Елена Анатольевна [1241]',
                    'Ширинян Елена Николаевна [1388]',
                    'Аверина Светлана Георгиевна [1400]',
                    'Смолко Ирина Андреевна [1414]',
                    'Хамитова Альбина Шакировна [1445]',
                    'Столярова Ольга Сергеевна [1464]',
                    'Кондратьева Ольга Викторовна [1516]',
                    'Карцева Юлия Николаевна [1596]',
                    'Тиньгаева Наталья Владимировна [1726]',
                    'Салаева Наталия Дмитриевна [1787]',
                    'Егорова Кира Юрьевна [2155]',
                ]
            },
            series: [
                {
                    name: 'Обращений',
                    type: 'bar',
                    stack: 'Total',
                    label: {
                        show: false,
                        formatter: '{b}'
                    },
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    selectedMode: 'multiple',
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    data: [
                        {id: 111, value: 2},
                        {id: 69, value: 60},
                        {id: 73, value: 106},
                        {id: 106, value: 142},
                        {id: 77, value: 196},
                        {id: 137, value: 492},
                        {id: 103, value: 587},
                        {id: 72, value: 635},
                        {id: 136, value: 673},
                        {id: 23, value: 769},
                        {id: 112, value: 791},
                        {id: 98, value: 818},
                        {id: 83, value: 893},
                        {id: 122, value: 897},
                        {id: 121, value: 913},
                        {id: 119, value: 926},
                        {id: 70, value: 938},
                        {id: 32, value: 1018},
                        {id: 82, value: 1146},
                        {id: 86, value: 1182},
                        {id: 80, value: 1241},
                        {id: 22, value: 1388},
                        {id: 93, value: 1400},
                        {id: 87, value: 1414},
                        {id: 84, value: 1445},
                        {id: 120, value: 1464},
                        {id: 18, value: 1516},
                        {id: 85, value: 1596},
                        {id: 78, value: 1726},
                        {id: 130, value: 1787},
                        {id: 33, value: 2155},
                    ]
                }
            ]
        };
        operatorChart.setOption(optionOperator);
        operatorChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.operator_id');
        });
        window.addEventListener('resize', operatorChart.resize);

        var genderChart = echarts.init(document.getElementById('gender_chart'));

        optionGender = {
            title: {
                text: 'Обращения граждан по полу',
                subtext: 'Количество обращений по полу',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                type: 'scroll',
                orient: 'vertical',
                right: 20,
                top: 80,
                bottom: 20
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по полу',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['25%', '50%'],
                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 'w',
                            value: 23812,
                            name: 'Женский [23812]'
                        },
                        {
                            id: 'm',
                            value: 6504,
                            name: 'Мужской [6504]'
                        },
                    ]
                }
            ]
        };

        genderChart.setOption(optionGender);
        genderChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.gender');
        });
        window.addEventListener('resize', genderChart.resize);

        var benefitsChart = echarts.init(document.getElementById('benefits_chart'));

        optionBenefits = {
            title: {
                text: 'Обращения граждан по льготным категориям',
                subtext: 'Количество обращений по льготным категориям',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            legend: {
                right: 0,
                top: 60,
                bottom: 20,
                left: 320,
                type: 'scroll',
                orient: 'vertical',
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по льготным категориям',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['40%', '50%'],
                    width: '55%',
                    selectedMode: 'multiple',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 6829,
                            value: 4125,
                            name: 'Пенсионер [4125]'
                        },
                        {
                            id: 6830,
                            value: 3860,
                            name: 'Инвалид [3860]'
                        },
                        {
                            id: 6935,
                            value: 2060,
                            name: 'Многодетный родитель [2060]'
                        },
                        {
                            id: 6934,
                            value: 1172,
                            name: 'Ветеран Труда [1172]'
                        },
                        {
                            id: 6945,
                            value: 885,
                            name: 'Другое [885]'
                        },
                        {
                            id: 6927,
                            value: 756,
                            name: 'Родитель/представитель ребенка-инвалида... [756]'
                        },
                        {
                            id: 6937,
                            value: 608,
                            name: 'Гражданин, имеющий право на субсидию по ЖКУ... [608]'
                        },
                        {
                            id: 6938,
                            value: 574,
                            name: 'Малообеспеченная семья [574]'
                        },
                        {
                            id: 6941,
                            value: 507,
                            name: 'Потеря кормильца [507]'
                        },
                        {
                            id: 7517,
                            value: 464,
                            name: 'Родитель/представитель ребенка [464]'
                        },
                        {
                            id: 6932,
                            value: 406,
                            name: 'Ветеран боевых действий [406]'
                        },
                        {
                            id: 7346,
                            value: 245,
                            name: 'Предпенсионер [245]'
                        },
                        {
                            id: 6928,
                            value: 198,
                            name: 'Родитель в декрете [198]'
                        },
                        {
                            id: 6940,
                            value: 155,
                            name: 'Сельский бюджетник [155]'
                        },
                        {
                            id: 7716,
                            value: 148,
                            name: 'Член семьи погибшего ветерана ВОВ\БД\УЧАСТНИКА СВО... [148]'
                        },
                        {
                            id: 7355,
                            value: 130,
                            name: 'Мобилизованный/Участник СВО [130]'
                        },
                        {
                            id: 7747,
                            value: 111,
                            name: 'Представитель получателя МСП [111]'
                        },
                        {
                            id: 7834,
                            value: 104,
                            name: 'Член семьи участника СВО/ВБД [104]'
                        },
                        {
                            id: 6926,
                            value: 96,
                            name: 'Безработный [96]'
                        },
                        {
                            id: 6920,
                            value: 90,
                            name: 'Пенсионер силовых ведомств [90]'
                        },
                        {
                            id: 7723,
                            value: 81,
                            name: 'Ветеран военной службы [81]'
                        },
                        {
                            id: 6936,
                            value: 69,
                            name: 'Донор [69]'
                        },
                        {
                            id: 7359,
                            value: 69,
                            name: 'Гражданин без льгот  [69]'
                        },
                        {
                            id: 7982,
                            value: 49,
                            name: 'Ребенок из многодетной семьи [49]'
                        },
                        {
                            id: 7352,
                            value: 45,
                            name: 'Соц. работник [45]'
                        },
                        {
                            id: 7345,
                            value: 44,
                            name: 'Вдова военнослужащего [44]'
                        },
                        {
                            id: 7412,
                            value: 38,
                            name: 'Беременная женщина [38]'
                        },
                        {
                            id: 7703,
                            value: 38,
                            name: 'Опекун\усыновитель [38]'
                        },
                        {
                            id: 6931,
                            value: 31,
                            name: 'ЧАЭС [31]'
                        },
                        {
                            id: 6943,
                            value: 26,
                            name: 'Мать-одиночка [26]'
                        },
                        {
                            id: 6930,
                            value: 25,
                            name: 'Беженец [25]'
                        },
                        {
                            id: 7718,
                            value: 25,
                            name: 'Гражданин, взявший на себя обязанности по погребению... [25]'
                        },
                        {
                            id: 7413,
                            value: 24,
                            name: 'Одиноко проживающий(ая) [24]'
                        },
                        {
                            id: 7356,
                            value: 20,
                            name: 'Семья мобилизованного [20]'
                        },
                        {
                            id: 7702,
                            value: 20,
                            name: 'Реабилитированный [20]'
                        },
                        {
                            id: 7722,
                            value: 19,
                            name: 'Труженик тыла [19]'
                        },
                        {
                            id: 7903,
                            value: 16,
                            name: 'пенсионер,65+, проживающий в МО не менее 10 лет.... [16]'
                        },
                        {
                            id: 7353,
                            value: 12,
                            name: 'Сотрудник МФЦ [12]'
                        },
                        {
                            id: 7870,
                            value: 10,
                            name: 'Супруга (супруг) умершего участника Великой Отечественной войны (21ст.)... [10]'
                        },
                        {
                            id: 7796,
                            value: 10,
                            name: 'Ребенок-сирота [10]'
                        },
                        {
                            id: 7708,
                            value: 10,
                            name: 'Сотрудник гос.орга... [10]'
                        },
                        {
                            id: 6939,
                            value: 10,
                            name: 'Студент [10]'
                        },
                        {
                            id: 7857,
                            value: 9,
                            name: 'Лица, не отмеченные государственными или ведомственными наградами, имеющие трудовой стаж 50 лет и более... [9]'
                        },
                        {
                            id: 7687,
                            value: 9,
                            name: 'Несовершеннолетний узник концлагеря... [9]'
                        },
                        {
                            id: 7878,
                            value: 7,
                            name: 'ЧАЭС - члены семьи [7]'
                        },
                        {
                            id: 6933,
                            value: 7,
                            name: 'Ветеран ВОВ [7]'
                        },
                        {
                            id: 7523,
                            value: 6,
                            name: 'Освободившийся из мест лишения свободы... [6]'
                        },
                        {
                            id: 7732,
                            value: 4,
                            name: 'Иностранный гражданин [4]'
                        },
                        {
                            id: 7519,
                            value: 3,
                            name: 'Юбиляр [3]'
                        },
                        {
                            id: 7518,
                            value: 2,
                            name: 'Одинокий родитель [2]'
                        },
                        {
                            id: 7804,
                            value: 2,
                            name: 'Военнослужащие и лица рядового и начальствующего состава органов внутренних дел, Государственной противопо... [2]'
                        },
                        {
                            id: 8054,
                            value: 1,
                            name: 'Работающий(ая) [1]'
                        },
                        {
                            id: 7794,
                            value: 1,
                            name: 'Работник бюджетной организации [1]'
                        },
                        {
                            id: 7725,
                            value: 1,
                            name: 'Инвалид ВОВ [1]'
                        },
                        {
                            id: 7861,
                            value: 1,
                            name: 'Граждане, находящиеся в трудной жизненной ситуации... [1]'
                        },
                    ]
                }
            ]
        };

        benefitsChart.setOption(optionBenefits);
        benefitsChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.reduced');
        });
        window.addEventListener('resize', benefitsChart.resize);

        var ageChart = echarts.init(document.getElementById('age_chart'));

        optionAge = {
            title: {
                text: 'Обращения граждан по возрасту',
                subtext: 'Количество обращений по возрасту',
                left: 'center'
            },
            tooltip: {
                trigger: 'item'
            },
            /*legend: {
                        top: '5%',
                        left: 'center'
                    },*/
            legend: {
                type: 'scroll',
                orient: 'vertical',
                right: 10,
                top: 80,
                bottom: 20
            },
            toolbox: {
                show: true,
                feature: {
                    mark: {show: true},
                    saveAsImage: {show: true}
                }
            },
            series: [
                {
                    name: 'Обращения граждан по возрасту',
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['25%', '50%'],

                    selectedMode: 'single',
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 5,
                        borderColor: '#fff',
                        borderWidth: 2
                    },
                    label: {
                        show: false,
                        position: 'center'
                    },
                    emphasis: {
                        label: {
                            show: false,
                            fontSize: 14,
                            fontWeight: 'bold'
                        },
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    },
                    labelLine: {
                        show: true
                    },
                    data: [
                        {
                            id: 7318,
                            value: 966,
                            name: 'До 18 [966]'
                        },
                        {
                            id: 7319,
                            value: 2942,
                            name: 'С 18 до 35 [2942]'
                        },
                        {
                            id: 7320,
                            value: 6548,
                            name: 'С 36 до 55 [6548]'
                        },
                        {
                            id: 7321,
                            value: 12356,
                            name: '55+ [12356]'
                        },
                    ]
                }
            ]
        };

        ageChart.setOption(optionAge);
        ageChart.on('click', function (params) {
            el_stat.setFilterFromPie(params, 'm.age');
        });
        window.addEventListener('resize', ageChart.resize);

        var dynChart = echarts.init(document.getElementById('dynamic_chart'));

        optionDyn = {
            title: {
                text: 'Динамика поступления обращений',
                subtext: 'Количество обращений по датам',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                position: function (pt) {
                    return [pt[0], '10%'];
                }
            },
            toolbox: {
                feature: {
                    dataZoom: {
                        yAxisIndex: 'none'
                    },
                    restore: {},
                    saveAsImage: {}
                }
            },
            xAxis: {
                type: 'category',
                boundaryGap: true,
                data: [
                    '02 мая 2025г.  08:00:00',
                    '02 мая 2025г.  09:00:00',
                    '02 мая 2025г.  10:00:00',
                    '02 мая 2025г.  11:00:00',
                    '02 мая 2025г.  12:00:00',
                    '02 мая 2025г.  13:00:00',
                    '02 мая 2025г.  14:00:00',
                    '02 мая 2025г.  15:00:00',
                    '02 мая 2025г.  16:00:00',
                    '02 мая 2025г.  17:00:00',
                    '02 мая 2025г.  18:00:00',
                    '02 мая 2025г.  19:00:00',
                    '03 мая 2025г.  08:00:00',
                    '03 мая 2025г.  09:00:00',
                    '03 мая 2025г.  10:00:00',
                    '03 мая 2025г.  11:00:00',
                    '03 мая 2025г.  12:00:00',
                    '03 мая 2025г.  13:00:00',
                    '03 мая 2025г.  14:00:00',
                    '03 мая 2025г.  15:00:00',
                    '03 мая 2025г.  16:00:00',
                    '03 мая 2025г.  17:00:00',
                    '03 мая 2025г.  18:00:00',
                    '03 мая 2025г.  19:00:00',
                    '04 мая 2025г.  08:00:00',
                    '04 мая 2025г.  09:00:00',
                    '04 мая 2025г.  10:00:00',
                    '04 мая 2025г.  11:00:00',
                    '04 мая 2025г.  12:00:00',
                    '04 мая 2025г.  13:00:00',
                    '04 мая 2025г.  14:00:00',
                    '04 мая 2025г.  15:00:00',
                    '04 мая 2025г.  16:00:00',
                    '04 мая 2025г.  17:00:00',
                    '04 мая 2025г.  18:00:00',
                    '04 мая 2025г.  19:00:00',
                    '05 мая 2025г.  08:00:00',
                    '05 мая 2025г.  09:00:00',
                    '05 мая 2025г.  10:00:00',
                    '05 мая 2025г.  11:00:00',
                    '05 мая 2025г.  12:00:00',
                    '05 мая 2025г.  13:00:00',
                    '05 мая 2025г.  14:00:00',
                    '05 мая 2025г.  15:00:00',
                    '05 мая 2025г.  16:00:00',
                    '05 мая 2025г.  17:00:00',
                    '05 мая 2025г.  18:00:00',
                    '05 мая 2025г.  19:00:00',
                    '05 мая 2025г.  20:00:00',
                    '06 мая 2025г.  08:00:00',
                    '06 мая 2025г.  09:00:00',
                    '06 мая 2025г.  10:00:00',
                    '06 мая 2025г.  11:00:00',
                    '06 мая 2025г.  12:00:00',
                    '06 мая 2025г.  13:00:00',
                    '06 мая 2025г.  14:00:00',
                    '06 мая 2025г.  15:00:00',
                    '06 мая 2025г.  16:00:00',
                    '06 мая 2025г.  17:00:00',
                    '06 мая 2025г.  18:00:00',
                    '06 мая 2025г.  19:00:00',
                    '07 мая 2025г.  08:00:00',
                    '07 мая 2025г.  09:00:00',
                    '07 мая 2025г.  10:00:00',
                    '07 мая 2025г.  11:00:00',
                    '07 мая 2025г.  12:00:00',
                    '07 мая 2025г.  13:00:00',
                    '07 мая 2025г.  14:00:00',
                    '07 мая 2025г.  15:00:00',
                    '07 мая 2025г.  16:00:00',
                    '07 мая 2025г.  17:00:00',
                    '07 мая 2025г.  18:00:00',
                    '07 мая 2025г.  19:00:00',
                    '08 мая 2025г.  08:00:00',
                    '08 мая 2025г.  09:00:00',
                    '08 мая 2025г.  10:00:00',
                    '08 мая 2025г.  11:00:00',
                    '08 мая 2025г.  12:00:00',
                    '08 мая 2025г.  13:00:00',
                    '08 мая 2025г.  14:00:00',
                    '08 мая 2025г.  15:00:00',
                    '08 мая 2025г.  16:00:00',
                    '08 мая 2025г.  17:00:00',
                    '08 мая 2025г.  18:00:00',
                    '08 мая 2025г.  19:00:00',
                    '10 мая 2025г.  08:00:00',
                    '10 мая 2025г.  09:00:00',
                    '10 мая 2025г.  10:00:00',
                    '10 мая 2025г.  11:00:00',
                    '10 мая 2025г.  12:00:00',
                    '10 мая 2025г.  13:00:00',
                    '10 мая 2025г.  14:00:00',
                    '10 мая 2025г.  15:00:00',
                    '10 мая 2025г.  16:00:00',
                    '10 мая 2025г.  17:00:00',
                    '10 мая 2025г.  18:00:00',
                    '10 мая 2025г.  19:00:00',
                    '11 мая 2025г.  08:00:00',
                    '11 мая 2025г.  09:00:00',
                    '11 мая 2025г.  10:00:00',
                    '11 мая 2025г.  11:00:00',
                    '11 мая 2025г.  12:00:00',
                    '11 мая 2025г.  13:00:00',
                    '11 мая 2025г.  14:00:00',
                    '11 мая 2025г.  15:00:00',
                    '11 мая 2025г.  16:00:00',
                    '11 мая 2025г.  17:00:00',
                    '11 мая 2025г.  18:00:00',
                    '11 мая 2025г.  19:00:00',
                    '12 мая 2025г.  08:00:00',
                    '12 мая 2025г.  09:00:00',
                    '12 мая 2025г.  10:00:00',
                    '12 мая 2025г.  11:00:00',
                    '12 мая 2025г.  12:00:00',
                    '12 мая 2025г.  13:00:00',
                    '12 мая 2025г.  14:00:00',
                    '12 мая 2025г.  15:00:00',
                    '12 мая 2025г.  16:00:00',
                    '12 мая 2025г.  17:00:00',
                    '12 мая 2025г.  18:00:00',
                    '12 мая 2025г.  19:00:00',
                    '13 мая 2025г.  08:00:00',
                    '13 мая 2025г.  09:00:00',
                    '13 мая 2025г.  10:00:00',
                    '13 мая 2025г.  11:00:00',
                    '13 мая 2025г.  12:00:00',
                    '13 мая 2025г.  13:00:00',
                    '13 мая 2025г.  14:00:00',
                    '13 мая 2025г.  15:00:00',
                    '13 мая 2025г.  16:00:00',
                    '13 мая 2025г.  17:00:00',
                    '13 мая 2025г.  18:00:00',
                    '13 мая 2025г.  19:00:00',
                    '14 мая 2025г.  08:00:00',
                    '14 мая 2025г.  09:00:00',
                    '14 мая 2025г.  10:00:00',
                    '14 мая 2025г.  11:00:00',
                    '14 мая 2025г.  12:00:00',
                    '14 мая 2025г.  13:00:00',
                    '14 мая 2025г.  14:00:00',
                    '14 мая 2025г.  15:00:00',
                    '14 мая 2025г.  16:00:00',
                    '14 мая 2025г.  17:00:00',
                    '14 мая 2025г.  18:00:00',
                    '14 мая 2025г.  19:00:00',
                    '14 мая 2025г.  20:00:00',
                    '15 мая 2025г.  08:00:00',
                    '15 мая 2025г.  09:00:00',
                    '15 мая 2025г.  10:00:00',
                    '15 мая 2025г.  11:00:00',
                    '15 мая 2025г.  12:00:00',
                    '15 мая 2025г.  13:00:00',
                    '15 мая 2025г.  14:00:00',
                    '15 мая 2025г.  15:00:00',
                    '15 мая 2025г.  16:00:00',
                    '15 мая 2025г.  17:00:00',
                    '15 мая 2025г.  18:00:00',
                    '15 мая 2025г.  19:00:00',
                    '16 мая 2025г.  08:00:00',
                    '16 мая 2025г.  09:00:00',
                    '16 мая 2025г.  10:00:00',
                    '16 мая 2025г.  11:00:00',
                    '16 мая 2025г.  12:00:00',
                    '16 мая 2025г.  13:00:00',
                    '16 мая 2025г.  14:00:00',
                    '16 мая 2025г.  15:00:00',
                    '16 мая 2025г.  16:00:00',
                    '16 мая 2025г.  17:00:00',
                    '16 мая 2025г.  18:00:00',
                    '16 мая 2025г.  19:00:00',
                    '17 мая 2025г.  08:00:00',
                    '17 мая 2025г.  09:00:00',
                    '17 мая 2025г.  10:00:00',
                    '17 мая 2025г.  11:00:00',
                    '17 мая 2025г.  12:00:00',
                    '17 мая 2025г.  13:00:00',
                    '17 мая 2025г.  14:00:00',
                    '17 мая 2025г.  15:00:00',
                    '17 мая 2025г.  16:00:00',
                    '17 мая 2025г.  17:00:00',
                    '17 мая 2025г.  18:00:00',
                    '17 мая 2025г.  19:00:00',
                    '18 мая 2025г.  08:00:00',
                    '18 мая 2025г.  09:00:00',
                    '18 мая 2025г.  10:00:00',
                    '18 мая 2025г.  11:00:00',
                    '18 мая 2025г.  12:00:00',
                    '18 мая 2025г.  13:00:00',
                    '18 мая 2025г.  14:00:00',
                    '18 мая 2025г.  15:00:00',
                    '18 мая 2025г.  16:00:00',
                    '18 мая 2025г.  17:00:00',
                    '18 мая 2025г.  18:00:00',
                    '18 мая 2025г.  19:00:00',
                    '19 мая 2025г.  08:00:00',
                    '19 мая 2025г.  09:00:00',
                    '19 мая 2025г.  10:00:00',
                    '19 мая 2025г.  11:00:00',
                    '19 мая 2025г.  12:00:00',
                    '19 мая 2025г.  13:00:00',
                    '19 мая 2025г.  14:00:00',
                    '19 мая 2025г.  15:00:00',
                    '19 мая 2025г.  16:00:00',
                    '19 мая 2025г.  17:00:00',
                    '19 мая 2025г.  18:00:00',
                    '19 мая 2025г.  19:00:00',
                    '20 мая 2025г.  08:00:00',
                    '20 мая 2025г.  09:00:00',
                    '20 мая 2025г.  10:00:00',
                    '20 мая 2025г.  11:00:00',
                    '20 мая 2025г.  12:00:00',
                    '20 мая 2025г.  13:00:00',
                    '20 мая 2025г.  14:00:00',
                    '20 мая 2025г.  15:00:00',
                    '20 мая 2025г.  16:00:00',
                    '20 мая 2025г.  17:00:00',
                    '20 мая 2025г.  18:00:00',
                    '20 мая 2025г.  19:00:00',
                    '21 мая 2025г.  08:00:00',
                    '21 мая 2025г.  09:00:00',
                    '21 мая 2025г.  10:00:00',
                    '21 мая 2025г.  11:00:00',
                    '21 мая 2025г.  12:00:00',
                    '21 мая 2025г.  13:00:00',
                    '21 мая 2025г.  14:00:00',
                    '21 мая 2025г.  15:00:00',
                    '21 мая 2025г.  16:00:00',
                    '21 мая 2025г.  17:00:00',
                    '21 мая 2025г.  18:00:00',
                    '21 мая 2025г.  19:00:00',
                    '22 мая 2025г.  08:00:00',
                    '22 мая 2025г.  09:00:00',
                    '22 мая 2025г.  10:00:00',
                    '22 мая 2025г.  11:00:00',
                    '22 мая 2025г.  12:00:00',
                    '22 мая 2025г.  13:00:00',
                    '22 мая 2025г.  14:00:00',
                    '22 мая 2025г.  15:00:00',
                    '22 мая 2025г.  16:00:00',
                    '22 мая 2025г.  17:00:00',
                    '22 мая 2025г.  18:00:00',
                    '22 мая 2025г.  19:00:00',
                    '23 мая 2025г.  08:00:00',
                    '23 мая 2025г.  09:00:00',
                    '23 мая 2025г.  10:00:00',
                    '23 мая 2025г.  11:00:00',
                    '23 мая 2025г.  12:00:00',
                    '23 мая 2025г.  13:00:00',
                    '23 мая 2025г.  14:00:00',
                    '23 мая 2025г.  15:00:00',
                    '23 мая 2025г.  16:00:00',
                    '23 мая 2025г.  17:00:00',
                    '23 мая 2025г.  18:00:00',
                    '23 мая 2025г.  19:00:00',
                    '24 мая 2025г.  08:00:00',
                    '24 мая 2025г.  09:00:00',
                    '24 мая 2025г.  10:00:00',
                    '24 мая 2025г.  11:00:00',
                    '24 мая 2025г.  12:00:00',
                    '24 мая 2025г.  13:00:00',
                    '24 мая 2025г.  14:00:00',
                    '24 мая 2025г.  15:00:00',
                    '24 мая 2025г.  16:00:00',
                    '24 мая 2025г.  17:00:00',
                    '24 мая 2025г.  18:00:00',
                    '24 мая 2025г.  19:00:00',
                    '25 мая 2025г.  08:00:00',
                    '25 мая 2025г.  09:00:00',
                    '25 мая 2025г.  10:00:00',
                    '25 мая 2025г.  11:00:00',
                    '25 мая 2025г.  12:00:00',
                    '25 мая 2025г.  13:00:00',
                    '25 мая 2025г.  14:00:00',
                    '25 мая 2025г.  15:00:00',
                    '25 мая 2025г.  16:00:00',
                    '25 мая 2025г.  17:00:00',
                    '25 мая 2025г.  18:00:00',
                    '25 мая 2025г.  19:00:00',
                    '26 мая 2025г.  08:00:00',
                    '26 мая 2025г.  09:00:00',
                    '26 мая 2025г.  10:00:00',
                    '26 мая 2025г.  11:00:00',
                    '26 мая 2025г.  12:00:00',
                    '26 мая 2025г.  13:00:00',
                    '26 мая 2025г.  14:00:00',
                    '26 мая 2025г.  15:00:00',
                    '26 мая 2025г.  16:00:00',
                    '26 мая 2025г.  17:00:00',
                    '26 мая 2025г.  18:00:00',
                    '26 мая 2025г.  19:00:00',
                    '27 мая 2025г.  07:00:00',
                    '27 мая 2025г.  08:00:00',
                    '27 мая 2025г.  09:00:00',
                    '27 мая 2025г.  10:00:00',
                    '27 мая 2025г.  11:00:00',
                    '27 мая 2025г.  12:00:00',
                    '27 мая 2025г.  13:00:00',
                    '27 мая 2025г.  14:00:00',
                    '27 мая 2025г.  15:00:00',
                    '27 мая 2025г.  16:00:00',
                    '27 мая 2025г.  17:00:00',
                    '27 мая 2025г.  18:00:00',
                    '27 мая 2025г.  19:00:00',
                    '27 мая 2025г.  20:00:00',
                    '28 мая 2025г.  08:00:00',
                    '28 мая 2025г.  09:00:00',
                    '28 мая 2025г.  10:00:00',
                    '28 мая 2025г.  11:00:00',
                    '28 мая 2025г.  12:00:00',
                    '28 мая 2025г.  13:00:00',
                    '28 мая 2025г.  14:00:00',
                    '28 мая 2025г.  15:00:00',
                    '28 мая 2025г.  16:00:00',
                    '28 мая 2025г.  17:00:00',
                    '28 мая 2025г.  18:00:00',
                    '28 мая 2025г.  19:00:00',
                    '29 мая 2025г.  08:00:00',
                    '29 мая 2025г.  09:00:00',
                    '29 мая 2025г.  10:00:00',
                    '29 мая 2025г.  11:00:00',
                    '29 мая 2025г.  12:00:00',
                    '29 мая 2025г.  13:00:00',
                    '29 мая 2025г.  14:00:00',
                    '29 мая 2025г.  15:00:00',
                    '29 мая 2025г.  16:00:00',
                    '29 мая 2025г.  17:00:00',
                    '29 мая 2025г.  18:00:00',
                    '29 мая 2025г.  19:00:00',
                    '30 мая 2025г.  08:00:00',
                    '30 мая 2025г.  09:00:00',
                    '30 мая 2025г.  10:00:00',
                    '30 мая 2025г.  11:00:00',
                    '30 мая 2025г.  12:00:00',
                    '30 мая 2025г.  13:00:00',
                    '30 мая 2025г.  14:00:00',
                    '30 мая 2025г.  15:00:00',
                    '30 мая 2025г.  16:00:00',
                    '30 мая 2025г.  17:00:00',
                    '30 мая 2025г.  18:00:00',
                    '30 мая 2025г.  19:00:00',
                    '31 мая 2025г.  08:00:00',
                    '31 мая 2025г.  09:00:00',
                    '31 мая 2025г.  10:00:00',
                    '31 мая 2025г.  11:00:00',
                    '31 мая 2025г.  12:00:00',
                    '31 мая 2025г.  13:00:00',
                    '31 мая 2025г.  14:00:00',
                    '31 мая 2025г.  15:00:00',
                    '31 мая 2025г.  16:00:00',
                    '31 мая 2025г.  17:00:00',
                    '31 мая 2025г.  18:00:00',
                    '31 мая 2025г.  19:00:00',
                ]
            },
            yAxis: {
                type: 'value',
                boundaryGap: [0, '100%']
            },
            dataZoom: [
                {
                    type: 'inside',
                    start: 0,
                    end: 20
                },
                {
                    start: 0,
                    end: 20
                }
            ],
            series: [
                {
                    name: 'Количество обращений',
                    type: 'line',
                    smooth: true,
                    symbol: 'emptyCircle',
                    data: [
                        8,
                        18,
                        19,
                        35,
                        39,
                        32,
                        27,
                        20,
                        16,
                        20,
                        11,
                        8,
                        6,
                        20,
                        24,
                        27,
                        33,
                        22,
                        17,
                        22,
                        19,
                        17,
                        7,
                        4,
                        3,
                        10,
                        8,
                        13,
                        11,
                        8,
                        3,
                        6,
                        6,
                        6,
                        5,
                        5,
                        79,
                        166,
                        154,
                        163,
                        146,
                        170,
                        145,
                        187,
                        159,
                        49,
                        37,
                        44,
                        1,
                        91,
                        146,
                        164,
                        180,
                        180,
                        177,
                        138,
                        214,
                        176,
                        77,
                        55,
                        21,
                        72,
                        152,
                        179,
                        204,
                        134,
                        152,
                        86,
                        149,
                        144,
                        60,
                        42,
                        37,
                        17,
                        23,
                        37,
                        31,
                        41,
                        30,
                        16,
                        17,
                        44,
                        11,
                        2,
                        3,
                        7,
                        22,
                        21,
                        28,
                        15,
                        29,
                        15,
                        24,
                        19,
                        13,
                        8,
                        13,
                        18,
                        11,
                        11,
                        17,
                        23,
                        21,
                        18,
                        5,
                        11,
                        8,
                        5,
                        3,
                        89,
                        166,
                        152,
                        168,
                        135,
                        155,
                        200,
                        176,
                        184,
                        57,
                        40,
                        47,
                        94,
                        168,
                        144,
                        158,
                        130,
                        130,
                        145,
                        177,
                        214,
                        46,
                        38,
                        46,
                        99,
                        170,
                        191,
                        217,
                        172,
                        154,
                        148,
                        179,
                        169,
                        79,
                        37,
                        43,
                        1,
                        79,
                        176,
                        217,
                        238,
                        219,
                        184,
                        181,
                        186,
                        179,
                        97,
                        47,
                        45,
                        80,
                        196,
                        167,
                        191,
                        172,
                        133,
                        154,
                        178,
                        134,
                        60,
                        40,
                        31,
                        24,
                        32,
                        39,
                        39,
                        44,
                        23,
                        31,
                        37,
                        27,
                        13,
                        4,
                        7,
                        5,
                        7,
                        10,
                        14,
                        8,
                        8,
                        12,
                        8,
                        6,
                        6,
                        5,
                        7,
                        66,
                        176,
                        211,
                        218,
                        176,
                        154,
                        175,
                        218,
                        190,
                        95,
                        35,
                        33,
                        79,
                        189,
                        179,
                        237,
                        172,
                        148,
                        150,
                        214,
                        205,
                        74,
                        45,
                        41,
                        84,
                        178,
                        197,
                        224,
                        189,
                        166,
                        154,
                        176,
                        163,
                        42,
                        30,
                        32,
                        83,
                        132,
                        181,
                        239,
                        183,
                        172,
                        172,
                        213,
                        169,
                        62,
                        56,
                        49,
                        73,
                        167,
                        182,
                        249,
                        202,
                        171,
                        210,
                        197,
                        154,
                        72,
                        42,
                        23,
                        12,
                        26,
                        29,
                        40,
                        49,
                        28,
                        41,
                        35,
                        33,
                        19,
                        13,
                        7,
                        2,
                        6,
                        12,
                        15,
                        15,
                        13,
                        12,
                        15,
                        1,
                        3,
                        8,
                        10,
                        64,
                        142,
                        145,
                        142,
                        128,
                        104,
                        158,
                        132,
                        149,
                        54,
                        27,
                        38,
                        1,
                        62,
                        163,
                        163,
                        172,
                        160,
                        138,
                        173,
                        158,
                        179,
                        66,
                        36,
                        23,
                        1,
                        72,
                        116,
                        141,
                        171,
                        137,
                        119,
                        138,
                        152,
                        151,
                        71,
                        29,
                        41,
                        44,
                        154,
                        147,
                        180,
                        142,
                        118,
                        130,
                        157,
                        128,
                        59,
                        27,
                        23,
                        47,
                        168,
                        160,
                        174,
                        119,
                        133,
                        163,
                        157,
                        111,
                        35,
                        38,
                        20,
                        8,
                        18,
                        51,
                        45,
                        40,
                        29,
                        33,
                        31,
                        22,
                        16,
                        11,
                        10,
                    ]
                }
            ]
        };

        dynChart.setOption(optionDyn);
        window.addEventListener('resize', dynChart.resize);
    </script>


</div>
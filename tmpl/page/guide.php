<?
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/header.php';
?>

<body>
    <div class="wrap">
        <div class="content">
            <?
            include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/left_menu.php';
            ?>
            <div class="main_data">
                <div class="nav">
                    <div class="nav_01">



                        <div class="title">Справочники</div>
                        <form>
                            <div class="nav_02">
                                <div class="widget_01">

                                    <div class="nav_select">

                                        <select name="guide" data-label="">
                                            <option value="Тип операции">Тип операции</option>
                                            <option value="Вид оплаты">Вид оплаты</option>
                                            <option value="ЦФО">ЦФО</option>
                                            <option value="Подразделение">Подразделение</option>
                                            <option value="Статья">Статья</option>
                                            <option value="ОбоКонтрагентрот">Контрагент</option>

                                        </select>
                                    </div>

                                </div>

                            </div>
                        </form>
                        <div class="button icon" title="Сбросить все фильтры">
                            <span class="material-icons">autorenew</span></div>
                        <div class="button icon text" onclick="pop_up_guide_create(); return false" title="Новый справочник">
                            <span class="material-icons">control_point</span>Создать
                        </div>
                        <div class="button icon text disabled" onclick="pop_up_notify(); return false" title="Копия документа">
                            <span class="material-icons">control_point_duplicate</span>Дублировать
                        </div>
                        <div class="button icon text disabled" onclick="pop_up_notify(); return false" title="Удалить выделенные">
                            <span class="material-icons">delete_forever</span>Удалить
                        </div>


                        <div class="button icon right" title="Выйти">
                            <span class="material-icons">logout</span>
                        </div>
                    </div>

                </div>
                <div class="scroll_wrap">
                    <table class="table_data">
                        <thead>
                            <tr class="fixed_thead">
                                <th>
                                    <div class="custom_checkbox">
                                        <label class="container" title="Выделить все">
                                            <input type="checkbox" id="check_all"><span class="checkmark"></span>
                                        </label>
                                    </div>
                                </th>
                                <th class="sort">
                                    <div class="head_sort_filter">
                                        <div class="button icon text" title="Сортировать">Статус<span class="material-icons">
                                                north
                                            </span></div>
                                        <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                    </div>
                                    <!--<div class="data_filter_select">
                                        <form>
                                            <select multiple data-label="">
                                                <option value="Проведено">Активный</option>
                                                <option value="Не проведено">Заблокирован</option>

                                            </select>
                                        </form>
                                    </div>-->
                                </th>
                                <th class="sort">
                                    <div class="head_sort_filter">
                                        <div class="button icon text" title="Сортировать">Наименование<span class="material-icons">
                                                north
                                            </span></div>
                                        <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                    </div>
                                </th>
                                <th>
                                    <div class="head_sort_filter">Примечания

                                    </div>
                                </th>
                            </tr>
                        </thead>


                        <tbody>
                            <!-- row -->
                            <tr>
                                <td>
                                    <div class="custom_checkbox">
                                        <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                                    </div>
                                </td>
                                <td class="status"><span class="material-icons">task_alt</span></td>
                                <td>Тип операции</td>
                                <td>По клику переходим в справочник</td>
                            </tr>
                            <!-- row -->
                            <tr>
                                <td>
                                    <div class="custom_checkbox">
                                        <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                                    </div>
                                </td>
                                <td class="status"><span class="material-icons">task_alt</span></td>
                                <td>Вид оплаты</td>
                                <td>Способ перечисления денежных средств</td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="js/call_popups.js"></script>
    <script src="js/chart_01.js"></script>

    <!-- Создание новой операции -->
    <div id="pop_up_guide_create" class="wrap_pop_up">
        <div class="pop_up drag">
            <div class="title handle">
                <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
                <div class="name">Создать справочник</div>
                <div class="button icon close" onclick="pop_up_guide_create_close(); return false"><span class="material-icons">close</span></div>
            </div>
            <div class="pop_up_body">
                <form>
                    <div class="group">

                        <div class="item w_100">
                            <div class="el_data">
                                <label>Наименование</label>
                                <input required class="el_input" type="text">
                            </div>
                        </div>

                        <div class="item w_50">
                            <select required data-label="Родительский справочник">
                                <option value="Аналитика">Аналитика</option>

                            </select>

                        </div>



                        <div class="item w_100">
                            <div class="el_data">
                                <label>Примечания</label>
                                <textarea class="el_textarea">

                                </textarea>
                            </div>
                        </div>
                    </div>
                    <div class="confirm">

                        <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>


                    </div>
                </form>
            </div>

        </div>
    </div>
    <!-- Конец "универсальный wrapper" для Pop-Up-ов -->




    <!-- Конец "универсальный wrapper" для Pop-Up-ов -->
    <!-- notify  + error -->
    <div id="pop_up_notify" class="wrap_pop_up">
        <div class="pop_up drag notify">
            <div class="title handle">
                <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
                <div class="name">Уведомление</div>
                <div class="button icon close" onclick="pop_up_notify_close(); return false"><span class="material-icons">close</span></div>
            </div>
            <div class="pop_up_body">
                <form>
                    <div class="group">
                        <div class="item w_100">Клонировать 15 записей?</div>
                    </div>
                    <div class="confirm">

                        <button class="button icon text" type="submit"><span class="material-icons">done</span>Да</button>
                        <!--<button class="button icon text"><span class="material-icons">save</span>Сохранить</button>-->
                        <button class="button icon text" type="reset"><span class="material-icons">block</span>Отмена</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
    <!-- Конец "универсальный wrapper" для Pop-Up-ов -->
    <!-- ------------------------------ ПОПАПЫ СПРАВОЧНИКОВ ----------------------------------------- -->
    <!-- Справочник типов операций -->
    <div id="pop_up_dir_operation" class="wrap_pop_up">
        <div class="pop_up drag">
            <div class="title handle">
                <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
                <div class="name">Добавить значение: <span>"Тип операции"</span></div>
                <div class="button icon close" onclick="pop_up_dir_operation_close(); return false"><span class="material-icons">close</span></div>
            </div>
            <div class="pop_up_body">
                <form>
                    <div class="group">
                        <div class="item w_100">
                            <div class="el_data">
                                <label>Наименование</label>
                                <input class="el_input" type="text">
                            </div>
                        </div>
                    </div>
                    <div class="confirm">

                        <button class="button icon text" type="submit"><span class="material-icons">done</span>Сохранить</button>


                    </div>
                </form>
            </div>

        </div>
    </div>
    <!-- Конец "универсальный wrapper" для Pop-Up-ов -->

<?
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/footer.php';
?>
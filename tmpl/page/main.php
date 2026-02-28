<?
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/header.php';
?>
<body>
<div class="wrap">
    <div class="content">
        <?
        if($_SESSION['user_roles'] != 4) {
            include_once $_SERVER['DOCUMENT_ROOT'] . '/tmpl/page/blocks/left_menu.php';
        }
        ?>
        <div class="main_data">
            <div class="nav">
                <div class="nav_01">
                    <div class="title">Данные</div>
                    <form>
                        <div class="nav_02">
                            <div class="widget_01">
                                <div class="value rub no_wrap">48 758 123,78</div>
                                <div class="nav_select">
                                    <select data-label="">
                                        <option value="Прибыль">Прибыль</option>
                                        <option value="Оборот">Оборот</option>
                                        <option value="Поступления">Поступления</option>
                                        <option value="Остаток на начало">Остаток на начало</option>
                                        <option value="Остаток на конец">Остаток на конец</option>
                                        <option value="Расход">Расход</option>
                                    </select>
                                </div>
                            </div>
                            <div class="group">
                                <div class="date_range">
                                    <div class="el_data" title="Устанивть дату или период">
                                        <input class="el_input" type="date">
                                    </div>
                                </div>
                                <!-- <div class="button icon" title="Фильтровать" onclick="pop_up_data_filters(); return false"><span class="material-icons">filter_alt</span></div>-->
                                <div class="button icon" title="Сбросить все фильтры"><span class="material-icons">autorenew</span></div>
                                <div class="button icon" title="Выделить строки цветом" id="colored"><span class="material-icons">invert_colors</span></div>
                                <!-- <div class="search">
                                <div class="el_data add_button">
                                    <input class="el_input" type="text">
                                </div>
                                <div class="button icon"><span class="material-icons">search</span></div>
                            </div> -->
                            </div>
                        </div>
                    </form>
                    <div class="button icon text" onclick="pop_up_data_create(); return false" title="Новый документ">
                        <span class="material-icons">control_point</span>Создать
                    </div>
                    <div class="button icon text" onclick="pop_up_notify(); return false" title="Провести отмеченные">
                        <span class="material-icons">task_alt</span>Провести
                    </div>
                    <div class="button icon text" onclick="pop_up_notify(); return false" title="Копия документа">
                        <span class="material-icons">control_point_duplicate</span>Дублировать
                    </div>
                    <div class="button icon text" onclick="pop_up_notify(); return false" title="Удалить выделенные">
                        <span class="material-icons">delete_forever</span>Удалить
                    </div>
                    <div class="button icon text" onclick="pop_up_data_custom(); return false" title="Настройка отображения">
                        <span class="material-icons">tune</span>Настройки
                    </div>


                    <div class="button icon right" title="Выйти" id="logout">
                        <span class="material-icons">logout</span>
                    </div>
                </div>
                <!-- <form>
                    <div class="nav_02">
                        <div class="widget_01">
                            <div class="value rub no_wrap">48 758 123,78</div>
                            <div class="nav_select">
                                <select data-label="">
                                    <option value="Прибыль">Прибыль</option>
                                    <option value="Оборот">Оборот</option>
                                    <option value="Поступления">Поступления</option>
                                    <option value="Остаток на начало">Остаток на начало</option>
                                    <option value="Остаток на конец">Остаток на конец</option>
                                    <option value="Расход">Расход</option>
                                </select>
                            </div>
                        </div>
                        <div class="group">
                            <div class="date_range">
                                <div class="el_data">
                                    <input class="el_input" type="date">
                                </div>
                            </div>
                            <div class="button icon" title="Фильтровать" onclick="pop_up_data_filters(); return false"><span class="material-icons">filter_alt</span></div>
                            <div class="button icon" title="Сбросить фильтры"><span class="material-icons">autorenew</span></div>
                            <div class="button icon" title="Выделить цветом" id="colored"><span class="material-icons">invert_colors</span></div>
                            <div class="search">
                                <div class="el_data add_button">
                                    <input class="el_input" type="text">
                                </div>
                                <div class="button icon"><span class="material-icons">search</span></div>
                            </div>
                        </div>
                    </div>
                </form> -->
            </div>
            <div class="scroll_wrap">
                <table class="table_data" id="tbl_oper">
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
                            <div class="data_filter_select">
                                <form>
                                    <select multiple data-label="">
                                        <option value="Проведено">Проведено</option>
                                        <option value="Не проведено">Не проведено</option>
                                        <option value="Удалить">Удалить</option>
                                    </select>
                                </form>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">Дата<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">Тип<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">Вид<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">ЦФО<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text active" title="Сортировать">Подразделение<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>

                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">Статья<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">Сумма<span class="material-icons">
                                                north
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">Контрагент<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon active" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                            <div class="data_filter_select">
                                <form>
                                    <div class="el_data">
                                        <input class="el_input" type="text">
                                        <div class="el_select_list bottom" style="top: 1.4375rem; bottom: auto; display: block;">
                                            <div class="el_option selected" data-value="Тренажерный зал">Тренажерный зал</div>
                                            <div class="el_option" data-value="Бассейн">Бассейн</div>
                                            <div class="el_option" data-value="Групповые программы">Групповые программы</div>
                                            <div class="el_option" data-value="Студия единоборств">Студия единоборств</div>
                                            <div class="el_option" data-value="Детский клуб">Детский клуб</div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </th>
                        <th>
                            <div class="head_sort_filter">Примечания
                                <div class="button icon" title="Сортировать"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                    </tr>
                    </thead>


                    <tbody>
                    <!-- row -->
                    <tr class="money_in">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">28 сен 2020</td>
                        <td><span class="material-icons">arrow_back</span></td>
                        <td>Альфа-банк</td>
                        <td>Отдел сервис - консультирования</td>
                        <td>Групповые программы</td>
                        <td>Приобретение спецодежды, спецоснастки, форменной одежды</td>
                        <td class="right no_wrap"><span>28 375,50</span></td>
                        <td>Константинопольский Константин Константинович</td>
                        <td>За проведение фуршета 31.12.2020</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_transfer">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">15 мая 2019</td>
                        <td><span class="material-icons">swap_horiz</span></td>
                        <td>Пластик</td>
                        <td>Технический отдел</td>
                        <td>Студия единоборств</td>
                        <td>Обслуживание тренажеров и спорт инвентаря</td>
                        <td class="right no_wrap"><span>1 250,00</span></td>
                        <td>Муратов Евгений Влаадимирович</td>
                        <td>доплата</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_out">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">28 окт 2020</td>
                        <td><span class="material-icons">arrow_forward</span></td>
                        <td>Депозит</td>
                        <td>Отдел рекламы и маркетинга</td>
                        <td>Бассейн</td>
                        <td>Пополнение депозита ЧК</td>
                        <td class="right no_wrap"><span>1 300,00</span></td>
                        <td>Муратов Евгений Влаадимирович</td>
                        <td>10</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_in">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">28 сен 2020</td>
                        <td><span class="material-icons">arrow_back</span></td>
                        <td>Альфа-банк</td>
                        <td>Отдел сервис - консультирования</td>
                        <td>Групповые программы</td>
                        <td>Приобретение спецодежды, спецоснастки, форменной одежды</td>
                        <td class="right no_wrap"><span>28 375,50</span></td>
                        <td>Константинопольский Константин Константинович</td>
                        <td>За проведение фуршета 31.12.2020</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_in">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">28 сен 2020</td>
                        <td><span class="material-icons">arrow_back</span></td>
                        <td>Альфа-банк</td>
                        <td>Отдел сервис - консультирования</td>
                        <td>Групповые программы</td>
                        <td>Приобретение спецодежды, спецоснастки, форменной одежды</td>
                        <td class="right no_wrap"><span>28 375,50</span></td>
                        <td>Константинопольский Константин Константинович</td>
                        <td>За проведение фуршета 31.12.2020</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_transfer">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">15 мая 2019</td>
                        <td><span class="material-icons">swap_horiz</span></td>
                        <td>Пластик</td>
                        <td>Технический отдел</td>
                        <td>Студия единоборств</td>
                        <td>Обслуживание тренажеров и спорт инвентаря</td>
                        <td class="right no_wrap"><span>1 250,00</span></td>
                        <td>Муратов Евгений Влаадимирович</td>
                        <td>доплата</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_out">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">28 окт 2020</td>
                        <td><span class="material-icons">arrow_forward</span></td>
                        <td>Депозит</td>
                        <td>Отдел рекламы и маркетинга</td>
                        <td>Бассейн</td>
                        <td>Пополнение депозита ЧК</td>
                        <td class="right no_wrap"><span>1 300,00</span></td>
                        <td>Муратов Евгений Влаадимирович</td>
                        <td>10</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_in temp">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status for_del"><span class="material-icons">remove_circle_outline</span></td>
                        <td class="right no_wrap">28 сен 2020</td>
                        <td><span class="material-icons">arrow_back</span></td>
                        <td>Альфа-банк</td>
                        <td>Отдел сервис - консультирования</td>
                        <td>Групповые программы</td>
                        <td>Приобретение спецодежды, спецоснастки, форменной одежды</td>
                        <td class="right no_wrap"><span>28 375,50</span></td>
                        <td>Константинопольский Константин Константинович</td>
                        <td>За проведение фуршета 31.12.2020</td>
                    </tr>
                    <tr class="money_out temp">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">radio_button_unchecked</span></td>
                        <td class="right no_wrap">28 окт 2020</td>
                        <td><span class="material-icons">arrow_forward</span></td>
                        <td>Депозит</td>
                        <td>Отдел рекламы и маркетинга</td>
                        <td>Бассейн</td>
                        <td>Пополнение депозита ЧК</td>
                        <td class="right no_wrap"><span>1 300,00</span></td>
                        <td>Муратов Евгений Влаадимирович</td>
                        <td>10</td>
                    </tr>
                    <!-- row -->
                    <tr class="money_in">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td class="right no_wrap">28 сен 2020</td>
                        <td><span class="material-icons">arrow_back</span></td>
                        <td>Альфа-банк</td>
                        <td>Отдел сервис - консультирования</td>
                        <td>Групповые программы</td>
                        <td>Приобретение спецодежды, спецоснастки, форменной одежды</td>
                        <td class="right no_wrap"><span>28 375,50</span></td>
                        <td>Константинопольский Константин Константинович</td>
                        <td>За проведение фуршета 31.12.2020</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>



<!-- Создание новой операции -->
<div id="pop_up_data_create" class="wrap_pop_up">
    <div class="pop_up drag">
        <div class="title handle">
            <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
            <div class="name">Создать операцию</div>
            <div class="button icon close" onclick="pop_up_data_create_close(); return false"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form>
                <div class="group">
                    <!-- <div class="item w_50">
                        <div class="el_data">
                            <label>Номер документа</label>
                            <input readonly class="el_input" value="1254354">
                        </div>
                    </div> -->
                    <div class="item w_50">
                        <div class="el_data w_50">
                            <label>Дата</label>
                            <input class="el_input" type="date" value="2021-05-01">

                        </div>

                    </div>
                    <div class="item w_50">
                        <select required data-label="Тип операции">
                            <option data-image="images/arrow_back.svg" value="Приход">Приход</option>
                            <option data-image="images/arrow_forward.svg" value="Расход">Расход</option>
                            <option data-image="images/swap.svg" value="Перевод">Перемещение</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_operation(); return false"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">

                        <select contenteditable="true" required data-label="Вид оплаты">
                            <option value="Наличная (К1)">Наличная (К1)</option>
                            <option value="Наличная (К2)">Наличная (К2)</option>
                            <option value="Пластиковая карта">Пластиковая карта</option>
                            <option value="Интернет-эквайринг">Интернет-эквайринг</option>
                            <option value="Мандарин">Мандарин</option>
                            <option value="Совкомбанк">Совкомбанк</option>
                            <option value="Банк Открытие">Банк Открытие</option>
                            <option value="Депозит">Депозит</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_payment(); return false"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="ЦФО">
                            <option value="Отдел продаж">Отдел продаж</option>
                            <option value="Фитнес департамент">Фитнес департамент</option>
                            <option value="Рецепция">Рецепция</option>
                            <option value="Ресторан">Ресторан</option>
                            <option value="Салон красоты">Салон красоты</option>
                            <option value="Административный отдел">Административный отдел</option>
                            <option value="Отдел сервис - консультирования">Отдел сервис - консультирования</option>
                            <option value="Отдел рекламы и маркетинга">Отдел рекламы и маркетинга</option>
                            <option value="IT отдел">IT отдел</option>
                            <option value="Технический отдел">Технический отдел</option>
                            <option value="Строительный отдел">Строительный отдел</option>
                            <option value="Управляющая компания">Управляющая компания</option>
                            <option value="Учредители">Учредители</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_cfo(); return false"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="Подразделение">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_department(); return false"><span class="material-icons">folder</span></div>
                    </div>

                    <div class="item w_50">
                        <select required data-label="Статья бюджета">
                            <option value="Круглосуточные клубные карты">Круглосуточные клубные карты</option>
                            <option value="Депозитные карты">Депозитные карты</option>
                            <option value="Дневные клубные карты">Дневные клубные карты</option>
                            <option value="Детские клубные карты">Детские клубные карты</option>
                            <option value="Депозит входящий в карту">Депозит входящий в карту</option>
                            <option value="Пополнение депозита ЧК">Пополнение депозита ЧК</option>
                            <option value="Обнуление депозита">Обнуление депозита</option>
                            <option value="Гостевые визиты">Гостевые визиты</option>
                            <option value="Анализ состава тела">Анализ состава тела</option>
                            <option value="Продление блока тренировок">Продление блока тренировок</option>
                            <option value="Персональные тренировки">Персональные тренировки</option>
                            <option value="Сплит тренировки">8Сплит тренировки88</option>
                            <option value="Минигруппы">Минигруппы</option>
                            <option value="Секции">Секции</option>
                            <option value="Игровая комната">Игровая комната</option>
                            <option value="Фитнес няня">Фитнес няня</option>
                            <option value="Витрина Ресепшен">Витрина Ресепшен</option>
                            <option value="Аренда шкафчиков">Аренда шкафчиков</option>
                            <option value="Продажа напитков">Продажа напитков</option>
                            <option value="Продажа блюд">Продажа блюд</option>
                            <option value="Продажа товаров">Продажа товаров</option>
                            <option value="Продажа Спорт Пита">Продажа Спорт Пита</option>
                            <option value="Массаж">Массаж</option>
                            <option value="Ногтевой сервис">Ногтевой сервис</option>
                            <option value="Стилист">Стилист</option>
                            <option value="Косметолог">Косметолог</option>
                            <option value="Пармейстер">Пармейстер</option>
                            <option value="Товары SPA">Товары SPA</option>
                            <option value="Солярий">Солярий</option>
                            <option value="Субаренда/Аренда площадей клуба">Субаренда/Аренда площадей клуба</option>
                            <option value="Прочее">Прочее</option>
                            <option value="Возврат покупателям">Возврат покупателям</option>
                            <option value="Возврат в связи с переоформлением">Возврат в связи с переоформлением</option>
                            <option value="Арендные выплаты (постоянная часть)">Арендные выплаты (постоянная часть)</option>

                        </select>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_budget(); return false"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item  w_50 ">
                        <div class="el_data">
                            <label>Контрагент</label>
                            <input required class="el_input" value="">
                            <div class="el_select_list bottom" style="top: 1.4375rem; bottom: auto; display: block;">
                                <div class="el_option selected" data-value="Тренажерный зал">Тренажерный зал</div>
                                <div class="el_option" data-value="Бассейн">Бассейн</div>
                                <div class="el_option" data-value="Групповые программы">Групповые программы</div>
                                <div class="el_option" data-value="Студия единоборств">Студия единоборств</div>
                                <div class="el_option" data-value="Детский клуб">Детский клуб</div>
                            </div>
                        </div>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_counteragent(); return false"><span class="material-icons">folder</span></div>
                    </div>


                    <div class="item w_50">
                        <div class="el_data">
                            <label>Сумма</label>
                            <input required class="el_input" type="number" pattern="\d+(\.\d{2})?" step="0.01" value="">
                        </div>
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
                    <button class="button icon text"><span class="material-icons">task_alt</span>Провести</button>
                    <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
                    <!-- <div class="button icon text disabled"><span class="material-icons">control_point_duplicate</span>Дублировать</div> -->

                </div>
            </form>
        </div>

    </div>
</div>
<!-- Конец "универсальный wrapper" для Pop-Up-ов -->
<!-- Просмотр/редактирование сохранённой, но не проведённой операции -->
<div id="pop_up_data_edit" class="wrap_pop_up">
    <div class="pop_up drag">
        <div class="title handle">

            <div class="name">Редактирование операции</div>
            <div class="button icon close" onclick="pop_up_data_edit_close(); return false"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form>
                <div class="group">



                    <div class="record_number w_100"><span>ПР-0002</span></div>




                    <div class="item w_50">
                        <div class="el_data w_50">
                            <label>Дата</label>
                            <input class="el_input" type="date" value="2021-05-01">
                        </div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="Тип операции">
                            <option data-image="images/arrow_back.svg" value="Приход">Приход</option>
                            <option data-image="images/arrow_forward.svg" value="Расход">Расход</option>
                            <option data-image="images/swap.svg" value="Перевод">Перемещение</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">

                        <select contenteditable="true" required data-label="Вид оплаты">
                            <option value="Наличная (К1)">Наличная (К1)</option>
                            <option value="Наличная (К2)">Наличная (К2)</option>
                            <option value="Пластиковая карта">Пластиковая карта</option>
                            <option value="Интернет-эквайринг">Интернет-эквайринг</option>
                            <option value="Мандарин">Мандарин</option>
                            <option value="Совкомбанк">Совкомбанк</option>
                            <option value="Банк Открытие">Банк Открытие</option>
                            <option value="Депозит">Депозит</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="ЦФО">
                            <option value="Отдел продаж">Отдел продаж</option>
                            <option value="Фитнес департамент">Фитнес департамент</option>
                            <option value="Рецепция">Рецепция</option>
                            <option value="Ресторан">Ресторан</option>
                            <option value="Салон красоты">Салон красоты</option>
                            <option value="Административный отдел">Административный отдел</option>
                            <option value="Отдел сервис - консультирования">Отдел сервис - консультирования</option>
                            <option value="Отдел рекламы и маркетинга">Отдел рекламы и маркетинга</option>
                            <option value="IT отдел">IT отдел</option>
                            <option value="Технический отдел">Технический отдел</option>
                            <option value="Строительный отдел">Строительный отдел</option>
                            <option value="Управляющая компания">Управляющая компания</option>
                            <option value="Учредители">Учредители</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="Подразделение">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника"><span class="material-icons">folder</span></div>
                    </div>

                    <div class="item w_50">
                        <select required data-label="Статья бюджета">
                            <option value="Круглосуточные клубные карты">Круглосуточные клубные карты</option>
                            <option value="Депозитные карты">Депозитные карты</option>
                            <option value="Дневные клубные карты">Дневные клубные карты</option>
                            <option value="Детские клубные карты">Детские клубные карты</option>
                            <option value="Депозит входящий в карту">Депозит входящий в карту</option>
                            <option value="Пополнение депозита ЧК">Пополнение депозита ЧК</option>
                            <option value="Обнуление депозита">Обнуление депозита</option>
                            <option value="Гостевые визиты">Гостевые визиты</option>
                            <option value="Анализ состава тела">Анализ состава тела</option>
                            <option value="Продление блока тренировок">Продление блока тренировок</option>
                            <option value="Персональные тренировки">Персональные тренировки</option>
                            <option value="Сплит тренировки">8Сплит тренировки88</option>
                            <option value="Минигруппы">Минигруппы</option>
                            <option value="Секции">Секции</option>
                            <option value="Игровая комната">Игровая комната</option>
                            <option value="Фитнес няня">Фитнес няня</option>
                            <option value="Витрина Ресепшен">Витрина Ресепшен</option>
                            <option value="Аренда шкафчиков">Аренда шкафчиков</option>
                            <option value="Продажа напитков">Продажа напитков</option>
                            <option value="Продажа блюд">Продажа блюд</option>
                            <option value="Продажа товаров">Продажа товаров</option>
                            <option value="Продажа Спорт Пита">Продажа Спорт Пита</option>
                            <option value="Массаж">Массаж</option>
                            <option value="Ногтевой сервис">Ногтевой сервис</option>
                            <option value="Стилист">Стилист</option>
                            <option value="Косметолог">Косметолог</option>
                            <option value="Пармейстер">Пармейстер</option>
                            <option value="Товары SPA">Товары SPA</option>
                            <option value="Солярий">Солярий</option>
                            <option value="Субаренда/Аренда площадей клуба">Субаренда/Аренда площадей клуба</option>
                            <option value="Прочее">Прочее</option>
                            <option value="Возврат покупателям">Возврат покупателям</option>
                            <option value="Возврат в связи с переоформлением">Возврат в связи с переоформлением</option>
                            <option value="Арендные выплаты (постоянная часть)">Арендные выплаты (постоянная часть)</option>

                        </select>
                        <div class="button icon" title="Добавить элемент справочника"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item  w_50 ">
                        <div class="el_data">
                            <label>Контрагент</label>
                            <input required class="el_input" value="">
                            <!-- <div class="el_select_list bottom" style="top: 1.4375rem; bottom: auto; display: block;">
                                <div class="el_option selected" data-value="Тренажерный зал">Тренажерный зал</div>
                                <div class="el_option" data-value="Бассейн">Бассейн</div>
                                <div class="el_option" data-value="Групповые программы">Групповые программы</div>
                                <div class="el_option" data-value="Студия единоборств">Студия единоборств</div>
                                <div class="el_option" data-value="Детский клуб">Детский клуб</div>
                            </div> -->
                        </div>
                        <div class="button icon" title="Добавить элемент справочника"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <div class="el_data">
                            <label>Сумма</label>
                            <input required class="el_input" type="number" pattern="\d+(\.\d{2})?" step="0.01" value="">
                        </div>
                    </div>
                    <div class="item w_100">
                        <div class="el_data">
                            <label>Примечания</label>
                            <textarea class="el_textarea"></textarea>
                        </div>
                    </div>
                </div>
                <div class="confirm">
                    <div class="autor">
                        <div class="date_create"><span>Редактирование:</span>01.05.2021</div>
                        <div class="user"><span>Пользователь:</span><a href="#">Помидоркин С.П.</a></div>
                    </div>
                    <button class="button icon text"><span class="material-icons">task_alt</span>Провести</button>
                    <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
                    <button class="button icon text"><span class="material-icons">delete_forever</span>Удалить</button>
                </div>
            </form>
        </div>

    </div>
</div>
<!-- Конец "универсальный wrapper" для Pop-Up-ов -->
<!-- Просмотр проведённой операции -->
<div id="pop_up_data_view" class="wrap_pop_up">
    <div class="pop_up drag">
        <div class="title handle">
            <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
            <div class="name">Операция проведена</div>
            <div class="button icon close" onclick="pop_up_data_view_close(); return false"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form>
                <div class="group">



                    <div class="record_number w_100"><span>ПР-0002</span></div>




                    <div class="item w_50">
                        <div class="el_data w_50">
                            <label>Дата</label>
                            <input disabled class="el_input" type="date" value="2021-05-01">
                        </div>
                    </div>
                    <div class="item w_50">
                        <select disabled data-label="Тип операции">
                            <option data-image="images/arrow_back.svg" value="Приход">Приход</option>
                            <option selected data-image="images/arrow_forward.svg" value="Расход">Расход</option>
                            <option data-image="images/swap.svg" value="Перевод">Перемещение</option>
                        </select>

                    </div>
                    <div class="item w_50">

                        <select disabled data-label="Вид оплаты">
                            <option value="Наличная (К1)">Наличная (К1)</option>
                            <option value="Наличная (К2)">Наличная (К2)</option>
                            <option value="Пластиковая карта">Пластиковая карта</option>
                            <option value="Интернет-эквайринг">Интернет-эквайринг</option>
                            <option selected value="Мандарин">Мандарин</option>
                            <option value="Совкомбанк">Совкомбанк</option>
                            <option value="Банк Открытие">Банк Открытие</option>
                            <option value="Депозит">Депозит</option>
                        </select>

                    </div>
                    <div class="item w_50">
                        <select disabled data-label="ЦФО">
                            <option value="Отдел продаж">Отдел продаж</option>
                            <option value="Фитнес департамент">Фитнес департамент</option>
                            <option value="Рецепция">Рецепция</option>
                            <option value="Ресторан">Ресторан</option>
                            <option value="Салон красоты">Салон красоты</option>
                            <option selected value="Административный отдел">Административный отдел</option>
                            <option value="Отдел сервис - консультирования">Отдел сервис - консультирования</option>
                            <option value="Отдел рекламы и маркетинга">Отдел рекламы и маркетинга</option>
                            <option value="IT отдел">IT отдел</option>
                            <option value="Технический отдел">Технический отдел</option>
                            <option value="Строительный отдел">Строительный отдел</option>
                            <option value="Управляющая компания">Управляющая компания</option>
                            <option value="Учредители">Учредители</option>
                        </select>

                    </div>
                    <div class="item w_50">
                        <select disabled data-label="Подразделение">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option selected value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>

                    </div>

                    <div class="item w_50">
                        <select disabled data-label="Статья бюджета">
                            <option value="Круглосуточные клубные карты">Круглосуточные клубные карты</option>
                            <option value="Депозитные карты">Депозитные карты</option>
                            <option value="Дневные клубные карты">Дневные клубные карты</option>
                            <option value="Детские клубные карты">Детские клубные карты</option>
                            <option value="Депозит входящий в карту">Депозит входящий в карту</option>
                            <option value="Пополнение депозита ЧК">Пополнение депозита ЧК</option>
                            <option value="Обнуление депозита">Обнуление депозита</option>
                            <option value="Гостевые визиты">Гостевые визиты</option>
                            <option value="Анализ состава тела">Анализ состава тела</option>
                            <option value="Продление блока тренировок">Продление блока тренировок</option>
                            <option selected value="Персональные тренировки">Персональные тренировки</option>
                            <option value="Сплит тренировки">8Сплит тренировки88</option>
                            <option value="Минигруппы">Минигруппы</option>
                            <option value="Секции">Секции</option>
                            <option value="Игровая комната">Игровая комната</option>
                            <option value="Фитнес няня">Фитнес няня</option>
                            <option value="Витрина Ресепшен">Витрина Ресепшен</option>
                            <option value="Аренда шкафчиков">Аренда шкафчиков</option>
                            <option value="Продажа напитков">Продажа напитков</option>
                            <option value="Продажа блюд">Продажа блюд</option>
                            <option value="Продажа товаров">Продажа товаров</option>
                            <option value="Продажа Спорт Пита">Продажа Спорт Пита</option>
                            <option value="Массаж">Массаж</option>
                            <option value="Ногтевой сервис">Ногтевой сервис</option>
                            <option value="Стилист">Стилист</option>
                            <option value="Косметолог">Косметолог</option>
                            <option value="Пармейстер">Пармейстер</option>
                            <option value="Товары SPA">Товары SPA</option>
                            <option value="Солярий">Солярий</option>
                            <option value="Субаренда/Аренда площадей клуба">Субаренда/Аренда площадей клуба</option>
                            <option value="Прочее">Прочее</option>
                            <option value="Возврат покупателям">Возврат покупателям</option>
                            <option value="Возврат в связи с переоформлением">Возврат в связи с переоформлением</option>
                            <option value="Арендные выплаты (постоянная часть)">Арендные выплаты (постоянная часть)</option>
                        </select>

                    </div>
                    <div class="item  w_50 ">
                        <div class="el_data">
                            <label>Контрагент</label>
                            <input disabled class="el_input" value="Константинопольский К.К.">
                            <!-- <div class="el_select_list bottom" style="top: 1.4375rem; bottom: auto; display: block;">
                                <div class="el_option selected" data-value="Тренажерный зал">Тренажерный зал</div>
                                <div class="el_option" data-value="Бассейн">Бассейн</div>
                                <div class="el_option" data-value="Групповые программы">Групповые программы</div>
                                <div class="el_option" data-value="Студия единоборств">Студия единоборств</div>
                                <div class="el_option" data-value="Детский клуб">Детский клуб</div>
                            </div> -->
                        </div>

                    </div>
                    <div class="item w_50">
                        <div class="el_data">
                            <label>Сумма</label>
                            <input disabled class="el_input" type="number" pattern="\d+(\.\d{2})?" step="0.01" value="58752.38">
                        </div>
                    </div>
                    <div class="item w_100">
                        <div class="el_data">
                            <label>Примечания</label>
                            <textarea class="el_textarea">Предоплата за февраль</textarea>
                        </div>
                    </div>
                </div>
                <div class="confirm">
                    <div class="autor">
                        <div class="date_create"><span>Редактирование:</span>01.05.2021</div>
                        <div class="user"><span>Пользователь:</span><a href="#">Помидоркин С.П.</a></div>
                    </div>
                    <button class="button icon text"><span class="material-icons">restart_alt</span>Отменить</button>
                    <!--<button class="button icon text"><span class="material-icons">save</span>Сохранить</button>-->
                    <button class="button icon text"><span class="material-icons">delete_forever</span>Удалить</button>
                </div>
            </form>
        </div>

    </div>
</div>
<!-- Конец "универсальный wrapper" для Pop-Up-ов -->
<!-- Настройки отображения -->
<div id="pop_up_data_custom" class="wrap_pop_up">
    <div class="pop_up drag">
        <div class="title handle">
            <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
            <div class="name">Настройки отображения модуля "Данные"</div>
            <div class="button icon close" onclick="pop_up_data_custom_close(); return false"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form>
                <div class="group">

                    <div class="item w_100">
                        <select multiple data-label="Колонки таблицы">
                            <option selected value="Дата">Дата</option>
                            <option selected value="Тип операции">Тип операции</option>
                            <option selected value="Вид операции">Вид операции</option>
                            <option selected value="ЦФО">ЦФО</option>
                            <option selected value="Подразделение">Подразделение</option>
                            <option selected value="Статья">Статья</option>
                            <option selected value="Сумма">Сумма</option>
                            <option selected value="Контрагент">Контрагент</option>
                            <option value="Примечания">Примечания</option>
                        </select>

                    </div>
                    <div class="item w_50">
                        <select data-label="График">
                            <option value="Прибыль">Прибыль</option>
                            <option value="Оборот">Оборот</option>
                            <option value="Приход">Приход</option>
                            <option value="Расход">Расход</option>
                            <option value="Остаток на начало">Остаток на начало</option>
                            <option value="Остаток на конец">Остаток на конец</option>

                        </select>

                    </div>
                    <div class="item w_50">
                        <select data-label="Диапазон дат по умолчанию">
                            <option value="Сегодня">Сегодня</option>
                            <option value="Текущая неделя">Текущая неделя</option>
                            <option value="Текущий месяц">Текущий месяц</option>
                            <option value="Текущий квартал">Текущий квартал</option>
                            <option value="Текущий год">Текущий год</option>
                            <option value="Вчера">Вчера</option>
                            <option value="Прошлая неделя">Прошлая неделя</option>
                            <option value="Прошлый месяц">Прошлый месяц</option>
                            <option value="Прошлый квартал">Прошлый квартал</option>
                            <option value="Прошлый год">Прошлый год</option>
                        </select>

                    </div>
                    <div class="item w_50">
                        <select data-label="Диапазон дат для кнопки 'Дата'">
                            <option value="Сегодня">Сегодня</option>
                            <option value="Текущая неделя">Текущая неделя</option>
                            <option value="Текущий месяц">Текущий месяц</option>
                            <option value="Текущий квартал">Текущий квартал</option>
                            <option value="Текущий год">Текущий год</option>
                            <option value="Вчера">Вчера</option>
                            <option value="Прошлая неделя">Прошлая неделя</option>
                            <option value="Прошлый месяц">Прошлый месяц</option>
                            <option value="Прошлый квартал">Прошлый квартал</option>
                            <option value="Прошлый год">Прошлый год</option>
                        </select>

                    </div>
                </div>

                <div class="confirm">

                    <button class="button icon text" type="submit"><span class="material-icons">done</span>Применить</button>
                    <button class="button icon text" type="reset"><span class="material-icons">restart_alt</span>Сбросить</button>

                </div>
            </form>
        </div>

    </div>
</div>
<!-- Конец "универсальный wrapper" для Pop-Up-ов -->
<!-- Настройки отображения -->
<div id="pop_up_data_filters" class="wrap_pop_up">
    <div class="pop_up drag">
        <div class="title handle">
            <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
            <div class="name">Фильтр данных</div>
            <div class="button icon close" onclick="pop_up_data_filters_close(); return false"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form>
                <div class="group">
                    <div class="item w_50">
                        <select data-label="Типовые текущие даты">
                            <option value="Сегодня">Сегодня</option>
                            <option value="Текущая неделя">Текущая неделя</option>
                            <option value="Текущий месяц">Текущий месяц</option>
                            <option value="Текущий квартал">Текущий квартал</option>
                            <option value="Текущий год">Текущий год</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-label="Типовые прошлые даты">
                            <option value="Вчера">Вчера</option>
                            <option value="Прошлая неделя">Прошлая неделя</option>
                            <option value="Прошлый месяц">Прошлый месяц</option>
                            <option value="Прошлый квартал">Прошлый квартал</option>
                            <option value="Прошлый год">Прошлый год</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <div class="el_data">
                            <label>Свой диапазон дат</label>
                            <input class="el_input" type="date" value="2021-05-01">
                        </div>

                        <div class="el_data">
                            <label></label>
                            <input class="el_input" type="date" value="2021-05-01">
                        </div>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                </div>
                <div class="group">


                    <div class="item w_50">
                        <div class="el_data">
                            <label>Номер документа</label>
                            <input class="el_input" value="">
                        </div>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-label="Тип операции" data-place="Любой">
                            <option data-image="images/arrow_back.svg" value="Приход">Приход</option>
                            <option data-image="images/arrow_forward.svg" value="Расход">Расход</option>
                            <option data-image="images/swap.svg" value="Перевод">Перемещение</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">

                        <select contenteditable="true" data-place="Любой" data-label="Вид оплаты">
                            <option value="Наличная (К1)">Наличная (К1)</option>
                            <option value="Наличная (К2)">Наличная (К2)</option>
                            <option value="Пластиковая карта">Пластиковая карта</option>
                            <option value="Интернет-эквайринг">Интернет-эквайринг</option>
                            <option value="Мандарин">Мандарин</option>
                            <option value="Совкомбанк">Совкомбанк</option>
                            <option value="Банк Открытие">Банк Открытие</option>
                            <option value="Депозит">Депозит</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-place="Любой" data-label="ЦФО">
                            <option value="Отдел продаж">Отдел продаж</option>
                            <option value="Фитнес департамент">Фитнес департамент</option>
                            <option value="Рецепция">Рецепция</option>
                            <option value="Ресторан">Ресторан</option>
                            <option value="Салон красоты">Салон красоты</option>
                            <option value="Административный отдел">Административный отдел</option>
                            <option value="Отдел сервис - консультирования">Отдел сервис - консультирования</option>
                            <option value="Отдел рекламы и маркетинга">Отдел рекламы и маркетинга</option>
                            <option value="IT отдел">IT отдел</option>
                            <option value="Технический отдел">Технический отдел</option>
                            <option value="Строительный отдел">Строительный отдел</option>
                            <option value="Управляющая компания">Управляющая компания</option>
                            <option value="Учредители">Учредители</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-place="Любое" data-label="Подразделение">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>

                    <div class="item w_50">
                        <select data-place="Любая" data-label="Статья бюджета">
                            <option value="Круглосуточные клубные карты">Круглосуточные клубные карты</option>
                            <option value="Депозитные карты">Депозитные карты</option>
                            <option value="Дневные клубные карты">Дневные клубные карты</option>
                            <option value="Детские клубные карты">Детские клубные карты</option>
                            <option value="Депозит входящий в карту">Депозит входящий в карту</option>
                            <option value="Пополнение депозита ЧК">Пополнение депозита ЧК</option>
                            <option value="Обнуление депозита">Обнуление депозита</option>
                            <option value="Гостевые визиты">Гостевые визиты</option>
                            <option value="Анализ состава тела">Анализ состава тела</option>
                            <option value="Продление блока тренировок">Продление блока тренировок</option>
                            <option value="Персональные тренировки">Персональные тренировки</option>
                            <option value="Сплит тренировки">8Сплит тренировки88</option>
                            <option value="Минигруппы">Минигруппы</option>
                            <option value="Секции">Секции</option>
                            <option value="Игровая комната">Игровая комната</option>
                            <option value="Фитнес няня">Фитнес няня</option>
                            <option value="Витрина Ресепшен">Витрина Ресепшен</option>
                            <option value="Аренда шкафчиков">Аренда шкафчиков</option>
                            <option value="Продажа напитков">Продажа напитков</option>
                            <option value="Продажа блюд">Продажа блюд</option>
                            <option value="Продажа товаров">Продажа товаров</option>
                            <option value="Продажа Спорт Пита">Продажа Спорт Пита</option>
                            <option value="Массаж">Массаж</option>
                            <option value="Ногтевой сервис">Ногтевой сервис</option>
                            <option value="Стилист">Стилист</option>
                            <option value="Косметолог">Косметолог</option>
                            <option value="Пармейстер">Пармейстер</option>
                            <option value="Товары SPA">Товары SPA</option>
                            <option value="Солярий">Солярий</option>
                            <option value="Субаренда/Аренда площадей клуба">Субаренда/Аренда площадей клуба</option>
                            <option value="Прочее">Прочее</option>
                            <option value="Возврат покупателям">Возврат покупателям</option>
                            <option value="Возврат в связи с переоформлением">Возврат в связи с переоформлением</option>
                            <option value="Арендные выплаты (постоянная часть)">Арендные выплаты (постоянная часть)</option>

                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_100">
                        <select data-place="Любой" data-label="Контрагент">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                </div>
                <div class="confirm">
                    <button class="button icon text"><span class="material-icons">done</span>Показать</button>
                    <button class="button icon text"><span class="material-icons">restart_alt</span>Сбросить</button>
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
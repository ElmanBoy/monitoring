
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
                    <tr>
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


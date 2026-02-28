
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
                    <table class="table_data" id="tbl_registry">
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

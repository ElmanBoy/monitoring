            <div class="nav">
                <div class="nav_01">
                    <div class="title">Пользователи</div>
                    <div class="button icon" title="Сбросить все фильтры">
                        <span class="material-icons">autorenew</span></div>
                    <div class="button icon text" onclick="pop_up_users_create(); return false" title="Новый документ">
                        <span class="material-icons">control_point</span>Создать
                    </div>
                    <div class="button icon text" onclick="pop_up_notify(); return false" title="Копия документа">
                        <span class="material-icons">control_point_duplicate</span>Дублировать
                    </div>
                    <div class="button icon text" onclick="pop_up_notify(); return false" title="Удалить выделенные">
                        <span class="material-icons">delete_forever</span>Удалить
                    </div>
                    <div class="button icon text" onclick="pop_up_notify(); return false" title="Установить роль">
                        <span class="material-icons">admin_panel_settings</span>Роль
                    </div>
                    <div class="button icon text" onclick="pop_up_users_custom(); return false" title="Настройка отображения">
                        <span class="material-icons">tune</span>Настройки
                    </div>
                    <div class="button icon right" title="Выйти">
                        <span class="material-icons">logout</span>
                    </div>
                </div>

            </div>
            <div class="scroll_wrap">
                <table class="table_data" id="tbl_user">
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
                                <div class="button icon text" title="Сортировать">ФИО<span class="material-icons">
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
                                <div class="button icon text" title="Сортировать">Подразделение<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text" title="Сортировать">Должность<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                            </div>
                        </th>
                        <th class="sort">
                            <div class="head_sort_filter">
                                <div class="button icon text active" title="Сортировать">Роль<span class="material-icons">
                                                north
                                            </span></div>
                                <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
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
                    <tr>
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status"><span class="material-icons">task_alt</span></td>
                        <td>Константинопольский Константин Константинович</td>
                        <td>Отдел сервис - консультирования</td>

                        <td>Подразделение</td>
                        <td>Должность</td>


                        <td>Администратор</td>
                        <td>Классный пацан</td>

                    </tr>

                    </tbody>
                </table>
            </div>

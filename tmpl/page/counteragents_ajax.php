
                <div class="nav">
                    <div class="nav_01">



                        <div class="title">Контрагенты</div>


                        <div class="button icon text" onclick="pop_up_counteragent_create(); return false" title="Новый справочник">
                            <span class="material-icons">control_point</span>Создать
                        </div>
                        <div class="button icon text disabled" onclick="pop_up_notify(); return false" title="Копия документа">
                            <span class="material-icons">control_point_duplicate</span>Дублировать
                        </div>
                        <div class="button icon text disabled" onclick="pop_up_notify(); return false" title="Удалить выделенные">
                            <span class="material-icons">delete_forever</span>Удалить
                        </div>
                        <div class="button icon" title="Сбросить все фильтры"><span class="material-icons">autorenew</span></div>
                        <div class="button icon text" onclick="pop_up_counteragent_custom(); return false" title="Настройка отображения">
                            <span class="material-icons">tune</span>Настройки
                        </div>

                        <div class="button icon right" title="Выйти">
                            <span class="material-icons">logout</span>
                        </div>
                    </div>

                </div>
                <div class="scroll_wrap">
                    <table class="table_data" id="tbl_counteragent">
                        <thead>
                        <tr class="fixed_thead">
                            <th>
                                <div class="custom_checkbox">
                                    <label class="container" title="Выделить все">
                                        <input type="checkbox" id="check_all"><span class="checkmark"></span>
                                    </label>
                                </div>
                            </th>
                            <!---->
                            <th class="sort">
                                <div class="head_sort_filter">
                                    <div class="button icon text" title="Сортировать">Статус<span class="material-icons">
                                                north
                                            </span></div>
                                    <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                </div>
                            </th>
                            <!---->
                            <th class="sort">
                                <div class="head_sort_filter">
                                    <div class="button icon text" title="Сортировать">ОПФ<span class="material-icons">
                                                north
                                            </span></div>
                                    <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                </div>
                            </th>
                            <!---->
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
                            <!---->
                            <th class="sort">
                                <div class="head_sort_filter">
                                    <div class="button icon text" title="Сортировать">ИНН<span class="material-icons">
                                                north
                                            </span></div>
                                    <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                </div>
                            </th>
                            <!---->
                            <th class="sort">
                                <div class="head_sort_filter">
                                    <div class="button icon text" title="Сортировать">КПП<span class="material-icons">
                                                north
                                            </span></div>
                                    <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                </div>
                            </th>
                            <!---->
                            <th class="sort">
                                <div class="head_sort_filter">
                                    <div class="button icon text" title="Сортировать">Банк<span class="material-icons">
                                                north
                                            </span></div>
                                    <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                </div>
                            </th>
                            <!---->
                            <th class="sort">
                                <div class="head_sort_filter">
                                    <div class="button icon text" title="Сортировать">Расчётный счёт<span class="material-icons">
                                                north
                                            </span></div>
                                    <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                </div>
                            </th>
                            <!---->
                            <!---->
                            <th class="sort">
                                <div class="head_sort_filter">
                                    <div class="button icon text" title="Сортировать">Телефон<span class="material-icons">
                                                north
                                            </span></div>
                                    <div class="button icon" title="Фильтр"><span class="material-icons">
                                                filter_alt
                                            </span></div>
                                </div>
                            </th>
                            <!---->
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
                            <td>Физ/лицо</td>
                            <td>Пупкин Вальдемар Станиславович</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>+7 916 987-65-56</td>
                            <td>Постоянный клиент, часто забывает плавки</td>
                        </tr>
                        <!-- row -->
                        <tr class="temp">
                            <td>
                                <div class="custom_checkbox">
                                    <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                                </div>
                            </td>
                            <td class="status for_del"><span class="material-icons">remove_circle_outline</span></td>
                            <td>ООО</td>
                            <td>Купелинка</td>
                            <td>77841818514</td>
                            <td>465465</td>
                            <td>ПАО "Сбербанк"</td>
                            <td>4184168464368436463</td>
                            <td>+7 495 123-45-56</td>
                            <td></td>
                        </tr>
                        <!-- row -->
                        <tr class="temp">
                            <td>
                                <div class="custom_checkbox">
                                    <label class="container"><input type="checkbox"><span class="checkmark"></span></label>
                                </div>
                            </td>
                            <td class="status"><span class="material-icons">radio_button_unchecked</span></td>
                            <td>ООО</td>
                            <td>"Мадагаскар"</td>
                            <td>77841818514</td>
                            <td>465465</td>
                            <td>ПАО "Сбербанк"</td>
                            <td>4184168464368436463</td>
                            <td>+7 495 123-45-56</td>
                            <td></td>
                        </tr>

                        </tbody>
                    </table>
                </div>
                <!--  pagination -->
                <div class="pagination">
                    <div class="paginate">
                        <a href="/catalog/knigi/page2/" title="Назад">
                            <span class="material-icons">navigate_before</span>
                        </a>
                    </div>
                    <div class="page"><a href="/catalog/knigi/">9368</a></div>
                    <div class="page"><a href="/catalog/knigi/page1/">9369</a></div>
                    <div class="page"><a href="/catalog/knigi/page2/">3</a></div>
                    <div class="page current">48759</div>
                    <div class="page"><a href="/catalog/knigi/page4/">358</a></div>
                    <div class="page"><a href="/catalog/knigi/page5/">356</a></div>
                    <div class="page dotted"><a href="/catalog/knigi/page6/">....</a></div>
                    <div class="paginate">
                        <a href="/catalog/knigi/page4/" title="Вперёд">
                            <span class="material-icons">navigate_next</span>
                        </a>
                    </div>
                </div>
                <!-- end paginate -->

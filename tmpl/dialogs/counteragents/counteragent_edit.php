<div class="pop_up drag">
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Контрагент</div>
        <div class="button icon close" onclick="pop_up_counteragent_edit_close(); return false"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form>
            <div class="group">

                <div class="item w_58">
                    <div class="el_data">
                        <label>Краткое наименование</label>
                        <input required class="el_input" type="text" value="Передовые технологии">
                    </div>
                    <div class="button icon" title="Посмотреть отчёт"><span class="material-icons">query_stats</span></div>
                </div>

                <div class="item w_41">
                    <select required data-label="Статус">
                        <option selected value="Активный">Активный</option>
                        <option value="Заблокирован">Заблокирован</option>
                    </select>

                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Полное наименование</label>
                        <input class="el_input" type="text" value="Общество с ограниченной ответственностью 'Передовые технологии'">
                    </div>

                </div>
                <div class="item w_50">
                    <select required data-label="ОПФ">
                        <option value="ф/л">ф/л</option>
                        <option selected value="ООО">ООО</option>
                        <option value="ПАО">ПАО</option>
                        <option value="ОАО">ОАО</option>
                    </select>
                    <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_guide(); return false"><span class="material-icons">folder</span></div>
                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ИНН</label>
                        <input class="el_input" type="text" value="123456789">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ОГРН</label>
                        <input class="el_input" type="text" value="987899">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>КПП</label>
                        <input class="el_input" type="text" value="4568741687">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ОКАТО</label>
                        <input class="el_input" type="text" value="4654654876846">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ОКПО</label>
                        <input class="el_input" type="text" value="45648746">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>Банк</label>
                        <input class="el_input" type="text" value="Реновационный банк Кемеровской области">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>БИК</label>
                        <input class="el_input" type="text" value="7898468764">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>Расчётный счёт</label>
                        <input class="el_input" type="text" value="79875831834384">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>Кор. счёт</label>
                        <input class="el_input" type="text" value="687874364646">
                    </div>

                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Юридический адрес</label>
                        <input class="el_input" type="text" value="123456 Красноярский район, г.Кемерово, ул.Ватучича, д.11 корп. стр.2">
                    </div>

                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Фактический адрес</label>
                        <input class="el_input" type="text" value="123456 Красноярский район, г.Кемерово, ул.Ватучича, д.11 корп. стр.2">
                    </div>
                    <div class="button icon" title="Совпадает с юридическим"><span class="material-icons">content_copy</span></div>
                </div>

                <div class="item w_50">
                    <div class="el_data">
                        <label>E-mail</label>
                        <input class="el_input" type="email" value="support@kemerobobank.ru">
                    </div>
                    <div class="button icon" title="Написать письмо"><span class="material-icons">mail_outline</span></div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>Телефон</label>
                        <input class="el_input" type="tel" value="+7 903 123-45-67">
                    </div>


                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Контактное лицо</label>
                        <input class="el_input" type="text" value="Задонский Самуил Аркадьевич">
                    </div>

                </div>




                <div class="item w_100">
                    <div class="el_data">
                        <label>Примечания</label>
                        <textarea class="el_textarea">
Нормальные ребята, работают только по безналу.
                                </textarea>
                    </div>
                </div>
            </div>
            <div class="confirm">

                <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
                <button class="button icon text"><span class="material-icons">control_point_duplicate</span>Дублировать</button>
                <button class="button icon text"><span class="material-icons">delete_forever</span>Удалить</button>


            </div>
        </form>
    </div>

</div>
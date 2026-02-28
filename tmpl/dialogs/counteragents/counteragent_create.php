<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Контрагент</div>
        <div class="button icon close" onclick="pop_up_counteragent_create_close(); return false"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form>
            <div class="group">

                <div class="item w_58">
                    <div class="el_data">
                        <label>Краткое наименование</label>
                        <input required class="el_input" type="text">
                    </div>
                    <!-- <div class="button icon" title="Посмотреть отчёт"><span class="material-icons">query_stats</span></div>-->
                </div>

                <!-- <div class="item w_41">
                    <select required data-label="Статус">
                        <option value="Активный">Активный</option>
                        <option value="Заблокирован">Заблокирован</option>
                    </select>

                </div> -->
                <div class="item w_100">
                    <div class="el_data">
                        <label>Полное наименование</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_50">
                    <select required data-label="ОПФ">
                        <option value="ф/л">ф/л</option>
                        <option value="ООО">ООО</option>
                        <option value="ПАО">ПАО</option>
                        <option value="ОАО">ОАО</option>
                    </select>
                    <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_guide(); return false"><span class="material-icons">folder</span></div>
                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ИНН</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ОГРН</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>КПП</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ОГРН</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>ОКПО</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_58">
                    <div class="el_data">
                        <label>Банк</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_41">
                    <div class="el_data">
                        <label>БИК</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>Расчётный счёт</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_50">
                    <div class="el_data">
                        <label>Кор. счёт</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Юридический адрес</label>
                        <input class="el_input" type="text">
                    </div>

                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Фактический адрес</label>
                        <input class="el_input" type="text">
                    </div>
                    <div class="button icon" title="Совпадает с юридическим"><span class="material-icons">content_copy</span></div>
                </div>




                юрадрес
                факт адрес
                контакты
                лицо


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
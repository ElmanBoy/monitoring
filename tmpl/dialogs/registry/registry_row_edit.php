<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">

        <div class="name">Элемент справочника</div>
        <div class="button icon close" onclick="pop_up_guide_row_edit_close(); return false"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form>
            <div class="group">
                <div class="item w_58">
                    <div class="el_data">
                        <label>Наименование</label>
                        <input required class="el_input" type="text" value="Наличная (К1)">
                    </div>
                </div>
                <div class="item w_41">
                    <select required data-label="Статус">
                        <option selected value="Активный">Активный</option>
                        <option value="Заблокирован">Заблокирован</option>
                    </select>
                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Примечания</label>
                        <textarea class="el_textarea">Касса при входе
                                </textarea>
                    </div>
                </div>
            </div>
            <div class="confirm">
                <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
                <button class="button icon text"><span class="material-icons">control_point_duplicate</span>Клонировать</button>
                <button class="button icon text"><span class="material-icons">delete_forever</span>Удалить</button>
            </div>
        </form>
    </div>

</div>
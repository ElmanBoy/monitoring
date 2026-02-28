<div class="pop_up drag">
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Новый элемент справочника</div>
        <div class="button icon close" onclick="pop_up_guide_row_create_close(); return false"><span class="material-icons">close</span></div>
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
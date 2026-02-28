<div class="pop_up drag">
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Настройки отображения модуля "Пользователи"</div>
        <div class="button icon close" onclick="pop_up_users_custom_close(); return false"><span class="material-icons">close</span></div>
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
            </div>

            <div class="confirm">

                <button class="button icon text" type="submit"><span class="material-icons">done</span>Применить</button>
                <button class="button icon text" type="reset"><span class="material-icons">restart_alt</span>Сбросить</button>

            </div>
        </form>
    </div>

</div>
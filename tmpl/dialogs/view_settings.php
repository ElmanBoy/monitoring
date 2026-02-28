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
<!-- Настройки отображения -->
    <div class="pop_up drag">
        <div class="title handle">
            <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
            <div class="name">Фильтр данных</div>
            <div class="button icon close" onclick="pop_up_data_filters_close(); return false"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form>
                <div class="group">
                    <div class="item w_50">
                        <select data-label="Типовые текущие даты">
                            <option value="Сегодня">Сегодня</option>
                            <option value="Текущая неделя">Текущая неделя</option>
                            <option value="Текущий месяц">Текущий месяц</option>
                            <option value="Текущий квартал">Текущий квартал</option>
                            <option value="Текущий год">Текущий год</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-label="Типовые прошлые даты">
                            <option value="Вчера">Вчера</option>
                            <option value="Прошлая неделя">Прошлая неделя</option>
                            <option value="Прошлый месяц">Прошлый месяц</option>
                            <option value="Прошлый квартал">Прошлый квартал</option>
                            <option value="Прошлый год">Прошлый год</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <div class="el_data">
                            <label>Свой диапазон дат</label>
                            <input class="el_input" type="date" value="2021-05-01">
                        </div>

                        <div class="el_data">
                            <label></label>
                            <input class="el_input" type="date" value="2021-05-01">
                        </div>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                </div>
                <div class="group">


                    <div class="item w_50">
                        <div class="el_data">
                            <label>Номер документа</label>
                            <input class="el_input" value="">
                        </div>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-label="Тип операции" data-place="Любой">
                            <option data-image="images/arrow_back.svg" value="Приход">Приход</option>
                            <option data-image="images/arrow_forward.svg" value="Расход">Расход</option>
                            <option data-image="images/swap.svg" value="Перевод">Перемещение</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">

                        <select contenteditable="true" data-place="Любой" data-label="Вид оплаты">
                            <option value="Наличная (К1)">Наличная (К1)</option>
                            <option value="Наличная (К2)">Наличная (К2)</option>
                            <option value="Пластиковая карта">Пластиковая карта</option>
                            <option value="Интернет-эквайринг">Интернет-эквайринг</option>
                            <option value="Мандарин">Мандарин</option>
                            <option value="Совкомбанк">Совкомбанк</option>
                            <option value="Банк Открытие">Банк Открытие</option>
                            <option value="Депозит">Депозит</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-place="Любой" data-label="ЦФО">
                            <option value="Отдел продаж">Отдел продаж</option>
                            <option value="Фитнес департамент">Фитнес департамент</option>
                            <option value="Рецепция">Рецепция</option>
                            <option value="Ресторан">Ресторан</option>
                            <option value="Салон красоты">Салон красоты</option>
                            <option value="Административный отдел">Административный отдел</option>
                            <option value="Отдел сервис - консультирования">Отдел сервис - консультирования</option>
                            <option value="Отдел рекламы и маркетинга">Отдел рекламы и маркетинга</option>
                            <option value="IT отдел">IT отдел</option>
                            <option value="Технический отдел">Технический отдел</option>
                            <option value="Строительный отдел">Строительный отдел</option>
                            <option value="Управляющая компания">Управляющая компания</option>
                            <option value="Учредители">Учредители</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_50">
                        <select data-place="Любое" data-label="Подразделение">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>

                    <div class="item w_50">
                        <select data-place="Любая" data-label="Статья бюджета">
                            <option value="Круглосуточные клубные карты">Круглосуточные клубные карты</option>
                            <option value="Депозитные карты">Депозитные карты</option>
                            <option value="Дневные клубные карты">Дневные клубные карты</option>
                            <option value="Детские клубные карты">Детские клубные карты</option>
                            <option value="Депозит входящий в карту">Депозит входящий в карту</option>
                            <option value="Пополнение депозита ЧК">Пополнение депозита ЧК</option>
                            <option value="Обнуление депозита">Обнуление депозита</option>
                            <option value="Гостевые визиты">Гостевые визиты</option>
                            <option value="Анализ состава тела">Анализ состава тела</option>
                            <option value="Продление блока тренировок">Продление блока тренировок</option>
                            <option value="Персональные тренировки">Персональные тренировки</option>
                            <option value="Сплит тренировки">8Сплит тренировки88</option>
                            <option value="Минигруппы">Минигруппы</option>
                            <option value="Секции">Секции</option>
                            <option value="Игровая комната">Игровая комната</option>
                            <option value="Фитнес няня">Фитнес няня</option>
                            <option value="Витрина Ресепшен">Витрина Ресепшен</option>
                            <option value="Аренда шкафчиков">Аренда шкафчиков</option>
                            <option value="Продажа напитков">Продажа напитков</option>
                            <option value="Продажа блюд">Продажа блюд</option>
                            <option value="Продажа товаров">Продажа товаров</option>
                            <option value="Продажа Спорт Пита">Продажа Спорт Пита</option>
                            <option value="Массаж">Массаж</option>
                            <option value="Ногтевой сервис">Ногтевой сервис</option>
                            <option value="Стилист">Стилист</option>
                            <option value="Косметолог">Косметолог</option>
                            <option value="Пармейстер">Пармейстер</option>
                            <option value="Товары SPA">Товары SPA</option>
                            <option value="Солярий">Солярий</option>
                            <option value="Субаренда/Аренда площадей клуба">Субаренда/Аренда площадей клуба</option>
                            <option value="Прочее">Прочее</option>
                            <option value="Возврат покупателям">Возврат покупателям</option>
                            <option value="Возврат в связи с переоформлением">Возврат в связи с переоформлением</option>
                            <option value="Арендные выплаты (постоянная часть)">Арендные выплаты (постоянная часть)</option>

                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                    <div class="item w_100">
                        <select data-place="Любой" data-label="Контрагент">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>
                        <div class="button icon" title="Очистить"><span class="material-icons">restart_alt</span></div>
                    </div>
                </div>
                <div class="confirm">
                    <button class="button icon text"><span class="material-icons">done</span>Показать</button>
                    <button class="button icon text"><span class="material-icons">restart_alt</span>Сбросить</button>
                </div>
            </form>
        </div>

    </div>
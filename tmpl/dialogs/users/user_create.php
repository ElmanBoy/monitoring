    <div class="pop_up drag">
        <div class="title handle">
            <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
            <div class="name">Новый пользователь</div>
            <div class="button icon close" onclick="pop_up_users_create_close(); return false"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form>
                <div class="group">

                    <div class="item w_100">
                        <div class="el_data">
                            <label>Фамилия</label>
                            <input class="el_input" type="text">
                        </div>
                        <div class="button icon" title="Сотрудники"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <div class="el_data">
                            <label>Имя</label>
                            <input class="el_input" type="text">
                        </div>
                    </div>
                    <div class="item w_50">
                        <div class="el_data">
                            <label>Отчество</label>
                            <input class="el_input" type="text">
                        </div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="ЦФО">
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
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_cfo(); return false"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="Подразделение">
                            <option value="Тренажерный зал">Тренажерный зал</option>
                            <option value="Бассейн">Бассейн</option>
                            <option value="Групповые программы">Групповые программы</option>
                            <option value="Студия единоборств">Студия единоборств</option>
                            <option value="Детский клуб">Детский клуб</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_department(); return false"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item w_50">
                        <select required data-label="Должность">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                        <div class="button icon" title="Добавить элемент справочника" onclick="pop_up_dir_budget(); return false"><span class="material-icons">folder</span></div>
                    </div>
                    <div class="item  w_50 ">
                        <select required data-label="Роль">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>

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
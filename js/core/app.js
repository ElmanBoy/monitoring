/**
 * @file Объект ядра с основным функционалом системы.
 */
var timer_connected = "";
var loader_active = true;
/**
 * Объект с основными методами приложения
 *
 * @global
 * @namespace el_app
 */
var el_app = {

    calendars: {popup_calendar: []},
    opened_dialog: '',
    changed_form: {},
    edited_id: 0,
    popup_opened: false,
    main_class: "",
    prev_path: "",
    curr_pat: "",
    mainInitRunning: false,
    currentEditedSelect: null,
    current_title: "",


    /**
     * Метод активирует прелоудер
     *
     * @function loader_show
     * @memberof el_app
     */
    loader_show: function () {
        $(".item.logo").addClass("logo_animate");
        $("html").css("cursor", "wait");
        //$("body").append("<div id='loader_wrap'></div>");
    },

    /**
     * Метод деактивирует прелоудер
     *
     * @function loader_hide
     * @memberof el_app
     */
    loader_hide: function () {

        $("html").css("cursor", "default");
        setTimeout(function () {
            $(".item.logo").removeClass("logo_animate");
        }, 1500);
        //$("#loader_wrap").remove();
    },

    /**
     * Метод создает ajax запрос к роутеру в index.php в корне проекта.
     *
     * Этот метод вызывается в других методах и не используется напрямую в приложении.
     *
     * @function loadContent
     * @memberof el_app
     * @param url {string} Имя файла обработчика AJAX-запроса без расширения.
     * @param mode {string} Режим. Варианты: "popup", "mainpage".
     * @param params {string} Дополнительные параметры в формате GET-запроса.
     * @param module {string} Имя модуля, к которому направлен запрос
     * @param action {string} Имя обработчика запроса (файла)
     * @returns {(json|html)} JSON-объект или HTML-код
     */
    loadContent: function (url, mode, params, module = undefined, action = undefined) {
        return $.ajax({
            url: '/',
            type: 'POST',
            data: {
                "ajax": 1,
                "action": action,
                "mode": mode,
                "url": url,
                "params": params,
                "module": module
            }
        });
    },

    /**
     * Метод запрашивает контент для страницы через метод el_app.loadContent
     * и вставляет полученный HTML-код в контейнер страницы с классом "main_data"
     *
     * @function setMainContent
     * @memberof el_app
     * @param url {string} Имя файла обработчика AJAX-запроса без расширения.
     * @param params {string} Дополнительные параметры в формате GET-запроса.
     * @param scrollTo
     */
    setMainContent: function (url, params, scrollTo = false) {
        let paramsArr = [];
        paramsArr = document.location.search.split("&");
        if (typeof params !== "undefined" && params.length > 0) {
            params = params.replace(/(\?+)/g, "");
        }
        tinymce.remove();
        $(".flatpickr-calendar").remove();
        $(".tipsy").hide();

        el_app.prev_path = document.location.pathname + (paramsArr[0].indexOf("id=") > -1 ? paramsArr[0] : "");

        //$(".preloader").fadeIn();
        //$(document).trigger("content_unload");
        $.when(el_app.loadContent(url, "mainpage", params)).then(function (answer) {
            let regexp = /<div class="title">([^<]+)<\/div>/i;
            let title = regexp.exec(answer),
                queryString = "",
                currDate = new Date();
            if (typeof params !== "undefined" && params.length > 0) {
                paramsArr = params.split("&");
                queryString = "?" + params;
            }
            if (typeof title != "undefined" && title != null) {
                if (title.indexOf(",") > -1) {
                    title = title.split(",");
                }
                document.title = "MonitoringCRM - " + ((typeof title != "undefined") ? title[1] : "");
            }
            $(".main_data").html(answer);
            if (typeof scrollTo === "undefined" && scrollTo !== false) {
                $("body,html").animate({"scrollTop": 0}, "slow");
            }
            if (scrollTo.length > 0) {
                el_tools.scrollToObj(scrollTo);
            }

            /*if(typeof params != "undefined" && params.indexOf("open_dialog") > -1){
                let od = params.split("=");
                el_app.dialog_open("view_task", {taskId: od[1], view_result: 0}, "calendar");
            }*/

            history.pushState({param: 'Value'}, answer.title, url + queryString);
            paramsArr = document.location.search.split("&");
            el_app.curr_path = document.location.pathname + '/' + document.location.search;
            el_tools.setcookie('last_path', el_app.curr_path);
            el_app.mainInit();
            $(document).trigger("content_load", {url, params});
            //$(".preloader").fadeOut();
        });
    },

    /**
     * Обновляет контент страницы через AJAX-запрос
     *
     * @function reloadMainContent
     * @memberof el_app
     */
    reloadMainContent: function () {
        el_app.setMainContent(document.location.pathname, document.location.search.replace("?", ""));
    },

    /**
     * Получает контент для открываемого попапа
     *
     * @function setPopupContent
     * @memberof el_app
     * @param url {string} Имя скрипта, выдаваемого контент для попапа, без расширения
     */
    setPopupContent: function (url) {
        $.when(el_app.loadContent(url, "popup", "", "")).then(function (answer) {
            //$("body").css("overflow", "hidden");
            $(".card_up").html(answer.main).css("display", "flex");
            $("#close_info").on("click", function (e) {
                e.preventDefault();
                $(".card_up").html("").css("display", "none");
                //$("body").css("overflow", "auto");
                return false;
            });
            el_app.mainInit();
        });
    },

    /**
     * Получает через ajax список для наполнения зависимого select
     *
     * @param params {string} Дополнительные параметры в формате GET-запроса.
     * @param module {string} Имя модуля, к которому направлен запрос
     * @param action {string} Имя скрипта, выдаваемого контент для select, без расширения
     * @param target {object} Ссылка на целевой select в виде jQuery объекта
     * @return {string}
     */
    setChildrenSelectOptions: function (params, module, action, target) {
        return $.when(el_app.loadContent("", "getSelect", params, module, action)).then(function (answer) {
            target.html(answer);
            return answer;
        });
    },

    /**
     * Открывает диалоговое окно с заданными параметрами
     *
     * @function dialog_open
     * @memberof el_app
     * @param dialog_id {string} ID окна
     * @param params {string} Параметры в формате GET-запроса
     * @param module {string} Строковый идентификатор модуля
     */
    dialog_open: function (dialog_id, params, module) {
        $.when(el_app.loadContent(dialog_id, "popup", params, module)).then(function (content) {
            const url = new URL(window.location);
            let open_dialog = JSON.stringify(params);
            if (module) {
                url.searchParams.set('module', module);
            } else {
                url.searchParams.set('module', url.pathname.replace("/", ""));
            }
            /*if (typeof params === "object"){
                if ("docId" in params){
                    open_dialog = params.docId;
                }
            }*/
            url.searchParams.set('mode', dialog_id);
            url.searchParams.set('open_dialog', open_dialog);

            $("body").append("<div class='wrap_pop_up' id='" + dialog_id + "'></div>");
            let $dialog = $("div#" + dialog_id);

            jQuery.ajaxSetup({async: false});
            $dialog.html(content).css("display", "flex");
            let $form = $("div#" + dialog_id + " form"),
                $buttons = $form.find("button");

            el_app.changed_form[dialog_id] = "";
            setTimeout(function () {
                el_app.changed_form[dialog_id] = $form.serialize().replace(/&question_time%5B%5D=\d*&/g, "&");
                jQuery.ajaxSetup({async: true});
            }, 1000);

            el_app.popup_init();

            //Проверяем изменены ли данные полей и выводим предупреждение в случае закрытия диалога
            $("#" + dialog_id + " .close, #" + dialog_id + " .close_button").on("click", function () {
                if (!$form.hasClass("rejected")) {
                    if (el_app.changed_form[dialog_id] !== $form.serialize().replace(/&question_time%5B%5D=\d*&/g, "&")) {
                        let ok = confirm("Желаете сохранить изменения?").then(
                            response => {
                                if (response) {
                                    el_app.changed_form[dialog_id] = $form.serialize();
                                    $form.trigger("submit");
                                } else {
                                    el_app.dialog_close(dialog_id);
                                }
                            }
                        )
                    } else {
                        el_app.dialog_close(dialog_id);
                    }
                }else{
                    el_app.dialog_close(dialog_id);
                }
            });

            setTimeout(function () {
                $("body").trigger("dialog_open", dialog_id);
            }, 300);

            //$("#oper_edit :input:visible:first").trigger("focus");

            $(document).off("keydown").on("keydown", function (e) {
                switch (e.which) {
                    //Клавиша Enter
                    /*case 13:
                        $("#" + dialog_id + " .confirm button:first").click();
                        break;*/
                    //Клавиша Escape
                    case 27:
                        el_app.dialog_close(dialog_id);
                        el_app.select_table_row();
                        break;
                }

                if ($form.find("button.spend").is("button")) {
                    // если нажаты клавишы Ctrl и Enter
                    if ((e.ctrlKey || e.metaKey) && e.which === 13) {
                        e.preventDefault();
                        $("#" + dialog_id + " .confirm button.spend").trigger("mousedown").trigger("click");
                    }
                }
            });

            if (el_app.popup_opened) {
                $dialog.find(".pop_up").css({"left": "20px", "top": "20px"});
            }

            // replaceState меняет текущий URL без добавления в историю
            history.replaceState({}, "", url);
            el_app.current_title = document.title;
            document.title = $("#" + dialog_id + " .name").text();

            el_app.popup_opened = $(".wrap_pop_up").css("display") === "flex";
            el_app.opened_dialog = dialog_id;
        });
    },

    /**
     * Закрывает диалоговое окно
     *
     * @function dialog_close
     * @memberof el_app
     * @param dialog_id {string} ID окна
     */
    dialog_close: function (dialog_id) {
        const url = new URL(window.location);
        url.searchParams.delete('module');
        url.searchParams.delete('mode');
        url.searchParams.delete('open_dialog');

        // Просто заменяем текущий URL
        history.replaceState({}, '', url);
        document.title = el_app.current_title;

        $("#" + dialog_id).trigger("dialog_close").remove();
        if (el_app.calendars.hasOwnProperty("popup_calendar")) {
            if ("destroy" in el_app.calendars.popup_calendar) {
                el_app.calendars.popup_calendar.destroy();
            }
            el_app.popup_opened = $(".wrap_pop_up").css("display") === "flex";
            delete el_app.calendars.popup_calendar;
            delete el_app.changed_form[dialog_id];
            el_app.keyboardInit(el_app.main_class);
            el_app.select_table_row();
        }
    },

    focus_manage: function () {
        /*$( window ).on("dialog_open dialog_close", function(e, d){
            $("#oper_edit :input:visible:first").trigger("focus");
            //console.log(e, d)
        });*/
    },

    /**
     * Определение предвыбранной строки таблицы
     *
     * По умолчанию при обновлении таблицы выбрана первая строка.
     * Если было произведено редактирование записи, то при обновлении будет выбрана редактируемая ранее строка
     *
     * @function select_table_row
     * @memberof el_app
     */
    select_table_row: function () {
        if (!$(".table_data").hasClass("statistic")) {
            if (!el_app.popup_opened && !el_tools.notify_opened) {
                let $tr = (el_app.edited_id > 0) ? "tr[data-id=" + el_app.edited_id + "]" : "tr:first";

                setTimeout(function () {
                    $(".table_data tbody " + $tr + " td:last").trigger("click");
                    $(".table_data tbody " + $tr).trigger("focus");
                }, 300);

            }
        }
    },

    popup_init: function () {
        let $single_date = $(".single_date");
        let $single_date_time = $(".single_date_time");

        $("select:not(.flatpickr-monthDropdown-months, .not_chosen)").chosen(
            {
                search_contains: true,
                no_results_text: "Ничего не найдено.",
                group_search: false,
                allowInput: true
            }
        );

        el_app.calendars.popup_calendar = $single_date.flatpickr({
            locale: 'ru',
            mode: 'single',
            time_24hr: true,
            dateFormat: 'Y-m-d',
            altFormat: 'd.m.Y',
            allowInput: true,
            conjunction: '-',
            altInput: true,
            altInputClass: "el_input",
            firstDayOfWeek: 1
        });

        el_app.calendars.popup_calendar = $single_date_time.flatpickr({
            locale: 'ru',
            mode: 'single',
            time_24hr: true,
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            altFormat: 'd.m.Y H:i',
            allowInput: true,
            conjunction: '-',
            altInput: true,
            altInputClass: "el_input",
            firstDayOfWeek: 1
        });

        el_tools.initForms();

        $("input[type=tel]").mask('+7 (999) 999-99-99');
        $single_date.next(".el_input").mask("99.99.9999");

        $(".drag").draggable(({
            handle: '.handle'
        }));

        $('[title]').tipsy({
            arrowWidth: 10,
            /* attr: 'data-tipsy',*/
            cls: null,
            duration: 150,
            offset: 16,
            position: 'right',
            /* trigger: 'hover',*/
            onShow: null,
            onHide: null
        });

        // Инициализация при открытии dropdown
        $(document).on('mousedown', '.chosen-container', function () {
            setTimeout(el_app.initChosenTipsy, 100); // Небольшая задержка для рендеринга
        });

        // Инициализация при наведении
        $(document).on('mouseover', '.chosen-container .chosen-results li', function () {
            el_app.initChosenTipsy();
        });

        /* $(".button_registry").off("click").on("click", function () {
             let id = $(this).data("reg");
             el_app.dialog_open("registry_items_create", id, "registry");
         });*/
        $(".button_cagent").off("click").on("click", function () {
            el_app.dialog_open("counteragent_create", 0, "counteragents");
        });

        $(".viewmode-select").chosen("destroy");
    },

    /**
     * Инициализация основного интерфейса системы.
     *
     * Инициализаруются плагины календарей, выпадающих списков, всплывающих подсказок.
     * Задаются реакции контролов на события мыши и клавиш.
     * Ссылкам переназначается поведение с переходов по ним на AJAX-запросы.
     *
     * @function mainInit
     * @memberof el_app
     */
    mainInit: function () {
        //Предотвращение множественного вызова
        if (el_app.mainInitRunning) {
            //console.log('mainInit уже выполняется!');
            $(".preloader").fadeOut('fast');
            //return; // или return Promise.reject(), throw new Error()
        }
        el_app.mainInitRunning = true;
        let $table_data = $(".table_data"),
            main_class = '', $single_date = null;
        if ($table_data.is("table")) {
            main_class = $table_data.attr("id").replace("tbl_", "");
            $single_date = $(".single_date");
            $single_date.next(".el_input").mask("99.99.9999");
        }


        el_app.main_class = main_class;
        el_app.focus_manage();
        el_app.popup_init();

        if (document.location.pathname !== "/") {
            /*clearInterval(timer_connected);
            timer_connected = setInterval(function () {
                loader_active = false;
                $.post("/", {ajax: 1, action: "checkConnect"}, function (data) {
                    if (data === "0") {
                        clearInterval(timer_connected);
                        document.location.href = "?logout";
                    }
                }).fail(function () {
                    alert("Утеряно подключение к серверу!");
                    clearInterval(timer_connected);
                    document.location.href = "?logout";
                });

            }, 5000);*/
        }


        $("#logout").on("click", function () {
            document.location.href = "?logout";
        });

        $("select:not(.flatpickr-monthDropdown-months):not(.viewmode-select)").chosen(
            {
                search_contains: true
            }
        );

        el_app.calendars.range_calendar = $("[type=date]:not(.single_date)").flatpickr({
            locale: 'ru',
            mode: 'range',
            //inline: true,
            time_24hr: true,
            dateFormat: 'Y-m-d',
            altFormat: 'd.m.Y',
            conjunction: '-',
            altInput: true,
            allowInput: true,
            altInputClass: "el_input",
            firstDayOfWeek: 1,
            //minDate: "today"
        });
        el_app.calendars.time_calendar = $("[name=time]").flatpickr({
            locale: 'ru',
            enableTime: true,
            noCalendar: true,
            allowInput: true,
            dateFormat: "H:i",
            time_24hr: true
        });
        /*el_app.calendars.single_calendar = $single_date.flatpickr({
            locale: 'ru',
            mode: 'single',
            //inline: true,
            time_24hr: true,
            allowInput: true,
            dateFormat: 'Y-m-d',
            altFormat: 'd.m.Y',
            conjunction: '-',
            altInput: true,
            altInputClass: "el_input",
            firstDayOfWeek: 1,
            //minDate: "today"
        });*/

        el_tools.initForms();

        $("input[type=tel]").mask('+7 (999) 999-99-99');

        $("body").off("dialog_open").on("dialog_open", function () {
            $(".el_textarea").on("change input click", function () {
                let lht = parseInt($(".el_textarea").css("fontSize")) * 1.5,
                    padding = parseInt($(this).css("padding")),
                    lines = parseInt($(this).prop('scrollHeight') / lht);
                $(this).css("height", lines * lht + padding);
            });
        });


        el_app.dblClickRow();

        el_app.keyboardInit(main_class);

        el_app.initTabs();

        el_app.binFullscreen();

        el_app.bindRegistryButtonsExternal();

        //Клик по кнопке "Сбросить все фильтры"
        $("#button_nav_refresh").off("click").on("click", function () {
            let query = el_tools.getUrlVar(document.location.href),
                qString = "",
                allQuery = [];

            for (let param in query) {
                if (param !== "sort" && param !== "filter")
                    allQuery.push(param + "=" + query[param]);
            }
            if (allQuery.length > 0) {
                qString = allQuery.join("&");
            }

            $("#filterer select").val("").trigger("chosen:updated");

            el_app.setMainContent(document.location.pathname, qString);
        });

        //Вызов диалога настроек отображения
        $("#button_nav_settings").off("click").on("click", function () {
            el_app.dialog_open("view_settings");
        });
        //Снять выделние в таблице
        $("#check_all").off("click").on("click", function () {
            let checked = $(this).prop("checked"),
                $tr = $(".table_data tbody tr");
            $(".custom_checkbox input").prop("checked", checked);
            if (checked) {
                $tr.addClass("selected");
            } else {
                $tr.removeClass("selected");
            }
        });

        //Клик по чекбоксу в строке таблицы
        $(".custom_checkbox input").on("change", function () {
            if ($(this).prop("checked")) {
                $(this).closest("tr").addClass("selected");
            } else {
                $(this).closest("tr").removeClass("selected");
            }

            //Если отмечен один и более чекбоксов снимаем класс disabled
            // у иконок групповых действий вверху
            if ($(".custom_checkbox input:checked").length > 0) {
                $(".group_action").removeClass("disabled");
            } else {
                $(".group_action").addClass("disabled");
            }
        });

        $(".drag").draggable(({
            handle: '.handle'
        }));

        $('[title]').tipsy({
            arrowWidth: 10,
            /* attr: 'data-tipsy',*/
            cls: null,
            duration: 150,
            offset: 16,
            position: 'right',
            /* trigger: 'hover',*/
            onShow: null,
            onHide: null
        });


        $(".pagination a").off("click").on("click", function (e) {
            e.preventDefault();
            let link = $(this).attr("href"),
                linkArr = link.split("?");
            $(".pagination .page").removeClass("current");
            $(this).closest(".page").addClass("current");
            el_app.setMainContent(linkArr[0], linkArr[1]);
            return false;
        });

        $(document).off("registry_item_updated").on("registry_item_updated", function (e, data) {
            let reg_id = parseInt(data.reg_id),
                row_id = data.row_id;
            $.post("/", {ajax: 1, action: "getRegistryItems", regId: reg_id}, function (data) {
                let $select = $("select[data-reg=" + reg_id + "]");
                $(el_app.currentEditedSelect).prev("input[type=hidden]").val(row_id);
                console.log(reg_id, row_id, el_app.currentEditedSelect);
                for (let i = 0; i < $select.length; i++) {
                    let $s = $($select[i]);
                    $s.html(data).val($s.prev("input").val()).trigger("chosen:updated").trigger("change");
                }
            });
            console.log(data);
        });

        let $filterer = $("#filterer");
        if ($filterer.is("div")) {
            $("#filter_panel").off("click").on("click", function () {
                if ($filterer.hasClass("show")) {
                    el_app.hideFilterer();
                } else {
                    el_app.showFilterer();
                }
            });
        } else {
            $filterer.hide();
        }

        $("input[type=tel]").mask('+7 (999) 999-99-99');

        $(".breadcrumb li a").off("click").on("click", function (e) {
            e.preventDefault();
            el_app.setMainContent($(this).attr("href"));
        });

        let $notificationsList = $("#notificationsList");
        if ($notificationsList.is("div")) {
            $("#notification_panel").off("click").on("click", function () {
                if ($notificationsList.hasClass("show")) {
                    el_app.hideNotifications();
                } else {
                    el_app.showNotifications();
                }
                $("#notificationsList .close").off("click").on("click", function () {
                    el_app.hideNotifications();
                });
            });
            el_app.bindNotificationDelete();
            el_app.bindNotificationClick();
        } else {
            $notificationsList.hide();
        }

        $(document).off("task_viewed").on("task_viewed", function (e, data) {
            $.post("/", {ajax: 1, action: "setNotificationViewed", id: data.taskId}, function (data) {
                el_app.updateNotifications();
            });
        });

        setTimeout(function () {
            el_app.select_table_row();
        }, 500);

        if (el_app.curr_path !== el_app.prev_path) {
            el_app.cloneFilterer();
        }

        $(".viewmode-select").chosen("destroy");

        $("#clear_form").off("click").on("click", function (e) {
            e.preventDefault();
            $("#clear_institutions").trigger("click");
            let $form = $(this).closest("form");
            $form.find("input, textarea").val("")
            $form.find("select").val("0").trigger("chosen:updated");
            $(".group.institutions").not(":first").remove();
            if (typeof window.tinyMCE == "object") {
                tinyMCE.activeEditor.setContent("");
            }
        });

        $("#clear_institutions").off("click").on("click", function () {
            el_app.clearInstitutions();
        });

        /*$("h1.expand_link").off("click").on("click", function () {
            $(".expandArea").slideToggle(200, function () {
                $("h1.expand_link .arrow").css("transform",
                    $(".expandArea").is(":visible") ? "rotate(180deg)" : "rotate(0deg)")
            });
        });*/

        $(".preloader").fadeOut('fast');

    },
    /*selectedMonths: {},
    bindQuarter: function () {
        let controlId = "quarterWrapper",
            yearValue = "2025";

        var quarterWrapper = $('.quarterWrapper');
        var elData = $('.el_data');

        // Если клик был не по quarterWrapper и не по его родителю el_data
        if (!quarterWrapper.is(e.target) && quarterWrapper.has(e.target).length === 0 &&
            !elData.is(e.target) && elData.has(e.target).length === 0) {
            quarterWrapper.hide();
        }

        $(".quarterWrapper .quarter .ui.label:not(.disabled)").off("click").on("click", function () {
            if (!$(this).parent().hasClass("disabled")) {
                if ($(this).hasClass("selected")) {
                    $(this).removeClass("selected");
                    var id = $(this).attr("id").replace("month", "");
                    var idx = this.selectedMonths[controlId][yearValue].indexOf(id);
                    this.selectedMonths[controlId][yearValue].splice(idx, 1);
                } else {
                    $(this).addClass("selected");
                }
                var $monthsLabelsSelected = $(".quarterWrapper .ui.label .ui.label.selected"),
                    parentQuarter = $(this).parents(".quarter");
                if (parentQuarter.children(".selected").length == 3) {
                    parentQuarter.addClass("selected");
                } else {
                    parentQuarter.removeClass("selected");
                }
                if ($monthsLabelsSelected.length > 0) {
                    $(".reportRange").val("");
                }
                el_app.getSelectedMonths(controlId, yearValue);
            }
        });
        $(".quarterWrapper .quarter b").off("click").on("click", function (){
            let $quarter = $(this).closest(".quarter");
            $quarter.children(".ui.label").removeClass("selected");
            if ($quarter.hasClass("selected")) {
                $quarter.removeClass("selected");
            }else{
                $quarter.addClass("selected");
            }
        });
    },

    getSelectedMonths: function (controlId, yearValue) {
        var $monthsLabelsSelected = $("#" + controlId + " .ui.label .ui.label.selected"),
            selectedCount = 0;
        if (!(this.selectedMonths)[controlId])
            this.selectedMonths[controlId] = {};
        if (!this.selectedMonths[controlId][yearValue])
            this.selectedMonths[controlId][yearValue] = [];
        for (var s = 0; s < $monthsLabelsSelected.length; s++) {
            var id = $monthsLabelsSelected[s].id.replace("month", "");
            if (this.selectedMonths[controlId][yearValue].indexOf(id) == -1) {
                this.selectedMonths[controlId][yearValue].push(id);
                //selectedMonths[controlId][yearValue] = array_unique(selectedMonths[controlId][yearValue]);
            }
        }
        for (var yearValue in this.selectedMonths[controlId]) {
            if (this.selectedMonths[controlId][yearValue].length > 0) {
                this.selectedMonths[controlId][yearValue].sort();
                selectedCount++;
            }
        }
        if (controlId == "periodsContainer") {
            $(".reportRange").attr("disabled", selectedCount > 0);

            $("#selectedMonths").val(selectedCount > 0 ? JSON.stringify(selectedMonths[controlId]) : "");

            if ($monthsLabelsSelected.length > 0) {
                $(".reportRange").val("");
            }
        }
        if (controlId == "quarterContainer") {
            $("#monthsSelect").attr("disabled", selectedCount == 0);
            $(".aQuarter a").attr("data-value", selectedCount > 0 ? JSON.stringify(selectedMonths[controlId]) : "period");
        }
        console.log(this.selectedMonths)
        //setcookie('aPeriod', this.selectedMonths, (new Date).getTime() + (2 * 365 * 24 * 60 * 60 * 1000));
    },*/

    clearInstitutions: function () {
        let $allIns = $(".group.institutions");
        if ($allIns.is("div")) {
            $allIns.not(":first").remove();
            $(".group.institutions:first select").val("0").trigger("chosen:updated");
            $(".group.institutions:first input").val("");
            $(".group.institutions:first .button.clear").hide();
        }
    },

    clearAgreement: function () {
        let $agreement_list_group = $(".agreement_list_group");
        $(".agreement_list_group .sections").not(":first, .signers").remove();
        $(".agreement_list_group .sections:first .agreement_list").html("");
        $agreement_list_group.closest(".group").find("select").val("0").trigger("chosen:updated");
        $agreement_list_group.closest(".group").find("input, textarea").val("");
        $(".agreement_list_group .sections:first .button.clear").hide();
    },

    // Функция для инициализации tipsy
    initChosenTipsy: function () {
        $('.chosen-container .chosen-results li').each(function () {
            var $li = $(this);
            var title = $li.data('original-title') || $li.attr('title');

            if (title && !$li.data('tipsy-initialized')) {
                $li.tipsy({
                    arrowWidth: 10,
                    cls: null,
                    duration: 1000,
                    offset: 16,
                    position: 'right'
                }).data('tipsy-initialized', true);
            }
        });
    },

    updateNotifications: function () {
        $.post("/", {ajax: 1, action: "getNotifications"}, function (data) {
            let answer = JSON.parse(data),
                $notification_panel = $("#notification_panel");
            $("#notificationsList .group").html(answer.messages);
            $notification_panel.attr("title", "" + answer.countTitle);
            $("#messageCount").text(answer.countTotal);
            if (parseInt(answer.countUnseens) === 0) {
                $notification_panel.removeClass("shake");
            }
            if (parseInt(answer.countTotal) === 0) {
                //$notification_panel.hide();
            }
            el_app.bindNotificationDelete();
            el_app.bindNotificationClick();
        });
    },

    bindNotificationDelete: function () {
        $("#notificationsList .deleteNotification").off("click").on("click", function (e) {
            e.stopPropagation();
            let nId = $(this).data("id");
            $.post("/", {ajax: 1, action: "deleteNotification", id: nId}, function (data) {
                el_app.updateNotifications();
            });
        });
    },

    bindNotificationClick: function () {
        $("#notificationsList .notificationItem").off("click").on("click", function () {
            let dialogId = $(this).data("id");
            el_app.setMainContent($(this).data("path"), "open_dialog=" + dialogId);
            $(document).trigger('task_viewed', [{taskId: dialogId}]);

        });
    },

    //Кнопки "Добавить элемент справочника" и "Редактировать элемент справочника"
    bindRegistryButtonsExternal: function () {
        $(".button_registry").off("click").on("click", function (e) {
            let id = $(this).data("reg");
            el_app.currentEditedSelect = $(e.currentTarget).closest(".item").find("select");
            el_app.dialog_open("registry_items_create", id, "registry");
        });
        $(".button_registry_edit").off("click").on("click", function (e) {
            let id = $(this).data("reg"),
                row = $(this).closest(".item").find("select").val();
            el_app.currentEditedSelect = $(e.currentTarget).closest(".item").find("select");
            if (parseInt(row) > 0) {
                el_app.dialog_open("registry_items_edit_dialog", [row, id], "registry");
            } else {
                alert("Выберите значение из выпадающего списка для редактирования.")
            }
        });
    },

    getExtAnswersCount: function () {
        loader_active = false;
        $.post("/", {ajax: 1, action: "checkExtAnswers"}, function (data) {
            let answer = JSON.parse(data);
            if (parseInt(answer.resultText) > 0) {
                if ($("[href='/registry'] .alert").is("div")) {
                    $("[href='/registry'] .alert").text(answer.resultText);
                    $('[title]').tipsy({
                        arrowWidth: 10,
                        duration: 150,
                        offset: 16,
                        position: 'right'
                    });
                } else {
                    $("[href='/registry'], [href='/registry/?id=14']").append("<div class='alert' " +
                        "title='Есть альтернативные ответы для утверждения'>"
                        + answer.resultText + "</div>");
                }
                if ($("[href='/registry/?id=14'] .alert").is("div")) {
                    $("[href='/registry/?id=14'] .alert").text(answer.resultText)
                } else {
                    $("[href='/registry/?id=14']").append("<div class='alert' " +
                        "title='Есть альтернативные ответы для утверждения'>"
                        + answer.resultText + "</div>");
                    $('[title]').tipsy({
                        arrowWidth: 10,
                        duration: 150,
                        offset: 16,
                        position: 'right'
                    });
                }
            }
            loader_active = true;
        });
    },

    /**
     * Реакция на двойной клик по строке таблицы.
     *
     * Открывает диалог редактирования записи, по которой кликнули
     *
     * @function dblClickRow
     * @memberof el_app
     */
    dblClickRow: function () {
        //Реагируем на двойной клик по строке таблицы открытием диалога редактирования записи
        $(".table_data tbody tr:not(.temp):not(.noclick) td:not(:first-child), .table_data tbody tr:not(.noclick) td:not(.link)")
            .off("dblclick")
            .on("dblclick", function (e) {
                window.getSelection().removeAllRanges();
                e.preventDefault();
                $(".preloader").fadeIn('fast');
                let row_id = $(this).closest("tr").data("id"),
                    parent_id = $(this).closest("tr").data("parent"),
                    params = row_id;
                if (typeof parent_id !== "undefined") {
                    params = [row_id, parent_id];
                }
                if ($(this).closest("table").hasClass("historyTbl")) {
                    params = [row_id, "no_pane"]
                }
                el_app.edited_id = row_id;
                el_app.dialog_open(el_app.main_class + "_edit", params);

            })
            .off("click").on("click", function () {
            $(".table_data tbody tr").removeClass("selected");
            $(this).closest("tr").addClass("selected");
        });

        $(".table_data tbody tr:not(.noclick) td.group a, .table_data tbody tr:not(.noclick) td.link span.reg_settings")
            .off("click").on("click", function (e) {
            e.preventDefault();
            $(this).closest("td").trigger("dblclick");
        });
        $(".preloader").fadeOut('fast');
    },

    /**
     * Прослушивание нажатий клавиш.
     *
     * Дублируются действия, вызываемые кликом мыши
     *
     * @function keyboardInit
     * @memberof el_app
     * @param module_code
     */
    keyboardInit: function (module_code) {
        $(document).off("keydown").on("keydown", function (e) {
            let $tr = $(".table_data tbody tr"),
                $trs = $(".table_data tbody tr.selected"),
                $pagination = $(".pagination");

            if ($tr.is(e.target) || $tr.has(e.target).length > 0) {

                $tr.on("focus", function (e) {
                    $tr.removeClass("selected");
                    $(this).addClass("selected");
                });

                switch (e.which) {
                    case 13: //Enter
                        let row_id = $trs.data("id");
                        el_app.edited_id = row_id;
                        el_app.dialog_open(module_code + "_edit", row_id);
                        setTimeout(function () {
                            $("#" + module_code + "_edit input:visible:first").trigger("focus");
                        }, 500);
                        break;
                    case 27: //Escape
                        if ($("#" + module_code + "_edit").is("div"))
                            el_app.dialog_close(module_code + "_edit");
                        $(".table_data tbody tr.selected").trigger("focus");
                        break;
                    case 32: //Пробел
                        e.preventDefault();
                        $trs.find("[type=checkbox]").click();
                        e.target.focus();
                        break;
                    case 33: //PageUp
                    case 36: //Home
                        $tr.removeClass("selected").first().addClass("selected").focus();
                        break;
                    case 34: //PageDown
                    case 35: //End
                        $tr.removeClass("selected").last().addClass("selected").focus();
                        break;
                    case 38: //Вверх
                        if ($trs.prev("tr").is("tr"))
                            $trs.removeClass("selected").prev("tr").addClass("selected").focus();
                        break;
                    case 37: //Влево
                        if ($pagination.is("div")) {
                            let $prev = $(".pagination .current").prev(".page").find("a");
                            if ($prev.is("a"))
                                $prev.click();
                        }
                        break;
                    case 39: //Вправо
                        if ($pagination.is("div")) {
                            let $next = $(".pagination .current").next(".page").find("a");
                            if ($next.is("a"))
                                $next.click();
                        }
                        break;
                    case 40: //Вниз
                        if ($trs.next("tr").is("tr"))
                            $trs.removeClass("selected").next("tr").addClass("selected").focus();
                        break;
                    case 45: //Insert
                        e.preventDefault();
                        $("#button_nav_create").click();
                        break;
                    case 46: //Delete
                        e.preventDefault();
                        $("#button_nav_delete").click();
                        break;
                }
            }
        });
        /**
         * Реакция на нажатие клавиши Ctrl + N - новая запись
         * @event onkeydown
         */
        el_tools.controlPlusKey('N', function () {
            $("#button_nav_create").click();
        });

        /**
         * Реакция на нажатие клавиши Ctrl + C - клонирование
         * @event onkeydown
         */
        el_tools.controlPlusKey('C', function () {
            $("#button_nav_clone").click();
        });
    },

    /**
     * Инициализация поведения сортировщиков в заголовках таблиц
     *
     * @function sort_init
     * @memberof el_app
     */
    sort_init: function () {
        $(".sorter").on("click", function () {
            let field = $(this).data("field"),
                query = el_tools.getUrlVar(document.location.href),
                qr = field,
                allQuery = [],
                q = [],
                preQuery = "";

            for (let param in query) {
                if (param !== "sort"/* && param !== "filter"*/)
                    allQuery.push(param + "=" + query[param]);
            }
            if (allQuery.length > 0) {
                preQuery = allQuery.join("&") + "&";
            }
            if (typeof query.sort !== "undefined") {

                let q = query.sort.split(":"),
                    f = field.replace("_r", ""),
                    f_r = field.replace("_r", "") + "_r",
                    fieldPos = q.indexOf(f),
                    fieldPos_r = q.indexOf(f_r);

                q = el_tools.array_clean(q);
                q = el_tools.array_unique(q);

                if (fieldPos > -1 || fieldPos_r > -1) {
                    if (fieldPos > -1)
                        q.splice(fieldPos, 1);
                    if (fieldPos_r > -1)
                        q.splice(fieldPos_r, 1);
                    q.unshift(field);
                    qr = q.join(":");
                } else {
                    qr = field + ":" + query.sort;
                }
            }
            el_app.setMainContent(document.location.pathname, preQuery + "sort=" + qr);
        });
    },

    /**
     * Инициализация поведения фильтров в заголовках таблиц
     *
     * @function filter_init
     * @memberof el_app
     */
    filter_init: function () {

        $(".filterer").off("click").on("click", function (e) {
            e.preventDefault();
            let listWrap = $(this).closest("th").find(".data_filter_select"),
                list = listWrap.find("input"),
                list_name = $(list[0]).attr("name").replace("[]", "");

            if (listWrap.css("display") === "none") {
                $(".table_data .data_filter_select").hide();
                listWrap.show();
                el_tools.setcookie("role_show_" + list_name, "open");

                /*$(document).off("click").on("click", function (e) {
                    let listWrap = $(".data_filter_select"),
                        $button = listWrap.closest("th.sort").find(".filterer");
                    if (!listWrap.is(e.target) && listWrap.has(e.target).length === 0
                        && !$button.is(e.target) && $button.has(e.target).length === 0) {
                        listWrap.hide();
                        el_tools.setcookie("role_show_" + list_name, "close", "31 Dec 2120 23:59:59 GMT");
                    }
                });*/
            } else {
                listWrap.hide();
                el_tools.setcookie("role_show_" + list_name, "close");
            }
        });

        $(".el_suggest").el_suggest();

        /*$(document).off("click").on("click", function (e) {
            let listWrap = $(".data_filter_select"),
                $button = listWrap.closest("th.sort").find(".filterer");
            if (!listWrap.is(e.target) && listWrap.has(e.target).length === 0
                && !$button.is(e.target) && $button.has(e.target).length === 0) {
                listWrap.hide();
            }
        });

        $(document).off("click").on("click", function (e) {
            if (!$el_suggest.closest(".el_data").is(e.target)
                && $el_suggest.closest(".el_data").has(e.target).length === 0) {
                Plugin.prototype.close.apply(that);
            }
        });*/

        if ($(".constant input[type=checkbox]").length > 0) {
            $(".constant input[type=checkbox]").off("click").on("click", function () {
                let field = $(this).attr("name").replace("filter_", "").replace("[]", ""),
                    query = el_tools.getUrlVar(document.location.href),
                    that = $(this),
                    ch_value = that.val(),
                    q = [],
                    paramExist = false,
                    rq = [];
                console.log(query)
                for (let param in query) {
                    if (param !== "sort" && param !== "filter")
                        rq.push(param + "=" + query[param]);
                }

                if (typeof query.sort !== "undefined") {
                    rq.push("sort=" + query.sort);
                }

                if (typeof query.filter !== "undefined") {
                    q = el_tools.array_unique(query.filter.split(";"));

                    $.each(q, function (f, v) {
                        if (typeof v != "undefined") {
                            let fArr = v.split(":");

                            if (fArr[0] === field) {

                                paramExist = true;

                                if (that.prop("checked")) {

                                    q[f] = field + ":" + fArr[1] + "|" + ch_value;

                                } else {

                                    let valArr = fArr[1].split("|");
                                    q.splice(q.indexOf(v), 1);

                                    const currValue = {value: ch_value};
                                    valArr = valArr.filter(function (item) {
                                        return item !== this.value;
                                    }, currValue);

                                    valArr = el_tools.array_clean(valArr);
                                    valArr = el_tools.array_unique(valArr);

                                    if (valArr.length > 0) {
                                        q.push(fArr[0] + ":" + valArr.join("|"));
                                    }

                                }
                            }
                        }
                    });
                    if (!paramExist) {
                        q.push(field + ":" + ch_value);
                    }
                } else {
                    q.push(field + ":" + $(this).val());
                }

                q = el_tools.array_clean(q);
                q = el_tools.array_unique(q);

                if (q.length > 0) {
                    rq.push("filter=" + q.join(";"));
                } else {
                    /*rq.splice(rq.indexOf("filter="), 1);*/
                    for (let param in query) {
                        if (param !== "sort" && param !== "filter")
                            rq.push(param + "=" + query[param]);
                    }

                    if (typeof query.sort !== "undefined") {
                        rq.push("sort=" + query.sort);
                    }
                }
                rq = el_tools.array_clean(rq);
                rq = el_tools.array_unique(rq);
                el_app.setMainContent(document.location.pathname, rq.join("&"));
            });
        }
    },

    cloneFilterer: function () {
        let $filters = $(".table_data th.sort .el_data, .table_data th.sort .constant");
        $("#filterer .group").html("");
        for (let i = 0; i < $filters.length; i++) {
            let label = $($filters[i]).closest("th").find(".sorter").text().replace("south", "");

            $("#filterer .group").append("<div class='item w_50 el_data'></div>");

            if ($($filters[i]).hasClass("el_data")) {
                //Autocomplete

                let $field = $($filters[i]).find("input"),
                    name = $field.attr("name"),
                    dataAttr = JSON.parse($field.attr("data-src"));

                $("#filterer .item.w_50:last").prepend("<select data-label='" + label + "' name='" + name + "' multiple>" +
                    "<option>Начните вводить...</option></select>");
                let $newSelect = $("#filterer .item.w_50:last select");
                $newSelect.chosen({
                    allow_single_deselect: true,
                    placeholder_text_multiple: "Начните вводить..."
                });

                let $autocomplete = $('#filterer .item.w_50:last .chosen-search-input');
                $autocomplete.autocomplete({
                    source: function (request, response) {
                        $.post("/", {
                                ajax: 1,
                                action: dataAttr.action,
                                source: dataAttr.source,
                                column: dataAttr.column,
                                value: dataAttr.value,
                                search: request.term
                            },
                            function (data) {
                                let answer = JSON.parse(data);
                                //$newSelect.empty();
                                $autocomplete.closest(".el_data").prev("select").empty();
                                $autocomplete.closest(".el_data").find(".chosen-results").empty();
                                response($.map(answer, function (item) {
                                    $newSelect.append('<option value="' + item.value + '">' + item.text + '</option>');
                                }));
                                $newSelect.trigger("chosen:updated");
                                $autocomplete.val(request.term).css("width", "100%");
                            });
                    }
                }).on("chosen:search_updated", function (search_term) {
                    //$autocomplete.val(search_term).css("width", "100%");
                });
            } else {
                //Select
                let $options = $($filters[i]).find(".el_option"),
                    optionsArr = [],
                    name = "";
                for (let o = 0; o < $options.length; o++) {
                    let $option = $($options[o]).find("input"),
                        value = $option.val();

                    name = $option.attr("name")
                    optionsArr.push("<option value='" + value + "'>" + $($options[o]).text() + "</option>");
                }

                $("#filterer .item.w_50:last").prepend("<select data-label='" + label + "' name='" + name + "' multiple>" +
                    optionsArr.join("\n") + "</select>");
                $("#filterer .item.w_50:last select").chosen();
            }
        }

        $('#filterer select').on('change', function (evt, params) {
            let fSelects = $('#filterer select'),
                query = el_tools.getUrlVar(document.location.href),
                rq = [],
                fValues = [];

            for (let param in query) {
                if (param !== "sort" && param !== "filter")
                    rq.push(param + "=" + query[param]);
            }

            if (typeof query.sort !== "undefined") {
                rq.push("sort=" + query.sort);
            }

            for (let i = 0; i < fSelects.length; i++) {
                let fName = $(fSelects[i]).attr("name").replace("filter_", "").replace("[]", ""),
                    fValue = $(fSelects[i]).val();
                if (fValue.length > 0) {
                    fValues.push(fName + ":" + fValue.join("|"));
                }
            }
            if (fValues.length > 0) {
                rq.push("filter=" + fValues.join(";"));
            }

            el_app.setMainContent(document.location.pathname, rq.join("&"));

            $("#filterer [type=reset]").off("click").on("click", function () {
                let rq = [];
                $("#filterer select").val("").trigger("chosen:updated");
                for (let param in query) {
                    if (param !== "sort" && param !== "filter")
                        rq.push(param + "=" + query[param]);
                }

                if (typeof query.sort !== "undefined") {
                    rq.push("sort=" + query.sort);
                }
                el_app.setMainContent(document.location.pathname, rq.join("&"));
            });
        });

        $("#filterer .close").off("click").on("click", function () {
            el_app.hideFilterer();
        });
    },

    showFilterer: function () {
        $("#filterer").addClass("show");
    },

    hideFilterer: function () {
        $("#filterer").removeClass("show");
    },

    showNotifications: function () {
        $("#notificationsList").addClass("show");
    },

    hideNotifications: function () {
        $("#notificationsList").removeClass("show");
    },

    /**
     * Метод вставляет в выпадающий список новый элемент и делает его выбранным
     *
     * @function insertSelectItem
     * @memberof el_app
     * @param selectName {string} Имя выпадающего списка
     * @param value {string} Значение нового элемента
     * @param text {string} Текст нового элемента
     * @example <caption>Пример использования:</caption>
     * el_app.insertSelectItem("cagent", "100", "Новый контрагент");
     */
    insertSelectItem: function (selectName, value, text) {
        $("select[name=" + selectName + "] option").removeAttr("selected");
        $("select[name=" + selectName + "]").prepend("<option value='" + value + "' selected>" + text + "</option>");
    },

    setUserSettings(name, value) {
        $.post("/", {ajax: 1, action: "setUserSettings", name: name, value: value}, function (data) {
            let answer = JSON.parse(data);
            if (answer.result) {
                inform('Отлично!', answer.resultText);
            } else {
                el_tools.notify('error', 'Ошибка', answer.resultText);
            }
        });
    },

    loginCountdown: function (seconds) {
        let remainingTime = seconds;

        function updateCountdown() {
            const minutes = Math.floor(remainingTime / 60);
            const secs = remainingTime % 60;
            $countdownElement = $('#login_countdown');

            $($countdownElement).text(`${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`);

            if (remainingTime <= 0) {
                clearInterval(interval);
                $($countdownElement).text("00:00");
                // Дополнительные действия после окончания отсчета
                el_tools.notify_close();
            } else {
                remainingTime--;
            }
        }

        const interval = setInterval(updateCountdown, 1000);
    },

    initTabs: function () {
        $(".tab-pane li").off("click").on("click", function () {
            $(this).closest(".tab-pane").find("li").removeClass("active");
            $(this).addClass("active");
            $(".tab-panel").hide();
            $("#" + $(this).attr("id") + "-panel").show();
        });
    },

    bindFindINN: function (inn) {
        let url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party";
        let token = "eb83a00ad060d6cca3d2341f2acb15cdb76b67df";
        let options = {
            method: "POST",
            mode: "cors",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": "Token " + token
            },
            body: JSON.stringify({query: inn})
        }

        fetch(url, options)
            .then(response => response.text())
            .then(result => console.log(result))
            .catch(error => console.log("error", error));
    },

    checklistInit: function () {
        let $bi = $(".checklist li[data-row-behaviour]");
        if ($bi.length > 0) {
            for (let i = 0; i < $bi.length; i++) {
                let behavior = $($bi[i]).data("row-behaviour"),
                    parentFieldId = parseInt(behavior.parentField);
                if (!behavior.visible) {
                    $($bi[i]).addClass("hidden").find("input, select, textarea").val("")
                        .attr("disabled", true).prop({"checked": false, "selected": false});
                }
                if (parentFieldId > 0) {
                    $(".checklist li[data-id=" + parentFieldId + "] input, " +
                        ".checklist li[data-id=" + parentFieldId + "] select," +
                        ".checklist li[data-id=" + parentFieldId + "] textarea").on("change input paste", function () {
                        if (behavior.valuesType === "1") {
                            $($bi[i]).removeClass("hidden").find("input, select, textarea").attr("disabled", false);
                        } else {
                            let isAnySelected = $('.checklist li[data-id=' + parentFieldId + '] select option:selected, ' +
                                '.checklist li[data-id=' + parentFieldId + '] input:checked, ' +
                                '.checklist li[data-id=' + parentFieldId + '] textarea').filter(function () {
                                return behavior.values.includes($(this).val());
                            }).length > 0;
                            if (isAnySelected) {
                                $($bi[i]).removeClass("hidden").find("input, select, textarea").attr("disabled", false);
                            } else {
                                $($bi[i]).addClass("hidden").find("input, select, textarea").val("")
                                    .attr("disabled", true).prop({"checked": false, "selected": false});
                            }
                        }
                    });
                }
            }
        }
    },

    bindGetUnitsByOrg: function () {
        $("select[name='institutions[]'], select[name='institution']").off("change").on("change", function () {
            let instId = $(this).val(),
                $units = $(this).closest(".group").find("select[name='units[]'], select[name='unit'], select[name='division']"),
                unitSelect = $(this).closest(".group").find("input[name='units_hidden[]'], input[name='units_hidden']").val();
            $units.html("").trigger("chosen:updated");
            $.post("/", {ajax: 1, action: "getUnitsByOrg", orgId: instId, selected: unitSelect}, function (data) {
                $units.html(data);
                setTimeout(function () {
                    $units.trigger("change").trigger("chosen:updated");
                }, 200);
            });
        });
    },

    binFullscreen: function () {
        $('#fullscreen').off("click").on('click', function (e) {
            if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) { /* Enter fullscreen */

                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen(); /* IE/Edge */
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen(); /* Firefox */
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen(); /* Chrome, Safari & Opera */
                }
                $(this).attr("title", "Перейти в полноэкранный режим");
                $(this).find(".material-icons").text("open_in_full");
            } else { /* exit fullscreen */
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.msRequestFullscreen) {
                    document.documentElement.msRequestFullscreen();
                }
                $(this).attr("title", "Выйти из полноэкранного режима");
                $(this).find(".material-icons").text("close_fullscreen");
            }
        });
    },

    getUserById: async function (id, mode = 'full') {
        try {
            return await $.ajax({
                url: "/",
                type: "POST",
                data: {
                    ajax: 1,
                    action: "getUserById",
                    user_id: id,
                    mode: mode
                }
            }); // Вернет данные пользователя
        } catch (error) {
            console.error("Ошибка запроса:", error);
            throw error; // Пробрасываем ошибку для обработки снаружи
        }
    },

    bindSetMinistriesByOrg: function (instanceObj) {
        let $inst = instanceObj.find("select[name='institutions[]']"),
            $ministries = instanceObj.find("select[name='ministries[]']"),
            $units = instanceObj.find("select[name='units[]']"),
            $users = instanceObj.find("select[name='users[]']"),
            selected_ministry = instanceObj.find("input[name='ministries_hidden[]']").val(),
            $add_agreement_message = instanceObj.find(".add_agreement_message");
        $inst.off("change").on("change", function () {
            $ministries.html("").trigger("chosen:updated");//.trigger("change");
            $units.html("").trigger("chosen:updated");//.trigger("change");
            $users.html("").trigger("chosen:updated");//.trigger("change");
            $add_agreement_message.text("");
            $.post("/", {
                ajax: 1,
                action: "getMinistriesByOrg",
                orgId: $inst.val(),
                selected: selected_ministry
            }, function (data) {
                $ministries.html(data).trigger("chosen:updated").trigger("change");
            })
        });
    },

    bindSetUnitsByOrg: function (instanceObj) {
        let $inst = instanceObj.find("select[name='institutions[]']"),
            $ministries = instanceObj.find("select[name='ministries[]']"),
            $units = instanceObj.find("select[name='units[]']"),
            $users = instanceObj.find("select[name='users[]'], select[name='executors[]']"),
            selected_unit = instanceObj.find("input[name='units_hidden[]']").val(),
            $add_agreement_message = instanceObj.find(".add_agreement_message");
        $ministries.off("change").on("change", function () {
            $units.html("").trigger("chosen:updated");//.trigger("change");
            $users.html("").trigger("chosen:updated");//.trigger("change");
            $add_agreement_message.text("");
            $.post("/", {
                    ajax: 1,
                    action: "getUnitsByOrg",
                    orgId: $inst.val(),
                    ministriesId: $ministries.val(),
                    selected: selected_unit
                },
                function (data) {
                    $units.html(data).trigger("chosen:updated").trigger("change");
                })
        });
    },

    // Функция показа уведомления с действиями
    showNotificationWithActions: function (title, body, icon, data = {}) {
        el_app.requestNotificationPermission().then((hasPermission) => {
            if (hasPermission) {
                if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                    // Отправляем сообщение в Service Worker
                    navigator.serviceWorker.controller.postMessage({
                        type: 'SHOW_NOTIFICATION',
                        title: title,
                        silent: false,
                        requireInteraction: true,
                        tag: 'monitoring',
                        options: {
                            body: body,
                            icon: icon,
                            url: data.url,
                            data: data // Передаем данные для обработки действий
                        }
                    });
                } else {
                    // Fallback: простое уведомление без действий
                    new Notification(title, {body, icon});
                }
            } else {
                $.toast({
                    text: body,
                    heading: title,
                    position: 'top-right',
                    showHideTransition: 'slide',
                    bgColor: '#263238',
                    textColor: '#fff',
                    loaderBg: '#78909c',
                    hideAfter: false,
                });
            }

        });
        if (notificationSound === "up" || notificationSound === "") {
            el_app.playSound("/images/sounds/deluxe_meloboom.mp3");
            el_app.attractAttention('Новое сообщение!', {
                blinkTitle: true,
                changeFavicon: true,
                playSound: false,
                duration: 10000
            });
        }
        el_app.updateNotifications();
    },

    handleNotificationAction: function (action, data) {
        switch (action) {
            case 'accept':
                console.log('Пользователь принял:', data);
                break;
            case 'reject':
                console.log('Пользователь отклонил:', data);
                break;
        }
    },

    // Запрос разрешения на уведомления
    requestNotificationPermission: async function () {
        try {
            const permission = await Notification.requestPermission();
            return permission === 'granted';
        } catch (error) {
            console.error('Permission error:', error);
            return false;
        }
    },

    // Инициализация push уведомлений
    initPushNotifications: async function () {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Push notifications not supported');
            return;
        }

        try {
            // Регистрируем Service Worker
            const registration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('Service Worker registered');

            // Запрашиваем разрешение
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.log('Notification permission denied');
                return;
            }

            // Подписываемся на push
            await el_app.subscribeToPush(registration);

        } catch (error) {
            console.error('Push initialization failed:', error);
        }
    },

    // Подписка на push уведомления
    subscribeToPush: async function (registration) {
        let pushSubscription;
        try {
            // Получаем публичный ключ с сервера
            const response = await fetch('/web-push/public-key.php');
            const {publicKey} = await response.json();

            // Конвертируем ключ
            const applicationServerKey = el_app.urlBase64ToUint8Array(publicKey);

            // Подписываемся
            pushSubscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            // Отправляем подписку на сервер
            await el_app.sendSubscriptionToServer(pushSubscription);

            console.log('Subscribed to push notifications');

        } catch (error) {
            console.error('Push subscription failed:', error);
        }
    },

    // Отправка подписки на сервер
    sendSubscriptionToServer: async function (subscription) {
        try {
            await fetch('/web-push/push.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    userId: userId,
                    subscription: subscription
                })
            });
        } catch (error) {
            console.error('Failed to send subscription:', error);
        }
    },

    // Вспомогательная функция
    urlBase64ToUint8Array: function (base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    },

    playSound: function (soundFile) {
        // Создаем элемент audio
        var audio = $('<audio>', {
            src: soundFile,
            volume: 1.0,
            preload: 'auto'
        });

        // Добавляем на страницу (скрыто)
        $('body').append(audio);

        // Воспроизводим звук
        audio[0].play().catch(function (error) {
            console.error('Ошибка воспроизведения:', error);
            el_app.playNotificationSound();
        });

        // Удаляем элемент после завершения воспроизведения
        audio.on('ended', function () {
            $(this).remove();
        });
    },

    playNotificationSound: function (type = 'default') {
        // Пытаемся использовать Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            // Разные звуки для разных типов
            const settings = {
                'default': {freq: 800, duration: 0.3},
                'important': {freq: 1200, duration: 0.5},
                'message': {freq: 600, duration: 0.2}
            };

            const {freq, duration} = settings[type] || settings.default;

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(freq, audioContext.currentTime);
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.start();
            oscillator.stop(audioContext.currentTime + duration);

        } catch (error) {
            // Fallback на HTML5 Audio
            console.warn('Web Audio not supported, using fallback');
            const audio = new Audio('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qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq//uSZPAP8AAAaQAAAAgAAA0gAAABAAABpAAAACAAADSAAAAEqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqg==');
            audio.volume = 0.3;
            audio.play();
        }
    },

    /**
     * Привлекает внимание к вкладке браузера
     * @param {string} message - Текст уведомления (для title)
     * @param {Object} options - Настройки эффектов
     */
    attractAttention: function (message, options = {}) {
        const defaults = {
            blinkTitle: true,      // Мигание заголовка
            changeFavicon: true,   // Изменение favicon
            playSound: false,      // Воспроизведение звука
            soundFile: 'notification.mp3', // Файл звука
            vibration: false,      // Вибрация (для мобильных)
            duration: 5000,        // Длительность эффекта (мс)
            blinkInterval: 1000,   // Интервал мигания (мс)
            attentionText: '❗ '    // Текст для привлечения внимания
        };

        const settings = $.extend({}, defaults, options);
        let originalTitle = document.title;
        let originalFavicon = $('link[rel="icon"]').attr('href') || '/favicon.ico';
        let blinkInterval;
        let attentionInterval;

        // Останавливаем предыдущие эффекты
        stopAttentionEffects();

        // Эффект мигания заголовка
        if (settings.blinkTitle) {
            let isOriginalTitle = true;
            blinkInterval = setInterval(() => {
                document.title = isOriginalTitle ?
                    settings.attentionText + message :
                    originalTitle;
                isOriginalTitle = !isOriginalTitle;
            }, settings.blinkInterval);
        }

        // Эффект изменения favicon
        if (settings.changeFavicon) {
            const favicon = $('link[rel="icon"]');
            const originalSize = favicon.attr('sizes') || '32x32';
            let isOriginalFavicon = true;

            // Создаем canvas для генерации цветного favicon
            const canvas = document.createElement('canvas');
            canvas.width = 32;
            canvas.height = 32;
            const ctx = canvas.getContext('2d');

            attentionInterval = setInterval(() => {
                if (isOriginalFavicon) {
                    // Рисуем красный кружок
                    ctx.fillStyle = '#ff4444';
                    ctx.beginPath();
                    ctx.arc(16, 16, 14, 0, 2 * Math.PI);
                    ctx.fill();

                    const newFavicon = canvas.toDataURL('image/png');
                    favicon.attr('href', newFavicon);
                } else {
                    favicon.attr('href', originalFavicon);
                }
                isOriginalFavicon = !isOriginalFavicon;
            }, settings.blinkInterval);
        }

        // Воспроизведение звука
        if (settings.playSound && settings.soundFile) {
            playNotificationSound(settings.soundFile);
        }

        // Вибрация (для поддерживающих устройств)
        if (settings.vibration && 'vibrate' in navigator) {
            navigator.vibrate([200, 100, 200]);
        }

        // Фокусируем окно (работает только если пользователь уже взаимодействовал с сайтом)
        try {
            window.focus();
        } catch (e) {
            console.log('Не удалось сфокусировать окно');
        }

        // Автоматическое прекращение эффектов через указанное время
        setTimeout(() => {
            stopAttentionEffects();
        }, settings.duration);

        // Функция остановки эффектов
        function stopAttentionEffects() {
            clearInterval(blinkInterval);
            clearInterval(attentionInterval);
            document.title = originalTitle;
            $('link[rel="icon"]').attr('href', originalFavicon);
        }

        // Возвращаем функцию для ручной остановки
        return {
            stop: stopAttentionEffects,
            originalTitle: originalTitle,
            originalFavicon: originalFavicon
        };
    },

};

/**
 * При загрузке страницы вызываем методы инициализации
 *
 * @event onDocumentLoad
 */
$(document).ready(function () {

    // Очистка кэша Cache API
    caches.keys().then(cacheNames => {
        return Promise.all(
            cacheNames.map(cacheName => caches.delete(cacheName))
        );
    });

    //el_tools.initForms();
    el_app.mainInit();

    $("input[name=inn]").on("keyup", function () {
        let val = $(this).val();
        if (val.length > 9) {
            el_app.bindFindINN(val);
        }
    });


    $('a[href="#"]').on('click', async function (e) {
        e.preventDefault();
        await alert('Раздел еще не создан');
    });

    $("#wide_control").on("click", function () {
        let $content = $(".content"),
            $left = $("#wide_control .left"),
            $right = $("#wide_control .right"),
            $titles = $(".content .main_nav .title"),
            $items = $(".content .main_nav .item");

        if ($content.hasClass("wide")) {
            $content.removeClass("wide");
            $right.hide();
            $left.show();
            setTimeout(function () {
                $titles.fadeIn();
            }, 200);
            $items.attr("data-tipsy-disabled", "");
            el_tools.setcookie("widthPage", "thin");
        } else {
            console.log("wide")
            $titles.hide();
            $content.addClass("wide");
            $right.show();
            $left.hide();
            $items.removeAttr("data-tipsy-disabled");
            el_tools.setcookie("widthPage", "wide");
        }
    });

    /**
     * Реакция на нажатие клавиши Ctrl + N - новая запись
     * @event onkeydown
     */
    el_tools.controlPlusKey('N', function () {
        $("#button_nav_create").click();
    });

    /**
     * Реакция на нажатие клавиши Ctrl + C - клонирование
     * @event onkeydown
     */
    el_tools.controlPlusKey('C', function () {
        $("#button_nav_clone").click();
    });

    /**
     * Реакция на нажатие клавиши Del - удаление
     * @event onkeydown
     */
    $(document).on("keydown", function (e) {
        if (e.which === 46) {
            e.preventDefault();
            $("#button_nav_delete").click();
        }
        if (e.which === 45) {
            e.preventDefault();
            $("#button_nav_create").click();
        }
    });


    /**
     * @name ajaxSetup
     * @function
     * @desc
     * * Задаем дефолтные настройки для AJAX.
     * * Навешиваем ссылкам обработчик загрузки через AJAX.
     * * Управляем URL при загрузке нового контента
     */
    if (el_tools.ajaxFunction()) {
        $.ajaxSetup({
            url: '/',
            type: 'POST',
            //dataType: 'json',
            cache: false,
            headers: {
                'X-Csrf-Token': el_tools.getcookie("CSRF-TOKEN"),
                'X-Requested-With': "XMLHttpRequest"
            },
            beforeSend: function () {
                if (loader_active)
                    el_app.loader_show();
            },
            complete: function () {
                if (loader_active)
                    el_app.loader_hide();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                if (textStatus === "timeout") {
                    alert("Утеряно подключение к серверу!");
                }
                console.log('Ошибка: ' + textStatus + ' | ' + errorThrown);
            }
        });

        $(".main_nav a:not(.external)").off("click").on("click", async function (e) {
            let link = $(this).attr("href");
            if (!$(this).hasClass("parent_item")) {
                if (link !== '' && link !== '/' && link !== '#') {
                    e.preventDefault();
                    $(".main_nav a").removeClass("active");
                    $(this).addClass("active");
                    el_app.setMainContent(link);
                    return false;
                } else {
                    await alert('Раздел ещё не создан');
                }
            }
        });

        $(document).on("content_load", function (event, params) {
            $(".main_nav a").removeClass("active");
            $(".main_nav a[href='" + params.url + "']").addClass("active");
        })

        $(".main_nav .parent-item").off("click").on("click", function (e) {
            e.preventDefault();
            let $settings = $(this).closest(".settings-group");
            $settings.toggleClass("expanded");
            el_tools.setcookie("settings_show", $settings.hasClass("expanded"));
        });

        window.addEventListener('popstate', function (e) {
            var url = document.location.href.replace(document.location.protocol + '//' + document.location.host, ""),
                query = document.location.search,
                params = undefined,
                urlArr = url.split("?");
            if (query.length > 0) {
                params = query;
            }
            //el_app.setMainContent(urlArr[0], urlArr[1]);
            $.when(el_app.loadContent(urlArr[0], "mainpage", urlArr[1])).then(function (answer) {

                let regexp = /<div class="title">([^<]+)<\/div>/g,
                    title = regexp.exec(answer);
                if (title != null) {
                    document.title = "CallCRM - " + title[1];
                    $(".main_data").html(answer);
                    $("body,html").animate({"scrollTop": 0}, "slow");
                    el_app.mainInit();
                }
            })
        });
    }

    $("#notificationSound").off("click").on("click", function () {
        let icon = $(this).text().replaceAll("volume_", ""),
            newIcon = icon === "up" ? "off" : "up",
            newTitle = icon === "up" ? "Звук уведомлений выключен" : "Звук уведомлений включен"
        el_app.setUserSettings("notificationSound", newIcon);
        $(this).text("volume_" + newIcon).attr("title", newTitle);
        notificationSound = newIcon;
    });

    $("#notificationDelete").off("click").on("click", function () {
        $("#notificationsList .deleteNotification").trigger("click");
        $("#notificationHeader .close").trigger("click");
    });


    const ws = new WebSocket('wss://monitoring.msr.mosreg.ru/websocket');

    ws.onopen = function () {
        console.log('Connected to WebSocket server');
        clearInterval(pingTimer);
        pingTimer = setInterval(ping, 30000);
    };

    ws.onmessage = function (event) {
        let d = JSON.parse(event.data);//console.log(d);
        if (d.event !== "ping" && parseInt(d.userId) === parseInt(userId)) {
            el_app.showNotificationWithActions(d.title, d.body, '/favicons/favicon-96x96.png', {url: d.url});

        }
        //console.log(d);
    };

    ws.onclose = function () {
        console.log('Disconnected from WebSocket server');
    };

    ws.onerror = function (error) {
        console.error('WebSocket error:', error);
    };

    $(document).off('content_unload').on('beforeunload content_unload', {socket: ws}, function (event) {
        console.log('Disconnect from WebSocket server');
        ws.close();
    });

    function ping() {
        let obj = {
            'event': 'ping'
        };
        ws.send(JSON.stringify(obj));
    }


    // Регистрация Service Worker
    /*if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js')
            .then((registration) => {
                console.log('SW registered: ', registration);
            })
            .catch((error) => {
                console.log('SW registration failed: ', error);
            });
    }*/

    // Обработка ответов от действий уведомления
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data.type === 'NOTIFICATION_ACTION') {
            const action = event.data.action;
            const data = event.data.data;

            // Отправляем действие на сервер через WebSocket
            if (ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'notification_action',
                    action: action,
                    data: data
                }));
            }

            // Локальная обработка
            el_app.handleNotificationAction(action, data);
        }
    });

    el_app.requestNotificationPermission().then((hasPermission) => {
        if (hasPermission) {
            console.log('Уведомления разрешены!');
        } else {
            console.log('Уведомления запрещены или отклонены');
        }
    });
});

document.addEventListener('DOMContentLoaded', async () => {
    await el_app.initPushNotifications();
});

/**
 * При выгрузке страницы деактивируем прелоудер
 *
 * @event onDocumentUnLoad
 * @global
 */
$(window).on("beforeunload", function () {
    el_app.loader_show();
})
$(document).ready(function(){
    el_app.mainInit();
    el_registry.create_init();
});

var check_number = 1;
var newRegistryData = [];
var institutions_counter = 1;
var staffs_counter = 1;
var calendars = {};
var quarters = {};

var el_registry = {
    //Инициализация контролов в разделе "Задания"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            el_app.dialog_open("assign_staff", {}, "calendar");
        });

        $("#button_nav_delete").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Содержимое справчников будет так же удалено. Уверены, что хотите удалить эти справочники?");
                if (ok) {
                    $("form#registry_delete").trigger("submit");
                }
            }
        });
        $("#button_nav_clone").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                $("form#user_delete").attr("id", "user_clone").trigger("submit").attr("id", "user_delete");
            }
        });
        $("#button_nav_list_props").on("click", function (){
            document.location.href = "/registry/props";
        });
        $("#button_nav_plans").on("click", function (){
            document.location.href = "/plans";
        });

        $(".viewDoc").off("click").on("click", function (){
            let docId = $(this).data("value") || $(this).data("id");
            /*$.post("/", {ajax: 1, action: "pdf", url: "pdf", docId: docId}, function (data){
                console.log(data)
            });*/
            el_app.dialog_open("planPdf", docId, "plans");
        });
        $("#button_nav_registry").on("click", function (){
            document.location.href = "/registry";
        });

        $(".custom_checkbox input#in_menu").on("change", function (){
                $(".tm-icon-picker, .short_name").css("visibility", ($(".custom_checkbox input#in_menu").prop("checked") ? "visible" : "hidden"));
        });

        $("[name=field_types]").on("change", function (){
            let val = $(this).val(),
                $tf = $("#minmax"),
                $sf = $("#sizefield"),
                $ar = $("#area"),
                $op = $("#oplist"),
                $ch = $("#check"),
                $ra = $("#radio"),
                $df = $("#default_value"),
                $dd = $("#default_date"),
                $dt = $("#default_time"),
                $ddt = $("#default_datetime"),
                $ct = $("#calendar_type"),
                $db = $("#fromdb"),
                $dbs = $("#fromdb .chosen-container");

            $(".field_option").hide();

            switch (val) {
                case "text":
                    $sf.css("display", "flex");
                    $df.show();
                    break;
                case "textarea":
                    $ar.css("display", "flex");
                    $df.show();
                    break;
                case "integer":
                case "float":
                    $tf.css("display", "flex");
                    $df.show();
                    break;
                case "radio":
                    $ra.show();
                    break;
                case "checkbox":
                    $ch.show();
                    break;
                case "select":
                case "multiselect":
                    $op.show();
                    break;
                case "list_fromdb":
                    $db.css("display", "flex");
                    $dbs.show();
                    break;
                case "calendar":
                    $dd.show();
                    $ct.show();
                    break;
                case "time":
                    $dt.show();
                    break;
                case "datetime":
                    $ddt.show();
                    $ct.show();
                    break;
                case "multi_date":
                case "range_date":
                    $ct.show();
                    break;

            }
        });

        $(".new_check").on("click", function (e){
            e.preventDefault();
            $(".pop_up_body .check_button:last").clone().insertAfter(".pop_up_body .check_button:last");
            $(".pop_up_body .check_button:last input").val("");
            el_registry.setCheckNumber();

            check_number++;
            //$(".check_number").last().text("Кнопка №" + check_number);
            if(check_number > 1){
                $(".check_number").last().after('<div class="button icon close"><span class="material-icons">close</span></div>');
                $(".check_button .close").off("click").on("click", function (){
                    check_number--;
                    $(this).closest(".check_button").remove();
                    el_registry.setCheckNumber();
                });
            }
        });

        $(".new_option").on("click", function (e){
            e.preventDefault();
            $(".pop_up_body .option_button:last").clone().insertAfter(".pop_up_body .option_button:last");
            $(".pop_up_body .option_button:last input").val("");
            el_registry.setOptionNumber();

            option_number++;
            if(option_number > 1){
                $(".option_number").last().after('<div class="button icon close"><span class="material-icons">close</span></div>');
                $(".option_button .close").off("click").on("click", function (){
                    option_number--;
                    $(this).closest(".option_button").remove();
                    el_registry.setOptionNumber();
                });
            }
        });

        $("#add_props").on("click", function (e){
            e.preventDefault();
            let $selected = $("#all_props_list input:checked");
            $selected.closest(".item").appendTo("#reg_props_list");
            $("#reg_props_list input:checked").prop("checked", false);
            $("#reg_props_list input[type=hidden]").attr("name", "prop[]");
            el_registry.getPropsInRegistry();
        });
        $("#remove_props").on("click", function (e){
            e.preventDefault();
            let $selected = $("#reg_props_list input:checked");
            $selected.closest(".item").appendTo("#all_props_list")
                .find(".rename, .rename_done, .drag_handler, .required, .unique").remove();
            $("#all_props_list input:checked").prop("checked", false);
            $("#all_props_list input[type=hidden]").attr("name", "props[]");
            el_registry.getPropsInRegistry();
        });
        $("#addProps").off("click").on("click", function () {
            el_app.dialog_open("prop_create", "", "registry/props");
        });

        $(".link a").off("click").on("click", async function (e) {
            let link = $(this).attr("href");
            if (link !== '' && link !== '/' && link !== '#') {
                e.preventDefault();
                let linkArr = link.split('/?');
                el_app.setMainContent('/plans', linkArr[1]);
                return false;
            }else{
                await alert('Раздел ещё не создан');
            }
        });

        $("#registry_list select").off("change").on("change", function(){
            let params = (parseInt($(this).val()) > 0) ? "id=" + parseInt($(this).val()) : "";
            el_app.setMainContent('/registry', params);
        });

        $("#user_list select").on("change", function(){
            let params = (parseInt($(this).val()) > 0) ? "id=" + parseInt($(this).val()) : "";
            el_app.setMainContent('/registry', params);
        });

        $(document).on("content_load", function (){
            el_registry.getAllPropsInCreateRegistry();
        });

        $("select[name=fromdb]").on("change", function (){
            el_registry.showFieldsFromDB($(this).val(), $("input[name=selected_field]").val());
        });

        $("[in_menu]").on("change", function (){
            $(document).trigger("set_in_menu");
        });

        $("input[name=table_name]").on("input paste blur", function (){
            let $input = $(this);
            if ($input.val().length > 60) {
                $input.val($input.val().substring(0, 60));
            }
            el_tools.validateLowercaseAlphanumeric(this);
        }).on("keypress", function (e){
            const char = String.fromCharCode(e.which);
            return /[a-z0-9]/.test(char);
        });

        $("#registry_create input[name=reg_name]").on("blur", function (){
            el_tools.translateWithGoogle($(this).val(), "regprops").
            then(r => $("input[name=table_name]").val(r)) ;
        });
        $("#prop_create input[name=prop_name]").on("blur", function (){
            el_tools.translateWithGoogle($(this).val(), "regprops").
            then(r => $("input[name=field_name]").val(r)) ;
        });

        $(".search_props input").on("keyup", function() {
            let $that = $(this),
                value = this.value.toLowerCase().trim(),
                $search_clear = $that.closest(".search_props").find(".search_clear"),
                $search_zoom = $that.closest(".search_props").find(".search_zoom");
            if (value.length > 1){
                $search_clear.removeClass("hidden")
                    .off("click").on("click", function (e){
                    e.preventDefault();
                    $that.val("").trigger("keyup");
                });
                $search_zoom.addClass("hidden");
            }else{
                $search_clear.addClass("hidden");
                $search_zoom.removeClass("hidden");
            }
            $that.closest("ol").find("li").show().filter(function() {
                return $(this).text().toLowerCase().trim().indexOf(value) == -1;
            }).hide();
        });

        $(".pop_up_body .new_institution").off("click").on("click", function(e){
            e.preventDefault();
            el_registry.cloneInstitution();
        });

        $(".pop_up_body .new_staff").off("click").on("click", function(e){
            e.preventDefault();
            el_registry.cloneStaff();
        });

        $(".institutions .clear").off("click").on("click", function (){
            $(this).closest(".institutions").remove();

            let $institutions = $(".pop_up_body .institutions");
            for (let i = 0; i < $institutions.length; i++){
                $($institutions[i]).find(".question_number").text("Учреждение №" + (i+ 1));
            }
        });

        $("select[name=planname]").on("change", function (){
            $.post("/", {ajax: 1, action: "getLongNamePlan", id: $(this).val()}, function (data){
                $("textarea[name=longname]").val(data);
                tinymce.activeEditor.setContent(data);
            });
        });


        $(".pop_up_body .institutions:last input[name='check_periods[]']")
            .flatpickr({
                defaultDate: el_registry.getPrevPeriod(),
                locale: 'ru',
                mode: 'range',
                time_24hr: true,
                dateFormat: 'Y-m-d',
                altFormat: 'd.m.Y',
                conjunction: '-',
                altInput: true,
                altInputClass: "el_input",
                firstDayOfWeek: 1,
            });

        el_registry.bindSetOrgByType();
        el_registry.bindPassword();
        el_registry.bindLoginAlias();
        el_registry.getPropsInRegistry();
        el_registry.bindDadata();
        el_app.sort_init();
        el_app.filter_init();
    },

    getPrevPeriod: function (){
        const now = new Date();
        const lastYear = now.getFullYear() - 1;

        const startStr = `${lastYear}-01-01`;
        const endStr = `${lastYear}-12-31`;

        return `${startStr} - ${endStr}`;
    },

    showFieldsFromDB: function (regId, selected){
        $.post("/", {ajax: 1, action: "getFieldsFromDB", reg_id: regId, selected: selected}, function (data){
            $("select[name=fromdb_fields]").html(data).trigger("chosen:updated");
        })
    },

    getAllPropsInCreateRegistry: function (){
        $.post("/", {ajax: 1, action: "getAllProps"}, function (data){
            $("#all_props_list").html(data);
        });
    },

    setCheckNumber: function (){
        let $check_numbers = $(".check_number");
        for (let i = 1; i < $check_numbers.length; i++){
            $($check_numbers[i]).text("Кнопка №" + (i + 1));
        }
    },

    getPropsInRegistry: function (){
        //получить итоговый список полей, добавить к каждому пункту иконки редактроования и перетаскивания,
        // и получившийся список внести в переменную для сохранения
        let $items = $("#reg_props_list .item");
        $items.find(".rename, .rename_done, .drag_handler, .required, .unique").remove();
        for (let i = 0; i < $items.length; i++){
            $($items[i]).append('<span class="material-icons required" title="Обязательное поле">' +
                ($($items[i]).data("required") === 1 ? 'check_circle' : 'panorama_fish_eye') + '</span>' +
                '<span class="material-icons unique" title="Уникальное поле">' +
                ($($items[i]).data("unique") === 1 ? 'check_circle' : 'panorama_fish_eye') + '</span>' +
                '<span class="material-icons rename" title="Переименовать поле">drive_file_rename_outline</span>\n' +
                '<span class="material-icons rename_done" title="Переименовать поле">done</span>\n' +
                '    <span class="material-icons drag_handler" title="Переместить">drag_handle</span>');
        }
        el_registry.setNewRegistryData();

        $items.find(".rename").off("click").on("click", function (){
            let $label = $(this).closest(".item").find(".fieldName"),
                labelText = $label.text();
            $label.html("<input type='text' name='fieldName' value='"+labelText+"'>");
            $(this).hide();
            $(this).closest(".item").find(".rename_done").show()
                .off("click").on("click", function (){
                    let $input = $(this).closest(".item").find("input[name=fieldName]"),
                    newVal = $input.val();
                    $input.remove();
                $(this).closest(".item").find(".fieldName").text(newVal);
                $(this).hide();
                $(this).closest(".item").find(".rename").show();
                el_registry.setNewRegistryData();
            });
        });

        $items.find(".required, .unique").off("click").on("click", function (){
            let icon = $(this).text(),
                $item = $(this).closest(".item"),
                action = $(this).attr("class").replace("material-icons ", "");
            if (icon === "panorama_fish_eye") {
                $(this).text("check_circle");
                $item.attr("data-" + action, 1);
                el_registry.setNewRegistryData();
            }else{
                $(this).text("panorama_fish_eye");
                $item.attr("data-" + action, 0);
                el_registry.setNewRegistryData();
            }
        });

        $('#reg_props_list span[title]').tipsy({
            arrowWidth: 10,
            cls: null,
            duration: 150,
            offset: 16,
            position: 'right'
        });

        $("#reg_props_list").nestedSortable({
            axis: "y",
            cursor: "grabbing",
            listType: "ol",
            handle: ".drag_handler",
            items: "li",
            stop: function (event, ui) {
                el_registry.setNewRegistryData();
            }
        });
    },

    bindDadata: function (){
        $("input[name=inn]").suggestions({
            token: "eb83a00ad060d6cca3d2341f2acb15cdb76b67df",
            type: "PARTY",
            /* Вызывается, когда пользователь выбирает одну из подсказок */
            onSelect: function(suggestion) {
                console.log(suggestion);
                $("input[name=name]").val(suggestion.data.name.full_with_opf);
                $("input[name=short]").val(suggestion.data.name.short_with_opf);
                $("input[name=inn]").val(suggestion.data.inn);
                $("input[name=kpp]").val(suggestion.data.kpp);
                $("textarea[name=legal]").val(suggestion.data.address.unrestricted_value);
                $("textarea[name=email]").val(suggestion.data.emails)
                $("select[name=orgtype] option").filter(function() {
                    return $(this).text() === suggestion.data.opf.full;
                    //return $(this).text().includes(suggestion.data.opf.full);
                }).prop('selected', true);
                $("select[name=orgtype]").trigger('chosen:updated');
                "Общество с ограниченной ответственностью"
            }
        });
    },

    bindPassword(){
        $("input[name=password]").closest(".item").find(".button").off("click").on("click", function(){
            let new_pass = el_tools.genPass(8),
                $input = $(this).closest(".item").find(".el_input");
            el_tools.copyStringToClipboard(new_pass);
            $input.val(new_pass).attr("title", "Пароль скопирован в буфер обмена");
            $input.tipsy({
                arrowWidth: 10,
                duration: 150,
                offset: 16,
                position: 'top-center'
            }).trigger("mouseover");
        });
    },

    bindLoginAlias(){
        $("input[name='surname'], input[name='name']").on("input", function(){
            let name = $("input[name='name']").val(),
                surname = $("input[name='surname']").val(),
                login = el_tools.translit(name.charAt(0) + surname);

            $("input[name='login']").val(login);
        });
    },

    setNewRegistryData: function (){
        let $items = $("#reg_props_list .item");
        newRegistryData = [];
        if ($items.length > 0) {
            for (let i = 0; i < $items.length; i++) {
                newRegistryData.push({
                    label: $.trim($($items[i]).find("label").text()),
                    value: $($items[i]).find("input").val(),
                    required: $($items[i]).find(".required").text() == "check_circle",
                    unique: $($items[i]).find(".unique").text() == "check_circle",
                    sort: i
                });
            }
            $.post("/", {ajax: 1, action: "buildForm", props: newRegistryData}, function (data){
                $("#tab_form-panel").html(data);
                $("select:not(.viewmode-select)").chosen();
                $("#tab_form-panel [required]").removeAttr("required");
                $(".pop_up_body .new_institution").off("click").on("click", function(e){
                    e.preventDefault();
                    el_registry.cloneInstitution();
                });
                $("input[type=tel]").mask('+7 (999) 999-99-99');
                el_registry.bindDadata();
                el_registry.bindSetOrgByType();
                el_registry.bindPassword();
                el_registry.bindLoginAlias();
                el_registry.bindTipsy();
                el_registry.bindCalendar();

            });

            $("[name=reg_prop]").val(JSON.stringify(newRegistryData));
        }else{
            $("[name=reg_prop]").val("");
        }
    },

    bindTipsy(){
        $('#tab_form-panel [title]').tipsy({
            arrowWidth: 10,
            cls: null,
            duration: 150,
            offset: 16,
            position: 'right'
        });
    },

    bindCalendar(minDate, maxDate){
        let cal = $("[type=date]:not(.single_date)").flatpickr({
                locale: 'ru',
                mode: 'range',
                time_24hr: true,
                dateFormat: 'Y-m-d',
                altFormat: 'd.m.Y',
                conjunction: '-',
                altInput: true,
                allowInput: true,
                defaultDate: "",
                minDate: minDate,
                maxDate: maxDate,
                altInputClass: "el_input",
                firstDayOfWeek: 1,
            }),
            cal_single = $("[type=date].single_date").flatpickr({
                locale: 'ru',
                mode: 'single',
                time_24hr: true,
                dateFormat: 'Y-m-d',
                altFormat: 'd.m.Y',
                altInput: true,
                allowInput: true,
                defaultDate: "",
                minDate: minDate,
                maxDate: maxDate,
                altInputClass: "el_input",
                firstDayOfWeek: 1,
            });
        if (typeof el_app.calendars.popup_calendar != "undefined" && "push" in el_app.calendars.popup_calendar) {
            el_app.calendars.popup_calendar.push(cal);
            el_app.calendars.popup_calendar.push(cal_single);
        }
    },

    cloneInstitution: function(){
        let current_check = $(".pop_up_body select[name='check_types[]']").val(),
        check_type = $(".pop_up_body select[name='inspections[]']").val(),
        check_period = $(".pop_up_body input[name='check_periods[]']").val();

        $(".pop_up_body .institutions select").chosen("destroy");
        $(".pop_up_body .institutions:last").clone().insertAfter(".pop_up_body .institutions:last");
        $(".pop_up_body .new_institution").off("click").on("click", function(e){
            e.preventDefault();
            el_registry.cloneInstitution();
        });
        $(".pop_up_body .institutions select").chosen({
            search_contains: true,
            no_results_text: "Ничего не найдено."
        });
        $(".pop_up_body .institutions:last .quarterWrapper")
            .removeClass("open").find("b, .ui.label").removeClass("selected");
        $(".pop_up_body .institutions:last input").val("");

        $(".pop_up_body .institutions:last select[name='institutions[]']").empty().trigger("chosen:updated");
        el_registry.bindSetOrgByType();
        $(".pop_up_body .institutions:last select[name='check_types[]']").val(current_check)
            .trigger("chosen:updated").trigger("change");
        $(".pop_up_body .institutions:last select[name='inspections[]']").val(check_type)
            .trigger("chosen:updated");


        $(".pop_up_body .institutions:last [name='check_periods[]']").
        removeClass("flatpickr-input").attr("type", "date").next("input").remove();
        $(".pop_up_body .institutions:last input[name='check_periods[]']")
            .flatpickr({
                defaultDate:check_period,
                locale: 'ru',
                mode: 'range',
                time_24hr: true,
                dateFormat: 'Y-m-d',
                altFormat: 'd.m.Y',
                conjunction: '-',
                altInput: true,
                altInputClass: "el_input",
                firstDayOfWeek: 1,
            });
        institutions_counter++;
        if(institutions_counter > 1){
            $(".question_number").last().after('<div class="button icon clear"><span class="material-icons">close</span></div>');
            $(".institutions .clear").off("click").on("click", function (){
                $(this).closest(".institutions").remove();
                el_registry.setItemsNumbers($(".pop_up_body .institutions"), "Учреждение");
                institutions_counter--;
            });
        }

        /*let $institutions = $(".pop_up_body .institutions");
        for (let i = 0; i < $institutions.length; i++){
            $($institutions[i]).find(".question_number").text("Учреждение №" + (i+ 1));
        }*/
        el_registry.setItemsNumbers($(".pop_up_body .institutions"), "Учреждение");
        el_registry.bindTipsy();
        quarter.bindQuarter("#" + $(".pop_up_body .institutions:last .quarter_select").attr("id"));
        el_registry.bindCalendar();
        el_registry.scrollToLastInstitution();
    },

    cloneStaff: function(){
        let current_unit = $(".pop_up_body select[name='units[]']").val();

        $(".pop_up_body .staff select").chosen("destroy");
        $(".pop_up_body .staff:last").clone().insertAfter(".pop_up_body .staff:last");
        $(".pop_up_body .new_staff").off("click").on("click", function(e){
            e.preventDefault();
            el_registry.cloneStaff();
        });
        $(".pop_up_body .staff select").chosen({
            search_contains: true,
            no_results_text: "Ничего не найдено."
        });
        $(".pop_up_body .staff:last .quarterWrapper")
            .removeClass("open").find("b, .ui.label").removeClass("selected");
        $(".pop_up_body .staff:last input").val("");

        $(".pop_up_body .staff:last select[name='users[]']").val("").trigger("chosen:updated");
        //el_registry.bindSetOrgByType();
        $(".pop_up_body .staff:last select[name='units[]']").val(current_unit)
            .trigger("chosen:updated").trigger("change");

        $(".pop_up_body .staff:last [name='dates[]']").
        removeClass("flatpickr-input").attr("type", "date").next("input").remove();

        staffs_counter++;
        if(staffs_counter > 1){
            $(".question_number").last().after('<div class="button icon clear"><span class="material-icons">close</span></div>');
            $(".pop_up_body .staff:last [name='is_head[" + staffs_counter + "]'").prop("checked", false);
            $(".staff .clear").off("click").on("click", function (){
                $(this).closest(".staff").remove();
                el_registry.setItemsNumbers($(".pop_up_body .staff"), "Учреждение");
                staffs_counter--;
            });
        }

        /*let $staffs = $(".pop_up_body .staff");
        for (let i = 0; i < $staffs.length; i++){
            $($staffs[i]).find(".question_number").text("Сотрудник №" + (i+ 1));
        }*/
        el_registry.setItemsNumbers($(".pop_up_body .staff"), "Сотрудник");
        $(".pop_up_body .staff:last [name='is_head[" + staffs_counter + "]'").prop("checked", false);
        el_registry.bindTipsy();
        el_registry.bindCalendar();
        el_registry.scrollToLastInstitution();
    },

    setItemsNumbers: function (objects, title){
        for (let i = 0; i < objects.length; i++){
            $(objects[i]).find(".question_number").text(title + " №" + (i+ 1));
        }
    },

    scrollToLastInstitution: function(){
        $(".pop_up").animate({
            scrollTop: $(".pop_up").scrollTop() + 100000
        }, 700);
    },

    bindSetOrgByType: function (){

        $("select[name='check_types[]']").off("change").on("change", function (){
            let $parent = $(this).closest(".institutions"),
                $inst = $parent.find("select[name='institutions[]']"),
                $instSelected = $parent.find("input[name='institutions_hidden']");
            $inst.html("").trigger("chosen:updated");
            $.post("/", {ajax: 1, action: "getOrgByCheckType", check_type: $(this).val(), selected: $instSelected.val()},
                function (data){
                    $inst.html(data).trigger("chosen:updated");
                });
        });
    }
};
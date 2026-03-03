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
    check_institutions: 0,
    just_opened: false,
    //Инициализация контролов в разделе "Планы проверок"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            el_app.dialog_open("registry_create");
        });

        $("#button_nav_delete").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Содержимое планов будет так же удалено. Уверены, что хотите удалить эти планы?");
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
            //document.location.href = "/registry/props";
            el_app.setMainContent('/registry/props');
        });
        $("#button_nav_plans").on("click", function (){
            //document.location.href = "/plans";
            el_app.setMainContent('/plans');
        });

        $(".viewDoc").off("click").on("click", function (){
            let docId = $(this).data("id") || $(this).data("value");
            /*$.post("/", {ajax: 1, action: "pdf", url: "pdf", docId: docId}, function (data){
                console.log(data)
            });*/
            el_app.dialog_open("planPdf", {docId: docId, docType: 3}, "plans");
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

        /*$(document).on("content_load", function (){
            el_registry.getAllPropsInCreateRegistry();
        });*/

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

        /*$("select[name=year]").on("change", function (){
            let year = $(this).val(),
                //$textarea = $("textarea[name=longname]"),
                longName = tinymce.activeEditor.getContent("textarea[name=longname]");//$textarea.val();
            if (longName.length > 0) {
                longName = longName.replace(/на (\d{4}|{{curr_year}}) год/, "на " + year + " год");
                longName = longName.replace(/в (\d{4}|{{curr_year}}) году/, "в " + year + " году");
                //$textarea.val(longName);
                tinymce.activeEditor.setContent(longName);
            }
            tinymce.activeEditor.setContent(longName);
        });*/

        $("select[name=checks]").on("change", function (){
            let checkId = $(this).val();
            $.post("/", {ajax:1, action: "getInspectionsByCheck", checkId: checkId}, function (data){
                $("select[name=inspections]").html(data).trigger("chosen:updated");
            });
            $.post("/", {ajax:1, action: "getTemplatesByCheck", checkId: checkId}, function (data){
                $("select[name=document]").html(data).trigger("chosen:updated");
            });
        });
        $("select[name=document]").on("change", function (){
            let tempId = $(this).val();
            $.post("/", {ajax:1, action: "getCheckByTemplates", tempId: tempId}, function (data){
                $("select[name=checks]").html(data).trigger("chosen:updated");
            });
            $.post("/", {ajax:1, action: "getInspectionsByTemplate", tempId: tempId}, function (data){
                $("select[name=inspections]").html(data).trigger("chosen:updated");
            });
        });
        $("select[name=inspections]").on("change", function (){
            let tempId = $(this).val();
            $.post("/", {ajax:1, action: "getCheckByInspection", tempId: tempId}, function (data){
                $("select[name=checks]").html(data).trigger("chosen:updated");
            });
            $.post("/", {ajax:1, action: "getTemplateByInspection", tempId: tempId}, function (data){
                $("select[name=document]").html(data).trigger("chosen:updated");
            });
        });

        $("select[name=planname]").on("change", function (){

            let val = parseInt($(this).val());
            if (val === 0){
                el_app.clearInstitutions();
            }else {
                $.post("/", {ajax: 1, action: "getLongNamePlan", id: $(this).val()}, function (data) {
                    let answer = JSON.parse(data);
                    $(".preloader").fadeIn();
                    if (typeof answer == "object" && typeof answer != null) {
                        let addinstitution = JSON.parse(answer.addinstitution);
                        el_app.clearInstitutions();
                        $("input[name=short]").val(answer.short);

                        answer.longname.replace(/на (\d{4}|{{curr_year}}) год/, "на " + $("[name=year]").val() + " год");
                        //$("textarea[name=longname]").val(answer.longname);
                        //tinymce.activeEditor.setContent(answer.longname);

                        $("select[name=year]").val(answer.year).trigger("chosen:updated");
                        $("select[name=document]").val(answer.document).trigger("chosen:updated");
                        $("select[name=checks]").val(answer.checks).trigger("chosen:updated");
                        $("select[name=inspections]").val(answer.inspections).trigger("chosen:updated");

                        if (addinstitution !== null && addinstitution.length > 0) {
                            for (let i = 0; i < addinstitution.length; i++) {
                                el_registry.setInstitution(addinstitution[i]);
                                if (i < addinstitution.length - 1) {
                                    el_registry.cloneInstitution(i >= (addinstitution.length - 2), false);
                                }
                            }
                            $('[name^="institutions["]').trigger("change");
                        }
                    } else {
                        alert("Шаблон пуст");
                    }
                    $(".preloader").fadeOut();

                });
            }
        });


        $("#import_plan_btn").off("click").on("click", function(e){
            e.preventDefault();
            el_app.dialog_open("plan_import");
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
                allowInput: true,
                altInputClass: "el_input",
                firstDayOfWeek: 1,
            });

        $("#save_plan_template").off("click").on("click", function (e){
            e.preventDefault();
            let $form = $(this).closest("form"),
                prevFormId = $form.attr("id");

            $form.attr("id", "save_plan_template").trigger("submit");
            setTimeout(function (){$form.attr("id", prevFormId);}, 200);

        });

        el_registry.bindSetOrgByType();
        el_registry.bindPassword();
        el_registry.bindLoginAlias();
        el_registry.getPropsInRegistry();
        el_registry.bindDadata();
        el_registry.bindChangeInstitution();
        //el_registry.bind_check_institution_availability($(".institutions:last"));
        el_app.sort_init();
        el_app.filter_init();
        el_registry.bindRemoveInstitution();
        el_registry.just_opened = true;

        $('.pop_up_body select[name="institutions[]"]').trigger("change");
        el_registry.just_opened = false;
    },

    showLoadingIndicator: function () {
        $("#loading-indicator").show();
    },

    hideLoadingIndicator: function () {
        $("#loading-indicator").hide();
    },

    uploadFile: async function(file) {
        try {
            const formData = new FormData();
            formData.append('excelFile', file);
            formData.append('table_begin', 13);
            formData.append('plan_name', 9);

            const response = await fetch('/modules/plans/ajaxHandlers/upload-excel.php', {
                method: 'POST',
                body: formData,
                onUploadProgress: (event) => {
                    if (event.lengthComputable) {
                        const percent = (event.loaded / event.total) * 100;
                        el_registry.updateProgressBar(percent);
                    }
                }
            });

            if (response.ok) {
                const result = await response.json();
                el_registry.hideLoadingIndicator(); console.log(result);
                if (result.result) {

                    $("#importFields").html(result.resultHtml);
                    $("#importFields select").chosen();
                    $("#uploadButton").hide();
                    $("#importButton").css("display", "flex");
                    $("#importButton button").attr("disabled", false).off("click").on("click", function (e){
                        e.preventDefault();

                        let full_name = $('[name="full_name"]').val(),
                            checks_type = $('[name="checks_type"]').val(),
                            subject_type = $('[name="subject_type"]').val(),
                            year = $('[name="plan_year"]').val(),
                            addinstitution = $('[name^="institution["]');

                        el_registry.clearInstitutions();

                        $(".preloader").fadeIn();
                        $("textarea[name=longname]").val(full_name);
                        tinymce.activeEditor.setContent(full_name);
                        $("select[name=checks]").val(checks_type).trigger("chosen:updated");
                        $("select[name=subject]").val(subject_type).trigger("chosen:updated");
                        $("select[name=year]").val(year).trigger("chosen:updated");

                        if (addinstitution !== null && addinstitution.length > 0) {
                            for (let i = 0; i < addinstitution.length; i++) {
                                let index = i + 1,
                                data = {
                                    institutions: $(addinstitution[i]).val(),
                                    periods: $("[name='periods[" + index + "]']").val(),
                                    periods_hidden: $("[name='periods_hidden[" + index + "]']").val(),
                                    check_periods: $("[name='check_periods[" + index + "]']").val()
                                };
                                //console.log(data);
                                el_registry.setInstitution(data);
                                if (i < addinstitution.length - 1){
                                    el_registry.cloneInstitution(i >= (addinstitution.length - 2), false);
                                }
                                //console.log(index, $("[name='check_periods[" + index + "]']").val(), data);
                            }
                        }
                        $('[name^="institutions["]').trigger("change");
                        el_app.dialog_close("plan_import");
                        $(".preloader").fadeOut();
                    });
                }else{
                    $("#result").html('<div style="color: red;">' + result.resultText.join('<br>') + '</div>');
                }
            } else {
                alert('Ошибка при загрузке файла');
            }
        } catch (error) {
            el_registry.hideLoadingIndicator();
            $("#result").html(`<div style="color: red;">Ошибка: ${error.message}</div>`);
        }
    },

    updateProgressBar: function(percent) {
        const $progressBar = $('.progress-bar');
        $progressBar.css("width", `${percent}%`);
        $progressBar.attr('aria-valuenow', percent);
    },

    clearInstitutions: function (){
        $(".clear").closest(".institutions").remove();
        $(".institutions input, .institutions select").val("").trigger("chosen:updated");
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
            token: window.DADATA_TOKEN,
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

    bindRemoveInstitution(){
        $(".institutions .clear").off("click").on("click", function (){
            $(this).closest(".institutions").remove();

            let $institutions = $(".pop_up_body .institutions");
            for (let i = 0; i < $institutions.length; i++){
                $($institutions[i]).find(".question_number").text("Учреждение №" + (i+ 1));
            }
            if ($institutions.length === 1){
                $(".institutions .button.clear").hide();
            }
        });
    },

    cloneInstitution: function(is_last = true, purify = true){
        let current_check = $(".pop_up_body select[name='check_types[]']").val(),
        check_type = $(".pop_up_body select[name='inspections[]']").val(),
        check_period = $(".pop_up_body input[name='check_periods[]']").val();

        $(".pop_up_body .institutions select").chosen("destroy");
        $(".pop_up_body .institutions:last").clone().insertAfter(".pop_up_body .institutions:last");
        $(".pop_up_body .new_institution").off("click").on("click", function(e){
            e.preventDefault();
            el_registry.cloneInstitution();
        });

        $(".pop_up_body .institutions:last .quarterWrapper")
            .removeClass("open").find("b, .ui.label").removeClass("selected");
        $(".pop_up_body .institutions:last input").val("");

        /*$(".pop_up_body .institutions:last select[name='institutions[]']").empty().trigger("chosen:updated");
        el_registry.bindSetOrgByType();*/
        $(".pop_up_body .institutions:last select[name='check_types[]']").val(current_check)
            .trigger("chosen:updated");//.trigger("change");
        $(".pop_up_body .institutions:last select[name='inspections[]']").val(check_type)
            .trigger("chosen:updated");

        //$('[name="check_periods[]"] ~ input').mask('99.99.9999 - 99.99.9999');

        $(".pop_up_body .institutions:last [name='check_periods[]']").
        removeClass("flatpickr-input").attr("type", "date").next("input").remove();

        if (purify) {
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
                    allowInput: true,
                    altInputClass: "el_input",
                    firstDayOfWeek: 1,
                });
            $(".pop_up_body .institutions:last input[name='check_periods[]'] ~ input")
                .mask('99.99.9999 - 99.99.9999');
            setTimeout(function () {
                $(".pop_up_body .institutions:last [name='institutions[]']").val("0").trigger("chosen:updated").trigger("change");
            }, 200);
        }

        institutions_counter++;


            $(".institutions:last .clear").off("click").on("click", function (){
                $(this).closest(".institutions").remove();
                el_registry.setItemsNumbers($(".pop_up_body .institutions"), "Учреждение");
                if(institutions_counter > 1){
                    institutions_counter--;
                }
            });


        if (is_last) {
            $(".pop_up_body .institutions select").chosen({
                search_contains: true,
                no_results_text: "Ничего не найдено."
            });
            el_registry.setItemsNumbers($(".pop_up_body .institutions"), "Учреждение");
            el_registry.bindTipsy();
            el_registry.bindChangeInstitution();
            quarter.bindQuarter("#" + $(".pop_up_body .institutions:last .quarter_select").attr("id"));
            el_registry.bindCalendar();
            //el_registry.bind_check_institution_availability($(".institutions:last"));
            //el_registry.check_institution_availability($(".institutions"));
            el_registry.scrollToLastInstitution();
            el_app.bindRegistryButtonsExternal();
            el_registry.bindRemoveInstitution();
        }
    },

    bindChangeInstitution: function (){
        $('.pop_up_body select[name="institutions[]"]').off("change").on("change", function (){
            let $self = $(this);
            $.post("/", {ajax: 1, action: "getAddressByOrg", insId: $(this).val()}, function (data){
                $self.closest(".institutions").find('select[name="units[]"]').html(data).trigger("chosen:updated");
            });
            //el_registry.check_institution_availability($(this).closest(".institutions"));
        });
    },

    bind_check_institution_availability: function ($block){
        $block.find("[name='institutions[]'], [name='periods_hidden[]']")
            .on("change input", function (){
            el_registry.check_institution_availability($block);
        });
        $block.find("[name='periods[]']")
            .on("blur", function (){
                el_registry.check_institution_availability($block);
            });
        $(".pop_up_body [name=year]").on("change", function (){
            el_registry.check_institution_availability($block);
        });
    },

    /*check_institution_availability: async function ($block, mode = 'alert'){
       let result;
        if ((el_registry.just_opened && el_registry.check_institutions === 0) || !el_registry.just_opened) {
           let year = $("[name=year]").val(),
               ins = [],//parseInt($block.find("[name='institutions[]']").val()),
               $insSelect = $("select[name='institutions[]']"),
               period = $block.find("[name='periods_hidden[]']").val(),
               reg_id = parseInt($("[name=reg_id]").val());

           for (let i = 0; i < $insSelect.length; i++) {
               let v = parseInt($($insSelect[i]).val());
               if (v > 0) {
                   ins.push(v);
               }
           }

           //console.log("check_institution_availability", period, ins, reg_id);
           if (period !== "" && ins.length > 0/!* && (reg_id === 0 || isNaN(reg_id))*!/) {
               $.post("/", {
                   ajax: 1,
                   action: "get_institution_by_period",
                   plan: reg_id,
                   institution: ins,
                   period: period,
                   year: year
               }, async function (data) {
                   if (data !== "") {
                       let answer = JSON.parse(data);
                       if (answer.length > 0) {
                           let html = [];
                           for (let i = 0; i < answer.length; i++) {
                               html.push("<div style='display:block'> Для учреждения <strong>&laquo;" + answer[i].name + "&raquo;</strong>" +
                                   " уже назначены проверки на период " + answer[i].quarters + " " + year + " г.<br>"
                                   + "<br><strong>Проверки найдены в планах:</strong><br><ul class='alertList'><li>" + answer[i].plans.join("</li><li>") +
                                   "</li></div>")
                           }
                           if (mode === 'alert'){
                               alert(html.join("<hr>"));
                               result = true;
                           }else {
                               result = await confirm(html.join("<hr>") + "<p>&nbsp;</p>Всё равно продолжить?");
                           }
                           $(".notify .item.w_100").css("display", "block");
                       }
                   }
               });
           }
       }
        el_registry.check_institutions++;
       return result;
    },*/
    check_institution_availability: async function ($block, mode = 'alert') {
        if ((el_registry.just_opened && el_registry.check_institutions === 0) || !el_registry.just_opened) {
            let year = $("[name=year]").val(),
                ins = [],
                $insSelect = $("select[name='institutions[]']"),
                period = $block.find("[name='periods_hidden[]']").val(),
                reg_id = parseInt($("[name=reg_id]").val());

            for (let i = 0; i < $insSelect.length; i++) {
                let v = parseInt($($insSelect[i]).val());
                if (v > 0) {
                    ins.push(v);
                }
            }

            if (period !== "" && ins.length > 0) {
                // Используем await для AJAX запроса
                const data = await $.post("/", {
                    ajax: 1,
                    action: "get_institution_by_period",
                    module: "plans",
                    plan: reg_id,
                    institution: ins,
                    period: period,
                    year: year
                });

                if (data !== "") {
                    let answer = JSON.parse(data);
                    if (answer.length > 0) {
                        let html = [];
                        for (let i = 0; i < answer.length; i++) {
                            html.push("Для учреждения <strong>&laquo;" + answer[i].name + "&raquo;</strong>" +
                                " уже назначены проверки на период " + answer[i].quarters + " " + year + " г.<br>"
                                + "<br><strong>Проверки найдены в планах:</strong><br><ul class='alertList'><li>" +
                                answer[i].plans.join("</li><li>") +
                                "</li></ul>");
                        }

                        if (mode === 'alert') {
                            alert("<div style='display:block'>" + html.join("<hr>") + "</div>");
                            el_registry.check_institutions++;
                            return false; // Возвращаем false, если есть конфликты
                        } else {
                            // Создаем кастомное диалоговое окно вместо confirm
                            const userConfirmed = await confirm("<div style='display:block'>" + html.join("<hr>") +
                                "<p>&nbsp;</p>Всё равно продолжить?</div>");
                            el_registry.check_institutions++;
                            return userConfirmed; // Возвращаем true если пользователь нажал "Отмена" (хочет продолжить)
                        }
                    }
                }
            }
        }

        el_registry.check_institutions++;
        return true; // Возвращаем true если проверка пройдена (можно отправлять)
    },

    setInstitution: function(data){

        $(".pop_up_body .institutions:last input[name='institutions_hidden[]']").val(data.institutions);
        $(".pop_up_body .institutions:last input[name='units_hidden[]']").val(data.units);
        $(".pop_up_body .institutions:last select[name='institutions[]']").val(data.institutions)
            .trigger("chosen:updated");
        $(".pop_up_body .institutions:last select[name='check_types[]']").val(data.check_types)
            .trigger("chosen:updated");//.trigger("change");
        $(".pop_up_body .institutions:last input[name='inspections_hidden[]']").val(data.inspections);
        $(".pop_up_body .institutions:last select[name='inspections[]']").val(data.inspections)
            .trigger("chosen:updated");
        $(".pop_up_body .institutions:last input[name='periods[]']").val(data.periods);
        $(".pop_up_body .institutions:last input[name='periods_hidden[]']").val(data.periods_hidden);
        $(".pop_up_body .institutions:last input[name='check_periods[]']").val(data.check_periods)
            .flatpickr({
                defaultDate:data.check_periods,
                locale: 'ru',
                mode: 'range',
                time_24hr: true,
                dateFormat: 'Y-m-d',
                altFormat: 'd.m.Y',
                conjunction: '-',
                altInput: true,
                allowInput: true,
                altInputClass: "el_input",
                firstDayOfWeek: 1,
            });

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
            $(".staff .clear").off("click").on("click", function (){
                $(this).closest(".staff").remove();
                el_registry.setItemsNumbers($(".pop_up_body .staff"), "Учреждение");
                staffs_counter--;
            });
        }
        el_app.bindGetUnitsByOrg();
        $("select[name='institutions[]']:last").trigger("change");

        /*let $staffs = $(".pop_up_body .staff");
        for (let i = 0; i < $staffs.length; i++){
            $($staffs[i]).find(".question_number").text("Сотрудник №" + (i+ 1));
        }*/
        el_registry.setItemsNumbers($(".pop_up_body .staff"), "Сотрудник");
        el_registry.bindTipsy();
        el_registry.bindCalendar();

        el_registry.scrollToLastInstitution();
    },

    setItemsNumbers: function (objects, title){
        for (let i = 0; i < objects.length; i++){
            $(objects[i]).find(".question_number").text(title + " №" + (i+ 1));
            if($(objects[i]).find(".button.icon.clear").is("div")){
                $(objects[i]).find(".button.icon.clear").remove();
            }
            $(objects[i]).find(".question_number")
                .after('<div class="button icon clear"><span class="material-icons">close</span></div>');
        }
    },

    scrollToLastInstitution: function(){
        $(".pop_up").animate({
            scrollTop: $(".pop_up").scrollTop() + 100000
        }, 700);
    },

    bindSetOrgByType: function (){

        /*$("select[name='check_types[]']").off("change").on("change", function (){
            let $parent = $(this).closest(".institutions"),
                $inst = $parent.find("select[name='institutions[]']"),
                $instSelected = $parent.find("input[name='institutions_hidden[]']");
            $inst.html("").trigger("chosen:updated");
            $.post("/", {ajax: 1, action: "getOrgByCheckType", check_type: $(this).val(), selected: $instSelected.val()},
                function (data){
                    //el_registry.bindSetUnitsByOrg();
                    el_app.bindGetUnitsByOrg();
                    $inst.html(data).trigger("chosen:updated");//.trigger("change");
                });
        });*/
    },

    bindSetUnitsByOrg: function (){

        $("select[name='institutions[]']").off("change").on("change", function (){
            let $parent = $(this).closest(".institutions"),
                $units = $parent.find("select[name='units[]']"),
                $unitsSelected = $parent.find("input[name='units_hidden[]']");
            $units.html("").trigger("chosen:updated");
            $.post("/", {ajax: 1, action: "getUnitsByOrg", orgId: $(this).val(), selected: $unitsSelected.val()},
                function (data){
                    $units.html(data).trigger("chosen:updated");
                });
        });
    }
};
$(document).ready(function(){
    el_registry.create_item_init();
    el_app.mainInit();
});

var institutions_counter = 1;
var calendars = {};

var el_registry = {
    //Инициализация контролов в разделе "Документы"
    create_item_init: function() {

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            let regId = $("#registry_id").val();
            el_app.dialog_open("registry_items_create", regId);
        });

        $("#button_nav_delete").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Уверены, что хотите удалить этот элемент справочника?");
                if (ok) {
                    $("form#registry_items_delete").trigger("submit");
                }
            }
        });
        $("#button_nav_clone").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                $("form#registry_items_delete").attr("id", "registry_items_clone").trigger("submit").attr("id", "registry_items_delete");
            }
        });

        $(".link a").off("click").on("click", function (e) {
            el_app.setMainContent('/registry');
            return false;
        });

        $("#registry_list select").off("change").on("change", function () {
            let params = (parseInt($(this).val()) > 0) ? "id=" + parseInt($(this).val()) : "";
            el_app.setMainContent('/registry', params);
        });

        $("#parent_registry").off("change").on("change", function () {
            $.post("/", {
                ajax: 1,
                action: "get_registry",
                source: "registryitems",
                parent: $(this).val(),
                selected: $(this).data("selected")
            }, function (data) {
                let $dependWrap = $("#depend_registry"),
                    $depend = $("#depend_registry select");
                if (data !== "") {
                    $depend.html(data).trigger("chosen:updated");
                    $dependWrap.show();
                } else {
                    $dependWrap.hide();
                }
            });
        });

        $("#depend_registry select").on("change", function () {
            $("#parent_registry").attr("data-selected", $(this).val().join(","))
        });

        $(".showAnswers").off("click").on("click", function (e) {
            e.preventDefault();
            let regId = $(this).closest("tr").data("id");
            el_app.dialog_open("registry_answers_edit", regId);
        });

        $(".answer_approve").on("click", async function (e) {
            e.preventDefault();
            let ok = await confirm("Остальные варианты ответов будут удалены. Вы уверены?")
            let $answers = $("#registry_answers_edit .group"),
                approve_id = $(this).closest(".group").attr("id");
            if (ok) {
                for (let i = 0; i < $answers.length; i++) {
                    if ($($answers[i]).attr("id") !== approve_id) {
                        $($answers[i]).remove();
                    }
                }
                $(".confirm .button").attr("disabled", false);
            }
        });

        $(".viewDoc").off("click").on("click", function () {
            let taskId = $(this).data("id");
            el_app.dialog_open("pdf", {docId: taskId, is_inst: true}, "documents");
        });
        $(".addRoad").off("click").on("click", function () {
            let taskId = $(this).data("id"),
                insId = $(this).data("ins");
            el_app.dialog_open("add_road", {docId: taskId, insId: insId});
        });

        $(".new_schedule_row").off("click").on("click", function (e){
            e.preventDefault();
            $(".pop_up_body .schedule_row:last").clone().insertAfter(".pop_up_body .schedule_row:last");
        });

        el_registry.bindDadata();
        el_app.sort_init();
        el_app.filter_init();

    },

    cloneInstitution: function(){
        let current_check = $(".pop_up_body select[name='check_types[]']").val();

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
        el_registry.bindSetOrgByType($(".institutions:last"));
        $(".pop_up_body .institutions:last select[name='check_types[]']").val(current_check)
            .trigger("chosen:updated").trigger("change");

        $(".pop_up_body .institutions:last [name='check_periods[]']").
        removeClass("flatpickr-input").attr("type", "date").next("input").remove();

        institutions_counter++;
        if(institutions_counter > 1){
            $(".question_number").last().after('<div class="button icon close"><span class="material-icons">close</span></div>');
            $(".institutions .close").off("click").on("click", function (){
                $(this).closest(".institutions").remove();
                institutions_counter--;
            });
        }

        let $institutions = $(".pop_up_body .institutions");
        for (let i = 0; i < $institutions.length; i++){
            $($institutions[i]).find(".question_number").text("Учреждение №" + (i+ 1));
        }

        el_registry.bindTipsy();
        quarter.bindQuarter("#" + $(".pop_up_body .institutions:last .quarter_select").attr("id"));
        el_registry.bindCalendar();
        el_registry.scrollToLastInstitution();
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
            }),

            cal_single_date_time = $(".single_date_time").flatpickr({
                locale: 'ru',
                mode: 'single',
                time_24hr: true,
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                altFormat: 'd.m.Y H:i',
                allowInput: true,
                conjunction: '-',
                minDate: minDate,
                maxDate: maxDate,
                altInput: true,
                altInputClass: "el_input",
                firstDayOfWeek: 1
            });

        if (typeof el_app.calendars.popup_calendar != "undefined" && "push" in el_app.calendars.popup_calendar) {

            console.log(el_app.calendars.popup_calendar);
            el_app.calendars.popup_calendar.push(cal);
            el_app.calendars.popup_calendar.push(cal_single);
            el_app.calendars.popup_calendar.push(cal_single_date_time);
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

    scrollToLastInstitution: function(){
        $(".pop_up").animate({
            scrollTop: $(".pop_up").scrollTop() + 1000
        }, 500);
    },

    bindSetOrgByType: function (instanceObj){
        let $that = instanceObj.find("select[name='check_types[]']"),
            $inst = instanceObj.find("select[name='institutions[]']");
        $that.off("change").on("change", function (){
            $inst.html("").trigger("chosen:updated").trigger("change");
            $.post("/", {ajax: 1, action: "getOrgByCheckType", check_type: $that.val()}, function (data){
                $inst.html(data).trigger("chosen:updated");
            })
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
    }
};

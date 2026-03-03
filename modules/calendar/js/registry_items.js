$(document).ready(function(){
    el_registry.create_init();
    //el_app.mainInit();
});
var violation_counter = 1;
var el_registry = {
    //Инициализация контролов в разделе "Календарь"
    create_init: function(){

        /*$("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            let regId = $("#registry_id").val();
            el_app.dialog_open("registry_create", regId);
        });*/
        $('#button_nav_create:not(.disabled)').off('click').on('click', function () {
            let plan_id = el_tools.getUrlVar(document.location.href);
            el_app.dialog_open('assign_staff', plan_id.id, 'calendar');
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
        $("#button_nav_plans").on("click", function (){
            //document.location.href = "/plans";
            el_app.setMainContent('/plans');
        });
        $(".link a").off("click").on("click", function (e) {
            el_app.setMainContent('/registry');
            return false;
        });

        $(".link .assign").off("click").on("click", function () {
            let insId = $(this).data("ins"),
                uid = $(this).data("uid");
            el_app.dialog_open("assign_staff", [insId, uid]);
        });

        $("#plan_list select").off("change").on("change", function(){
            let id = parseInt($(this).val()),
                params = id > 0 ? "id=" + id : "";
            el_app.setMainContent('/calendar', params);
            el_tools.setcookie("current_plan_id", id);
        });


        $("#parent_registry").off("change").on("change", function(){
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

        $("#depend_registry select").on("change", function(){
            $("#parent_registry").attr("data-selected", $(this).val().join(","))
        });

        $(".showAnswers").off("click").on("click", function(e){
            e.preventDefault();
            let regId = $(this).closest("tr").data("id");
            el_app.dialog_open("registry_answers_edit", regId);
        });

        $(".answer_approve").on("click", async function(e){
            e.preventDefault();
            let ok = await confirm("Остальные варианты ответов будут удалены. Вы уверены?")
            let $answers = $("#registry_answers_edit .group"),
                approve_id = $(this).closest(".group").attr("id");
            if(ok){
                for(let i = 0; i < $answers.length; i++){
                    if($($answers[i]).attr("id") !== approve_id){
                        $($answers[i]).remove();
                    }
                }
                $(".confirm .button").attr("disabled", false);
            }
        });

        $(".institutions .clear").off("click").on("click", function (e){
            e.preventDefault();
            $(this).closest(".institutions").remove();
        });

        $(".link .assign").off("click").on("click", function () {
            let taskId = $(this).data("id");
            el_app.dialog_open("assign_staff", {insId: 0, taskId});
        });

        $(".link .view_staff").off("click").on("click", function () {
            let taskId = $(this).data("id");
            el_app.dialog_open("assign_staff", {insId: 0, taskId});
        });

        $("#check_staff input[name='dates[]'], #check_staff select[name='units[]']")
            .off("change input").on("change input", function (){
            let dates = $("#check_staff input[name='dates[]']").val(),
                units = $("#check_staff input[name='units[]']").val(),
                user_selected = $("#check_staff input[name='users_hidden[]']").val(),
                $users = $("#check_staff input[name='users[]']");
            if (dates.length > 0 && units.length > 0){
                $.post("/", {ajax: 1, action: "available_staff", dates: dates,  units: units, user_selected: user_selected},
                    function (data){
                    $users.html(data);
                });
            }
        });

        $(".view_task").off("click").on("click", function (){
            let taskId = $(this).data("id");
            el_app.dialog_open("view_task", {taskId: taskId, view_result: 0}, "calendar");
        });
        $(".view_result").off("click").on("click", function (){
            let taskId = $(this).data("id");
            el_app.dialog_open("view_task", {taskId: taskId, view_result: 1}, "calendar");
        });

        $("input[type=tel]").mask('+7 (999) 999-99-99');

        $("input[name=toggle_view]").off("change").on("change", function (){
            let value = $(this).attr("id").replaceAll("switch_", ""),
                $table = $("#registry_items_delete"),
                $calendar = $("#calendar"),
                $gantt = $("#gantt");
            $(".scroll_current").hide();
            $("#" + value).show();
            el_tools.setcookie("calendar_view", value);
            calendarGrid.render();
            gantt.render();
        });

        el_registry.bindCloneViolation();
        el_registry.bindRemoveViolation();
        el_registry.bindDadata();
        el_app.sort_init();
        el_app.filter_init();
    },

    cloneViolation: function(){

        $("#tab_my-panel .violation select").chosen("destroy");
        tinymce.remove();
        $("#tab_my-panel .violation:last").clone().insertAfter("#tab_my-panel .violation:last");

        $("#tab_my-panel .violation:last [name='violation_id[]']").val("");
        $("#tab_my-panel .violation:last .otherAuthor").remove();

        $("#tab_my-panel .violation:last select").val("")
        $("#tab_my-panel .violation select").chosen({
            search_contains: true,
            no_results_text: "Ничего не найдено."
        });

        let textId = "textarea" + Date.now();
        $("#tab_my-panel .violation:last textarea").val("").attr("id", textId);

        tinymce.init({
            selector: ".arbitrary textarea",//[name='violation_text[]']
            language: "ru",
            plugins: "code link table autoresize lists",
            width: "100%",
            license_key: "gpl",
            branding: false,
            statusbar: false,
            menubar: false,
            extended_valid_elements: "code[*]", // Разрешает теги <code>
            protect: [
                /\{\{.*?\}\}/g,     // Защищает {{ переменные }}
                /\{\%.*?\%\}/g      // Защищает {% операторы %}
            ],
            toolbar: "undo redo | paste pastetext| styles | fontsize | bold italic | alignleft aligncenter alignright alignjustify | outdent indent | bullist numlist | link | table | code"
        });

        violation_counter++;
        if(violation_counter > 1){
            $("#tab_my-panel .violation_number").last().after('<div class="button icon clear"><span class="material-icons">close</span></div>');
            /*$(".violation .clear").off("click").on("click", function (){
                $(this).closest(".violation").remove();
                el_registry.setItemsNumbers($(".pop_up_body .violation"), "Нарушение");
                violation_counter--;
            });*/
        }
        el_registry.setItemsNumbers($("#tab_my-panel .violation"), "Нарушение");
        el_registry.bindTipsy();
        el_registry.bindCloneViolation();
        el_registry.bindRemoveViolation();
        el_registry.scrollToLastViolation();
    },

    bindCloneViolation: function (){
        $("#tab_my-panel .new_violation").off("click").on("click", function(e){
            e.preventDefault();
            el_registry.cloneViolation();
        });
    },

    bindRemoveViolation: function(){
        $(".violation .clear").off("click").on("click", function (){
            $(this).closest(".violation").remove();
            el_registry.setItemsNumbers($(".pop_up_body .violation"), "Нарушение");
            violation_counter--;
        });
    },

    setItemsNumbers: function (objects, title){
        for (let i = 0; i < objects.length; i++){
            $(objects[i]).find(".violation_number").text(title + " №" + (i+ 1));
        }
    },

    scrollToLastViolation: function(){
        $(".pop_up").animate({
            scrollTop: 10000//$(".pop_up_body").height() + $(".pop_up_body .violation:last").position().top
        }, 300);
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
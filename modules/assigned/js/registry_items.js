$(document).ready(function(){
    el_registry.create_init();
    //el_app.mainInit();
});

var el_registry = {
    //Инициализация контролов в разделе "Задания"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            el_app.dialog_open("assign_staff", {}, "calendar");
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
            document.location.href = "/plans";
        });
        $(".view_task").off("click").on("click", function (){
            let taskId = $(this).data("id");
            el_app.dialog_open("view_task", {taskId: taskId, view_result: 0}, "calendar");
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

        $("#registry_list select").off("change").on("change", function(){
            let params = (parseInt($(this).val()) > 0) ? "id=" + parseInt($(this).val()) : "";
            el_app.setMainContent('/registry', params);
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

        $("input[name=toggle_view]").off("change").on("change", function (){
            let value = $(this).attr("id"),
            $table = $("#registry_items_delete"),
            $gantt = $("#gantt");
            if (value === "switch_table"){
                $table.show();
                $gantt.hide();
            }else{
                $table.hide();
                $gantt.show();
            }
        });

        el_registry.bindDadata();
        el_app.sort_init();
        el_app.filter_init();
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
    }
};
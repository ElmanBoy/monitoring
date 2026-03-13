$(document).ready(function () {
    el_registry.create_item_init();
    el_app.mainInit();
});

var institutions_counter = 1;
var calendars = {};

var el_registry = {

    create_item_init: function () {

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
                $("form#registry_items_delete")
                    .attr("id", "registry_items_clone")
                    .trigger("submit")
                    .attr("id", "registry_items_delete");
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
                ajax: 1, action: "get_registry", source: "registryitems",
                parent: $(this).val(), selected: $(this).data("selected")
            }, function (data) {
                let $dependWrap = $("#depend_registry"),
                    $depend     = $("#depend_registry select");
                if (data !== "") {
                    $depend.html(data).trigger("chosen:updated");
                    $dependWrap.show();
                } else {
                    $dependWrap.hide();
                }
            });
        });

        $("#depend_registry select").on("change", function () {
            $("#parent_registry").attr("data-selected", $(this).val().join(","));
        });

        $(".showAnswers").off("click").on("click", function (e) {
            e.preventDefault();
            let regId = $(this).closest("tr").data("id");
            el_app.dialog_open("registry_answers_edit", regId);
        });

        $(".answer_approve").on("click", async function (e) {
            e.preventDefault();
            let ok = await confirm("Остальные варианты ответов будут удалены. Вы уверены?");
            let $answers   = $("#registry_answers_edit .group"),
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

        // Просмотр PDF акта / графика
        $(".viewDoc").off("click").on("click", function () {
            let taskId = $(this).data("id");
            el_app.dialog_open("pdf", {docId: taskId, is_inst: true}, "roadmap");
        });

        // Создать график
        $(".addRoad").off("click").on("click", function () {
            let taskId = $(this).data("id"),
                insId  = $(this).data("ins");
            el_app.dialog_open("add_road", {docId: taskId, insId: insId}, "roadmap");
        });

        // Открыть существующий график устранения
        $(".viewRoad").off("click").on("click", function () {
            let roadId = $(this).data("id");
            el_app.dialog_open("view_road", {roadId: roadId}, "roadmap");
        });

        // Добавить строку вручную (если нет нарушений из акта)
        $(".new_schedule_row").off("click").on("click", function (e) {
            e.preventDefault();
            let $last  = $(".pop_up_body .schedule_row:last"),
                $clone = $last.clone();
            // Очищаем значения клона
            $clone.find("input[type=text], input[type=date], textarea").val("");
            $clone.find(".button.clear").show();
            $clone.insertAfter($last);
            // Пересчитываем № п/п
            el_registry.renumberRows();
        });

        el_registry.bindDadata();
        el_app.sort_init();
        el_app.filter_init();
    },

    renumberRows: function () {
        $(".pop_up_body .schedule_row").each(function (idx) {
            $(this).find("td:first .el_data").text(idx + 1);
        });
    },

    cloneInstitution: function () {
        let current_check = $(".pop_up_body select[name='check_types[]']").val();

        $(".pop_up_body .institutions select").chosen("destroy");
        $(".pop_up_body .institutions:last").clone().insertAfter(".pop_up_body .institutions:last");
        $(".pop_up_body .new_institution").off("click").on("click", function (e) {
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
        $(".pop_up_body .institutions:last select[name='institutions[]']")
            .empty().trigger("chosen:updated");
        el_registry.bindSetOrgByType($(".institutions:last"));
        $(".pop_up_body .institutions:last select[name='check_types[]']")
            .val(current_check).trigger("chosen:updated").trigger("change");
    },

    bindDadata: function () {
        // Заглушка — переопределяется глобальным модулем если есть
        if (typeof el_dadata !== "undefined" && typeof el_dadata.init === "function") {
            el_dadata.init();
        }
    },

    bindSetOrgByType: function ($wrap) {
        // Заглушка
    },
};
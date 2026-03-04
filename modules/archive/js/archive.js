$(document).ready(function () {
    el_archive.init();
});

var el_archive = {

    init: function () {

        // Восстановление из архива
        $("#button_nav_restore").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Восстановить выбранные записи из архива?");
                if (ok) {
                    let $checked = $("#registry_items_delete_real").find("input[name='reg_id[]']:checked");
                    if ($checked.length === 0) return;
                    $("#registry_items_restore").find("input[name='reg_id[]']").remove();
                    $checked.clone().appendTo("#registry_items_restore");
                    $("form#registry_items_restore").trigger("submit");
                }
            }
        });

        // Чекбокс "выделить все"
        $("#check_all").off("change").on("change", function () {
            let checked = $(this).is(":checked");
            $("input[name='reg_id[]']").prop("checked", checked);
            el_archive.toggleGroupButtons();
        });

        // Активация групповых кнопок при выборе строк
        $(document).on("change", "input[name='reg_id[]']", function () {
            el_archive.toggleGroupButtons();
        });
    },

    toggleGroupButtons: function () {
        let count = $("input[name='reg_id[]']:checked").length;
        if (count > 0) {
            $(".group_action").removeClass("disabled");
        } else {
            $(".group_action").addClass("disabled");
        }
    }
};

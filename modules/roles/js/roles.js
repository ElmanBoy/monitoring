$(document).ready(function(){
    el_app.mainInit();
    el_roles.create_init();
});

var el_roles = {
    //Инициализация контролов в разделе "Роли"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            el_app.dialog_open("role_create");
        });
        $("#button_nav_delete").off("click").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Уверены, что хотите удалить эти роли?");
                if (ok) {
                    $("form#role_delete").trigger("submit");
                }
            }
        });
        $("#button_nav_clone").off("click").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                $("form#role_delete").attr("id", "role_clone").trigger("submit").attr("id", "role_delete");
            }
        });


        el_app.sort_init();
        el_app.filter_init();
        $(".preloader").fadeOut('fast');
    }
};
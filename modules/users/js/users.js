$(document).ready(function(){
    el_app.mainInit();
    el_users.create_init();
});

var el_users = {
    //Инициализация контролов в разделе "Роли"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            el_app.dialog_open("registry_create");
        });

        $("#button_nav_delete").off("click").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Уверены, что хотите удалить этих пользователей?");
                if (ok) {
                    $("form#registry_items_delete").trigger("submit");
                }
            }
        });
        $("#button_nav_clone").off("click").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                $("form#user_delete").attr("id", "user_clone").trigger("submit").attr("id", "user_delete");
            }
        });

        $("#gen_pass").off("click").on("click", function(){
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

        $("#registry_create input[name='surname'], #registry_create input[name='name']").on("input", function(){
            let name = $("#registry_create input[name='name']").val(),
                surname = $("#registry_create input[name='surname']").val(),
                login = el_tools.translit(name.charAt(0) + surname);

            $("#registry_create input[name='login']").val(login);
        });
        $("#registry_create input[name='surname']").on("paste", function (){
            let $self = $(this);
            setTimeout(function (){
                let fioArr = $self.val().split(" ");
                $("#registry_create input[name='surname']").val(fioArr[0]).trigger("input");
                $("#registry_create input[name='name']").val(fioArr[1]);
                $("#registry_create input[name='middle_name']").val(fioArr[2]);
                $("#registry_create input[name='email']")
                    .val($("#registry_create input[name='login']").val() + "@mosreg.ru");
            }, 200);
        });

        $("#parent_registry").off("change").on("change", function(){
            $.post("/", {
                ajax: 1,
                action: "get_division",
                source: "registryitems",
                parent: $(this).val(),
                selected: $(this).data("selected")
            }, function (data) {
                if (data !== "") {
                    $("#depend_registry").html(data);
                } else {
                    $("#depend_registry").html("");
                }
            });
        });

        $("#registry_create select[name=active]").val(1).trigger("chosen:updated");
        $("#registry_create select[name=institution]").val(12).trigger("chosen:updated");


        $("#depend_registry").on("change", function(){
            $("#parent_registry").attr("data-selected", $(this).val())
        });


        $("[name='roles[]']").on("change", function(){ console.log($(this).val(), el_tools.in_array('3', $(this).val()));
            let $subordinates = $("[name='subordinates[]']");
            if(el_tools.in_array('3', $(this).val()) ){
                $subordinates.show();
            }else{
                $subordinates.hide();
            }
        });

        el_app.sort_init();
        el_app.filter_init();
    }
};
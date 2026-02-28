$(document).ready(function(){
    el_app.mainInit();
    el_registry.create_init();
});

var check_number = 1;
var option_number = 1;
var newRegistryData = [];

var el_registry = {
    //Инициализация контролов в разделе "Роли"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            el_app.dialog_open("prop_create", "", "registry/props");
        });

        $("#button_nav_delete").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Уверены, что хотите удалить этих пользователей?");
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
        $("#button_nav_registry").on("click", function (){
            //document.location.href = "/registry";
            el_app.setMainContent('/registry');
        });



        $(".link a").off("click").on("click", async function (e) {
            let link = $(this).attr("href");
            if (link !== '' && link !== '/' && link !== '#') {
                e.preventDefault();
                let linkArr = link.split('/?');
                el_app.setMainContent('/registry', linkArr[1]);
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
                case "list_fromdb_multi":
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
            el_registry.getPropsInRegistry();
        });
        $("#remove_props").on("click", function (e){
            e.preventDefault();
            let $selected = $("#reg_props_list input:checked");
            $selected.closest(".item").appendTo("#all_props_list")
                .find(".rename, .rename_done, .drag_handler").remove();
            $("#all_props_list input:checked").prop("checked", false);
            el_registry.getPropsInRegistry();
        });

        $("input[name=field_name]").on("input paste blur", function (){
            let $input = $(this);
            if ($input.val().length > 60) {
                $input.val($input.val().substring(0, 60));
            }
            el_tools.validateLowercaseAlphanumeric(this);
        }).on("keypress", function (e){
            const char = String.fromCharCode(e.which);
            return /[a-z0-9]/.test(char);
        });

        $("#prop_create input[name=prop_name]").on("blur", function (){
            el_tools.translateWithGoogle($(this).val(), "regprops").
            then(r => $("input[name=field_name]").val(r)) ;
        });

        $("select[name=fromdb]").on("change", function (){
            el_registry.showFieldsFromDB(
                $(this).val(),
                $("input[name=selected_field]").val(),
                $("input[name=selected_value]").val());
        });

        $("input[type=tel]").mask('+7 (999) 999-99-99');

        el_app.sort_init();
        el_app.filter_init();
        //el_app.getExtAnswersCount();
    },

    showFieldsFromDB: function (regId, selected, selectedValue){
        $.post("/", {ajax: 1, action: "getFieldsFromDB", reg_id: regId, selected: selected, selectedValue: selectedValue},
            function (data){
                let answer = JSON.parse(data);
                $("select[name=fromdb_fields]").html(answer.text.join("\n")).trigger("chosen:updated");
                $("select[name=fromdb_value]").html(answer.value.join("\n")).trigger("chosen:updated");
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

    setOptionNumber: function (){
        let $option_number = $(".option_number");
        for (let i = 1; i < $option_number.length; i++){
            $($option_number[i]).text("Опция №" + (i + 1));
        }
    },

    getPropsInRegistry: function (){
        //получить итоговый список полей, добавить к каждому пункту иконки редактроования и перетаскивания,
        // и получившийся список внести в переменную для сохранения
        let $items = $("#reg_props_list .item");
        $items.find(".rename, .rename_done, .drag_handler").remove();
        for (let i = 0; i < $items.length; i++){
            $($items[i]).append('<span class="material-icons rename" title="Переименовать поле">drive_file_rename_outline</span>\n' +
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

        $('.rename[title], .drag_handler[title]').tipsy({
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

    setNewRegistryData: function (){
        let $items = $("#reg_props_list .item");
        newRegistryData = [];
        for (let i = 0; i < $items.length; i++){
            newRegistryData.push({
                label: $.trim($($items[i]).find("label").text()),
                value: $($items[i]).find("input").val(),
                sort: i
            });
        }
        console.log(newRegistryData);
        //$("[name=reg_prop]").val(JSON.stringify(newRegistryData));
        try {
            $("input[name=reg_prop]").val(JSON.stringify(newRegistryData));
            console.log($("input[name=reg_prop]").val());
        } catch (e) {
            console.error("Ошибка при записи:", e); // Найдёте скрытые исключения
        }
    }
};
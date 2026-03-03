$(document).ready(function(){
    el_app.mainInit();
    el_registry.create_init();
});

var check_number = 1;
var newRegistryData = [];
var institutions_counter = 1;
var calendars = {};
var quarters = {};

var el_registry = {
    //Инициализация контролов в разделе "Чек-листы"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            el_app.dialog_open("registry_create");
        });

        $("#button_nav_delete").on("click", async function () {
            if (!$(this).hasClass("disabled")) {
                let ok = await confirm("Содержимое чек-листов будет так же удалено. Уверены, что хотите удалить эти чек-листы?");
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
        $("#button_nav_check_props").on("click", function (){
            //document.location.href = "/checklists/props";
            el_app.setMainContent('/checklists/props');
        });
        $("#button_nav_check_items").on("click", function (){
            //document.location.href = "/checklists/items";
            el_app.setMainContent('/checklists/items');
        });
        $("#button_nav_check").on("click", function (){
            //document.location.href = "/checklists";
            el_app.setMainContent('/checklists');
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

        $("#add_props").off("click").on("click", function (e){
            e.preventDefault();
            let $selected = $("#all_props_list input:checked"),
                $item = $selected.closest(".item"),
                $checked = $item.find("[type=checkbox]:checked"),
                blockArr = [];

            for (let i = 0; i < $checked.length; i++) {
                let $parent = $($checked[i]).closest(".item"),
                    is_block = $parent.find(".block_data").find("[type=checkbox]").prop("checked") === true; //Выбран ли заголовок блока

                if (!is_block) {
                    //Если выбраны только дочерние пункты без блока, помещаем в отдельные li

                    let itemId = $($checked[i]).attr("id").replaceAll("prop", ""),
                        $placeholder = $("#reg_props_list li[data-id=" + itemId + "]");
                    //Если это ранее удаленный пункт из блока
                    if (typeof $placeholder !== "undefined"){
                        $placeholder.addClass("el_data").html($($checked[i]).closest(".el_data").html());
                        $($checked[i]).closest(".item").remove();
                    }else {
                        //Если это отдельный пункт, то перенести, как есть
                        $selected.closest(".item").appendTo("#reg_props_list");
                    }
                    /*$($checked[i]).prop("checked", false).closest(".el_data").hide();
                    $("<li class='el_data'>" + $($checked[i])
                        .closest(".el_data").html() + "</li>").appendTo("#reg_props_list");*/

                } else if (is_block) {
                    //Если это блок, то клонировать только чекнутые пункты с блоком в один li
                    let parentId = $parent.data("id"),
                        idAttr = "",
                        blockClass = "";

                    if (!$($checked[i]).hasClass("block_check")){
                        idAttr = " data-parent='" + parentId + "' style='display:" + $($checked[i]).closest(".el_data").css("display") + "'";
                    }else{
                        blockClass = " block_data";
                    }
                    blockArr.push("<li class='el_data" + blockClass + "'" + idAttr + ">" + $($checked[i])
                        .closest(".el_data").html() + "</li>");


                    if ($($checked[(i + 1)]).hasClass("block_check") || i >= $checked.length - 1){
                        //Если дошли до следующего блока или до конца списка, то склеиваем и создаем предыдущий блок
                        $("<li class='item blockItem' data-id='" + parentId + "'><ol>" + blockArr.join("") + "</ol></li>")
                            .appendTo("#reg_props_list");
                        blockArr = [];
                    }
                    //$parent.find("[type=checkbox]").prop("checked", false);
                    $parent.closest(".item").hide();

                } else {
                    /*let itemId = $($checked[i]).attr("id").replaceAll("prop", ""),
                        $placeholder = $("#reg_props_list li[data-id=" + itemId + "]");
                    //Если это ранее удаленный пункт из блока
                    if (typeof $placeholder !== "undefined"){
                        $placeholder.addClass("el_data").html($($checked[i]).closest(".el_data").html());
                        $($checked[i]).closest(".item").remove();
                    }else {
                        //Если это отдельный пункт, то перенести, как есть
                        $selected.closest(".item").appendTo("#reg_props_list");
                    }*/
                }
            }

            $("#reg_props_list input:checked").prop("checked", false);
            $selected.prop("checked", false);
            $("#reg_props_list input[type=hidden]").attr("name", "prop[]");
            el_registry.getPropsInRegistry();
            el_registry.bindShowBlock();
        });

        $("#remove_props").off("click").on("click", function (e){
            e.preventDefault();
            let $selected = $("#reg_props_list input:checked"),
                blockArr = [];

            for (let i = 0; i < $selected.length; i++){
                let id = $($selected[i]).attr("id").replace("prop", ""),
                    $self = $("#all_props_list [id=prop" + id + "]"),
                    $parent = $($selected[i]).closest(".item"),
                    parentId = $parent.data("id"),
                    is_block = typeof $parent.data("id") !== "undefined";

                if ($($selected[0]).hasClass("block_check")){
                    //Если отмечен блок и в левой части есть скрытый блок, то показываем снова весь блок в левой панели
                    if ($self.closest(".item").is("li")) {
                        $self.closest(".item").show();
                        //И удаляем из правой панели
                        $($selected[i]).closest(".item").remove();
                    }else{
                        //Иначе просто переносим  из правой в левую часть
                        $($selected[0]).closest(".item").appendTo("#all_props_list")
                            .find(".rename, .rename_done, .drag_handler, .required, .unique").remove();
                        break;
                    }

                    //Удаляем так же ранее исключенные из этого блока пункты
                    $("#all_props_list > .item.wblocks[data-parent=" + parentId + "]").closest(".item").remove();
                }else{
                    //Если ранее этот пункт в левой панели был скрыт, показываем его
                    let $exItem = $(".el_data[data-itemId=" + id + "]");
                    if ($exItem.is("li") && $parent.css("display") === "flex"){
                        $exItem.show();
                    }else{
                        //Вставляем их в левой панели в конец
                        blockArr.push("<li class='item el_data wblocks' data-parent='" + parentId + "'>" +
                            $($selected[i]).closest(".el_data").html() + "</li>");
                        //Если это пункт из блока
                        if (is_block) {
                            //Создаем плейсхолдер вместо удаляемого пункта для возможного возвращения назад
                            let loseId = $($selected[i]).attr("id").replace("prop", "");
                            $($selected[i]).closest(".el_data").removeClass("el_data").attr("data-id", loseId).html("");
                        }else{
                            //Или удаляем из правой панели
                            $($selected[i]).closest(".item").remove();
                        }
                    }


                }

                if (blockArr.length > 0){
                    //Вставляем отдельные выделенные пункты в конец в левой панели
                    $(blockArr.join("")).appendTo("#all_props_list");
                    blockArr = [];
                }
                //$self.closest(parentClass).show();
                //$($selected[i]).closest(".item").remove();
            }

            /*$selected.closest(".item").appendTo("#all_props_list")
                .find(".rename, .rename_done, .drag_handler, .required, .unique").remove();*/
            $("#all_props_list input:checked, #reg_props_list input:checked").prop("checked", false);
            $("#all_props_list input[type=hidden]").attr("name", "props[]");
            el_registry.getPropsInRegistry();
            el_registry.bindShowBlock();
        });

        $(".block_check").on("change click", function (){
            let $children = $(this).closest(".item").find("[type=checkbox]");
            $children.prop("checked", $(this).prop("checked"));
        });

        $("#addProps").off("click").on("click", function () {
            el_app.dialog_open("prop_create", "", "checklists/props");
        });

        $(".link a").off("click").on("click", async function (e) {
            let link = $(this).attr("href");
            if (link !== '' && link !== '/' && link !== '#') {
                e.preventDefault();
                let linkArr = link.split('/?');
                el_app.setMainContent('/checklists', linkArr[1]);
                return false;
            }else{
                await alert('Раздел ещё не создан');
            }
        });

        $("#registry_list select").off("change").on("change", function(){
            let params = (parseInt($(this).val()) > 0) ? "id=" + parseInt($(this).val()) : "";
            el_app.setMainContent('/checklists', params);
        });

        $("#user_list select").on("change", function(){
            let params = (parseInt($(this).val()) > 0) ? "id=" + parseInt($(this).val()) : "";
            el_app.setMainContent('/checklists', params);
        });

        $(document).on("content_load", function (){
            if (el_tools.function_exists("el_registry.getAllPropsInCreateRegistry")) {
                el_registry.getAllPropsInCreateRegistry();
            }
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
            el_tools.translateWithGoogle($(this).val(), "checklists").
            then(r => $("input[name=table_name]").val(r)) ;
        });
        $("#prop_create [name=prop_name]").on("blur", function (){
            el_tools.translateWithGoogle($(this).val(), "checklists").
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



        $("#all_props_list .el_data .add_item, #addItem").off("click").on("click", function () {
            el_app.dialog_open("prop_create", $(this).closest(".el_data").data("id"), "checklists/items");
        });

        el_registry.bindPassword();
        el_registry.bindLoginAlias();
        el_registry.getPropsInRegistry();
        el_registry.bindShowBlock();
        el_app.sort_init();
        el_app.filter_init();
    },

    bindShowBlock: function (){
        $("#all_props_list .el_data span.show_block, #reg_props_list .el_data span.show_block")
            .off("click").on("click", function (){
            let id = $(this).data("id"),
                icon = $(this).text(),
                $items = $("[data-parent="+id+"]:not(.wblocks)");
            if (icon === "expand_more"){
                $items.hide();
                $(this).text("chevron_right").attr("title", "Развернуть");
                el_tools.setcookie("rowItem" + id, "close");
            }else{
                $items.show();
                $(this).text("expand_more").attr("title", "Свернуть");
                el_tools.setcookie("rowItem" + id, "open");
            }
        });
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

    getPropsInRegistry: function (){
        //получить итоговый список полей, добавить к каждому пункту иконки редактроования и перетаскивания,
        // и получившийся список внести в переменную для сохранения
        let $items = $("#reg_props_list .el_data"),
            blockNumber = 1;
        $items.find(".rename, .rename_done, .drag_handler, .required, .unique, .add_item").remove();

        for (let i = 0; i < $items.length; i++){
            $($items[i]).append('<span class="material-icons drag_handler" title="Переместить">drag_handle</span>');
            $($items[i]).find(".blockNumber").text(blockNumber);
            let $children = $($items[i]).find(".el_data");
            if ($children.length > 0){
                let itemNumber = 0;
                for (let c = 0; c < $children.length; c++ ){
                    let itemNumberStr = parseInt(itemNumber) > 0 ? itemNumber : "";
                    $($children[c]).find(".itemNumber").text(blockNumber + "." + itemNumberStr);
                    itemNumber++;
                }
            }
            if ($($items[i]).hasClass("block_data")){
                blockNumber++;
            }

        }
        el_registry.setNewRegistryData();

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
            disableParentChange: true,
            stop: function (event, ui) {
                el_registry.setNewRegistryData();
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
        let $items = $("#reg_props_list [type=checkbox]"),
            regId = parseInt($("[name=reg_id]").val()),
            editedPosition = 0;
        newRegistryData = [];
        if ($items.length > 0) {
            for (let i = 0; i < $items.length; i++) {
                newRegistryData.push({
                    id: $($items[i]).attr("id").replaceAll("prop", ""),
                    rowBehaviour: $($items[i]).closest("li").attr("data-row-behaviour"),
                    is_block: $($items[i]).closest(".el_data").hasClass("block_data") ? 1 : 0,
                    parent_id: $($items[i]).closest("li").data("parent"),
                    sort: i
                });
            }
            $.post("/", {ajax: 1, action: "buildForm", props: newRegistryData, regId: regId, mode: "edit"}, function (data){
                $("#tab_form-panel").html(data);
                $("select:not(.viewmode-select)").chosen();
                $("#tab_form-panel [required]").removeAttr("required");

                $(".behaviour").off("click").on("click", function (){
                    let $self = $(this),
                        fieldId = $self.data("id");

                    $.post("/", {
                        ajax: 1,
                        action: "inspector",
                        field: fieldId,
                        regData: $("[name=reg_prop]").val()
                    }, function (data){
                        let $editedItem = $self.closest("li"),
                            $tab_form_panel = $("#tab_form-panel"),
                            editedPosition = $editedItem.position().top,
                            $behaviour = $("#behaviour #inspector"),
                            behaviourPosition = editedPosition - 200 < 0 ? 0 : editedPosition - 200;

                        $behaviour.html(data);
                        $("#behaviour").css("top", behaviourPosition);
                        $(".checklist li:not(.blockName)").css("background", "none");
                        $tab_form_panel.addClass("showInspector");
                        $editedItem.css("background", "#ecf6fa");

                        el_registry.inspectorInit(fieldId);
                    })
                });
                $("input[type=tel]").mask('+7 (999) 999-99-99');
                $("#behaviour .close").off("click").on("click", function (){
                    el_registry.hideInspector();
                });
                el_registry.bindTipsy();
                $(".checklist").nestedSortable({
                    axis: "y",
                    cursor: "grabbing",
                    listType: "ol",
                    handle: ".drag_handler",
                    items: "li",
                    stop: function (event, ui) {
                        el_registry.setNewRegistryData();
                    }
                });
            });

            $("[name=reg_prop]").val(JSON.stringify(newRegistryData));
        }else{
            $("[name=reg_prop]").val("");
        }
    },

    inspectorInit: function (fieldId){
        let current_behaviour = $(".checklist li[data-id=" + fieldId + "]").data("row-behaviour");

        if (typeof current_behaviour !== "undefined"){
            if (!current_behaviour.visible) {
                $("[name=elem_visible]").prop("checked", true).trigger("change");
                $("#selectField, #fromSelectType").removeClass("hidden");
                $("select[name=parentField]").val(current_behaviour.parentField).trigger("change");
                $("[name=any_values][value=" + current_behaviour.valuesType + "]").prop("checked", true).trigger("change");
                if (current_behaviour.valuesType === '0'){
                    $("#fromSelectValues").removeClass("hidden");
                    setTimeout(function (){$("[name=parentFieldItems]").val(current_behaviour.values).trigger("chosen:updated");}, 300);
                }
            }
        }

        $("#inspector select").chosen();

        $("[name=elem_visible]").off("change").on("change", function (){
            if ($(this).prop("checked")){
                $("#selectField").removeClass("hidden");
                $(".apply").attr("disabled", false);
            }else{
                $("#selectField, #fromSelectType").addClass("hidden");
                //$(".apply").attr("disabled", true);
            }

        });

        $("select[name=parentField]").off("change").on("change", function (){
            let index = $(this).chosen()[0].selectedIndex,
                fieldType = $(this).chosen()[0][index].dataset.type,
                fieldId = $(this).val();

            switch (fieldType){
                case 'radio':
                case 'checkbox':
                case 'list_fromdb':
                case 'list_fromdb_multi':
                case 'select':
                case 'multiselect':
                    $("#fromSelectType").removeClass("hidden");
                    $("input[name=any_values]").off("change").on("change", function (){
                        if ($(this).val() === '0'){
                            $.post("/", {
                                ajax: 1,
                                action: "getOptionsByField",
                                table: "checkitems",
                                fieldId: fieldId
                            }, function (data){
                                $("[name=parentFieldItems]").html(data).trigger("chosen:updated");
                                $("#fromSelectValues").removeClass("hidden");
                            });

                        }
                    })
                    break;
                    default:
                        $("#fromSelectValues").addClass("hidden");

            }
        });

        $("#inspector .apply").off("click").on("click", function (e){
            e.preventDefault();

            if ($("#inspector [name=elem_visible]").prop("checked")) {
                let rowBehaviour = {
                    visible: !$("#inspector [name=elem_visible]").prop("checked"),
                    parentField: $("#inspector [name=parentField]").val(),
                    valuesType: $("#inspector [name=any_values]:checked").val(),
                    values: $("[name=parentFieldItems]").val()
                };
                for (let i = 0; i < newRegistryData.length; i++){
                    if (parseInt(newRegistryData[i].id) === parseInt(fieldId)){
                        newRegistryData[i].rowBehaviour = JSON.stringify(rowBehaviour);
                    }
                }
                $(".checklist li[data-id=" + fieldId + "]").attr("data-row-behaviour", JSON.stringify(rowBehaviour));
                $(".checklist .behaviour[data-id=" + fieldId + "]").addClass("assigned").attr("title", "Поведение настроено");
            }else{
                for (let i = 0; i < newRegistryData.length; i++){
                    if (parseInt(newRegistryData[i].id) === parseInt(fieldId)){
                        delete newRegistryData[i].rowBehaviour;
                    }
                }
                $(".checklist li[data-id=" + fieldId + "]").removeAttr("data-row-behaviour");
                $(".checklist .behaviour[data-id=" + fieldId + "]").removeClass("assigned").attr("title", "Настроить поведение");
            }
            $("[name=reg_prop]").val(JSON.stringify(newRegistryData));
            el_registry.hideInspector();
            el_app.checklistInit();
        });
    },

    hideInspector: function (){
        $("#behaviour #inspector").html("");
        $(".checklist li:not(.blockName)").css("background", "none");
        $("#tab_form-panel").removeClass("showInspector");
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

    scrollToLastInstitution: function(){
        $(".pop_up").animate({
            scrollTop: $(".pop_up").scrollTop() + 1000
        }, 500);
    }
};
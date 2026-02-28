$(document).ready(function(){
    el_registry.create_init();
    //el_app.mainInit();
});

var el_registry = {
    //Инициализация контролов в разделе "Роли"
    create_init: function(){

        $("#button_nav_create:not(.disabled)").off("click").on("click", function () {
            let regId = $("#registry_id").val();
            el_app.dialog_open("registry_create", regId);
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
        $("#button_nav_reg_settings").off("click").on("click", function (){
            el_app.dialog_open("registry_edit", "34", "registry");
        });
        $(".link a").off("click").on("click", function (e) {
            el_app.setMainContent('/registry');
            return false;
        });

        $("#eais_import").off("click").on("click", function(){
            $(".preloader").fadeIn('fast');
            $.post("/", {ajax: 1, action: "importFromEAIS"}, function (data){
                let answer = JSON.parse(data);
                if(answer.result){
                    el_tools.notify('success', 'Отлично!', answer.resultText);
                    el_app.reloadMainContent();
                }else{
                    el_tools.notify('error', 'Ошибка', answer.resultText);
                }
                $(".preloader").fadeOut('fast');
            })
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

    bindSetOrgByType: function (){

        $("select[name='check_types[]']").off("change").on("change", function (){
            let $parent = $(this).closest(".institutions"),
                $inst = $parent.find("select[name='institutions[]']"),
                $instSelected = $parent.find("input[name='institutions_hidden']");
            $inst.html("").trigger("chosen:updated");
            $.post("/", {ajax: 1, action: "getOrgByCheckType", check_type: $(this).val(), selected: $instSelected.val()},
                function (data){
                    $inst.html(data).trigger("chosen:updated");
                });
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
                $(".pop_up_body .new_section").off("click").on("click", function(e){
                    e.preventDefault();
                    el_registry.cloneSection();
                });
                $("input[type=tel]").mask('+7 (999) 999-99-99');
                el_registry.bindDadata();
                el_registry.bindSetOrgByType($(".institutions:last"));
                el_registry.bindSetMinistriesByOrg($(".institutions:last"));
                el_registry.bindSetUnitsByOrg($(".institutions:last"));
                el_registry.bindSetUserByUnit($(".institutions:last"));
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

    bindDadata: function (){
        $("input[name=inn]").suggestions({
            token: "eb83a00ad060d6cca3d2341f2acb15cdb76b67df",
            type: "PARTY",
            /* Вызывается, когда пользователь выбирает одну из подсказок */
            onSelect: function(suggestion) {
                console.log(suggestion);
                //$("input[name=name]").val(suggestion.data.name.full_with_opf);
                //$("input[name=short]").val(suggestion.data.name.short_with_opf);
                $("input[name=inn]").val(suggestion.data.inn);
                $("input[name=kpp]").val(suggestion.data.kpp);
                $("textarea[name=legal]").val(suggestion.data.address.unrestricted_value);
                $("textarea[name=email]").val(suggestion.data.emails)
                $("select[name=orgtype] option").filter(function() {
                    return $(this).text() === suggestion.data.opf.full;
                    //return $(this).text().includes(suggestion.data.opf.full);
                }).prop('selected', true);
                $("select[name=orgtype]").trigger('chosen:updated');
                $("input[name=geo_lat]").val(suggestion.data.address.data.geo_lat);
                $("input[name=geo_lon]").val(suggestion.data.address.data.geo_lon);
                el_registry.getDataByINN(suggestion.data.inn);
            }
        });
    },

    getDataByINN: function(inn){
        /*$.post("/", {ajax: 1, action: "itsoft", inn: inn}, function (data){
            console.log(data);
        })*/
        function readTextFile(file, callback) {
            var rawFile = new XMLHttpRequest();
            rawFile.overrideMimeType("application/json");
            rawFile.open("GET", file, true);
            rawFile.onreadystatechange = function() {

                if (rawFile.readyState === 4 && rawFile.status == "200") {
                    callback(rawFile.responseText);
                }
            }
            rawFile.send(null);
        }

        readTextFile('https://egrul.itsoft.ru/' + inn + '.json',
            function(text){
                var data = JSON.parse(text);
                console.log(data);
                if (typeof data.СвЮЛ.СведДолжнФЛ != "undefined") {
                    let headFIO = data.СвЮЛ.СведДолжнФЛ.СвФЛ["@attributes"].Фамилия + " "
                        + data.СвЮЛ.СведДолжнФЛ.СвФЛ["@attributes"].Имя + " "
                        + data.СвЮЛ.СведДолжнФЛ.СвФЛ["@attributes"].Отчество;
                    $("input[name=leader]").val(el_tools.formatFIO(headFIO));
                }
                $("select[name=orgtype]").val(data.СвЮЛ["@attributes"].КодОПФ).trigger("chosen:updated");
                if (typeof data.СвЮЛ.СвАдрЭлПочты != "undefined") {
                    $("input[name=email]").val(data.СвЮЛ.СвАдрЭлПочты["@attributes"]["E-mail"].toLowerCase());
                }
                if (typeof data.СвЮЛ.СвНаимЮЛ.СвНаимЮЛСокр != "undefined"){
                    $("input[name=short]").val(data.СвЮЛ.СвНаимЮЛ.СвНаимЮЛСокр["@attributes"].НаимСокр);
                }

            });
    }
};
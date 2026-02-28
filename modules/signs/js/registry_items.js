$(document).ready(function(){
    el_registry.create_item_init();
    el_app.mainInit();
});

var el_registry = {
    //Инициализация контролов в разделе "Роли"
    create_item_init: function(){

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
        $("#button_nav_registry").on("click", function (){
            el_app.setMainContent('/registry');
        });

        $("#reg_import").off("click").on("click", function(){
            let vars = el_tools.getUrlVar(location.search),
                regId = vars.id;
            el_app.dialog_open("registry_items_import", regId);
        });

        $(".selectFile").off("click").on("click", function (e){
            e.preventDefault();
            $("#selectFileCSV").trigger("click");
        });

        $("#selectFileCSV").on('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                $("#uploadStatus").html("Выбран файл: &laquo;" + file.name + "&raquo;");
                el_registry.showLoadingIndicator();
                el_registry.uploadFile(file).then(r => console.log(r));
            }
        });

        $(".link a").off("click").on("click", function (e) {
            el_app.setMainContent('/registry');
            return false;
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


        el_registry.bindDadata();
        el_app.sort_init();
        el_app.filter_init();
    },

    showLoadingIndicator: function () {
        $("#loading-indicator").show();
    },

    hideLoadingIndicator: function () {
        $("#loading-indicator").hide();
    },

    uploadFile: async function(file) {
        try {
            const formData = new FormData();
            formData.append('excelFile', file);
            formData.append('parent', $("input[name=reg_id]").val());

            const response = await fetch('/modules/registry/ajaxHandlers/upload-excel.php', {
                method: 'POST',
                body: formData,
                onUploadProgress: (event) => {
                    if (event.lengthComputable) {
                        const percent = (event.loaded / event.total) * 100;
                        el_registry.updateProgressBar(percent);
                    }
                }
            });

            if (response.ok) {
                const result = await response.json();
                el_registry.hideLoadingIndicator();
                if (result.result) {
                    $("#importFields").html(result.resultHtml);
                    $("#importFields select").chosen();
                    $("#uploadButton").hide();
                    $("#importButton").css("display", "flex");
                    $("#importButton button").attr("disabled", false);
                }else{
                    $("#result").html(`<div style="color: red;">' + result.resultText.errors.join('<br>') + '</div>`);
                }
                console.log(result);
            } else {
                alert('Ошибка при загрузке файла');
            }
        } catch (error) {
            el_registry.hideLoadingIndicator();
            $("#result").html(`<div style="color: red;">Ошибка: ${error.message}</div>`);
        }
    },

    updateProgressBar: function(percent) {
    const $progressBar = $('.progress-bar');
        $progressBar.css("width", `${percent}%`);
    $progressBar.attr('aria-valuenow', percent);
},

    bindDadata: function (){
        $("input[name=inn]").suggestions({
            token: "eb83a00ad060d6cca3d2341f2acb15cdb76b67df",
            type: "PARTY",
            /* Вызывается, когда пользователь выбирает одну из подсказок */
            onSelect: function(suggestion) {
                console.log(suggestion);
                $("input[name=name]").val((typeof suggestion.data.name.full_with_opf != null ? suggestion.data.name.full_with_opf : ''));
                $("input[name=short]").val((typeof suggestion.data.name.short_with_opf != null ? suggestion.data.name.short_with_opf : ''));
                $("input[name=inn]").val((typeof suggestion.data.inn != null ? suggestion.data.inn : ''));
                $("input[name=kpp]").val((typeof suggestion.data.kpp != null ? suggestion.data.kpp : ''));
                $("input[name=leader]").val((suggestion.data.management != null ? suggestion.data.management.name : ''));
                $("textarea[name=legal]").val((typeof suggestion.data.address.unrestricted_value != null ? suggestion.data.address.unrestricted_value : ''));
                $("textarea[name=email]").val((typeof suggestion.data.emails != null ? suggestion.data.emails : ''))
                $("select[name=orgtype]").val((typeof suggestion.data.opf.code != null ? suggestion.data.opf.code : '')).trigger('chosen:updated');
            }
        });
    }
};

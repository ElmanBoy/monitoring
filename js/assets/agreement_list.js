var institutions_counter = 1;
var agreement_list = {
    approve_types: ['', 'Обычный', 'Срочный', 'Незамедлительно'],
    oneSignerDocumentacial: [1],
    signersCount: 0 || $(".sections.signers .agreement_list li").length,


    agreement_list_init: function () {
        let $ins = $(".sections");

        for (let i = 0; i < $ins.length; i++) {
            //el_app.bindSetMinistriesByOrg($($ins[i]));
            //el_app.bindSetUnitsByOrg($($ins[i]));
            //agreement_list.bindSetUserByUnit($($ins[i]));
            agreement_list.bindUsersChange($($ins[i]));
            agreement_list.bindRemove();

            $($ins[i]).find("select[name=agreementtemplate]").off("change").on("change", function () {
                $.post("/", {ajax: 1, action: "getDocTemplate", temp_id: $(this).val()}, function (data) {
                    let answer = JSON.parse(data),
                        agreementlist = JSON.parse(answer.agreementlist);
                    $($ins[i]).find("[name=brief]").val(answer.brief);
                    $($ins[i]).find("[name=initiator]").val(answer.initiator).trigger('chosen:updated');
                    $.post("/", {
                        ajax: 1,
                        action: "buildAgreement",
                        agreementlist: answer.agreementlist
                    }, function (data) {
                        $(".agreement_list_group").html(data);
                        el_app.mainInit();
                        agreement_list.agreement_list_init();
                    });
                });
            });

            $($ins[i]).find("input[name='urgent[]']").off("change").on("change", function () {
                $($ins[i]).find(".user_urgent").val($(this).val());
            });

            $($ins[i]).find("[name='stages[]'").off("change input").on("change input", function () {
                agreement_list.setAgreementList($($ins[i]));
            });

            $($ins[i]).find("[name='section_types[" + i + "]'], [name='urgent[" + i + "]']")
                .off("change").on("change", function () {
                    if ($(this).attr("name").includes("urgent")) {
                        let this_value = $(this).val(),
                            $urgent_type_select = $(this).closest(".item").next(".item").find("select.user_urgent"),
                            $new_signer = $(this).closest(".sections").find(".item.new_signer")
                                .find("[name='approve_types[]'][value='" + this_value + "']");
                        $urgent_type_select.val(this_value);
                        $new_signer.prop("checked", true);
                    }
                agreement_list.setAgreementList($($ins[i]));
            });

            /*$($ins[i]).find(".clear").off("click").on("click", function (e) {
                e.preventDefault();
                $(this).closest("li").remove();
                $($ins[i]).find(".add_agreement_message").text("");
                agreement_list.setAgreementList($($ins[i]));
            });*/
            agreement_list.bindRemoveUser($($ins[i]));

            $($ins[i]).find(".agreement_list").nestedSortable({
                axis: "y",
                cursor: "grabbing",
                listType: "ol",
                handle: ".drag_handler",
                items: "li",
                stop: function (event, ui) {
                    agreement_list.setAgreementList($($ins[i]));
                }
            });
            $($ins[i]).find('.agreement_list [title]').tipsy({
                arrowWidth: 10,
                cls: null,
                duration: 150,
                offset: 16,
                position: 'right'
            });
            //$($ins[i]).find("select[name='institutions[]']").trigger('change');
        }
        //$(".pop_up_body [name='approve_types[]']").closest(".item").hide();
        $(".pop_up_body .new_section").off("click").on("click", function (e) {
            e.preventDefault();
            agreement_list.cloneSection();
        });


        /*$("select[name='signators[]']").off("change").on("change", function (e, param){
            agreement_list.cloneSection();
        });*/
        $(".viewmode-select").chosen("destroy");
    },

    build_approve_type_list: function (user_id, selected){
        let html = '<select title="Срок согласования" name="urgent' + user_id + '" class="user_urgent viewmode-select">';
        for (let s = 1; s < agreement_list.approve_types.length; s++){
            let sel = selected === s ? ' selected="selected"' : '';
            html += '<option value="' + s + '"' + sel + '>' +  agreement_list.approve_types[s] + '</option>';
        }
        html += '</select>';

        return html;
    },

    cloneSection: function () {
        //let current_institutions = $(".pop_up_body select[name='institutions[]']").val();
        let st = $(".pop_up_body .sections:not(.signers):last")
                .find('input[type="radio"][name^="section_types["]:checked').val(),
            ur = $(".pop_up_body .sections:not(.signers):last")
                .find('input[type="radio"][name^="urgent["]:checked').val();

        //Разрушаем chosen для клонирования нативного select
        $(".pop_up_body .sections select").chosen("destroy");
        //Клонируем секцию, если она не "Подписанты"
        $(".pop_up_body .sections:not(.signers):last").clone().insertAfter(".pop_up_body .sections:not(.signers):last");
        //Инициализация chosen
        $(".pop_up_body .sections select:not(.viewmode-select)").chosen({
            search_contains: true,
            no_results_text: "Ничего не найдено."
        });
        //Очищаем список и скрытый input согласователей в новой секции
        $(".pop_up_body .sections:not(.signers):last .agreement_list").html("");
        $(".pop_up_body .sections:not(.signers):last [name='agreementlist[]']").val("");

        //Переназначаем номера секций
        agreement_list.setStageNumber();

        //$(".pop_up_body .sections:last select[name='institutions[]']").val(current_institutions).trigger("chosen:updated");
        //Перенициализация всех секций
        agreement_list.agreement_list_init();

        $(".pop_up_body .sections:not(.signers):last")
            .find('input[type="radio"][name^="section_types["]:checked').prop("checked", true);
        $(".pop_up_body .sections:not(.signers):last")
            .find('input[type="radio"][name^="section_types["]:checked').prop("checked", true);

        //$(".pop_up_body .sections:last [name='check_periods[]']").removeClass("flatpickr-input").attr("type", "date").next("input").remove();
        //Переназначаем номера секций
        /*$(".pop_up_body .sections:not(.signers):last [name='section_types[" + (institutions_counter - 1) + "]']")
            .attr("name", "section_types[" + institutions_counter + "]");*/
        institutions_counter++;
        //Добавляем кнопку "Удалить"
        if (institutions_counter > 1) {
            $(".pop_up_body .sections:not(.signers):last .section_number")
                .after('<div class="button icon clear"><span class="material-icons">close</span></div>');
            agreement_list.bindRemove();
        }

        let $institutions = $(".pop_up_body .sections:not(.signers)");
        for (let i = 0; i < $institutions.length; i++) {
            //if (!$($institutions[i]).hasClass("signers")) {
                $($institutions[i]).find(".section_number").text("Этап №" + (i + 1));
            //}
        }

        el_registry.bindTipsy();
        el_registry.bindCalendar();
        agreement_list.scrollToLastSection();
    },

    bindRemove: function () {
        $(".sections .button.icon.clear").off("click").on("click", function (e) {
            e.preventDefault();
            $(this).closest(".sections").remove();
            agreement_list.setStageNumber();
            institutions_counter--;
        });
    },

    bindRemoveUser: function (liObj){
        liObj.find(".clear").off("click").on("click", function (e) {
            e.preventDefault();
            if ($(this).closest(".sections").hasClass("signers") && agreement_list.signersCount > 0) {
                agreement_list.signersCount--;
            }
            $(this).closest("li").remove();
            liObj.find(".add_agreement_message").text("");
            agreement_list.setAgreementList(liObj);
        });
    },

    setStageNumber() {
        let $stages = $(".sections:not(.signers) input[name='stages[]']");
        //if ($($stages[0]).val() !== '') {
            for (let i = 0; i < $stages.length; i++) {
                if ($($stages[0]).val() !== '') {
                    $($stages[i]).val(i + 1);
                    $($stages[i]).closest(".sections").find(".section_number").text("Этап №" + (i + 1));

                    let st = $($stages[i]).closest(".sections")
                            .find('input[type="radio"][name="section_types[' + i + ']"]:checked').val(),
                    ur = $($stages[i]).closest(".sections")
                        .find('input[type="radio"][name="urgent[' + i + ']"]:checked').val();
                    //console.log(i, st,  ur);
                    /*$($stages[i]).closest(".sections").
                    find('input[type="radio"][name="section_types[' + (i - 1) + ']"][value="' + st + '"]').prop("checked", true);
                    $($stages[i]).closest(".sections").
                    find('input[type="radio"][name="urgent[' + (i - 1) + ']"][value="' + ur +'"]').prop("checked", true);*/
                    $($stages[i]).closest(".sections").
                    find('input[type="radio"][name="section_types[' + i + ']"][value="' + st + '"]').prop("checked", true);
                    $($stages[i]).closest(".sections").
                    find('input[type="radio"][name="urgent[' + i + ']"][value="' + ur +'"]').prop("checked", true);

                    $($stages[i]).closest(".sections").find('input[type="radio"][name^="section_types["]')
                        .attr("name", "section_types[" + i + "]");
                    $($stages[i]).closest(".sections").find('input[type="radio"][name^="urgent["]')
                        .attr("name", "urgent[" + i + "]");
                }
            }
        //}
    },

    scrollToLastSection: function () {
        $(".pop_up").animate({
            scrollTop: $(".pop_up").scrollTop() + 10000
        }, 500);
    },

    /*bindUsersChange: function(instanceObj){
        let $users = instanceObj.find("select[name='users[]']"),
            $new_signer = instanceObj.find(".new_signer"),
            $approve_types = instanceObj.find("[name='approve_types[]']"),
            $agreementlist = instanceObj.find("[name='agreementlist[]']"),
            $add_agreement_message = instanceObj.find(".add_agreement_message");
        $users.off("change").on("change", function () {

            $new_signer.off("click change").on("click change", function (e) {
                e.preventDefault();
                let $agreement_list = $(this).closest(".sections").find(".agreement_list");
                $agreement_list.append("<li data-id='" + $users.val() + "' data-type='2' data-urgent='"
                    + $(this).val() + "'>"
                    + $users.find('option:selected').html() +
                    '<span>' + agreement_list.approve_types[parseInt($(this).val()) - 1] + '</span>' +
                    "<span class='material-icons drag_handler' title='Переместить'>drag_handle</span>" +
                    "<span class='material-icons close' title='Удалить'>close</span></li>");

                agreement_list.setAgreementList(instanceObj);
                $(this).closest(".item").hide();

                $agreement_list.find(".close").off("click").on("click", function () {
                    $(this).closest("li").remove();
                    $add_agreement_message.text("");
                    agreement_list.setAgreementList(instanceObj);
                });

                $agreement_list.nestedSortable({
                    axis: "y",
                    cursor: "grabbing",
                    listType: "ol",
                    handle: ".drag_handler",
                    items: "li",
                    stop: function (event, ui) {
                        agreement_list.setAgreementList(instanceObj);
                        console.log(event, ui);
                    }
                });
                $('.agreement_list [title]').tipsy({
                    arrowWidth: 10,
                    cls: null,
                    duration: 150,
                    offset: 16,
                    position: 'right'
                });
            });

            let agArr = [];
            if ($agreementlist.val() !== "") {
                agArr = JSON.parse($agreementlist.val());
            }
            if (parseInt($(this).val()) > 0) {
                if (!agreement_list.findById(agArr, $users.val())) {
                    $add_agreement_message.text("");
                    $new_signer.closest(".item").show();
                } else {
                    $add_agreement_message.text("Этот сотрудник уже есть в списке.");
                    $new_signer.closest(".item").hide();
                }
            }else{
                $add_agreement_message.text("");
                $new_signer.closest(".item").hide();
            }
        }).trigger("change");
    },*/

    bindUsersChange: function(instanceObj){
        let $users = instanceObj.find("select[name^='users[']"),
            $new_signer = instanceObj.find(".new_signer"),
            $new_signer_button = instanceObj.find("button.new_signer"),
            $agreementlist = instanceObj.find("[name='agreementlist[]']"),
            $add_agreement_message = instanceObj.find(".add_agreement_message"),
            stage = instanceObj.find("[name='stages[]'").val(),
            userList = $("[name='users[]']").html(),
            documentacial = parseInt($("[name=documentacial]").val()),
            oneSignOnly = $.inArray(documentacial, agreement_list.oneSignerDocumentacial) > -1;

        userList = userList.replace(/&lt;br&gt;/g, "\n");

        $users.off("change").on("change", function () {

            $new_signer_button.off("click").on("click", function (e) {
                e.preventDefault();
                let $agreement_list = $(this).closest(".sections").find(".agreement_list"),
                    $approve_types = instanceObj.find("[name='approve_types[]']:checked"),
                    user_title = $users.find('option:selected').attr("title"),
                    approve_types = $approve_types.val() ||
                        $(this).closest(".sections").find("[name^='urgent[']:checked").val(),
                    sign_type = stage === "" ? 1 : 2;

                $agreement_list.append("<li data-id='" + $users.val() + "' data-type='" + sign_type + "' data-urgent='"
                    + approve_types + "'>"
                    + "<ruby title='" + user_title + "'>" + $users.find('option:selected').html() + "</ruby>" +
                    agreement_list.build_approve_type_list($users.val(), parseInt(approve_types)) +
                    "<select name='vrio" + $users.val() + "' title='Отсутствующий сотрудник' class='viewmode-select vrio'>" + userList + "</select>" +
                    (stage === "" ? "<select name='role" + $users.val() + "' title='Роль' class='viewmode-select role'>" +
                        (!oneSignOnly ? "<option></option><option value='0'>Утверждает</option>" : "") +
                        "<option value='1'>Подписывает</option></select>" : "") +
                    "<span class='material-icons drag_handler' title='Переместить'>drag_handle</span>" +
                    "<span class='material-icons clear' title='Удалить'>close</span></li>");
                if (stage === "" && oneSignOnly){
                    agreement_list.signersCount++;
                    let ministryHead = "Министр социального развития Московской области",
                        $last_vrio = $("select.vrio:last"); console.log($last_vrio)
                    if (user_title.indexOf(ministryHead) === -1){
                        $last_vrio.val($last_vrio.find('option[title*="' + ministryHead + '"]').val());
                    }
                }

                //agreement_list.approve_types[parseInt(approve_types) - 1]
                agreement_list.setAgreementList(instanceObj);
                //$(this).closest(".item").hide();
                $new_signer.hide();

                /*$agreement_list.find(".clear").off("click").on("click", function (e) {
                    e.preventDefault();
                    $(this).closest("li").remove();
                    $add_agreement_message.text("");
                    agreement_list.setAgreementList(instanceObj);
                });*/
                agreement_list.bindRemoveUser(instanceObj);

                $agreement_list.nestedSortable({
                    axis: "y",
                    cursor: "grabbing",
                    listType: "ol",
                    handle: ".drag_handler",
                    items: "li",
                    stop: function (event, ui) {
                        agreement_list.setAgreementList(instanceObj);
                        console.log(event, ui);
                    }
                });

                $('.agreement_list [title]').tipsy({
                    arrowWidth: 10,
                    cls: null,
                    duration: 150,
                    offset: 16,
                    position: 'right'
                });

                $(".user_urgent, .vrio, .role").off("change").on("change", function (){
                    let $roles = $(".role"); console.log($roles);
                    if ($($roles[0]).val() === "0"){
                        $($roles[1]).val("1");
                    }else{
                        $($roles[1]).val("0");
                    }
                    agreement_list.setAgreementList(instanceObj);
                });
            });

            let agArr = [];
            if ($agreementlist.val() !== "") {
                try{
                    agArr = JSON.parse($agreementlist.val());
                }catch (e){ console.log(e); }
            }

            if (parseInt($(this).val()) > 0) {
                let signersCount = $(".sections.signers ruby").length;
                if (stage === "" && oneSignOnly && signersCount > 0) {
                    $add_agreement_message.text("Подписант в этом документе может быть только один.");
                    $new_signer.hide();
                }else if (!agreement_list.findById(agArr, $users.val())) {
                    $add_agreement_message.text("");
                    $new_signer.show();
                }else{
                    $add_agreement_message.text("Этот сотрудник уже есть в списке.");
                    $new_signer.hide();
                }
            }else{
                $add_agreement_message.text("");
                $new_signer.hide();
            }
        }).trigger("change");

        instanceObj.find(".user_urgent, .vrio, .role").off("change").on("change", function (){
            agreement_list.setAgreementList(instanceObj);
        });

        agreement_list.bindRoleChange();
    },

    bindRoleChange: function (){
        let $roles = $(".role");

        if ($roles.length > 1) {
            let $select1 = $($roles[0]),
                $select2 = $($roles[1]);
            const oppositeValues = {
                '0': '1',
                '1': '0'
            };

            $select1.on('change', function () {
                const value = $(this).val();
                if (value === '0' || value === '1') {
                    $select2.val(oppositeValues[value]);
                }
            });

            $select2.on('change', function () {
                const value = $(this).val();
                if (value === '0' || value === '1') {
                    $select1.val(oppositeValues[value]);
                }
            });
        }
    },

    bindSetUserByUnit: function (instanceObj) {
        /*let $inst = instanceObj.find("select[name='institutions[]']"),
            $ministries = instanceObj.find("select[name='ministries[]']"),
            $units = instanceObj.find("select[name='units[]']"),
            $users = instanceObj.find("select[name='users[]']"),
            $agreementlist = instanceObj.find("[name='agreementlist[]']"),
            $new_signer = instanceObj.find("[name='approve_types[]']"),
            $add_agreement_message = instanceObj.find(".add_agreement_message");
        $new_signer.hide();
        $units.off("change").on("change", function () {
            $users.html("").trigger("chosen:updated change");
            $.post("/", {
                ajax: 1, action: "getUsersByOrg", orgId: $inst.val(), ministriesId: $ministries.val(),
                unitsId: $units.val()
            }, function (data) {
                $users.html(data).trigger("chosen:updated").off("change").on("change", function () {
                    //let $new_signer = instanceObj.find("[name='approve_types[]']");//find(".new_signer"),
                    $new_signer.off("click change").on("click change", function (e) {
                        e.preventDefault();
                        let $agreement_list = $(this).closest(".sections").find(".agreement_list");
                        $agreement_list.append("<li data-id='" + $users.val() + "' data-type='2' data-urgent='"
                            + $(this).val() + "'>"
                            + $users.find('option:selected').html() +
                            '<span>' + agreement_list.approve_types[parseInt($(this).val()) - 1] + '</span>' +
                            "<span class='material-icons drag_handler' title='Переместить'>drag_handle</span>" +
                            "<span class='material-icons close' title='Удалить'>close</span></li>");

                        $ministries.val("").trigger("chosen:updated");
                        $units.val("").trigger("chosen:updated").trigger("change");

                        agreement_list.setAgreementList(instanceObj);
                        $(this).closest(".item").hide();

                        $agreement_list.find(".close").off("click").on("click", function () {
                            $(this).closest("li").remove();
                            $add_agreement_message.text("");
                            agreement_list.setAgreementList(instanceObj);
                        });

                        $agreement_list.nestedSortable({
                            axis: "y",
                            cursor: "grabbing",
                            listType: "ol",
                            handle: ".drag_handler",
                            items: "li",
                            stop: function (event, ui) {
                                agreement_list.setAgreementList(instanceObj);
                                console.log(event, ui);
                            }
                        });
                        $('.agreement_list [title]').tipsy({
                            arrowWidth: 10,
                            cls: null,
                            duration: 150,
                            offset: 16,
                            position: 'right'
                        });
                    });

                    let agArr = [];
                    if ($agreementlist.val() !== "") {
                        agArr = JSON.parse($agreementlist.val());
                    }
                    if ($(this).val() > 0) {
                        if (!agreement_list.findById(agArr, $users.val())) {
                            $add_agreement_message.text("");
                            $new_signer.closest(".item").show();
                        } else {
                            $add_agreement_message.text("Этот сотрудник уже есть в списке.");
                            $new_signer.closest(".item").hide();
                        }
                    }
                });
            });
        });*/
    },

    setAgreementList: function (instanceObj) {
        let $agreementlist = instanceObj.find("[name='agreementlist[]']"),
            $agreement_list = instanceObj.find(".agreement_list li"),
            agArr = [{
                stage: instanceObj.find("[name='stages[]'").val(),
                list_type: instanceObj.find("[name^='section_types[']:checked").val(),
                urgent: instanceObj.find("[name^='urgent[']:checked").val()
            }];

        for (let i = 0; i < $agreement_list.length; i++) {
            let user_id = $($agreement_list[i]).data("id");
            agArr.push({
                id: user_id,
                type: $($agreement_list[i]).data("type"),
                urgent: $($agreement_list[i]).find("select[name=urgent" + user_id + "]").val(),
                vrio: $($agreement_list[i]).find("select[name=vrio" + user_id + "]").val(),
                role: $($agreement_list[i]).find("select[name=role" + user_id + "]").val()
            });
        }
        $agreementlist.val($agreement_list.length > 0 ? JSON.stringify(agArr) : "");
    },

    findById: function (arr, id) {
        for (let i = 0; i < arr.length; i++) {
            if (parseInt(arr[i].id) === parseInt(id)) {
                return true;
            }
        }
        return false;
    }

}
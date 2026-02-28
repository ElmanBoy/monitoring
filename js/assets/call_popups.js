/* pop_up_data_create ******************************************* */
function pop_up_data_create() {
    el_app.dialog_open("oper_create");
}

/* pop_up_users_create ******************************************* */
function pop_up_users_create() {
    el_app.dialog_open("user_create");
}

/* pop_up_data_custom ******************************************* */
function pop_up_data_custom() {
    el_app.dialog_open("view_settings");
}

/* pop_up_users_custom ******************************************* */
function pop_up_users_custom() {
    el_app.dialog_open("view_settings");
}

/* pop_up_data_filters ******************************************* */
function pop_up_data_filters() {
    el_app.dialog_open("oper_filters");
}

/* pop_up_data_notify ******************************************* */
function pop_up_notify() {
    el_tools.notify(true, "Внимание!", "Подтвердите действие",
        [
            {
                html: '<button class="button icon text close_button" type="button">' +
                    '<span class="material-icons">done</span>Подтверждаю</button>',
                name: ".close_button",
                handler: function () {
                    el_tools.notify_close()
                }
            },
            {
                html: '<button class="button icon text close_button" type="button">' +
                    '<span class="material-icons">block</span>Отмена</button>',
                name: ".close_button",
                handler: function () {
                    el_tools.notify_close()
                }
            }]);
}

/* pop_up_dir_operation ******************************************* */
function pop_up_dir_operation() {
    $("#pop_up_dir_operation").show().css('display', 'flex');
}

function pop_up_dir_operation_close() {
    el_app.dialog_open("oper_read");
};

/* pop_up_counteragent_create ******************************************* */
function pop_up_counteragent_create() {
    //$("#pop_up_counteragent_create").show().css('display', 'flex');
    el_app.dialog_open("counteragent_create");
}

/* pop_up_guide_create ******************************************* */
function pop_up_guide_create() {
    $("#pop_up_guide_create").show().css('display', 'flex');
    el_app.dialog_open("registry_create");
}

/* pop_up_guide_row_create ******************************************* */
function pop_up_guide_row_create() {
    $("#pop_up_guide_row_create").show().css('display', 'flex');
    el_app.dialog_open("registry_row_create");
}

/* pop_up_role_creat ******************************************* */
function pop_up_role_creat() {
    $("#pop_up_role_creat").show().css('display', 'flex');
    el_app.dialog_open("role_create");
}

$(".table_data tbody tr.temp td:not(:first-child)").on("click", function (e) {
    e.preventDefault();
    $("#pop_up_data_edit").show().css('display', 'flex');
    el_app.dialog_open("role_edit");
});

function pop_up_guide_row_edit_close() {
    $("#pop_up_guide_row_edit").hide();
    el_app.dialog_open("role_edit");
}

/* pop_up_dir_operation ******************************************* */
function pop_up_dir_operation() {
    $("#pop_up_dir_operation").show().css('display', 'flex');
    el_app.dialog_open("dir_operation");
}

/* pop_up_dir_guide ******************************************* */
function pop_up_dir_guide() {
    $("#pop_up_dir_guide").show().css('display', 'flex');
    el_app.dialog_open("dir_guide");
}

/* ********************************************************************************** */

/* for View and Edit ...pop_up_data_close ******************************************* */
function pop_up_data_view_close() {
    $("#pop_up_data_view").hide();
};

function pop_up_data_edit_close() {
    $("#pop_up_data_edit").hide();
};

function pop_up_users_edit_close() {
    $("#pop_up_users_edit").hide();
};


/* ********************************************************************************* */


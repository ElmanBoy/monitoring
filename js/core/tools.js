/**
 * @file Объект ядра с полезным инструментарием.
 */
/**
 * Переопределение нативного alert. Вместо него выводится стилизованное и одинаковое в разных браузерах
 * информационное диалоговое окно.
 * @name alert
 * @global
 * @function
 * @param message {string} Текст сообщения
 * @returns {Promise<event>}
 */
window.alert = (message) => {
    var PromiseAlert = el_tools.notify(true, 'Внимание!', message);
    return new Promise(function (resolve, reject) {
        PromiseAlert.on('alert_close', resolve);
    });
};

/**
 * Переопределение нативного confirm. Вместо него выводится стилизованное и одинаковое в разных браузерах
 * диалоговое окно запроса.
 * @name confirm
 * @global
 * @function
 * @param question {string} Текст вопроса
 * @returns {Promise<boolean>}
 */
window.confirm = (question) => {
    let confirm = false,
        PromiseConfirm = el_tools.notify(true, "Подтвердите действие", question,
            [
                {
                    html: '<button class="button icon text close_button success" type="button">' +
                        '<span class="material-icons">done</span>Да</button>',
                    name: ".close_button",
                    handler: function () {
                        $("#pop_up_notify").trigger("alert_confirmed");
                        el_tools.notify_close();
                        confirm = true;
                    }
                },
                {
                    html: '<button class="button icon text close_button fail" type="button">' +
                        '<span class="material-icons">block</span>Нет</button>',
                    name: ".close_button",
                    handler: function () {
                        $("#pop_up_notify").trigger("alert_reject");
                        el_tools.notify_close();
                        confirm = false;
                    }
                }]);

    $("#pop_up_notify .success").on('click', e => {
        confirm = true;
    });
    $("#pop_up_notify .fail, #pop_up_notify .close").on('click', e => {
        confirm = false;
    });
    return new Promise(function (resolve, reject) {
        PromiseConfirm.on('alert_close', (e) => {
            resolve(confirm);
        });
    });
};

function inform(title, text) {
    if (typeof bootstrap === "object"){
        $("#staticBackdropLabel").text(title);
        text = text.replace(/href = "(.*)"/, "href = \"index.html\"");
        $("#staticBackdrop .modal-body .mb-0").html(text);
        const modalWindow = new bootstrap.Modal("#staticBackdrop");
        modalWindow.show();
    }else {
        $.toast({
            text: text,
            heading: title,
            position: 'top-right',
            showHideTransition: 'fade',
            bgColor: '#263238',
            textColor: '#fff',
            loaderBg: '#78909c',
            hideAfter: 5000,
        });
    }
}

/**
 * Объект с часто употрибимыми методами (инструментарий)
 *
 * @global
 * @namespace el_tools
 * */
const el_tools = {
    uploadedFiles: "",
    notify_opened: false,
    /**
     * Записать значение куки
     * @function getcookie
     * @memberof el_tools
     * @param {string} name Имя параметра
     * @param {string} value Значение параметра
     * @param {Date} expires Дата окончания действия
     * @param {string} path Путь, по которому действует cookie
     * @param {string} domain Домен, в котором действует cookie
     * @param {bool} secure Доступ к cookie только по https
     */
    setcookie: function (name, value, expires, path, domain, secure) {
        // Если expires не передан или равен null, устанавливаем на год вперед
        if (!expires) {
            const now = new Date();
            now.setFullYear(now.getFullYear() + 1);
            expires = now;
        } else if (typeof expires === 'number') { // Если передан timestamp
            expires = new Date(expires);
        }

        // Проверяем, что expires является объектом Date
        if (!(expires instanceof Date)) {
            console.error('expires должен быть объектом Date или timestamp');
            return;
        }

        document.cookie = name + "=" + escape(value) +
            "; expires=" + expires.toUTCString() +
            ((path) ? "; path=" + path : "; path=/") +
            ((domain) ? "; domain=" + domain : "") +
            ((secure) ? "; secure" : "");
    },


    /**
     * Получить значение куки по имени параметра
     * @function getcookie
     * @memberof el_tools
     * @param {string} name Имя параметра
     */
    getcookie: function (name) {
        let cookie = " " + document.cookie;
        let search = " " + name + "=";
        let setStr = null;
        let offset = 0;
        let end = 0;

        if (cookie.length > 0) {
            offset = cookie.indexOf(search);

            if (offset !== -1) {
                offset += search.length;
                end = cookie.indexOf(";", offset)

                if (end === -1) {
                    end = cookie.length;
                }

                setStr = unescape(cookie.substring(offset, end));
            }
        }

        return (setStr);
    },

    /**
     * Очищает массив от дублей и возвращает уникализированный экземпляр
     *
     * @function array_unique
     * @memberof el_tools
     * @param a {Array} Массив до очистки
     * @returns {Array} Уникализированный массив
     */
    array_unique: function (a) {
        //let a = this.concat();
        for (let i = 0; i < a.length; ++i) {
            for (let j = i + 1; j < a.length; ++j) {
                if (a[i] === a[j])
                    a.splice(j--, 1);
            }
        }
        return a;
    },

    /**
     * Проверка на наличие элемента needle в массиве haystack
     *
     * @function in_array
     * @memberof el_tools
     * @param needle {*} Искомое значение
     * @param haystack {Array} Проверяемый массив
     * @param strict {boolean} Признак строгого соответсвия на тип данных
     * @returns {boolean} TRUE, если элемент присутствует, иначе FALSE
     */
    in_array: function (needle, haystack, strict) {
        let found = false, key, stricted = !!strict;
        for (key in haystack) {
            if (haystack.hasOwnProperty(key)) {
                if ((stricted && haystack[key] === needle) || (!stricted && haystack[key] == needle)) {
                    found = true;
                    break;
                }
            }
        }

        return found;
    },

    /**
     * Очищает массив от пустых значений
     *
     * @function array_clean
     * @memberof el_tools
     * @param a {Array} Очищаемый массив
     * @returns {Array} Очищенный массив
     */
    array_clean: function (a) {
        let out = [];
        for (let i = 0; i < a.length; ++i) {
            if (a[i].length > 0) {
                out.push(a[i]);
            }
        }
        return out;
    },

    /**
     * Объеденяет одноименные параметры в один и сливает их значения через символ |
     *
     * @function mergeDuplicateParams
     * @memberof el_tools
     * @param str {string} Входная строка
     * @returns {string} Преобразованная строка
     */
    mergeDuplicateParams(str) {
        if (str.length > 7) {
            const pairs = str.split('=');
            const params = (typeof pairs[1] !== "undefined") ? pairs[1].split(';') : str.split(';'); // разделяем строку на параметры
            const paramCounter = {}; // словарь для подсчета количества повторяющихся параметров
            const paramValues = {}; // словарь для хранения значений повторяющихся параметров

            params.forEach(param => {
                const [key, value] = param.split(':'); // разделяем ключ и значение параметра
                if (!paramCounter[key]) {
                    paramCounter[key] = 1;
                    paramValues[key] = value;
                } else {
                    paramCounter[key]++;
                    paramValues[key] = `${paramValues[key]}|${value}`;
                }
            });

            // формируем новую строку с объединенными значениями
            let result = [];
            Object.keys(paramCounter).forEach(key => {
                /*if (paramCounter[key] > 1) {
                    result += `${key}:${paramValues[key]};`;
                } else {
                    result += `${key}:${paramValues[key]};`;
                }*/
                result.push(`${key}:${paramValues[key]}`);
            });

            return pairs[0] + '=' + result.join(";");
        } else {
            return '';
        }
    },

    arrayItemCompare: function (field, order) {
        let len = arguments.length;
        if (len === 0) {
            return function (a, b) {
                return (a < b && -1) || (a > b && 1) || 0
            };
        }
        if (len === 1) {
            switch (typeof field) {
                case 'number':
                    return field < 0 ?
                        function (a, b) {
                            return (a < b && 1) || (a > b && -1) || 0
                        } :
                        function (a, b) {
                            return (a < b && -1) || (a > b && 1) || 0
                        };
                case 'string':
                    return function (a, b) {
                        return (a[field] < b[field] && -1) || (a[field] > b[field] && 1) || 0
                    };
            }
        }
        if (len === 2 && typeof order === 'number') {
            return order < 0 ?
                function (a, b) {
                    return ((a[field] < b[field] && 1) || (a[field] > b[field] && -1) || 0)
                } :
                function (a, b) {
                    return ((a[field] < b[field] && -1) || (a[field] > b[field] && 1) || 0)
                };
        }
        let fields, orders;
        if (typeof field === 'object') {
            fields = Object.getOwnPropertyNames(field);
            orders = fields.map(function (key) {
                return field[key]
            });
            len = fields.length;
        } else {
            fields = new Array(len);
            orders = new Array(len);
            for (let i = len; i--;) {
                fields[i] = arguments[i];
                orders[i] = 1;
            }
        }
        return function (a, b) {
            for (let i = 0; i < len; i++) {
                if (a[fields[i]] < b[fields[i]]) return orders[i];
                if (a[fields[i]] > b[fields[i]]) return -orders[i];
            }
            return 0;
        };
    },

    el_postfix: function (number, one, two, five) {
        number = typeof number != "undefined" ? parseInt(number) : 0;
        let out = '';
        if (number > 20) {
            let numArr = String(number);
            number = numArr[numArr.length - 1];
            out = el_tools.el_postfix(number, one, two, five);
        } else if (number === 1) {
            out = one;
        } else if (number > 1 && number < 5) {
            out = two;
        } else if (number >= 5 || number === 0) {
            out = five;
        }
        return out;
    },

    floatRound: function (num, presicion) {
        decimals = (presicion !== undefined) ? presicion : 1;
        return Number(Math.round(num + 'e' + decimals) + 'e-' + decimals);

    },

    addExportFrame: function (path) {
        let $ef = $("#export_frame");
        if (!$ef.is("iframe")) {
            $("body").after("<iframe id='export_frame' width='0' height='0' frameborder='0'></iframe>");
        }
        $ef.attr("src", path);
    },

    // использование Math.round() даст неравномерное распределение!
    getRandomInt: function (min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    },

    function_exists: function (function_name) {
        if (typeof function_name == 'string') {
            return (typeof window[function_name] == 'function');
        } else {
            return (function_name instanceof Function);
        }
    },

    genPass: function (maxChars) {
        let text = "";
        let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        for (let i = 0; i < maxChars; i++)
            text += possible.charAt(Math.floor(Math.random() * possible.length));

        return text;
    },

    copyStringToClipboard: function (str) {
        let el = document.createElement('textarea');
        el.value = str;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    },

    copyToClipboard: function (fieldId) {
        $(fieldId).focus().select();
        return document.execCommand('copy');
    },

    notify: function (result, title, html, buttons) {
        if (typeof bootstrap === "object"){
            $("#staticBackdropLabel").text(title);
            html = html.replace(/href = "(.*)"/, "href = \"index.html\"");
            $("#staticBackdrop .modal-body .mb-0").html(html);
            const modalWindow = new bootstrap.Modal("#staticBackdrop");
            modalWindow.show();
        }else {
            let buttonsHtml = '<button class="button icon text close_button success" type="submit" tabindex="0">' +
                '<span class="material-icons">done</span>OK</button>',
                b = [], bName = [], bHandler = [];

            if (!$("#pop_up_notify").is("div")) {
                if (typeof buttons != "undefined") {
                    for (let i = 0; i < buttons.length; i++) {
                        b[i] = buttons[i].html;
                        bHandler[i] = {name: buttons[i].name, handler: buttons[i].handler};
                    }
                    buttonsHtml = b.join("");
                }
                $("body").append('<div id="pop_up_notify" class="wrap_pop_up">' +
                    '<div class="pop_up drag notify ' + result + '" style="min-width: 30rem; width: 40rem"><div class="title handle">' +
                    '<div class="name">' + title + '</div>' +
                    '<div class="button icon close"><span class="material-icons">close</span></div></div>' +
                    '<div class="pop_up_body"><div class="group"><div class="item w_100">' + html + '</div>' +
                    '</div><div class="confirm">' + buttonsHtml + '</div></div></div>');
                $("#pop_up_notify .drag").draggable(({
                    handle: '.handle'
                }));
            }
            let $popup_notify = $("#pop_up_notify");

            $popup_notify.css("display", "flex");

            el_tools.notify_opened = true;
            $popup_notify.trigger("alert_open");

            $("#pop_up_notify .close, #pop_up_notify .close_button").on("click", function () {
                el_tools.notify_close();
            });


            $("#pop_up_notify .close_button.success").trigger("focus");

            $popup_notify.off("keydown").on("keydown", function (e) {
                switch (e.which) {
                    //Клавиша Enter
                    case 13:
                        $("#pop_up_notify .close_button.success").trigger("click");
                        break;
                    //Клавиша Escape
                    case 27:
                        $("#pop_up_notify .close_button.fail, #pop_up_notify .button.icon.close")
                            .trigger("click");
                        break;
                }
            });

            if (bHandler.length > 0) {
                for (let h = 0; h < bHandler.length; h++) {
                    let action = bHandler[h].handler;
                    $(bHandler[h].name).on("click", function () {
                        action();
                    });
                }
            }

            return $popup_notify;
        }
    },

    notify_close: function () {
        $("#pop_up_notify").fadeOut(300, function () {	// Выставляем таймер
            $("#pop_up_notify").trigger("alert_close").remove(); // Удаляем разметку всплывающего окна
            el_tools.notify_opened = false;
            el_app.select_table_row();
        });
    },

    initUpload: function (){
        let maxFileUploads = 20; // значение по умолчанию
        let maxFileSize = 10 * 1024 * 1024; // 10MB по умолчанию
        const fileDropZone = $('#fileDropZone');
        const fileInput = $('#fileInput');
        const fileList = $('#fileList');
        const uploadButton = $('#uploadButton');

        $("h1.upload_link").off("click").on("click", function() {
            let $self = $(this),
                $container = $(this).next(".upload-container");
            $container.slideToggle(200, function (){
                $self.find(".arrow").css("transform",
                    $container.is(":visible") ? "rotate(180deg)" : "rotate(0deg)")
            });
        });

        $(".attached_files .file_delete").off("click").on("click", function (){
            let file_id = $(this).closest("li").data("id");
            $.post("/", {ajax: 1, action: "delete_file", file_id: file_id}, function (data){
                let answer = JSON.parse(data);
                if(answer.result){
                    inform('Отлично!', answer.resultText);
                    $(".attached_files li[data-id='" + file_id + "']").remove();
                }else{
                    el_tools.notify('error', 'Ошибка', answer.resultText);
                }
            });
        });

        // Запрашиваем ограничения с сервера
        $.post("/", {ajax: 1, action: 'get_file_limits'}, function(response) {
            try {
                const limits = response;
                maxFileUploads = limits.maxFileUploads;
                maxFileSize = limits.maxFileSize;

                // Обновляем подсказку для пользователя
                $('.file-label').attr('title', `Максимум ${maxFileUploads} файлов, каждый до ${el_tools.formatFileSize(maxFileSize)}`);
            } catch (e) {
                console.error('Ошибка при получении ограничений:', e);
            }
        }).fail(function() {
            console.error('Не удалось получить ограничения с сервера');
        });

        // Обработчики для drop-зоны
        fileDropZone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('active');
        });

        fileDropZone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('active');
        });

        fileDropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('active');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFiles(files);
            }
        });

        // Обработчик изменения input файлов
        fileInput.change(function() {
            if (this.files.length > 0) {
                handleFiles(this.files);
            }
        });

        // Функция обработки файлов
        function handleFiles(files) {
            fileList.empty();

            // Проверка количества файлов
            if (files.length > maxFileUploads) {
                showStatusMessage(`Выбрано слишком много файлов. Максимум: ${maxFileUploads}`, 'error');
                fileInput.val(''); // очищаем input
                return;
            }

            // Проверка размера файлов
            let hasOversizedFiles = false;
            for (let file of files) {
                if (file.size > maxFileSize) {
                    hasOversizedFiles = true;
                    showStatusMessage(`Файл ${file.name} превышает максимальный размер (${el_tools.formatFileSize(maxFileSize)})`, 'error');
                }
            }

            if (hasOversizedFiles) {
                fileInput.val(''); // очищаем input
                return;
            }

            // Обновляем FileList в input (для формы)
            const dataTransfer = new DataTransfer();
            for (let i = 0; i < files.length; i++) {
                dataTransfer.items.add(files[i]);
            }
            fileInput[0].files = dataTransfer.files;

            // Отображаем файлы
            if (files.length > 0) {
                uploadButton.prop('disabled', false);

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileItem = $(`
                    <div class="file-item" data-index="${i}">
                        <div class="file-info">
                            <div class="file-name">${file.name}</div>
                            <div class="file-size">${el_tools.formatFileSize(file.size)}</div>
                        </div>
                        <input type="text" name="custom_names[]" class="file-custom-name" 
                               placeholder="Введите название для файла" 
                               value="${file.name.replace(/\.[^/.]+$/, '')}">
                        <button type="button" class="remove-file">×</button>
                    </div>
                `);

                    fileList.append(fileItem);
                    $(document).trigger("files_has_added");
                }
            } else {
                uploadButton.prop('disabled', true);
            }
        }

        // Удаление файла из списка
        $(document).off("click").on('click', '.remove-file', function() {
            const fileItem = $(this).closest('.file-item');
            const index = fileItem.data('index');

            // Удаляем файл из input
            const fileInput = $('#fileInput')[0];
            const files = Array.from(fileInput.files);
            files.splice(index, 1);

            // Создаем новый DataTransfer и добавляем оставшиеся файлы
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;

            // Удаляем элемент из списка
            fileItem.remove();

            // Обновляем индексы оставшихся элементов
            $('.file-item').each(function(newIndex) {
                $(this).data('index', newIndex);
            });

            // Если файлов не осталось, деактивируем кнопку загрузки
            if (fileInput.files.length === 0) {
                $('#uploadButton').prop('disabled', true);
                $(document).trigger("files_has_removed");
            }
        });

        // Отправка формы
        $('#uploadForm').submit(function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const uploadButton = $('#uploadButton');

            uploadButton.prop('disabled', true);
            uploadButton.text('Загрузка...');

            $.ajax({
                url: 'upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            showStatusMessage('Файлы успешно загружены!', 'success');
                            $('#fileList').empty();
                            $('#fileInput').val('');
                        } else {
                            showStatusMessage(result.message || 'Ошибка при загрузке файлов', 'error');
                        }
                    } catch (e) {
                        showStatusMessage('Ошибка обработки ответа сервера', 'error');
                    }
                },
                error: function() {
                    showStatusMessage('Ошибка соединения с сервером', 'error');
                },
                complete: function() {
                    uploadButton.prop('disabled', false);
                    uploadButton.text('Загрузить файлы');
                }
            });
        });



        // Функция для отображения статусного сообщения
        function showStatusMessage(message, type) {
            $('.status-message').remove();
            const messageDiv = $(`<div class="status-message ${type}">${message}</div>`);
            $('#uploadForm').append(messageDiv);
        }
    },

    // Функция для форматирования размера файла
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    initForms: function () {

        let $form = $("form.ajaxFrm");

        if ($form.find(".tab-pane").length > 0){
            $form.find("[required]").attr("required", false);
        }

        $form.off("submit").on("submit", function (e) {
            e.preventDefault();
            $(".preloader").fadeIn('fast');
            let form = $(this);
            form.find("[type=submit]").addClass("loading");
            //form.addClass("disabled");
            /*setTimeout(function () {
                form.find("button, input, select, textarea").attr("disabled", true).addClass("disabled");
            }, 500);*/
            let data = new FormData(form[0]);
            if (typeof uploadedFiles != "undefined" && uploadedFiles.length > 0) {
                $.each(uploadedFiles, function (key, value) {
                    data.append(key, value);
                });
            }

            data.append("ajax", "1");
            data.append("action", form.attr("id"));

            $.ajax({
                url: '/',
                type: 'POST',
                data: data,
                cache: false,
                dataType: 'json',
                headers: {
                    'X-Csrf-Token': el_tools.getcookie("CSRF-TOKEN"),
                    'X-Requested-With': "XMLHttpRequest"
                },
                processData: false,
                contentType: false,
                success: function (respond) {

                    if (typeof respond.error === 'undefined') {
                        if (respond.result === true) {
                            if (typeof respond.resultText != "undefined") {
                                inform("Отлично!", respond.resultText);
                                if (!form.hasClass("noreset")) {
                                    form.removeClass("disabled").trigger("reset");
                                }
                                form.find(".hide").html("");
                                if (typeof uploadedFiles != "undefined" && uploadedFiles.length > 0) {
                                    $("#attachZone .removeUpload").click();
                                }
                            }
                        } else {
                            if (typeof respond.resultText != "undefined") {
                                el_tools.notify("error", "Ошибка", respond.resultText);
                                if (typeof respond.errorFields != "undefined" && respond.errorFields !== []) {
                                    el_tools.highlightFields(respond.errorFields);
                                }
                            }
                        }
                        $(".preloader").fadeOut('fast');
                        form.find("[type=submit]").removeClass("loading");
                        //form.removeClass("disabled");
                        setTimeout(function () {
                            form.find("button, input, select, textarea").attr("disabled", false).removeClass("disabled");
                        }, 500);
                    } else {
                        console.log('ОШИБКИ ОТВЕТА сервера: ' + respond.error);
                    }
                },
                error: function (jqXHR, textStatus) {
                    console.log('ОШИБКИ AJAX запроса: ' + textStatus);
                    $(".preloader").fadeOut('fast');
                }
            });

            return false;
        });

        $("h1.expand_link").off("click").on("click", function () {
            let $self = $(this),
                $expandArea = $(this).next(".expandArea");
            $expandArea.slideToggle(200, function () {
                $self.find(".arrow").css("transform",
                    $expandArea.is(":visible") ? "rotate(180deg)" : "rotate(0deg)")
            });
        });
    },

    getCurrentDate: function () {
        let today = new Date();
        let dd = today.getDate();
        let mm = today.getMonth() + 1; //January is 0!
        let yyyy = today.getFullYear();

        if (dd < 10) {
            dd = "0" + dd
        }

        if (mm < 10) {
            mm = "0" + mm
        }

        return dd + "." + mm + "." + yyyy;
    },

    dateFormat: function (dateString) {
        if (typeof dateString != "undefined") {
            let date = new Date(dateString),
                getMont = parseInt(date.getMonth()) + 1,
                month = getMont < 10 ? "0" + getMont : getMont,
                day = date.getDate() < 10 ? "0" + date.getDate() : date.getDate();
            return date.getFullYear() + "-" + month + "-" + day;
        } else {
            return '';
        }
    },

    dateToString: function ($date) {
        if ($.trim($date).length > 0) {
            let $dateArr = [],
                $time = "",
                $dateString = "",
                $dateStringArr = [],
                $timeArr = [],
                $year = "",
                $month = "",
                $day = "",
                $mont = "";

            if ($date.indexOf(' ') > 0) {
                $dateArr = $date.split(' ');
                $dateString = $dateArr[0];
                $timeArr = $dateArr[1].split('.');
                $time = $timeArr[0];
            } else {
                $dateString = $date;
            }

            $dateStringArr = $dateString.split("-");
            $year = $dateStringArr[0];
            $month = $dateStringArr[1];
            $day = $dateStringArr[2];
            switch (parseInt($month)) {
                case 1:
                    $mont = "янв";
                    break;
                case 2:
                    $mont = "фев";
                    break;
                case 3:
                    $mont = "мар";
                    break;
                case 4:
                    $mont = "апр";
                    break;
                case 5:
                    $mont = "мая";
                    break;
                case 6:
                    $mont = "июн";
                    break;
                case 7:
                    $mont = "июл";
                    break;
                case 8:
                    $mont = "авг";
                    break;
                case 9:
                    $mont = "сен";
                    break;
                case 10:
                    $mont = "окт";
                    break;
                case 11:
                    $mont = "ноя";
                    break;
                case 12:
                    $mont = "дек";
                    break;
            }
            return $day + " " + $mont + " " + $year + "г. " + (($time.length > 0) ? $time : '');
        } else {
            return '';
        }
    },

    decodeHtml: function (text) {
        return text
            .replace(/&amp;/g, '&')
            .replace(/&lt;/, '<')
            .replace(/&gt;/, '>')
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'");
    },

    htmlspecialchars: function (str) {
        if (typeof (str) == "string") {
            str = str.replace(/&/g, "&amp;");
            /* must do &amp; first */
            str = str.replace(/"/g, "&quot;");
            str = str.replace(/'/g, "&#039;");
            str = str.replace(/</g, "&lt;");
            str = str.replace(/>/g, "&gt;");
        }
        return str;
    },

    strip_tags: function (str) {
        return str.replace(/<\/?[^>]+>/gi, '');
    },

    translit: function (word) {
        let answer = "", a = {};

        a["Ё"] = "YO";
        a["Й"] = "I";
        a["Ц"] = "TS";
        a["У"] = "U";
        a["К"] = "K";
        a["Е"] = "E";
        a["Н"] = "N";
        a["Г"] = "G";
        a["Ш"] = "SH";
        a["Щ"] = "SCH";
        a["З"] = "Z";
        a["Х"] = "H";
        a["Ъ"] = "'";
        a["ё"] = "yo";
        a["й"] = "i";
        a["ц"] = "ts";
        a["у"] = "u";
        a["к"] = "k";
        a["е"] = "e";
        a["н"] = "n";
        a["г"] = "g";
        a["ш"] = "sh";
        a["щ"] = "sch";
        a["з"] = "z";
        a["х"] = "h";
        a["ъ"] = "'";
        a["Ф"] = "F";
        a["Ы"] = "I";
        a["В"] = "V";
        a["А"] = "a";
        a["П"] = "P";
        a["Р"] = "R";
        a["О"] = "O";
        a["Л"] = "L";
        a["Д"] = "D";
        a["Ж"] = "ZH";
        a["Э"] = "E";
        a["ф"] = "f";
        a["ы"] = "i";
        a["в"] = "v";
        a["а"] = "a";
        a["п"] = "p";
        a["р"] = "r";
        a["о"] = "o";
        a["л"] = "l";
        a["д"] = "d";
        a["ж"] = "zh";
        a["э"] = "e";
        a["Я"] = "Ya";
        a["Ч"] = "CH";
        a["С"] = "S";
        a["М"] = "M";
        a["И"] = "I";
        a["Т"] = "T";
        a["Ь"] = "'";
        a["Б"] = "B";
        a["Ю"] = "YU";
        a["я"] = "ya";
        a["ч"] = "ch";
        a["с"] = "s";
        a["м"] = "m";
        a["и"] = "i";
        a["т"] = "t";
        a["ь"] = "'";
        a["б"] = "b";
        a["ю"] = "yu";
        a[" "] = "-";
        a[","] = "-";
        a["?"] = "";
        a["!"] = "";
        a["."] = "-";

        for (i = 0; i < word.length; ++i) {
            answer += a[word[i]] === undefined ? word[i] : a[word[i]];
        }
        return answer;
    },

    /*highlightFields: function (fieldNameArr) {
        $("input.error, select.error, textarea.error, button.error").removeClass("error");
        for (let i = 0; i < fieldNameArr.length; i++) {
            $("*[name='" + fieldNameArr[i] + "']").addClass("error");
        }
    },*/
    highlightFields: function(fieldNameArr) {
        $("input.error, select.error, textarea.error, button.error").removeClass("error");

        for (let i = 0; i < fieldNameArr.length; i++) {
            const fieldName = fieldNameArr[i];

            if (fieldName.endsWith('[]')) {
                // Подсветить все элементы массива (users[0], users[1], ...)
                const baseName = fieldName.slice(0, -2);
                $(`*[name^="${baseName}["], *[name="${baseName}[]"]`).addClass("error");
            } else {
                // Подсветить конкретное поле (включая users[1])
                $(`*[name="${fieldName}"]`).addClass("error");
            }
        }
    },

    getUrlVar: function (url) {
        // извлекаем строку из URL или объекта window
        let queryString = url ? url.split('?')[1] : window.location.search.slice(1);

        // объект для хранения параметров
        let obj = {};

        // если есть строка запроса
        if (queryString) {

            // данные после знака # будут опущены
            queryString = queryString.split('#')[0];

            // разделяем параметры
            let arr = queryString.split('&');

            for (let i = 0; i < arr.length; i++) {
                // разделяем параметр на ключ => значение
                let a = arr[i].split('=');

                // обработка данных вида: list[]=thing1&list[]=thing2
                let paramNum = undefined;
                let paramName = a[0].replace(/\[\d*\]/, function (v) {
                    paramNum = v.slice(1, -1);
                    return '';
                });

                // передача значения параметра ('true' если значение не задано)
                let paramValue = typeof (a[1]) === 'undefined' ? true : a[1];

                // преобразование регистра
                paramName = paramName.toLowerCase();
                if (typeof paramValue != "boolean")
                    paramValue = decodeURI(paramValue.toLowerCase());

                // если ключ параметра уже задан
                if (obj[paramName]) {
                    // преобразуем текущее значение в массив
                    if (typeof obj[paramName] === 'string') {
                        obj[paramName] = [obj[paramName]];
                    }
                    // если не задан индекс...
                    if (typeof paramNum === 'undefined') {
                        // помещаем значение в конец массива
                        obj[paramName].push(paramValue);
                    }
                    // если индекс задан...
                    else {
                        // размещаем элемент по заданному индексу
                        obj[paramName][paramNum] = paramValue;
                    }
                }
                // если параметр не задан, делаем это вручную
                else {
                    obj[paramName] = paramValue;
                }
            }
        }

        return obj;
    },

    getFilterParams: function() {
        const urlParams = new URLSearchParams(window.location.search);
        const filterParam = urlParams.get('filter');

        if (!filterParam) {
            return {};
        }

        const filterParams = {};

        // Разделяем фильтры по запятой (если их несколько)
        const filters = filterParam.split(',');

        filters.forEach(filter => {
            // Разделяем каждый фильтр на поле и значение
            const [field, value] = filter.split(':');

            if (field && value !== undefined) {
                // Убираем возможные пробелы и добавляем в объект
                filterParams[field.trim()] = value.trim();
            }
        });

        return filterParams;
    },

    controlPlusKey: function (targetKey, callback) {
        // добавляем событие нажатия клавиш на документ
        $(document).on('keydown', function (event) {
            //console.log(event.which)
            // если нажаты клавишы Cntrl и targetKey
            if ((event.ctrlKey || event.metaKey) && event.which === targetKey.charCodeAt(0)) {
                // запрещаем действие по умолчанию
                event.preventDefault();
                // вызываем наше действие
                callback();
            }
        });
    },

    ajaxFunction: async function () {
        let ajaxRequest;  // The variable that makes Ajax possible!

        try {
            // Opera 8.0+, Firefox, Safari (1st attempt)
            ajaxRequest = new XMLHttpRequest();
        } catch (e) {
            // IE browser (2nd attempt)
            try {
                ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    // 3rd attempt
                    ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {
                    await alert("AJAX не поддерживается, работа приложения невозможна. Обновите или смените браузер.");
                    return false;
                }
            }
        }
        //console.log("AJAX поддерживается");
        return true;
    },

    scrollToObj: function (objId) {
        let pos = $(objId).position().top;
        $("body,html").animate({"scrollTop": pos}, "slow");
    },

    validateLowercaseAlphanumeric: function (inputElement) {
        // Получаем текущее значение поля
        let value = $(inputElement).val();

        // Преобразуем все буквы в нижний регистр
        value = value.toLowerCase();

        // Удаляем все символы, кроме a-z и 0-9
        const cleanValue = value.replace(/[^a-z0-9]/g, '');

        // Устанавливаем очищенное значение обратно в поле
        $(inputElement).val(cleanValue);

        // Добавляем/удаляем класс для индикации ошибки
        if (cleanValue.length !== value.length) {
            $(inputElement).addClass('error');
            return false;
        } else {
            $(inputElement).removeClass('error');
            return true;
        }
    },

    translateWithGoogle: async function (russianText, tableName) {
        russianText = russianText.toLowerCase().trim();
        // Выделяем самое длинное последнее слово
        const words = russianText
            .split(/[^a-zа-яё]+/gi)
            .filter(w => w.trim().length > 0);

        if (words.length === 0) {
            //outputElement.value = '[Нет слов для перевода]';
            return;
        }

        const longestWord = words
            .slice()
            .reverse()
            .reduce((a, b) => a.length > b.length ? a : b);

        let fNamesArr = [];

        try {
            // 1. Делаем AJAX-запрос и ждем ответа (оборачиваем $.post в Promise)
            const fNamesArr = await new Promise((resolve, reject) => {
                $.post("/", {
                    ajax: 1,
                    action: "checkFieldName",
                    fieldName: longestWord,
                    tableName: tableName
                }, function(data) {
                    resolve(JSON.parse(data));
                }).fail(reject);
            });


            // 2. После получения данных делаем запрос к Google Translate
            const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=ru&tl=en&dt=t&q=${encodeURIComponent(longestWord)}`;
            const response = await fetch(url);
            const answer = await response.json();

            //Если такое название уже есть, запрашиваем перевод с предыдущим словом
            if (fNamesArr.indexOf(answer[0][0][0].toLowerCase().trim()) > -1 ){
                let lastWords = words.slice(-1)
                    .reverse()
                    .reduce((a, b) => a.length > b.length ? a : b);

                return el_tools.translateWithGoogle(lastWords + longestWord, tableName);
            }

            console.log("Перевод:", answer[0][0][0]);
            return answer[0][0][0].toLowerCase().trim().replace(" ", "")
                .replace("-", "") || "Введите английское название";
        } catch (error) {
            console.error("Ошибка:", error);
            return "Ошибка перевода";
        }
    },

    getEventListeners: function(element) {
        var results = {};
        var types = ['click', 'mouseover', 'mouseout', 'keypress', 'keydown', 'keyup', 'submit', 'change'];

        for (var i = 0; i < types.length; i++) {
            var type = types[i];
            var listeners = element.addEventListener ? element._eventListeners[type] : element.attachEvent ? element.eventListeners[type] : null;

            if (listeners) {
                results[type] = listeners;
            }
        }

        return results;
    },

    getEventHandlers: function (element){
        let events = jQuery._data(element, "events"),
            out = [];

        if (events) {
            for (var type in events) {
                console.log('Событие: ' + type);
                $.each(events[type], function(index, handler) {
                    out.push = {"event": type, "handler": handler.handler};
                    console.log('Обработчик: ' + handler.handler);
                });
            }
        }
        return out;
    },
    //Пример: el_tools.getEventHandlers($(".button.new_signer:last")[0])

    formatFIO: function(fio) {
        if (!fio || typeof fio !== 'string') {
            return '';
        }

        return fio
            .trim()
            .toLowerCase()
            .split(/\s+/)
            .map(word => {
                if (word.length > 0) {
                    return word.charAt(0).toUpperCase() + word.slice(1);
                }
                return word;
            })
            .join(' ');
    }
}
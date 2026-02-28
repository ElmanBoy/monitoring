// Service Worker Register 
var userId = 1;
var latitude = 0;
var longitude = 0;
var accuracy = 0;
var el_app = {
    checklistInit: function () {
    }
};
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}

// PWA Button Installation

let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
});

const installButton = document.getElementById('installAffan');
const installWrap = document.getElementById('installWrap');

if (installButton) {
    function updateInstallButton() {
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            installButton.textContent = 'Installed';
            installWrap.style.display = 'none';
        } else {
            installButton.textContent = 'Install Now';
            installWrap.style.display = 'block';
        }
    }

    installButton.addEventListener('click', async () => {
        if (installButton.textContent === 'Installed') {
            return;
        }

        if (deferredPrompt) {
            deferredPrompt.prompt();
            const {
                outcome
            } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                installButton.textContent = 'Installed';
                installWrap.style.display = 'none';
            } else {
                installButton.textContent = 'Install Now';
            }
            deferredPrompt = null;
        }
    });

    updateInstallButton();
    window.matchMedia('(display-mode: standalone)').addEventListener('change', updateInstallButton);
}

$(document).ready(function () {
    // Проверяем авторизацию при загрузке
    checkAuth();

    $("#logout").on("click", function (e) {
        e.preventDefault();
        logout();
    });

    // Обработка формы входа
    $('#loginForm').submit(function (e) {
        e.preventDefault();
        const login = $('#user').val();
        const password = $('#psw-input').val();

        $('#message').text('Проверка...').css('color', 'blue');

        $.post("/", {ajax: 1, action: "mobileLogin", user: login, password: password}, function (data) {
            let answer = JSON.parse(data);
            if (!answer.result) {
                inform("Ошибка", answer.resultText);
                $(".login-wrapper").css("display", "flex");
                $(".page-content-wrapper").hide();
            } else {
                userId = answer.userId;
                inform("Отлично!", answer.resultText);
                $(".login-wrapper").hide();
                $(".page-content-wrapper").show();
            }
        });



    });




    initTasks();
});

function initTasks(date = "") {
    let currDate = (date === "") ? new Date().toJSON().slice(0, 10) : date;
    $(".element-heading h6").text("Мои задачи на " + dateToString(currDate));
    $('.container .list-group').html("Нет задач");
    loadTasks(date).then(content => {
        //console.log('Содержимое файла:', content);
        $('.container .list-group').html(content);

        $(".list-group").off("click").on("click", function () {
            let task_id = $(this).find("a").data("id");
            loadTask(task_id).then(content => {
                //console.log('Содержимое файла:', content);
                let html = '<form method="post" id="save_result" onsubmit="return false">' +
                    '<button type="button" class="btn btn-info position-absolute top-0" style="top: 27px !important;z-index:1000; display: none" ' +
                    'id="checkin">Нахожусь на объекте, начинаю выполнение</button>' +
                    '<button type="button" ' +
                    'class="btn btn-success position-absolute top-0" style="top: 27px !important;z-index:1000" ' +
                    'id="takeit">Взять в работу</button>' +
                    '<input type="hidden" name="datetime" id="datetimeInpt">' +
                    '<input type="hidden" name="latitude" id="latitudeInpt">' +
                    '<input type="hidden" name="longitude" id="longitudeInpt">' +
                    '<input type="hidden" name="task_id" id="task_id" value="' + task_id + '">' +
                    '<span id="dateTimeStr" style="position:absolute; top: 40px !important;"></span>' +
                    content + '<div class="d-grid gap-2 d-md-flex justify-content-md-center m-4">' +
                    '<button type="submit" class="btn btn-primary" ' +
                    'id="submitBtn">Отправить результат</button></div></form>';
                $('#affanOffcanvas .sidenav-wrapper').html(html);
                $("#openlayers-container").hide();
                $("#affanNavbarToggler").trigger("click");
                $("#save_result *:not(#takeit, #checkin)").attr("disabled", true);
                $("#takeit").off("click").on("click", function () {
                    takeIt();
                });
            }).catch(error => {
                console.log('Ошибка:', error);
                //alert(error.message);
            });
        });
    }).catch(error => {
        console.log('Ошибка:', error);
        //alert(error.message);
    });
}

function takeIt() {
    $(".offcanvas-body").scrollTop(0);
    if (!navigator.geolocation) {
        $('#status').text('Геолокация не поддерживается вашим браузером');
    } else {
        navigator.geolocation.getCurrentPosition(
            successCallback,
            errorCallback,
            {enableHighAccuracy: true, timeout: 5000, maximumAge: 0}
        );
    }

    // Инициализация карты с центром по умолчанию (Москва), если не удалось получить местоположение
    ymaps.ready(function () {
        new ymaps.Map('map', {
            center: [geo_lat, geo_lon], // Координаты Москвы
            zoom: 15,
            controls: ['zoomControl']
        }).geoObjects.add(
            new ymaps.Placemark([geo_lat, geo_lon], {
                balloonContent: orgNameShort,
                iconCaption: orgName
            }, {
                preset: 'islands#blueDotIconWithCaption'
            })
        );
    });

    $("#takeit").hide();
    $("#checkin").show().off("click").on("click", function (){
        let currentDateTime = getCurrentDateTimeIntl();
        $(this).hide();
        $("#save_result *").attr("disabled", false);
        $("#datetimeInpt").val(currentDateTime);
        $("#dateTimeStr").text('Дата и время прибытия: ' + currentDateTime);
        $("#latitudeInpt").val(latitude);
        $("#longitudeInpt").val(longitude);
        $("#task_id").val()
        bindForm();
    });
    $("#openlayers-container").show();
}

async function loadTasks(date = "") {
    let currDate = (date === "") ? new Date().toJSON().slice(0, 10) : date,
        errorStr = "";
    try {
        const response = await fetch(`/cache/tasks/${userId}/${currDate}.html`, {
            headers: {
                'Accept': 'text/html',
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.text();
    } catch (error) {
        console.error('Ошибка загрузки HTML:', error);
        throw error;
    }
}

async function loadTask(number) {

    let errorStr = "";
    try {
        const response = await fetch(`/cache/tasks/${userId}/${number}.html`, {
            headers: {
                'Accept': 'text/html',
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.text();
    } catch (error) {
        console.error('Ошибка загрузки HTML:', error);
        throw error;
    }
}

function checkAuth() {

    if ($(".login-wrapper").css("display") !== "block") {
        $.post("/", {ajax: 1, action: "mobileCheckAuth"}, function (data) {
            if (data.includes("Ваша сессия устарела.")) {
                $(".login-wrapper").show();
                $(".page-content-wrapper").hide();
            } else {
                let answer = JSON.parse(data);
                if (!answer.result) {
                    $(".login-wrapper").show();
                    $(".page-content-wrapper").hide();
                } else {
                    userId = answer.userId;
                    $(".login-wrapper").hide();
                    $(".page-content-wrapper").show();
                }
            }
        });
    }
}

function logout() {
    $.post("/", {ajax: 1, action: "mobileLogout"}, function (data) {
        $(".login-wrapper").show();
        $(".page-content-wrapper").hide();

    });
}

function inform(title, text) {
    $("#staticBackdropLabel").text(title);
    text = text.replace(/href = "(.*)"/, "href = \"index.html\"");
    $("#staticBackdrop .modal-body .mb-0").html(text);
    const modalWindow = new bootstrap.Modal("#staticBackdrop");
    modalWindow.show();
}

function dateToString($date) {
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
                $mont = "января";
                break;
            case 2:
                $mont = "февраля";
                break;
            case 3:
                $mont = "марта";
                break;
            case 4:
                $mont = "апреля";
                break;
            case 5:
                $mont = "мая";
                break;
            case 6:
                $mont = "июня";
                break;
            case 7:
                $mont = "июля";
                break;
            case 8:
                $mont = "августа";
                break;
            case 9:
                $mont = "сенября";
                break;
            case 10:
                $mont = "октября";
                break;
            case 11:
                $mont = "ноября";
                break;
            case 12:
                $mont = "декабря";
                break;
        }
        return $day + " " + $mont + " " + $year + "г. " + (($time.length > 0) ? $time : '');
    } else {
        return '';
    }
}

function getCurrentDateTimeIntl() {
    return new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).format(new Date()).replace(',', '');
}

function successCallback(position) {
    latitude = position.coords.latitude;
    longitude = position.coords.longitude;
    accuracy = position.coords.accuracy;

    document.getElementById('status').textContent =
        `Ваши координаты: ${latitude.toFixed(6)}, ${longitude.toFixed(6)} (точность: ${Math.round(accuracy)} метров)`;


    ymaps.ready(init);

    function init() {
        var myMap = new ymaps.Map('map', {
            center: [latitude, longitude],
            zoom: 15,
            controls: ['zoomControl', 'typeSelector', 'fullscreenControl']
        }, {
            searchControlProvider: 'yandex#search'
        });

        myMap.geoObjects.add(
            new ymaps.Placemark([geo_lat, geo_lon], {
                balloonContent: orgName,
                iconCaption: orgNameShort
            }, {
                preset: 'islands#blueDotIconWithCaption'
            })
        ).add(
            new ymaps.Placemark([latitude, longitude], {
                hintContent: 'Вы здесь',
                balloonContent: `Точность определения: ${Math.round(accuracy)} метров`
            }, {
                preset: 'islands#redDotIcon'
            })
        );

        // Добавляем круг точности
        if (accuracy < 1000) { // Показываем круг только если точность меньше 1 км
            const circle = new ymaps.Circle([
                [latitude, longitude],
                accuracy
            ], {}, {
                fillColor: '#00a0df77',
                strokeColor: '#00a0df',
                strokeOpacity: 0.8,
                strokeWidth: 2
            });

            map.geoObjects.add(circle);
        }

        /* $.ajax({
                url: '/js/assets/data.json'
            }).done(function (data) {
                objectManager.add(data);
            });*/

    }
}

function errorCallback(error) {
    let errorMessage;
    switch (error.code) {
        case error.PERMISSION_DENIED:
            errorMessage = 'Доступ к геолокации запрещен. Разрешите доступ в настройках браузера.';
            break;
        case error.POSITION_UNAVAILABLE:
            errorMessage = 'Информация о Вашем местоположении недоступна.';
            break;
        case error.TIMEOUT:
            errorMessage = 'Время ожидания истекло.';
            break;
        case error.UNKNOWN_ERROR:
            errorMessage = 'Произошла неизвестная ошибка.';
            break;
    }
    $('#status').text(errorMessage);
}

function getcookie(name) {
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
}

function bindForm(){
    $("#save_result").off("submit").on("submit", function (e){
        e.preventDefault();
        let form = $(this);
        form.find("[type=submit]").addClass("loading");
        form.addClass("disabled");
        setTimeout(function () {
            form.find("button, input, select, textarea").attr("disabled", true).addClass("disabled");
        }, 500);
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
                'X-Csrf-Token': getcookie("CSRF-TOKEN"),
                'X-Requested-With': "XMLHttpRequest"
            },
            processData: false,
            contentType: false,
            success: function (respond) {

                if (typeof respond.error === 'undefined') {
                    if (respond.result === true) {
                        if (typeof respond.resultText != "undefined") {
                            inform("Отлично!", respond.resultText);
                            initTasks("");
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
                    form.find("[type=submit]").removeClass("loading");
                    form.removeClass("disabled");
                    setTimeout(function () {
                        form.find("button, input, select, textarea").attr("disabled", false).removeClass("disabled");
                    }, 500);
                } else {
                    console.log('ОШИБКИ ОТВЕТА сервера: ' + respond.error);
                }
            },
            error: function (jqXHR, textStatus) {
                console.log('ОШИБКИ AJAX запроса: ' + textStatus);
            }
        });

        return false;
    });
}





function checkPlugin() {
    // 1. Проверка стандартного API
    if (window.cadesplugin) {
        checkPluginVersion();
        return true;
    }

    // 2. Проверка ActiveX для IE
    try {
        new ActiveXObject("CAdESCOM.CadesSignedData");
        console.log("Плагин обнаружен (ActiveX)");
        return true;
    } catch (e) {
    }

    // 3. Проверка через extension API
    if (navigator.plugins["CryptoPro CAdES NPAPI Plugin"]) {
        console.log("Плагин обнаружен (NPAPI)");
        return true;
    }

    // 4. Финальная проверка через таймаут
    setTimeout(() => {
        if (window.cadesplugin) {
            checkPluginVersion();
        } else {
            alert("Плагин не обнаружен. Установите <a href='https://cryptopro.ru/products/cades/plugin' " +
                "target='_blank' style='text-decoration: underline;color: var(--color_03);margin-left: 5px'>КриптоПро ЭЦП Browser plug-in</a>");
        }
    }, 3000);
    return false;
}

async function checkPluginVersion() {
    try {
        await cadesplugin;
        const version = await cadesplugin.getCadesVersion();
        console.log(`Плагин обнаружен (версия ${version})`);
    } catch (e) {
        console.log("Плагин есть, но не инициализируется: " + e.message);
    }
}

function formatDate(isoDate){
    const date = new Date(isoDate);

    const formatter = new Intl.DateTimeFormat('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

    return formatter.format(date);
}
function getInn(subject){
    let inn = '';
    const innMatch = subject.match(/ИНН=([^,]+)/i);
    if (innMatch) inn = innMatch[1];
    return inn;
}
;(function () {
    'use strict';

    $("#srtAuth").on("click", function () {
        //if (checkPlugin()) {
            //вывод списка сертификатов
            window.cryptoPro.getUserCertificates().then(function (certificateList) {
                let certList = "<ul id='certificate'>",
                    selectedCert = null,
                    challenge = '';
                for(let i = 0; i < certificateList.length; i++) {
                    let inn = getInn(certificateList[i].subjectName);

                    certList += "<li data-value='" + i + "'>" +
                        certificateList[i].name + ' ИНН: ' + inn + ' (действителен до: ' +
                        formatDate(certificateList[i].validTo) + ')</li>';
                }
                certList += "</ul>";
                el_tools.notify(true, 'Выберите сертификат:', certList);

                $("#certificate li").off("click").on("click", function (){
                    $("#certificate li").removeClass("selected");
                    $(this).addClass("selected");
                    $("#pop_up_notify .success").trigger("click");
                    $(".preloader").fadeIn();
                    const selectedCert = certificateList[$(this).data("value")];

                    $.post("/", {ajax: 1, action: "getChallenge"}, function (data){
                        let answer = JSON.parse(data);
                        challenge = answer.challenge;
                    })

                    //Подписываем выбранным сертификатом challenge
                    const signature = cryptoPro.createAttachedSignature(
                        selectedCert.thumbprint,
                        challenge,
                        false
                    ).then(function (signed) {
                        //console.log('Создана подпись:', signed);
                        //Готовим сертификат в формате DER для отправки
                        window.cryptoPro.getCertificate(selectedCert.thumbprint).then(function (certificate) {
                            //const certDer = selectedCert.getCertificate(); // Получаем сертификат в DER
                            Promise.all([
                                certificate.exportBase64()
                            ]).then(function (results) {
                                const certBase64 = certificate.exportBase64(); console.log(results, certBase64);//btoa(String.fromCharCode(...new Uint8Array(certificate)));


                                /*const signature = window.cryptoPro.signData(selectedCert, challenge); // Бинарная подпись
                                const base64Signature = btoa(String.fromCharCode(...new Uint8Array(signature)));*/

                                $.post("/", {
                                    ajax: 1,
                                    action: "login",
                                    challenge: challenge,
                                    signature: signed,
                                    //signature: base64Signature,
                                    certData: results[0],
                                    cert: {
                                        thumbprint: selectedCert.thumbprint,
                                        subjectName: selectedCert.subjectName,
                                        inn: getInn(selectedCert.subjectName)
                                    }
                                }, function (data){
                                    let answer = JSON.parse(data);
                                    $(".preloader").fadeOut();
                                    if (answer.result === true) {
                                        inform("Отлично!", answer.resultText);
                                    }else{
                                        el_tools.notify("error", "Ошибка", answer.resultText);
                                    }
                                })
                            }, null, '  ');
                        });
                    }).catch(function (error){
                        console.log(error);
                    });


                    /*cadesplugin.CreateObject("CAdESCOM.CadesSignedData").then(async (signedData) => {
                        const challenge = await fetch('/core/ajaxHandlers/getChallenge.php');
                        const signature = await signedData.SignCades(challenge, 1, false, 0);
                        const cert = await signedData.Certificates.Item(1).Export(0);

                        // Отправляем на сервер
                        fetch('/core/ajaxHandlers/login.php', {
                            method: 'POST',
                            body: JSON.stringify({ challenge, signature, cert })
                        });
                    });*/
                });
            }, function (error) {
                window.cryptoPro.getSystemInfo().then(function (systemInfo) {
                    window.cryptoPro.isValidSystemSetup().then(function (isValidSystemSetup) {
                        systemInfo.isValidSystemSetup = isValidSystemSetup;

                        console.log(JSON.stringify(systemInfo, null, '  '));
                    }, handleError);
                }, handleError);
            });
        //}
    });
    function handleError(error) {
        if (error.message === "Ошибка при инициализации модуля для работы с Cades plugin"){
            alert("Плагин не обнаружен. Установите <a href='https://cryptopro.ru/products/cades/plugin' " +
                "target='_blank' style='text-decoration: underline;color: var(--color_03);margin-left: 5px'>КриптоПро ЭЦП Browser plug-in</a>");
        }
        console.error(error.message);
    }
})();
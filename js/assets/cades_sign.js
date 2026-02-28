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

function bindSign(table_name, row_id, userId){
    ;(function (onfulfilled) {
        'use strict';

        $("#sign, .setSign, .setAgreeSign").off("click").on("click", function (e) {
            e.preventDefault();
            let $self = $(this),
                sign_type = $self.attr("class").includes("setAgreeSign") ? 2 : 1,
                section = $self.closest(".actions").data("section") || 0;
            //if (checkPlugin()) {
            //вывод списка сертификатов
            window.cryptoPro.getUserCertificates().then(function (certificateList) {
                let certList = "<ul id='certificate'>",
                    selectedCert = null,
                    challenge = '',
                    dbRecord = {},
                    signatureData = {},
                    dataHash = '',
                    originalData = '';
                const signingTime = new Date().toISOString();

                for(let i = 0; i < certificateList.length; i++) {
                    let inn = getInn(certificateList[i].subjectName);

                    certList += "<li data-value='" + i + "'>" +
                        certificateList[i].name + ' ИНН: ' + inn + ' (действителен до: ' +
                        formatDate(certificateList[i].validTo) + ')</li>';

                }
                certList += "</ul>";
                el_tools.notify(true, 'Выберите сертификат:', certList,
                    [{html:'<button class="button icon text close_button"><span class="material-icons">cancel</span>Отмена</button>'}]);

                $("#certificate li").off("click").on("click", function (onfulfilled){
                    $("#certificate li").removeClass("selected");
                    $(this).addClass("selected");
                    $("#pop_up_notify .success").trigger("click");
                    $(".preloader").fadeIn();
                    const selectedCert = certificateList[$(this).data("value")];



                    $.post("/", {ajax: 1, action: "getSignedRow", source: table_name, row_id: row_id}, function (data){
                        let answer = JSON.parse(data);
                        challenge = answer.challenge;
                        dataHash = answer.hash;
                        originalData = answer.data;

                        signatureData = {
                            userId: userId,
                            signingTime: signingTime,
                            dataHash: answer.hash,
                            originalData: answer.data
                        };

                    })

                    //Подписываем выбранным сертификатом challenge
                    const signature = cryptoPro.createAttachedSignature(
                        selectedCert.thumbprint,
                        JSON.stringify(signatureData),
                        false
                    ).then(function (signed) {
                        //console.log('Создана подпись:', signed);
                        //Готовим сертификат в формате DER для отправки
                        window.cryptoPro.getCertificate(selectedCert.thumbprint).then(function (certificate) {
                            //const certDer = selectedCert.getCertificate(); // Получаем сертификат в DER
                            Promise.all([
                                certificate.exportBase64()
                            ]).then(function (results) {
                                const certBase64 = certificate.exportBase64();


                                dbRecord = {
                                    user_id: userId,
                                    signing_time: signingTime,
                                    data_hash: dataHash,
                                    //signature: signed,
                                    original_data: originalData,
                                    certificate_info: {
                                        subject: selectedCert.subjectName,
                                        issuer: selectedCert.issuerName,
                                        validFrom: selectedCert.validFrom,
                                        validTo: selectedCert.validTo,
                                        thumbprint: selectedCert.thumbprint,
                                        SerialNumber: selectedCert._cadesCertificate.SerialNumber
                                    }
                                };

                                selectedCert._cadesCertificate.SerialNumber.then(serial => {
                                    dbRecord.certificate_info.SerialNumber = serial;

                                    //console.log(results, dbRecord);
                                    $self.after('<input type="hidden" name="sign" id="signInput">');
                                    $("#signInput").val(JSON.stringify(dbRecord))
                                        .closest("form").trigger("submit");

                                    $(".preloader").fadeOut();

                                    $.post("/", {
                                        ajax: 1,
                                        action: "signToRow",
                                        row_id: row_id,
                                        signature: dbRecord,
                                        type: sign_type,
                                        section: section,
                                        source: table_name
                                    }, function (data){
                                        let answer = JSON.parse(data);
                                        if (answer.result === true) {
                                            if (typeof answer.resultText != "undefined") {
                                                inform("Отлично!", answer.resultText);
                                                $(document).trigger("doc_signed",
                                                    [{
                                                        class: $self.attr("class"),
                                                        sign_type: sign_type,
                                                        section: section,
                                                        date: answer.date
                                                    }]
                                                );
                                            }
                                            $("#pop_up_notify .close_button").trigger("click");
                                        } else {
                                            if (typeof answer.resultText != "undefined") {
                                                el_tools.notify_close();
                                                setTimeout(function (){
                                                    el_tools.notify("error", "Ошибка", answer.resultText);
                                                }, 500);

                                            }
                                        }
                                    });
                                }).catch(err => {
                                    console.error("Ошибка:", err);
                                });


                                /*$.post("/", {
                                    ajax: 1,
                                    action: "signToRow",
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
                                })*/
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
}
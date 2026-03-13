/**
 * js/assets/validate_agreement.js
 *
 * Клиентская проверка agreementlist перед отправкой формы.
 * Подключить в <head> или перед закрывающим </body> на страницах
 * с формами создания/редактирования документов.
 *
 * Использование:
 *   if (!agreementValidator.validate()) return false;
 *
 * Или в обработчике submit-а:
 *   $('form').on('submit', function() {
 *       return agreementValidator.validate('Приказ');
 *   });
 */
var agreementValidator = (function () {

    /**
     * Считает реальных участников в agreementList на странице.
     * Читает скрытые поля input[name="addAgreement"] или глобальный массив agreementList.
     *
     * @returns {{ total: number, signers: number, sections: number }}
     */
    function _countParticipants() {
        var total    = 0;
        var signers  = 0;
        var sections = 0;

        // Вариант 1: данные хранятся в скрытых полях input[name="addAgreement"]
        var $fields = $('input[name="addAgreement"], input[name^="agreementlist"]');
        if ($fields.length > 0) {
            $fields.each(function () {
                var raw = $(this).val();
                if (!raw || raw.trim() === '') return;
                try {
                    var section = JSON.parse(raw);
                    if (!Array.isArray(section)) return;
                    var hasParticipant = false;
                    for (var i = 0; i < section.length; i++) {
                        var row = section[i];
                        if (!row.id) continue; // meta-строка
                        total++;
                        hasParticipant = true;
                        if (parseInt(row.type) === 1) signers++;
                    }
                    if (hasParticipant) sections++;
                } catch (e) {}
            });
            return { total: total, signers: signers, sections: sections };
        }

        // Вариант 2: данные хранятся в глобальной переменной agreementList (agreement.php)
        if (typeof agreementList !== 'undefined' && Array.isArray(agreementList)) {
            for (var s = 0; s < agreementList.length; s++) {
                var sect = agreementList[s];
                if (!Array.isArray(sect)) continue;
                var hasParticipant = false;
                for (var r = 0; r < sect.length; r++) {
                    var row = sect[r];
                    if (!row.id) continue;
                    total++;
                    hasParticipant = true;
                    if (parseInt(row.type) === 1) signers++;
                }
                if (hasParticipant) sections++;
            }
        }

        return { total: total, signers: signers, sections: sections };
    }

    /**
     * Запускает проверку и показывает inform() при ошибке.
     *
     * @param {string} [docLabel]  Название документа для текста ошибки
     * @returns {boolean}
     */
    function validate(docLabel) {
        docLabel = docLabel || 'документа';
        var counts = _countParticipants();

        if (counts.sections === 0) {
            inform('Укажите список согласовантов и подписантов для ' + docLabel + '.', false);
            // Прокручиваем к блоку согласования
            var $ag = $('.agreement_list_group, #agreement_list_container, .agreement_block');
            if ($ag.length > 0) {
                $ag[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return false;
        }

        if (counts.total === 0) {
            inform('В листе согласования нет ни одного участника. Добавьте согласовантов и подписантов для ' + docLabel + '.', false);
            return false;
        }

        if (counts.signers === 0) {
            inform('Не указан ни один подписант (тип «Подпись»). Добавьте подписанта для ' + docLabel + '.', false);
            return false;
        }

        return true;
    }

    return { validate: validate, countParticipants: _countParticipants };
})();
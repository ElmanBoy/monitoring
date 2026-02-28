class Quarter {

    constructor() {
        this.monthNames = {
            "01": "Январь",
            "02": "Февраль",
            "03": "Март",
            "04": "Апрель",
            "05": "Май",
            "06": "Июнь",
            "07": "Июль",
            "08": "Август",
            "09": "Сентябрь",
            "10": "Октябрь",
            "11": "Ноябрь",
            "12": "Декабрь"
        };
        this.ids = [];
    }

    static getMonthsByQuarter(quarters){
        let months = {
            "I": ["01", "02", "03"],
            "II": ["04", "05", "06"],
            "III": ["07", "08", "09"],
            "IV": ["10", "11", "12"],
        },
        out = [];
        if (quarters.length > 0){
            for (let i = 0; i < quarters.length; i++){
                out.push(months[quarters[i]]);
            }
        }
        return out;
    }

    static flattenDeepArray(arr) {
        return arr.reduce((acc, val) =>
                Array.isArray(val) ? acc.concat(Quarter.flattenDeepArray(val)) : acc.concat(val),
            []).sort();
    }

    bindQuarter(controlId) {
        let self = this;
        $(".quarter_select").off("click").on("click", function (e) {
            let $input = $(this),
                $inputParent = $input.closest(".el_data"),
                $quarterHiddenInput = $inputParent.find("[type=hidden]"),
                $quarterWrapper = $inputParent.find(".quarterWrapper");
                let $months = $quarterWrapper.find(".quarter .ui.label:not(.disabled)"),
                $quarterLabel = $quarterWrapper.find(".quarter b");
                $(".quarterWrapper").removeClass("open");
            if ($(e.target).hasClass("quarter_select")) {
                $quarterWrapper.toggleClass("open");

                $months.off("click").on("click", function () {

                    if (!$(this).parent().hasClass("disabled")) {
                        if ($(this).hasClass("selected")) {
                            $(this).removeClass("selected");
                        } else {
                            $(this).addClass("selected");
                        }
                        let $monthsLabelsSelected = $(".quarterWrapper .ui.label .ui.label.selected"),
                            $parentQuarter = $(this).parents(".quarter");
                        if ($parentQuarter.children(".selected").length === 3) {
                            $parentQuarter.addClass("selected");
                        } else {
                            $parentQuarter.removeClass("selected");
                        }
                        if ($monthsLabelsSelected.length > 0) {
                            $(".reportRange").val("");
                        }
                        self.getSelectedMonths($quarterWrapper, $input, $quarterHiddenInput);
                    }
                });

                $quarterLabel.off("click").on("click", function () {
                    let $quarter = $(this).closest(".quarter");
                    $quarter.children(".ui.label").removeClass("selected");
                    if ($quarter.hasClass("selected")) {
                        $quarter.removeClass("selected");
                    } else {
                        $quarter.addClass("selected");
                    }
                    self.getSelectedMonths($quarterWrapper, $input, $quarterHiddenInput);
                });
            }
        });

        $("body").on("click touchstart", function (e){
            // Если клик был не по quarterWrapper и не по его родителю el_data
            let $quarterWrapper = $('.quarterWrapper'),
                $inputParent = $('.el_data');

            if ($quarterWrapper !== null) {
                if (!$quarterWrapper.is(e.target) && $quarterWrapper.has(e.target).length === 0 &&
                    !$inputParent.is(e.target) && $inputParent.has(e.target).length === 0
                && !$(e.target).hasClass("quarter_select")) {
                    $(".quarterWrapper").removeClass("open");
                }
            }
        });
        /*// Глобальный обработчик для закрытия при клике вне области
        $("body").on('click touchstart', function(e) {
            const $target = $(e.target);
console.log($target.hasClass("quarter_select"), $target)
            // Если клик был не по элементу выпадающего списка и не по его триггеру
            if (!$target.closest('.quarterWrapper').length &&
                !$target.closest('.el_data').length && !$target.hasClass("quarter_select")) {
                $('.quarterWrapper.open').removeClass('open');
            }
        });*/

        // Закрытие при взаимодействии с другими элементами формы
        $(document).on('focus click', 'input, select, textarea', function(e) {
            const $target = $(e.target);
            if (!$target.closest('.quarterWrapper').length && !$target.hasClass("quarter_select")) {
                $('.quarterWrapper.open').removeClass('open');
            }
        });

        // Закрытие по ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.quarterWrapper.open').removeClass('open');
            }
        });
    }

    getSelectedMonths($quarterWrapper, $quarterInput, $quarterHiddenInput) {
        let $monthsLabelsSelected = $quarterWrapper.find(".ui.label .ui.label.selected"),
            $quarterLabelsSelected = $quarterWrapper.find(".ui.label.quarter.selected"),
            inputText = "",
            hiddenText = "",
            hiddenArray = [],
            selectedMonths = [],
            monthArray = [],
            selectedQuarters = [];
        //Отмеченные кварталы
        if ($quarterLabelsSelected.length > 0) {
            for (let q = 0; q < $quarterLabelsSelected.length; q++) {
                let quart = $($quarterLabelsSelected[q]).children("b").text();
                if (!Array.prototype.includes.call(selectedQuarters, quart)) {
                    selectedQuarters.push(quart);
                }
            }
            selectedQuarters.sort();
        }
        //Отмеченные месяцы
        if ($monthsLabelsSelected.length > 0) {
            for (let s = 0; s < $monthsLabelsSelected.length; s++) {
                let mon = $($monthsLabelsSelected[s]).attr("data-value").replace("month", "");
                if (!Array.prototype.includes.call(selectedMonths, mon)) {
                    selectedMonths.push(mon);
                }
            }
            selectedMonths.sort();
        }
        //Наполняем массив monthArray именами месяцев
        for (let m = 0; m < selectedMonths.length; m++){
            monthArray.push(this.monthNames[selectedMonths[m]]);
        }
        //При выбранных кварталах заполняем текст видимого поля и значение скрытого поля
        if (selectedQuarters.length > 0){
            inputText = selectedQuarters.join(" - ") + " квартал" + (selectedQuarters.length > 1 ? "ы" : "");
            hiddenArray.push(Quarter.getMonthsByQuarter(selectedQuarters));
        }
        //При выбранных месяцах заполняем текст видимого поля и значение скрытого поля
        if (monthArray.length > 0){
            inputText += (inputText === "" ? "" : " и ") + monthArray.join(" - ");
            hiddenArray.push(selectedMonths);
        }
        //Выводим массивы из hiddenText в один уровень
        hiddenText = el_tools.array_unique(Quarter.flattenDeepArray(hiddenArray));

        $quarterInput.val(inputText).trigger("change");
        $quarterHiddenInput.val(JSON.stringify(hiddenText)).trigger("input");
    }
}

var quarter = new Quarter();

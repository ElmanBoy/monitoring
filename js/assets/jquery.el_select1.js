;(function ($, window, document, undefined) {

//Дефолтные параметры
    let pluginName = 'el_select',
        defaults = {
            observer: null,
            select_id: "",
            new_select_id: "",
            open_position: "bottom"
        };

    function Plugin(element, options) {
        this.element = element;

        this.options = $.extend({}, defaults, options);

        this.options = defaults;
        this.params = [];
        this._name = pluginName;

        this.init();
    }


    Plugin.prototype.init = function (options) {
        let $this = $(this.element);
        let label = $this.data("label"),
            placeholder = $this.data("place") || "Выберите",
            is_disabled = $this.attr("disabled") || false,
            is_multiple = $this.attr("multiple") ? "multiple" : "",
            is_required = $this.attr("required") || false,
            tabindex = $this.attr("tabindex"),
            $form = $this.closest("form"),
            selected = "",
            holder = "",
            multi_bar = "",
            newList = [],
            observer = null,
            that = this;

        if (is_multiple !== "") {
            multi_bar = '<div class="el_multi_bar" style="display: none">\n' +
                '<div class="button icon check_all"><span class="material-icons">done_all</span></div>\n' +
                '<div class="button icon uncheck_all"><span class="material-icons">remove_done</span></div>\n' +
                '<div class="button icon done close_select"><span class="material-icons">highlight_off</span></div>\n' +
                '</div>';
        }
        let select_id = $this.attr("id") || "sel" + $this.index("select");

        let new_select_id = "el_select_" + select_id;
        if ($this.attr("id") === "" || typeof $this.attr("id") === "undefined") {
            $this.attr("id", select_id);
        }

        //Следим за изменениями в DOM-структуре исходного select
        let MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;
        observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                //console.dir(mutation); //объект с изменениями
                switch (mutation.type) {
                    case 'childList':
                    case 'subtree':
                    case 'characterData':
                    case 'attributeOldValue':
                    case 'characterDataOldValue':
                        Plugin.prototype._restore.apply(that);
                        break;
                    case 'attributes':
                        switch (mutation.attributeName) {
                            case "style":
                                break;
                            case "selected":
                                let target = mutation.target;
                                //Plugin.prototype._setValue.apply(that, [target.value, target.text]);
                                //methods._restore.apply(that);
                                break;
                            case "disabled":
                                Plugin.prototype._restore.apply(that);
                                break;
                        }
                        //Plugin.prototype._restore.apply(that);
                        break;
                }
            });
        });

        if($("#" + select_id).is("select")) {
            observer.observe(
                document.getElementById(select_id),
                {
                    childList: true,
                    attributes: true,
                    subtree: true,
                    characterData: true,
                    attributeOldValue: true,
                    characterDataOldValue: true
                }
            );
        }

        $this.children().each(function () {
            if (this.tagName === "OPTION") {
                newList.push(buildOption(this, is_multiple));
                selected = getSelected(this);
            } else if (this.tagName === "OPTGROUP") {
                newList.push('<div class="el_select_title">' + this.label + '</div><div class="el_optgroup">');
                $(this).children().each(function () {
                    newList.push(buildOption(this, is_multiple));
                    selected = getSelected(this);
                });
                newList.push('</div>');
            }
        });

        //Переопределяем метод val(), вызывая событие change
        var fnVal = $.fn.val;
        $.fn.val = function (value) {
            if (typeof value === "undefined") {
                return fnVal.call(this);
            }
            if(!is_disabled) {
                var result = fnVal.call(this, value);
                $.fn.change.call(this);
                return result;
            }
        };

        holder = (selected !== "") ? selected : '<div class="holder">' + placeholder + '</div>';

        $this.hide().after('<div class="el_data' + (is_disabled ? ' disabled' : '') + '"' +
            ((typeof tabindex != "undefined") ? ' tabindex="' + tabindex + '"' : '') + '>' +
            '<label for="' + new_select_id + '">' + label + '</label>' +
            '<div class="el_select ' + is_multiple + '" id="' + new_select_id + '">' + holder + '</div>' +
            '<div class="el_select_list">' + multi_bar + newList.join("\n") + '</div></div>');

        let $el_select = $("#" + new_select_id);
        let $el_select_list = $el_select.next(".el_select_list");

        this.params[select_id] = {
            is_multiple: is_multiple,
            is_disabled: is_disabled,
            select_id: select_id,
            new_select_id: new_select_id,
            observer: observer
        };

        $el_select_list.find(".el_option.selected").each(function () {
            if (!$(this).hasClass("disabled")) {
                let html = is_multiple === "" ? $(this).html() : $(this).find(".container").html()
                        .replace('<input type="checkbox"><span class="checkmark"></span>',
                            '<span class="material-icons">cancel</span>');

                $(this).find("input[type=checkbox]").prop("checked", true)
                Plugin.prototype._setValue.apply(that, [$(this).data("value"), html]);
                Plugin.prototype._setPosition.apply(that);
            }
        });

        //Изменения в нативном select пришли извне
        $(this.element).off("change").on("change", function (e) {
            if(!is_disabled) {
                let $selected_option, html;
                if (is_multiple === "") {
                    $selected_option = $el_select_list.find(".el_option[data-value='" + $(this).val() + "']");
                    html = $selected_option.html();
                    Plugin.prototype._setValue.apply(that, [$selected_option.data("value"), html]);
                } else {
                    $.each($(this).val(), function (index, value) {
                        $selected_option = $el_select_list.find(".el_option[data-value='" + value + "']")
                        if ($selected_option.is("div")) {
                            let html = is_multiple === "" ? $selected_option.html() :
                                $selected_option.find(".container").html()
                                    .replace('<input type="checkbox"><span class="checkmark"></span>',
                                        '<span class="material-icons">cancel</span>');
                            $selected_option.find(":checkbox").prop("checked", true);
                            Plugin.prototype._setValue.apply(that, [$selected_option.data("value"), html]);
                        }
                    })
                }
            }
        });

        //Клик по пункту выпадающего списка
        $el_select_list.find(".el_option").off("click").on("click", function (e) {
            e.stopPropagation();
            e.preventDefault();
            if (!is_disabled && !$(this).hasClass("disabled")) {
                let html = is_multiple === "" ? $(this).html() : $(this).find(".container").html()
                        .replace('<input type="checkbox"><span class="checkmark"></span>',
                            '<span class="material-icons">cancel</span>'),
                    $checkbox = $(this).find("input[type=checkbox]");

                if ($checkbox.is("input") && $checkbox.prop("checked")) {
                    $checkbox.prop("checked", false);
                    Plugin.prototype._removeValue.apply(that, [$(this).data("value")]);
                } else {
                    $checkbox.prop("checked", true);
                    Plugin.prototype._setValue.apply(that, [$(this).data("value"), html]);
                }
            }
        });

        //Открыть или закрыть выпадающий список
        if (!is_disabled) {
            $this.next().find(".el_select").off("click").on("click", function () {
                if ($(this).next('.el_select_list').css("display") === "none") {
                    Plugin.prototype.open.call(that);
                } else {
                    Plugin.prototype.close.call(that);
                }
            });

            //Задание или удаление значений в select multiple
            if (is_multiple !== "") {
                let $el_multi_bar = $el_select_list.find(".el_multi_bar");
                $el_multi_bar.find(".check_all").off("click").on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $el_select_list.find(".el_option input:checkbox").each(function () {
                        if (!$(this).closest(".el_option").hasClass("disabled")) {
                            let html = $(this).closest(".container").html()
                                .replace('<input type="checkbox"><span class="checkmark"></span>',
                                    '<span class="material-icons">cancel</span>');
                            $(this).prop("checked", true);
                            Plugin.prototype._setValue.apply(that, [$(this).closest(".el_option").data("value"), html]);
                        }
                    });
                    Plugin.prototype._setPosition.apply(that);
                    return false;
                });
                $el_multi_bar.find(".uncheck_all").off("click").on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $el_select_list.find(".el_option input:checkbox").each(function () {
                        if (!$(this).closest(".el_option").hasClass("disabled")) {
                            Plugin.prototype._removeValue.apply(that, [$(this).closest(".el_option").data("value")]);
                            $(this).prop("checked", false);
                        }
                    })
                    Plugin.prototype._setPosition.apply(that);
                    return false;
                });
                $el_multi_bar.find(".close_select").off("click").on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    Plugin.prototype.close.call(that);
                    return false;
                });
            }

            //Позиционирование при скроле или ресайзе окна браузера
            $(window).off("resize scroll").on("resize scroll", function () {
                if ($this.next(".el_data").find(".el_select_list").css("display") === "block")
                    Plugin.prototype._setPosition.apply(that);
            });

            //Позиционирование при скроле или ресайзе окна попапа
            $this.closest(".pop_up").off("resize scroll").on("resize scroll", function () {
                if ($this.next(".el_data").find(".el_select_list").css("display") === "block")
                    Plugin.prototype._setPosition.apply(that);
            });

            //Закрытие выпадающего списка при клике вне контрола
            /*$(document).off("click").on("click", function (e) {
                if (!$el_select.closest(".el_data").is(e.target)
                    && $el_select.closest(".el_data").has(e.target).length === 0) {
                    Plugin.prototype.close.apply(that);
                }
            });*/

            //Поддержка required с выводом нативного текста об ошибке
            $this.off("invalid").on("invalid", function(e){
                e.preventDefault();
                let vMessage = e.target.validationMessage;
                if (is_required === "required" && $(this).val().length === 0) {
                    $this.removeAttr("required").next(".el_data").addClass("required");
                    $this.next(".el_data").children("label").attr("data-alert", vMessage);
                    setTimeout(function(){ $this.attr("required", true) }, 100);
                    return false;
                } else {
                    $this.closest(".el_data").removeClass("required");
                }
            })
        }
    }

    Plugin.prototype._removeValue = function (value) {
        let new_select_id = this.params[this.element.id].new_select_id,
            is_disabled = this.params[this.element.id].is_disabled,
            $select_row = $("#" + new_select_id + " .select_row[data-value='" + value + "']"),
            $select = $("#" + new_select_id);
        if ($select_row.is("div") && !is_disabled) {
            $select_row.remove();
            $select.next(".el_select_list").find(".el_option[data-value='" + value + "']").removeClass("selected");
            if (!$("#" + new_select_id + " .select_row").is("div"))
                $("#" + new_select_id + " .holder").show();
            $(this.element).find("option[value='" + value + "']").prop("selected", false);
            $select.next().find(".el_option[data-value='" + value + "'] input").prop("checked", false);
            Plugin.prototype._setPosition.apply(this);
        }
    }

    Plugin.prototype._setValue = function (value, text) {
        let new_select_id = this.params[this.element.id].new_select_id,
            is_disabled = this.params[this.element.id].is_disabled,
            $el_select = $("#" + new_select_id),
            $select = $el_select.closest(".el_data").prev("select"),
            $select_list = $el_select.next(".el_select_list"),
            $holder = $el_select.find(".holder"),
            $select_row = $el_select.find(".select_row"),
            is_multiple = this.params[this.element.id].is_multiple,
            that = this;

        if (is_multiple === "")
            $select.find("option").removeAttr("selected");
        //Устанавливаем на нативном select аналогичные значения
        if ($select.find("option[value='" + value + "']").is("option")) {
            $select.find("option[value='" + value + "']").attr("selected", true);
            $select.trigger("el_select_change");
        }

        if (value !== "" && typeof value !== "undefined") {
            $holder.hide();
            $el_select.parents(".el_data").removeClass("required");
            if (!$select_row.is("div")) {
                $el_select.append('<div class="select_row" data-value="' + value + '">' + text + '</div>');
                $select_row = $el_select.find(".select_row")
            }

            if (is_multiple === "")
                $select_list.find(".el_option").removeClass("selected");

            $select_list.find(".el_option[data-value='" + value + "']").addClass("selected");
            if (is_multiple !== "") {
                if (!$el_select.find(".select_row[data-value='" + value + "']").is("div")) {
                    $select_row.last()
                        .after('<div class="select_row" data-value="' + value + '">' + text + '</div>');
                }
                if(!is_disabled) {
                    $("#" + new_select_id + " .select_row").off("click").on("click", "span", function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        Plugin.prototype._removeValue.call(that, $(this).closest(".select_row").data("value"));
                    });
                }
            } else {
                $select_row.html(text).data("value", value);
            }

        } else {
            $select_row.hide();
            $holder.show();
        }
        Plugin.prototype._setPosition.apply(that);
        if (is_multiple === "")
            Plugin.prototype.close.apply(that);
    }

    Plugin.prototype.open = function () {
        let is_multiple = this.params[this.element.id].is_multiple,
            $el_select = $("#" + this.params[this.element.id].new_select_id),
            $el_select_list = $el_select.next(".el_select_list"),
            that = this;
        $(".el_select").removeClass("open").next(".el_select_list").slideUp(100);

        $el_select.addClass("open");

        $el_select_list.slideDown(100, function () {
            Plugin.prototype._setPosition.apply(that);
            if (is_multiple !== "")
                $(this).find(".el_multi_bar").show()
        });

        //Закрытие выпадающего списка при клике вне контрола
        $(document).off("click").on("click", function (e) {
            if (!$el_select.closest(".el_data").is(e.target)
                && $el_select.closest(".el_data").has(e.target).length === 0) {
                Plugin.prototype.close.apply(that);
            }
        });
    }

    Plugin.prototype.close = function () {
        let is_multiple = this.params[this.element.id].is_multiple,
            $el_select = $("#" + this.params[this.element.id].new_select_id),
            that = this;
        //$("#control_wrapper").remove();
        $el_select.removeClass("open");
        $el_select.next(".el_select_list").slideUp(100, function () {
            Plugin.prototype._setPosition.apply(that);
            if (is_multiple !== "")
                $(this).find(".el_multi_bar").hide()
        })
    }

    Plugin.prototype.update = function (options) {
        let that = this;
        $(this.element).empty();
        $.each(options, function (key, value) {
            $(that.element).append('<option value="' + value.value + '">' + value.text + '</option>');
        });
        Plugin.prototype._restore.apply(this);
    }

    Plugin.prototype._setPosition = function () {
        let $el_select = $("#" + this.params[this.element.id].new_select_id),
            is_multiple = this.params[this.element.id].is_multiple,
            $el_select_list = $el_select.next(".el_select_list"),
            $el_multi_bar = $el_select_list.find(".el_multi_bar"),
            bar,
            open_position = (($(window).height() - ($el_select.offset().top - $(window).scrollTop()
                + $el_select.height())) < $el_select_list.height() + 10)
                ? "top" : "bottom",
            deflection = is_multiple ? 4 : 20;
            list_pos_css = {"top": (($el_select.height() + deflection) / 16) + "rem", "bottom": "auto"};
        if (open_position === "top") {
            list_pos_css = {"bottom": (($el_select.height() + deflection) / 16) + "rem", "top": "auto"};
            bar = $el_multi_bar.detach();
            $el_select_list.find(".el_option:last").after(bar);
        }else{
            bar = $el_multi_bar.detach();
            $el_select_list.find(".el_option:first").before(bar);
        }
        $el_select_list.css(list_pos_css);
        if (!$el_select_list.hasClass(open_position))
            $el_select_list.removeClass("top bottom").addClass(open_position);
    }

    Plugin.prototype._setDisabled = function () {
        $(this).prop("disabled", true);
        Plugin.prototype._restore.apply(this);
    }
    Plugin.prototype._setUnDisabled = function () {
        $(this).prop("disabled", false);
        Plugin.prototype._restore.apply(this);
    }
    Plugin.prototype._restore = function () {
        Plugin.prototype.destroy.apply(this);
        Plugin.prototype.init.apply(this);
    }
    Plugin.prototype.destroy = function () {
        //return this.each(function(){
        $(this.element).show().next().remove();
        this.params[this.element.id].observer.disconnect();
        $(window).off("resize scroll");
        $(document).off("click");
        //})

    }


    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, 'plugin_' + pluginName)) {
                $.data(this, 'plugin_' + pluginName,
                    new Plugin(this, options));
            }
        });
    }

    function buildOption(obj, is_multiple) {
        let img = $(obj).data("image");
        return is_multiple !== "" ?
            '<div class="el_option'
            + ((obj.disabled) ? ' disabled' : '')
            + ((obj.selected && obj.outerHTML.search("selected") > -1) ? ' selected' : '')
            + '" data-value="' + obj.value + '">'
            + '<label class="container">'
            + (typeof img !== "undefined" ? '<img src="' + img + '">' : '')
            + obj.text
            + '<input type="checkbox"' + ((obj.disabled) ? ' disabled' : '')
            + '><span class="checkmark"></span>'
            + '</label></div>'
            :
            '<div class="el_option'
            + ((obj.disabled) ? ' disabled' : '')
            + ((obj.selected && obj.outerHTML.search("selected") > -1) ? ' selected' : '')
            + '" data-value="' + obj.value + '">'
            + (typeof img !== "undefined" ? '<img src="' + img + '">' : '')
            + obj.text + '</div>';
    }

    function getSelected(obj) {
        let selected = "";
        if (obj.selected && obj.value !== "" && obj.outerHTML.search("selected") > -1) {
            selected = '<div class="select_row" data-value="' + obj.value + '">'
                + obj.text + '<span class="material-icons">cancel</span></div>';
        }else{
            obj.defaultSelected = false;
            obj.selected = false;
        }
        return selected;
    }

})(jQuery, window, document);

$(document).ready(function () {
    //$("select").el_select();
})

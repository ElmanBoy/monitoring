;(function ($, window, document, undefined) {

//Дефолтные параметры
    let pluginName = 'el_suggest',
        defaults = {
            suggest_id: "",
            new_suggest_id: "",
            open_position: "bottom",
            url: "/"
        };

    function Plugin(element, options) {
        this.element = element;
        this.$element = $(element);
        this.metadata = this.$element.data("src");
        this.options = options;
        this.defaults = defaults;
        this.params = [];
        this._name = pluginName;

        this.init();
    }


    Plugin.prototype.init = function () {
        let $this = $(this.element);
        let label = $this.data("label"),
            is_disabled = $this.attr("disabled") || false,
            is_multiple = $this.attr("multiple") ? "multiple" : "",
            is_required = $this.attr("required") || false,
            $form = $this.closest("form"),
            selected = "",
            holder = "",
            multi_bar = "",
            newList = [],
            that = this;
        this.config = $.extend({}, this.defaults, this.options, this.metadata);

        if (is_multiple !== "") {
            multi_bar = '<div class="el_multi_bar" style="display: none">\n' +
                '<div class="button icon uncheck_all"><span class="material-icons">remove_done</span></div>\n' +
                '<div class="button icon done close_select"><span class="material-icons">highlight_off</span></div>\n' +
                '</div>';
        }
        let suggest_id = $this.attr("id") || "sug" + $this.index(".el_suggest");

        let new_suggest_id = "el_suggest_" + suggest_id;
        if ($this.attr("id") === "" || typeof $this.attr("id") === "undefined") {
            $this.attr("id", new_suggest_id);
        }

        if(!$this.next(".el_suggest_list").is("div")) {
            $this.after('<div class="el_suggest_list">' + multi_bar + newList.join("\n") + '</div></div>');
        }

        let $el_suggest = $("#" + new_suggest_id);
        let $el_suggest_list = $el_suggest.next(".el_suggest_list");

        this.params[new_suggest_id] = {
            is_multiple: is_multiple,
            is_disabled: is_disabled,
            suggest_id: suggest_id,
            suggest_name: $this.attr("name"),
            new_suggest_id: new_suggest_id
        };

        if($el_suggest_list.find(".el_option").is("div"))
            Plugin.prototype._bindOption.apply(that);

        //Изменения в нативном input пришли извне
        $this.off("input").on("input", function (e) {

            if(!is_disabled && $this.val().length >= 3) {
                let $selected_option, html,
                    source = that.metadata.source,
                    column = that.metadata.column,
                    value = that.metadata.value,
                    idAsValue = that.metadata.idAsValue;

                //$el_suggest_list.html("");
                $.post(that.config.url, {ajax: 1, action: "suggest", source: source,
                    column: column, value: value, search: $this.val()}, function(data){
                    $el_suggest_list.html("");
                    if(data.length > 0) {
                        let answer = JSON.parse(data);
                        if (typeof answer === "object") {
                            if(!$el_suggest_list.find(".el_multi_bar").is("div"))
                                $el_suggest_list.html("").append(multi_bar);
                            for (let i = 0; i < answer.length; i++) {
                                if(!$el_suggest_list.find(".el_option[data-value='" + answer[i].value + "']").is("div")) {
                                    let Value = (idAsValue) ? answer[i].id : answer[i].value;
                                    $el_suggest_list.append(buildOption(Value, answer[i].text,
                                        is_multiple, $this.attr("name"), false));
                                }
                            }
                            //Клик по пункту выпадающего списка
                            Plugin.prototype._bindOption.apply(that);

                            Plugin.prototype.open.call(that);
                        }
                    }else{
                        $el_suggest_list.html("Ничего не найдено");
                    }
                });
            }else{
                //Plugin.prototype.close.call(that);
            }
        });

        //Открыть или закрыть выпадающий список
        if (!is_disabled) {
            $this.next().find(".el_suggest").off("click").on("click", function () {
                if ($(this).next('.el_suggest_list').css("display") === "none") {
                    Plugin.prototype.open.call(that);
                } else {
                    Plugin.prototype.close.call(that);
                }
            });

            //Задание или удаление значений в select multiple
            if (is_multiple !== "") {
                let $el_multi_bar = $el_suggest_list.find(".el_multi_bar");

                $el_multi_bar.find(".uncheck_all").off("click").on("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $el_suggest_list.find(".el_option input:checkbox").each(function () {
                        if (!$(this).closest(".el_option").hasClass("disabled")) {
                            //Plugin.prototype._removeValue.apply(that, [$(this).closest(".el_option").data("value")]);
                            $(this).prop("checked", false).trigger("click");
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
                if ($this.next(".el_data").find(".el_suggest_list").css("display") === "block")
                    Plugin.prototype._setPosition.apply(that);
            });

            //Позиционирование при скроле или ресайзе окна попапа
            $this.closest(".pop_up").off("resize scroll").on("resize scroll", function () {
                if ($this.next(".el_data").find(".el_suggest_list").css("display") === "block")
                    Plugin.prototype._setPosition.apply(that);
            });

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

    Plugin.prototype._bindOption = function(){
        let $el_suggest_list = $(this.element).next(".el_suggest_list"),
            new_suggest_id = this.params[this.element.id].new_suggest_id,
            is_disabled = this.params[new_suggest_id].is_disabled,
            that = this;

        $el_suggest_list.find(".el_option").off("click").on("click", function (e) {
            e.stopPropagation();
            e.preventDefault();
            if (!is_disabled && !$(this).hasClass("disabled")) {
                let $checkbox = $(this).find("input[type=checkbox]");

                if ($checkbox.is("input") && $checkbox.prop("checked")) {
                    $checkbox.prop("checked", false);
                    //Plugin.prototype._removeValue.apply(that, [$(this).data("value")]);
                } else {
                    $checkbox.prop("checked", true);
                    //Plugin.prototype._setValue.apply(that, [$(this).data("value"), html]);
                }
                Plugin.prototype._setFilter.apply(that, [$checkbox]);
            }
        });
    }

    Plugin.prototype._getFilter = function(){
        let field = this.params[this.element.id].suggest_name.replace("filter_", "").replace("[]", ""),
            $el_suggest_list = $(this.element).next(".el_suggest_list"),
            query = el_tools.getUrlVar(decodeURIComponent(document.location.href)),
            new_suggest_id = this.params[this.element.id].new_suggest_id,
            is_multiple = this.params[new_suggest_id].is_multiple,
            that = this,
            multi_bar = "",
            q = [];



        if (typeof query.filter !== "undefined") {
            q = query.filter.split(";");

            $.each(q, function (f, v) {
                let fArr = v.split(":");

                let valArr = fArr[1].split("|");
                valArr = el_tools.array_clean(valArr);
                if (valArr.length > 0) {
                    //$el_suggest_list.append(multi_bar);
                    for(let i = 0; i < valArr.length; i++){
                        $el_suggest_list.append(buildOption(valArr[i], valArr[i],
                            that.params[that.element.id].is_multiple, $(that).attr("name"), true));
                    }
                }
                Plugin.prototype._bindOption.apply(that);
            });
        }
    }

    Plugin.prototype._setFilter = function(checkbox){
        let field = this.params[this.element.id].suggest_name.replace("filter_", "").replace("[]", ""),
            query = el_tools.getUrlVar(decodeURIComponent(document.location.href)),
            ch_value = checkbox.val(),
            q = [],
            paramExist = false,
            rq = [];

        for(let param in query){
            if(param !== "sort" && param !== "filter")
                rq.push(param + "=" + query[param]);
        }

        if (typeof query.sort !== "undefined") {
            rq.push("sort=" + query.sort);
        }

        if (typeof query.filter !== "undefined") {
            q = query.filter.split(";");
            q = el_tools.array_unique(q);

            for(let i = 0; i < q.length; i++){

                let fArr = q[i].split(":");

                if(fArr[0] === field) {

                    paramExist = true;

                    if (checkbox.prop("checked")) {

                        q[i] = field + ":" + fArr[1] + "|" + ch_value;

                    } else {

                        let valArr = fArr[1].split("|");

                        q.splice(q.indexOf(q[i]), 1);

                        const currValue = {value: ch_value};
                        valArr = valArr.filter(function (item) {
                            return item !== this.value;
                        }, currValue);

                        valArr = el_tools.array_clean(valArr);
                        valArr = el_tools.array_unique(valArr);
                        if (valArr.length > 0) {
                            q.push(fArr[0] + ":" + valArr.join("|"));
                        }

                    }
                }
            }
            if(!paramExist){
                q.push(field + ":" + ch_value);
            }
        } else {
            q.push(field + ":" + ch_value);
        }

        q = el_tools.array_clean(q);
        q = el_tools.array_unique(q);

        if (q.length > 0) {
            rq.push("filter=" + encodeURIComponent(q.join(";")));
        } else {
            rq.splice(rq.indexOf("filter="), 1);
        }

        el_app.setMainContent(document.location.pathname, rq.join("&"));
    }

    Plugin.prototype._removeValue = function (value) {
        let new_suggest_id = this.params[this.element.id].new_suggest_id,
            is_disabled = this.params[this.element.id].is_disabled,
            $select_row = $("#" + new_suggest_id + " .select_row[data-value='" + value + "']"),
            $select = $("#" + new_suggest_id);
        if ($select_row.is("div") && !is_disabled) {
            $select_row.remove();
            $select.next(".el_suggest_list").find(".el_option[data-value='" + value + "']").removeClass("selected");
            if (!$("#" + new_suggest_id + " .select_row").is("div"))
                $("#" + new_suggest_id + " .holder").show();
            $(this.element).find("option[value='" + value + "']").prop("selected", false);
            $select.next().find(".el_option[data-value='" + value + "'] input").prop("checked", false);
            Plugin.prototype._setPosition.apply(this);
        }
    }

    Plugin.prototype._setValue = function (value, text) {
        let new_suggest_id = this.element.id,
            is_disabled = this.params[new_suggest_id].is_disabled,
            $el_suggest = $("#" + new_suggest_id),
            $select = $el_suggest.closest(".el_data").prev("select"),
            $select_list = $el_suggest.next(".el_suggest_list"),
            $holder = $el_suggest.find(".holder"),
            $select_row = $el_suggest.find(".select_row"),
            is_multiple = this.params[new_suggest_id].is_multiple,
            that = this;

        if (is_multiple === "")
            $select.find("option").removeAttr("selected");
        this.$element.val(text)
        $select.trigger("el_suggest_change");

        if (value !== "" && typeof value !== "undefined") {
            $holder.hide();
            $el_suggest.parents(".el_data").removeClass("required");
            if (!$select_row.is("div")) {
                $el_suggest.append('<div class="select_row" data-value="' + value + '">' + text + '</div>');
                $select_row = $el_suggest.find(".select_row")
            }

            if (is_multiple === "")
                $select_list.find(".el_option").removeClass("selected");

            $select_list.find(".el_option[data-value='" + value + "']").addClass("selected");
            if (is_multiple !== "") {
                if (!$el_suggest.find(".select_row[data-value='" + value + "']").is("div")) {
                    $select_row.last()
                        .after('<div class="select_row" data-value="' + value + '">' + text + '</div>');
                }
                if(!is_disabled) {
                    $("#" + new_suggest_id + " .select_row").off("click").on("click", "span", function (e) {
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
            $el_suggest = $("#" + this.params[this.element.id].new_suggest_id),
            $el_suggest_list = $el_suggest.next(".el_suggest_list"),
            list_name = $(this.element).attr("name").replace("[]", ""),
            that = this;
        //Plugin.prototype._getFilter.apply(this);
        $(".el_suggest").removeClass("open").next(".el_suggest_list").slideUp(100);

        $el_suggest.addClass("open");

        $el_suggest_list.slideDown(100, function () {
            Plugin.prototype._setPosition.apply(that);
            if (is_multiple !== "")
                $(this).find(".el_multi_bar").show()
        });

        //Закрытие выпадающего списка при клике вне контрола
        /*$(document).off("click").on("click", function (e) {
            if (!$el_suggest.closest(".el_data").is(e.target)
                && $el_suggest.closest(".el_data").has(e.target).length === 0) {
                Plugin.prototype.close.apply(that);
                el_tools.setcookie("role_show_" + list_name, "close", "31 Dec 2120 23:59:59 GMT");
            }
        });*/
    }

    Plugin.prototype.close = function () {
        let is_multiple = this.params[this.element.id].is_multiple,
            $el_suggest = $("#" + this.element.id),
            that = this;
        //$("#control_wrapper").remove();
        $el_suggest.removeClass("open");
        $el_suggest.next(".el_suggest_list").slideUp(100, function () {
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
        let $el_suggest = $("#" + this.params[this.element.id].new_suggest_id),
            is_multiple = this.params[this.element.id].is_multiple,
            $el_suggest_list = $el_suggest.next(".el_suggest_list"),
            $el_multi_bar = $el_suggest_list.find(".el_multi_bar"),
            bar,
            open_position = (($(window).height() - ($el_suggest.offset().top - $(window).scrollTop()
                + $el_suggest.height())) < $el_suggest_list.height() + 10)
                ? "top" : "bottom",
            deflection = is_multiple ? 4 : 20;
            list_pos_css = {"top": (($el_suggest.height() + deflection) / 16) + "rem", "bottom": "auto"};
        if (open_position === "top") {
            list_pos_css = {"bottom": (($el_suggest.height() + deflection) / 16) + "rem", "top": "auto"};
            bar = $el_multi_bar.detach();
            $el_suggest_list.find(".el_option:last").after(bar);
        }else{
            bar = $el_multi_bar.detach();
            $el_suggest_list.find(".el_option:first").before(bar);
        }
        $el_suggest_list.css(list_pos_css);
        if (!$el_suggest_list.hasClass(open_position))
            $el_suggest_list.removeClass("top bottom").addClass(open_position);
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

    function buildOption(value, text, is_multiple, field_name, selected) {
        return is_multiple !== "" ?
            '<div class="el_option" data-value="' + value + '"><label class="container">'
            + text
            + '<input type="checkbox" name="' + field_name + '" value="' + value + '"' + ((selected) ? " checked" : "") + '>' +
            '<span class="checkmark"></span></label></div>'
            :
            '<div class="el_option" data-value="' + value + '">' + text + '</div>';
    }

    function getSelected(obj) {
        let selected = "";
        if (obj.selected && obj.value !== "" && obj.outerHTML.search("selected") > -1) {
            selected = '<div class="select_row" data-value="' + obj.value + '">'
                + obj.text + '</div>';
        }else{
            obj.defaultSelected = false;
            obj.selected = false;
        }
        return selected;
    }

})(jQuery, window, document);

$(document).ready(function () {
    $(".el_suggest").el_suggest();
})

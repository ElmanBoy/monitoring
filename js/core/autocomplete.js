/**
 * @file Элемент ввода с поиском в базе данных и выпадающей подсказкой
 * */
const el_autocomplete = {
	init: function(){
		let $ac = $(".el_autocomplete");

		for(let i = 0; i < $ac.length; i++) {
			if (!$($ac[i]).next(".el_select_list").is("div")) {
				$($ac[i]).after('<div class="el_select_list"></div>');
			}

			let $this = $($ac[i]),
				select_name = $($ac[i]).attr("name"),
				metadata = $this.data("src");

			if(metadata.target && !$this.prev(".el_select_value").is("input")){
				$($ac[i]).attr("name", select_name + "_view")
					.before('<input type="hidden" class="el_select_value" name="' + select_name + '">');
			}

			el_autocomplete.showClear($($ac[i]));

			$($ac[i]).off("input").on("input", function (e) {
				let $this = $(this),
					$el_suggest_list = $this.next(".el_select_list"),
					is_multiple = $this.attr("multiple") ? "multiple" : "",
					is_disabled = $this.attr("disabled") || false,
					metadata = $this.data("src");

				if (!is_disabled && $this.val().length >= 2) {

					el_autocomplete.showClear($($ac[i]));

					$.post("/", {
						ajax: 1, action: "suggest", source: metadata.source,
						column: metadata.column, value: metadata.value, parent: metadata.parent, search: $this.val()
					}, function (data) {
						if (data !== "[]") {
							let answer = JSON.parse(data);
							if (typeof answer === "object") {
								$el_suggest_list.html("");
								for (let i = 0; i < answer.length; i++) {
									if (!$el_suggest_list.find(".el_option[data-value='" + answer[i].value + "']").is("div")) {
										$el_suggest_list.append(el_autocomplete.buildOption(answer[i].id,
											answer[i].value, answer[i].text,
											is_multiple, $this.attr("name"), false));
									}
								}
								el_autocomplete.open($($ac[i]));
							}
						}else{
							el_autocomplete.close($($ac[i]));
						}
					});
				}

				if($this.val() === ""){
					$this.prev(".el_select_value").val("");
				}
			});

			//Позиционирование при скроле или ресайзе окна браузера
			$(window).off("resize scroll").on("resize scroll", function () {
				if ($($ac[i]).next(".el_data").find(".el_select_list").css("display") === "block")
					el_autocomplete.setPosition($($ac[i]));
			});

			//Позиционирование при скроле или ресайзе окна попапа
			$($ac[i]).closest(".pop_up").off("resize scroll").on("resize scroll", function () {
				if ($($ac[i]).next(".el_data").find(".el_select_list").css("display") === "block")
					el_autocomplete.setPosition($($ac[i]));
			});
		}
	},

	open: function($el_suggest){
		let $el_suggest_list = $el_suggest.next(".el_select_list");

		$el_suggest_list.slideDown(100, function () {
			el_autocomplete.setPosition($el_suggest);
		});

		$el_suggest.off("keydown").on("keydown", function(e){

			if(e.which === 40){ //Клавиша вниз
				let $selected = $el_suggest_list.find(".el_option.selected");
				if($selected.is("div")){
					let $next = $selected.next(".el_option");
					$el_suggest_list.find(".el_option").removeClass("selected");
					$next.addClass("selected");
				}else{
					$el_suggest_list.find(".el_option").first().addClass("selected");
				}
			}
			if(e.which === 38){ //Клавиша вверх
				let $selected = $el_suggest_list.find(".el_option.selected"),
					$prev = $selected.prev(".el_option");
				$selected.removeClass("selected");
				$prev.addClass("selected");
			}
			if(e.which === 13){ //Enter
				e.stopPropagation();
				e.preventDefault();
				el_autocomplete.pick($el_suggest);
			}
		});

		$el_suggest_list.find(".el_option").on("click", function(e){
			e.stopPropagation();
			//e.preventDefault();
			$el_suggest_list.find(".el_option").removeClass("selected");
			$(this).addClass("selected");
			el_autocomplete.pick($el_suggest);
		});

		//Закрытие выпадающего списка при клике вне контрола
		$(document).off("click").on("click", function (e) {
			if (!$el_suggest.closest(".el_data").is(e.target)
				&& $el_suggest.closest(".el_data").has(e.target).length === 0) {
				el_autocomplete.close($el_suggest);
			}
		});
	},

	close: function($el_suggest){
		$el_suggest.next(".el_select_list").slideUp(100, function () {
			$el_suggest.off("keydown");
			$(document).off("click");
		})
	},

	showClear: function($el_suggest){
		let $label = $el_suggest.closest(".el_data").find("label");
		if($el_suggest.val() !== "" && !$label.next(".clear").is("div")) {
			$label.after("<div class='clear material-icons' title='Очистить'>close</div>");
			$label.next(".clear").on("click", function () { console.log($el_suggest)
				$el_suggest.val("");
				$el_suggest.prev(".el_select_value").val("");
				$(this).remove();
			});
		}
	},

	pick: function($el_suggest){
		let $el_suggest_list = $el_suggest.next(".el_select_list"),
			$selected = $el_suggest_list.find(".el_option.selected"),
			metadata = $el_suggest.data("src");

		$el_suggest.val($selected.data("value"));

		el_autocomplete.showClear($el_suggest);

		if(metadata.target){
			let $el_select_value = $el_suggest.prev(".el_select_value");
			$el_select_value.val($selected.data("id"));
			$el_select_value.trigger("change input");
		}
		el_autocomplete.close($el_suggest);
	},

	buildOption: function(id, value, text, is_multiple, field_name, selected) {
		return is_multiple !== "" ?
			'<div class="el_option" data-value="' + value + '" data-id="' + id + '">' +
			'<label class="container">' + text
			+ '<input type="checkbox" name="' + field_name + '" value="' + value + '"' + ((selected) ? " checked" : "") + '>' +
			'<span class="checkmark"></span></label></div>'
			:
			'<div class="el_option" data-value="' + value + '" data-id="' + id + '">' + text + '</div>';
	},

	setPosition: function ($el_suggest) {
		let $el_suggest_list = $el_suggest.next(".el_select_list"),
			open_position = (($(window).height() - ($el_suggest.offset().top - $(window).scrollTop()
				+ $el_suggest.height())) < $el_suggest_list.height() + 10)
				? "top" : "bottom",
			list_pos_css = "";

		list_pos_css = {"top": (($el_suggest.height()) / 16 + 1.15) + "rem", "bottom": "auto"};
		if (open_position === "top") {
			list_pos_css = {"bottom": (($el_suggest.height()) / 16) + "rem", "top": "auto"};
		}
		$el_suggest_list.css(list_pos_css);
		if (!$el_suggest_list.hasClass(open_position))
			$el_suggest_list.removeClass("top bottom").addClass(open_position);
	}
}
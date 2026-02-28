/**
 * @file Элемент ввода с выпадающей подсказкой
 */
const el_suggest = {
	init: function(){
		let $ac = $(".el_suggest");

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
						column: metadata.column, value: metadata.value, search: $this.val()
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
	}
}
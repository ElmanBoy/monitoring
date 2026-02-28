
function simpleIconPicker(selector)
{
    this.pickerEle = document.querySelector(selector);
    this.inputEle = this.pickerEle.querySelector(".tm-icon-picker-input-text");
		this.iconsGridEle = this.pickerEle.querySelector(".icons-grid");
		this.selectedIconEle = this.pickerEle.querySelector(".tm-icon-picker-append");

		this.iconsloaded = false;
		this.allIcons = [];
		this.selectedIcon = '';


		this.showIconGrid = () => {
	      this.iconsGridEle.style.display = "block";
		}

		this.hideIconGrid = () => {
	      this.iconsGridEle.style.display = "none";
		}

		this.fetchIcons = (callback = null) => {
				fetch("/modules/registry/props/js/icon-picker/json/material-design-icons.json").then((response) => {
					response.json().then(data => {

	            this.iconsloaded = true;
			        this.allIcons = data;

					    if(callback) {
					    	callback();
					    }
					});
					
				})
				.catch(error => {
					console.error(error);
				});
		}

		this.filterIcons = (query) => {
			let filtered = [];

			if(query) {
				filtered = this.allIcons.filter(icon => {
					return icon.includes(query);
				});
			} else {
				filtered = this.allIcons;
			}

			return filtered;
		}

		window.selectIcon = e => {
				const icon = e.dataset.icon;

				this.selectedIcon = icon;

				this.selectedIconEle.innerHTML = `<i class="material-icons mdi mdi-${this.selectedIcon}"></i>`;
				this.inputEle.value = this.selectedIcon;

				this.hideIconGrid();
		}

		this.renderIcons = (icons) => {
			if(icons) {
				let iconHtml = '';

				icons.forEach(icon => {
					iconHtml += `<div class='icon' data-icon='${icon}' onclick='selectIcon(this)'><i class="material-icons mdi mdi-${icon}"></i></div>`;
				});

				this.iconsGridEle.innerHTML = iconHtml;
			}
		}

		this.handleInputFocus = e => {
			const enteredValue = e.target.value;

			this.showIconGrid();

			if(!this.iconsloaded) {
				this.fetchIcons(() => {
					let filteredIcons = this.filterIcons(enteredValue);

					this.renderIcons(filteredIcons);
				});
			} else {
				let filteredIcons = this.filterIcons(enteredValue);
		    this.renderIcons(filteredIcons);
			}
		}

		this.inputEle.addEventListener('focus', this.handleInputFocus);    // handling input focus
		
		this.inputEle.addEventListener('keyup', (e) => {
			const enteredValue = e.target.value;
			
			let filteredIcons = this.filterIcons(enteredValue);

			this.renderIcons(filteredIcons);
		});

		window.addEventListener('click', (e) => {                          // handling click outside input
		  if (!this.inputEle.contains(e.target) && !this.iconsGridEle.contains(e.target)){
		    this.hideIconGrid();
		  }
  	});


}

const picker = new simpleIconPicker('.tm-icon-picker');
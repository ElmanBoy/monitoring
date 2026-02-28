/*
  * MMK Premium Calendar v1.0 (https://premiumcalendar.malleeshkanna.com/)
  * Copyright 2025 Malleeshkanna (https://malleeshkanna.com)
  * Licensed under MIT (https://premiumcalendar.malleeshkanna.com/license)
  */
 
class MMKPremiumCalendar {
  constructor(container, config = {}) {
    this.container = document.querySelector(container);
    this.config = Object.assign(
      {
        type: "range",
        color: "#4f46e5",
        disablePrevDates: false,
        disableFutureDates: false, // NEW OPTION
        shape: "default",
        handleChange: null,
        onMonthChange: null,
        disableDays: [],
        datesupto: null,
        dateRange: "onlyRange",
        disabledDates: [],
        darkMode: false, 
      },
      config
    );

    this.state = {
      currentMonth: luxon.DateTime.now().startOf("month"),
      selectedDates: [],
    };

    this.prevRenderedMonth = null;
    this.init();
  }

  init() {
    this.render();
    this.attachEvents();
  }

  render() {
    this.container.innerHTML = ""; // Clear existing

    const wrapper = document.createElement("div");
    wrapper.className = "mmk-calendar-container";

    if (this.config.darkMode) {
      wrapper.classList.add("dark");
    }

    const header = document.createElement("div");
    header.className = "calendar-header";

    const prevBtn = document.createElement("button");
    prevBtn.id = "prevMonth";
    prevBtn.innerHTML = "<i class='left-chevron mmk-icon'></i>";

    const monthSelect = document.createElement("select");
    monthSelect.className = "mmk-calendar-input";
    monthSelect.id = "MmkmonthSelect";

    const yearInput = document.createElement("input");
    yearInput.type = "number";
    yearInput.className = "mmk-calendar-input";
    yearInput.id = "yearInput";

    const nextBtn = document.createElement("button");
    nextBtn.id = "nextMonth";
    nextBtn.innerHTML = "<i class='right-chevron mmk-icon'></i>";

    header.append(prevBtn, monthSelect, yearInput, nextBtn);

    const gridWrapper = document.createElement("div");
    gridWrapper.className = "calendar-grid-wrapper";

    const calendarDays = document.createElement("div");
    calendarDays.className = "calendar-grid fade";
    calendarDays.id = "calendarDays";

    gridWrapper.appendChild(calendarDays);

    const todayWrapper = document.createElement("div");
    todayWrapper.style.marginTop = "1rem";
    todayWrapper.style.textAlign = "center";

    const todayBtn = document.createElement("button");
    todayBtn.id = "MmktodayBtn";
    todayBtn.textContent = "Сегодня";

    todayWrapper.appendChild(todayBtn);

    wrapper.append(header, gridWrapper, todayWrapper);
    this.container.appendChild(wrapper);
    this.updateStyles();
    this.renderCalendar();
    this.updateNavButtons();
    this.updateMonthYearFields();
  }

  getMonthOptions() {
    return luxon.Info.months("long")
      .map((month, i) => `<option value="${i}">${month}</option>`)
      .join("");
  }

  updateStyles() {
    document.documentElement.style.setProperty(
      "--selected-color",
      this.config.color
    );
    document.documentElement.style.setProperty(
      "--button-bg",
      this.config.color
    );
    document.documentElement.style.setProperty(
      "--highlight-color",
      this.config.color
    );
  }

  updateMonthYearFields() {
    const yearInput = this.container.querySelector("#yearInput");

    const { DateTime } = luxon;
    const now = DateTime.now();
    const minYear = this.config.disablePrevDates ? now.year : 1950;
    const maxYear = this.config.disableFutureDates
      ? now.year
      : this.config.datesupto
      ? DateTime.fromISO(this.config.datesupto).year
      : now.year;

    yearInput.min = minYear;
    yearInput.max = maxYear;
    yearInput.value = this.state.currentMonth.year;

    yearInput.addEventListener("change", () => {
      let val = parseInt(yearInput.value, 10);
      if (val < minYear) val = minYear;
      if (val > maxYear) val = maxYear;
      this.state.currentMonth = this.state.currentMonth.set({ year: val });
      this.renderMonthOptions();
      this.renderCalendar({ animate: true });
    });

    this.renderMonthOptions();
  }

  renderMonthOptions() {
    const monthSelect = this.container.querySelector("#MmkmonthSelect");
    const { DateTime, Info } = luxon;
    const now = DateTime.now();
    const currentYear = this.state.currentMonth.year;

    const minMonth =
      this.config.disablePrevDates && currentYear === now.year ? now.month : 1;
    const maxMonth =
      this.config.disableFutureDates && currentYear === now.year
        ? now.month
        : this.config.datesupto &&
          currentYear === DateTime.fromISO(this.config.datesupto).year
        ? DateTime.fromISO(this.config.datesupto).month
        : 12;

    monthSelect.innerHTML = Info.months("long")
      .map((month, i) => {
        const monthIndex = i + 1;
        const isDisabled = monthIndex < minMonth || monthIndex > maxMonth;
        return `<option value="${i}" ${
          isDisabled ? "disabled" : ""
        }>${month}</option>`;
      })
      .join("");

    monthSelect.value = this.state.currentMonth.month - 1;

    monthSelect.addEventListener("change", () => {
      const selectedMonth = parseInt(monthSelect.value, 10) + 1;
      this.state.currentMonth = this.state.currentMonth.set({
        month: selectedMonth,
      });
      this.renderCalendar({ animate: true });
    });
  }

  renderCalendar({ animate = false } = {}) {
    const calendarDays = this.container.querySelector("#calendarDays");
    const { DateTime } = luxon;

    if (animate) {
      calendarDays.classList.remove("fade-in");
      void calendarDays.offsetWidth;
    }

    let start = this.state.currentMonth.startOf("month");
    while (start.weekday !== 7) start = start.minus({ days: 1 });

    let end = this.state.currentMonth.endOf("month");
    while (end.weekday !== 6) end = end.plus({ days: 1 });

    const today = DateTime.now().toISODate();
    const disableDaysMap = {
      mon: 1,
      tue: 2,
      wed: 3,
      thu: 4,
      fri: 5,
      sat: 6,
      sun: 7,
    };
    const disabledWeekdays = this.config.disableDays.map(
      (d) => disableDaysMap[d.toLowerCase()]
    );
    let current = start;

    calendarDays.innerHTML = "";
    ["Вос", "Пон", "Втр", "Срд", "Чтв", "Пят", "Суб"].forEach((d) => {
      const div = document.createElement("div");
      div.className = "day-name";
      div.textContent = d;
      calendarDays.appendChild(div);
    });

    while (current <= end) {
      const div = document.createElement("div");
      div.className = "day";
      div.textContent = current.day;
      const iso = current.toISODate();

      const isToday = iso === today;
      const isBeforeToday =
        this.config.disablePrevDates && current < DateTime.now().startOf("day");
      const isAfterMaxDate =
        (this.config.datesupto &&
          current > DateTime.fromISO(this.config.datesupto)) ||
        (this.config.disableFutureDates &&
          current > DateTime.now().startOf("day"));
      const isDisabledDate = this.config.disabledDates.includes(iso);
      const isWeekdayDisabled = disabledWeekdays.includes(current.weekday);

      if (isToday) div.classList.add("today");
      if (
        isBeforeToday ||
        isAfterMaxDate ||
        isDisabledDate ||
        isWeekdayDisabled
      ) {
        div.classList.add("disabled");
        if (isWeekdayDisabled) div.style.color = "red";
      }

      if (
        this.config.type === "range" &&
        this.state.selectedDates.length === 2
      ) {
        const [startDate, endDate] = this.state.selectedDates;
        if (iso > startDate && iso < endDate) {
          div.classList.add("in-range");
        }
      }

      if (this.state.selectedDates.includes(iso)) {
        div.classList.add("selected");
      }

      if (this.config.shape === "circle") {
        div.style.borderRadius = "50%";
      }

      div.addEventListener("click", () => this.handleDateClick(iso));
      calendarDays.appendChild(div);
      current = current.plus({ days: 1 });
    }

    if (
      typeof this.config.onMonthChange === "function" &&
      (!this.prevRenderedMonth ||
        !this.state.currentMonth.hasSame(this.prevRenderedMonth, "month"))
    ) {
      const monthNumber = parseInt(this.state.currentMonth.toFormat("M"));
      const monthName = this.state.currentMonth.toFormat("MMM").toLowerCase();
      const year = parseInt(this.state.currentMonth.toFormat("yyyy"));
      
      this.config.onMonthChange({ month: monthName, code: monthNumber, year });
    }
    
    this.prevRenderedMonth = this.state.currentMonth;
    this.updateNavButtons();
    this.updateMonthYearFields();

    if (animate) {
      calendarDays.classList.add("fade-in");
    }
  }

  handleDateClick(iso) {
    const { DateTime } = luxon;
    const date = DateTime.fromISO(iso);
    const disableDaysMap = {
      mon: 1,
      tue: 2,
      wed: 3,
      thu: 4,
      fri: 5,
      sat: 6,
      sun: 7,
    };
    const disabledWeekdays = this.config.disableDays.map(
      (d) => disableDaysMap[d.toLowerCase()]
    );
    const isBeforeToday =
      this.config.disablePrevDates && date < DateTime.now().startOf("day");
    const isAfterMaxDate =
      (this.config.datesupto &&
        date > DateTime.fromISO(this.config.datesupto)) ||
      (this.config.disableFutureDates && date > DateTime.now().startOf("day"));
    const isDisabled =
      this.config.disabledDates.includes(iso) ||
      disabledWeekdays.includes(date.weekday);

    if (isBeforeToday || isAfterMaxDate || isDisabled) return;

    if (this.config.type === "single") {
      this.state.selectedDates = [iso];
    } else if (this.config.type === "range") {
      if (this.state.selectedDates.length < 2) {
        this.state.selectedDates.push(iso);
        this.state.selectedDates.sort();
      } else {
        this.state.selectedDates = [iso];
      }
    }

    if (typeof this.config.handleChange === "function") {
      let selectedOutput = [...this.state.selectedDates];

      if (
        this.config.type === "range" &&
        this.state.selectedDates.length === 2 &&
        this.config.dateRange === "all"
      ) {
        const [startStr, endStr] = this.state.selectedDates;
        let dates = [];
        let current = DateTime.fromISO(startStr);
        const end = DateTime.fromISO(endStr);
        while (current <= end) {
          const iso = current.toISODate();
          const isWeekdayDisabled = disabledWeekdays.includes(current.weekday);
          if (!this.config.disabledDates.includes(iso) && !isWeekdayDisabled) {
            dates.push(iso);
          }
          current = current.plus({ days: 1 });
        }
        selectedOutput = dates;
      }

      this.config.handleChange(selectedOutput);
    }

    this.renderCalendar();
  }

  updateNavButtons() {
    const prevBtn = this.container.querySelector("#prevMonth");
    const nextBtn = this.container.querySelector("#nextMonth");
    const today = luxon.DateTime.now().startOf("day");

    const prevMonthStart = this.state.currentMonth
      .minus({ months: 1 })
      .endOf("month");
    prevBtn.disabled = this.config.disablePrevDates && prevMonthStart < today;

    const nextMonthStart = this.state.currentMonth
      .plus({ months: 1 })
      .startOf("month");
    const datesUpto = this.config.datesupto
      ? luxon.DateTime.fromISO(this.config.datesupto)
      : null;
    const isBeyondDatesUpto = datesUpto && nextMonthStart > datesUpto;
    const isBeyondToday =
      this.config.disableFutureDates && nextMonthStart > today;

    nextBtn.disabled = isBeyondDatesUpto || isBeyondToday;
  }

  attachEvents() {
    this.container.querySelector("#prevMonth").addEventListener("click", () => {
      this.state.currentMonth = this.state.currentMonth.minus({ months: 1 });
      this.renderCalendar({ animate: true });
    });

    this.container.querySelector("#nextMonth").addEventListener("click", () => {
      const nextMonthStart = this.state.currentMonth
        .plus({ months: 1 })
        .startOf("month");
      const datesUpto = this.config.datesupto
        ? luxon.DateTime.fromISO(this.config.datesupto)
        : null;
      const today = luxon.DateTime.now().startOf("day");
      if (
        (datesUpto && nextMonthStart > datesUpto) ||
        (this.config.disableFutureDates && nextMonthStart > today)
      )
        return;
      this.state.currentMonth = nextMonthStart;
      this.renderCalendar({ animate: true });
    });

    this.container
      .querySelector("#MmktodayBtn")
      .addEventListener("click", () => {
        const now = luxon.DateTime.now();
        const todayISO = now.toISODate();
        this.state.currentMonth = now.startOf("month");
        this.state.selectedDates = [todayISO];
        this.renderCalendar({ animate: true });

        if (typeof this.config.handleChange === "function") {
          this.config.handleChange([todayISO]);
        }
      });

    this.addSwipeListeners();
  }

  addSwipeListeners() {
    const calendar = this.container.querySelector("#calendarDays");
    let touchStartX = 0;
    let touchEndX = 0;

    calendar.addEventListener("touchstart", (e) => {
      touchStartX = e.changedTouches[0].screenX;
    });

    calendar.addEventListener("touchend", (e) => {
      touchEndX = e.changedTouches[0].screenX;
      const deltaX = touchEndX - touchStartX;

      if (Math.abs(deltaX) > 50) {
        const today = luxon.DateTime.now().startOf("day");
        if (deltaX < 0) {
          const nextMonthStart = this.state.currentMonth
            .plus({ months: 1 })
            .startOf("month");
          const datesUpto = this.config.datesupto
            ? luxon.DateTime.fromISO(this.config.datesupto)
            : null;
          if (
            (datesUpto && nextMonthStart > datesUpto) ||
            (this.config.disableFutureDates && nextMonthStart > today)
          )
            return;
          this.state.currentMonth = nextMonthStart;
        } else {
          const prevMonthEnd = this.state.currentMonth
            .minus({ months: 1 })
            .endOf("month");
          if (!this.config.disablePrevDates || prevMonthEnd >= today) {
            this.state.currentMonth = this.state.currentMonth.minus({
              months: 1,
            });
          } else return;

        }

        this.renderCalendar({ animate: true });
      }
    });
  }
}

export default MMKPremiumCalendar;
$(document).ready(function(){
    el_app.mainInit();
    el_stat.create_init();

    $("#top_filter .search-choice-close").on("click", function(){
        el_stat.deleteFromFilter($(this).data("value"));
    })
});

var el_stat = {
    //Инициализация контролов в разделе "Статистика"
    create_init: function(){

        if(el_app.calendars.hasOwnProperty("top_calendar")) {
            if("destroy" in el_app.calendars.top_calendar) {
                el_app.calendars.top_calendar.destroy();
            }
            delete el_app.calendars.top_calendar;
        }

        el_app.calendars.top_calendar = $("#top_calendar").flatpickr({
            locale: 'ru',
            mode: 'range',
            time_24hr: true,
            dateFormat: 'Y-m-d',
            altFormat: 'd.m.Y',
            conjunction: '-',
            altInput: true,
            altInputClass: "el_input",
            firstDayOfWeek: 1,
            //defaultDate: el_main.getDefaultDates(),
            onChange: function(selectedDates, dateStr, instance) {
                let dateStart = el_tools.dateFormat(selectedDates[0]),
                    dateEnd = el_tools.dateFormat(selectedDates[1]);

                if(dateStart !== "" && dateEnd !== "") {
                    let query = el_tools.getUrlVar(document.location.href),
                        rq = [],
                        q = [],
                        dates = 0;

                    if (typeof query.sort !== "undefined") {
                        rq.push("sort=" + query.sort);
                    }

                    if (typeof query.filter !== "undefined") {
                        q = query.filter.split(";");
                    }

                    for(let i = 0; i < q.length; i++){
                        let qArr = q[i].split(":");
                        if(qArr[0] === "date_from"){
                            q[i] = "date_from:" + dateStart;
                            dates++;
                        }
                        if(qArr[0] === "date_to"){
                            q[i] = "date_to:" + dateEnd;
                            dates++;
                        }
                    }
                    if(dates === 0) {
                        q.push("date_from:" + dateStart);
                        q.push("date_to:" + dateEnd);
                    }

                    q = el_tools.array_clean(q);
                    q = el_tools.array_unique(q);

                    if (q.length > 0) {
                        rq.push("filter=" + q.join(";"));
                    } else {
                        rq.splice(rq.indexOf("filter="), 1);
                    }

                    el_app.setMainContent(document.location.pathname, rq.join("&"));
                }
            }
        });

        $("#export_excel").on("click", function(e){
            e.preventDefault();
            $.ajax({
                url: "/modules/statistic/ajaxHandlers/export_excel2.php",
                dataType: 'binary',
                data: document.location.search,
                xhrFields: {
                    'responseType': 'blob'
                },
                success: function(data, status, xhr) {
                    var blob = new Blob([data], {type: xhr.getResponseHeader('Content-Type')});
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = "report.xlsx";
                    link.click();
                }
            });
        });

        el_app.sort_init();
        el_app.filter_init();
    },

    parseFilterParams(filterParamName, value){
        let pair = "";
        if(filterParamName === "m.age"){
            switch(parseInt(value)){
                case 1: pair = "m.age_to:18";
                    break;
                case 4: pair = "m.age_from:18;m.age_to:35";
                    break;
                case 3: pair = "m.age_from:36;m.age_to:55";
                    break;
                case 2: pair = "m.age_from:56";
                    break;
                default: pair = filterParamName + ":" + value;
            }
        }else if(filterParamName === "a.claim_category") {
            pair = "a.category:" + value;
        }else{
            pair = filterParamName + ":" + value;
        }

        return pair;
    },

    setFilterFromPie(params, filterParamName){
        let query = el_tools.getUrlVar(document.location.href),
            rq = [],
            q = [],
            field = 0,
            curr_value = 0,
            curr_index = 0,
            value = params.data.id,
            is_selected = params.event.target.selected,
            new_string = filterParamName + ":" + value;//el_stat.parseFilterParams(filterParamName, value);

        if (typeof query.filter !== "undefined") {
            q = query.filter.split(";");
            for(let i = 0; i < q.length; i++){
                let qArr = q[i].split(":");
                if(qArr[0] === filterParamName){
                    q[i] = new_string;
                    curr_value = parseInt(qArr[1]);
                    curr_index = i;
                    field++;
                }
            }
        }

        if(field === 0) {
            q.push(new_string);
        }
        /*if(filterParamName === "a.claim_category"){
            q.push("a.is_claim:1")
        }else{
            if(el_tools.in_array("a.is_claim:1", q, false)) {
                q.splice(q.indexOf("a.is_claim:1"), 1);
            }
        }*/
        if(value === curr_value && !is_selected){
            q.splice(curr_index, 1);
        }

        q = el_tools.array_clean(q);
        q = el_tools.array_unique(q);

        if (q.length > 0) {
            rq.push("filter=" + q.join(";"));
        } else {
            rq.splice(rq.indexOf("filter="), 1);
        }

        el_app.setMainContent(document.location.pathname, el_tools.mergeDuplicateParams(rq.join("&")));
    },

    deleteFromFilter(params){
        let query = el_tools.getUrlVar(document.location.href),
            q = [],
            rq = [],
            qString = "",
            paramsArr = params.split(':'),
            filterParamName = paramsArr[0];

        if (typeof query.filter !== "undefined") {
            q = query.filter.split(";");

            for(let i = 0; i < q.length; i++){
                let qArr = q[i].split(":");
                if(qArr[0] !== filterParamName){
                    rq.push(qArr[0] + ":" + qArr[1]);
                }
            }
        }

        rq = el_tools.array_clean(rq);
        rq = el_tools.array_unique(rq);

        if (rq.length > 0) {
            qString = "?filter=" + rq.join(";");
        }
        el_app.setMainContent(document.location.pathname, qString);
    },

    setFilterFromBar(params, filterParamName){
        let query = el_tools.getUrlVar(document.location.href),
            rq = [],
            q = [],
            field = 0,
            curr_value = 0,
            curr_index = 0,
            value = params.data.id,
            is_selected = params.event.target.selected;

        if (typeof query.filter !== "undefined") {
            q = query.filter.split(";");
        }

        for(let i = 0; i < q.length; i++){
            let qArr = q[i].split(":");
            if(qArr[0] === filterParamName){
                q[i] = filterParamName + ":" + value;
                curr_value = parseInt(qArr[1]);
                curr_index = i;
                field++;
            }
        }

        if(field === 0) {
            q.push(filterParamName + ":" + value);
        }
        if(value === curr_value || !is_selected){
            q.splice(curr_index, 1);
        }

        q = el_tools.array_clean(q);
        q = el_tools.array_unique(q);

        if (q.length > 0) {
            rq.push("filter=" + q.join(";"));
        } else {
            rq.splice(rq.indexOf("filter="), 1);
        }

        el_app.setMainContent(document.location.pathname, rq.join("&"));
    },

    setFilterFromTop(){
        $(".topQuestions tr").on("click", function(){
            let value = encodeURIComponent($(this).find("td:nth-child(2)").text()),
                query = el_tools.getUrlVar(document.location.href),
                rq = [],
                q = [],
                field = 0,
                curr_value = "",
                curr_index = 0,
                is_selected = $(this).hasClass("selected");

            $(".topQuestions tr").removeClass("selected");
            $(this).addClass("selected");

            if (typeof query.filter !== "undefined" && query !== {}) {
                q = query.filter.split(";");
            }

            for(let i = 0; i < q.length; i++){
                let qArr = q[i].split(":");
                if(qArr[0] === "a.question"){
                    q[i] = "a.question" + ":" + value;
                    curr_value = qArr[1];
                    curr_index = i;
                    field++;
                }
            }

            if(field === 0) {
                q.push("a.question" + ":" + value);
            }
            if(value === curr_value || is_selected){
                q.splice(curr_index, 1);
                $(this).removeClass("selected");
            }

            q = el_tools.array_clean(q);
            q = el_tools.array_unique(q);

            if (q.length > 0) {
                rq.push("filter=" + q.join(";"));
            } else {
                rq.splice(rq.indexOf("filter="), 1);
            }

            el_app.setMainContent(document.location.pathname, rq.join("&"));
        });

    },

    setViewFromDynamic(){
        $(".dynamicViewMode").on("click", function(e){
            let query = el_tools.getUrlVar(document.location.href),
                qr = [];

            query.dvm = $(this).data("mode");

            for(let q in query){
                if(query.hasOwnProperty(q))
                    qr.push(q + "=" + query[q]);
            }

            el_app.setMainContent(document.location.pathname, qr.join("&"), false);
        })
    },

    setFilterFromCallers(){
        $(".topCallers tr").on("click", function(){
            let value = encodeURIComponent($(this).find("td:nth-child(2)").text()),
                query = el_tools.getUrlVar(document.location.href),
                rq = [],
                q = [],
                field = 0,
                curr_value = "",
                curr_index = 0,
                is_selected = $(this).hasClass("selected");

            $(".topQuestions tr").removeClass("selected");
            $(this).addClass("selected");

            if (typeof query.filter !== "undefined" && query !== {}) {
                q = query.filter.split(";");
            }

            for(let i = 0; i < q.length; i++){
                let qArr = q[i].split(":");
                if(qArr[0] === "m.phone"){
                    q[i] = "m.phone" + ":" + value;
                    curr_value = qArr[1];
                    curr_index = i;
                    field++;
                }
            }

            if(field === 0) {
                q.push("m.phone" + ":" + value);
            }
            if(value === curr_value || is_selected){
                q.splice(curr_index, 1);
                $(this).removeClass("selected");
            }

            q = el_tools.array_clean(q);
            q = el_tools.array_unique(q);

            if (q.length > 0) {
                rq.push("filter=" + q.join(";"));
            } else {
                rq.splice(rq.indexOf("filter="), 1);
            }

            el_app.setMainContent(document.location.pathname, rq.join("&"));
        });

    },

    setChartSelected(chartObj, filterParamName){
        let query = el_tools.getUrlVar(document.location.href),
            options = chartObj.getOption(),
            data = options.series[0].data,
            find = 0,
            q = "";
        if (typeof query.filter !== "undefined") {
            q = query.filter.split(";");
        }
        if(typeof q !== "undefined") {
            for (let i = 0; i < q.length; i++) {
                let qArr = q[i].split(":");

                if (qArr[0] === filterParamName) {
                    for (let c = 0; c < data.length; c++) {
                        let values = qArr[1].split('|');
                        for(let v = 0; v < values.length; v++) {
                            if (data[c].id === parseInt(values[v]) || data[c].id === values[v]) {
                                chartObj.dispatchAction({
                                    type: 'select',
                                    dataIndex: c
                                });
                                find++;
                            }
                        }
                    }
                }
            }
        }
    },

    getDefaultDates: function(){
        let urlVars = el_tools.getUrlVar(document.location.href);
        if(typeof urlVars.filter !== "undefined") {
            let datesFilter = urlVars.filter,
                datesArr = datesFilter.split(";"),
                datesFromArr = [],
                datesToArr = [];

            datesArr = el_tools.array_unique(datesArr);

            if(typeof datesArr[0] !== "undefined" && datesArr[0] === "date_from") {
                datesFromArr = datesArr[0].split(":");
            }
            if(typeof datesArr[1] !== "undefined" && datesArr[1] === "date_to") {
                datesToArr = datesArr[1].split(":");
            }

            return [datesFromArr[1], datesToArr[1]];
        }else{
            return [];
        }
    }


};

$(document).ready(function (){
    $('#stat_type_list').off('change').on('change', function () { console.log(parseInt($(this).val()))
        let params = (parseInt($(this).val()) > 0) ? 'stat_type=' + parseInt($(this).val()) : '';
        el_app.setMainContent('/statistic', params);
    });
});
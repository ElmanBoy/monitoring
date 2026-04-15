$(document).ready(function () {
    el_app.mainInit();

    // Открыть график устранения
    $(document).off('click', '.btn-open-road').on('click', '.btn-open-road', function () {
        var roadId = $(this).data('road-id');
        el_app.dialog_open('view_road', {roadId: roadId}, 'roadmap');
    });

    // Двойной клик на строке — открыть график
    $(document).off('dblclick', '#tbl_violations tbody tr').on('dblclick', '#tbl_violations tbody tr', function () {
        var roadId = $(this).data('road-id');
        if (roadId) {
            el_app.dialog_open('view_road', {roadId: roadId}, 'roadmap');
        }
    });

    // Фильтр по статусу
    $(document).off('click', '.violations-filter__btn').on('click', '.violations-filter__btn', function () {
        var status = $(this).data('status');
        el_app.setMainContent('/violations', 'filter_fix_status=' + status);
    });
});

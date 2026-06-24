$(document).ready(function () {

    $('#searchForm').on('submit', function (e) {

        const rank = parseInt($('#rank').val()) || 0;
        const threshold = parseInt($('#threshold').val()) || 0;

        if (threshold > rank) {
            alert('Threshold cannot be greater than your rank.');
            $('#threshold').focus();
            e.preventDefault();
            return false;
        }
    });

    $('#rank').on('input', function () {

        const rank = parseInt($(this).val()) || 0;

        $('#threshold').attr('max', rank);

    });

    $('#resetFilters').on('click', function () {
        $('#filterType').val('');
        $('#filterInstitute').val('');
        $('#filterBranch').val('');
        $('#filterQuota').val('');
        $('#filterGender').val('');
        $('#filterRound').val('');
        $('#filterPrep').val('');

        table.columns().search('');
        table.search('');
        table.draw();
    });

    const table = $('#resultTable').DataTable({
        paging: false,
        searching: true,
        info: false,
        lengthChange: false,
        ordering: true,
        order: [[6, 'asc']],
        dom: 't'
    });

    function addOptions(columnIndex, selectId) {
        const column = table.column(columnIndex);

        if (!column || !column.data()) {
            return;
        }

        const select = $(selectId);
        select.find('option:not(:first)').remove();

        column.data().unique().sort().each(function (value) {
            value = $('<div>').html(value).text().trim();

            if (value !== '') {
                select.append(`<option value="${value}">${value}</option>`);
            }
        });
    }

    addOptions(0, '#filterType');
    addOptions(1, '#filterInstitute');
    addOptions(3, '#filterQuota');
    addOptions(5, '#filterGender');
    addOptions(6, '#filterRound');
    addOptions(7, '#filterPrep');

    let filtersVisible = false;

    $('#toggleFilters').on('click', function () {

        filtersVisible = !filtersVisible;

        $('#filterPanel').slideToggle(150);

        $(this).html(
            filtersVisible
                ? '✖ Hide Filters'
                : '🔎 Show Filters'
        );

    });

    $('#filterType').on('change', function () {
        table.column(0).search(this.value).draw();
    });

    $('#filterInstitute').on('change', function () {
        table.column(1).search(this.value).draw();
    });

    $('#filterBranch').on('keyup change', function () {
        table.column(2).search(this.value).draw();
    });

    $('#filterQuota').on('change', function () {
        table.column(3).search(this.value).draw();
    });

    $('#filterGender').on('change', function () {
        table.column(5).search(this.value).draw();
    });

    $('#filterRound').on('change', function () {
        table.column(6).search(this.value).draw();
    });

    $('#filterPrep').on('change', function () {
        table.column(7).search(this.value).draw();
    });

    $('#resetFilters').on('click', function () {
        $('#filterType').val('');
        $('#filterInstitute').val('');
        $('#filterBranch').val('');
        $('#filterQuota').val('');
        $('#filterGender').val('');
        $('#filterRound').val('');
        $('#filterPrep').val('');

        table.columns().search('');
        table.search('');
        table.draw();
    });


});
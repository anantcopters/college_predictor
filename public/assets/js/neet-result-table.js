$(document).ready(function () {

    const resultTable = $('#neetResultTable');

    if (!resultTable.length) {
        return;
    }

    const table = resultTable.DataTable({
        paging: false,
        searching: true,
        info: false,
        lengthChange: false,
        ordering: true,
        order: [[0, 'asc']],
        autoWidth: false,
        dom: 't'
    });

    function escapeRegex(value) {
        return $.fn.dataTable.util.escapeRegex(value);
    }

    function cleanCellValue(value) {
        return $('<div>')
            .html(value)
            .text()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function addOptions(columnIndex, selector) {

        const select = $(selector);

        if (!select.length) {
            return;
        }

        select.find('option:not(:first)').remove();

        const values = [];

        table.column(columnIndex)
            .data()
            .each(function (value) {

                const cleanValue = cleanCellValue(value);

                if (
                    cleanValue !== '' &&
                    cleanValue !== '-' &&
                    !values.includes(cleanValue)
                ) {
                    values.push(cleanValue);
                }
            });

        values.sort(function (a, b) {
            return a.localeCompare(b, undefined, {
                numeric: true,
                sensitivity: 'base'
            });
        });

        values.forEach(function (value) {

            select.append(
                $('<option>', {
                    value: value,
                    text: value
                })
            );

        });
    }

    /*
     * Table column indexes:
     *
     * 0 Rank
     * 1 Status
     * 2 Institute
     * 3 Course
     * 4 Quota
     * 5 Category
     * 6 Round
     * 7 Option
     * 8 Remarks
     */

    addOptions(1, '#neetFilterStatus');
    addOptions(2, '#neetFilterInstitute');
    addOptions(3, '#neetFilterCourse');
    addOptions(4, '#neetFilterQuota');
    addOptions(5, '#neetFilterCategory');
    addOptions(6, '#neetFilterRound');

    function applyExactFilter(columnIndex, value) {

        const searchValue = value
            ? '^' + escapeRegex(value) + '$'
            : '';

        table.column(columnIndex)
            .search(searchValue, true, false)
            .draw();
    }

    $('#neetFilterStatus').on('change', function () {
        applyExactFilter(1, this.value);
    });

    $('#neetFilterInstitute').on('change', function () {

        /*
         * Institute cell may contain previous and upgraded institute.
         * Therefore use normal contains search rather than exact search.
         */
        table.column(2)
            .search(this.value)
            .draw();
    });

    $('#neetFilterCourse').on('change', function () {
        applyExactFilter(3, this.value);
    });

    $('#neetFilterQuota').on('change', function () {
        applyExactFilter(4, this.value);
    });

    $('#neetFilterCategory').on('change', function () {
        applyExactFilter(5, this.value);
    });

    $('#neetFilterRound').on('change', function () {
        applyExactFilter(6, this.value);
    });

    let filtersVisible = false;

    $('#neetToggleFilters').on('click', function () {

        filtersVisible = !filtersVisible;

        $('#neetFilterPanel').stop(true, true).slideToggle(150);

        $(this).html(
            filtersVisible
                ? '✖ Hide Filters'
                : '🔎 Show Filters'
        );
    });

    $('#neetResetFilters').on('click', function () {

        $('#neetFilterStatus').val('');
        $('#neetFilterInstitute').val('');
        $('#neetFilterCourse').val('');
        $('#neetFilterQuota').val('');
        $('#neetFilterCategory').val('');
        $('#neetFilterRound').val('');

        table.columns().search('');
        table.search('');
        table.draw();
    });

    table.on('draw', function () {

        const visibleRows = table.rows({
            search: 'applied'
        }).count();

        $('#neetVisibleCount').text(visibleRows);
    });

});
$(document).ready(function () {
    $('.filter-btn').on('click', function () {
        const filterType = $(this).data('filter-type');
        const filterValue = $(this).data('filter-value');
        const masterUserId = $('#master_userid').val();

        $.ajax({
            url: 'fetch_filtered_tickets.php',
            type: 'POST',
            data: {
                filter_type: filterType,
                filter_value: filterValue,
                master_user_id: masterUserId
            },
            success: function (response) {
                $('#dynamic-table-body').html(response);
            },
            error: function (xhr, status, error) {
                alert('An error occurred while fetching tickets.');
            }
        });
    });
});

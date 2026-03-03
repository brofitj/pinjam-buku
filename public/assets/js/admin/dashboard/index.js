$(function () {
    var $totalBooks = $('#dashboard_total_books');
    var $totalMembers = $('#dashboard_total_members');
    var $totalManagers = $('#dashboard_total_managers');
    var $activeTransactions = $('#dashboard_active_transactions');

    function showFallback() {
        $totalBooks.text('0');
        $totalMembers.text('0');
        $totalManagers.text('0');
        $activeTransactions.text('0');
    }

    $.ajax({
        url: '/api/dashboard/stats',
        method: 'GET',
        dataType: 'json',
        success: function (res) {
            if (!res || !res.success || !res.data) {
                showFallback();
                return;
            }

            var data = res.data || {};
            $totalBooks.text(parseInt(data.total_books || 0, 10) || 0);
            $totalMembers.text(parseInt(data.total_members || 0, 10) || 0);
            $totalManagers.text(parseInt(data.total_managers || 0, 10) || 0);
            $activeTransactions.text(parseInt(data.active_transactions || 0, 10) || 0);
        },
        error: function () {
            showFallback();
        }
    });
});


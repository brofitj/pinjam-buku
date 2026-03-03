$(function () {
    var $tbody = $('#member_transaction_table_body');
    var $countSpan = $('#member_transaction_count');
    var $search = $('#member_transaction_search');
    var $pageInfo = $('#member_transaction_page_info');
    var $prevBtn = $('#member_transaction_prev');
    var $nextBtn = $('#member_transaction_next');

    var currentPage = 1;
    var lastPage = 1;
    var perPage = 10;
    var sortField = 'id';
    var sortDir = 'desc';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) return '-';
        return escapeHtml(value);
    }

    function formatCurrency(value) {
        var amount = parseFloat(value || 0);
        if (!isFinite(amount)) amount = 0;
        return 'Rp ' + amount.toLocaleString('id-ID');
    }

    function getStatusBadge(statusRaw) {
        var status = String(statusRaw || '').toLowerCase();
        if (status === 'waiting' || status === 'menunggu') {
            return '<span class="kt-badge kt-badge-warning kt-badge-outline">Menunggu</span>';
        }
        if (status === 'returned' || status === 'selesai') {
            return '<span class="kt-badge kt-badge-success kt-badge-outline">Selesai</span>';
        }
        if (status === 'overdue' || status === 'terlambat') {
            return '<span class="kt-badge kt-badge-warning kt-badge-outline">Terlambat</span>';
        }
        if (status === 'borrowed' || status === 'dipinjam' || status === '') {
            return '<span class="kt-badge kt-badge-primary kt-badge-outline">Dipinjam</span>';
        }
        return '<span class="kt-badge kt-badge-secondary kt-badge-outline">' + escapeHtml(statusRaw || '-') + '</span>';
    }

    function renderTable(items) {
        if (!$tbody.length) return;

        $tbody.empty();

        if (!items.length) {
            $tbody.append(
                '<tr>' +
                    '<td colspan="7" class="text-center py-6 text-sm text-secondary-foreground">' +
                        'Belum ada data transaksi.' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        $.each(items, function (_, item) {
            var rowHtml =
                '<tr>' +
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.transaction_code || '-') + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.borrow_date) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.due_date) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.return_date) + '</td>' +
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.total_books || 0) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + escapeHtml(formatCurrency(item.fine_amount)) + '</td>' +
                    '<td>' + getStatusBadge(item.status) + '</td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });
    }

    function loadTransactions(page) {
        currentPage = page || 1;

        $.ajax({
            url: '/api/member/transactions',
            method: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                per_page: perPage,
                q: $search.val() || '',
                sort_by: sortField,
                sort_dir: sortDir
            },
            success: function (res) {
                var data = res.data || [];
                var meta = res.meta || {};

                lastPage = meta.last_page || 1;

                if ($countSpan.length) {
                    $countSpan.text(meta.total || data.length || 0);
                }

                if ($pageInfo.length) {
                    $pageInfo.text('Halaman ' + currentPage + ' dari ' + lastPage);
                }

                renderTable(data);

                $prevBtn.prop('disabled', currentPage <= 1);
                $nextBtn.prop('disabled', currentPage >= lastPage);
            },
            error: function () {
                if ($tbody.length) {
                    $tbody.html(
                        '<tr>' +
                            '<td colspan="7" class="text-center py-6 text-sm text-destructive">' +
                                'Terjadi kesalahan saat memuat data transaksi.' +
                            '</td>' +
                        '</tr>'
                    );
                }
            }
        });
    }

    $search.on('input', function () {
        loadTransactions(1);
    });

    $prevBtn.on('click', function () {
        if (currentPage > 1) loadTransactions(currentPage - 1);
    });

    $nextBtn.on('click', function () {
        if (currentPage < lastPage) loadTransactions(currentPage + 1);
    });

    var $sortControls = $('[data-sort-field]');
    $sortControls.on('click', function () {
        var field = $(this).data('sort-field');

        if (sortField === field) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortField = field;
            sortDir = 'asc';
        }

        $sortControls.removeClass('is-sorted-asc is-sorted-desc');
        $(this).addClass(sortDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');

        loadTransactions(1);
    });

    loadTransactions(1);
});

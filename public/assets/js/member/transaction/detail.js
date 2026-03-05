$(function () {
    var $code = $('#member_transaction_detail_code');
    var $status = $('#member_transaction_detail_status');
    var $borrowDate = $('#member_transaction_detail_borrow_date');
    var $dueDate = $('#member_transaction_detail_due_date');
    var $returnDate = $('#member_transaction_detail_return_date');
    var $fineAmount = $('#member_transaction_detail_fine_amount');
    var $totalBooks = $('#member_transaction_detail_total_books');
    var $itemsBody = $('#member_transaction_detail_items_body');

    function showToast(message, variant) {
        if (window.KTToast && typeof window.KTToast.show === 'function') {
            window.KTToast.show({ message: message, variant: variant });
            return;
        }
        alert(message);
    }

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
            return '<span class="kt-badge kt-badge-warning kt-badge-outline">Persetujuan Peminjaman</span>';
        }
        if (status === 'return_requested' || status === 'menunggu_pengembalian') {
            return '<span class="kt-badge kt-badge-info kt-badge-outline">Persetujuan Pengembalian</span>';
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

    function getTransactionId() {
        var url = new URL(window.location.href);
        return parseInt(url.searchParams.get('id') || '0', 10) || 0;
    }

    function renderItems(items) {
        if (!$itemsBody.length) return;
        $itemsBody.empty();

        if (!items || !items.length) {
            $itemsBody.append(
                '<tr>' +
                    '<td colspan="5" class="text-center py-6 text-sm text-secondary-foreground">' +
                        'Detail buku tidak ditemukan.' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        $.each(items, function (_, item) {
            var coverSrc = '/themes/metronic/dist/assets/media/avatars/blank.png';
            if (item.cover_image) {
                var cover = String(item.cover_image);
                if (cover.indexOf('http://') === 0 || cover.indexOf('https://') === 0 || cover.indexOf('/') === 0) {
                    coverSrc = cover;
                } else {
                    coverSrc = '/book/cover?file=' + encodeURIComponent(cover);
                }
            }

            $itemsBody.append(
                '<tr>' +
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.book_code || '-') + '</td>' +
                    '<td>' +
                        '<img class="rounded-md object-cover border border-border" style="width:48px;height:64px;" src="' + coverSrc + '" alt="Cover ' + escapeHtml(item.title || '-') + '"/>' +
                    '</td>' +
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.title || '-') + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + escapeHtml(item.author || '-') + '</td>' +
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.quantity || 0) + '</td>' +
                '</tr>'
            );
        });
    }

    function loadDetail() {
        var id = getTransactionId();
        if (!id) {
            showToast('ID transaksi tidak valid.', 'destructive');
            return;
        }

        $.ajax({
            url: '/api/member/transactions/show',
            method: 'GET',
            dataType: 'json',
            data: { id: id },
            success: function (res) {
                if (!res || !res.success || !res.data || !res.data.transaction) {
                    showToast((res && res.message) ? res.message : 'Gagal memuat detail transaksi.', 'destructive');
                    return;
                }

                var transaction = res.data.transaction || {};
                var items = res.data.items || [];

                $code.text(transaction.transaction_code || '-');
                $status.html(getStatusBadge(transaction.status));
                $borrowDate.text(formatDate(transaction.borrow_date));
                $dueDate.text(formatDate(transaction.due_date));
                $returnDate.text(formatDate(transaction.return_date));
                $fineAmount.text(formatCurrency(transaction.fine_amount));
                $totalBooks.text(transaction.total_books || 0);

                renderItems(items);
            },
            error: function (xhr) {
                var message = 'Gagal memuat detail transaksi.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'destructive');
            }
        });
    }

    loadDetail();
});

$(function () {
    var $tbody = $('#member_transaction_table_body');
    var $countSpan = $('#member_transaction_count');
    var $search = $('#member_transaction_search');
    var $pageInfo = $('#member_transaction_page_info');
    var $prevBtn = $('#member_transaction_prev');
    var $nextBtn = $('#member_transaction_next');
    var $requestReturnTransactionCode = $('#request_return_transaction_code');
    var $confirmRequestReturnBtn = $('#confirm_request_return');
    var isSubmittingReturn = false;
    var selectedReturnTransactionId = 0;

    function showToast(message, variant) {
        if (window.KTToast && typeof window.KTToast.show === 'function') {
            window.KTToast.show({
                message: message,
                variant: variant
            });
            return;
        }

        alert(message);
    }

    function closeRequestReturnModal() {
        $('[data-kt-modal-dismiss="#request_return_modal"]').first().trigger('click');
    }

    function openRequestReturnModal(transactionId, transactionCode) {
        selectedReturnTransactionId = parseInt(transactionId, 10) || 0;
        $requestReturnTransactionCode.text(transactionCode || '-');

        var $modalTrigger = $('<button>', {
            type: 'button',
            'data-kt-modal-toggle': '#request_return_modal'
        }).hide();

        $('body').append($modalTrigger);
        $modalTrigger.trigger('click');
        $modalTrigger.remove();
    }

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

    function renderTable(items) {
        if (!$tbody.length) return;

        $tbody.empty();

        if (!items.length) {
            $tbody.append(
                '<tr>' +
                    '<td colspan="8" class="text-center py-6 text-sm text-secondary-foreground">' +
                        'Belum ada data transaksi.' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        $.each(items, function (_, item) {
            var status = String(item.status || '').toLowerCase().trim();
            var hasBorrowDate = !!item.borrow_date;
            var hasReturnDate = !!item.return_date;
            var isBorrowingLike =
                status === 'borrowed' ||
                status === 'dipinjam' ||
                status === 'overdue' ||
                status === 'terlambat';
            var isLegacyBorrowing = status === '' && hasBorrowDate && !hasReturnDate;
            var canRequestReturn = isBorrowingLike || isLegacyBorrowing;
            var actionHtml = '';

            if (canRequestReturn) {
                actionHtml =
                    '<div class="kt-menu" data-kt-menu="true">' +
                        '<div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">' +
                            '<button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button">' +
                                '<i class="ki-filled ki-dots-vertical text-lg"></i>' +
                            '</button>' +
                            '<div class="kt-menu-dropdown kt-menu-default w-full max-w-[220px]" data-kt-menu-dismiss="true">' +
                                '<div class="kt-menu-item">' +
                                    '<a class="kt-menu-link btn-request-return" href="#" data-transaction-id="' + item.id + '" data-transaction-code="' + escapeHtml(item.transaction_code || '-') + '">' +
                                        '<span class="kt-menu-title">Ajukan Pengembalian</span>' +
                                    '</a>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            } else {
                actionHtml =
                    '<button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button" disabled aria-disabled="true" title="Aksi tidak tersedia">' +
                        '<i class="ki-filled ki-dots-vertical text-lg"></i>' +
                    '</button>';
            }

            var rowHtml =
                '<tr>' +
                    '<td>' + actionHtml + '</td>' +
                    '<td>' + getStatusBadge(item.status) + '</td>' +
                    '<td class="text-sm text-foreground font-medium">' +
                        '<a class="kt-link kt-link-underlined text-primary" href="/member/dashboard/detail?id=' + item.id + '">' +
                            escapeHtml(item.transaction_code || '-') +
                        '</a>' +
                    '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.borrow_date) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.due_date) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.return_date) + '</td>' +
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.total_books || 0) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + escapeHtml(formatCurrency(item.fine_amount)) + '</td>' +
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
                if (window.KTMenu && typeof window.KTMenu.createInstances === 'function') {
                    window.KTMenu.createInstances();
                }

                $prevBtn.prop('disabled', currentPage <= 1);
                $nextBtn.prop('disabled', currentPage >= lastPage);
            },
            error: function () {
                if ($tbody.length) {
                    $tbody.html(
                        '<tr>' +
                            '<td colspan="8" class="text-center py-6 text-sm text-destructive">' +
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

    $(document).on('click', '.btn-request-return', function (e) {
        e.preventDefault();

        var transactionId = parseInt($(this).data('transaction-id'), 10) || 0;
        var transactionCode = $(this).data('transaction-code') || '-';
        if (!transactionId) {
            showToast('ID transaksi tidak valid.', 'destructive');
            return;
        }

        openRequestReturnModal(transactionId, transactionCode);
    });

    $confirmRequestReturnBtn.on('click', function () {
        if (isSubmittingReturn) return;

        var transactionId = selectedReturnTransactionId;
        if (!transactionId) {
            showToast('ID transaksi tidak valid.', 'destructive');
            return;
        }

        isSubmittingReturn = true;
        $confirmRequestReturnBtn.prop('disabled', true).text('Memproses...');

        $.ajax({
            url: '/api/member/transactions/request-return',
            method: 'POST',
            dataType: 'json',
            data: { id: transactionId },
            success: function (res) {
                if (res && res.success) {
                    selectedReturnTransactionId = 0;
                    closeRequestReturnModal();
                    showToast((res && res.message) ? res.message : 'Pengajuan pengembalian berhasil dikirim.', 'success');
                    loadTransactions(currentPage);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal mengajukan pengembalian.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal mengajukan pengembalian.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'destructive');
            },
            complete: function () {
                isSubmittingReturn = false;
                $confirmRequestReturnBtn.prop('disabled', false).text('Ya, Ajukan');
            }
        });
    });

    loadTransactions(1);
});

$(function () {
    var $tbody = $('#transaction_table_body');
    var $countSpan = $('#transaction_count');
    var $search = $('#transaction_search');
    var $pageInfo = $('#transaction_page_info');
    var $prevBtn = $('#transaction_prev');
    var $nextBtn = $('#transaction_next');
    var $approveTransactionCode = $('#approve_transaction_code');
    var $confirmApproveBtn = $('#confirm_approve_transaction');

    var currentPage = 1;
    var lastPage = 1;
    var perPage = 10;
    var sortField = 'id';
    var sortDir = 'desc';
    var selectedTransactionId = 0;
    var isApproving = false;

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

    function closeApproveModal() {
        $('[data-kt-modal-dismiss="#approve_transaction_modal"]').first().trigger('click');
    }

    function openApproveModal(transactionId, transactionCode) {
        selectedTransactionId = parseInt(transactionId, 10) || 0;
        $approveTransactionCode.text(transactionCode || '-');

        var $modalTrigger = $('<button>', {
            type: 'button',
            'data-kt-modal-toggle': '#approve_transaction_modal'
        }).hide();

        $('body').append($modalTrigger);
        $modalTrigger.trigger('click');
        $modalTrigger.remove();
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
                    '<td colspan="9" class="text-center py-6 text-sm text-secondary-foreground">' +
                        'Belum ada data transaksi.' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        $.each(items, function (_, item) {
            var status = String(item.status || '').toLowerCase();
            var isWaiting = status === 'waiting' || status === 'menunggu';
            var actionHtml = '';

            if (isWaiting) {
                actionHtml =
                    '<div class="kt-menu" data-kt-menu="true">' +
                        '<div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">' +
                            '<button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" type="button">' +
                                '<i class="ki-filled ki-dots-vertical text-lg"></i>' +
                            '</button>' +
                            '<div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">' +
                                '<div class="kt-menu-item">' +
                                    '<a class="kt-menu-link btn-open-approve-modal" href="#" data-transaction-id="' + item.id + '" data-transaction-code="' + escapeHtml(item.transaction_code || '-') + '">' +
                                        '<span class="kt-menu-icon">' +
                                            '<i class="ki-filled ki-delivery-3"></i>' +
                                        '</span>' +
                                        '<span class="kt-menu-title">Dipinjam</span>' +
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
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.transaction_code || '-') + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + escapeHtml(item.member_name || '-') + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.borrow_date) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.due_date) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + formatDate(item.return_date) + '</td>' +
                    '<td class="text-sm text-foreground font-medium">' + escapeHtml(item.total_books || 0) + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + escapeHtml(formatCurrency(item.fine_amount)) + '</td>' +
                    '<td>' + getStatusBadge(item.status) + '</td>' +
                    '<td>' + actionHtml + '</td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });
    }

    function loadTransactions(page) {
        currentPage = page || 1;

        $.ajax({
            url: '/api/transactions',
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
                            '<td colspan="9" class="text-center py-6 text-sm text-destructive">' +
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

    $(document).on('click', '.btn-open-approve-modal', function (e) {
        e.preventDefault();

        var transactionId = $(this).data('transaction-id');
        var transactionCode = $(this).data('transaction-code');

        openApproveModal(transactionId, transactionCode);
    });

    $confirmApproveBtn.on('click', function () {
        if (isApproving) return;

        var transactionId = selectedTransactionId;
        if (!transactionId) {
            showToast('ID transaksi tidak valid.', 'destructive');
            return;
        }

        isApproving = true;
        $confirmApproveBtn.prop('disabled', true).text('Memproses...');

        $.ajax({
            url: '/api/transactions/update-status',
            method: 'POST',
            dataType: 'json',
            data: { id: transactionId },
            success: function (res) {
                if (res && res.success) {
                    selectedTransactionId = 0;
                    closeApproveModal();
                    loadTransactions(currentPage);
                    showToast((res && res.message) ? res.message : 'Status transaksi berhasil diubah.', 'success');
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal mengubah status transaksi.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal mengubah status transaksi.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'destructive');
            },
            complete: function () {
                isApproving = false;
                $confirmApproveBtn.prop('disabled', false).text('Ya, Ubah Status');
            }
        });
    });

    loadTransactions(1);
});

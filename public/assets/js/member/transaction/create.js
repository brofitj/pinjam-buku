$(function () {
    var $bookTbody = $('#member_book_table_body');
    var $bookSearch = $('#member_book_search');
    var $bookPageInfo = $('#member_book_page_info');
    var $bookPrev = $('#member_book_prev');
    var $bookNext = $('#member_book_next');
    var $cartTbody = $('#member_cart_table_body');
    var $cartCount = $('.member-cart-count');
    var $resetBtn = $('#member_cart_reset');
    var $submitBtn = $('#member_create_transaction_btn');
    var $tabBooksBtn = $('#member_tab_books');
    var $tabCartBtn = $('#member_tab_cart');
    var $tabBooksPanel = $('#member_tab_panel_books');
    var $tabCartPanel = $('#member_tab_panel_cart');

    var currentPage = 1;
    var lastPage = 1;
    var perPage = 10;
    var sortField = 'id';
    var sortDir = 'desc';
    var isSubmitting = false;
    var cart = {};

    function activateTab(tab) {
        var isBooks = tab !== 'cart';

        $tabBooksPanel.toggleClass('hidden', !isBooks);
        $tabCartPanel.toggleClass('hidden', isBooks);

        $tabBooksBtn
            .toggleClass('kt-btn-primary', isBooks)
            .toggleClass('kt-btn-outline', !isBooks);

        $tabCartBtn
            .toggleClass('kt-btn-primary', !isBooks)
            .toggleClass('kt-btn-outline', isBooks);
    }

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

    function renderBooks(items) {
        $bookTbody.empty();

        if (!items.length) {
            $bookTbody.append(
                '<tr><td colspan="6" class="text-center py-6 text-sm text-secondary-foreground">Belum ada buku tersedia.</td></tr>'
            );
            return;
        }

        $.each(items, function (_, item) {
            var stock = parseInt(item.stock, 10) || 0;
            var inCartQty = cart[item.id] ? cart[item.id].quantity : 0;
            var coverSrc = item.cover_image
                ? '/book/cover?file=' + encodeURIComponent(item.cover_image)
                : '/themes/metronic/dist/assets/media/avatars/blank.png';

            $bookTbody.append(
                '<tr>' +
                    '<td class="text-sm font-medium text-foreground">' + escapeHtml(item.book_code || '-') + '</td>' +
                    '<td>' +
                        '<img class="rounded-md object-cover border border-border" style="width:48px;height:64px;" src="' + coverSrc + '" alt="' + escapeHtml(item.title || 'Cover') + '">' +
                    '</td>' +
                    '<td class="text-sm text-foreground">' + escapeHtml(item.title || '-') + '</td>' +
                    '<td class="text-sm text-foreground">' + escapeHtml(item.author || '-') + '</td>' +
                    '<td class="text-sm text-foreground">' +
                        '<span class="kt-badge kt-badge-success kt-badge-outline">Tersedia: ' + stock + '</span>' +
                    '</td>' +
                    '<td>' +
                        '<button type="button" class="kt-btn kt-btn-sm kt-btn-outline btn-add-book" ' +
                            'data-book-id="' + item.id + '" ' +
                            'data-book-code="' + escapeHtml(item.book_code || '') + '" ' +
                            'data-book-title="' + escapeHtml(item.title || '') + '" ' +
                            'data-book-cover="' + escapeHtml(item.cover_image || '') + '" ' +
                            'data-book-stock="' + stock + '">' +
                            (inCartQty > 0 ? ('Tambah (' + inCartQty + ')') : 'Tambah') +
                        '</button>' +
                    '</td>' +
                '</tr>'
            );
        });
    }

    function renderCart() {
        var entries = Object.keys(cart).map(function (bookId) {
            return cart[bookId];
        });
        var totalQuantity = 0;
        $.each(entries, function (_, item) {
            totalQuantity += parseInt(item.quantity, 10) || 0;
        });

        $cartTbody.empty();
        $cartCount.text(totalQuantity);

        if (!entries.length) {
            $cartTbody.append(
                '<tr><td colspan="5" class="text-center py-6 text-sm text-secondary-foreground">Belum ada buku dipilih.</td></tr>'
            );
            return;
        }

        $.each(entries, function (_, item) {
            var cartCoverSrc = item.cover_image
                ? '/book/cover?file=' + encodeURIComponent(item.cover_image)
                : '/themes/metronic/dist/assets/media/avatars/blank.png';

            $cartTbody.append(
                '<tr>' +
                    '<td class="text-sm font-medium text-foreground">' + escapeHtml(item.book_code || '-') + '</td>' +
                    '<td>' +
                        '<img class="rounded-md object-cover border border-border" style="width:48px;height:64px;" src="' + cartCoverSrc + '" alt="' + escapeHtml(item.title || 'Cover') + '">' +
                    '</td>' +
                    '<td class="text-sm text-foreground">' + escapeHtml(item.title || '-') + '</td>' +
                    '<td class="text-sm text-foreground">' + item.stock + '</td>' +
                    '<td>' +
                        '<input type="number" min="1" max="' + item.stock + '" value="' + item.quantity + '" class="kt-input h-8 w-[90px] cart-qty" data-book-id="' + item.id + '">' +
                    '</td>' +
                    '<td>' +
                        '<button type="button" class="kt-btn kt-btn-sm kt-btn-destructive btn-remove-cart" data-book-id="' + item.id + '">Hapus</button>' +
                    '</td>' +
                '</tr>'
            );
        });
    }

    function loadBooks(page) {
        currentPage = page || 1;

        $.ajax({
            url: '/api/member/books',
            method: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                per_page: perPage,
                q: $bookSearch.val() || '',
                sort_by: sortField,
                sort_dir: sortDir
            },
            success: function (res) {
                var data = res.data || [];
                var meta = res.meta || {};

                lastPage = meta.last_page || 1;
                $bookPageInfo.text('Halaman ' + currentPage + ' dari ' + lastPage);
                $bookPrev.prop('disabled', currentPage <= 1);
                $bookNext.prop('disabled', currentPage >= lastPage);

                renderBooks(data);
            },
            error: function () {
                $bookTbody.html(
                    '<tr><td colspan="5" class="text-center py-6 text-sm text-destructive">Gagal memuat buku.</td></tr>'
                );
            }
        });
    }

    function normalizeQuantity(value, maxStock) {
        var qty = parseInt(value, 10);
        if (!isFinite(qty) || qty < 1) qty = 1;
        if (qty > maxStock) qty = maxStock;
        return qty;
    }

    $(document).on('click', '.btn-add-book', function () {
        var bookId = parseInt($(this).data('book-id'), 10) || 0;
        var stock = parseInt($(this).data('book-stock'), 10) || 0;
        var bookCode = $(this).data('book-code') || '';
        var title = $(this).data('book-title') || '';
        var coverImage = $(this).data('book-cover') || '';

        if (!bookId || stock <= 0) return;

        if (!cart[bookId]) {
            cart[bookId] = {
                id: bookId,
                book_code: bookCode,
                title: title,
                cover_image: coverImage,
                quantity: 1,
                stock: stock
            };
        } else {
            cart[bookId].quantity = normalizeQuantity(cart[bookId].quantity + 1, stock);
        }

        renderCart();
        loadBooks(currentPage);
    });

    $(document).on('change', '.cart-qty', function () {
        var bookId = parseInt($(this).data('book-id'), 10) || 0;
        if (!bookId || !cart[bookId]) return;

        cart[bookId].quantity = normalizeQuantity($(this).val(), cart[bookId].stock);
        renderCart();
        loadBooks(currentPage);
    });

    $(document).on('click', '.btn-remove-cart', function () {
        var bookId = parseInt($(this).data('book-id'), 10) || 0;
        if (!bookId) return;

        delete cart[bookId];
        renderCart();
        loadBooks(currentPage);
    });

    $resetBtn.on('click', function () {
        cart = {};
        renderCart();
        loadBooks(1);
    });

    $bookSearch.on('input', function () {
        loadBooks(1);
    });

    $tabBooksBtn.on('click', function () {
        activateTab('books');
    });

    $tabCartBtn.on('click', function () {
        activateTab('cart');
    });

    $bookPrev.on('click', function () {
        if (currentPage > 1) loadBooks(currentPage - 1);
    });

    $bookNext.on('click', function () {
        if (currentPage < lastPage) loadBooks(currentPage + 1);
    });

    $('[data-sort-field]').on('click', function () {
        var field = $(this).data('sort-field');
        if (!field) return;

        if (sortField === field) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortField = field;
            sortDir = 'asc';
        }

        loadBooks(1);
    });

    $submitBtn.on('click', function () {
        if (isSubmitting) return;

        var items = Object.keys(cart).map(function (bookId) {
            return {
                book_id: cart[bookId].id,
                quantity: cart[bookId].quantity
            };
        });

        if (!items.length) {
            showToast('Pilih minimal 1 buku untuk dipinjam.', 'destructive');
            return;
        }

        isSubmitting = true;
        $submitBtn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: '/api/member/transactions/create',
            method: 'POST',
            dataType: 'json',
            data: {
                items: JSON.stringify(items)
            },
            success: function (res) {
                if (res && res.success) {
                    showToast(res.message || 'Transaksi berhasil diajukan.', 'success');
                    window.setTimeout(function () {
                        window.location.href = '/member/dashboard';
                    }, 500);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal membuat transaksi.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal membuat transaksi.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'destructive');
            },
            complete: function () {
                isSubmitting = false;
                $submitBtn.prop('disabled', false).text('Ajukan Transaksi');
            }
        });
    });

    renderCart();
    activateTab('books');
    loadBooks(1);
});

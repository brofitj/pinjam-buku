$(function () {
    var $tbody = $('#book_table_body');
    var $countSpan = $('#book_count');
    var $search = $('#book_search');
    var $pageInfo = $('#book_page_info');
    var $prevBtn = $('#book_prev');
    var $nextBtn = $('#book_next');
    var $deleteBookTitle = $('#delete_book_title');
    var $confirmDeleteBtn = $('#confirm_delete_book');

    var currentPage = 1;
    var lastPage = 1;
    var perPage = 10;
    var sortField = 'id';
    var sortDir = 'desc';
    var selectedBookId = 0;
    var isDeleting = false;

    function closeDeleteModal() {
        $('[data-kt-modal-dismiss="#delete_book_modal"]').first().trigger('click');
    }

    function openDeleteModal(bookId, bookTitle) {
        selectedBookId = parseInt(bookId, 10) || 0;
        $deleteBookTitle.text(bookTitle || '-');

        var $modalTrigger = $('<button>', {
            type: 'button',
            'data-kt-modal-toggle': '#delete_book_modal'
        }).hide();

        $('body').append($modalTrigger);
        $modalTrigger.trigger('click');
        $modalTrigger.remove();
    }

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

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderTable(books) {
        if (!$tbody.length) return;

        $tbody.empty();

        if (!books.length) {
            $tbody.append(
                '<tr>' +
                    '<td colspan="10" class="text-center py-6 text-sm text-secondary-foreground">' +
                        'Belum ada data buku.' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        $.each(books, function (_, book) {
            var code = escapeHtml(book.book_code || '-');
            var title = escapeHtml(book.title || '-');
            var description = escapeHtml(book.description || '-');
            var author = escapeHtml(book.author || '-');
            var publisher = escapeHtml(book.publisher || '-');
            var year = escapeHtml(book.publication_year || '-');
            var isbn = escapeHtml(book.isbn || '-');
            var stockNumber = parseInt(book.stock, 10);
            if (isNaN(stockNumber) || stockNumber < 0) stockNumber = 0;
            var stockText = escapeHtml(String(stockNumber));
            var stockBadge = '';

            if (stockNumber <= 0) {
                stockBadge = '<span class="kt-badge kt-badge-destructive kt-badge-outline">Habis: ' + stockText + '</span>';
            } else if (stockNumber <= 5) {
                stockBadge = '<span class="kt-badge kt-badge-warning kt-badge-outline">Tersedia: ' + stockText + '</span>';
            } else {
                stockBadge = '<span class="kt-badge kt-badge-success kt-badge-outline">Tersedia: ' + stockText + '</span>';
            }
            var coverSrc = '/themes/metronic/dist/assets/media/avatars/blank.png';

            if (book.cover_image) {
                var cover = String(book.cover_image);
                if (cover.indexOf('http://') === 0 || cover.indexOf('https://') === 0 || cover.indexOf('/') === 0) {
                    coverSrc = cover;
                } else {
                    coverSrc = '/book/cover?file=' + encodeURIComponent(cover);
                }
            }

            var rowHtml =
                '<tr>' +
                    '<td class="text-sm text-foreground font-medium">' + code + '</td>' +
                    '<td>' +
                        '<img class="rounded-md object-cover border border-border" style="width:48px;height:64px;" src="' + coverSrc + '" alt="Cover ' + title + '"/>' +
                    '</td>' +
                    '<td>' +
                        '<div class="flex flex-col gap-0.5">' +
                            '<span class="leading-none font-medium text-sm text-mono">' + title + '</span>' +
                            '<span class="text-sm text-secondary-foreground font-normal truncate max-w-[220px]" title="' + description + '">' +
                                description +
                            '</span>' +
                        '</div>' +
                    '</td>' +
                    '<td>' + stockBadge + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + author + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + publisher + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + year + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + isbn + '</td>' +
                    '<td>' +
                        '<a class="kt-btn kt-btn-icon kt-btn-ghost" href="/book/edit?id=' + book.id + '">' +
                            '<i class="ki-filled ki-notepad-edit"></i>' +
                        '</a>' +
                    '</td>' +
                    '<td>' +
                        '<button type="button" class="kt-btn kt-btn-icon kt-btn-ghost btn-delete-book" data-book-id="' + book.id + '" data-book-title="' + title + '">' +
                            '<i class="ki-filled ki-trash"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });
    }

    function loadBooks(page) {
        currentPage = page || 1;

        $.ajax({
            url: '/api/books',
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
                            '<td colspan="10" class="text-center py-6 text-sm text-destructive">' +
                                'Terjadi kesalahan saat memuat data buku.' +
                            '</td>' +
                        '</tr>'
                    );
                }
            }
        });
    }

    $search.on('input', function () {
        loadBooks(1);
    });

    $prevBtn.on('click', function () {
        if (currentPage > 1) loadBooks(currentPage - 1);
    });

    $nextBtn.on('click', function () {
        if (currentPage < lastPage) loadBooks(currentPage + 1);
    });

    var $sortControls = $('[data-sort-field]');

    $sortControls.on('click', function () {
        var field = $(this).data('sort-field');

        if (sortField === field) {
            sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
        } else {
            sortField = field;
            sortDir = 'asc';
        }

        $sortControls.removeClass('is-sorted-asc is-sorted-desc');
        $(this).addClass(sortDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');

        loadBooks(1);
    });

    $(document).on('click', '.btn-delete-book', function () {
        var bookId = $(this).data('book-id');
        var bookTitle = $(this).data('book-title');

        openDeleteModal(bookId, bookTitle);
    });

    $confirmDeleteBtn.on('click', function () {
        if (isDeleting) return;

        var bookId = selectedBookId;
        if (!bookId) {
            showToast('ID buku tidak valid.', 'destructive');
            return;
        }

        isDeleting = true;
        $confirmDeleteBtn.prop('disabled', true).text('Menghapus...');

        $.ajax({
            url: '/api/books/delete',
            method: 'POST',
            dataType: 'json',
            data: { id: bookId },
            success: function (res) {
                if (res && res.success) {
                    selectedBookId = 0;
                    closeDeleteModal();
                    loadBooks(currentPage);
                    showToast((res && res.message) ? res.message : 'Data buku berhasil dihapus.', 'success');
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal menghapus buku.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal menghapus buku.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'destructive');
            },
            complete: function () {
                isDeleting = false;
                $confirmDeleteBtn.prop('disabled', false).text('Hapus');
            }
        });
    });

    loadBooks(1);
});

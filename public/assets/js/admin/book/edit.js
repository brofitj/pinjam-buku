$(function () {
    var $form = $('#book_edit_form');
    var $submitBtn = $('#book_edit_submit');
    var $bookId = $('#book_edit_id');
    var $coverPreview = $('#book_cover_preview');
    var isSubmitting = false;

    function showToast(message, variant) {
        if (window.KTToast && typeof window.KTToast.show === 'function') {
            window.KTToast.show({ message: message, variant: variant });
            return;
        }

        alert(message);
    }

    if (!$form.length) return;

    function getBookId() {
        var idFromInput = parseInt($bookId.val(), 10);
        if (idFromInput > 0) return idFromInput;

        var url = new URL(window.location.href);
        var idFromQuery = parseInt(url.searchParams.get('id') || '0', 10);
        return idFromQuery > 0 ? idFromQuery : 0;
    }

    function setCoverPreview(coverImage) {
        var src = coverImage
            ? '/book/cover?file=' + encodeURIComponent(coverImage)
            : '/themes/metronic/dist/assets/media/avatars/blank.png';

        $coverPreview.css('background-image', 'url("' + src + '")');
    }

    function loadBook() {
        var id = getBookId();
        if (!id) {
            showToast('ID buku tidak valid.', 'destructive');
            return;
        }

        $.ajax({
            url: '/api/books/show',
            method: 'GET',
            dataType: 'json',
            data: { id: id },
            success: function (res) {
                if (!res || !res.success || !res.data) {
                    showToast((res && res.message) ? res.message : 'Gagal memuat data buku.', 'destructive');
                    return;
                }

                var data = res.data;
                $bookId.val(data.id || id);
                $('[name="book_code"]').val(data.book_code || '');
                $('[name="title"]').val(data.title || '');
                $('[name="author"]').val(data.author || '');
                $('[name="publisher"]').val(data.publisher || '');
                $('[name="publication_year"]').val(data.publication_year || '');
                $('[name="isbn"]').val(data.isbn || '');
                $('[name="stock"]').val(data.stock || 0);
                $('[name="description"]').val(data.description || '');
                $('[name="cover_remove"]').val('');

                setCoverPreview(data.cover_image || '');
            },
            error: function (xhr) {
                var message = 'Gagal memuat data buku.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'destructive');
            }
        });
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        if (isSubmitting) return;

        var id = getBookId();
        if (!id) {
            showToast('ID buku tidak valid.', 'destructive');
            return;
        }

        isSubmitting = true;
        $submitBtn.prop('disabled', true).text('Menyimpan...');

        var formData = new FormData($form[0]);
        formData.set('id', id);

        $.ajax({
            url: '/api/books/update',
            method: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res && res.success) {
                    showToast((res && res.message) ? res.message : 'Data buku berhasil diperbarui.', 'success');

                    setTimeout(function () {
                        window.location.href = '/book';
                    }, 700);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal memperbarui buku.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal memperbarui buku.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showToast(message, 'destructive');
            },
            complete: function () {
                isSubmitting = false;
                $submitBtn.prop('disabled', false).text('Simpan');
            }
        });
    });

    loadBook();
});


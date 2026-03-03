$(function () {
    var $form = $('#book_add_form');
    var $submitBtn = $('#book_add_submit');
    var isSubmitting = false;

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

    function parseErrorMessage(xhr, fallbackMessage) {
        if (xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }

        var text = xhr.responseText || '';
        if (text) {
            try {
                var jsonDirect = JSON.parse(text);
                if (jsonDirect && jsonDirect.message) {
                    return jsonDirect.message;
                }
            } catch (e1) {}

            var start = text.lastIndexOf('{');
            if (start >= 0) {
                try {
                    var jsonTail = JSON.parse(text.slice(start));
                    if (jsonTail && jsonTail.message) {
                        return jsonTail.message;
                    }
                } catch (e2) {}
            }
        }

        return fallbackMessage;
    }

    if (!$form.length) return;

    $form.on('submit', function (e) {
        e.preventDefault();

        if (isSubmitting) return;

        isSubmitting = true;
        $submitBtn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: '/api/books/create',
            method: 'POST',
            dataType: 'json',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: function (res) {
                if (res && res.success) {
                    showToast((res && res.message) ? res.message : 'Data buku berhasil ditambahkan.', 'success');
                    $form.trigger('reset');

                    setTimeout(function () {
                        window.location.href = '/book';
                    }, 700);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal menambahkan buku.', 'destructive');
            },
            error: function (xhr) {
                var message = parseErrorMessage(xhr, 'Gagal menambahkan buku.');
                showToast(message, 'destructive');
            },
            complete: function () {
                isSubmitting = false;
                $submitBtn.prop('disabled', false).text('Simpan');
            }
        });
    });
});

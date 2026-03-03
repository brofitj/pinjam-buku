$(function () {
    var $form = $('#user_add_form');
    var $submitBtn = $('#user_add_submit');
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

    if (!$form.length) return;

    $form.on('submit', function (e) {
        e.preventDefault();

        if (isSubmitting) return;

        isSubmitting = true;
        $submitBtn.prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: '/api/users/create',
            method: 'POST',
            dataType: 'json',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: function (res) {
                if (res && res.success) {
                    showToast((res && res.message) ? res.message : 'Data pengelola berhasil ditambahkan.', 'success');
                    $form.trigger('reset');

                    setTimeout(function () {
                        window.location.href = '/user';
                    }, 700);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal menambahkan pengelola.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal menambahkan pengelola.';

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
});

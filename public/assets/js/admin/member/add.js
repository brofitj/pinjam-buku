$(function () {
    var $form = $('#member_add_form');
    var $submitBtn = $('#member_add_submit');
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
            url: '/api/members/create',
            method: 'POST',
            dataType: 'json',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: function (res) {
                if (res && res.success) {
                    showToast((res && res.message) ? res.message : 'Data anggota berhasil ditambahkan.', 'success');
                    $form.trigger('reset');

                    setTimeout(function () {
                        window.location.href = '/member';
                    }, 700);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal menambahkan anggota.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal menambahkan anggota.';

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

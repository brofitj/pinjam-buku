$(function () {
    var $form = $('#user_edit_form');
    var $submitBtn = $('#user_edit_submit');
    var $userId = $('#user_edit_id');
    var $avatarPreview = $('#user_avatar_preview');
    var isSubmitting = false;

    function showToast(message, variant) {
        if (window.KTToast && typeof window.KTToast.show === 'function') {
            window.KTToast.show({ message: message, variant: variant });
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

    function getUserId() {
        var idFromInput = parseInt($userId.val(), 10);
        if (idFromInput > 0) return idFromInput;

        var url = new URL(window.location.href);
        var idFromQuery = parseInt(url.searchParams.get('id') || '0', 10);
        return idFromQuery > 0 ? idFromQuery : 0;
    }

    function setAvatarPreview(avatar) {
        var src = avatar
            ? '/user/avatar?file=' + encodeURIComponent(avatar)
            : '/themes/metronic/dist/assets/media/avatars/blank.png';

        $avatarPreview.css('background-image', 'url("' + src + '")');
    }

    function loadUser() {
        var id = getUserId();
        if (!id) {
            showToast('ID pengelola tidak valid.', 'destructive');
            return;
        }

        $.ajax({
            url: '/api/users/show',
            method: 'GET',
            dataType: 'json',
            data: { id: id },
            success: function (res) {
                if (!res || !res.success || !res.data) {
                    showToast((res && res.message) ? res.message : 'Gagal memuat data pengelola.', 'destructive');
                    return;
                }

                var data = res.data;
                $userId.val(data.id || id);
                $('[name="name"]').val(data.name || '');
                $('[name="phone"]').val(data.phone || '');
                $('[name="address"]').val(data.address || '');
                $('[name="email"]').val(data.email || '');
                $('[name="password"]').val('');
                $('[name="gender"][value="' + (data.gender || 'male') + '"]').prop('checked', true);
                $('[name="status"][value="' + (data.status || 'active') + '"]').prop('checked', true);
                $('[name="avatar_remove"]').val('');

                var roleLabel = data.role === 'superadmin' ? 'Superadmin' : 'Librarian';
                $('#role_label').val(roleLabel);

                setAvatarPreview(data.avatar || '');
            },
            error: function (xhr) {
                var message = 'Gagal memuat data pengelola.';
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

        var id = getUserId();
        if (!id) {
            showToast('ID pengelola tidak valid.', 'destructive');
            return;
        }

        isSubmitting = true;
        $submitBtn.prop('disabled', true).text('Menyimpan...');

        var formData = new FormData($form[0]);
        formData.set('id', id);

        $.ajax({
            url: '/api/users/update',
            method: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res && res.success) {
                    showToast((res && res.message) ? res.message : 'Data pengelola berhasil diperbarui.', 'success');

                    setTimeout(function () {
                        window.location.href = '/user';
                    }, 700);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal memperbarui pengelola.', 'destructive');
            },
            error: function (xhr) {
                var message = parseErrorMessage(xhr, 'Gagal memperbarui pengelola.');
                showToast(message, 'destructive');
            },
            complete: function () {
                isSubmitting = false;
                $submitBtn.prop('disabled', false).text('Simpan');
            }
        });
    });

    loadUser();
});

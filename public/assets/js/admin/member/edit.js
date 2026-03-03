$(function () {
    var $form = $('#member_edit_form');
    var $submitBtn = $('#member_edit_submit');
    var $memberId = $('#member_edit_id');
    var $avatarPreview = $('#member_avatar_preview');
    var isSubmitting = false;

    function showToast(message, variant) {
        if (window.KTToast && typeof window.KTToast.show === 'function') {
            window.KTToast.show({ message: message, variant: variant });
            return;
        }

        alert(message);
    }

    if (!$form.length) return;

    function getMemberId() {
        var idFromInput = parseInt($memberId.val(), 10);
        if (idFromInput > 0) return idFromInput;

        var url = new URL(window.location.href);
        var idFromQuery = parseInt(url.searchParams.get('id') || '0', 10);
        return idFromQuery > 0 ? idFromQuery : 0;
    }

    function setAvatarPreview(avatar) {
        var src = avatar
            ? '/member/avatar?file=' + encodeURIComponent(avatar)
            : '/themes/metronic/dist/assets/media/avatars/blank.png';

        $avatarPreview.css('background-image', 'url("' + src + '")');
    }

    function loadMember() {
        var id = getMemberId();
        if (!id) {
            showToast('ID anggota tidak valid.', 'destructive');
            return;
        }

        $.ajax({
            url: '/api/members/show',
            method: 'GET',
            dataType: 'json',
            data: { id: id },
            success: function (res) {
                if (!res || !res.success || !res.data) {
                    showToast((res && res.message) ? res.message : 'Gagal memuat data anggota.', 'destructive');
                    return;
                }

                var data = res.data;
                $memberId.val(data.id || id);
                $('[name="name"]').val(data.name || '');
                $('[name="phone"]').val(data.phone || '');
                $('[name="address"]').val(data.address || '');
                $('[name="email"]').val(data.email || '');
                $('[name="password"]').val('');
                $('[name="gender"][value="' + (data.gender || 'male') + '"]').prop('checked', true);
                $('[name="status"][value="' + (data.status || 'active') + '"]').prop('checked', true);
                $('[name="avatar_remove"]').val('');

                setAvatarPreview(data.avatar || '');
            },
            error: function (xhr) {
                var message = 'Gagal memuat data anggota.';
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

        var id = getMemberId();
        if (!id) {
            showToast('ID anggota tidak valid.', 'destructive');
            return;
        }

        isSubmitting = true;
        $submitBtn.prop('disabled', true).text('Menyimpan...');

        var formData = new FormData($form[0]);
        formData.set('id', id);

        $.ajax({
            url: '/api/members/update',
            method: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res && res.success) {
                    showToast((res && res.message) ? res.message : 'Data anggota berhasil diperbarui.', 'success');

                    setTimeout(function () {
                        window.location.href = '/member';
                    }, 700);
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal memperbarui anggota.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal memperbarui anggota.';
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

    loadMember();
});

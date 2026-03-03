$(function () {
    var $tbody = $('#user_table_body');
    var $countSpan = $('#user_count');
    var $search = $('#user_search');
    var $pageInfo = $('#user_page_info');
    var $prevBtn = $('#user_prev');
    var $nextBtn = $('#user_next');
    var $deleteUserName = $('#delete_user_name');
    var $confirmDeleteBtn = $('#confirm_delete_user');

    var currentPage = 1;
    var lastPage = 1;
    var perPage = 10;
    var sortField = 'id';
    var sortDir = 'desc';
    var selectedUserId = 0;
    var isDeleting = false;

    function closeDeleteModal() {
        $('[data-kt-modal-dismiss="#delete_user_modal"]').first().trigger('click');
    }

    function openDeleteModal(userId, userName) {
        selectedUserId = parseInt(userId, 10) || 0;
        $deleteUserName.text(userName || '-');

        var $modalTrigger = $('<button>', {
            type: 'button',
            'data-kt-modal-toggle': '#delete_user_modal'
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

    function renderTable(users) {
        if (!$tbody.length) return;

        $tbody.empty();

        if (!users.length) {
            $tbody.append(
                '<tr>' +
                    '<td colspan="8" class="text-center py-6 text-sm text-secondary-foreground">' +
                        'Belum ada data pengelola.' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        $.each(users, function (_, user) {
            var avatarSrc = user.avatar
                ? '/user/avatar?file=' + encodeURIComponent(user.avatar)
                : '/themes/metronic/dist/assets/media/avatars/blank.png';

            var roleBadge = user.role === 'superadmin'
                ? '<span class="kt-badge kt-badge-primary kt-badge-outline">Superadmin</span>'
                : '<span class="kt-badge kt-badge-mono kt-badge-outline">Librarian</span>';
            var isSuperadmin = user.role === 'superadmin';
            var deleteBtnClass = 'kt-btn kt-btn-icon kt-btn-ghost btn-delete-user' + (isSuperadmin ? ' opacity-50 cursor-not-allowed' : '');
            var deleteBtnAttr = isSuperadmin ? 'disabled title="Superadmin tidak dapat dihapus"' : '';

            var statusBadge = user.status === 'active'
                ? '<span class="kt-badge kt-badge-success kt-badge-outline">Aktif</span>'
                : '<span class="kt-badge kt-badge-destructive kt-badge-outline">Tidak Aktif</span>';

            var genderLabel = user.gender === 'male'
                ? 'Laki-laki'
                : (user.gender === 'female' ? 'Perempuan' : '-');

            var rowHtml =
                '<tr>' +
                    '<td>' +
                        '<div class="flex items-center gap-2.5">' +
                            '<div>' +
                                '<img class="h-9 rounded-full" src="' + avatarSrc + '" style="min-width:36px"/>' +
                            '</div>' +
                            '<div class="flex flex-col gap-0.5">' +
                                '<span class="leading-none font-medium text-sm text-mono">' + (user.name || '-') + '</span>' +
                                '<span class="text-sm text-secondary-foreground font-normal">' + (user.email || '-') + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td>' + roleBadge + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + genderLabel + '</td>' +
                    '<td class="text-sm text-foreground font-normal">+' + (user.phone || '-') + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + (user.address || '-') + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' +
                        '<a class="kt-btn kt-btn-icon kt-btn-ghost" href="/user/edit?id=' + user.id + '">' +
                            '<i class="ki-filled ki-notepad-edit"></i>' +
                        '</a>' +
                    '</td>' +
                    '<td>' +
                        '<button type="button" class="' + deleteBtnClass + '" ' + deleteBtnAttr + ' data-user-id="' + user.id + '" data-user-name="' + (user.name || '-') + '" data-user-role="' + (user.role || '') + '">' +
                            '<i class="ki-filled ki-trash"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });
    }

    function loadUsers(page) {
        currentPage = page || 1;

        $.ajax({
            url: '/api/users',
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
                            '<td colspan="8" class="text-center py-6 text-sm text-destructive">' +
                                'Terjadi kesalahan saat memuat data pengelola.' +
                            '</td>' +
                        '</tr>'
                    );
                }
            }
        });
    }

    $search.on('input', function () {
        loadUsers(1);
    });

    $prevBtn.on('click', function () {
        if (currentPage > 1) loadUsers(currentPage - 1);
    });

    $nextBtn.on('click', function () {
        if (currentPage < lastPage) loadUsers(currentPage + 1);
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

        loadUsers(1);
    });

    $(document).on('click', '.btn-delete-user', function () {
        if ($(this).prop('disabled') || $(this).data('user-role') === 'superadmin') {
            showToast('User dengan role superadmin tidak dapat dihapus.', 'destructive');
            return;
        }

        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');

        openDeleteModal(userId, userName);
    });

    $confirmDeleteBtn.on('click', function () {
        if (isDeleting) return;

        var userId = selectedUserId;
        if (!userId) {
            showToast('ID pengelola tidak valid.', 'destructive');
            return;
        }

        isDeleting = true;
        $confirmDeleteBtn.prop('disabled', true).text('Menghapus...');

        $.ajax({
            url: '/api/users/delete',
            method: 'POST',
            dataType: 'json',
            data: { id: userId },
            success: function (res) {
                if (res && res.success) {
                    selectedUserId = 0;
                    closeDeleteModal();
                    loadUsers(currentPage);
                    showToast((res && res.message) ? res.message : 'Data pengelola berhasil dihapus.', 'success');
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal menghapus pengelola.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal menghapus pengelola.';
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

    loadUsers(1);
});

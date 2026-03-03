$(function () {
    var $tbody     = $('#member_table_body');
    var $countSpan = $('#member_count');
    var $search    = $('#member_search');
    var $pageInfo  = $('#member_page_info');
    var $prevBtn   = $('#member_prev');
    var $nextBtn   = $('#member_next');
    var $deleteMemberName = $('#delete_member_name');
    var $confirmDeleteBtn = $('#confirm_delete_member');
    var selectedMemberId  = 0;

    var currentPage = 1;
    var lastPage    = 1;
    var perPage     = 10;
    var sortField   = 'id';
    var sortDir     = 'desc';
    var isDeleting  = false;

    function closeDeleteModal() {
        $('[data-kt-modal-dismiss="#delete_member_modal"]').first().trigger('click');
    }

    function openDeleteModal(memberId, memberName) {
        selectedMemberId = parseInt(memberId, 10) || 0;
        $deleteMemberName.text(memberName || '-');

        var $modalTrigger = $('<button>', {
            type: 'button',
            'data-kt-modal-toggle': '#delete_member_modal'
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

    /**
     * Render members data into table.
     * @param {Array} members
     */
    function renderTable(members) {
        if (!$tbody.length) return;

        $tbody.empty();

        if (!members.length) {
            $tbody.append(
                '<tr>' +
                    '<td colspan="7" class="text-center py-6 text-sm text-secondary-foreground">' +
                        'Belum ada data anggota.' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        $.each(members, function (_, member) {
            var avatarSrc = member.avatar
                ? '/member/avatar?file=' + encodeURIComponent(member.avatar)
                : '/themes/metronic/dist/assets/media/avatars/blank.png';

            var statusBadge = member.status === 'active'
                ? '<span class="kt-badge kt-badge-success kt-badge-outline">Aktif</span>'
                : '<span class="kt-badge kt-badge-destructive kt-badge-outline">Tidak Aktif</span>';

            var genderLabel = member.gender === 'male'
                ? 'Laki-laki'
                : (member.gender === 'female' ? 'Perempuan' : '-');

            var rowHtml =
                '<tr>' +
                    '<td>' +
                        '<div class="flex items-center gap-2.5">' +
                            '<div>' +
                                '<img class="h-9 rounded-full" src="' + avatarSrc + '" style="min-width:36px"/>' +
                            '</div>' +
                            '<div class="flex flex-col gap-0.5">' +
                                '<span class="leading-none font-medium text-sm text-mono">' +
                                    (member.name || '-') +
                                '</span>' +
                                '<span class="text-sm text-secondary-foreground font-normal">' +
                                    (member.email || '-') +
                                '</span>' +
                            '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + genderLabel + '</td>' +
                    '<td class="text-sm text-foreground font-normal">+' + (member.phone || '-') + '</td>' +
                    '<td class="text-sm text-foreground font-normal">' + (member.address || '-') + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' +
                        '<a class="kt-btn kt-btn-icon kt-btn-ghost" href="/member/edit?id=' + member.id + '">' +
                            '<i class="ki-filled ki-notepad-edit"></i>' +
                        '</a>' +
                    '</td>' +
                    '<td>' +
                        '<button type="button" class="kt-btn kt-btn-icon kt-btn-ghost btn-delete-member" data-member-id="' + member.id + '" data-member-name="' + (member.name || '-') + '">' +
                            '<i class="ki-filled ki-trash"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });
    }

    /**
     * Load members data from API.
     * @param {*} page 
     */
    function loadMembers(page) {
        currentPage = page || 1;

        $.ajax({
            url: '/api/members',
            method: 'GET',
            dataType: 'json',
            data: {
                page: currentPage,
                per_page: perPage,
                q: $search.val() || '',
                sort_by:  sortField,
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
                            '<td colspan="7" class="text-center py-6 text-sm text-destructive">' +
                                'Terjadi kesalahan saat memuat data anggota.' +
                            '</td>' +
                        '</tr>'
                    );
                }
            }
        });
    }

    /**
     * Search input handler.
     */
    $search.on('input', function () {
        loadMembers(1);
    });

    /**
     * Pagination controls.
     */
    $prevBtn.on('click', function () {
        if (currentPage > 1) loadMembers(currentPage - 1);
    });

    $nextBtn.on('click', function () {
        if (currentPage < lastPage) loadMembers(currentPage + 1);
    });

    loadMembers(1);

    /**
     * Sorting controls.
     */

    var $sortControls = $('[data-sort-field]');

    $sortControls.on('click', function () {
        var field = $(this).data('sort-field');

        if (sortField === field) {
            sortDir = (sortDir === 'asc') ? 'desc' : 'asc';
        } else {
            sortField = field;
            sortDir   = 'asc';
        }

        $sortControls.removeClass('is-sorted-asc is-sorted-desc');
        $(this).addClass(sortDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');

        loadMembers(1);
    });

    /**
     * Delete member handler.
     */
    $(document).on('click', '.btn-delete-member', function () {
        var memberId = $(this).data('member-id');
        var memberName = $(this).data('member-name');

        openDeleteModal(memberId, memberName);
    });

    $confirmDeleteBtn.on('click', function () {
        if (isDeleting) return;

        var memberId = selectedMemberId;
        if (!memberId) {
            showToast('ID anggota tidak valid.', 'destructive');
            return;
        }

        isDeleting = true;
        $confirmDeleteBtn.prop('disabled', true).text('Menghapus...');

        $.ajax({
            url: '/api/members/delete',
            method: 'POST',
            dataType: 'json',
            data: { id: memberId },
            success: function (res) {
                if (res && res.success) {
                    selectedMemberId = 0;
                    closeDeleteModal();
                    loadMembers(currentPage);
                    showToast((res && res.message) ? res.message : 'Data anggota berhasil dihapus.', 'success');
                    return;
                }

                showToast((res && res.message) ? res.message : 'Gagal menghapus anggota.', 'destructive');
            },
            error: function (xhr) {
                var message = 'Gagal menghapus anggota.';

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
});

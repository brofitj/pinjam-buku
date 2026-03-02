$(function () {
    var $tbody     = $('#member_table_body');
    var $countSpan = $('#member_count');
    var $search    = $('#member_search');
    var $pageInfo  = $('#member_page_info');
    var $prevBtn   = $('#member_prev');
    var $nextBtn   = $('#member_next');

    var currentPage = 1;
    var lastPage    = 1;
    var perPage     = 10;

    /**
     * Render members data into table
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
                                '<img class="h-9 rounded-full" src="/themes/metronic/dist/assets/media/avatars/blank.png" style="min-width:36px"/>' +
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
                        '<button type="button" class="kt-btn kt-btn-icon kt-btn-ghost" data-member-id="' + member.id + '">' +
                            '<i class="ki-filled ki-trash"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>';

            $tbody.append(rowHtml);
        });
    }

    /**
     * Load members data from API
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

                if ($pageInfo && $pageInfo.length) {
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
     * Search input handler
     */
    $search.on('input', function () {
        loadMembers(1);
    });

    /**
     * Pagination controls
     */
    $prevBtn.on('click', function () {
        if (currentPage > 1) loadMembers(currentPage - 1);
    });

    $nextBtn.on('click', function () {
        if (currentPage < lastPage) loadMembers(currentPage + 1);
    });

    loadMembers(1);

    /**
     * Sorting controls
     */
    var sortField = 'name';
    var sortDir   = 'asc';

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
});
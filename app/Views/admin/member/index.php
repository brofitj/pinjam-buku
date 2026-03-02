<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

$breadcrumbs = [
    ['label' => 'Anggota', 'url' => '/member'],
    ['label' => 'Semua'],
];

ob_start();

?>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Anggota
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Central hub for personal customization.
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="/member/add">
                <i class="ki-filled ki-plus"></i>
                Anggota
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed pb-7.5">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header flex-wrap py-5">
                <h3 class="kt-card-title">
                    <span id="member_count">0</span> Anggota
                </h3>
                <div class="flex items-center gap-6">
                    <label class="kt-input">
                        <i class="ki-filled ki-magnifier"></i>
                        <input id="member_search" placeholder="Cari anggota" type="text" value=""/>
                    </label>
                </div>
            </div>
    
            <div class="kt-card-content">
                <div id="member_table_wrapper">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table table-fixed kt-table-border">
                            <thead>
                                <tr>
                                    <th class="w-[250px]" data-sort-field="name">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">
                                                Nama
                                            </span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[200px]" data-sort-field="gender">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">
                                                Jenis Kelamin
                                            </span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">
                                                Nomor WA
                                            </span>
                                        </span>
                                    </th>
                                    <th class="w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">
                                                Alamat
                                            </span>
                                        </span>
                                    </th>
                                    <th class="w-[100px]" data-sort-field="status">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">
                                                Status
                                            </span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[60px]"></th>
                                    <th class="w-[60px]"></th>
                                </tr>
                            </thead>
                            <tbody id="member_table_body"></tbody>
                        </table>
                    </div>
                    <div class="kt-card-footer justify-end md:justify-end flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <button id="member_prev" class="kt-btn kt-btn-sm kt-btn-outline">Prev</button>
                            <span id="member_page_info">Halaman 1 dari 1</span>
                            <button id="member_next" class="kt-btn kt-btn-sm kt-btn-outline">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- <button class="kt-btn" data-kt-modal-toggle="#modal">Hapus</button> -->
<div class="kt-modal" data-kt-modal="true" id="delete_member_modal">
    <div class="kt-modal-content max-w-[500px] top-[5%]">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Hapus</h3>
            <button
                type="button"
                class="kt-modal-close"
                aria-label="Close modal"
                data-kt-modal-dismiss="#delete_member_modal"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="lucide lucide-x"
                    aria-hidden="true"
                >
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
        <div class="kt-modal-body">
            <div class="text-sm text-foreground font-normal">
                Data anggota <span id="delete_member_name" class="font-semibold text-mono">-</span>
                akan dihapus secara permanen. Apakah Anda yakin ingin melanjutkan?
            </div>
        </div>
        <div class="kt-modal-footer">
            <div></div>
            <div class="flex gap-4">
                <button class="kt-btn kt-btn-secondary" data-kt-modal-dismiss="#delete_member_modal">Batal</button>
                <button class="kt-btn kt-btn-destructive" id="confirm_delete_member" type="button">Hapus</button>
            </div>
        </div>
    </div>
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/admin/app.php';

?>

<script src="/assets/js/admin/member/index.js"></script>
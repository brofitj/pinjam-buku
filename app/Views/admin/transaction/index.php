<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

$breadcrumbs = [
    ['label' => 'Transaksi', 'url' => '/transaction'],
    ['label' => 'Semua'],
];

ob_start();

?>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Transaksi
            </h1>
        </div>
    </div>
</div>

<div class="kt-container-fixed pb-7.5">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header flex-wrap py-5">
                <div class="flex items-center gap-2">
                    <button id="transaction_tab_pending" class="kt-btn kt-btn-sm kt-btn-primary" type="button">
                        Perlu Persetujuan (<span id="transaction_tab_pending_count">0</span>)
                    </button>
                    <button id="transaction_tab_overdue" class="kt-btn kt-btn-sm kt-btn-outline" type="button">
                        Terlambat (<span id="transaction_tab_overdue_count">0</span>)
                    </button>
                    <button id="transaction_tab_other" class="kt-btn kt-btn-sm kt-btn-outline" type="button">
                        Riwayat Lainnya (<span id="transaction_tab_other_count">0</span>)
                    </button>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <label class="kt-input">
                        <i class="ki-filled ki-magnifier"></i>
                        <input id="transaction_search" placeholder="Cari kode nama anggota status" type="text" value=""/>
                    </label>
                </div>
            </div>
            <div class="kt-card-content">
                <div id="transaction_table_wrapper">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table table-fixed kt-table-border">
                            <thead>
                                <tr>
                                    <th class="w-[60px]"></th>
                                    <th class="w-[200px]" data-sort-field="status">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[170px]" data-sort-field="transaction_code">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Kode</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[200px]" data-sort-field="member_name">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Anggota</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[150px]" data-sort-field="borrow_date">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Pinjam</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[150px]" data-sort-field="due_date">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Jatuh Tempo</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[150px]" data-sort-field="return_date">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Kembali</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[100px]" data-sort-field="total_books">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Buku</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[150px]" data-sort-field="fine_amount">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Denda</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="transaction_table_body"></tbody>
                        </table>
                    </div>
                    <div class="kt-card-footer justify-end md:justify-end flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                        <div class="flex items-center gap-4 order-1 md:order-2">
                            <button id="transaction_prev" class="kt-btn kt-btn-sm kt-btn-outline">Prev</button>
                            <span id="transaction_page_info">Halaman 1 dari 1</span>
                            <button id="transaction_next" class="kt-btn kt-btn-sm kt-btn-outline">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="kt-modal" data-kt-modal="true" id="approve_transaction_modal">
    <div class="kt-modal-content max-w-[500px] top-[5%]">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Konfirmasi Approval</h3>
            <button
                type="button"
                class="kt-modal-close"
                aria-label="Close modal"
                data-kt-modal-dismiss="#approve_transaction_modal"
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
                Transaksi <span id="approve_transaction_code" class="font-semibold text-mono">-</span>
                akan diubah ke status <span class="font-semibold text-mono">Dipinjam</span>.
                Apakah Anda yakin ingin melanjutkan?
            </div>
            <div class="mt-4">
                <label class="kt-form-label font-normal text-mono mb-2" for="approve_duration_days">
                    Durasi Peminjaman (hari)
                </label>
                <input
                    id="approve_duration_days"
                    type="number"
                    min="1"
                    max="60"
                    step="1"
                    class="kt-input w-full"
                    value="7"
                />
                <div class="text-xs text-secondary-foreground mt-2">
                    Jatuh tempo akan dihitung berdasarkan durasi ini saat transaksi disetujui.
                </div>
            </div>
        </div>
        <div class="kt-modal-footer">
            <div></div>
            <div class="flex gap-4">
                <button class="kt-btn kt-btn-secondary" data-kt-modal-dismiss="#approve_transaction_modal">Batal</button>
                <button class="kt-btn kt-btn-primary" id="confirm_approve_transaction" type="button">Ya, Ubah Status</button>
            </div>
        </div>
    </div>
</div>

<div class="kt-modal" data-kt-modal="true" id="approve_return_modal">
    <div class="kt-modal-content max-w-[500px] top-[5%]">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Konfirmasi Approval</h3>
            <button
                type="button"
                class="kt-modal-close"
                aria-label="Close modal"
                data-kt-modal-dismiss="#approve_return_modal"
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
                Transaksi <span id="approve_return_transaction_code" class="font-semibold text-mono">-</span>
                akan diubah ke status <span class="font-semibold text-mono">Selesai</span>.
                Apakah Anda yakin ingin melanjutkan?
            </div>
        </div>
        <div class="kt-modal-footer">
            <div></div>
            <div class="flex gap-4">
                <button class="kt-btn kt-btn-secondary" data-kt-modal-dismiss="#approve_return_modal">Batal</button>
                <button class="kt-btn kt-btn-primary" id="confirm_approve_return" type="button">Ya, Ubah Status</button>
            </div>
        </div>
    </div>
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/admin/app.php';

?>

<script src="/assets/js/admin/transaction/index.js"></script>

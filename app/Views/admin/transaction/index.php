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
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Daftar transaksi peminjaman buku.
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed pb-7.5">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full">
            <div class="kt-card-header flex-wrap py-5">
                <h3 class="kt-card-title">
                    <span id="transaction_count">0</span> Transaksi
                </h3>
                <div class="flex items-center gap-6">
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
                                    <th class="w-[170px]" data-sort-field="transaction_code">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Kode</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[220px]" data-sort-field="member_name">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Anggota</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[130px]" data-sort-field="borrow_date">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Pinjam</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[130px]" data-sort-field="due_date">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Jatuh Tempo</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[130px]" data-sort-field="return_date">
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
                                    <th class="w-[140px]" data-sort-field="fine_amount">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Denda</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="w-[130px]" data-sort-field="status">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
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

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/admin/app.php';

?>

<script src="/assets/js/admin/transaction/index.js"></script>

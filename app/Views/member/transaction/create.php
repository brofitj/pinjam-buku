<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

ob_start();

?>

<div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
    <div class="flex flex-col justify-center gap-2">
        <h1 class="text-xl font-medium leading-none text-mono">
            Buat Transaksi Peminjaman
        </h1>
    </div>
    <div class="flex items-center gap-2.5">
        <a class="kt-btn kt-btn-outline" href="/member/dashboard">
            Kembali
        </a>
    </div>
</div>

<div class="grid gap-5 lg:gap-7.5 mb-7.5">
    <div class="kt-card kt-card-grid min-w-full">
        <div class="kt-card-header py-4">
            <div class="flex items-center gap-2">
                <button id="member_tab_books" class="kt-btn kt-btn-sm kt-btn-primary" type="button">Buku</button>
                <button id="member_tab_cart" class="kt-btn kt-btn-sm kt-btn-outline" type="button">
                    Keranjang (<span class="member-cart-count">0</span>)
                </button>
            </div>
        </div>

        <div id="member_tab_panel_books" class="kt-card-content">
            <div class="kt-card-header justify-end py-4">
                <label class="kt-input">
                    <i class="ki-filled ki-magnifier"></i>
                    <input id="member_book_search" placeholder="Cari kode, judul, penulis" type="text" value=""/>
                </label>
            </div>
            <div class="kt-scrollable-x-auto">
                <table class="kt-table table-fixed kt-table-border">
                    <thead>
                        <tr>
                            <th class="w-[170px]" data-sort-field="book_code">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Kode</span>
                                    <span class="kt-table-col-sort"></span>
                                </span>
                            </th>
                            <th class="w-[120px]">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Cover</span>
                                </span>
                            </th>
                            <th class="w-[360px]" data-sort-field="title">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Judul</span>
                                    <span class="kt-table-col-sort"></span>
                                </span>
                            </th>
                            <th class="w-[220px]" data-sort-field="author">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Penulis</span>
                                    <span class="kt-table-col-sort"></span>
                                </span>
                            </th>
                            <th class="w-[120px]" data-sort-field="stock">
                                <span class="kt-table-col">
                                    <span class="kt-table-col-label">Stok</span>
                                    <span class="kt-table-col-sort"></span>
                                </span>
                            </th>
                            <th class="w-[120px]"></th>
                        </tr>
                    </thead>
                    <tbody id="member_book_table_body"></tbody>
                </table>
            </div>
            <div class="kt-card-footer justify-end md:justify-end flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                <div class="flex items-center gap-4 order-1 md:order-2">
                    <button id="member_book_prev" class="kt-btn kt-btn-sm kt-btn-outline">Prev</button>
                    <span id="member_book_page_info">Halaman 1 dari 1</span>
                    <button id="member_book_next" class="kt-btn kt-btn-sm kt-btn-outline">Next</button>
                </div>
            </div>
        </div>

        <div id="member_tab_panel_cart" class="kt-card-content hidden">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table table-fixed kt-table-border">
                    <thead>
                        <tr>
                            <th class="w-[120px]">Kode</th>
                            <th class="w-[100px]">Cover</th>
                            <th class="w-[220px]">Judul</th>
                            <th class="w-[90px]">Stok</th>
                            <th class="w-[110px]">Jumlah</th>
                            <th class="w-[90px]"></th>
                        </tr>
                    </thead>
                    <tbody id="member_cart_table_body"></tbody>
                </table>
            </div>
            <div class="kt-card-footer justify-end gap-3 mt-0">
                <button id="member_cart_reset" class="kt-btn kt-btn-outline" type="button">Reset</button>
                <button id="member_create_transaction_btn" class="kt-btn kt-btn-primary" type="button">
                    Ajukan Transaksi
                </button>
            </div>
        </div>
    </div>
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/member/app.php';

?>

<script src="/assets/js/member/transaction/create.js"></script>

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
            Detail Transaksi
        </h1>
        <div class="text-sm text-secondary-foreground">
            Kode: <span id="member_transaction_detail_code" class="font-semibold text-mono">-</span>
        </div>
    </div>
    <div class="flex items-center gap-2.5">
        <a class="kt-btn kt-btn-outline" href="/member/dashboard">
            <i class="ki-filled ki-left"></i>
            Kembali
        </a>
    </div>
</div>

<div class="grid gap-5 lg:gap-7.5">
    <div class="kt-card min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Informasi Transaksi</h3>
        </div>
        <div class="kt-card-content">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="text-sm">
                    <span class="text-secondary-foreground">Status:</span>
                    <span id="member_transaction_detail_status">-</span>
                </div>
                <div class="text-sm">
                    <span class="text-secondary-foreground">Denda:</span>
                    <span id="member_transaction_detail_fine_amount" class="font-medium text-foreground">-</span>
                </div>
                <div class="text-sm">
                    <span class="text-secondary-foreground">Tanggal Pinjam:</span>
                    <span id="member_transaction_detail_borrow_date" class="font-medium text-foreground">-</span>
                </div>
                <div class="text-sm">
                    <span class="text-secondary-foreground">Jatuh Tempo:</span>
                    <span id="member_transaction_detail_due_date" class="font-medium text-foreground">-</span>
                </div>
                <div class="text-sm">
                    <span class="text-secondary-foreground">Tanggal Kembali:</span>
                    <span id="member_transaction_detail_return_date" class="font-medium text-foreground">-</span>
                </div>
            </div>
        </div>
    </div>

    <div class="kt-card min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                Daftar Buku (<span id="member_transaction_detail_total_books">0</span>)
            </h3>
        </div>
        <div class="kt-card-content">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table table-fixed kt-table-border">
                    <thead>
                        <tr>
                            <th class="w-[170px]">
                                <span class="kt-table-col"><span class="kt-table-col-label">Kode Buku</span></span>
                            </th>
                            <th class="w-[90px]">
                                <span class="kt-table-col"><span class="kt-table-col-label">Cover</span></span>
                            </th>
                            <th class="w-[320px]">
                                <span class="kt-table-col"><span class="kt-table-col-label">Judul</span></span>
                            </th>
                            <th class="w-[240px]">
                                <span class="kt-table-col"><span class="kt-table-col-label">Penulis</span></span>
                            </th>
                            <th class="w-[120px]">
                                <span class="kt-table-col"><span class="kt-table-col-label">Qty</span></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="member_transaction_detail_items_body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/member/app.php';

?>

<script src="/assets/js/member/transaction/detail.js"></script>

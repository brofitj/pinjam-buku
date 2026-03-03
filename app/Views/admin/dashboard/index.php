<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

$breadcrumbs = [
    ['label' => 'Dashboard'],
];

ob_start();

?>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Dashboard
            </h1>
        </div>
    </div>
</div>

<style>
    .channel-stats-bg {
        background-image: url('/themes/metronic/dist/assets/media/images/2600x1600/bg-3.png');
    }
    .dark .channel-stats-bg {
        background-image: url('/themes/metronic/dist/assets/media/images/2600x1600/bg-3-dark.png');
    }
</style>

<div class="kt-container-fixed pb-7.5">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 lg:gap-7.5">
        <div class="kt-card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
            <div class="size-10 rounded-lg ring-1 ring-input bg-accent/60 text-info flex items-center justify-center mt-4 ms-5">
                <i class="ki-filled ki-delivery-3 text-xl"></i>
            </div>
            <div class="flex flex-col gap-1 pb-4 px-5">
                <span id="dashboard_active_transactions" class="text-3xl font-semibold text-mono">0</span>
                <span class="text-sm font-normal text-secondary-foreground">Transaksi Aktif</span>
            </div>
        </div>

        <div class="kt-card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
            <div class="size-10 rounded-lg ring-1 ring-input bg-accent/60 text-info flex items-center justify-center mt-4 ms-5">
                <i class="ki-filled ki-book text-xl"></i>
            </div>
            <div class="flex flex-col gap-1 pb-4 px-5">
                <span id="dashboard_total_books" class="text-3xl font-semibold text-mono">0</span>
                <span class="text-sm font-normal text-secondary-foreground">Total Buku</span>
            </div>
        </div>

        <div class="kt-card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
            <div class="size-10 rounded-lg ring-1 ring-input bg-accent/60 text-info flex items-center justify-center mt-4 ms-5">
                <i class="ki-filled ki-users text-xl"></i>
            </div>
            <div class="flex flex-col gap-1 pb-4 px-5">
                <span id="dashboard_total_members" class="text-3xl font-semibold text-mono">0</span>
                <span class="text-sm font-normal text-secondary-foreground">Total Anggota</span>
            </div>
        </div>

        <div class="kt-card flex-col justify-between gap-6 h-full bg-cover rtl:bg-[left_top_-1.7rem] bg-[right_top_-1.7rem] bg-no-repeat channel-stats-bg">
            <div class="size-10 rounded-lg ring-1 ring-input bg-accent/60 text-info flex items-center justify-center mt-4 ms-5">
                <i class="ki-filled ki-user text-xl"></i>
            </div>
            <div class="flex flex-col gap-1 pb-4 px-5">
                <span id="dashboard_total_managers" class="text-3xl font-semibold text-mono">0</span>
                <span class="text-sm font-normal text-secondary-foreground">Total Pengelola</span>
            </div>
        </div>
    </div>
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/admin/app.php';

?>

<script src="/assets/js/admin/dashboard/index.js"></script>

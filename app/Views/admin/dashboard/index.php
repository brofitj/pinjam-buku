<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

ob_start();

?>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Dashboard
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Central hub for personal customization.
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed pb-7.5">
    
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/admin/app.php';
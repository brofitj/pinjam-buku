<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

ob_start();

?>

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/member/app.php';
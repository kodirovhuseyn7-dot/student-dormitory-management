
<?php
$pageTitle = $pageTitle ?? 'SDMS — Идоракунии хобгоҳ';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="main-header">
    <a href="dashboard.php" class="brand">SDMS</a>

    <nav class="main-nav">
        <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Панели асосӣ</a>
        <a href="students.php" class="<?= $currentPage === 'students.php' ? 'active' : '' ?>">Донишҷӯён</a>
        <a href="dormitories.php" class="<?= $currentPage === 'dormitories.php' ? 'active' : '' ?>">Хобгоҳҳо</a>
        <a href="rooms.php" class="<?= $currentPage === 'rooms.php' ? 'active' : '' ?>">Ҳуҷраҳо</a>
        <a href="applications.php" class="<?= $currentPage === 'applications.php' ? 'active' : '' ?>">Дархостҳо</a>
        <a href="allocations.php" class="<?= $currentPage === 'allocations.php' ? 'active' : '' ?>">Ҷойгиркунӣ</a>
        <a href="payments.php" class="<?= $currentPage === 'payments.php' ? 'active' : '' ?>">Пардохтҳо</a>
        <a href="reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">Ҳисобот</a>
    </nav>
</header>
<main class="page-content">

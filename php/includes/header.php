<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'SDMS';
$active = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';
$roleLabels = ['admin' => 'Администратор', 'manager' => 'Менеҷер', 'student' => 'Донишҷӯ'];
?>
<!DOCTYPE html>
<html lang="tg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | SDMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= $user ? 'dashboard.php' : 'login.php' ?>"><span class="brand-mark">S</span><span>SDMS</span></a>
  <?php if ($user): ?>
  <nav class="main-nav">
    <a class="<?= $active==='dashboard.php'?'active':'' ?>" href="dashboard.php">Панели асосӣ</a>
    <a class="<?= $active==='dormitories.php'?'active':'' ?>" href="dormitories.php">Хобгоҳҳо</a>
    <?php if (in_array($role, ['admin','manager'], true)): ?>
      <a class="<?= $active==='students.php'?'active':'' ?>" href="students.php">Донишҷӯён</a>
      <a class="<?= $active==='rooms.php'?'active':'' ?>" href="rooms.php">Ҳуҷраҳо</a>
      <a class="<?= $active==='applications.php'?'active':'' ?>" href="applications.php">Дархостҳо</a>
      <a class="<?= $active==='allocations.php'?'active':'' ?>" href="allocations.php">Ҷойгиркунӣ</a>
      <a class="<?= $active==='reports.php'?'active':'' ?>" href="reports.php">Ҳисобот</a>
    <?php else: ?>
      <a class="<?= $active==='my_applications.php'?'active':'' ?>" href="my_applications.php">Дархостҳои ман</a>
    <?php endif; ?>
    <a class="<?= $active==='payments.php'?'active':'' ?>" href="payments.php">Пардохтҳо</a>
  </nav>
  <div class="user-area"><span class="user-avatar"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span><span class="user-meta"><b><?= e($user['name'] ?? 'Корбар') ?></b><small><?= e($roleLabels[$role] ?? $role) ?></small></span><a class="logout-link" href="logout.php">Баромадан</a></div>
  <?php else: ?>
  <nav class="main-nav"><a href="login.php">Воридшавӣ</a><a class="nav-cta" href="register.php">Бақайдгирӣ</a></nav>
  <?php endif; ?>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): $f=$_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="alert <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endif; ?>

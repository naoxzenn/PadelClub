<?php
if (!isset($pageTitle)) $pageTitle = 'PadelClub';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — PadelClub Premium</title>
    <meta name="description" content="Sistem booking lapangan padel online terpercaya. Pesan lapangan padel indoor dan outdoor dengan mudah dan cepat.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap">
    <link rel="stylesheet" href="<?= $baseUrl ?? '' ?>assets/style.css">
</head>
<body>

<nav class="nav">
    <a class="logo" href="<?= $baseUrl ?? '' ?>index.php">PadelClub</a>

    <ul class="nav-links">
        <li><a href="<?= $baseUrl ?? '' ?>index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Beranda</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?= $baseUrl ?? '' ?>dashboardadmin.php" class="<?= $currentPage === 'dashboardadmin.php' ? 'active' : '' ?>">Dashboard Admin</a></li>
            <?php else: ?>
                <li><a href="<?= $baseUrl ?? '' ?>dashboarduser.php" class="<?= $currentPage === 'dashboarduser.php' ? 'active' : '' ?>">Dashboard Saya</a></li>
                <li><a href="<?= $baseUrl ?? '' ?>booking.php" class="<?= $currentPage === 'booking.php' ? 'active' : '' ?>">Booking</a></li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>

    <div class="nav-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="avatar" title="<?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>">
                <?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
            </div>
            <a href="<?= $baseUrl ?? '' ?>logout.php" class="btn-pill">Keluar</a>
        <?php else: ?>
            <a href="<?= $baseUrl ?? '' ?>login.php" class="btn-pill">Masuk</a>
            <a href="<?= $baseUrl ?? '' ?>register.php" class="btn-pill primary">Daftar</a>
        <?php endif; ?>
    </div>
</nav>

<?php
if (!isset($pageTitle))
    $pageTitle = 'PadelClub';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — PadelClub Premium</title>
    <meta name="description"
        content="Sistem booking lapangan padel online terpercaya. Pesan lapangan padel indoor dan outdoor dengan mudah dan cepat.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap">
    <link rel="stylesheet" href="<?= $baseUrl ?? '' ?>assets/style.css">
    <?php
    // Load Clerk JS hanya untuk customer/guest (bukan admin/kasir dashboard)
    $isAdminKasir = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'kasir'], true);
    if (!$isAdminKasir) {
        include_once __DIR__ . '/clerk-js.php';
    }
    ?>

</head>

<body
    class="<?= (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir')) ? 'dashboard-body' : '' ?>">

    <!-- Toast container (shared by all pages) -->
    <div id="toast-container"></div>

    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir')): ?>
        <!-- DASHBOARD LAYOUT -->
        <div class="dashboard-container">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <div class="sidebar-brand">
                    <span class="gradient-text">PadelClub</span>
                    <span class="role-badge <?= $_SESSION['role'] ?>"><?= ucfirst($_SESSION['role']) ?></span>
                </div>

                <nav class="sidebar-nav">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php"
                            class="<?= $currentPage === 'dashboard.php' && !isset($_GET['tab']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">dashboard</span> Dashboard
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php?tab=booking"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'booking' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">calendar_month</span> Booking
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php?tab=users"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'users' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">groups</span> Customers
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php?tab=lapangan"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'lapangan' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">sports_tennis</span> Courts
                        </a>
                        <a href="#" class="disabled-link">
                            <span class="material-symbols-outlined">package</span> Packages
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php?tab=payment"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'payment' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">payments</span> Payments
                        </a>
                        <a href="#" class="disabled-link">
                            <span class="material-symbols-outlined">analytics</span> Reports
                        </a>
                        <a href="#" class="disabled-link">
                            <span class="material-symbols-outlined">manage_accounts</span> Users
                        </a>
                        <a href="#" class="disabled-link">
                            <span class="material-symbols-outlined">settings</span> Settings
                        </a>
                    <?php elseif ($_SESSION['role'] === 'kasir'): ?>
                        <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php"
                            class="<?= $currentPage === 'dashboard.php' && !isset($_GET['tab']) && strpos($_SERVER['REQUEST_URI'], '/kasir/') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">dashboard</span> Dashboard
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php?tab=booking-confirm"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'booking-confirm' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">check_circle</span> Booking
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php?tab=payment"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'payment' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">payments</span> Pembayaran
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php?tab=receipt"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'receipt' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">receipt</span> Cetak Struk
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php?tab=profil"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'profil' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">account_circle</span> Profil
                        </a>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?? '' ?>logout.php" class="logout-btn">
                        <span class="material-symbols-outlined">logout</span> Keluar
                    </a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <main class="dashboard-main">
                <!-- Dashboard Header -->
                <header class="dashboard-header">
                    <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle Sidebar">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="header-title">
                        <h2><?= htmlspecialchars($pageTitle) ?></h2>
                    </div>
                    <div class="header-user">
                        <div class="avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?></div>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
                    </div>
                </header>

                <div class="dashboard-content">
                <?php else: ?>
                    <!-- Normal customer / guest header -->
                    <nav class="nav" id="main-nav">
                        <a class="logo" href="<?= $baseUrl ?? '' ?>index.php">PadelClub</a>

                        <ul class="nav-links">
                            <li><a href="<?= $baseUrl ?? '' ?>index.php"
                                    class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Beranda</a></li>
                            <li><a href="<?= $baseUrl ?? '' ?>about.php"
                                    class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">Tentang</a></li>
                            <li><a href="<?= $baseUrl ?? '' ?>contact.php"
                                    class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">Kontak</a></li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <li><a href="<?= $baseUrl ?? '' ?>admin/dashboard.php"
                                            class="<?= $currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : '' ?>">Dashboard
                                            Admin</a></li>
                                <?php elseif ($_SESSION['role'] === 'kasir'): ?>
                                    <li><a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php"
                                            class="<?= $currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/kasir/') !== false ? 'active' : '' ?>">Dashboard
                                            Kasir</a></li>
                                <?php else: ?>
                                    <li><a href="<?= $baseUrl ?? '' ?>dashboarduser.php"
                                            class="<?= $currentPage === 'dashboarduser.php' ? 'active' : '' ?>">Dashboard Saya</a>
                                    </li>
                                    <li><a href="<?= $baseUrl ?? '' ?>booking.php"
                                            class="<?= $currentPage === 'booking.php' ? 'active' : '' ?>">Booking</a></li>
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
                            <!-- Hamburger -->
                            <button class="nav-hamburger" id="nav-hamburger" aria-label="Menu" aria-expanded="false"
                                aria-controls="mobile-nav">
                                <span></span><span></span><span></span>
                            </button>
                        </div>
                    </nav>

                    <!-- Mobile drawer -->
                    <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation">
                        <a href="<?= $baseUrl ?? '' ?>index.php"
                            class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">home</span> Beranda
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>about.php"
                            class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">info</span> Tentang
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>contact.php"
                            class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">contact_mail</span> Kontak
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="mobile-nav-divider"></div>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php"
                                    class="<?= $currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">dashboard</span> Dashboard Admin
                                </a>
                            <?php elseif ($_SESSION['role'] === 'kasir'): ?>
                                <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php"
                                    class="<?= $currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/kasir/') !== false ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">dashboard</span> Dashboard Kasir
                                </a>
                            <?php else: ?>
                                <a href="<?= $baseUrl ?? '' ?>booking.php"
                                    class="<?= $currentPage === 'booking.php' ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">sports_tennis</span> Booking
                                </a>
                                <a href="<?= $baseUrl ?? '' ?>dashboarduser.php"
                                    class="<?= $currentPage === 'dashboarduser.php' ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">receipt_long</span> Dashboard Saya
                                </a>
                            <?php endif; ?>
                            <div class="mobile-nav-divider"></div>
                            <div class="mobile-nav-actions">
                                <a href="<?= $baseUrl ?? '' ?>logout.php" class="btn btn-secondary">Keluar</a>
                            </div>
                        <?php else: ?>
                            <div class="mobile-nav-divider"></div>
                            <div class="mobile-nav-actions">
                                <a href="<?= $baseUrl ?? '' ?>login.php" class="btn btn-secondary">Masuk</a>
                                <a href="<?= $baseUrl ?? '' ?>register.php" class="btn btn-primary">Daftar</a>
                            </div>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

                <script>
                    (function () {
                        const btn = document.getElementById('nav-hamburger');
                        const drawer = document.getElementById('mobile-nav');
                        if (!btn || !drawer) return;
                        btn.addEventListener('click', function () {
                            const open = drawer.classList.toggle('open');
                            btn.classList.toggle('open', open);
                            btn.setAttribute('aria-expanded', open);
                            document.body.style.overflow = open ? 'hidden' : '';
                        });
                        // Close on outside click
                        document.addEventListener('click', function (e) {
                            if (!btn.contains(e.target) && !drawer.contains(e.target)) {
                                drawer.classList.remove('open');
                                btn.classList.remove('open');
                                btn.setAttribute('aria-expanded', 'false');
                                document.body.style.overflow = '';
                            }
                        });
                        // Close on ESC
                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape') {
                                drawer.classList.remove('open');
                                btn.classList.remove('open');
                                btn.setAttribute('aria-expanded', 'false');
                                document.body.style.overflow = '';
                            }
                        });
                    })();

                    // ── Global toast helper (available to all pages) ──────────────────────────
                    function showToast(message, type = 'success', duration = 4000) {
                        const icons = { success: 'check_circle', error: 'error', warning: 'warning' };
                        const container = document.getElementById('toast-container');
                        const t = document.createElement('div');
                        t.className = 'toast ' + (type !== 'success' ? type : '');
                        t.innerHTML = `<span class="material-symbols-outlined" style="color:${type === 'success' ? 'var(--green)' : type === 'error' ? '#EF4444' : '#F59E0B'}">${icons[type] || 'info'}</span>${message}`;
                        container.appendChild(t);
                        setTimeout(() => {
                            t.classList.add('hiding');
                            t.addEventListener('animationend', () => t.remove());
                        }, duration);
                    }

                    // ── Global modal helpers ──────────────────────────────────────────────────
                    function openModal(id) {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.style.display = 'flex';
                        requestAnimationFrame(() => el.classList.add('show'));
                        document.body.style.overflow = 'hidden';
                        // Focus first focusable element
                        const focusable = el.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                        if (focusable) setTimeout(() => focusable.focus(), 50);
                    }
                    function closeModal(id) {
                        const el = document.getElementById(id);
                        if (!el) return;
                        el.classList.remove('show');
                        el.addEventListener('transitionend', function handler() {
                            el.style.display = 'none';
                            el.removeEventListener('transitionend', handler);
                        });
                        document.body.style.overflow = '';
                    }
                    // Close modal on backdrop click
                    document.addEventListener('click', function (e) {
                        if (e.target.classList.contains('modal-backdrop')) {
                            const id = e.target.id;
                            if (id) closeModal(id);
                        }
                    });
                    // Close on ESC
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') {
                            document.querySelectorAll('.modal-backdrop.show').forEach(m => closeModal(m.id));
                        }
                    });
                </script>
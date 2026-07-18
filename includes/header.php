<?php
if (!isset($pageTitle))
    $pageTitle = 'PadelClub';
$currentPage = basename($_SERVER['PHP_SELF']);

// Load updated user details for sidebar if logged in
$headerUser = null;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/koneksi.php';
    $stmtH = mysqli_prepare($conn, "SELECT nama_lengkap, role, avatar, login_provider FROM users WHERE id = ?");
    if ($stmtH) {
        mysqli_stmt_bind_param($stmtH, 'i', $_SESSION['user_id']);
        mysqli_stmt_execute($stmtH);
        $headerUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtH));
        mysqli_stmt_close($stmtH);
    }
}
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
    <link rel="stylesheet" href="<?= $baseUrl ?? '' ?>assets/css/theme.css">
    <script>
        (function() {
            const theme = localStorage.getItem('padelclub_theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-mode');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <script src="<?= $baseUrl ?? '' ?>assets/js/theme.js" defer></script>
</head>

<body
    class="<?= (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir' || $_SESSION['role'] === 'customer')) ? 'dashboard-body' : '' ?>">

    <!-- Toast container (shared by all pages) -->
    <div id="toast-container"></div>

    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir' || $_SESSION['role'] === 'customer')): ?>
        <!-- DASHBOARD LAYOUT -->
        <div class="dashboard-container">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <div class="sidebar-brand" style="padding: 20px; text-align: center; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 15px;">
                        <span class="material-symbols-outlined" style="font-size: 2.2rem; color: var(--blue);">sports_tennis</span>
                        <span class="gradient-text" style="font-size: 1.6rem; font-weight: 800; text-decoration: none;">PadelClub</span>
                    </div>
                    <div class="sidebar-profile" style="margin: 15px 0; text-align: center;">
                        <div class="profile-avatar" style="width: 70px; height: 70px; border-radius: 50%; background: var(--gradient); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; margin: 0 auto 10px auto; border: 3px solid rgba(255,255,255,0.1); box-shadow: var(--shadow-sm); overflow:hidden;">
                            <?php 
                            $dispAvatar = $headerUser['avatar'] ?? '';
                            $dispName = $headerUser['nama_lengkap'] ?? $_SESSION['nama'] ?? 'User';
                            $dispRole = $_SESSION['role'] ?? 'customer';
                            if (!empty($dispAvatar)): 
                                if (str_starts_with($dispAvatar, 'http')): ?>
                                    <img src="<?= htmlspecialchars($dispAvatar) ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <img src="<?= $baseUrl ?? '' ?>uploads/profile/<?= htmlspecialchars($dispAvatar) ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                                <?php endif; ?>
                            <?php else: ?>
                                <?= strtoupper(substr($dispName, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <h4 class="profile-name" style="font-size: 1rem; font-weight: 700; color: #fff; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;"><?= htmlspecialchars($dispName) ?></h4>
                        <?php 
                        $badgeBg = $dispRole === 'admin' ? 'rgba(14, 165, 233, 0.15)' : ($dispRole === 'kasir' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(34, 197, 94, 0.15)');
                        $badgeCo = $dispRole === 'admin' ? 'var(--blue)' : ($dispRole === 'kasir' ? '#F59E0B' : 'var(--green)');
                        $badgeBo = $dispRole === 'admin' ? 'rgba(14, 165, 233, 0.3)' : ($dispRole === 'kasir' ? 'rgba(245, 158, 11, 0.3)' : 'rgba(34, 197, 94, 0.3)');
                        ?>
                        <span class="role-badge" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; background: <?= $badgeBg ?>; color: <?= $badgeCo ?>; border: 1px solid <?= $badgeBo ?>; display: inline-block; padding: 2px 8px; border-radius: 4px;"><?= ucfirst($dispRole) ?></span>
                    </div>
                </div>

                <nav class="sidebar-nav">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php"
                            class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">dashboard</span> Dashboard
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/bookings.php"
                            class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">calendar_month</span> Booking
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/customers.php"
                            class="<?= $currentPage === 'customers.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">groups</span> Customers
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/courts.php"
                            class="<?= $currentPage === 'courts.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">sports_tennis</span> Courts
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/checkin_list.php"
                            class="<?= $currentPage === 'checkin_list.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">qr_code_scanner</span> Check In
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/analysis.php"
                            class="<?= $currentPage === 'analysis.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">analytics</span> Analisa Data Penyewaan
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/payments.php"
                            class="<?= $currentPage === 'payments.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">payments</span> Payments
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/reports.php"
                            class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">description</span> Reports
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>admin/users.php"
                            class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">manage_accounts</span> Users
                        </a>
                        <div class="sidebar-menu-group" style="display: flex; flex-direction: column;">
                            <a href="<?= $baseUrl ?? '' ?>admin/settings.php"
                                class="<?= ($currentPage === 'settings.php' && strpos($_SERVER['PHP_SELF'], 'backup') === false && strpos($_SERVER['PHP_SELF'], 'restore') === false && strpos($_SERVER['PHP_SELF'], 'export') === false && strpos($_SERVER['PHP_SELF'], 'log') === false) ? 'active' : '' ?>">
                                <span class="material-symbols-outlined">settings</span> Settings
                            </a>
                            <?php 
                            $isBackupPage = (
                                strpos($currentPage, 'backup') !== false || 
                                strpos($currentPage, 'restore') !== false || 
                                strpos($currentPage, 'export') !== false ||
                                strpos($currentPage, 'log') !== false
                            );
                            if ($isBackupPage || $currentPage === 'settings.php'): 
                            ?>
                                <div class="sidebar-submenu" style="padding-left: 24px; display: flex; flex-direction: column; gap: 4px; margin-top: 4px; margin-bottom: 8px;">
                                    <a href="<?= $baseUrl ?? '' ?>admin/backup.php" 
                                       class="<?= $currentPage === 'backup.php' ? 'active' : '' ?>" 
                                       style="padding: 8px 12px; font-size: 0.8rem; gap: 8px; font-weight: normal; border-left: none; border-radius: var(--radius-sm);">
                                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">backup</span> Backup Database
                                    </a>
                                    <a href="<?= $baseUrl ?? '' ?>admin/restore.php" 
                                       class="<?= $currentPage === 'restore.php' ? 'active' : '' ?>" 
                                       style="padding: 8px 12px; font-size: 0.8rem; gap: 8px; font-weight: normal; border-left: none; border-radius: var(--radius-sm);">
                                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">settings_backup_restore</span> Restore Database
                                    </a>
                                    <a href="<?= $baseUrl ?? '' ?>admin/export.php" 
                                       class="<?= $currentPage === 'export.php' ? 'active' : '' ?>" 
                                       style="padding: 8px 12px; font-size: 0.8rem; gap: 8px; font-weight: normal; border-left: none; border-radius: var(--radius-sm);">
                                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">download</span> Export Data
                                    </a>
                                    <a href="<?= $baseUrl ?? '' ?>admin/backup_logs.php" 
                                       class="<?= $currentPage === 'backup_logs.php' ? 'active' : '' ?>" 
                                       style="padding: 8px 12px; font-size: 0.8rem; gap: 8px; font-weight: normal; border-left: none; border-radius: var(--radius-sm);">
                                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">history</span> Log Backup
                                    </a>
                                    <a href="<?= $baseUrl ?? '' ?>admin/backup_settings.php" 
                                       class="<?= $currentPage === 'backup_settings.php' ? 'active' : '' ?>" 
                                       style="padding: 8px 12px; font-size: 0.8rem; gap: 8px; font-weight: normal; border-left: none; border-radius: var(--radius-sm);">
                                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">settings_suggest</span> Pengaturan Backup
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
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
                        <a href="<?= $baseUrl ?? '' ?>kasir/checkin.php"
                            class="<?= $currentPage === 'checkin.php' && strpos($_SERVER['REQUEST_URI'], '/kasir/') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">qr_code_scanner</span> QR Check-in
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php?tab=profil"
                            class="<?= isset($_GET['tab']) && $_GET['tab'] === 'profil' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">account_circle</span> Profil
                        </a>

                    <?php elseif ($_SESSION['role'] === 'customer'): ?>
                        <a href="<?= $baseUrl ?? '' ?>dashboarduser.php"
                            class="<?= $currentPage === 'dashboarduser.php' && strpos($_SERVER['REQUEST_URI'], 'scroll=riwayat') === false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">dashboard</span> Dashboard
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>booking.php"
                            class="<?= $currentPage === 'booking.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">sports_tennis</span> Booking
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>dashboarduser.php?scroll=riwayat"
                            class="<?= $currentPage === 'dashboarduser.php' && strpos($_SERVER['REQUEST_URI'], 'scroll=riwayat') !== false ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">history</span> Riwayat Booking
                        </a>
                        <a href="<?= $baseUrl ?? '' ?>profil.php"
                            class="<?= $currentPage === 'profil.php' ? 'active' : '' ?>">
                            <span class="material-symbols-outlined">account_circle</span> Pengaturan Profil
                        </a>
                    <?php endif; ?>
                    <button id="theme-toggle-sidebar" class="sidebar-theme-toggle-btn" aria-label="Toggle Theme">
                        <span class="material-symbols-outlined theme-icon">dark_mode</span>
                        <span class="theme-label">Mode Gelap</span>
                    </button>
                    <a href="<?= $baseUrl ?? '' ?>logout.php" class="logout-btn">
                        <span class="material-symbols-outlined">logout</span> Keluar
                    </a>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <main class="dashboard-main">
                <!-- Floating toggle button for admin, cashier and customer mobile sidebar -->
                <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kasir' || $_SESSION['role'] === 'customer'): ?>
                    <button class="sidebar-toggle floating-toggle" id="sidebar-toggle-admin" aria-label="Toggle Sidebar" style="display:none; position:fixed; top:16px; right:16px; z-index:1100; width:44px; height:44px; border-radius:50%; background:var(--navy); color:#fff; border:1px solid rgba(255,255,255,0.15); box-shadow:var(--shadow-md); align-items:center; justify-content:center; cursor:pointer;">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                <?php endif; ?>

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
                        </ul>

                        <div class="nav-actions">
                            <button id="theme-toggle" class="theme-toggle-btn" aria-label="Toggle Theme">
                                <span class="material-symbols-outlined theme-icon">dark_mode</span>
                            </button>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <?php if (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'admin') === 0): ?>
                                    <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php" class="btn-pill">Dashboard Admin</a>
                                <?php elseif (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'kasir') === 0): ?>
                                    <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php" class="btn-pill">Dashboard Kasir</a>
                                <?php else: ?>
                                    <a href="<?= $baseUrl ?? '' ?>dashboarduser.php" class="btn-pill">Dashboard Saya</a>
                                    <a href="<?= $baseUrl ?? '' ?>booking.php" class="btn-pill primary" style="background:var(--blue); border-color:var(--blue); color:#fff; box-shadow:none; margin-right:8px;">Booking</a>
                                <?php endif; ?>
                                <a href="<?= $baseUrl ?? '' ?>logout.php" class="btn-pill btn-logout-danger">Keluar</a>
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
                            <?php if (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'admin') === 0): ?>
                                <a href="<?= $baseUrl ?? '' ?>admin/dashboard.php"
                                    class="<?= $currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">dashboard</span> Dashboard Admin
                                </a>
                            <?php elseif (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'kasir') === 0): ?>
                                <a href="<?= $baseUrl ?? '' ?>kasir/dashboard.php"
                                    class="<?= $currentPage === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], '/kasir/') !== false ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">dashboard</span> Dashboard Kasir
                                </a>
                            <?php else: ?>
                                <a href="<?= $baseUrl ?? '' ?>dashboarduser.php"
                                    class="<?= $currentPage === 'dashboarduser.php' && strpos($_SERVER['REQUEST_URI'], 'scroll=riwayat') === false ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">dashboard</span> Dashboard
                                </a>
                                <a href="<?= $baseUrl ?? '' ?>booking.php"
                                    class="<?= $currentPage === 'booking.php' ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">sports_tennis</span> Booking
                                </a>
                                <a href="<?= $baseUrl ?? '' ?>dashboarduser.php?scroll=riwayat"
                                    class="<?= $currentPage === 'dashboarduser.php' && strpos($_SERVER['REQUEST_URI'], 'scroll=riwayat') !== false ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">history</span> Riwayat Booking
                                </a>
                                <a href="<?= $baseUrl ?? '' ?>profil.php"
                                    class="<?= $currentPage === 'profil.php' ? 'active' : '' ?>">
                                    <span class="material-symbols-outlined">account_circle</span> Pengaturan Profil
                                </a>
                            <?php endif; ?>
                            <div class="mobile-nav-divider"></div>
                            <div class="mobile-nav-actions">
                                <a href="<?= $baseUrl ?? '' ?>logout.php" class="btn btn-logout-danger" style="width:100%;">Keluar</a>
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
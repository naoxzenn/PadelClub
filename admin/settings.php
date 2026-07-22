<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Pengaturan Sistem';
$baseUrl = '../';
$msg = '';

// Path for configurations mock storage
$configFile = __DIR__ . '/../config/settings.json';
if (!file_exists($configFile)) {
    $defaultSettings = [
        'club_name' => 'PadelClub Premium',
        'contact_phone' => '081234567890',
        'address' => 'Jl. Padel Court No. 45, Jakarta',
        'operational_start' => '07:00',
        'operational_end' => '22:00',
        'court_price_factor' => '1.0'
    ];
    file_put_contents($configFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
}

$settings = json_decode(file_get_contents($configFile), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settings['club_name'] = trim($_POST['club_name']);
    $settings['contact_phone'] = trim($_POST['contact_phone']);
    $settings['address'] = trim($_POST['address']);
    $settings['operational_start'] = $_POST['operational_start'];
    $settings['operational_end'] = $_POST['operational_end'];
    $settings['court_price_factor'] = (float)$_POST['court_price_factor'];
    
    file_put_contents($configFile, json_encode($settings, JSON_PRETTY_PRINT));
    $msg = 'Pengaturan sistem berhasil disimpan.';
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <?php if ($msg): ?>
            <div class="alert alert-success" style="margin-bottom: 24px;"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Pengaturan Sistem</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Konfigurasi informasi klub padel, jam operasional sewa, dan parameter sistem reservasi.</p>
        </div>

        <div class="card" style="max-width: 100%; padding: 32px; margin-bottom: 32px;">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 24px;">Konfigurasi Umum</h2>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="club_name">Nama Klub Padel</label>
                        <input type="text" id="club_name" name="club_name" value="<?= htmlspecialchars($settings['club_name']) ?>" required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">Telepon Kontak</label>
                        <input type="text" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone']) ?>" required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Alamat Klub</label>
                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($settings['address']) ?>" required style="padding: 10px 12px; font-size: 0.88rem;">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="operational_start">Jam Buka Operasional</label>
                        <input type="time" id="operational_start" name="operational_start" value="<?= htmlspecialchars($settings['operational_start']) ?>" required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="operational_end">Jam Tutup Operasional</label>
                        <input type="time" id="operational_end" name="operational_end" value="<?= htmlspecialchars($settings['operational_end']) ?>" required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 28px;">
                    <label for="court_price_factor">Multiplier Harga Lapangan</label>
                    <input type="number" step="0.1" min="0.5" max="3.0" id="court_price_factor" name="court_price_factor" value="<?= htmlspecialchars($settings['court_price_factor']) ?>" required style="padding: 10px 12px; font-size: 0.88rem;">
                    <small style="color:var(--text-muted); display:block; margin-top:6px;">Faktor pengali tarif dasar lapangan (default: 1.0). Berguna saat memberlakukan tarif libur/peak-season.</small>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 0.9rem;">
                    <span class="material-symbols-outlined" style="font-size:1.15rem; vertical-align:middle; margin-right:4px;">save</span> Simpan Pengaturan
                </button>
            </form>
        </div>

        <!-- SECTION: MANAJEMEN DATABASE -->
        <style>
            .db-management-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin-bottom: 40px;
            }

            @media (max-width: 768px) {
                .db-management-grid {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }
            }

            .db-card {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 24px;
                margin-bottom: 0;
                border-radius: var(--radius-lg);
                background: var(--card-bg);
                border: 1px solid var(--border);
                box-shadow: var(--shadow-sm);
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            }

            .db-card:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
                border-color: var(--blue);
            }

            .db-card-icon {
                width: 48px;
                height: 48px;
                min-width: 48px;
                min-height: 48px;
                border-radius: var(--radius-md, 12px);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .db-card-icon span {
                font-size: 1.6rem;
            }

            .db-card-content {
                flex: 1;
                min-width: 0;
            }

            .db-card-content h3 {
                font-size: 1.05rem;
                font-weight: 700;
                color: var(--navy);
                margin: 0 0 4px 0;
            }

            .db-card-content p {
                font-size: 0.85rem;
                color: var(--text-muted);
                margin: 0;
                line-height: 1.4;
            }

            .btn-db-action {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                font-size: 0.85rem;
                white-space: nowrap;
                text-decoration: none;
                flex-shrink: 0;
            }
        </style>

        <!-- SECTION: JADWAL & OPERASIONAL VENUE -->
        <div style="margin-top: 12px; margin-bottom: 24px;">
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Jadwal &amp; Operasional Venue</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Kelola jam buka-tutup harian dan daftar hari libur venue untuk validasi reservasi.</p>
        </div>

        <div class="db-management-grid" style="margin-bottom: 32px;">
            <!-- 1. Jam Operasional -->
            <div class="card db-card">
                <div class="db-card-icon icon-blue">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
                <div class="db-card-content">
                    <h3>Jam Operasional</h3>
                    <p>Kelola jam buka dan tutup venue per hari.</p>
                </div>
                <a href="operating_hours.php" class="btn btn-primary btn-db-action">
                    <span class="material-symbols-outlined">tune</span> Kelola
                </a>
            </div>

            <!-- 2. Hari Libur -->
            <div class="card db-card">
                <div class="db-card-icon icon-amber">
                    <span class="material-symbols-outlined">event_busy</span>
                </div>
                <div class="db-card-content">
                    <h3>Hari Libur</h3>
                    <p>Kelola tanggal venue tutup / hari libur.</p>
                </div>
                <a href="holidays.php" class="btn btn-primary btn-db-action">
                    <span class="material-symbols-outlined">tune</span> Kelola
                </a>
            </div>
        </div>

        <div style="margin-top: 12px; margin-bottom: 24px;">
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Manajemen Database</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Kelola proses backup, restore, export, serta konfigurasi database aplikasi.</p>
        </div>

        <div class="db-management-grid">
            <!-- 1. Backup Database -->
            <div class="card db-card">
                <div class="db-card-icon icon-blue">
                    <span class="material-symbols-outlined">backup</span>
                </div>
                <div class="db-card-content">
                    <h3>Backup Database</h3>
                    <p>Membuat cadangan database secara manual.</p>
                </div>
                <a href="backup.php" class="btn btn-outline btn-db-action">
                    <span class="material-symbols-outlined">open_in_new</span> Buka
                </a>
            </div>

            <!-- 2. Restore Database -->
            <div class="card db-card">
                <div class="db-card-icon icon-amber">
                    <span class="material-symbols-outlined">settings_backup_restore</span>
                </div>
                <div class="db-card-content">
                    <h3>Restore Database</h3>
                    <p>Mengembalikan database dari file backup.</p>
                </div>
                <a href="restore.php" class="btn btn-outline btn-db-action">
                    <span class="material-symbols-outlined">open_in_new</span> Buka
                </a>
            </div>

            <!-- 3. Export Data -->
            <div class="card db-card">
                <div class="db-card-icon icon-green">
                    <span class="material-symbols-outlined">download</span>
                </div>
                <div class="db-card-content">
                    <h3>Export Data</h3>
                    <p>Export data ke Excel atau format lain.</p>
                </div>
                <a href="export.php" class="btn btn-outline btn-db-action">
                    <span class="material-symbols-outlined">open_in_new</span> Buka
                </a>
            </div>

            <!-- 4. Log Backup -->
            <div class="card db-card">
                <div class="db-card-icon icon-purple">
                    <span class="material-symbols-outlined">history</span>
                </div>
                <div class="db-card-content">
                    <h3>Log Backup</h3>
                    <p>Melihat riwayat backup database.</p>
                </div>
                <a href="backup_logs.php" class="btn btn-outline btn-db-action">
                    <span class="material-symbols-outlined">open_in_new</span> Buka
                </a>
            </div>

            <!-- 5. Pengaturan Backup -->
            <div class="card db-card">
                <div class="db-card-icon icon-blue">
                    <span class="material-symbols-outlined">settings_suggest</span>
                </div>
                <div class="db-card-content">
                    <h3>Pengaturan Backup</h3>
                    <p>Mengatur konfigurasi backup database.</p>
                </div>
                <a href="backup_settings.php" class="btn btn-outline btn-db-action">
                    <span class="material-symbols-outlined">open_in_new</span> Buka
                </a>
            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

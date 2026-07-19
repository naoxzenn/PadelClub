<?php
// admin/backup_settings.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../helpers/BackupHelper.php';
require_once __DIR__ . '/../models/BackupModel.php';
require_once __DIR__ . '/../controllers/BackupController.php';

$controller = new BackupController();
$msg = '';
$msgType = 'success';

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settings = $controller->getSettings();
    $settings['auto_backup'] = $_POST['auto_backup'] ?? 'off';
    
    if ($controller->saveSettings($settings)) {
        $_SESSION['toast_msg'] = "Pengaturan backup otomatis berhasil disimpan.";
        $_SESSION['toast_type'] = "success";
    } else {
        $_SESSION['toast_msg'] = "Gagal menyimpan pengaturan.";
        $_SESSION['toast_type'] = "error";
    }
    header('Location: backup_settings.php');
    exit;
}

$settings = $controller->getSettings();
$pageTitle = 'Pengaturan Backup';
$baseUrl = '../';

include __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <!-- Header -->
        <div style="margin-bottom: 28px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Pengaturan Backup</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Konfigurasi mekanisme pencadangan otomatis untuk meminimalisir risiko kehilangan data transaksi penting.</p>
        </div>

        <div class="admin-grid-layout">
            <!-- Settings Form Card -->
            <div class="card" style="padding: 32px;">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Konfigurasi Jadwal</h2>
                
                <form method="POST" action="backup_settings.php" style="margin: 0;">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="auto_backup">Frekuensi Backup Otomatis</label>
                        <select id="auto_backup" name="auto_backup" style="padding: 12px; font-size: 0.88rem;">
                            <option value="off" <?= $settings['auto_backup'] === 'off' ? 'selected' : '' ?>>Nonaktif (OFF)</option>
                            <option value="daily" <?= $settings['auto_backup'] === 'daily' ? 'selected' : '' ?>>Setiap Hari (Harian)</option>
                            <option value="weekly" <?= $settings['auto_backup'] === 'weekly' ? 'selected' : '' ?>>Setiap Minggu (Mingguan)</option>
                            <option value="monthly" <?= $settings['auto_backup'] === 'monthly' ? 'selected' : '' ?>>Setiap Bulan (Bulanan)</option>
                        </select>
                        <small style="color: var(--text-muted); display: block; margin-top: 8px; line-height: 1.4;">
                            Pilih seberapa sering sistem akan mencadangkan database. Frekuensi yang direkomendasikan adalah Harian atau Mingguan tergantung pada volume pemesanan Anda.
                        </small>
                    </div>

                    <div style="background: var(--surface-alt); border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--border); margin-bottom: 28px; font-size: 0.85rem; color: var(--text-muted);">
                        <div style="display:flex; align-items:center; gap:8px; color:var(--blue); font-weight:700; margin-bottom:6px;">
                            <span class="material-symbols-outlined" style="font-size: 1.15rem;">info</span> Informasi Sinkronisasi
                        </div>
                        Waktu eksekusi terakhir: <strong><?= $settings['last_run'] ? date('d M Y H:i:s', strtotime($settings['last_run'])) : 'Belum pernah dijalankan otomatis.' ?></strong>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; font-weight: 700;">
                        <span class="material-symbols-outlined">save</span> Simpan Pengaturan
                    </button>
                </form>
            </div>

            <!-- Server Integration Guide Card -->
            <div class="card" style="padding: 32px; background: var(--surface);">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 16px; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="color: var(--blue);">terminal</span> Panduan Cron Job
                </h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.6; margin-bottom: 16px;">
                    Untuk mengaktifkan backup otomatis di hosting / server produksi Linux (seperti cPanel), tambahkan perintah cron job berikut di panel kontrol Anda:
                </p>
                <div style="background: var(--navy); color: #a5f3fc; font-family: monospace; font-size: 0.78rem; padding: 12px 16px; border-radius: 6px; overflow-x: auto; margin-bottom: 16px; line-height: 1.4;">
                    0 0 * * * php <?= realpath(__DIR__ . '/../cron_backup.php') ?> > /dev/null 2>&1
                </div>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.6;">
                    Cron job di atas disetel untuk mengeksekusi script cadangan setiap hari pada jam 00:00. Pastikan path file PHP mengarah ke direktori root project yang tepat.
                </p>
            </div>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($_SESSION['toast_msg'])): ?>
        showToast(<?= json_encode($_SESSION['toast_msg']) ?>, <?= json_encode($_SESSION['toast_type'] ?? 'success') ?>);
        <?php 
            unset($_SESSION['toast_msg']); 
            unset($_SESSION['toast_type']); 
        ?>
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

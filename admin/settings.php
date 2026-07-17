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

        <div class="card" style="max-width: 720px; padding: 32px;">
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

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

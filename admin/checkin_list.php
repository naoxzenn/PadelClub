<?php
// admin/checkin_list.php - Manajemen Check-in Petugas

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'], true)) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

$pageTitle = 'Kelola Check In';
$baseUrl = '../';
$msg = '';
$err = '';

$model = new BookingModel($pdo);

// Handle POST to check-in manually from list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_checkin') {
    $code = $_POST['booking_code'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $petugas_id = $_SESSION['user_id'];

    if (!empty($code)) {
        $success = $model->checkin($code, $ip, $browser, $petugas_id);
        if ($success) {
            $msg = 'Check-in manual untuk booking #' . htmlspecialchars($code) . ' berhasil!';
        } else {
            $err = 'Gagal memproses check-in manual.';
        }
    }
}

// Get Filters
$dateFilter = $_GET['date_filter'] ?? 'today';
$statusFilter = $_GET['status_filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Load Stats
$stats = $model->getCheckinStats();

// Load List
$bookings = $model->getCheckinList($dateFilter, $statusFilter, $search);

include_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Manajemen Check In Pelanggan</h1>
        <p>Pantau kedatangan dan validasi tiket digital QR Code pemain hari ini</p>
    </div>
</section>

<section class="section">
    <div class="container">
        
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($err)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="summary-grid" style="margin-bottom: 32px;">
            <div class="summary-box">
                <div class="number"><?= $stats['total_today'] ?></div>
                <div class="label">Total Booking Hari Ini</div>
            </div>
            <div class="summary-box">
                <div class="number" style="color:var(--green);"><?= $stats['checked_today'] ?></div>
                <div class="label">Sudah Check In</div>
            </div>
            <div class="summary-box">
                <div class="number" style="color:#F59E0B;"><?= $stats['unchecked_today'] ?></div>
                <div class="label">Belum Check In</div>
            </div>
            <div class="summary-box">
                <div class="number"><?= $stats['attendance_rate'] ?>%</div>
                <div class="label">Persentase Kehadiran</div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="card" style="padding: 24px; margin-bottom: 24px; border-radius: var(--radius-lg);">
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 16px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="date_filter" style="font-size:0.7rem; font-weight:700;">Filter Tanggal</label>
                    <select id="date_filter" name="date_filter" style="padding: 10px; font-size: 0.88rem;">
                        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Hari Ini (Today)</option>
                        <option value="tomorrow" <?= $dateFilter === 'tomorrow' ? 'selected' : '' ?>>Besok (Tomorrow)</option>
                        <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>Semua Tanggal</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status_filter" style="font-size:0.7rem; font-weight:700;">Status Check-in</label>
                    <select id="status_filter" name="status_filter" style="padding: 10px; font-size: 0.88rem;">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="checked_in" <?= $statusFilter === 'checked_in' ? 'selected' : '' ?>>Sudah Check In</option>
                        <option value="not_checked_in" <?= $statusFilter === 'not_checked_in' ? 'selected' : '' ?>>Belum Check In</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search" style="font-size:0.7rem; font-weight:700;">Cari Data</label>
                    <input type="text" id="search" name="search" placeholder="Kode, Pelanggan, Lapangan..." value="<?= htmlspecialchars($search) ?>" style="padding: 10px; font-size: 0.88rem;">
                </div>

                <button type="submit" class="btn btn-primary" style="height: 44px; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                    <span class="material-symbols-outlined">search</span> Cari
                </button>
            </form>
        </div>

        <!-- Bookings List Table -->
        <div class="card" style="border-radius: var(--radius-lg); padding: 28px;">
            <h2>Daftar Kehadiran Bermain</h2>
            <?php if (empty($bookings)): ?>
                <div class="alert alert-info" style="margin-bottom:0;">
                    Tidak ada data pemesanan terkonfirmasi yang cocok dengan filter pencarian ini.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table style="width:100%; border-collapse:collapse; margin-top:10px;">
                        <thead>
                            <tr style="border-bottom:2px solid var(--border);">
                                <th style="padding:12px 8px; text-align:left;">Pelanggan</th>
                                <th style="padding:12px 8px; text-align:left;">Lapangan</th>
                                <th style="padding:12px 8px; text-align:left;">Jadwal Bermain</th>
                                <th style="padding:12px 8px; text-align:left;">Kode Booking</th>
                                <th style="padding:12px 8px; text-align:left;">Status Check-in</th>
                                <th style="padding:12px 8px; text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr style="border-bottom:1px solid var(--border);">
                                    <td style="padding:12px 8px;">
                                        <strong><?= htmlspecialchars($b['customer_name']) ?></strong>
                                    </td>
                                    <td style="padding:12px 8px;">
                                        <?= htmlspecialchars($b['nama_lapangan']) ?><br>
                                        <small style="color:var(--text-muted);"><?= $b['tipe_lapangan'] ?></small>
                                    </td>
                                    <td style="padding:12px 8px;">
                                        <?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?><br>
                                        <small style="color:var(--text-muted);"><?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?> WIB</small>
                                    </td>
                                    <td style="padding:12px 8px;">
                                        <code style="font-family:monospace; font-size:0.95rem; font-weight:700; color:var(--blue);"><?= htmlspecialchars($b['booking_code'] ?? '-') ?></code>
                                    </td>
                                    <td style="padding:12px 8px;">
                                        <?php if ($b['checkin_status'] === 'Checked In'): ?>
                                            <span style="color:var(--green); font-weight:700; font-size:0.85rem; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-symbols-outlined" style="font-size:1.1rem;">check_circle</span>
                                                Checked In
                                            </span><br>
                                            <small style="color:var(--text-muted);"><?= date('H:i', strtotime($b['checkin_time'])) ?> WIB</small>
                                        <?php else: ?>
                                            <span style="color:#F59E0B; font-weight:700; font-size:0.85rem; display:inline-flex; align-items:center; gap:4px;">
                                                <span class="material-symbols-outlined" style="font-size:1.1rem;">schedule</span>
                                                Not Checked In
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px 8px; text-align:center;">
                                        <?php if ($b['checkin_status'] !== 'Checked In'): ?>
                                            <div style="display:flex; justify-content:center; gap:8px;">
                                                <!-- Link to validation scan view -->
                                                <a href="../checkin.php?code=<?= $b['booking_code'] ?>" class="btn btn-sm btn-outline" style="display:inline-flex; align-items:center; gap:4px; padding:6px 12px; font-size:0.8rem; border-radius:6px; text-decoration:none;">
                                                    <span class="material-symbols-outlined" style="font-size:1.1rem;">qr_code_scanner</span> Scan View
                                                </a>
                                                <!-- Direct checkin form -->
                                                <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin melakukan check-in manual untuk booking ini?');" style="margin:0;">
                                                    <input type="hidden" name="action" value="manual_checkin">
                                                    <input type="hidden" name="booking_code" value="<?= $b['booking_code'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:4px; padding:6px 12px; font-size:0.8rem; border-radius:6px;">
                                                        <span class="material-symbols-outlined" style="font-size:1.1rem;">check</span> Check In
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-size:0.85rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>

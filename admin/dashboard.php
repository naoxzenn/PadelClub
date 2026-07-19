<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../helpers/BackupHelper.php';
require_once __DIR__ . '/../models/BackupModel.php';
require_once __DIR__ . '/../controllers/BackupController.php';

$backupCtrl = new BackupController();
$backupStats = $backupCtrl->getStats();

$pageTitle = 'Dashboard Admin';
$baseUrl = '../';

// ---- AMBIL DATA STATISTIK ----
// Total Booking
$totalBooking = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings"))[0];

// Booking Hari Ini
$bookingHariIni = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE DATE(tanggal_booking) = CURDATE()"))[0];

// Booking Bulan Ini
$bookingBulanIni = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE MONTH(tanggal_booking) = MONTH(CURDATE()) AND YEAR(tanggal_booking) = YEAR(CURDATE())"))[0];

// Total Customer
$totalCustomer = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'"))[0];

// Total Lapangan
$totalLapangan = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM courts"))[0];

// Pendapatan Hari Ini
$pendapatanHariIni = (float)mysqli_fetch_row(mysqli_query($conn, 
    "SELECT COALESCE(SUM(jumlah_bayar), 0) FROM payments WHERE status_verifikasi = 'terverifikasi' AND DATE(COALESCE(waktu_bayar, payment_date)) = CURDATE()"
))[0];

// Pendapatan Bulan Ini
$pendapatanBulanIni = (float)mysqli_fetch_row(mysqli_query($conn, 
    "SELECT COALESCE(SUM(jumlah_bayar), 0) FROM payments WHERE status_verifikasi = 'terverifikasi' AND MONTH(COALESCE(waktu_bayar, payment_date)) = MONTH(CURDATE()) AND YEAR(COALESCE(waktu_bayar, payment_date)) = YEAR(CURDATE())"
))[0];

// Booking Pending
$bookingPending = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status = 'pending'"))[0];

// Pembayaran Menunggu Verifikasi (Tindakan Admin)
$pembayaranPending = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM payments WHERE status_verifikasi = 'menunggu'"))[0];

// Booking Confirmed
$bookingConfirmed = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'"))[0];

// Booking Cancelled
$bookingCancelled = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'"))[0];

// ---- CHECK-IN STATS HARI INI ----
$todayDate = date('Y-m-d');
$checkinTotal   = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND tanggal_booking = '$todayDate'"))[0];
$checkinHadir   = (int)mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND tanggal_booking = '$todayDate' AND checkin_status = 'Checked In'"))[0];
$checkinBelum   = $checkinTotal - $checkinHadir;
$checkinRate    = $checkinTotal > 0 ? round(($checkinHadir / $checkinTotal) * 100) : 0;
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">
        
        <div style="margin-bottom: 28px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Selamat Datang, <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>!</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Berikut adalah ringkasan performa operasional PadelClub hari ini.</p>
        </div>

        <!-- 10 KPI Statistics Grid -->
        <div class="dashboard-stat-grid">
            <!-- Total Booking -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-blue">
                    <span class="material-symbols-outlined">calendar_month</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $totalBooking ?></span>
                    <span class="stat-card-label">Total Booking</span>
                </div>
            </div>

            <!-- Booking Hari Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-blue">
                    <span class="material-symbols-outlined">today</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingHariIni ?></span>
                    <span class="stat-card-label">Booking Hari Ini</span>
                </div>
            </div>

            <!-- Booking Bulan Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-blue">
                    <span class="material-symbols-outlined">date_range</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingBulanIni ?></span>
                    <span class="stat-card-label">Booking Bulan Ini</span>
                </div>
            </div>

            <!-- Total Customers -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-blue">
                    <span class="material-symbols-outlined">groups</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $totalCustomer ?></span>
                    <span class="stat-card-label">Total Customer</span>
                </div>
            </div>

            <!-- Total Lapangan -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-green">
                    <span class="material-symbols-outlined">sports_tennis</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $totalLapangan ?></span>
                    <span class="stat-card-label">Total Lapangan</span>
                </div>
            </div>

            <!-- Pendapatan Hari Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-green">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size:1.3rem;">Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></span>
                    <span class="stat-card-label">Pendapatan Hari Ini</span>
                </div>
            </div>

            <!-- Pendapatan Bulan Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-green">
                    <span class="material-symbols-outlined">account_balance_wallet</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size:1.3rem;">Rp <?= number_format($pendapatanBulanIni, 0, ',', '.') ?></span>
                    <span class="stat-card-label">Pendapatan Bulan Ini</span>
                </div>
            </div>

            <!-- Pembayaran (Perubahan 2: Menunggu Verifikasi -> Pembayaran) -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-amber">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $pembayaranPending ?></span>
                    <span class="stat-card-label">Pembayaran</span>
                </div>
            </div>

            <!-- Booking Confirmed -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-green">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingConfirmed ?></span>
                    <span class="stat-card-label">Booking Confirmed</span>
                </div>
            </div>

            <!-- Booking Cancelled -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-red">
                    <span class="material-symbols-outlined">cancel</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingCancelled ?></span>
                    <span class="stat-card-label">Booking Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Check-in Stats Hari Ini -->
        <div style="margin-bottom: 28px;">
            <div class="section-header-flex">
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--navy); display:flex; align-items:center; gap:8px; margin:0;">
                    <span class="material-symbols-outlined" style="color: var(--blue); font-size: 1.2rem;">qr_code_scanner</span>
                    Status Kehadiran Hari Ini
                </h3>
                <a href="checkin_list.php" style="font-size: 0.85rem; font-weight: 700; color: var(--blue); display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                    Lihat Semua <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>
                </a>
            </div>
            <div class="status-kehadiran-grid">
                <div class="dashboard-stat-card" style="border-left: 3px solid var(--blue);">
                    <div class="stat-card-icon icon-blue">
                        <span class="material-symbols-outlined">today</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value"><?= $checkinTotal ?></span>
                        <span class="stat-card-label">Booking Hari Ini</span>
                    </div>
                </div>
                <div class="dashboard-stat-card" style="border-left: 3px solid var(--green);">
                    <div class="stat-card-icon icon-green">
                        <span class="material-symbols-outlined">how_to_reg</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value" style="color: var(--green);"><?= $checkinHadir ?></span>
                        <span class="stat-card-label">Sudah Hadir</span>
                    </div>
                </div>
                <div class="dashboard-stat-card" style="border-left: 3px solid #F59E0B;">
                    <div class="stat-card-icon icon-amber">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value" style="color: #D97706;"><?= $checkinBelum ?></span>
                        <span class="stat-card-label">Belum Hadir</span>
                    </div>
                </div>
                <div class="dashboard-stat-card" style="border-left: 3px solid var(--blue-dark);">
                    <div class="stat-card-icon icon-blue">
                        <span class="material-symbols-outlined">percent</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value"><?= $checkinRate ?>%</span>
                        <span class="stat-card-label">Tingkat Kehadiran</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Overview Row -->
        <div style="margin-bottom: 32px;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 16px; display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="color: var(--blue);">cloud_sync</span> Status Cadangan Sistem (Database Backup)
            </h3>
            <div class="dashboard-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <!-- Backup Terakhir -->
                <div class="dashboard-stat-card">
                    <div class="stat-card-icon icon-blue">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value" style="font-size: 1.1rem;"><?= $backupStats['last_backup'] ?></span>
                        <span class="stat-card-label">Backup Terakhir</span>
                    </div>
                </div>

                <!-- Status Backup — dynamic color dari PHP, tetap inline -->
                <?php
                $healthColor = 'var(--green)';
                $healthLabel = 'Sehat (Aktif)';
                $healthIcon = 'check_circle';
                if ($backupStats['health'] === 'yellow') {
                    $healthColor = '#F59E0B';
                    $healthLabel = 'Perlu Backup (> 7 Hari)';
                    $healthIcon = 'warning';
                } elseif ($backupStats['health'] === 'red') {
                    $healthColor = '#EF4444';
                    $healthLabel = 'Sangat Kritis / Gagal';
                    $healthIcon = 'error';
                }
                ?>
                <div class="dashboard-stat-card">
                    <div class="stat-card-icon" style="background: <?= $healthColor ?>15; color: <?= $healthColor ?>;">
                        <span class="material-symbols-outlined"><?= $healthIcon ?></span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value" style="font-size: 1.1rem; color: <?= $healthColor ?>;"><?= $healthLabel ?></span>
                        <span class="stat-card-label">Status Backup</span>
                    </div>
                </div>

                <!-- Jumlah Backup -->
                <div class="dashboard-stat-card">
                    <div class="stat-card-icon icon-blue">
                        <span class="material-symbols-outlined">folder_zip</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value"><?= $backupStats['total_count'] ?> File</span>
                        <span class="stat-card-label">Jumlah File Backup</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: AKTIVITAS CEPAT (Perubahan 3 & Perubahan 1) -->
        <div style="margin-bottom: 32px;">
            <div class="section-header-flex">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--navy); display:flex; align-items:center; gap:8px; margin:0;">
                    <span class="material-symbols-outlined" style="color: var(--blue); font-size: 1.3rem;">bolt</span>
                    Aktivitas Cepat
                </h3>
            </div>
            
            <div class="quick-actions-grid">
                <!-- 1. Lihat Semua Booking (Perubahan 1) -->
                <a href="bookings.php" class="quick-action-card">
                    <div class="quick-action-top">
                        <div class="quick-action-icon icon-blue">
                            <span class="material-symbols-outlined">calendar_month</span>
                        </div>
                        <div class="quick-action-body">
                            <h4>Lihat Booking</h4>
                            <p>Kelola seluruh data booking pelanggan.</p>
                        </div>
                    </div>
                    <div class="quick-action-footer">
                        <span>Lihat Semua Booking</span>
                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_forward</span>
                    </div>
                </a>

                <!-- 2. Lihat Semua Pembayaran -->
                <a href="payments.php" class="quick-action-card">
                    <div class="quick-action-top">
                        <div class="quick-action-icon icon-green">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <div class="quick-action-body">
                            <h4>Lihat Semua Pembayaran</h4>
                            <p>Verifikasi bukti bayar & histori transaksi masuk.</p>
                        </div>
                    </div>
                    <div class="quick-action-footer">
                        <span>Kelola Pembayaran</span>
                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_forward</span>
                    </div>
                </a>

                <!-- 3. Kelola Lapangan -->
                <a href="courts.php" class="quick-action-card">
                    <div class="quick-action-top">
                        <div class="quick-action-icon icon-purple">
                            <span class="material-symbols-outlined">sports_tennis</span>
                        </div>
                        <div class="quick-action-body">
                            <h4>Kelola Lapangan</h4>
                            <p>Atur daftar lapangan, harga sewa, & fasilitas.</p>
                        </div>
                    </div>
                    <div class="quick-action-footer">
                        <span>Atur Lapangan</span>
                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_forward</span>
                    </div>
                </a>

                <!-- 4. Laporan -->
                <a href="reports.php" class="quick-action-card">
                    <div class="quick-action-top">
                        <div class="quick-action-icon icon-amber">
                            <span class="material-symbols-outlined">analytics</span>
                        </div>
                        <div class="quick-action-body">
                            <h4>Laporan Keuangan</h4>
                            <p>Analisis pendapatan, okupansi & tren booking.</p>
                        </div>
                    </div>
                    <div class="quick-action-footer">
                        <span>Lihat Laporan</span>
                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_forward</span>
                    </div>
                </a>

                <!-- 5. Backup Database -->
                <a href="backup.php" class="quick-action-card">
                    <div class="quick-action-top">
                        <div class="quick-action-icon icon-blue">
                            <span class="material-symbols-outlined">cloud_sync</span>
                        </div>
                        <div class="quick-action-body">
                            <h4>Backup Database</h4>
                            <p>Pencadangan data otomatis & restore sistem.</p>
                        </div>
                    </div>
                    <div class="quick-action-footer">
                        <span>Kelola Backup</span>
                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_forward</span>
                    </div>
                </a>
            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
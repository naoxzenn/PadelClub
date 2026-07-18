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


// ---- AMBIL DATA RINGKASAN ----
// 5 Booking Terbaru
$recentBookings = mysqli_fetch_all(mysqli_query($conn, "
    SELECT b.*, c.nama_lapangan, u.nama_lengkap, u.email
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 5
"), MYSQLI_ASSOC);

// 5 Pembayaran Menunggu Verifikasi
$recentPayments = mysqli_fetch_all(mysqli_query($conn, "
    SELECT p.*, u.nama_lengkap, c.nama_lapangan, b.tanggal_booking
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    WHERE p.status_verifikasi = 'menunggu'
    ORDER BY COALESCE(p.waktu_bayar, p.payment_date) DESC
    LIMIT 5
"), MYSQLI_ASSOC);
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
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">calendar_month</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $totalBooking ?></span>
                    <span class="stat-card-label">Total Booking</span>
                </div>
            </div>

            <!-- Booking Hari Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">today</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingHariIni ?></span>
                    <span class="stat-card-label">Booking Hari Ini</span>
                </div>
            </div>

            <!-- Booking Bulan Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">date_range</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingBulanIni ?></span>
                    <span class="stat-card-label">Booking Bulan Ini</span>
                </div>
            </div>

            <!-- Total Customers -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.08); color: #3B82F6;">
                    <span class="material-symbols-outlined">groups</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $totalCustomer ?></span>
                    <span class="stat-card-label">Total Customer</span>
                </div>
            </div>

            <!-- Total Lapangan -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.08); color: var(--green);">
                    <span class="material-symbols-outlined">sports_tennis</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $totalLapangan ?></span>
                    <span class="stat-card-label">Total Lapangan</span>
                </div>
            </div>

            <!-- Pendapatan Hari Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.08); color: var(--green);">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size:1.3rem;">Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></span>
                    <span class="stat-card-label">Pendapatan Hari Ini</span>
                </div>
            </div>

            <!-- Pendapatan Bulan Ini -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.08); color: var(--green);">
                    <span class="material-symbols-outlined">account_balance_wallet</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size:1.3rem;">Rp <?= number_format($pendapatanBulanIni, 0, ',', '.') ?></span>
                    <span class="stat-card-label">Pendapatan Bulan Ini</span>
                </div>
            </div>

            <!-- Booking Pending -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(245, 158, 11, 0.08); color: #F59E0B;">
                    <span class="material-symbols-outlined">hourglass_empty</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingPending ?></span>
                    <span class="stat-card-label">Booking Pending</span>
                </div>
            </div>

            <!-- Booking Confirmed -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.08); color: var(--green);">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingConfirmed ?></span>
                    <span class="stat-card-label">Booking Confirmed</span>
                </div>
            </div>

            <!-- Booking Cancelled -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(239, 68, 68, 0.08); color: #EF4444;">
                    <span class="material-symbols-outlined">cancel</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingCancelled ?></span>
                    <span class="stat-card-label">Booking Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Check-in Stats Hari Ini -->
        <div style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <h3 style="font-size: 1rem; font-weight: 800; color: var(--navy); display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="color: var(--blue); font-size: 1.2rem;">qr_code_scanner</span>
                    Status Kehadiran Hari Ini
                </h3>
                <a href="checkin_list.php" style="font-size: 0.82rem; font-weight: 700; color: var(--blue); display: inline-flex; align-items: center; gap: 4px;">
                    Lihat Semua <span class="material-symbols-outlined" style="font-size:1rem;">arrow_forward</span>
                </a>
            </div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                <div class="dashboard-stat-card" style="border-left: 3px solid var(--blue);">
                    <div class="stat-card-icon" style="background: rgba(14,165,233,0.08); color: var(--blue);">
                        <span class="material-symbols-outlined">today</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value"><?= $checkinTotal ?></span>
                        <span class="stat-card-label">Booking Hari Ini</span>
                    </div>
                </div>
                <div class="dashboard-stat-card" style="border-left: 3px solid var(--green);">
                    <div class="stat-card-icon" style="background: rgba(34,197,94,0.08); color: var(--green);">
                        <span class="material-symbols-outlined">how_to_reg</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value" style="color: var(--green);"><?= $checkinHadir ?></span>
                        <span class="stat-card-label">Sudah Hadir</span>
                    </div>
                </div>
                <div class="dashboard-stat-card" style="border-left: 3px solid #F59E0B;">
                    <div class="stat-card-icon" style="background: rgba(245,158,11,0.08); color: #F59E0B;">
                        <span class="material-symbols-outlined">pending_actions</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value" style="color: #D97706;"><?= $checkinBelum ?></span>
                        <span class="stat-card-label">Belum Hadir</span>
                    </div>
                </div>
                <div class="dashboard-stat-card" style="border-left: 3px solid var(--blue-dark);">
                    <div class="stat-card-icon" style="background: rgba(14,165,233,0.08); color: var(--blue-dark);">
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
                    <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value" style="font-size: 1.1rem;"><?= $backupStats['last_backup'] ?></span>
                        <span class="stat-card-label">Backup Terakhir</span>
                    </div>
                </div>

                <!-- Status Backup -->
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
                    <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                        <span class="material-symbols-outlined">folder_zip</span>
                    </div>
                    <div class="stat-card-info">
                        <span class="stat-card-value"><?= $backupStats['total_count'] ?> File</span>
                        <span class="stat-card-label">Jumlah File Backup</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities Grid -->
        <div class="admin-grid-activities">
            
            <!-- Recent Bookings Table -->
            <div class="card" style="margin: 0; padding: 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin:0;">5 Booking Terbaru</h2>
                    <a href="bookings.php" style="font-size:0.85rem; color:var(--blue); font-weight:600; text-decoration:none; display:flex; align-items:center; gap:4px;">
                        Lihat Semua <span class="material-symbols-outlined" style="font-size:1.1rem;">arrow_forward</span>
                    </a>
                </div>
                <div class="table-responsive">
                    <table style="font-size: 0.82rem; min-width:unset; width:100%;">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Customer</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td>#<?= $b['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['nama_lengkap']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($b['nama_lapangan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?></td>
                                    <td>
                                        <?php if ($b['status'] === 'cancelled'): ?>
                                            <span class="status-cancelled" style="padding:2px 8px; font-size:.7rem;">Dibatalkan</span>
                                        <?php elseif ($b['status'] === 'confirmed'): ?>
                                            <span class="status-confirmed" style="padding:2px 8px; font-size:.7rem;">Confirmed</span>
                                        <?php else: ?>
                                            <span class="status-pending" style="padding:2px 8px; font-size:.7rem;">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentBookings)): ?>
                                <tr><td colspan="5" style="text-align:center; color:#aaa; padding:12px;">Belum ada booking terbaru.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Payments Table -->
            <div class="card" style="margin: 0; padding: 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin:0;">Menunggu Verifikasi Pembayaran</h2>
                    <a href="payments.php" style="font-size:0.85rem; color:var(--blue); font-weight:600; text-decoration:none; display:flex; align-items:center; gap:4px;">
                        Lihat Semua <span class="material-symbols-outlined" style="font-size:1.1rem;">arrow_forward</span>
                    </a>
                </div>
                <div class="table-responsive">
                    <table style="font-size: 0.82rem; min-width:unset; width:100%;">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>Customer</th>
                                <th>Lapangan</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $p): ?>
                                <tr>
                                    <td>#<?= $p['booking_id'] ?></td>
                                    <td><strong><?= htmlspecialchars($p['nama_lengkap']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['nama_lapangan']) ?></td>
                                    <td>Rp <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></td>
                                    <td>
                                        <a href="payments.php" class="btn btn-sm btn-primary" style="padding: 4px 10px; font-size: 0.72rem; border-radius: 6px;">Verifikasi</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentPayments)): ?>
                                <tr><td colspan="5" style="text-align:center; color:#aaa; padding:12px;">Semua pembayaran terverifikasi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
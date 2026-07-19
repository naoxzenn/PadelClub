<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Laporan Keuangan & Booking';
$baseUrl = '../';

// Filter date range
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch summary stats in range
$stmt = mysqli_prepare($conn, "
    SELECT 
        COUNT(*) as total_booking,
        SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed_booking,
        SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled_booking,
        SUM(total_harga) as gross_revenue
    FROM bookings
    WHERE tanggal_booking BETWEEN ? AND ?
");
$stats = ['total_booking' => 0, 'confirmed_booking' => 0, 'cancelled_booking' => 0, 'gross_revenue' => 0];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ss', $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $stats = $row;
    }
    mysqli_stmt_close($stmt);
}

// Fetch verified payments in range
$stmt = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(jumlah_bayar), 0) as net_revenue
    FROM payments
    WHERE status_verifikasi = 'terverifikasi' AND DATE(waktu_bayar) BETWEEN ? AND ?
");
$net_revenue = 0.0;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ss', $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $net_revenue);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// Fetch records in range
$stmt = mysqli_prepare($conn, "
    SELECT b.*, c.nama_lapangan, u.nama_lengkap
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    JOIN users u ON b.user_id = u.id
    WHERE b.tanggal_booking BETWEEN ? AND ?
    ORDER BY b.tanggal_booking DESC
");
$bookings = [];
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ss', $startDate, $endDate);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $bookings = mysqli_fetch_all($res, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <div style="margin-bottom: 24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Laporan Ringkasan</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Analisa performa finansial dan total pemesanan lapangan berdasarkan rentang tanggal.</p>
            </div>
            <button onclick="window.print()" class="btn btn-secondary" style="padding: 10px 16px; font-size: 0.85rem; height: 42px; display:inline-flex; align-items:center; gap:6px;">
                <span class="material-symbols-outlined" style="font-size:1.15rem;">print</span> Cetak Laporan
            </button>
        </div>

        <!-- Date Range Filter Card -->
        <div class="card" style="padding: 20px; margin-bottom: 24px;">
            <form method="GET" class="admin-filters-grid">
                <div class="form-group" style="margin:0;">
                    <label for="start_date" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" style="padding: 9px 12px; font-size: 0.88rem;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="end_date" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Tanggal Selesai</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" style="padding: 9px 12px; font-size: 0.88rem;">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 16px; font-size: 0.85rem; height: 42px;">
                        Tampilkan Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Stats Overview Row -->
        <div class="dashboard-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px;">
            
            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-blue">
                    <span class="material-symbols-outlined">calendar_month</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= (int)$stats['total_booking'] ?></span>
                    <span class="stat-card-label">Total Booking</span>
                </div>
            </div>

            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-green">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= (int)$stats['confirmed_booking'] ?></span>
                    <span class="stat-card-label">Confirmed</span>
                </div>
            </div>

            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-red">
                    <span class="material-symbols-outlined">cancel</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= (int)$stats['cancelled_booking'] ?></span>
                    <span class="stat-card-label">Cancelled</span>
                </div>
            </div>

            <div class="dashboard-stat-card">
                <div class="stat-card-icon icon-green">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size:1.3rem;">Rp <?= number_format($net_revenue, 0, ',', '.') ?></span>
                    <span class="stat-card-label">Pendapatan Bersih</span>
                </div>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="card" style="padding: 24px;">
            <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Rincian Transaksi Booking</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Booking</th>
                            <th>Customer</th>
                            <th>Lapangan</th>
                            <th>Tanggal Main</th>
                            <th>Jam</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>#<?= $b['id'] ?></td>
                                <td><strong><?= htmlspecialchars($b['nama_lengkap']) ?></strong></td>
                                <td><?= htmlspecialchars($b['nama_lapangan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?></td>
                                <td><?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?></td>
                                <td>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($b['status'] === 'confirmed'): ?>
                                        <span class="status-confirmed">Confirmed</span>
                                    <?php elseif ($b['status'] === 'cancelled'): ?>
                                        <span class="status-cancelled">Cancelled</span>
                                    <?php else: ?>
                                        <span class="status-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="7" style="text-align:center; color:#aaa; padding:24px;">Tidak ada transaksi dalam rentang tanggal ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

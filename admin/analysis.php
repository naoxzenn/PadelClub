<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Analisa Data Penyewaan';
$baseUrl = '../';

// ---- QUERY DATA UNTUK VISUALISASI ----

// 1. Line Chart: Booking per hari (30 hari terakhir)
$res_daily = mysqli_query($conn, "
    SELECT tanggal_booking, COUNT(*) as count 
    FROM bookings 
    WHERE tanggal_booking >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY tanggal_booking 
    ORDER BY tanggal_booking ASC
");
$daily_labels = [];
$daily_data = [];
while ($row = mysqli_fetch_assoc($res_daily)) {
    $daily_labels[] = date('d M', strtotime($row['tanggal_booking']));
    $daily_data[] = (int)$row['count'];
}

// 2. Bar Chart: Booking per lapangan
$res_court = mysqli_query($conn, "
    SELECT c.nama_lapangan, COUNT(b.id) as count 
    FROM courts c 
    LEFT JOIN bookings b ON b.court_id = c.id 
    GROUP BY c.id 
    ORDER BY count DESC
");
$court_labels = [];
$court_data = [];
while ($row = mysqli_fetch_assoc($res_court)) {
    $court_labels[] = $row['nama_lapangan'];
    $court_data[] = (int)$row['count'];
}

// 3. Booking per bulan (12 bulan terakhir)
$res_monthly = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal_booking, '%M %Y') as month, COUNT(*) as count 
    FROM bookings 
    GROUP BY DATE_FORMAT(tanggal_booking, '%Y-%m') 
    ORDER BY tanggal_booking ASC 
    LIMIT 12
");
$monthly_labels = [];
$monthly_data = [];
while ($row = mysqli_fetch_assoc($res_monthly)) {
    $monthly_labels[] = $row['month'];
    $monthly_data[] = (int)$row['count'];
}

// 4. Booking per jam
$res_hourly = mysqli_query($conn, "
    SELECT HOUR(jam_mulai) as hour, COUNT(*) as count 
    FROM bookings 
    GROUP BY HOUR(jam_mulai) 
    ORDER BY hour ASC
");
$hourly_labels = [];
$hourly_data = [];
while ($row = mysqli_fetch_assoc($res_hourly)) {
    $hourly_labels[] = sprintf('%02d:00', $row['hour']);
    $hourly_data[] = (int)$row['count'];
}

// 5. Booking berdasarkan status (Doughnut Chart)
$res_status = mysqli_query($conn, "
    SELECT status, COUNT(*) as count 
    FROM bookings 
    GROUP BY status
");
$status_labels = [];
$status_data = [];
while ($row = mysqli_fetch_assoc($res_status)) {
    $status_labels[] = ucfirst($row['status']);
    $status_data[] = (int)$row['count'];
}

// 6. Pendapatan bulanan
$res_revenue = mysqli_query($conn, "
    SELECT DATE_FORMAT(waktu_bayar, '%M %Y') as month, SUM(jumlah_bayar) as revenue 
    FROM payments 
    WHERE status_verifikasi = 'terverifikasi' 
    GROUP BY DATE_FORMAT(waktu_bayar, '%Y-%m') 
    ORDER BY waktu_bayar ASC 
    LIMIT 12
");
$revenue_labels = [];
$revenue_data = [];
while ($row = mysqli_fetch_assoc($res_revenue)) {
    $revenue_labels[] = $row['month'];
    $revenue_data[] = (float)$row['revenue'];
}

// 7. Lapangan Paling Populer
$pop_court = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT c.nama_lapangan, COUNT(b.id) as count 
    FROM courts c 
    JOIN bookings b ON b.court_id = c.id 
    GROUP BY c.id 
    ORDER BY count DESC 
    LIMIT 1
"));

// 8. Jam Paling Ramai
$pop_hour = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT HOUR(jam_mulai) as hour, COUNT(*) as count 
    FROM bookings 
    GROUP BY HOUR(jam_mulai) 
    ORDER BY count DESC 
    LIMIT 1
"));

// 9. Top Customer
$top_cust = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT u.nama_lengkap, u.email, COUNT(b.id) as count, SUM(b.total_harga) as total_spent
    FROM users u 
    JOIN bookings b ON b.user_id = u.id 
    GROUP BY u.id 
    ORDER BY count DESC 
    LIMIT 1
"));

// 10. Statistik Transaksi (Cash vs Transfer)
$res_pay_stats = mysqli_query($conn, "
    SELECT metode_bayar, COUNT(*) as count, SUM(jumlah_bayar) as total 
    FROM payments 
    WHERE status_verifikasi = 'terverifikasi' 
    GROUP BY metode_bayar
");
$pay_stats = [
    'QRIS' => ['count' => 0, 'total' => 0],
    'Transfer' => ['count' => 0, 'total' => 0],
    'Cash' => ['count' => 0, 'total' => 0]
];
while ($row = mysqli_fetch_assoc($res_pay_stats)) {
    $pay_stats[$row['metode_bayar']] = $row;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Analisa Data Penyewaan</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Laporan visualisasi booking lapangan, data transaksi keuangan, dan grafik statistik pemesanan.</p>
        </div>

        <!-- Analytical Highlights Grid -->
        <div class="dashboard-stat-grid">
            
            <!-- Lapangan Populer -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.08); color: var(--green);">
                    <span class="material-symbols-outlined">star</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size: 1.15rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px;">
                        <?= $pop_court ? htmlspecialchars($pop_court['nama_lapangan']) : 'N/A' ?>
                    </span>
                    <span class="stat-card-label">Lapangan Terpopuler (<?= $pop_court ? $pop_court['count'] : 0 ?> Booking)</span>
                </div>
            </div>

            <!-- Jam Paling Ramai -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(245, 158, 11, 0.08); color: #F59E0B;">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size: 1.15rem; font-weight: 700;">
                        <?= $pop_hour ? sprintf('%02d:00', $pop_hour['hour']) : 'N/A' ?>
                    </span>
                    <span class="stat-card-label">Jam Teramai (<?= $pop_hour ? $pop_hour['count'] : 0 ?> Booking)</span>
                </div>
            </div>

            <!-- Top Customer -->
            <div class="dashboard-stat-card" style="grid-column: span 2;">
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">person</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size: 1.1rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;">
                        <?= $top_cust ? htmlspecialchars($top_cust['nama_lengkap']) : 'N/A' ?>
                    </span>
                    <span class="stat-card-label">Top Customer (<?= $top_cust ? $top_cust['count'] : 0 ?> Booking, Rp <?= $top_cust ? number_format($top_cust['total_spent'], 0, ',', '.') : 0 ?>)</span>
                </div>
            </div>
        </div>

        <!-- Row 1 Charts (Line & Bar) -->
        <div class="admin-grid-activities" style="margin-bottom: 24px;">
            <!-- Line Chart: Daily Volume -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Volume Booking Harian (30 Hari Terakhir)</h2>
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>

            <!-- Bar Chart: Bookings per Court -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Jumlah Booking per Lapangan</h2>
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="courtChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Row 2 Charts (Monthly Revenue, Booking Status, Transaction Split) -->
        <div class="admin-grid-charts-2col" style="margin-bottom: 24px;">
            
            <!-- Revenue Bar Chart -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Realisasi Pendapatan Bulanan</h2>
                <div style="position: relative; height: 320px; width: 100%;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart: Booking Status -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Status Pemesanan</h2>
                <div style="position: relative; height: 220px; width: 100%; margin-bottom: 20px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <!-- Transaction Split List -->
                <div style="border-top:1px solid var(--border); padding-top:16px;">
                    <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:8px;">
                        <span style="color:var(--text-muted);">QRIS / Transfer Bank:</span>
                        <strong style="color:var(--navy);"><?= ($pay_stats['QRIS']['count'] ?? 0) + ($pay_stats['Transfer']['count'] ?? 0) ?> Transaksi (Rp <?= number_format(($pay_stats['QRIS']['total'] ?? 0) + ($pay_stats['Transfer']['total'] ?? 0), 0, ',', '.') ?>)</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                        <span style="color:var(--text-muted);">Cash / Tunai:</span>
                        <strong style="color:var(--navy);"><?= $pay_stats['Cash']['count'] ?> Transaksi (Rp <?= number_format($pay_stats['Cash']['total'], 0, ',', '.') ?>)</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 3 Charts (Monthly Bookings & Hourly peak) -->
        <div class="admin-grid-activities">
            <!-- Monthly Volume -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Pemesanan Lapangan Bulanan</h2>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <!-- Hourly Peak Times -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Distribusi Jam Booking Teramai</h2>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- CHART.JS INSTANTIATION SCRIPTS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Global Colors
    const primaryGreen = '#22C55E';
    const primaryBlue = '#0EA5E9';
    const navyDark = '#0F172A';
    const orangeWarning = '#F59E0B';
    const redDanger = '#EF4444';
    
    // 1. Line Chart: Daily Volume
    const dailyLabels = <?= json_encode($daily_labels) ?>;
    const dailyData = <?= json_encode($daily_data) ?>;
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Jumlah Booking',
                data: dailyData,
                borderColor: primaryGreen,
                backgroundColor: 'rgba(34, 197, 94, 0.08)',
                fill: true,
                tension: 0.3,
                borderWidth: 3,
                pointBackgroundColor: primaryGreen
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 2. Bar Chart: Bookings per Court
    const courtLabels = <?= json_encode($court_labels) ?>;
    const courtData = <?= json_encode($court_data) ?>;
    new Chart(document.getElementById('courtChart'), {
        type: 'bar',
        data: {
            labels: courtLabels,
            datasets: [{
                label: 'Total Booking',
                data: courtData,
                backgroundColor: [primaryBlue, primaryGreen, orangeWarning, navyDark],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 3. Bar Chart: Revenue Chart
    const revLabels = <?= json_encode($revenue_labels) ?>;
    const revData = <?= json_encode($revenue_data) ?>;
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: revLabels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: revData,
                backgroundColor: 'rgba(34, 197, 94, 0.85)',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 4. Doughnut Chart: Booking Status
    const statusLabels = <?= json_encode($status_labels) ?>;
    const statusData = <?= json_encode($status_data) ?>;
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: [orangeWarning, primaryGreen, redDanger]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });

    // 5. Line Chart: Monthly Volume
    const monthLabels = <?= json_encode($monthly_labels) ?>;
    const monthData = <?= json_encode($monthly_data) ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Pemesanan Bulanan',
                data: monthData,
                borderColor: primaryBlue,
                backgroundColor: 'rgba(14, 165, 233, 0.08)',
                fill: true,
                tension: 0.2,
                borderWidth: 3,
                pointBackgroundColor: primaryBlue
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // 6. Bar Chart: Hourly Peak Times
    const hourLabels = <?= json_encode($hourly_labels) ?>;
    const hourData = <?= json_encode($hourly_data) ?>;
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hourLabels,
            datasets: [{
                label: 'Booking',
                data: hourData,
                backgroundColor: 'rgba(15, 23, 42, 0.8)',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

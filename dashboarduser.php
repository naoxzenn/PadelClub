<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] !== 'customer') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'kasir') {
        header('Location: kasir/dashboard.php');
    }
    exit;
}
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/helpers/QRHelper.php';
/** @var mysqli $conn */

$pageTitle = 'Dashboard Saya';
$baseUrl = '';
$user_id = $_SESSION['user_id'];

// Aksi batalkan
if (isset($_GET['cancel_id'])) {
    $cid = (int)$_GET['cancel_id'];
    $stmtC = mysqli_prepare($conn,
        "UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=? AND status='pending'"
    );
    if ($stmtC) {
        mysqli_stmt_bind_param($stmtC, 'ii', $cid, $user_id);
        mysqli_stmt_execute($stmtC);
        mysqli_stmt_close($stmtC);
    }
    header('Location: dashboarduser.php?msg=cancelled');
    exit;
}

// Ambil data user
$stmtU = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
if ($stmtU) {
    mysqli_stmt_bind_param($stmtU, 'i', $user_id);
    mysqli_stmt_execute($stmtU);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtU));
    mysqli_stmt_close($stmtU);
} else {
    die("Query error: " . mysqli_error($conn));
}

// Ambil riwayat booking
$stmtB = mysqli_prepare($conn,
    "SELECT b.*, c.nama_lapangan, c.tipe_lapangan, p.metode_bayar, p.status_verifikasi
     FROM bookings b
     JOIN courts c ON b.court_id = c.id
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC"
);
if ($stmtB) {
    mysqli_stmt_bind_param($stmtB, 'i', $user_id);
    mysqli_stmt_execute($stmtB);
    $bookings = mysqli_fetch_all(mysqli_stmt_get_result($stmtB), MYSQLI_ASSOC);
    mysqli_stmt_close($stmtB);
} else {
    die("Query error: " . mysqli_error($conn));
}

// Statistik
$total_booking  = count($bookings);
$booking_aktif  = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
$total_pengeluaran = array_sum(array_column(
    array_filter($bookings, fn($b) => $b['status'] !== 'cancelled'),
    'total_harga'
));
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>Dashboard Saya</h1>
        <p>Selamat datang, <?= htmlspecialchars($user['nama_lengkap']) ?>!</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
            <div class="alert alert-warning">Booking berhasil dibatalkan.</div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="summary-grid">
            <div class="summary-box">
                <div class="number"><?= $total_booking ?></div>
                <div class="label">Total Booking</div>
            </div>
            <div class="summary-box">
                <div class="number"><?= $booking_aktif ?></div>
                <div class="label">Booking Confirmed</div>
            </div>
            <div class="summary-box">
                <div class="number">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></div>
                <div class="label">Total Pengeluaran</div>
            </div>
        </div>

        <div style="text-align: right; margin-bottom: 16px;">
            <a href="booking.php" class="btn btn-primary">+ Booking Baru</a>
        </div>

        <!-- Riwayat Booking -->
        <div class="card">
            <h2>Riwayat Booking</h2>
            <?php if (empty($bookings)): ?>
                <div class="alert alert-info">
                    Belum ada booking. <a href="booking.php">Booking sekarang</a>!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="tabel-riwayat">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Paket</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Kode &amp; QR</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $i => $b): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['nama_lapangan']) ?></strong><br>
                                        <small><?= $b['tipe_lapangan'] ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?></td>
                                    <td><?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?></td>
                                    <td><?= $b['paket'] === 'per_jam' ? 'Per Jam' : 'Per Match' ?>
                                        <?= $b['sewa_raket'] ? '<br><small>+Raket</small>' : '' ?></td>
                                    <td>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                                    <td><span class="status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                                    <td>
                                         <?php if ($b['metode_bayar']): ?>
                                             <?= htmlspecialchars($b['metode_bayar']) ?><br>
                                             <small><?= ucfirst(htmlspecialchars($b['payment_status'] ?? '-')) ?></small>
                                         <?php else: ?>
                                             <span style="color:#aaa;">Belum bayar</span>
                                         <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($b['payment_status'] === 'Verified'): ?>
                                            <code style="font-family: monospace; font-size: 0.9rem; font-weight: 700; color: var(--navy); display:block; margin-bottom:4px;"><?= htmlspecialchars($b['booking_code']) ?></code>
                                            <a href="booking-detail.php?code=<?= $b['booking_code'] ?>">
                                                <img src="<?= QRHelper::generateQRCodeDataUri(QRHelper::generateCheckinUrl($b['booking_code'])) ?>" alt="QR" style="width: 50px; height: 50px; background:#fff; padding:2px; border:1px solid var(--border); border-radius:4px; display:inline-block;">
                                            </a>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">Menunggu verifikasi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <?php if ($b['payment_status'] === 'Verified'): ?>
                                                <a href="booking-detail.php?code=<?= $b['booking_code'] ?>" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;">
                                                    <span class="material-symbols-outlined" style="font-size:0.95rem;">confirmation_number</span> Tiket
                                                </a>
                                                <a href="invoice.php?code=<?= $b['booking_code'] ?>" class="btn btn-sm btn-outline" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;">
                                                    <span class="material-symbols-outlined" style="font-size:0.95rem;">receipt_long</span> Invoice
                                                </a>
                                                <a href="download_qr.php?code=<?= $b['booking_code'] ?>" class="btn btn-sm btn-outline" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;">
                                                    <span class="material-symbols-outlined" style="font-size:0.95rem;">download</span> QR Code
                                                </a>
                                            <?php else: ?>
                                                <a href="rincian_pembayaran.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;">
                                                    <span class="material-symbols-outlined" style="font-size:0.95rem;">payments</span> Detail Bayar
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <a href="dashboarduser.php?cancel_id=<?= $b['id'] ?>" class="btn btn-sm btn-danger" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;" onclick="return confirm('Apakah Anda yakin ingin membatalkan booking ini?');">
                                                    <span class="material-symbols-outlined" style="font-size:0.95rem;">cancel</span> Batal
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Akun -->
        <div class="card">
            <h2>Informasi Akun</h2>
            <div class="detail-box">
                <div class="detail-row">
                    <span class="label">Nama Lengkap</span>
                    <span class="value"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Email</span>
                    <span class="value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Nomor Telepon</span>
                    <span class="value"><?= htmlspecialchars($user['nomor_telepon'] ?? '-') ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Bergabung Sejak</span>
                    <span class="value"><?= date('d F Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

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
    // Verify ownership and status before cancelling
    $chk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM bookings WHERE id=$cid AND user_id=$user_id AND status='pending'"));
    if ($chk) {
        updateBookingVerification($conn, $cid, 'cancelled');
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

// Booking Selanjutnya
$upcoming_bookings = array_filter($bookings, function($b) {
    return $b['status'] === 'confirmed' && $b['tanggal_booking'] >= date('Y-m-d');
});
usort($upcoming_bookings, fn($a, $b) => strcmp($a['tanggal_booking'], $b['tanggal_booking']));
$next_booking = !empty($upcoming_bookings) ? reset($upcoming_bookings) : null;
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="page-header" style="padding-top: calc(var(--nav-height) + 36px); padding-bottom: 30px; background: var(--surface-alt); border-bottom: 1px solid var(--border);">
    <div class="container">
        <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Halo, <?= htmlspecialchars($user['nama_lengkap']) ?>! 👋</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Selamat datang di Dashboard Akun PadelClub Anda.</p>
    </div>
</section>

<section class="section" style="padding: 32px 0;">
    <div class="container">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled'): ?>
            <div class="alert alert-warning" style="margin-bottom: 24px;">Booking berhasil dibatalkan.</div>
        <?php endif; ?>

        <!-- Ringkasan Informasi Customer -->
        <div class="status-kehadiran-grid" style="margin-bottom: 32px;">
            <div class="summary-box" style="text-align: left; padding: 20px 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                    <span class="label" style="margin: 0;">Booking Aktif</span>
                    <span class="material-symbols-outlined" style="color: var(--blue);">event_available</span>
                </div>
                <div class="number" style="font-size: 1.8rem;"><?= $booking_aktif ?></div>
            </div>

            <div class="summary-box" style="text-align: left; padding: 20px 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                    <span class="label" style="margin: 0;">Riwayat Booking</span>
                    <span class="material-symbols-outlined" style="color: var(--green);">history</span>
                </div>
                <div class="number" style="font-size: 1.8rem;"><?= $total_booking ?></div>
            </div>

            <div class="summary-box" style="text-align: left; padding: 20px 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                    <span class="label" style="margin: 0;">Status Pembayaran</span>
                    <span class="material-symbols-outlined" style="color: #F59E0B;">payments</span>
                </div>
                <div class="number" style="font-size: 1.4rem; color: var(--navy);">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></div>
            </div>

            <div class="summary-box" style="text-align: left; padding: 20px 24px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                    <span class="label" style="margin: 0;">Booking Selanjutnya</span>
                    <span class="material-symbols-outlined" style="color: #8B5CF6;">sports_tennis</span>
                </div>
                <?php if ($next_booking): ?>
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--navy);"><?= date('d M Y', strtotime($next_booking['tanggal_booking'])) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= substr($next_booking['jam_mulai'],0,5) ?> - <?= htmlspecialchars($next_booking['nama_lapangan']) ?></div>
                <?php else: ?>
                    <div style="font-size: 0.88rem; color: var(--text-muted); font-weight: 500;">Belum Ada</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Action Grid -->
        <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 16px;">Quick Action</h2>
        <div class="quick-actions-grid" style="margin-bottom: 36px;">
            <a href="booking.php" class="quick-action-card">
                <div class="quick-action-top">
                    <div class="quick-action-icon icon-blue">
                        <span class="material-symbols-outlined">sports_tennis</span>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; margin: 0 0 4px 0; color: var(--navy);">Booking Lapangan</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Pilih lapangan & waktu main pilihan Anda.</p>
                    </div>
                </div>
            </a>

            <a href="#tabel-riwayat" class="quick-action-card">
                <div class="quick-action-top">
                    <div class="quick-action-icon icon-green">
                        <span class="material-symbols-outlined">receipt_long</span>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; margin: 0 0 4px 0; color: var(--navy);">Riwayat Booking</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Lihat detail tiket, invoice, & status pesanan.</p>
                    </div>
                </div>
            </a>

            <a href="profil.php" class="quick-action-card">
                <div class="quick-action-top">
                    <div class="quick-action-icon icon-purple">
                        <span class="material-symbols-outlined">manage_accounts</span>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; margin: 0 0 4px 0; color: var(--navy);">Lihat Profil</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Pusat pengaturan akun & informasi pribadi.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Riwayat Booking Table -->
        <div class="card" style="padding: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; flex-wrap:wrap; gap:12px;">
                <div>
                    <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin: 0 0 4px 0;">Riwayat Booking Anda</h2>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Daftar seluruh transaksi pemesanan lapangan PadelClub.</p>
                </div>
                <a href="booking.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; display:inline-flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">add</span> Booking Lapangan
                </a>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="alert alert-info">
                    Belum ada booking. <a href="booking.php" style="font-weight:700;">Booking sekarang</a>!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="tabel-riwayat">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lapangan</th>
                                <th>Tanggal & Jam</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $i => $b): ?>
                                <?php
                                $detailUrl = (!empty($b['booking_code']))
                                    ? 'booking-detail.php?code=' . urlencode($b['booking_code'])
                                    : 'rincian_pembayaran.php?booking_id=' . $b['id'];
                                $payStatus = $b['payment_status'] ?? 'Pending';
                                $isVerified = ($payStatus === 'Verified' || $b['status_verifikasi'] === 'terverifikasi');
                                $isRejected = ($payStatus === 'Rejected' || $b['status_verifikasi'] === 'ditolak');
                                ?>
                                <tr class="booking-row-link" onclick="window.location='<?= $detailUrl ?>'" title="Klik untuk melihat detail booking">
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['nama_lapangan']) ?></strong><br>
                                        <small><?= $b['tipe_lapangan'] ?></small>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?><br>
                                        <small><?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?></small>
                                    </td>
                                    <td>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                                    <td><span class="status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                                    <td>
                                        <?php if ($b['metode_bayar']): ?>
                                            <?php $mbg = $b['metode_bayar'] === 'QRIS' ? '#EEF6FF' : ($b['metode_bayar'] === 'Cash' ? '#F0FDF4' : '#EEF6FF'); ?>
                                            <?php $mco = $b['metode_bayar'] === 'Cash' ? '#16A34A' : '#0EA5E9'; ?>
                                            <span style="background:<?= $mbg ?>; color:<?= $mco ?>; font-weight:700; font-size:0.75rem; padding:2px 8px; border-radius:4px; display:inline-block; margin-bottom:4px;">
                                                <?= htmlspecialchars($b['metode_bayar']) ?>
                                            </span><br>
                                        <?php endif; ?>
                                        <?php if ($isVerified): ?>
                                            <span class="status-confirmed" style="font-size:0.75rem; padding:2px 8px; border-radius:4px; font-weight:700; display:inline-block;">Lunas</span>
                                        <?php elseif ($isRejected): ?>
                                            <span class="status-cancelled" style="font-size:0.75rem; padding:2px 8px; border-radius:4px; font-weight:700; display:inline-block;">Gagal</span>
                                        <?php else: ?>
                                            <span class="status-pending" style="font-size:0.75rem; padding:2px 8px; border-radius:4px; font-weight:700; display:inline-block;">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        <div style="display:flex; flex-direction:column; gap:4px;">
                                            <a href="<?= $detailUrl ?>" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;">
                                                <span class="material-symbols-outlined" style="font-size:0.95rem;">open_in_new</span>
                                                <?= $isVerified ? 'Tiket & QR' : 'Detail' ?>
                                            </a>
                                            <?php if ($isVerified && !empty($b['booking_code'])): ?>
                                                <a href="invoice.php?code=<?= $b['booking_code'] ?>" class="btn btn-sm btn-outline" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;">
                                                    <span class="material-symbols-outlined" style="font-size:0.95rem;">receipt_long</span> Invoice
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <a href="dashboarduser.php?cancel_id=<?= $b['id'] ?>" class="btn btn-sm btn-danger" style="display:inline-flex; align-items:center; gap:4px; justify-content:center;" onclick="event.stopPropagation(); return confirm('Batalkan booking ini?');">
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

    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scroll') === 'riwayat') {
        const target = document.getElementById('tabel-riwayat');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

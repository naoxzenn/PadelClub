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
/** @var mysqli $conn */

$pageTitle = 'Dashboard Saya';
$baseUrl = '';
$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Helper untuk memetakan status booking & rincian status
function getBookingStatusDetails($b) {
    if ($b['status'] === 'cancelled') {
        return [
            'label' => 'Dibatalkan',
            'class' => 'status-cancelled',
            'icon'  => 'bi-x-circle-fill'
        ];
    }
    
    if ($b['status'] === 'confirmed') {
        $now = time();
        $bookingStart = strtotime($b['tanggal_booking'] . ' ' . $b['jam_mulai']);
        $bookingEnd = strtotime($b['tanggal_booking'] . ' ' . $b['jam_selesai']);
        
        if ($now > $bookingEnd) {
            return [
                'label' => 'Selesai',
                'class' => 'status-confirmed',
                'icon'  => 'bi-check-circle-fill'
            ];
        } elseif ($now >= $bookingStart && $now <= $bookingEnd) {
            return [
                'label' => 'Sedang Bermain',
                'class' => 'status-confirmed',
                'icon'  => 'bi-play-circle-fill'
            ];
        } else {
            return [
                'label' => 'Dikonfirmasi',
                'class' => 'status-confirmed',
                'icon'  => 'bi-patch-check-fill'
            ];
        }
    }
    
    // Status is 'pending'
    if (!empty($b['metode_bayar'])) {
        return [
            'label' => 'Menunggu Konfirmasi',
            'class' => 'status-pending',
            'icon'  => 'bi-hourglass-split'
        ];
    } else {
        return [
            'label' => 'Menunggu Pembayaran',
            'class' => 'status-pending',
            'icon'  => 'bi-wallet2'
        ];
    }
}

// Aksi batalkan via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $cid = (int)$_POST['booking_id'];
    
    // Validasi kepemilikan booking
    $stmtCheck = mysqli_prepare($conn,
        "SELECT b.*, p.metode_bayar FROM bookings b 
         LEFT JOIN payments p ON p.booking_id = b.id 
         WHERE b.id = ? AND b.user_id = ?"
    );
    if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, 'ii', $cid, $user_id);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);
        $bookingToCancel = mysqli_fetch_assoc($resCheck);
        mysqli_stmt_close($stmtCheck);
        
        if ($bookingToCancel) {
            $statusDetails = getBookingStatusDetails($bookingToCancel);
            $canCancel = ($statusDetails['label'] === 'Menunggu Pembayaran' || $statusDetails['label'] === 'Menunggu Konfirmasi');
            
            if ($canCancel) {
                // Soft cancel: ubah status ke 'cancelled' dan catat cancelled_at
                $nowStr = date('Y-m-d H:i:s');
                $stmtCancel = mysqli_prepare($conn,
                    "UPDATE bookings SET status='cancelled', cancelled_at=? WHERE id=? AND user_id=?"
                );
                if ($stmtCancel) {
                    mysqli_stmt_bind_param($stmtCancel, 'sii', $nowStr, $cid, $user_id);
                    mysqli_stmt_execute($stmtCancel);
                    mysqli_stmt_close($stmtCancel);
                    
                    // Update status pembayaran terkait (jika ada) ke ditolak & unpaid
                    mysqli_query($conn, "UPDATE payments SET status_verifikasi='ditolak', payment_status='unpaid' WHERE booking_id=$cid");
                    
                    header('Location: dashboarduser.php?msg=cancelled');
                    exit;
                } else {
                    $error_msg = 'Gagal memproses pembatalan di database.';
                }
            } else {
                $error_msg = 'Booking ini tidak dapat dibatalkan karena statusnya sudah: ' . htmlspecialchars($statusDetails['label']) . '.';
            }
        } else {
            $error_msg = 'Booking tidak ditemukan atau bukan milik Anda.';
        }
    } else {
        $error_msg = 'Gagal mempersiapkan validasi data.';
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled') {
    $success_msg = 'Booking berhasil dibatalkan.';
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

<!-- Bootstrap 5 CSS & Bootstrap Icons CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/* --- OVERRIDES UNTUK MENCEGAH KONFLIK BOOTSTRAP --- */
ul {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Fix header navbar clash dengan Bootstrap */
#main-nav {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1000 !important;
    height: var(--nav-height) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 0 24px !important;
    background: rgba(255, 255, 255, .72) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    border-bottom: 1px solid rgba(226, 232, 240, .8) !important;
    box-sizing: border-box !important;
}
@media (min-width: 1024px) {
    #main-nav {
        padding: 0 64px !important;
    }
}
#main-nav.nav {
    flex-wrap: nowrap !important;
}
#main-nav .logo {
    font-size: 1.4rem !important;
    font-weight: 800 !important;
    text-decoration: none !important;
}
#main-nav ul.nav-links {
    display: none !important;
    margin-bottom: 0 !important;
    padding-left: 0 !important;
    list-style: none !important;
}
@media (min-width: 900px) {
    #main-nav ul.nav-links {
        display: flex !important;
    }
}
#main-nav ul.nav-links li {
    margin-bottom: 0 !important;
}
#main-nav ul.nav-links a {
    font-weight: 600 !important;
    font-size: .95rem !important;
    color: var(--text-muted) !important;
    padding-bottom: 4px !important;
    border-bottom: 2px solid transparent !important;
    text-decoration: none !important;
    transition: color .2s, border-color .2s !important;
}
#main-nav ul.nav-links a:hover,
#main-nav ul.nav-links a.active {
    color: var(--blue) !important;
    border-bottom-color: var(--blue) !important;
}

#mobile-nav a {
    text-decoration: none !important;
}

.site-footer {
    border-top: 1px solid var(--border) !important;
    background: #fff !important;
    color: var(--navy) !important;
}
.site-footer a {
    text-decoration: none !important;
}
.site-footer ul li {
    margin-bottom: 8px !important;
}

/* Custom Page styling */
.dashboard-wrapper {
    margin-top: var(--nav-height);
    padding: 40px 0;
}
</style>

<div class="dashboard-wrapper">
    <section class="page-header" style="margin-top: 0; padding: 40px 0; background: radial-gradient(ellipse at 0% 0%, rgba(34,197,94,.08) 0%, transparent 65%), #0F172A; color: #fff;">
        <div class="container">
            <h1 style="font-weight: 800; font-size: 2.2rem; letter-spacing: -0.02em;">Dashboard Saya</h1>
            <p style="color: rgba(255,255,255,0.7); margin-top: 6px; font-size: 1rem;">Selamat datang kembali, <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong>!</p>
        </div>
    </section>

    <section class="section" style="padding: 40px 0;">
        <div class="container">

            <!-- Alerts -->
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 500; margin-bottom: 24px; padding: 16px 20px;">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; font-weight: 500; margin-bottom: 24px; padding: 16px 20px;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
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

            <div style="text-align: right; margin-bottom: 20px;">
                <a href="booking.php" class="btn btn-primary" style="font-weight: 600; border-radius: 10px;">+ Booking Baru</a>
            </div>

            <!-- Riwayat Booking -->
            <div class="card" style="border-radius: 16px; border: 1px solid var(--border); padding: 24px; margin-bottom: 32px;">
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Riwayat Booking</h2>
                <?php if (empty($bookings)): ?>
                    <div class="alert alert-info" style="border-radius: 10px;">
                        Belum ada booking. <a href="booking.php" style="font-weight: 600; text-decoration: underline;">Booking sekarang</a>!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="tabel-riwayat" class="table align-middle" style="min-width: 900px; margin-bottom: 0;">
                            <thead style="background: var(--surface-alt);">
                                <tr>
                                    <th style="padding: 14px 16px;">#</th>
                                    <th>Lapangan</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Paket</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Pembayaran</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $i => $b): 
                                    $statusDetails = getBookingStatusDetails($b);
                                    $canCancel = ($statusDetails['label'] === 'Menunggu Pembayaran' || $statusDetails['label'] === 'Menunggu Konfirmasi');
                                ?>
                                    <tr>
                                        <td style="padding: 14px 16px;"><?= $i + 1 ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($b['nama_lapangan']) ?></strong><br>
                                            <small class="text-muted"><?= $b['tipe_lapangan'] ?></small>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?></td>
                                        <td><?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?></td>
                                        <td>
                                            <?= $b['paket'] === 'per_jam' ? 'Per Jam' : 'Per Match' ?>
                                            <?= $b['sewa_raket'] ? '<br><small class="text-success">+Raket</small>' : '' ?>
                                        </td>
                                        <td style="font-weight: 600; color: var(--navy);">Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="<?= $statusDetails['class'] ?>">
                                                <i class="bi <?= $statusDetails['icon'] ?> me-1"></i> <?= $statusDetails['label'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($b['metode_bayar']): ?>
                                                <?= $b['metode_bayar'] ?><br>
                                                <small class="text-muted"><?= ucfirst($b['status_verifikasi'] ?? '-') ?></small>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 0.85rem;">Belum bayar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a href="rincian_pembayaran.php?booking_id=<?= $b['id'] ?>"
                                                   class="btn btn-sm btn-primary" style="font-weight: 600; border-radius: 8px; font-size: 0.8rem;">
                                                   Detail
                                                </a>
                                                <?php if ($canCancel): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger btn-cancel-trigger"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#cancelModal"
                                                            data-booking-id="<?= $b['id'] ?>"
                                                            style="font-weight: 600; border-radius: 8px; font-size: 0.8rem; background-color: #EF4444; border: none;">
                                                        <i class="bi bi-x-circle me-1"></i> Batalkan Booking
                                                    </button>
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
            <div class="card" style="border-radius: 16px; border: 1px solid var(--border); padding: 24px;">
                <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Informasi Akun</h2>
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
</div>

<!-- Modal Konfirmasi Pembatalan -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; overflow: hidden; box-shadow: var(--shadow-md);">
            <div class="modal-header" style="border-bottom: 1px solid var(--border); background-color: #fff; padding: 20px 24px;">
                <h5 class="modal-title" id="cancelModalLabel" style="font-weight: 700; color: var(--navy);">
                    <i class="bi bi-x-circle text-danger me-2"></i> Batalkan Booking
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.8rem;"></button>
            </div>
            <form action="dashboarduser.php" method="POST">
                <input type="hidden" name="action" value="cancel_booking">
                <input type="hidden" name="booking_id" id="modalBookingId" value="">
                <div class="modal-body" style="padding: 24px; color: var(--text);">
                    <p style="font-size: 1.05rem; font-weight: 600; margin-bottom: 8px;">Apakah Anda yakin ingin membatalkan booking ini?</p>
                    <p class="text-muted mb-0" style="font-size: 0.92rem;">Booking yang sudah dibatalkan tidak dapat dikembalikan.</p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 24px; display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="flex: 1; border-radius: 10px; font-weight: 600; padding: 12px;">Tidak</button>
                    <button type="submit" class="btn btn-danger" style="flex: 1; border-radius: 10px; font-weight: 600; padding: 12px; background-color: #EF4444; border: none;">Ya, Batalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JavaScript Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cancelTriggers = document.querySelectorAll('.btn-cancel-trigger');
    const modalBookingIdInput = document.getElementById('modalBookingId');
    
    cancelTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            modalBookingIdInput.value = bookingId;
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


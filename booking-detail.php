<?php
// booking-detail.php - Detail Booking & Digital Ticket Customer View

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/helpers/QRHelper.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: dashboarduser.php');
    exit;
}

$model = new BookingModel($pdo);
$booking = $model->getBookingByCode($code);

if (!$booking) {
    header('Location: dashboarduser.php');
    exit;
}

// Security Validation
if ($_SESSION['role'] === 'customer' && $booking['user_id'] != $_SESSION['user_id']) {
    header('Location: dashboarduser.php');
    exit;
}

$pageTitle = 'Detail Booking #' . $booking['id'];
$baseUrl = '';
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Tiket Digital Anda</h1>
        <p>Gunakan QR Code di bawah ini untuk check-in di lokasi lapangan</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width: 700px;">
        <div style="margin-bottom: 20px;">
            <?php if ($_SESSION['role'] === 'customer'): ?>
                <a href="dashboarduser.php" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined">arrow_back</span> Kembali ke Dashboard
                </a>
            <?php else: ?>
                <a href="admin/bookings.php" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined">arrow_back</span> Kembali ke Manajemen Booking
                </a>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 24px;">
            <!-- Ticket Card -->
            <div class="card" style="padding: 32px; border-radius: var(--radius-lg); text-align: center; position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div style="position: absolute; top:0; right:0; left:0; height:6px; background: var(--gradient);"></div>
                
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--navy); margin-bottom: 8px;">PadelClub Premium Ticket</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">Tunjukkan QR Code ini kepada petugas lapangan untuk dipindai saat bermain.</p>
                
                <?php if ($booking['payment_status'] === 'Verified'): ?>
                    <div style="background: #ffffff; padding: 16px; border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid var(--border); display: inline-block; margin-bottom: 16px;">
                        <img src="<?= QRHelper::generateQRCodeDataUri(QRHelper::generateCheckinUrl($code)) ?>" alt="QR Code Check-in" style="width: 200px; height: 200px; display: block;">
                    </div>
                    <div style="font-family: monospace; font-size: 1.25rem; font-weight: 800; color: var(--navy); margin-bottom: 8px; letter-spacing: 0.05em;"><?= htmlspecialchars($code) ?></div>
                    <div style="font-size: 0.82rem; font-weight: 700; color: var(--green); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 24px;">✓ Pembayaran Terverifikasi</div>
                <?php else: ?>
                    <div style="background: var(--surface-alt); width: 200px; height: 200px; border-radius: var(--radius-md); border: 2px dashed var(--border); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; flex-direction: column; gap: 8px; color: var(--text-muted);">
                        <span class="material-symbols-outlined" style="font-size: 3.5rem;">qr_code_2</span>
                        <span style="font-size: 0.8rem; font-weight: 600; text-align: center; padding: 0 10px;">QR Code Belum Tersedia</span>
                    </div>
                    <div style="font-size: 0.85rem; font-weight: 700; color: #F59E0B; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">⏳ Pembayaran Menunggu Verifikasi</div>
                    <p style="font-size: 0.82rem; color: var(--text-muted); max-width: 320px; margin-bottom: 24px; line-height: 1.5;">QR Code akan otomatis aktif setelah pembayaran Anda diverifikasi oleh tim admin PadelClub.</p>
                <?php endif; ?>

                <!-- Action buttons -->
                <div style="display: flex; gap: 16px; width: 100%; max-width: 480px; flex-wrap: wrap;">
                    <?php if ($booking['payment_status'] === 'Verified'): ?>
                        <a href="download_qr.php?code=<?= $code ?>" class="btn btn-primary" style="flex: 1; min-width: 180px; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding: 12px 16px;">
                            <span class="material-symbols-outlined">download</span> Unduh QR Code
                        </a>
                        <a href="invoice.php?code=<?= $code ?>" class="btn btn-secondary" style="flex: 1; min-width: 180px; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding: 12px 16px;">
                            <span class="material-symbols-outlined">receipt_long</span> Cetak Invoice PDF
                        </a>
                    <?php else: ?>
                        <a href="rincian_pembayaran.php?booking_id=<?= $booking['id'] ?>" class="btn btn-primary" style="width: 100%; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding: 12px 16px;">
                            <span class="material-symbols-outlined">upload_file</span> Upload Bukti Pembayaran
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Details Card -->
            <div class="card" style="padding: 28px; border-radius: var(--radius-lg);">
                <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--navy); margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="color: var(--blue);">info</span>
                    Detail Booking Lapangan
                </h3>
                
                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.9rem; color: var(--text);">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 6px;">
                        <span style="color: var(--text-muted);">Nama Lapangan</span>
                        <strong style="color: var(--navy);"><?= htmlspecialchars($booking['nama_lapangan']) ?> (<?= $booking['tipe_lapangan'] ?>)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 6px;">
                        <span style="color: var(--text-muted);">Tanggal Bermain</span>
                        <strong style="color: var(--navy);"><?= date('l, d F Y', strtotime($booking['tanggal_booking'])) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 6px;">
                        <span style="color: var(--text-muted);">Slot Waktu</span>
                        <strong style="color: var(--navy);"><?= substr($booking['jam_mulai'],0,5) ?> – <?= substr($booking['jam_selesai'],0,5) ?> WIB</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 6px;">
                        <span style="color: var(--text-muted);">Paket Bermain</span>
                        <strong style="color: var(--navy);"><?= $booking['paket'] === 'per_jam' ? 'Per Jam' : 'Per Match' ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 6px;">
                        <span style="color: var(--text-muted);">Sewa Raket Tambahan</span>
                        <strong style="color: var(--navy);"><?= $booking['sewa_raket'] ? 'Ya (+ Rp 50.000)' : 'Tidak' ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 6px;">
                        <span style="color: var(--text-muted);">Catatan</span>
                        <strong style="color: var(--navy); max-width: 250px; text-align: right;"><?= htmlspecialchars(empty($booking['catatan']) ? '-' : $booking['catatan']) ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 6px;">
                        <span style="color: var(--text-muted);">Total Pembayaran</span>
                        <strong style="color: var(--blue); font-size: 1.1rem;">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></strong>
                    </div>
                    
                    <?php if ($booking['checkin_status'] === 'Checked In'): ?>
                        <div style="display: flex; justify-content: space-between; margin-top: 10px; background: rgba(34, 197, 94, 0.05); padding: 10px; border-radius: 6px;">
                            <span style="color: var(--green); font-weight: 600;">Status Check-in</span>
                            <strong style="color: var(--green);">Selesai pada <?= date('d/m/Y H:i', strtotime($booking['checkin_time'])) ?> WIB</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

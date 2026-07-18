<?php
// booking-detail.php - Detail Booking & Digital Ticket

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

// Fetch payment info
$stmtPay = $pdo->prepare("SELECT metode_bayar, status_verifikasi, jumlah_bayar, receipt_number FROM payments WHERE booking_id = :id LIMIT 1");
$stmtPay->execute([':id' => $booking['id']]);
$payInfo = $stmtPay->fetch();
$metodeBayar = $payInfo['metode_bayar'] ?? '-';
$receiptNumber = $payInfo['receipt_number'] ?? '-';

// Duration calculation
$start = new DateTime($booking['jam_mulai']);
$end = new DateTime($booking['jam_selesai']);
$diff = $start->diff($end);
$durasi = $diff->h . ' jam' . ($diff->i > 0 ? ' ' . $diff->i . ' menit' : '');

// Back URL based on role
$backUrl = ($_SESSION['role'] === 'customer') ? 'dashboarduser.php' : 'admin/bookings.php';
$backLabel = ($_SESSION['role'] === 'customer') ? 'Kembali ke Dashboard' : 'Kembali ke Booking';

$isVerified = ($booking['payment_status'] === 'Verified');
$isCheckedIn = ($booking['checkin_status'] === 'Checked In');

$pageTitle = 'Detail Booking ' . (!empty($code) ? $code : '#' . $booking['id']);
$baseUrl = '';
include __DIR__ . '/includes/header.php';
?>

<style>
    .booking-detail-wrap {
        max-width: 780px;
        margin: 0 auto;
        padding: 0 16px 48px;
    }

    .bd-header-card {
        background: var(--gradient);
        border-radius: var(--radius-lg);
        padding: 28px 28px 60px;
        color: #fff;
        position: relative;
        overflow: hidden;
        margin-bottom: -40px;
    }

    .bd-header-card::before {
        content: '';
        position: absolute;
        top: -40px;
        right: -40px;
        width: 180px;
        height: 180px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 50%;
    }

    .bd-header-card::after {
        content: '';
        position: absolute;
        bottom: -60px;
        left: -30px;
        width: 220px;
        height: 220px;
        background: rgba(255, 255, 255, 0.04);
        border-radius: 50%;
    }

    .bd-header-code {
        font-family: 'Courier New', monospace;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        opacity: 0.85;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .bd-header-title {
        font-size: 1.6rem;
        font-weight: 800;
        margin-bottom: 4px;
        position: relative;
        z-index: 1;
    }

    .bd-header-sub {
        font-size: 0.9rem;
        opacity: 0.85;
        position: relative;
        z-index: 1;
    }

    .bd-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        margin-top: 12px;
        position: relative;
        z-index: 1;
    }

    .bd-status-pill.verified {
        background: rgba(34, 197, 94, 0.18);
        color: #4ade80;
        border: 1px solid rgba(34, 197, 94, 0.35);
    }

    .bd-status-pill.pending {
        background: rgba(245, 158, 11, 0.18);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.35);
    }

    .bd-status-pill.rejected {
        background: rgba(239, 68, 68, 0.18);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.35);
    }

    /* QR Card */
    .bd-qr-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        padding: 28px;
        text-align: center;
        border: 1px solid var(--border);
        position: relative;
        z-index: 2;
        margin-bottom: 20px;
    }

    .bd-qr-img-wrap {
        display: inline-block;
        background: #fff;
        padding: 14px;
        border-radius: var(--radius-md);
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
        border: 1px solid var(--border);
        margin-bottom: 18px;
    }

    .bd-qr-inactive {
        width: 200px;
        height: 200px;
        border-radius: var(--radius-md);
        border: 2px dashed var(--border);
        background: var(--surface-alt);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: var(--text-muted);
        margin: 0 auto 18px;
    }

    .bd-booking-code-box {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: var(--surface-alt);
        border-radius: var(--radius-md);
        padding: 14px 20px;
        border: 1px solid var(--border);
        margin-bottom: 20px;
    }

    .bd-booking-code-text {
        font-family: 'Courier New', monospace;
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--navy);
        letter-spacing: 0.1em;
    }

    .btn-copy {
        background: var(--navy);
        color: #808080;
        border: none;
        border-radius: var(--radius-sm);
        padding: 8px 16px;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .btn-copy:hover {
        background: var(--blue);
        transform: translateY(-1px);
    }

    .btn-copy.copied {
        background: var(--green);
    }

    .bd-checkin-info {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: rgba(34, 197, 94, 0.08);
        border: 1px solid rgba(34, 197, 94, 0.2);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--green-dark);
        margin-bottom: 20px;
    }

    .bd-action-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    @media (max-width: 500px) {
        .bd-action-grid {
            grid-template-columns: 1fr;
        }

        .bd-booking-code-box {
            flex-direction: column;
        }

        .btn-copy {
            width: 100%;
            justify-content: center;
        }
    }

    /* Info Card */
    .bd-info-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .bd-info-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1rem;
        font-weight: 800;
        color: var(--navy);
    }

    .bd-info-row {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        align-items: center;
        gap: 8px;
        padding: 13px 24px;
        border-bottom: 1px solid var(--border);
        font-size: 0.9rem;
    }

    .bd-info-row:last-child {
        border-bottom: none;
    }

    .bd-info-label {
        color: var(--text-muted);
        font-weight: 500;
    }

    .bd-info-value {
        color: var(--navy);
        font-weight: 700;
        text-align: right;
    }

    .badge-method {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .badge-qris {
        background: #EEF6FF;
        color: #0EA5E9;
    }

    .badge-cash {
        background: #F0FDF4;
        color: #16A34A;
    }

    .badge-other {
        background: #F1F5F9;
        color: #475569;
    }

    .status-confirmed {
        color: var(--green);
        background: rgba(34, 197, 94, 0.10);
        padding: 3px 10px;
        border-radius: 6px;
    }

    .status-pending {
        color: #F59E0B;
        background: rgba(245, 158, 11, 0.10);
        padding: 3px 10px;
        border-radius: 6px;
    }

    .status-cancelled {
        color: #EF4444;
        background: rgba(239, 68, 68, 0.10);
        padding: 3px 10px;
        border-radius: 6px;
    }

    .status-rejected {
        color: #EF4444;
        background: rgba(239, 68, 68, 0.10);
        padding: 3px 10px;
        border-radius: 6px;
    }
</style>

<div class="booking-detail-wrap" style="padding-top: 32px;">

    <!-- Back Button -->
    <div style="margin-bottom: 24px;">
        <a href="<?= $backUrl ?>"
            style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); transition: color 0.2s;"
            onmouseover="this.style.color='var(--blue)'" onmouseout="this.style.color='var(--text-muted)'">
            <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_back</span>
            <?= $backLabel ?>
        </a>
    </div>

    <!-- Hero Header -->
    <div class="bd-header-card">
        <div class="bd-header-code">Booking ID</div>
        <div class="bd-header-title"><?= htmlspecialchars($booking['nama_lapangan']) ?></div>
        <div class="bd-header-sub">
            <?= date('l, d F Y', strtotime($booking['tanggal_booking'])) ?> &nbsp;·&nbsp;
            <?= substr($booking['jam_mulai'], 0, 5) ?> – <?= substr($booking['jam_selesai'], 0, 5) ?> WIB
        </div>
        <?php if ($isVerified): ?>
            <span class="bd-status-pill verified">
                <span class="material-symbols-outlined" style="font-size:0.95rem;">check_circle</span>
                Lunas & Terverifikasi
            </span>
        <?php elseif ($booking['payment_status'] === 'Rejected'): ?>
            <span class="bd-status-pill rejected">
                <span class="material-symbols-outlined" style="font-size:0.95rem;">cancel</span>
                Pembayaran Gagal
            </span>
        <?php else: ?>
            <span class="bd-status-pill pending">
                <span class="material-symbols-outlined" style="font-size:0.95rem;">schedule</span>
                Menunggu Pembayaran
            </span>
        <?php endif; ?>
    </div>

    <!-- QR Check-in Card -->
    <div class="bd-qr-card">
        <div
            style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 18px;">
            Tiket Check-in Digital
        </div>

        <?php if ($isVerified): ?>
            <!-- Active QR -->
            <div class="bd-qr-img-wrap">
                <img src="<?= QRHelper::generateQRCodeDataUri(QRHelper::generateCheckinUrl($code)) ?>"
                    alt="QR Check-in <?= htmlspecialchars($code) ?>" style="width: 200px; height: 200px; display: block;">
            </div>

            <?php if ($isCheckedIn): ?>
                <div class="bd-checkin-info">
                    <span class="material-symbols-outlined">how_to_reg</span>
                    Sudah Check-in pada <?= date('d/m/Y H:i', strtotime($booking['checkin_time'])) ?> WIB
                </div>
            <?php else: ?>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 16px;">
                    Tunjukkan QR ini kepada Kasir saat tiba di lapangan
                </div>
            <?php endif; ?>

            <!-- Booking Code Copy -->
            <div class="bd-booking-code-box">
                <span class="bd-booking-code-text" id="bd-code-text"><?= htmlspecialchars($code) ?></span>
                <button class="btn-copy" id="btn-copy-code" onclick="copyBookingCode()">
                    <span class="material-symbols-outlined" style="font-size:1rem;">content_copy</span>
                    Copy
                </button>
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 24px;">
                Gunakan kode di atas jika kamera Kasir bermasalah
            </div>

            <!-- Action Buttons -->
            <div class="bd-action-grid">
                <a href="invoice.php?code=<?= $code ?>" class="btn btn-primary"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 13px 16px; font-weight: 700;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">receipt_long</span>
                    Download Invoice
                </a>
                <a href="download_qr.php?code=<?= $code ?>" class="btn btn-outline"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 13px 16px; font-weight: 700;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">download</span>
                    Unduh QR
                </a>
            </div>

        <?php else: ?>
            <!-- Inactive QR -->
            <div class="bd-qr-inactive">
                <span class="material-symbols-outlined" style="font-size: 3.5rem;">qr_code_2</span>
                <span style="font-size: 0.85rem; font-weight: 700;">QR Belum Aktif</span>
                <span style="font-size: 0.75rem; text-align: center; padding: 0 20px;">Selesaikan pembayaran untuk
                    mengaktifkan tiket</span>
            </div>
            <?php if ($booking['payment_status'] !== 'Rejected'): ?>
                <a href="rincian_pembayaran.php?booking_id=<?= $booking['id'] ?>" class="btn btn-primary"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 13px 20px; font-weight: 700; width: 100%; margin-top: 4px;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">payments</span>
                    Lanjutkan Pembayaran
                </a>
            <?php else: ?>
                <div style="font-size: 0.85rem; color: #EF4444; font-weight: 600; margin-top: 8px;">
                    Pembayaran ditolak. Silakan hubungi admin.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Booking Info Card -->
    <div class="bd-info-card">
        <div class="bd-info-header">
            <span class="material-symbols-outlined" style="color: var(--blue);">info</span>
            Detail Booking
        </div>

        <div class="bd-info-row">
            <span class="bd-info-label">Kode Booking</span>
            <span class="bd-info-value" style="font-family: monospace; letter-spacing: 0.05em; color: var(--blue);">
                <?= !empty($code) ? htmlspecialchars($code) : '<span style="color:var(--text-muted);font-family:inherit;">Belum tersedia</span>' ?>
            </span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Lapangan</span>
            <span class="bd-info-value"><?= htmlspecialchars($booking['nama_lapangan']) ?></span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Tipe</span>
            <span class="bd-info-value"><?= htmlspecialchars($booking['tipe_lapangan']) ?></span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Tanggal Bermain</span>
            <span class="bd-info-value"><?= date('d F Y', strtotime($booking['tanggal_booking'])) ?></span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Jam Bermain</span>
            <span class="bd-info-value"><?= substr($booking['jam_mulai'], 0, 5) ?> –
                <?= substr($booking['jam_selesai'], 0, 5) ?> WIB</span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Durasi</span>
            <span class="bd-info-value"><?= $durasi ?></span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Paket</span>
            <span class="bd-info-value"><?= $booking['paket'] === 'per_jam' ? 'Per Jam' : 'Per Match' ?></span>
        </div>
        <?php if ($booking['sewa_raket']): ?>
            <div class="bd-info-row">
                <span class="bd-info-label">Sewa Raket</span>
                <span class="bd-info-value">Ya (+ Rp 50.000)</span>
            </div>
        <?php endif; ?>
        <div class="bd-info-row">
            <span class="bd-info-label">Total Harga</span>
            <span class="bd-info-value" style="font-size: 1.05rem; color: var(--blue);">Rp
                <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Metode Bayar</span>
            <span class="bd-info-value">
                <?php
                $mBadgeClass = match ($metodeBayar) {
                    'QRIS' => 'badge-qris',
                    'Cash' => 'badge-cash',
                    default => 'badge-other'
                };
                ?>
                <span class="badge-method <?= $mBadgeClass ?>"><?= htmlspecialchars($metodeBayar) ?></span>
            </span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Status Pembayaran</span>
            <span class="bd-info-value">
                <?php if ($isVerified): ?>
                    <span class="status-confirmed">Lunas</span>
                <?php elseif ($booking['payment_status'] === 'Rejected'): ?>
                    <span class="status-cancelled">Gagal</span>
                <?php else: ?>
                    <span class="status-pending">Pending</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Status Booking</span>
            <span class="bd-info-value">
                <?php
                $stMap = ['confirmed' => 'status-confirmed', 'pending' => 'status-pending', 'cancelled' => 'status-cancelled'];
                $stLabel = ['confirmed' => 'Confirmed', 'pending' => 'Pending', 'cancelled' => 'Cancelled'];
                $stCls = $stMap[$booking['status']] ?? 'status-pending';
                $stTxt = $stLabel[$booking['status']] ?? ucfirst($booking['status']);
                ?>
                <span class="<?= $stCls ?>"><?= $stTxt ?></span>
            </span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Status Check-in</span>
            <span class="bd-info-value">
                <?php if ($isCheckedIn): ?>
                    <span class="status-confirmed">
                        Sudah Hadir — <?= date('H:i', strtotime($booking['checkin_time'])) ?> WIB
                    </span>
                <?php else: ?>
                    <span class="status-pending">Belum Hadir</span>
                <?php endif; ?>
            </span>
        </div>
        <?php if (!empty($booking['catatan'])): ?>
            <div class="bd-info-row" style="align-items: flex-start;">
                <span class="bd-info-label">Catatan</span>
                <span class="bd-info-value"
                    style="word-break: break-word;"><?= htmlspecialchars($booking['catatan']) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Customer Info Card -->
    <div class="bd-info-card">
        <div class="bd-info-header">
            <span class="material-symbols-outlined" style="color: var(--blue);">person</span>
            Informasi Pemesan
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Nama</span>
            <span class="bd-info-value"><?= htmlspecialchars($booking['nama_lengkap']) ?></span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Email</span>
            <span class="bd-info-value" style="word-break: break-all;"><?= htmlspecialchars($booking['email']) ?></span>
        </div>
        <div class="bd-info-row">
            <span class="bd-info-label">Nomor HP</span>
            <span class="bd-info-value"><?= htmlspecialchars($booking['nomor_telepon'] ?? '-') ?></span>
        </div>
    </div>

</div>

<script>
    function copyBookingCode() {
        const code = document.getElementById('bd-code-text').innerText.trim();
        const btn = document.getElementById('btn-copy-code');
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(() => {
                btn.classList.add('copied');
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">check</span> Tersalin!';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">content_copy</span> Copy';
                }, 2200);
            });
        } else {
            // Fallback for older browsers
            const el = document.createElement('textarea');
            el.value = code;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            btn.classList.add('copied');
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">check</span> Tersalin!';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:1rem;">content_copy</span> Copy';
            }, 2200);
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
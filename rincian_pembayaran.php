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

$pageTitle = 'Rincian Pembayaran';
$baseUrl = '';
$errors = [];
$success = '';

$booking_id = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
if (!$booking_id) {
    header('Location: dashboarduser.php');
    exit;
}

// [NEW] Handler pembatalan booking — harus diproses sebelum query utama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'batalkan') {
    // [NEW] Validasi kepemilikan + status sebelum update
    $stmtCek = mysqli_prepare($conn,
        "SELECT id, status FROM bookings WHERE id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($stmtCek, 'ii', $booking_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmtCek);
    $rowCek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCek));
    mysqli_stmt_close($stmtCek);

    $statusBolehBatal = ['pending', 'menunggu'];

    if (!$rowCek) {
        $errors[] = 'Booking tidak ditemukan atau bukan milik Anda.';
    } elseif (!in_array($rowCek['status'], $statusBolehBatal, true)) {
        $errors[] = 'Booking dengan status "' . htmlspecialchars($rowCek['status']) . '" tidak dapat dibatalkan.';
    } else {
        // Call centralized helper to update status and send cancellation email
        updateBookingVerification($conn, $booking_id, 'cancelled');
        header('Location: dashboarduser.php?msg=cancelled');
        exit;
    }
}

// Ambil data booking + lapangan
$stmt = mysqli_prepare($conn,
    "SELECT b.*, c.nama_lapangan, c.tipe_lapangan, c.harga_per_jam, u.nama_lengkap, u.email
     FROM bookings b
     JOIN courts c ON b.court_id = c.id
     JOIN users u ON b.user_id = u.id
     WHERE b.id = ? AND b.user_id = ?"
);
if (!$stmt) {
    die("Query error: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$booking) {
    header('Location: dashboarduser.php');
    exit;
}

// Cek apakah sudah ada pembayaran
$stmtP = mysqli_prepare($conn, "SELECT * FROM payments WHERE booking_id = ?");
if (!$stmtP) {
    die("Query error: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmtP, 'i', $booking_id);
mysqli_stmt_execute($stmtP);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtP));

// Handle AJAX POST for QRIS Demo Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'demo_payment') {
    header('Content-Type: application/json');
    $status = $_POST['status'] ?? '';

    if ($status === 'bayar') {
        // Demo Bayar: Lunas
        if (updateBookingVerification($conn, $booking_id, 'confirmed', null, true, 'QRIS')) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan simulasi pembayaran.']);
        }
    } elseif ($status === 'tidak_bayar') {
        // Demo Tidak Bayar: Gagal
        if (updateBookingVerification($conn, $booking_id, 'rejected_pending', null, true, 'QRIS')) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan simulasi pembatalan.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    }
    exit;
}

// Handle AJAX POST from payment confirmation modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!$payment || $payment['status_verifikasi'] === 'ditolak')
    && ($_POST['action'] ?? '') !== 'batalkan'
    && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');
    $metode = $_POST['metode_bayar'] ?? 'Cash';

    if (empty($metode)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Pilih metode pembayaran.']);
        exit;
    }

    if (updateBookingVerification($conn, $booking_id, 'confirmed', null, true, $metode)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pembayaran.']);
    }
    exit;
}

// Fallback non-AJAX POST (legacy safety)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!$payment || $payment['status_verifikasi'] === 'ditolak') && ($_POST['action'] ?? '') !== 'batalkan') {
    $metode = $_POST['metode_bayar'] ?? '';
    $jumlah = (float)($booking['total_harga']);
    $bukti  = '';

    if (empty($metode)) $errors[] = 'Pilih metode pembayaran.';

    if ($metode === 'QRIS') {
        if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Bukti pembayaran wajib diupload.';
        } else {
            $file = $_FILES['bukti_transfer'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','pdf'])) {
                $errors[] = 'Format file tidak valid.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 2MB.';
            } else {
                $uploadDir = 'uploads/bukti_transfer/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName = 'bukti_' . $booking_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $bukti = $newName;
                } else {
                    $errors[] = 'Gagal mengupload file.';
                }
            }
        }
    }

    if (empty($errors)) {
        if (updateBookingVerification($conn, $booking_id, 'confirmed', null, true, $metode)) {
            $success = 'Pembayaran berhasil dicatat!';
            mysqli_stmt_execute($stmtP);
            $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtP));
        } else {
            $errors[] = 'Gagal menyimpan pembayaran.';
        }
    }
    mysqli_stmt_close($stmtP);
}

$durasi = (strtotime($booking['jam_selesai']) - strtotime($booking['jam_mulai'])) / 3600;
$durasiFormatted = formatDurasi($booking['jam_mulai'], $booking['jam_selesai']);

// [NEW] Tentukan apakah booking masih boleh dibatalkan
$bisaDibatalkan = in_array($booking['status'], ['pending', 'menunggu'], true);
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>Rincian Pembayaran</h1>
        <p>Booking #<?= $booking_id ?> – <?= htmlspecialchars($booking['nama_lapangan']) ?></p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 680px; margin: 0 auto;">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Detail Booking -->
            <div class="card fade-up">
                <h2>Detail Booking</h2>
                <div class="detail-box">
                    <div class="detail-row">
                        <span class="label">ID Booking</span>
                        <span class="value">#<?= $booking['id'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Nama</span>
                        <span class="value"><?= htmlspecialchars($booking['nama_lengkap']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Lapangan</span>
                        <span class="value"><?= htmlspecialchars($booking['nama_lapangan']) ?> (<?= $booking['tipe_lapangan'] ?>)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Tanggal</span>
                        <span class="value"><?= date('d F Y', strtotime($booking['tanggal_booking'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Jam</span>
                        <span class="value"><?= $booking['jam_mulai'] ?> – <?= $booking['jam_selesai'] ?> (<?= $durasiFormatted ?>)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Paket</span>
                        <span class="value"><?= $booking['paket'] === 'per_jam' ? 'Per Jam' : 'Per Match' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Sewa Raket</span>
                        <span class="value"><?= $booking['sewa_raket'] ? 'Ya (+Rp 50.000)' : 'Tidak' ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status Booking</span>
                        <span class="value">
                            <span class="status-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
                        </span>
                    </div>
                    <?php if ($booking['catatan']): ?>
                    <div class="detail-row">
                        <span class="label">Catatan</span>
                        <span class="value"><?= htmlspecialchars($booking['catatan']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row total">
                        <span class="label">Total Pembayaran</span>
                        <span class="value">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Status Pembayaran / Form -->
            <?php if ($payment && $payment['status_verifikasi'] !== 'ditolak'): ?>
                <div class="card fade-up">
                    <h2>Status Pembayaran</h2>
                    <div class="detail-box">
                        <div class="detail-row">
                            <span class="label">Metode</span>
                            <span class="value">
                                <?php if ($payment['metode_bayar'] === 'QRIS'): ?>
                                    <span class="badge" style="background:#EEF6FF; color:#0EA5E9; font-weight:700; font-size:0.8rem; padding:3px 10px; border-radius:6px;">QRIS</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($payment['metode_bayar']) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Jumlah Dibayar</span>
                            <span class="value">Rp <?= number_format($payment['jumlah_bayar'], 0, ',', '.') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Waktu Bayar</span>
                            <span class="value"><?= date('d F Y H:i', strtotime($payment['waktu_bayar'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Verifikasi</span>
                            <span class="value">
                                <?php
                                $sv = $payment['status_verifikasi'];
                                $cls = $sv === 'terverifikasi' ? 'confirmed' : ($sv === 'ditolak' ? 'cancelled' : 'pending');
                                ?>
                                <span class="status-<?= $cls ?>"><?= ucfirst($sv) ?></span>
                            </span>
                        </div>
                        <?php if ($payment['bukti_transfer']): ?>
                        <div class="detail-row">
                            <span class="label">Bukti Pembayaran</span>
                            <span class="value">
                                <a href="uploads/bukti_transfer/<?= htmlspecialchars($payment['bukti_transfer']) ?>"
                                   target="_blank" class="btn btn-sm btn-secondary">Lihat Bukti</a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-<?= $payment['status_verifikasi'] === 'terverifikasi' ? 'success' : 'info' ?>" style="margin-top: 16px;">
                        <?php if ($payment['status_verifikasi'] === 'terverifikasi'): ?>
                            Pembayaran Anda telah berhasil diverifikasi. Terima kasih!
                        <?php else: ?>
                            Pembayaran Anda sedang dalam proses verifikasi admin. Terima kasih!
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Form Upload Pembayaran — submit via modal confirmation -->
                

                <div class="card fade-up">
                    <h2>Konfirmasi Pembayaran</h2>
                    <form id="form-payment" novalidate enctype="multipart/form-data">

                        <div class="form-group">
                            <label for="metode_bayar">Metode Pembayaran</label>
                            <select id="metode_bayar" name="metode_bayar" required onchange="toggleBukti(this.value)">
                                <option value="">-- Pilih Metode --</option>
                                <option value="QRIS">QRIS Dummy</option>
                                <option value="Cash">Cash (Bayar di Tempat)</option>
                            </select>
                        </div>

                        <div id="area-bukti" style="display:none; margin-top: 20px;">
                            <div class="qris-panel" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid var(--border); border-radius: 16px; padding: 24px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.05); margin-bottom: 20px;">
                                
                                <div style="text-align: center; margin-bottom: 20px; background: linear-gradient(135deg, #0EA5E9 0%, #2563EB 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                    <h3 style="font-size: 1.35rem; font-weight: 800; margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                                        <span class="material-symbols-outlined" style="-webkit-text-fill-color: #0ea5e9;">qr_code_2</span> Pembayaran QRIS
                                    </h3>
                                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; -webkit-text-fill-color: initial;">Silakan scan QR Code di bawah untuk menyelesaikan pembayaran</p>
                                </div>

                                <div class="qris-panel-body">
                                    <!-- QR Column -->
                                    <div class="qris-qr-col">
                                        <div class="qris-image-wrapper" style="background: #fff; padding: 12px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid var(--border); display: inline-block;">
                                            <img src="assets/images/qris.png" alt="QRIS QR Code" style="width: 180px; height: 180px; display: block; object-fit: contain;">
                                        </div>
                                    </div>

                                    <!-- Nominal Row -->
                                    <div class="qris-nominal-row" style="margin-top: 10px; font-size: 1.15rem; font-weight: 800; color: #22C55E;">
                                        Nominal: Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?>
                                    </div>

                                    <!-- Countdown Row -->
                                    <div class="qris-countdown-row" style="margin-top: 4px;">
                                        Status: <span id="qris-status-badge" class="qris-badge-pending">Belum Dibayar</span>
                                        <span style="margin: 0 8px; color: var(--border);">|</span>
                                        Expired: <span id="qris-timer" class="qris-timer-alert">15:00</span>
                                    </div>

                                    <!-- Demo Row -->
                                    <div class="qris-demo-row" style="border-top: 1px solid var(--border); padding-top: 20px; margin-top: 10px;">
                                        <div style="margin: 0; text-align: center;">
                                            <label style="font-weight: 700; color: var(--navy); margin-bottom: 12px; display: block; font-size: 0.95rem;">
                                                Demo Pembayaran
                                            </label>
                                            <div class="btn-demo-group">
                                                <button type="button" class="btn btn-success" style="flex: 1; padding: 12px; font-weight: 700; font-size: 0.9rem;" onclick="processDemoPayment('bayar')">
                                                    <span class="material-symbols-outlined" style="margin-right: 4px; font-size: 1.1rem; vertical-align: middle;">check_circle</span> Demo Bayar
                                                </button>
                                                <button type="button" class="btn btn-danger" style="flex: 1; padding: 12px; font-weight: 700; font-size: 0.9rem;" onclick="processDemoPayment('tidak_bayar')">
                                                    <span class="material-symbols-outlined" style="margin-right: 4px; font-size: 1.1rem; vertical-align: middle;">cancel</span> Demo Tidak Bayar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Info Column -->
                                    <div class="qris-info-col">
                                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 6px;">
                                            <span class="material-symbols-outlined" style="font-size: 1.1rem; color: var(--blue);">info</span> Informasi Booking
                                        </h4>
                                        <div class="detail-box" style="margin-top: 10px; padding: 14px; border-radius: 12px; background: rgba(0,0,0,0.02); font-size: 0.85rem;">
                                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;"><span style="color:var(--text-muted);">Booking ID:</span><strong>#<?= $booking['id'] ?></strong></div>
                                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;"><span style="color:var(--text-muted);">Invoice:</span><strong style="font-family: monospace;">INV<?= date('ymd', strtotime($booking['created_at'])) ?><?= sprintf('%03d', $booking['id']) ?></strong></div>
                                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;"><span style="color:var(--text-muted);">Customer:</span><strong><?= htmlspecialchars($booking['nama_lengkap']) ?></strong></div>
                                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;"><span style="color:var(--text-muted);">Lapangan:</span><strong><?= htmlspecialchars($booking['nama_lapangan']) ?></strong></div>
                                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;"><span style="color:var(--text-muted);">Tanggal:</span><strong><?= date('d M Y', strtotime($booking['tanggal_booking'])) ?></strong></div>
                                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;"><span style="color:var(--text-muted);">Jam:</span><strong><?= substr($booking['jam_mulai'],0,5) ?> - <?= substr($booking['jam_selesai'],0,5) ?> WIB</strong></div>
                                            <div style="display:flex; justify-content:space-between; margin-bottom: 6px;"><span style="color:var(--text-muted);">Durasi:</span><strong><?= $durasiFormatted ?></strong></div>
                                            <div style="display:flex; justify-content:space-between; border-top: 1px solid var(--border); padding-top:6px; margin-top:4px;"><span style="color:var(--text-muted); font-weight:bold;">Total Pembayaran:</span><strong style="color: #0EA5E9; font-size: 1.05rem;">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <a href="dashboarduser.php" class="btn btn-outline">Nanti Saja</a>
                            <button type="button" class="btn btn-success" id="btn-show-pay-modal" style="flex:1; display:none;"
                                    onclick="showPaymentConfirmModal()">
                                <span class="material-symbols-outlined">payments</span>
                                Konfirmasi Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- [NEW] Tombol aksi bawah halaman -->
            <div style="display: flex; gap: 12px; justify-content: center; margin-top: 10px; flex-wrap: wrap;">
                <a href="dashboarduser.php" class="btn btn-secondary">
                    <span class="material-symbols-outlined">receipt_long</span>
                    Lihat Riwayat Booking
                </a>
                <?php if ($bisaDibatalkan): ?>
                    <!-- [NEW] Tombol batalkan — hanya muncul jika status pending/menunggu -->
                    <button type="button" class="btn btn-danger" onclick="openModal('modal-batalkan')">
                        <span class="material-symbols-outlined">cancel</span>
                        Batalkan Pesanan
                    </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════
     MODAL — PAYMENT CONFIRMATION
════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-payment-confirm" role="dialog" aria-modal="true" aria-labelledby="modal-pay-title" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modal-pay-title">Konfirmasi Pembayaran</h3>
            <button class="modal-close" onclick="closeModal('modal-payment-confirm')" aria-label="Tutup">&times;</button>
        </div>
        <div class="modal-body">
            <p>Apakah Anda yakin ingin mengirimkan konfirmasi pembayaran ini?</p>
            <div class="modal-summary-card">
                <div class="detail-row">
                    <span class="label">Booking</span>
                    <span class="value">#<?= $booking_id ?> — <?= htmlspecialchars($booking['nama_lapangan']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Metode</span>
                    <span class="value" id="modal-pay-metode">–</span>
                </div>
                <div class="detail-row total">
                    <span class="label">Total</span>
                    <span class="value">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modal-payment-confirm')">Batal</button>
            <button class="btn btn-success" id="btn-confirm-payment" onclick="submitPayment()">
                <span class="material-symbols-outlined">check_circle</span>
                Konfirmasi Pembayaran
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL — PAYMENT SUCCESS
════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-payment-success" role="dialog" aria-modal="true" aria-labelledby="modal-pay-success-title" style="display:none;">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-success-body">
            <div class="success-circle">
                <span class="material-symbols-outlined checkmark" style="font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 24;">check_circle</span>
            </div>
            <h3 id="modal-pay-success-title">Pembayaran Dikonfirmasi!</h3>
            <p>Konfirmasi pembayaran Anda telah berhasil terkirim. Tim kami akan segera memverifikasi pembayaran Anda.</p>
            <button class="btn btn-primary btn-block" id="btn-done-payment">
                <span class="material-symbols-outlined">home</span>
                Kembali ke Dashboard
            </button>
        </div>
    </div>
</div>

<!-- [NEW] ═══════════════════════════════════════════════
     MODAL — KONFIRMASI PEMBATALAN BOOKING
════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-batalkan" role="dialog" aria-modal="true" aria-labelledby="modal-batal-title" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modal-batal-title">Batalkan Pesanan</h3>
            <button class="modal-close" onclick="closeModal('modal-batalkan')" aria-label="Tutup">&times;</button>
        </div>
        <div class="modal-body">

            <!-- [NEW] Peringatan merah di atas -->
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <strong>Perhatian!</strong> Pembatalan tidak dapat diurungkan. Apakah kamu yakin ingin membatalkan pesanan ini?
            </div>

            <!-- [NEW] Ringkasan detail booking di dalam modal -->
            <div class="modal-summary-card">
                <div class="detail-row">
                    <span class="label">ID Booking</span>
                    <span class="value">#<?= $booking['id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Lapangan</span>
                    <span class="value">
                        <?= htmlspecialchars($booking['nama_lapangan']) ?>
                        <span class="badge badge-<?= strtolower($booking['tipe_lapangan']) ?>" style="margin-left:6px;">
                            <?= $booking['tipe_lapangan'] ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Tanggal</span>
                    <span class="value"><?= date('d F Y', strtotime($booking['tanggal_booking'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Jam Main</span>
                    <span class="value"><?= $booking['jam_mulai'] ?> – <?= $booking['jam_selesai'] ?> (<?= $durasiFormatted ?>)</span>
                </div>
                <div class="detail-row">
                    <span class="label">Paket</span>
                    <span class="value"><?= $booking['paket'] === 'per_jam' ? 'Per Jam' : 'Per Match' ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Status Saat Ini</span>
                    <span class="value">
                        <span class="status-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
                    </span>
                </div>
                <div class="detail-row total">
                    <span class="label">Total Harga</span>
                    <span class="value">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                </div>
            </div>

        </div>

        <!-- [NEW] Footer modal dengan form POST batalkan -->
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-batalkan')">
                <span class="material-symbols-outlined">arrow_back</span>
                Kembali
            </button>
            <form method="POST" action="rincian_pembayaran.php?booking_id=<?= $booking_id ?>" style="flex:1;">
                <input type="hidden" name="action" value="batalkan">
                <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                <button type="submit" class="btn btn-danger btn-block">
                    <span class="material-symbols-outlined">cancel</span>
                    Ya, Batalkan
                </button>
            </form>
        </div>

    </div>
</div>

<script>
function toggleBukti(val) {
    document.getElementById('area-bukti').style.display = val === 'QRIS' ? 'block' : 'none';
    document.getElementById('btn-show-pay-modal').style.display = val === 'Cash' ? 'block' : 'none';
}

// Countdown timer (15 minutes = 900 seconds)
let timeLeft = 900;
const timerEl = document.getElementById('qris-timer');
const statusBadgeEl = document.getElementById('qris-status-badge');

let timerInterval;
if (timerEl) {
    function updateTimer() {
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = 'Expired';
            if (statusBadgeEl) {
                statusBadgeEl.textContent = 'Expired';
                statusBadgeEl.className = 'qris-badge-expired';
            }
            const btnSuccess = document.querySelector('.btn-demo-group .btn-success');
            const btnDanger = document.querySelector('.btn-demo-group .btn-danger');
            if (btnSuccess) btnSuccess.disabled = true;
            if (btnDanger) btnDanger.disabled = true;
            return;
        }
        
        timeLeft--;
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerEl.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    timerInterval = setInterval(updateTimer, 1000);
    updateTimer();
}

function processDemoPayment(status) {
    if (timeLeft <= 0) {
        showToast('Waktu pembayaran QRIS telah kedaluwarsa. Silakan refresh halaman untuk mengulang.', 'error');
        return;
    }
    
    const btnSuccess = document.querySelector('.btn-demo-group .btn-success');
    const btnDanger = document.querySelector('.btn-demo-group .btn-danger');
    if (btnSuccess) btnSuccess.disabled = true;
    if (btnDanger) btnDanger.disabled = true;

    const formData = new FormData();
    formData.append('action', 'demo_payment');
    formData.append('status', status);

    fetch('rincian_pembayaran.php?booking_id=<?= $booking_id ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (status === 'bayar') {
                setTimeout(() => {
                    document.getElementById('modal-pay-success-title').textContent = 'Pembayaran Berhasil!';
                    document.querySelector('#modal-payment-success p').textContent = 'Simulasi pembayaran QRIS Anda berhasil dicatat sebagai Lunas.';
                    openModal('modal-payment-success');
                }, 100);
            } else {
                showToast('Simulasi Pembayaran Gagal.', 'error');
                setTimeout(() => {
                    window.location.href = 'dashboarduser.php';
                }, 1500);
            }
        } else {
            showToast(data.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
            if (btnSuccess) btnSuccess.disabled = false;
            if (btnDanger) btnDanger.disabled = false;
        }
    })
    .catch(() => {
        showToast('Koneksi bermasalah. Silakan coba lagi.', 'error');
        if (btnSuccess) btnSuccess.disabled = false;
        if (btnDanger) btnDanger.disabled = false;
    });
}

function showPaymentConfirmModal() {
    const metode = document.getElementById('metode_bayar');
    if (!metode || !metode.value) {
        showToast('Silakan pilih metode pembayaran terlebih dahulu.', 'warning');
        return;
    }
    document.getElementById('modal-pay-metode').textContent = 'Cash di Tempat';
    openModal('modal-payment-confirm');
}

function submitPayment() {
    const btn = document.getElementById('btn-confirm-payment');
    btn.classList.add('btn-loading');
    btn.disabled = true;

    const formData = new FormData(document.getElementById('form-payment'));

    fetch('rincian_pembayaran.php?booking_id=<?= $booking_id ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.classList.remove('btn-loading');
        btn.disabled = false;

        if (data.success) {
            closeModal('modal-payment-confirm');
            setTimeout(() => openModal('modal-payment-success'), 350);
        } else {
            closeModal('modal-payment-confirm');
            showToast(data.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
        }
    })
    .catch(() => {
        btn.classList.remove('btn-loading');
        btn.disabled = false;
        showToast('Koneksi bermasalah. Silakan coba lagi.', 'error');
    });
}

document.getElementById('btn-done-payment') && document.getElementById('btn-done-payment').addEventListener('click', function() {
    window.location.href = 'dashboarduser.php';
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
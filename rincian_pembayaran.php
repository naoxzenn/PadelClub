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

// Helper untuk memetakan status booking & rincian status
function getBookingStatusDetailsForDetail($b, $p) {
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
    if ($p) {
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


$pageTitle = 'Rincian Pembayaran';
$baseUrl = '';
$errors = [];
$success = '';

$booking_id = (int)($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    header('Location: dashboarduser.php');
    exit;
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

// Handle AJAX POST from payment confirmation modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$payment
    && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');

    $metode = $_POST['metode_bayar'] ?? '';
    $jumlah = (float)($booking['total_harga']);
    $bukti  = '';

    if (empty($metode)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Pilih metode pembayaran.']);
        exit;
    }

    if ($metode === 'Transfer') {
        if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Bukti transfer wajib diupload untuk metode Transfer.']);
            exit;
        }
        $file = $_FILES['bukti_transfer'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Format file tidak valid. Gunakan JPG, PNG, atau PDF.']);
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB.']);
            exit;
        }
        $uploadDir = 'uploads/bukti_transfer/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $newName = 'bukti_' . $booking_id . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengupload file. Coba lagi.']);
            exit;
        }
        $bukti = $newName;
    }

    $stmt2 = mysqli_prepare($conn,
        "INSERT INTO payments (booking_id, jumlah_bayar, metode_bayar, bukti_transfer) VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt2, 'idss', $booking_id, $jumlah, $metode, $bukti);
    if (mysqli_stmt_execute($stmt2)) {
        if ($metode === 'Cash') {
            mysqli_query($conn, "UPDATE bookings SET status='confirmed' WHERE id=$booking_id");
        }
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pembayaran.']);
    }
    exit;
}

// Fallback non-AJAX POST (legacy safety)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$payment) {
    $metode = $_POST['metode_bayar'] ?? '';
    $jumlah = (float)($booking['total_harga']);
    $bukti  = '';

    if (empty($metode)) $errors[] = 'Pilih metode pembayaran.';

    if ($metode === 'Transfer') {
        if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Bukti transfer wajib diupload.';
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
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO payments (booking_id, jumlah_bayar, metode_bayar, bukti_transfer) VALUES (?, ?, ?, ?)"
        );
        if ($stmt2) {
            mysqli_stmt_bind_param($stmt2, 'idss', $booking_id, $jumlah, $metode, $bukti);
            if (mysqli_stmt_execute($stmt2)) {
                if ($metode === 'Cash') {
                    mysqli_query($conn, "UPDATE bookings SET status='confirmed' WHERE id=$booking_id");
                }
                $success = 'Pembayaran berhasil dicatat!';
                mysqli_stmt_execute($stmtP);
                $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtP));
            } else {
                $errors[] = 'Gagal menyimpan pembayaran.';
            }
            mysqli_stmt_close($stmt2);
        } else {
            $errors[] = 'Gagal memproses pembayaran: ' . mysqli_error($conn);
        }
    }
    mysqli_stmt_close($stmtP);
}

$durasi = (strtotime($booking['jam_selesai']) - strtotime($booking['jam_mulai'])) / 3600;
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Bootstrap Icons CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

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
                        <span class="value"><?= $booking['jam_mulai'] ?> – <?= $booking['jam_selesai'] ?> (<?= $durasi ?> jam)</span>
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
                            <?php 
                            $statusDetails = getBookingStatusDetailsForDetail($booking, $payment);
                            ?>
                            <span class="<?= $statusDetails['class'] ?>">
                                <i class="bi <?= $statusDetails['icon'] ?> me-1"></i> <?= $statusDetails['label'] ?>
                            </span>
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
            <?php if ($payment): ?>
                <div class="card fade-up">
                    <h2>Status Pembayaran</h2>
                    <div class="detail-box">
                        <div class="detail-row">
                            <span class="label">Metode</span>
                            <span class="value"><?= $payment['metode_bayar'] ?></span>
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
                            <span class="label">Bukti Transfer</span>
                            <span class="value">
                                <a href="uploads/bukti_transfer/<?= htmlspecialchars($payment['bukti_transfer']) ?>"
                                   target="_blank" class="btn btn-sm btn-secondary">Lihat Bukti</a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-info" style="margin-top: 16px;">
                        Pembayaran Anda sedang dalam proses verifikasi admin. Terima kasih!
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
                                <option value="Transfer">Transfer Bank</option>
                                <option value="Cash">Cash (Bayar di Tempat)</option>
                            </select>
                        </div>

                        <div id="area-bukti" style="display:none;">
                            <div class="form-group">
                                <label for="bukti_transfer">Upload Bukti Transfer</label>
                                <div class="upload-area">
                                    <span class="material-symbols-outlined" style="font-size:2rem; color:var(--blue); opacity:.6;">upload_file</span>
                                    <p>Klik atau seret file ke sini</p>
                                    <input type="file" id="bukti_transfer" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf">
                                    <p style="margin-top:6px;">Format: JPG, PNG, PDF – Maks. 2MB</p>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <strong>Info Transfer:</strong><br>
                                Bank BCA – 1234567890 – a/n PadelClub<br>
                                Nominal: Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <a href="dashboarduser.php" class="btn btn-outline">Nanti Saja</a>
                            <button type="button" class="btn btn-success" id="btn-show-pay-modal" style="flex:1;"
                                    onclick="showPaymentConfirmModal()">
                                <span class="material-symbols-outlined">payments</span>
                                Konfirmasi Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 10px;">
                <a href="dashboarduser.php" class="btn btn-secondary">Lihat Riwayat Booking</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════
     MODAL 3 — PAYMENT CONFIRMATION
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
     MODAL 4 — PAYMENT SUCCESS
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

<script>
function toggleBukti(val) {
    document.getElementById('area-bukti').style.display = val === 'Transfer' ? 'block' : 'none';
}

function showPaymentConfirmModal() {
    const metode = document.getElementById('metode_bayar');
    if (!metode || !metode.value) {
        showToast('Silakan pilih metode pembayaran terlebih dahulu.', 'warning');
        return;
    }
    if (metode.value === 'Transfer') {
        const bukti = document.getElementById('bukti_transfer');
        if (!bukti || !bukti.files.length) {
            showToast('Silakan upload bukti transfer terlebih dahulu.', 'warning');
            return;
        }
    }
    document.getElementById('modal-pay-metode').textContent = metode.value === 'Transfer' ? 'Transfer Bank' : 'Cash di Tempat';
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

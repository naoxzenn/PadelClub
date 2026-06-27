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
if (!isset($_SESSION['booking_draft'])) {
    header('Location: booking.php');
    exit;
}
require_once __DIR__ . '/config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Pilih Paket';
$baseUrl = '';
$draft = $_SESSION['booking_draft'];
$errors = [];

// Ambil detail lapangan
$stmt = mysqli_prepare($conn, "SELECT * FROM courts WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $draft['court_id']);
    mysqli_stmt_execute($stmt);
    $court = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
} else {
    die("Query error: " . mysqli_error($conn));
}

if (!$court) {
    header('Location: booking.php');
    exit;
}

// Hitung durasi (jam)
$mulai  = strtotime($draft['jam_mulai']);
$selesai = strtotime($draft['jam_selesai']);
$durasi_jam = ($selesai - $mulai) / 3600;

// Harga per_match tetap
define('HARGA_PER_MATCH', 250000);
define('HARGA_SEWA_RAKET', 50000);

$total = 0;
$booking_id_created = null;

// Handle AJAX POST (modal confirmation submits via fetch)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmed']) && $_POST['confirmed'] === '1') {
    $paket      = $_POST['paket'] ?? 'per_jam';
    $sewa_raket = isset($_POST['sewa_raket']) ? 1 : 0;
    $catatan    = trim($_POST['catatan'] ?? '');

    if ($paket === 'per_jam') {
        $total = $court['harga_per_jam'] * $durasi_jam;
    } else {
        $total = HARGA_PER_MATCH;
    }
    if ($sewa_raket) $total += HARGA_SEWA_RAKET;

    $user_id = $_SESSION['user_id'];
    $stmt2 = mysqli_prepare($conn,
        "INSERT INTO bookings (user_id, court_id, tanggal_booking, jam_mulai, jam_selesai, total_harga, paket, sewa_raket, catatan, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, 'iisssdiss',
            $user_id, $draft['court_id'],
            $draft['tanggal'], $draft['jam_mulai'], $draft['jam_selesai'],
            $total, $paket, $sewa_raket, $catatan
        );

        if (mysqli_stmt_execute($stmt2)) {
            $booking_id_created = mysqli_insert_id($conn);
            $_SESSION['booking_draft'] = null;
            unset($_SESSION['booking_draft']);
            // Return JSON for AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'booking_id' => $booking_id_created]);
                exit;
            }
            // Fallback: redirect
            header("Location: rincian_pembayaran.php?booking_id=$booking_id_created");
            exit;
        } else {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan booking. Coba lagi.']);
                exit;
            }
            $errors[] = 'Gagal menyimpan booking. Coba lagi.';
        }
        mysqli_stmt_close($stmt2);
    } else {
        $errors[] = 'Gagal memproses booking: ' . mysqli_error($conn);
    }
}

// Preview harga
$paket_preview = $_POST['paket'] ?? 'per_jam';
$raket_preview = isset($_POST['sewa_raket']) ? 1 : 0;
if ($paket_preview === 'per_jam') {
    $total_preview = $court['harga_per_jam'] * $durasi_jam;
} else {
    $total_preview = HARGA_PER_MATCH;
}
if ($raket_preview) $total_preview += HARGA_SEWA_RAKET;
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>Pilih Paket Bermain</h1>
        <p>Tentukan paket dan opsi tambahan sesuai kebutuhan Anda</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div style="max-width: 680px; margin: 0 auto;">

            <!-- Ringkasan booking -->
            <div class="card fade-up">
                <h2>Ringkasan Booking</h2>
                <div class="detail-box">
                    <div class="detail-row">
                        <span class="label">Lapangan</span>
                        <span class="value"><?= htmlspecialchars($court['nama_lapangan']) ?> (<?= $court['tipe_lapangan'] ?>)</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Tanggal</span>
                        <span class="value"><?= date('d F Y', strtotime($draft['tanggal'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Jam</span>
                        <span class="value"><?= $draft['jam_mulai'] ?> – <?= $draft['jam_selesai'] ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Durasi</span>
                        <span class="value"><?= $durasi_jam ?> jam</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Harga/Jam</span>
                        <span class="value">Rp <?= number_format($court['harga_per_jam'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <div>• <?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Form Paket -->
            <div class="card fade-up">
                <h2>Pilih Paket &amp; Tambahan</h2>
                <!-- Note: form does NOT submit directly — JS intercepts and shows modal -->
                <form id="form-paket" novalidate>

                    <div class="form-group">
                        <label for="paket">Jenis Paket</label>
                        <select id="paket" name="paket" onchange="hitungTotal()" required>
                            <option value="per_jam" <?= ($_POST['paket'] ?? '') !== 'per_match' ? 'selected' : '' ?>>
                                Per Jam – Rp <?= number_format($court['harga_per_jam'], 0, ',', '.') ?>/jam
                                (Total: Rp <?= number_format($court['harga_per_jam'] * $durasi_jam, 0, ',', '.') ?>)
                            </option>
                            <option value="per_match" <?= ($_POST['paket'] ?? '') === 'per_match' ? 'selected' : '' ?>>
                                Per Match – Harga tetap Rp <?= number_format(HARGA_PER_MATCH, 0, ',', '.') ?>
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="sewa_raket" name="sewa_raket" value="1"
                                   onchange="hitungTotal()"
                                   <?= isset($_POST['sewa_raket']) ? 'checked' : '' ?>>
                            Sewa Raket (+Rp <?= number_format(HARGA_SEWA_RAKET, 0, ',', '.') ?>)
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="catatan">Catatan (opsional)</label>
                        <textarea id="catatan" name="catatan" rows="3"
                                  placeholder="Contoh: minta bola ekstra, dll."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
                    </div>

                    <!-- Total Harga Preview -->
                    <div class="detail-box" id="preview-total" style="margin-bottom:20px;">
                        <div class="detail-row total">
                            <span class="label">Estimasi Total</span>
                            <span class="value" id="total-display">
                                Rp <?= number_format($total_preview, 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 8px;">
                        <a href="booking.php" class="btn btn-outline">
                            <span class="material-symbols-outlined">arrow_back</span> Kembali
                        </a>
                        <button type="button" class="btn btn-primary" id="btn-konfirmasi" style="flex: 1;"
                                onclick="showBookingConfirmModal()">
                            Konfirmasi Booking &rarr;
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════
     MODAL 1 — BOOKING CONFIRMATION
════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-booking-confirm" role="dialog" aria-modal="true" aria-labelledby="modal-booking-title" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modal-booking-title">Konfirmasi Booking</h3>
            <button class="modal-close" onclick="closeModal('modal-booking-confirm')" aria-label="Tutup">&times;</button>
        </div>
        <div class="modal-body">
            <p>Apakah Anda yakin ingin membuat booking ini? Pastikan semua detail sudah benar.</p>

            <!-- Dynamic summary injected by JS -->
            <div class="modal-summary-card" id="modal-booking-summary">
                <!-- filled by JS -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('modal-booking-confirm')">Batal</button>
            <button class="btn btn-primary" id="btn-confirm-booking" onclick="submitBooking()">
                <span class="material-symbols-outlined">check_circle</span>
                Konfirmasi Booking
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL 2 — BOOKING SUCCESS
════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modal-booking-success" role="dialog" aria-modal="true" aria-labelledby="modal-success-title" style="display:none;">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-success-body">
            <div class="success-circle">
                <span class="material-symbols-outlined checkmark" style="font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 24;">check_circle</span>
            </div>
            <h3 id="modal-success-title">Booking Berhasil Dibuat!</h3>
            <p>Booking Anda telah berhasil dibuat. Silakan lanjutkan ke halaman pembayaran untuk menyelesaikan transaksi.</p>
            <button class="btn btn-primary btn-block" id="btn-go-payment">
                <span class="material-symbols-outlined">payments</span>
                Lihat Rincian Pembayaran
            </button>
        </div>
    </div>
</div>

<script>
const hargaPerJam   = <?= $court['harga_per_jam'] ?>;
const durasiJam     = <?= $durasi_jam ?>;
const hargaPerMatch = <?= HARGA_PER_MATCH ?>;
const hargaRaket    = <?= HARGA_SEWA_RAKET ?>;

const courtName = <?= json_encode($court['nama_lapangan']) ?>;
const tanggal   = <?= json_encode(date('d F Y', strtotime($draft['tanggal']))) ?>;
const jamMulai  = <?= json_encode($draft['jam_mulai']) ?>;
const jamSelesai= <?= json_encode($draft['jam_selesai']) ?>;

let createdBookingId = null;

function hitungTotal() {
    const paket = document.getElementById('paket').value;
    const raket = document.getElementById('sewa_raket').checked;
    let total = paket === 'per_jam' ? hargaPerJam * durasiJam : hargaPerMatch;
    if (raket) total += hargaRaket;
    document.getElementById('total-display').innerText = 'Rp ' + total.toLocaleString('id-ID');
}

function formatRp(n) {
    return 'Rp ' + n.toLocaleString('id-ID');
}

function showBookingConfirmModal() {
    const paket = document.getElementById('paket').value;
    const raket = document.getElementById('sewa_raket').checked;
    let total = paket === 'per_jam' ? hargaPerJam * durasiJam : hargaPerMatch;
    if (raket) total += hargaRaket;

    const paketLabel = paket === 'per_jam'
        ? 'Per Jam – ' + formatRp(hargaPerJam) + '/jam'
        : 'Per Match – ' + formatRp(hargaPerMatch);

    const rows = [
        ['Lapangan',  courtName + ' (<?= $court['tipe_lapangan'] ?>)'],
        ['Paket',     paketLabel],
        ['Tanggal',   tanggal],
        ['Jam',       jamMulai + ' – ' + jamSelesai],
        ['Durasi',    durasiJam + ' jam'],
        ['Sewa Raket',raket ? 'Ya (+' + formatRp(hargaRaket) + ')' : 'Tidak'],
    ];

    let html = rows.map(([l, v]) =>
        `<div class="detail-row"><span class="label">${l}</span><span class="value">${v}</span></div>`
    ).join('');
    html += `<div class="detail-row total"><span class="label">Total</span><span class="value">${formatRp(total)}</span></div>`;

    document.getElementById('modal-booking-summary').innerHTML = html;
    openModal('modal-booking-confirm');
}

function submitBooking() {
    const btn = document.getElementById('btn-confirm-booking');
    btn.classList.add('btn-loading');
    btn.disabled = true;

    const paket     = document.getElementById('paket').value;
    const sewRaket  = document.getElementById('sewa_raket').checked ? '1' : '';
    const catatan   = document.getElementById('catatan').value;

    const body = new URLSearchParams({
        confirmed: '1',
        paket: paket,
        catatan: catatan,
    });
    if (sewRaket) body.append('sewa_raket', '1');

    fetch('pilih_paket.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
        btn.classList.remove('btn-loading');
        btn.disabled = false;

        if (data.success) {
            createdBookingId = data.booking_id;
            closeModal('modal-booking-confirm');
            setTimeout(() => openModal('modal-booking-success'), 350);
        } else {
            closeModal('modal-booking-confirm');
            showToast(data.message || 'Terjadi kesalahan. Silakan coba lagi.', 'error');
        }
    })
    .catch(() => {
        btn.classList.remove('btn-loading');
        btn.disabled = false;
        showToast('Koneksi bermasalah. Silakan coba lagi.', 'error');
    });
}

document.getElementById('btn-go-payment').addEventListener('click', function() {
    if (createdBookingId) {
        window.location.href = 'rincian_pembayaran.php?booking_id=' + createdBookingId;
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

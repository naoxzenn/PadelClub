<?php
// kasir/checkin.php - QR Scanner Check-in untuk Kasir

session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'], true)) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

$pageTitle = 'QR Check-in Kasir';
$baseUrl = '../';

include __DIR__ . '/../includes/header.php';
?>


<?php
// Load stats
$model = new BookingModel($pdo);
$stats = $model->getCheckinStats();
?>

<div class="dashboard-content" style="padding: 24px;">

    <!-- Page Title -->
    <div style="margin-bottom: 24px;">
        <h1
            style="font-size: 1.6rem; font-weight: 800; color: var(--navy); margin-bottom: 4px; display: flex; align-items: center; gap: 10px;">
            <span class="material-symbols-outlined"
                style="font-size: 1.8rem; color: var(--blue);">qr_code_scanner</span>
            QR Check-in
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9rem;">Scan QR Code atau input kode booking untuk konfirmasi
            kehadiran pelanggan</p>
    </div>

    <!-- Stats -->
    <div class="ci-stats">
        <div class="ci-stat-card">
            <div class="ci-stat-num" style="color: var(--blue);"><?= $stats['total_today'] ?></div>
            <div class="ci-stat-label">Booking Hari Ini</div>
        </div>
        <div class="ci-stat-card">
            <div class="ci-stat-num" style="color: var(--green);"><?= $stats['checked_today'] ?></div>
            <div class="ci-stat-label">Sudah Hadir</div>
        </div>
        <div class="ci-stat-card">
            <div class="ci-stat-num" style="color: #F59E0B;"><?= $stats['unchecked_today'] ?></div>
            <div class="ci-stat-label">Belum Hadir</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="qr-grid">

        <!-- LEFT: QR Scanner -->
        <div class="scanner-card">
            <div class="scanner-header">
                <span class="material-symbols-outlined">photo_camera</span>
                Kamera Scanner
            </div>
            <div class="scanner-body">
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">
                    Arahkan kamera ke QR Code pada tiket pelanggan. Klik <strong>Start Scanning</strong> untuk
                    mengaktifkan kamera.
                </p>
                <div id="qr-reader"></div>
                <div class="scanner-status scanning" id="scanner-msg">
                    <span class="material-symbols-outlined"
                        style="font-size: 1.2rem; animation: spin 1.2s linear infinite;">progress_activity</span>
                    Menginisialisasi scanner...
                </div>
            </div>
        </div>

        <!-- RIGHT: Manual Input + Result -->
        <div style="display: flex; flex-direction: column; gap: 20px;">

            <!-- Manual Input -->
            <div class="manual-card">
                <div class="manual-header">
                    <span class="material-symbols-outlined">keyboard</span>
                    Input Manual Kode Booking
                </div>
                <div class="manual-body">
                    <div class="search-input-wrap">
                        <input type="text" id="manual-code-input" class="code-input"
                            placeholder="Contoh: BK20240718ABCD" autocomplete="off" spellcheck="false">
                        <button class="btn-search" onclick="searchByCode()">
                            <span class="material-symbols-outlined">search</span>
                            Cari
                        </button>
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                        Tekan <kbd
                            style="background:var(--surface-alt); padding:1px 5px; border-radius:4px; font-family:monospace;">Enter</kbd>
                        atau klik Cari untuk mencari booking
                    </div>
                </div>
            </div>

            <!-- Result Panel -->
            <div id="booking-result">
                <!-- Filled by JS -->
            </div>

        </div>
    </div>
</div>

<!-- html5-qrcode via CDN -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    const BASE_URL = '<?= $baseUrl ?>';
    const PROCESS_URL = BASE_URL + 'kasir/checkin_process.php';

    // ---- QR Scanner Init ----
    let html5QrCode = null;
    let scannerRunning = false;

    function startScanner() {
        if (html5QrCode && scannerRunning) return;

        html5QrCode = new Html5Qrcode("qr-reader");
        const config = { fps: 10, qrbox: { width: 220, height: 220 }, aspectRatio: 1 };

        setScannerMsg('Memulai kamera...', 'scanning');

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                // QR decoded!
                let token = decodedText;
                // Extract token param 't' or 'code' from scanned URL
                try {
                    const url = new URL(decodedText);
                    const tParam = url.searchParams.get('t') || url.searchParams.get('code');
                    if (tParam) token = tParam;
                } catch (_) { /* not a URL, use as-is */ }

                if (scannerRunning) {
                    html5QrCode.pause(); // Pause to prevent re-trigger
                    setScannerMsg('QR terdeteksi!', 'found');
                    fetchBooking(token, true);
                }
            },
            (errorMessage) => {
                // scan failure — expected, ignore
            }
        ).then(() => {
            scannerRunning = true;
            setScannerMsg('Scanner aktif. Arahkan QR ke kamera.', 'scanning');
        }).catch(err => {
            setScannerMsg('Kamera tidak tersedia. Gunakan input manual.', 'error');
        });
    }

    function setScannerMsg(text, type) {
        const el = document.getElementById('scanner-msg');
        el.textContent = text;
        el.className = 'scanner-status active ' + type;
        if (type === 'scanning') {
            el.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.2rem;">radar</span> ' + text;
        } else if (type === 'found') {
            el.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.2rem;">check_circle</span> ' + text;
        } else if (type === 'error') {
            el.innerHTML = '<span class="material-symbols-outlined" style="font-size:1.2rem;">error</span> ' + text;
        }
    }

    // Auto-start scanner on load
    window.addEventListener('load', () => {
        setTimeout(startScanner, 500);
    });

    // ---- Manual Input ----
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('manual-code-input');
        if (input) {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') searchByCode();
            });
        }
    });

    function searchByCode() {
        const token = document.getElementById('manual-code-input').value.trim();
        if (!token) {
            alert('Masukkan token atau kode booking terlebih dahulu.');
            return;
        }
        fetchBooking(token, false);
    }

    // ---- Fetch Booking Info ----
    function fetchBooking(token, fromScanner) {
        showLoading();
        fetch(PROCESS_URL + '?action=lookup&token=' + encodeURIComponent(token))
            .then(r => r.json())
            .then(data => {
                renderResult(data, token, fromScanner);
            })
            .catch(() => {
                renderError('Gagal terhubung ke server. Coba lagi.');
                if (fromScanner && html5QrCode) {
                    setTimeout(() => html5QrCode.resume(), 3000);
                }
            });
    }

    function showLoading() {
        const el = document.getElementById('booking-result');
        el.style.display = 'block';
        el.innerHTML = `
        <div class="loading-card">
            <span class="material-symbols-outlined" style="font-size:2rem; color:var(--blue); animation:spin 1s linear infinite; display:block; margin-bottom:8px;">progress_activity</span>
            <span style="color:var(--text-muted); font-weight:600; font-size:0.9rem;">Mencari data QR booking...</span>
        </div>`;
    }

    function renderError(msg) {
        const el = document.getElementById('booking-result');
        el.style.display = 'block';
        el.innerHTML = `<div class="result-error">
        <span class="material-symbols-outlined">error</span>
        ${escapeHtml(msg)}
    </div>`;
    }

    function renderResult(data, token, fromScanner) {
        const el = document.getElementById('booking-result');
        el.style.display = 'block';

        if (data.error) {
            el.innerHTML = `<div class="result-error">
            <span class="material-symbols-outlined">error</span>
            ${escapeHtml(data.error)}
        </div>`;
            if (fromScanner && html5QrCode) {
                setTimeout(() => html5QrCode.resume(), 3500);
            }
            return;
        }

        const b = data.booking;
        const isCheckedIn = (b.checkin_status === 'Checked In' || data.is_checked_in);
        const canCheckin = data.can_checkin;

        let badgeHtml = '';
        if (isCheckedIn) {
            badgeHtml = `<span class="checkin-badge badge-ci-done"><span class="material-symbols-outlined" style="font-size:0.9rem;">how_to_reg</span> Sudah Hadir</span>`;
        } else if (!canCheckin) {
            badgeHtml = `<span class="checkin-badge badge-ci-invalid"><span class="material-symbols-outlined" style="font-size:0.9rem;">block</span> Tidak Valid</span>`;
        } else {
            badgeHtml = `<span class="checkin-badge badge-ci-pending"><span class="material-symbols-outlined" style="font-size:0.9rem;">schedule</span> Belum Hadir</span>`;
        }

        let actionHtml = '';
        if (isCheckedIn) {
            const details = data.checkin_details || {};
            actionHtml = `<div class="result-error" style="flex-direction:column; align-items:flex-start; padding: 14px 16px; border-radius: var(--radius-md);">
                <div style="display:flex; align-items:center; gap:8px; font-weight:800; font-size:1rem;">
                    <span class="material-symbols-outlined">cancel</span>
                    QR Sudah Digunakan.
                </div>
                <div style="font-size:0.85rem; margin-top:6px; line-height:1.4;">
                    <div><strong>Tanggal Check-in:</strong> ${escapeHtml(details.tanggal || '-')}</div>
                    <div><strong>Jam Check-in:</strong> ${escapeHtml(details.jam || '-')}</div>
                    <div><strong>Kasir / Petugas:</strong> ${escapeHtml(details.kasir || (b.checkin_by_name || 'Petugas'))}</div>
                </div>
            </div>`;
        } else if (!canCheckin) {
            actionHtml = `<div class="result-error">
            <span class="material-symbols-outlined">block</span>
            ${escapeHtml(data.reason || 'Check-in tidak dapat dilakukan.')}
        </div>`;
            if (fromScanner && html5QrCode) {
                setTimeout(() => html5QrCode.resume(), 4000);
            }
        } else {
            const activeToken = b.checkin_token || token;
            actionHtml = `<button class="btn-confirm-checkin" id="btn-do-checkin" onclick="doCheckin('${escapeHtml(activeToken)}')">
            <span class="material-symbols-outlined">how_to_reg</span>
            Konfirmasi Check-in
        </button>`;
        }

        el.innerHTML = `
        <div class="result-card">
            <div class="result-top">
                <div>
                    <div class="result-name">${escapeHtml(b.customer_name)}</div>
                    <div style="font-family:monospace; font-size:0.8rem; color:var(--text-muted); margin-top:2px;">${escapeHtml(b.booking_code)}</div>
                </div>
                ${badgeHtml}
            </div>
            <div class="result-rows">
                <div class="result-row">
                    <span class="result-label">Lapangan</span>
                    <span class="result-value">${escapeHtml(b.court_name)}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Tanggal</span>
                    <span class="result-value">${escapeHtml(b.tanggal_fmt)}</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Jam Bermain</span>
                    <span class="result-value">${escapeHtml(b.jam_mulai)} – ${escapeHtml(b.jam_selesai)} WIB</span>
                </div>
                <div class="result-row">
                    <span class="result-label">Status Bayar</span>
                    <span class="result-value" style="color:${b.payment_status === 'Verified' ? 'var(--green)' : '#F59E0B'}">
                        ${b.payment_status === 'Verified' ? 'Verified ✓' : b.payment_status}
                    </span>
                </div>
                <div class="result-row">
                    <span class="result-label">Status Check-in</span>
                    <span class="result-value" style="color:${isCheckedIn ? 'var(--green)' : '#F59E0B'}">
                        ${isCheckedIn ? 'Checked In' : 'Not Checked In'}
                    </span>
                </div>
            </div>
            <div class="result-actions">${actionHtml}</div>
        </div>`;
    }

    // ---- Perform Check-in ----
    function doCheckin(token) {
        const btn = document.getElementById('btn-do-checkin');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="animation:spin 1s linear infinite;">progress_activity</span> Memproses...';
        }

        fetch(PROCESS_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=checkin&token=' + encodeURIComponent(token)
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderCheckinSuccess(data.booking);
                    // Resume scanner after 5s
                    if (html5QrCode) {
                        setTimeout(() => {
                            html5QrCode.resume();
                            setScannerMsg('Scanner aktif. Arahkan QR ke kamera.', 'scanning');
                        }, 5000);
                    }
                } else {
                    renderError(data.error || 'Gagal melakukan check-in.');
                }
            })
            .catch(() => renderError('Terjadi kesalahan jaringan.'));
    }

    function renderCheckinSuccess(b) {
        const el = document.getElementById('booking-result');
        el.innerHTML = `
        <div class="result-success" style="flex-direction:column; text-align:center; padding:24px; gap:12px;">
            <span class="material-symbols-outlined" style="font-size:3rem; color:var(--green);">task_alt</span>
            <div>
                <div style="font-size:1.1rem; font-weight:800; color:var(--navy); margin-bottom:4px;">Check-in Berhasil!</div>
                <div style="font-size:0.88rem; color:var(--text-muted);">
                    <strong>${escapeHtml((b && b.customer_name) || '')}</strong> — ${escapeHtml((b && b.court_name) || '')}
                </div>
                <div style="font-size:0.82rem; margin-top:4px; color:var(--green-dark); font-weight:600;">
                    Tercatat masuk pada ${escapeHtml((b && b.checkin_time) || '')} WIB oleh ${escapeHtml((b && b.kasir_name) || 'Kasir')}
                </div>
            </div>
        </div>`;
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
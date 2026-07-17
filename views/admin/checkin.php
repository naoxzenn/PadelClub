<?php
// views/admin/checkin.php
include_once __DIR__ . '/../../includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Pemeriksaan Tiket Digital</h1>
        <p>Validasi pemesanan lapangan pelanggan secara real-time</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:600px;">
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success" style="margin-bottom: 24px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-outlined" style="font-size:2rem; color:var(--green);">check_circle</span>
                    <div>
                        <strong style="display:block; font-size:1.1rem;">Check-in Berhasil!</strong>
                        <span>Pemain telah dikonfirmasi masuk lapangan.</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger" style="margin-bottom: 24px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span class="material-symbols-outlined" style="font-size:2rem; color:#EF4444;">error</span>
                    <div>
                        <strong style="display:block; font-size:1.1rem;">Check-in Gagal / Tidak Valid</strong>
                        <span><?= htmlspecialchars($error_msg) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($booking): ?>
            <div class="card" style="padding: 28px; border-radius: var(--radius-lg); position: relative; overflow: hidden;">
                <!-- Decorative background indicators -->
                <?php if (empty($error_msg) && $booking['checkin_status'] !== 'Checked In'): ?>
                    <div style="position: absolute; top:0; right:0; left:0; height:6px; background: var(--green);"></div>
                <?php else: ?>
                    <div style="position: absolute; top:0; right:0; left:0; height:6px; background: #EF4444; "></div>
                <?php endif; ?>

                <div style="text-align: center; margin-bottom: 24px; margin-top: 10px;">
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Status Kehadiran</div>
                    <?php if ($booking['checkin_status'] === 'Checked In'): ?>
                        <span class="status-confirmed" style="display:inline-block; padding: 6px 16px; font-size: 0.9rem; font-weight: 700; border-radius: var(--radius-full); margin-top: 8px; background: rgba(34, 197, 94, 0.15); color: var(--green);">Sudah Check In</span>
                    <?php else: ?>
                        <span class="status-pending" style="display:inline-block; padding: 6px 16px; font-size: 0.9rem; font-weight: 700; border-radius: var(--radius-full); margin-top: 8px; background: rgba(245, 158, 11, 0.15); color: #F59E0B;">Belum Check In</span>
                    <?php endif; ?>
                </div>

                <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--navy); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="color:var(--blue);">confirmation_number</span>
                    Informasi Pemesanan
                </h3>

                <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.92rem; color: var(--text);">
                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-weight: 500;">Nama Pelanggan</span>
                        <strong style="color: var(--navy);"><?= htmlspecialchars($booking['nama_lengkap']) ?></strong>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-weight: 500;">Kode Booking</span>
                        <strong style="font-family: monospace; font-size: 1.05rem; color: var(--navy);"><?= htmlspecialchars($booking['booking_code'] ?? '-') ?></strong>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-weight: 500;">Lapangan</span>
                        <strong style="color: var(--navy);"><?= htmlspecialchars($booking['nama_lapangan']) ?> (<?= $booking['tipe_lapangan'] ?>)</strong>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-weight: 500;">Tanggal Bermain</span>
                        <strong style="color: var(--navy);"><?= date('d F Y', strtotime($booking['tanggal_booking'])) ?></strong>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-weight: 500;">Slot Waktu</span>
                        <strong style="color: var(--navy);"><?= substr($booking['jam_mulai'],0,5) ?> – <?= substr($booking['jam_selesai'],0,5) ?> WIB</strong>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px;">
                        <span style="color: var(--text-muted); font-weight: 500;">Status Pembayaran</span>
                        <strong style="color: var(--green);"><?= $booking['payment_status'] ?></strong>
                    </div>

                    <?php if ($booking['checkin_status'] === 'Checked In'): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1.5fr; border-bottom: 1px solid rgba(0,0,0,0.03); padding-bottom: 8px; background: rgba(34, 197, 94, 0.05); padding: 8px; border-radius: 6px;">
                            <span style="color: var(--text-muted); font-weight: 500;">Waktu Check-in</span>
                            <strong style="color: var(--navy);"><?= date('d/m/Y H:i:s', strtotime($booking['checkin_time'])) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($error_msg) && $booking['checkin_status'] !== 'Checked In'): ?>
                    <div style="margin-top: 32px; border-top: 1px solid var(--border); padding-top: 24px; text-align: center;">
                        <div style="display:flex; align-items:center; justify-content:center; gap:8px; color:var(--green); font-weight:800; font-size:1.15rem; margin-bottom:16px;">
                            <span class="material-symbols-outlined" style="font-size:1.6rem;">check_circle</span>
                            ✓ TIKET VALID &amp; SIAP CHECK-IN
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="confirm_checkin">
                            <button type="submit" class="btn btn-primary btn-block" style="padding: 14px; font-weight:700; font-size: 1.1rem; border-radius: var(--radius-md);">Konfirmasi Masuk (Check In)</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 24px;">
            <a href="admin/checkin_list.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined">arrow_back</span>
                Kembali ke Daftar Kehadiran
            </a>
        </div>
    </div>
</section>

<?php
include_once __DIR__ . '/../../includes/footer.php';
?>

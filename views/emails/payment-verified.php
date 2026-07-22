<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Terverifikasi & Booking Dikonfirmasi</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #F8FAFC; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05); }
        .header { background: linear-gradient(135deg, #22C55E 0%, #0EA5E9 100%); padding: 30px; text-align: center; color: #FFFFFF; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.5px; }
        .content { padding: 40px 30px; }
        .welcome-text { font-size: 16px; line-height: 1.6; margin-bottom: 24px; }
        .details-card { background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .details-row:last-child { margin-bottom: 0; border-top: 1px solid #E2E8F0; padding-top: 12px; font-weight: bold; }
        .label { color: #64748B; }
        .value { color: #0F172A; text-align: right; }
        .ticket-box { background-color: #ECFDF5; border: 2px dashed #10B981; border-radius: 12px; padding: 20px; text-align: center; margin: 30px 0; }
        .ticket-code { font-size: 28px; font-weight: 800; letter-spacing: 2px; color: #059669; margin: 10px 0; }
        .btn-wrapper { text-align: center; margin-top: 30px; }
        .btn { display: inline-block; background-color: #10B981; color: #FFFFFF !important; text-decoration: none; padding: 12px 24px; border-radius: 9999px; font-weight: bold; font-size: 14px; }
        .footer { padding: 30px; text-align: center; font-size: 12px; color: #64748B; border-top: 1px solid #E2E8F0; }
        .footer a { color: #0EA5E9; text-decoration: none; }
        
        @media (prefers-color-scheme: dark) {
            body, .wrapper { background-color: #0B0F19 !important; color: #F8FAFC !important; }
            .container { background-color: #1E293B !important; border-color: #334155 !important; }
            .details-card { background-color: #0B0F19 !important; border-color: #334155 !important; }
            .details-row:last-child { border-top-color: #334155 !important; }
            .label { color: #94A3B8 !important; }
            .value { color: #F8FAFC !important; }
            .ticket-box { background-color: #064E3B !important; border-color: #10B981 !important; }
            .ticket-code { color: #34D399 !important; }
            .footer { border-top-color: #334155 !important; color: #94A3B8 !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>PadelClub</h1>
            </div>
            <div class="content">
                <p class="welcome-text">Halo <strong><?= htmlspecialchars($nama_lengkap) ?></strong>,</p>
                <p class="welcome-text">Kabar baik! Pembayaran sewa lapangan Anda telah diverifikasi oleh petugas kami. Booking Anda kini telah <strong>Confirmed</strong>.</p>
                
                <div class="details-card">
                    <div class="details-row">
                        <span class="label">ID Booking</span>
                        <span class="value">#<?= htmlspecialchars($id) ?></span>
                    </div>
                    <div class="details-row">
                        <span class="label">Lapangan</span>
                        <span class="value"><?= htmlspecialchars($nama_lapangan) ?></span>
                    </div>
                    <div class="details-row">
                        <span class="label">Tanggal Main</span>
                        <span class="value"><?= date('d F Y', strtotime($tanggal_booking)) ?></span>
                    </div>
                    <div class="details-row">
                        <span class="label">Jam &amp; Durasi</span>
                        <span class="value"><?= substr($jam_mulai, 0, 5) ?> - <?= substr($jam_selesai, 0, 5) ?> (<?= formatDurasi($jam_mulai, $jam_selesai) ?>)</span>
                    </div>
                    <div class="details-row">
                        <span class="label">Total Pembayaran</span>
                        <span class="value">Rp <?= number_format($total_harga, 0, ',', '.') ?></span>
                    </div>
                </div>

                <div class="ticket-box">
                    <span style="font-size: 14px; font-weight: bold; color: #047857; text-transform: uppercase;">Kode Tiket Digital</span>
                    <div class="ticket-code"><?= htmlspecialchars($booking_code) ?></div>
                    <p style="font-size: 12px; color: #047857; margin: 0;">Tunjukkan kode ini atau QR Code di dashboard Anda pada petugas saat check-in di lapangan.</p>
                </div>
                
                <div class="btn-wrapper">
                    <a href="<?= $_ENV['APP_URL'] ?>/booking-detail.php?code=<?= htmlspecialchars($booking_code) ?>" class="btn">Lihat Tiket Digital</a>
                </div>
            </div>
            <div class="footer">
                <p>&copy; <?= date('Y') ?> PadelClub Premium. Hak Cipta Dilindungi.</p>
                <p>Jl. Tulang Bawang Sel. No.26, Banjarsari, Surakarta</p>
            </div>
        </div>
    </div>
</body>
</html>

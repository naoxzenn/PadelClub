<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Dibatalkan / Pembayaran Ditolak</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #F8FAFC; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05); }
        .header { background: #EF4444; padding: 30px; text-align: center; color: #FFFFFF; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.5px; }
        .content { padding: 40px 30px; }
        .welcome-text { font-size: 16px; line-height: 1.6; margin-bottom: 24px; }
        .details-card { background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .details-row:last-child { margin-bottom: 0; border-top: 1px solid #E2E8F0; padding-top: 12px; font-weight: bold; }
        .label { color: #64748B; }
        .value { color: #0F172A; text-align: right; }
        .reason-box { background-color: #FEF2F2; border-left: 4px solid #EF4444; border-radius: 4px; padding: 16px; margin: 24px 0; font-size: 14px; color: #991B1B; }
        .footer { padding: 30px; text-align: center; font-size: 12px; color: #64748B; border-top: 1px solid #E2E8F0; }
        .footer a { color: #0EA5E9; text-decoration: none; }
        
        @media (prefers-color-scheme: dark) {
            body, .wrapper { background-color: #0B0F19 !important; color: #F8FAFC !important; }
            .container { background-color: #1E293B !important; border-color: #334155 !important; }
            .details-card { background-color: #0B0F19 !important; border-color: #334155 !important; }
            .details-row:last-child { border-top-color: #334155 !important; }
            .label { color: #94A3B8 !important; }
            .value { color: #F8FAFC !important; }
            .reason-box { background-color: #7F1D1D !important; color: #FCA5A5 !important; }
            .footer { border-top-color: #334155 !important; color: #94A3B8 !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>Booking Dibatalkan</h1>
            </div>
            <div class="content">
                <p class="welcome-text">Halo <strong><?= htmlspecialchars($nama_lengkap) ?></strong>,</p>
                <p class="welcome-text">Kami ingin menginformasikan bahwa pesanan sewa lapangan Anda dengan ID <strong>#<?= htmlspecialchars($id) ?></strong> telah dibatalkan.</p>
                
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
                        <span class="label">Jam</span>
                        <span class="value"><?= substr($jam_mulai, 0, 5) ?> - <?= substr($jam_selesai, 0, 5) ?></span>
                    </div>
                    <div class="details-row">
                        <span class="label">Total Pembayaran</span>
                        <span class="value">Rp <?= number_format($total_harga, 0, ',', '.') ?></span>
                    </div>
                </div>

                <?php if (!empty($reason)): ?>
                    <div class="reason-box">
                        <strong>Catatan/Alasan Pembatalan:</strong><br>
                        <?= htmlspecialchars($reason) ?>
                    </div>
                <?php endif; ?>

                <p class="welcome-text" style="font-size: 14px; color: #64748B;">Jika Anda merasa ini adalah kesalahan atau jika Anda telah melakukan transfer, silakan hubungi tim dukungan kami melalui kontak yang tersedia di website.</p>
            </div>
            <div class="footer">
                <p>&copy; <?= date('Y') ?> PadelClub Premium. Hak Cipta Dilindungi.</p>
                <p>Jl. Tulang Bawang Sel. No.26, Banjarsari, Surakarta</p>
            </div>
        </div>
    </div>
</body>
</html>

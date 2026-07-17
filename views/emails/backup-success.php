<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Database Berhasil</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #F8FAFC; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05); }
        .header { background: #0F172A; padding: 30px; text-align: center; color: #FFFFFF; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.5px; }
        .content { padding: 40px 30px; }
        .welcome-text { font-size: 16px; line-height: 1.6; margin-bottom: 24px; }
        .details-card { background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .details-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .details-row:last-child { margin-bottom: 0; }
        .label { color: #64748B; }
        .value { color: #0F172A; text-align: right; }
        .footer { padding: 30px; text-align: center; font-size: 12px; color: #64748B; border-top: 1px solid #E2E8F0; }
        
        @media (prefers-color-scheme: dark) {
            body, .wrapper { background-color: #0B0F19 !important; color: #F8FAFC !important; }
            .container { background-color: #1E293B !important; border-color: #334155 !important; }
            .details-card { background-color: #0B0F19 !important; border-color: #334155 !important; }
            .label { color: #94A3B8 !important; }
            .value { color: #F8FAFC !important; }
            .footer { border-top-color: #334155 !important; color: #94A3B8 !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>PadelClub System</h1>
            </div>
            <div class="content">
                <p class="welcome-text">Halo Administrator,</p>
                <p class="welcome-text">Kami ingin menginformasikan bahwa proses backup database PadelClub telah selesai dilaksanakan dengan sukses. Berikut detail file cadangan:</p>
                
                <div class="details-card">
                    <div class="details-row">
                        <span class="label">Nama File</span>
                        <span class="value"><code><?= htmlspecialchars($filename) ?></code></span>
                    </div>
                    <div class="details-row">
                        <span class="label">Ukuran File</span>
                        <span class="value"><?= htmlspecialchars($filesize) ?></span>
                    </div>
                    <div class="details-row">
                        <span class="label">Operator Pemroses</span>
                        <span class="value"><?= htmlspecialchars($created_by) ?></span>
                    </div>
                    <div class="details-row">
                        <span class="label">Waktu Eksekusi</span>
                        <span class="value"><?= date('d M Y, H:i', strtotime($created_at)) ?> WIB</span>
                    </div>
                </div>

                <p class="welcome-text" style="font-size: 14px; color: #64748B;">File backup disimpan dengan aman di storage server internal. Anda juga dapat melihat log backup dan mengunduh berkas cadangan melalui Dashboard Admin.</p>
            </div>
            <div class="footer">
                <p>&copy; <?= date('Y') ?> PadelClub Premium. Hak Cipta Dilindungi.</p>
                <p>System Administrator Services</p>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Reset Password</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #F8FAFC; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05); }
        .header { background: linear-gradient(135deg, #22C55E 0%, #0EA5E9 100%); padding: 30px; text-align: center; color: #FFFFFF; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 0.5px; }
        .content { padding: 40px 30px; }
        .welcome-text { font-size: 16px; line-height: 1.6; margin-bottom: 24px; }
        .btn-wrapper { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background-color: #0EA5E9; color: #FFFFFF !important; text-decoration: none; padding: 14px 28px; border-radius: 9999px; font-weight: bold; font-size: 14px; }
        .warning-text { font-size: 12px; color: #64748B; line-height: 1.5; background-color: #F8FAFC; border-radius: 8px; padding: 16px; border: 1px solid #E2E8F0; margin-top: 30px; }
        .footer { padding: 30px; text-align: center; font-size: 12px; color: #64748B; border-top: 1px solid #E2E8F0; }
        
        @media (prefers-color-scheme: dark) {
            body, .wrapper { background-color: #0B0F19 !important; color: #F8FAFC !important; }
            .container { background-color: #1E293B !important; border-color: #334155 !important; }
            .warning-text { background-color: #0B0F19 !important; border-color: #334155 !important; color: #94A3B8 !important; }
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
                <p class="welcome-text">Kami menerima permintaan untuk merestart password akun PadelClub Anda. Klik tombol di bawah ini untuk mengatur password baru Anda:</p>
                
                <div class="btn-wrapper">
                    <a href="<?= $reset_link ?>" class="btn">Reset Password</a>
                </div>
                
                <p class="welcome-text" style="font-size: 14px;">Atau salin tautan berikut ke browser Anda jika tombol tidak berfungsi:</p>
                <p class="welcome-text" style="font-size: 13px; word-break: break-all;"><a href="<?= $reset_link ?>" style="color: #0EA5E9;"><?= $reset_link ?></a></p>

                <div class="warning-text">
                    Tautan reset password ini hanya akan berlaku selama <strong>60 menit</strong> sejak email ini dikirimkan. Jika Anda tidak merasa melakukan permintaan ini, abaikan saja email ini dan password Anda akan tetap aman.
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

<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'kasir') {
        header('Location: kasir/dashboard.php');
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}
/** @var mysqli $conn */

$pageTitle = 'Daftar Akun';
$baseUrl = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telp     = trim($_POST['nomor_telepon'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['konfirmasi_password'] ?? '';

    // Validasi
    if (empty($nama))  $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email)) $errors[] = 'Email wajib diisi.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (empty($pass))  $errors[] = 'Password wajib diisi.';
    elseif (strlen($pass) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($pass !== $pass2) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        // Cek email sudah terdaftar
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = 'Email sudah terdaftar, gunakan email lain.';
            } else {
                // DEVELOPMENT MODE ONLY
                // Password hashing sementara dinonaktifkan untuk mempercepat proses testing.
                // Aktifkan kembali password_hash() sebelum deployment ke production:
                //   $hash = password_hash($pass, PASSWORD_DEFAULT);
                $hash = $pass;
                $stmt2 = mysqli_prepare($conn, "INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role) VALUES (?, ?, ?, ?, 'customer')");
                if ($stmt2) {
                    mysqli_stmt_bind_param($stmt2, 'ssss', $nama, $email, $hash, $telp);
                    if (mysqli_stmt_execute($stmt2)) {
                        $success = 'Registrasi berhasil! Silakan <a href="login.php">login</a>.';
                    } else {
                        $errors[] = 'Terjadi kesalahan. Coba lagi.';
                    }
                    mysqli_stmt_close($stmt2);
                } else {
                    $errors[] = 'Gagal memproses registrasi: ' . mysqli_error($conn);
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = 'Gagal memproses validasi email: ' . mysqli_error($conn);
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Buat Akun Baru</h2>
        <p class="sub">Daftar sekarang dan mulai booking lapangan padel favoritmu.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div>• <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="register.php" id="form-register">
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Contoh: Budi Santoso"
                       value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" placeholder="email@contoh.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="nomor_telepon">Nomor Telepon</label>
                <input type="text" id="nomor_telepon" name="nomor_telepon" placeholder="08xxxxxxxxxx"
                       value="<?= htmlspecialchars($_POST['nomor_telepon'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>

            <div class="form-group">
                <label for="konfirmasi_password">Konfirmasi Password</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btn-register">Daftar Sekarang</button>
        </form>
        <?php endif; ?>

        <div class="footer-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>

        <?php if (function_exists('isClerkConfigured') && isClerkConfigured()): ?>
        <div style="display:flex; align-items:center; gap:12px; margin:24px 0 16px;">
            <hr style="flex:1; border:none; border-top:1px solid #e0e0e0;">
            <span style="color:#999; font-size:13px; white-space:nowrap;">atau</span>
            <hr style="flex:1; border:none; border-top:1px solid #e0e0e0;">
        </div>
        <button type="button" id="btn-google-register" class="btn btn-secondary btn-block" style="display:flex; align-items:center; justify-content:center; gap:10px; padding:12px; font-size:14px; border:1px solid #ddd; background:#fff; cursor:pointer; border-radius:var(--radius-sm); transition:all .2s;" onmouseover="this.style.background='#f8fafc';this.style.borderColor='#0EA5E9'" onmouseout="this.style.background='#fff';this.style.borderColor='#ddd'">
            <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            Daftar dengan Google
        </button>
        <script>
            document.getElementById('btn-google-register')?.addEventListener('click', async function() {
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span style="display:inline-block;width:18px;height:18px;border:2px solid #E2E8F0;border-top-color:#0EA5E9;border-radius:50%;animation:clkSpin .6s linear infinite;"></span> Menghubungkan...';
                try {
                    const clerk = await initClerk();
                    const signUp = await clerk.client.signUp.create({
                        strategy: 'oauth_google',
                        redirectUrl: window.location.origin + '/PadelClub/auth/sso-callback.php',
                        actionCompleteRedirectUrl: window.location.origin + '/PadelClub/dashboarduser.php'
                    });
                    const extUrl = signUp.verifications.externalAccount.externalVerificationRedirectURL;
                    if (extUrl) {
                        window.location.href = extUrl;
                    } else {
                        throw new Error('Tidak ada redirect URL dari Google');
                    }
                } catch (err) {
                    console.error('Google OAuth error:', err);
                    btn.disabled = false;
                    btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg> Gagal. Coba lagi';
                    if (typeof showToast === 'function') {
                        showToast('Gagal menghubungkan ke Google: ' + (err.message || 'Unknown error'), 'error');
                    }
                }
            });
        </script>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

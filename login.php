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

$pageTitle = 'Masuk';
$baseUrl = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, password, role FROM users WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            // DEVELOPMENT MODE ONLY
            // Verifikasi password menggunakan perbandingan string biasa (plain text).
            // Aktifkan kembali password_verify() sebelum deployment ke production:
            //   if ($user && password_verify($pass, $user['password'])) {
            if ($user && $pass === $user['password']) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['nama']      = $user['nama_lengkap'];
                $_SESSION['role']      = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } elseif ($user['role'] === 'kasir') {
                    header('Location: kasir/dashboard.php');
                } else {
                    header('Location: dashboarduser.php');
                }
                mysqli_stmt_close($stmt);
                exit;
            } else {
                $error = 'Email atau password salah.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Gagal memproses login: ' . mysqli_error($conn);
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Masuk ke Akun Anda</h2>
        <p class="sub">Silakan masuk untuk melakukan booking lapangan.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="alert alert-success">Registrasi berhasil! Silakan login.</div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="form-login">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" placeholder="email@contoh.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btn-login">Masuk</button>
        </form>

        <div class="footer-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>

        <?php if (function_exists('isClerkConfigured') && isClerkConfigured()): ?>
        <div style="display:flex; align-items:center; gap:12px; margin:24px 0 16px;">
            <hr style="flex:1; border:none; border-top:1px solid #e0e0e0;">
            <span style="color:#999; font-size:13px; white-space:nowrap;">atau</span>
            <hr style="flex:1; border:none; border-top:1px solid #e0e0e0;">
        </div>
        <button type="button" id="btn-google-login" class="btn btn-secondary btn-block" style="display:flex; align-items:center; justify-content:center; gap:10px; padding:12px; font-size:14px; border:1px solid #ddd; background:#fff; cursor:pointer; border-radius:var(--radius-sm); transition:all .2s;" onmouseover="this.style.background='#f8fafc';this.style.borderColor='#0EA5E9'" onmouseout="this.style.background='#fff';this.style.borderColor='#ddd'">
            <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            Masuk dengan Google
        </button>
        <script>
            document.getElementById('btn-google-login')?.addEventListener('click', async function() {
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<span style="display:inline-block;width:18px;height:18px;border:2px solid #E2E8F0;border-top-color:#0EA5E9;border-radius:50%;animation:clkSpin .6s linear infinite;"></span> Menghubungkan...';
                try {
                    const clerk = await initClerk();
                    const signIn = await clerk.client.signIn.create({
                        strategy: 'oauth_google',
                        redirectUrl: window.location.origin + '/PadelClub/auth/sso-callback.php',
                        actionCompleteRedirectUrl: window.location.origin + '/PadelClub/dashboarduser.php'
                    });
                    const extUrl = signIn.firstFactorVerification.externalVerificationRedirectURL;
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

        <div class="footer-link" style="margin-top: 10px; font-size: 12px; color: #aaa;">
            Demo admin: <strong>admin@MyPadel.com</strong> / <strong>password</strong>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

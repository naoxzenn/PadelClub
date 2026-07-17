<?php
session_start();
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
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/helpers/AuthHelper.php';
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
        $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, password, role, email_verified FROM users WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            if ($user && AuthHelper::verifyPassword($pass, $user['password'])) {
                if ($user['role'] === 'customer' && !$user['email_verified']) {
                    $error = 'Email Anda belum terverifikasi. Silakan periksa email Anda untuk verifikasi.';
                } else {
                    // Session Fixation Protection
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['nama']      = $user['nama_lengkap'];
                    $_SESSION['role']      = $user['role'];

                    $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
                    if (!empty($redirect) && (strpos($redirect, '/') === 0 || strpos($redirect, 'http') === 0 || strpos($redirect, 'checkin') === 0)) {
                        header('Location: ' . $redirect);
                    } else {
                        if ($user['role'] === 'admin') {
                            header('Location: admin/dashboard.php');
                        } elseif ($user['role'] === 'kasir') {
                            header('Location: kasir/dashboard.php');
                        } else {
                            header('Location: dashboarduser.php');
                        }
                    }
                    mysqli_stmt_close($stmt);
                    exit;
                }
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

        <?php if (isset($_GET['error']) && $_GET['error'] === 'oauth_failed'): ?>
            <div class="alert alert-danger">Login Google gagal. <?= isset($_GET['details']) ? htmlspecialchars($_GET['details']) : '' ?></div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'no_auth_code'): ?>
            <div class="alert alert-danger">Kode otorisasi Google tidak ditemukan.</div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
            <div class="alert alert-success">Registrasi berhasil! Silakan login.</div>
        <?php endif; ?>

        <form method="POST" action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" id="form-login">
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

        <div class="auth-divider">
            <span>atau</span>
        </div>

        <a href="auth/google-login.php" class="btn btn-google btn-block">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.5 24c0-1.61-.15-3.16-.43-4.67H24v9.09h12.63c-.55 2.9-2.19 5.35-4.65 7l7.21 5.59C43.39 36.6 46.5 30.9 46.5 24z"/>
                <path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.21-5.59c-2.05 1.37-4.67 2.2-8.68 2.2-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Masuk dengan Google
        </a>

        <div class="footer-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>

        <div class="footer-link" style="margin-top: 5px;">
            Lupa Password? <a href="forgot-password.php">Reset di sini</a>
        </div>

        <div class="footer-link" style="margin-top: 10px; font-size: 12px; color: #aaa;">
            Demo admin: <strong>admin@MyPadel.com</strong> / <strong>password</strong>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

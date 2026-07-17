<?php
// reset-password.php - Reset Password Page
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/helpers/AuthHelper.php';
/** @var mysqli $conn */

$pageTitle = 'Reset Password';
$baseUrl = '';
$error = '';
$success = '';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if (empty($token)) {
    header('Location: login.php');
    exit;
}

// Cari user berdasarkan token
$user = null;
$stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, reset_expired_at FROM users WHERE reset_token = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Cek token valid dan belum expired
$isTokenValid = false;
if ($user) {
    $expiredTime = strtotime($user['reset_expired_at']);
    if ($expiredTime > time()) {
        $isTokenValid = true;
    } else {
        $error = 'Link reset password sudah kedaluwarsa (berlaku 60 menit).';
    }
} else {
    $error = 'Link reset password tidak valid atau sudah digunakan.';
}

// Proses form reset password
if ($isTokenValid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['konfirmasi_password'] ?? '';

    if (empty($pass) || empty($pass2)) {
        $error = 'Kedua bidang password wajib diisi.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($pass !== $pass2) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } else {
        // Hash password baru
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        // Update password & hapus reset token
        $stmtUpdate = mysqli_prepare($conn, "UPDATE users SET password = ?, reset_token = NULL, reset_expired_at = NULL WHERE id = ?");
        if ($stmtUpdate) {
            mysqli_stmt_bind_param($stmtUpdate, 'si', $hash, $user['id']);
            if (mysqli_stmt_execute($stmtUpdate)) {
                $success = 'Password Anda berhasil diperbarui! Silakan kembali masuk ke akun Anda.';
                $isTokenValid = false; // Sembunyikan form
            } else {
                $error = 'Gagal memperbarui password. Silakan coba lagi.';
            }
            mysqli_stmt_close($stmtUpdate);
        } else {
            $error = 'Terjadi kesalahan sistem.';
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Buat Password Baru</h2>
        <p class="sub">Silakan tentukan password baru yang aman untuk akun Anda.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($isTokenValid): ?>
        <form method="POST" action="reset-password.php" id="form-reset">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required autofocus>
            </div>

            <div class="form-group">
                <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btn-reset">Perbarui Password</button>
        </form>
        <?php endif; ?>

        <div class="footer-link" style="margin-top: 20px;">
            Kembali ke <a href="login.php">Halaman Masuk</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

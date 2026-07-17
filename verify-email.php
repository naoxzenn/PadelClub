<?php
// verify-email.php - Email Verification Page
session_start();

require_once __DIR__ . '/config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Verifikasi Email';
$baseUrl = '';
$error = '';
$success = '';

$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $error = 'Token verifikasi tidak ditemukan atau tidak valid.';
} else {
    // Cari user berdasarkan token verifikasi
    $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, email_verified FROM users WHERE verification_token = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            if ($user['email_verified'] == 1) {
                $success = 'Email Anda sudah terverifikasi sebelumnya! Silakan masuk.';
            } else {
                // Update status email_verified dan hapus token
                $now = date('Y-m-d H:i:s');
                $stmtUpdate = mysqli_prepare($conn, "UPDATE users SET email_verified = 1, verified_at = ?, verification_token = NULL WHERE id = ?");
                if ($stmtUpdate) {
                    mysqli_stmt_bind_param($stmtUpdate, 'si', $now, $user['id']);
                    if (mysqli_stmt_execute($stmtUpdate)) {
                        $success = 'Akun Anda berhasil diverifikasi! Selamat bergabung di PadelClub Premium.';
                    } else {
                        $error = 'Gagal memperbarui status verifikasi. Silakan coba lagi.';
                    }
                    mysqli_stmt_close($stmtUpdate);
                } else {
                    $error = 'Terjadi kesalahan sistem.';
                }
            }
        } else {
            $error = 'Token verifikasi kedaluwarsa atau tidak valid.';
        }
    } else {
        $error = 'Terjadi kesalahan pemrosesan data.';
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box">
        <?php if ($success): ?>
            <div style="text-align: center; margin-bottom: 20px; color: var(--green);">
                <span class="material-symbols-outlined" style="font-size: 64px; font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
            <h2 style="color: var(--green);">Verifikasi Sukses!</h2>
            <div class="alert alert-success" style="margin-top: 15px; border-left: none; text-align: center;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-bottom: 20px; color: #EF4444;">
                <span class="material-symbols-outlined" style="font-size: 64px; font-variation-settings: 'FILL' 1;">error</span>
            </div>
            <h2 style="color: #EF4444;">Verifikasi Gagal</h2>
            <div class="alert alert-danger" style="margin-top: 15px; border-left: none; text-align: center;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="footer-link" style="margin-top: 30px; text-align: center;">
            <a href="login.php" class="btn btn-primary btn-block">Masuk ke Akun Saya</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

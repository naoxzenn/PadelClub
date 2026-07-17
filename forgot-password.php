<?php
// forgot-password.php - Forgot Password Page
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/helpers/AuthHelper.php';
require_once __DIR__ . '/helpers/TokenHelper.php';
require_once __DIR__ . '/helpers/MailHelper.php';
/** @var mysqli $conn */

$pageTitle = 'Lupa Password';
$baseUrl = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Alamat email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } else {
        // Enforce rate limiting
        $rateLimit = AuthHelper::checkResetRateLimit($email);
        if (!$rateLimit['allowed']) {
            $error = 'Terlalu banyak permintaan reset password. Silakan coba lagi setelah ' . $rateLimit['retry_after'] . ' menit.';
        } else {
            // Log the attempt
            AuthHelper::logResetAttempt();

            // Cek apakah email terdaftar
            $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, login_provider FROM users WHERE email = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if ($user) {
                    if ($user['login_provider'] === 'google') {
                        $error = 'Akun ini terdaftar menggunakan login Google. Silakan masuk menggunakan Google OAuth.';
                    } else {
                        // Generate token
                        $token = TokenHelper::generateToken();
                        // 1 hour expiry from now
                        $expiry = date('Y-m-d H:i:s', time() + 3600);

                        // Save token & expiry to DB
                        $stmtUpdate = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_expired_at = ? WHERE id = ?");
                        if ($stmtUpdate) {
                            mysqli_stmt_bind_param($stmtUpdate, 'ssi', $token, $expiry, $user['id']);
                            if (mysqli_stmt_execute($stmtUpdate)) {
                                // Kirim email
                                $resetLink = $_ENV['APP_URL'] . "/reset-password.php?token=" . $token;
                                $emailData = [
                                    'nama_lengkap' => $user['nama_lengkap'],
                                    'reset_link' => $resetLink
                                ];
                                $mailSent = MailHelper::send($email, 'Instruksi Reset Password - PadelClub', 'forgot-password', $emailData);
                                
                                if ($mailSent) {
                                    $success = 'Instruksi reset password telah dikirimkan ke email Anda. Silakan periksa kotak masuk email Anda.';
                                } else {
                                    $error = 'Gagal mengirimkan email reset password. Silakan hubungi admin.';
                                }
                            } else {
                                $error = 'Terjadi kesalahan sistem. Coba lagi.';
                            }
                            mysqli_stmt_close($stmtUpdate);
                        } else {
                            $error = 'Gagal memproses permintaan sistem.';
                        }
                    }
                } else {
                    // Penyamaran info untuk keamanan: "Jika email terdaftar, instruksi telah dikirim."
                    // Namun kita tetap tampilkan pesan sukses agar penyerang tidak bisa brute force cek akun.
                    // Tapi, demi kejelasan user testing, mari kita tampilkan pesan sukses yang sama.
                    $success = 'Instruksi reset password telah dikirimkan ke email Anda jika alamat email tersebut terdaftar di sistem kami.';
                }
            } else {
                $error = 'Gagal memproses data: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Lupa Password Anda?</h2>
        <p class="sub">Masukkan alamat email Anda untuk menerima instruksi pembuatan password baru.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="forgot-password.php" id="form-forgot">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" placeholder="email@contoh.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btn-forgot">Kirim Link Reset</button>
        </form>
        <?php endif; ?>

        <div class="footer-link" style="margin-top: 20px;">
            Kembali ke <a href="login.php">Halaman Masuk</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

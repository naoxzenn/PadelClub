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
            Daftar dengan Google
        </a>
        <?php endif; ?>

        <div class="footer-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

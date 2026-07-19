<?php
session_start();

// Validasi Session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Hanya ijinkan customer
if ($_SESSION['role'] !== 'customer') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'kasir') {
        header('Location: kasir/dashboard.php');
    }
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/helpers/TokenHelper.php';
require_once __DIR__ . '/helpers/MailHelper.php';
/** @var mysqli $conn */

function formatTanggalIndonesia($datetime) {
    if (empty($datetime)) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $time = strtotime($datetime);
    $tgl = date('j', $time);
    $bln = (int)date('n', $time);
    $thn = date('Y', $time);
    return $tgl . ' ' . $bulan[$bln] . ' ' . $thn;
}

$user_id = (int)$_SESSION['user_id'];

// Proteksi CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Pengaturan Profil';
$baseUrl = '';

// Ambil data user terbaru
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) {
    die("User tidak ditemukan.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF Token
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($csrf) || $csrf !== ($_SESSION['csrf_token'] ?? '')) {
        $_SESSION['toast_msg'] = ['text' => 'Galat keamanan: Token CSRF tidak valid.', 'type' => 'error'];
        header('Location: profil.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ACTION 1: UPDATE INFO PROFIL
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $nomor_hp = trim($_POST['nomor_hp'] ?? '');

        // Validasi input
        if (empty($username)) {
            $_SESSION['toast_msg'] = ['text' => 'Username tidak boleh kosong.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        if (empty($nama_lengkap)) {
            $_SESSION['toast_msg'] = ['text' => 'Nama lengkap tidak boleh kosong.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        // Cek keunikan username
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? AND id != ?");
        mysqli_stmt_bind_param($stmt_check, 'si', $username, $user_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        $exists = mysqli_stmt_num_rows($stmt_check) > 0;
        mysqli_stmt_close($stmt_check);

        if ($exists) {
            $_SESSION['toast_msg'] = ['text' => 'Username sudah digunakan oleh akun lain.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        // Validasi nomor HP (hanya angka, 10-15 digit)
        if (!empty($nomor_hp)) {
            if (!preg_match('/^[0-9]+$/', $nomor_hp)) {
                $_SESSION['toast_msg'] = ['text' => 'Nomor HP hanya boleh berisi angka.', 'type' => 'error'];
                header('Location: profil.php');
                exit;
            }
            if (strlen($nomor_hp) < 10 || strlen($nomor_hp) > 15) {
                $_SESSION['toast_msg'] = ['text' => 'Nomor HP harus berukuran antara 10 hingga 15 digit.', 'type' => 'error'];
                header('Location: profil.php');
                exit;
            }
        }

        // Update database (sync nama_lengkap/full_name & nomor_telepon/phone)
        $stmt_update = mysqli_prepare($conn, "UPDATE users SET username = ?, nama_lengkap = ?, full_name = ?, nomor_telepon = ?, phone = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_update, 'sssssi', $username, $nama_lengkap, $nama_lengkap, $nomor_hp, $nomor_hp, $user_id);

        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['nama'] = $nama_lengkap; // Perbarui session nama
            $_SESSION['toast_msg'] = ['text' => 'Profil berhasil diperbarui.', 'type' => 'success'];
        } else {
            $_SESSION['toast_msg'] = ['text' => 'Gagal memperbarui profil: ' . mysqli_error($conn), 'type' => 'error'];
        }
        mysqli_stmt_close($stmt_update);
        header('Location: profil.php');
        exit;
    }

    // ACTION 2: GANTI PASSWORD
    elseif ($action === 'change_password') {
        // Pengguna Google tidak boleh ganti password dari sini
        if ($user['login_provider'] === 'google') {
            $_SESSION['toast_msg'] = ['text' => 'Ganti password dinonaktifkan untuk akun Google Login.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        $old_pass = $_POST['password_lama'] ?? '';
        $new_pass = $_POST['password_baru'] ?? '';
        $conf_pass = $_POST['konfirmasi_password'] ?? '';

        if (empty($old_pass) || empty($new_pass) || empty($conf_pass)) {
            $_SESSION['toast_msg'] = ['text' => 'Semua kolom password wajib diisi.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        // Validasi password lama
        if (!password_verify($old_pass, $user['password'])) {
            $_SESSION['toast_msg'] = ['text' => 'Password lama Anda salah.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        // Validasi password baru (minimal 8 karakter)
        if (strlen($new_pass) < 8) {
            $_SESSION['toast_msg'] = ['text' => 'Password baru harus minimal 8 karakter.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        if ($new_pass !== $conf_pass) {
            $_SESSION['toast_msg'] = ['text' => 'Konfirmasi password baru tidak cocok.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        // Hash & Simpan
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt_pw = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_pw, 'si', $hashed, $user_id);
        
        if (mysqli_stmt_execute($stmt_pw)) {
            $_SESSION['toast_msg'] = ['text' => 'Password berhasil diubah.', 'type' => 'success'];
        } else {
            $_SESSION['toast_msg'] = ['text' => 'Gagal merubah password. Silakan coba lagi.', 'type' => 'error'];
        }
        mysqli_stmt_close($stmt_pw);
        header('Location: profil.php');
        exit;
    }

    // ACTION 3: KIRIM ULANG EMAIL VERIFIKASI
    elseif ($action === 'resend_verification') {
        if ($user['email_verified']) {
            $_SESSION['toast_msg'] = ['text' => 'Email Anda sudah terverifikasi.', 'type' => 'warning'];
            header('Location: profil.php');
            exit;
        }

        $token = TokenHelper::generateToken();
        $stmt_token = mysqli_prepare($conn, "UPDATE users SET verification_token = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt_token, 'si', $token, $user_id);
        mysqli_stmt_execute($stmt_token);
        mysqli_stmt_close($stmt_token);

        $verification_link = $_ENV['APP_URL'] . "/verify-email.php?token=" . $token;
        $emailData = [
            'nama_lengkap' => $user['nama_lengkap'],
            'verification_link' => $verification_link
        ];

        $mailSent = MailHelper::send($user['email'], 'Verifikasi Alamat Email Anda - PadelClub', 'verify-email', $emailData);
        if ($mailSent) {
            $_SESSION['toast_msg'] = ['text' => 'Email verifikasi berhasil dikirim ulang. Silakan periksa inbox Anda.', 'type' => 'success'];
        } else {
            $_SESSION['toast_msg'] = ['text' => 'Gagal mengirim ulang email verifikasi. Silakan hubungi admin.', 'type' => 'error'];
        }
        header('Location: profil.php');
        exit;
    }
}

// Sertakan Header Aplikasi
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Pengaturan Profil</h1>
        <p>Kelola informasi pribadi dan kata sandi akun Anda di sini.</p>
    </div>
</section>

<section class="section">
    <div class="container profile-page-container">

        <!-- NOTICE BANNER JIKA EMAIL BELUM DIVERIFIKASI -->
        <?php if (!$user['email_verified']): ?>
            <div class="alert alert-warning profile-notice-banner">
                <div class="profile-notice-content">
                    <span class="material-symbols-outlined profile-notice-icon">mark_email_unread</span>
                    <div class="profile-notice-text">
                        <strong>Email Belum Diverifikasi</strong>
                        <p>Harap verifikasi email Anda agar dapat menikmati seluruh layanan PadelClub secara penuh.</p>
                    </div>
                </div>
                <form action="profil.php" method="POST" class="profile-notice-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="resend_verification">
                    <button type="submit" class="btn btn-sm btn-outline btn-resend-email">
                        <span class="material-symbols-outlined">send</span>
                        Kirim Ulang Email Verifikasi
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- CARD 1: EDIT INFORMASI AKUN & PROFIL -->
        <div class="card profile-card">
            
            <!-- Visual Default Avatar -->
            <div class="profile-visual-header">
                <div class="profile-default-avatar">
                    <span class="material-symbols-outlined">person</span>
                </div>
                <h2 class="profile-visual-name"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                <span class="profile-visual-handle">@<?= htmlspecialchars($user['username'] ?? '') ?></span>
            </div>

            <!-- Form Edit Profil -->
            <form action="profil.php" method="POST" id="form-update-profile" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="prof-nama">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" id="prof-nama" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" placeholder="Nama lengkap Anda" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="prof-username">Username <span class="text-danger">*</span></label>
                    <input type="text" id="prof-username" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="Tentukan username unik" required>
                </div>

                <div class="form-group">
                    <label for="prof-email">Email (Read-only)</label>
                    <div class="input-readonly-wrapper">
                        <input type="email" id="prof-email" class="form-control input-readonly" value="<?= htmlspecialchars($user['email']) ?>" readonly disabled>
                        <?php if ($user['email_verified']): ?>
                            <span class="badge badge-success badge-inline-status">
                                <span class="material-symbols-outlined">verified</span> Terverifikasi
                            </span>
                        <?php else: ?>
                            <span class="badge badge-warning badge-inline-status">Belum Verifikasi</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="prof-hp">Nomor HP / Telepon <span class="text-danger">*</span></label>
                    <input type="text" id="prof-hp" name="nomor_hp" class="form-control" value="<?= htmlspecialchars($user['nomor_telepon'] ?? '') ?>" placeholder="Contoh: 081234567890" required autocomplete="tel">
                </div>

                <div class="form-row form-row-2col">
                    <div class="form-group">
                        <label>Login Menggunakan</label>
                        <div class="readonly-badge-box">
                            <?php if ($user['login_provider'] === 'google'): ?>
                                <span class="badge badge-provider-google">
                                    <svg width="16" height="16" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v3.92h6.58c-.28 1.48-1.12 2.74-2.38 3.59v2.98h3.85c2.26-2.09 3.69-5.17 3.69-8.42z"/>
                                        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.85-2.98c-1.08.72-2.45 1.16-4.08 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.37 24 12 24z"/>
                                        <path fill="#FBBC05" d="M5.27 14.31c-.25-.72-.39-1.49-.39-2.31s.14-1.59.39-2.31V6.6H1.29C.47 8.22 0 10.06 0 12s.47 3.78 1.29 5.4l3.98-3.09z"/>
                                        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.93 1.19 15.22 0 12 0 7.37 0 3.26 2.7 1.29 6.6l3.98 3.09c.95-2.85 3.6-4.94 6.73-4.94z"/>
                                    </svg>
                                    Google Login
                                </span>
                            <?php else: ?>
                                <span class="badge badge-provider-manual">
                                    <span class="material-symbols-outlined">key</span> Manual Login
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Bergabung Sejak</label>
                        <div class="readonly-text-box">
                            <span class="material-symbols-outlined">calendar_today</span>
                            <?= formatTanggalIndonesia($user['created_at']) ?>
                        </div>
                    </div>
                </div>

                <div class="profile-form-actions">
                    <button type="submit" class="btn btn-primary btn-save-profile" id="btn-save-profile">
                        <span class="material-symbols-outlined">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>

        <!-- CARD 2: KATA SANDI / KEAMANAN -->
        <div class="card profile-card">
            <div class="card-header-title">
                <span class="material-symbols-outlined">lock</span>
                <h2>Keamanan & Kata Sandi</h2>
            </div>

            <?php if ($user['login_provider'] === 'google'): ?>
                <div class="alert alert-info google-login-notice">
                    <span class="material-symbols-outlined">info</span>
                    <div>
                        <strong>Akun menggunakan Google Login</strong>
                        <p>Password dikelola secara langsung dan aman oleh Google.</p>
                    </div>
                </div>
            <?php else: ?>
                <form action="profil.php" method="POST" id="form-change-password" class="profile-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="pass-lama">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" id="pass-lama" name="password_lama" class="form-control" placeholder="Ketikkan password saat ini" required>
                    </div>

                    <div class="form-row form-row-2col">
                        <div class="form-group">
                            <label for="pass-baru">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" id="pass-baru" name="password_baru" class="form-control" placeholder="Minimal 8 karakter" required>
                        </div>

                        <div class="form-group">
                            <label for="pass-conf">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <input type="password" id="pass-conf" name="konfirmasi_password" class="form-control" placeholder="Ulangi password baru" required>
                        </div>
                    </div>

                    <div class="profile-form-actions">
                        <button type="submit" class="btn btn-outline btn-save-password" id="btn-save-password">
                            <span class="material-symbols-outlined">key</span>
                            Ubah Kata Sandi
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const forms = ['form-update-profile', 'form-change-password'];
    forms.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('submit', function() {
                const btn = el.querySelector('button[type="submit"]');
                if (btn) {
                    btn.classList.add('btn-loading');
                    btn.disabled = true;
                }
            });
        }
    });
});
</script>

<?php if (isset($_SESSION['toast_msg'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            showToast(<?= json_encode($_SESSION['toast_msg']['text']) ?>, <?= json_encode($_SESSION['toast_msg']['type']) ?>);
        });
    </script>
    <?php unset($_SESSION['toast_msg']); ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

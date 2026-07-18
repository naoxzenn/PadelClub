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

        // Validasi nomor HP (hanya angka, minimal 10 digit, maksimal 15 digit)
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

    // ACTION 2: UPLOAD AVATAR
    elseif ($action === 'upload_avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['toast_msg'] = ['text' => 'Silakan pilih file gambar terlebih dahulu.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
        
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        // Proteksi Upload File
        if (!in_array($ext, $allowed_ext)) {
            $_SESSION['toast_msg'] = ['text' => 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau WEBP.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        if ($file_size > 2 * 1024 * 1024) {
            $_SESSION['toast_msg'] = ['text' => 'Ukuran file terlalu besar. Maksimal adalah 2 MB.', 'type' => 'error'];
            header('Location: profil.php');
            exit;
        }

        // Pastikan direktori tujuan tersedia
        $upload_dir = __DIR__ . '/uploads/profile/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Nama file acak (UUID/hash-based)
        $new_name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest_path = $upload_dir . $new_name;

        if (move_uploaded_file($file_tmp, $dest_path)) {
            // Hapus file avatar lama jika ada (dan bukan tautan eksternal Google)
            $old_avatar = $user['avatar'];
            if (!empty($old_avatar) && !str_starts_with($old_avatar, 'http')) {
                $old_file_path = $upload_dir . $old_avatar;
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }

            // Simpan ke DB
            $stmt_avatar = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt_avatar, 'si', $new_name, $user_id);
            mysqli_stmt_execute($stmt_avatar);
            mysqli_stmt_close($stmt_avatar);

            $_SESSION['toast_msg'] = ['text' => 'Upload avatar berhasil.', 'type' => 'success'];
        } else {
            $_SESSION['toast_msg'] = ['text' => 'Gagal menyimpan gambar di server.', 'type' => 'error'];
        }
        header('Location: profil.php');
        exit;
    }

    // ACTION 3: HAPUS AVATAR (REVERT TO DEFAULT)
    elseif ($action === 'delete_avatar') {
        $old_avatar = $user['avatar'];
        if (!empty($old_avatar) && !str_starts_with($old_avatar, 'http')) {
            $old_file_path = __DIR__ . '/uploads/profile/' . $old_avatar;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }

        $stmt_del = mysqli_prepare($conn, "UPDATE users SET avatar = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt_del, 'i', $user_id);
        mysqli_stmt_execute($stmt_del);
        mysqli_stmt_close($stmt_del);

        $_SESSION['toast_msg'] = ['text' => 'Avatar berhasil dihapus.', 'type' => 'success'];
        header('Location: profil.php');
        exit;
    }

    // ACTION 4: GANTI PASSWORD
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

    // ACTION 5: KIRIM ULANG EMAIL VERIFIKASI
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

<style>
/* CSS Khusus Layout Profil Desktop 2-Kolom & Mobile 1-Kolom */
.profile-grid-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 24px;
    align-items: start;
    margin-top: 10px;
}

.profile-avatar-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 30px 20px;
}

.avatar-preview-wrapper {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    margin-bottom: 16px;
    border: 4px solid var(--border-color, rgba(255,255,255,0.1));
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    font-weight: 800;
    color: #fff;
    background: var(--gradient);
    overflow: hidden;
    position: relative;
}

.avatar-preview-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-file-input {
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-file-input input[type="file"] {
    position: absolute;
    top: 0;
    right: 0;
    min-width: 100%;
    min-height: 100%;
    font-size: 100px;
    text-align: right;
    filter: alpha(opacity=0);
    opacity: 0;
    outline: none;
    cursor: pointer;
    display: block;
}

.info-status-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.info-status-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-status-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-muted, rgba(255,255,255,0.6));
}

.info-status-value {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text, #fff);
}

.badge-verified {
    background: rgba(34, 197, 94, 0.15);
    color: var(--green);
    border: 1px solid rgba(34, 197, 94, 0.3);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
}

.badge-unverified {
    background: rgba(239, 68, 68, 0.15);
    color: #EF4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
}

.google-info-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: var(--radius-md);
    padding: 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.google-logo-icon {
    width: 44px;
    height: 44px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-sm);
}

@media (max-width: 991px) {
    .profile-grid-container {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard-header" style="margin-bottom: 24px;">
    <h1>Pengaturan Profil</h1>
    <p class="text-muted">Kelola informasi pribadi, foto profil, dan keamanan akun Anda di sini.</p>
</div>

<div class="profile-grid-container">
    
    <!-- KOLOM KIRI: FOTO PROFIL & STATUS AKUN -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- CARD FOTO PROFIL (CARD 2 / CARD 4) -->
        <div class="card profile-avatar-card">
            <h2>Foto Profil</h2>
            <div class="avatar-preview-wrapper">
                <?php 
                $avatar = $user['avatar'];
                if (!empty($avatar)): 
                    if (str_starts_with($avatar, 'http')): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Foto Profil" id="avatar-img-view">
                    <?php else: ?>
                        <img src="<?= $baseUrl ?>uploads/profile/<?= htmlspecialchars($avatar) ?>" alt="Foto Profil" id="avatar-img-view">
                    <?php endif; ?>
                <?php else: ?>
                    <span id="avatar-initial-view"><?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            
            <p style="font-size: 0.8rem; color: var(--text-muted, rgba(255,255,255,0.5)); margin-bottom: 16px;">
                Maksimal ukuran file: 2 MB (Format: JPG, JPEG, PNG, WEBP).
            </p>

            <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                <form action="profil.php" method="POST" enctype="multipart/form-data" id="form-avatar-upload">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="upload_avatar">
                    <div class="btn btn-primary btn-file-input" style="width: 100%; justify-content: center;">
                        <span class="material-symbols-outlined">photo_camera</span>
                        Pilih & Upload Foto
                        <input type="file" name="avatar" id="avatar-input" accept=".jpg,.jpeg,.png,.webp" onchange="submitAvatarForm()">
                    </div>
                </form>
                
                <?php if (!empty($avatar) && !str_starts_with($avatar, 'http')): ?>
                    <form action="profil.php" method="POST" style="width: 100%;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="delete_avatar">
                        <button type="submit" class="btn btn-outline" style="width: 100%; color: #EF4444; border-color: rgba(239, 68, 68, 0.3); justify-content: center;" onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil ini?')">
                            <span class="material-symbols-outlined">delete</span>
                            Hapus Foto Kustom
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- CARD STATUS AKUN (CARD 3 / CARD 4) -->
        <div class="card">
            <h2>Status Akun</h2>
            <div class="info-status-list">
                <div class="info-status-item">
                    <span class="info-status-label">Status Email</span>
                    <span class="info-status-value">
                        <?php if ($user['email_verified']): ?>
                            <span class="badge-verified">Verified</span>
                        <?php else: ?>
                            <span class="badge-unverified">Belum Terverifikasi</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-status-item">
                    <span class="info-status-label">Metode Login</span>
                    <span class="info-status-value" style="text-transform: capitalize;"><?= htmlspecialchars($user['login_provider'] === 'google' ? 'Google OAuth' : 'Manual') ?></span>
                </div>
                <div class="info-status-item">
                    <span class="info-status-label">Dark Mode</span>
                    <span class="info-status-value" id="darkmode-status-text">Tidak Aktif</span>
                </div>
                <div class="info-status-item">
                    <span class="info-status-label">Tanggal Registrasi</span>
                    <span class="info-status-value"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="info-status-item">
                    <span class="info-status-label">Terakhir Login</span>
                    <span class="info-status-value"><?= !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-' ?></span>
                </div>
            </div>

            <?php if (!$user['email_verified']): ?>
                <div style="margin-top: 16px;">
                    <form action="profil.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="resend_verification">
                        <button type="submit" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 0.82rem;">
                            <span class="material-symbols-outlined">mail</span>
                            Kirim Ulang Email Verifikasi
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KOLOM KANAN: DATA PROFIL & KEAMANAN -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- CARD INFORMASI PROFIL (CARD 1) -->
        <div class="card">
            <h2>Informasi Akun</h2>
            
            <form action="profil.php" method="POST" id="form-update-profile">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="prof-id">ID User</label>
                        <input type="text" id="prof-id" value="<?= $user['id'] ?>" disabled style="background: rgba(255,255,255,0.03); cursor: not-allowed;">
                    </div>
                    <div class="form-group">
                        <label for="prof-role">Role</label>
                        <input type="text" id="prof-role" value="<?= htmlspecialchars(ucfirst($user['role'])) ?>" disabled style="background: rgba(255,255,255,0.03); cursor: not-allowed; text-transform: capitalize;">
                    </div>
                </div>

                <div class="form-group">
                    <label for="prof-email">Email</label>
                    <input type="email" id="prof-email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: rgba(255,255,255,0.03); cursor: not-allowed;">
                    <small style="color: var(--text-muted, rgba(255,255,255,0.4)); display: block; margin-top: 4px;">
                        Email tidak dapat diubah karena digunakan sebagai identitas akun. Hubungi Administrator jika terdesak.
                    </small>
                </div>

                <div class="form-group">
                    <label for="prof-username">Username <span style="color:#EF4444;">*</span></label>
                    <input type="text" id="prof-username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="Tentukan username unik Anda" required>
                </div>

                <div class="form-group">
                    <label for="prof-nama">Nama Lengkap <span style="color:#EF4444;">*</span></label>
                    <input type="text" id="prof-nama" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" placeholder="Nama lengkap Anda" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="prof-hp">Nomor HP / Telepon <span style="color:#EF4444;">*</span></label>
                    <input type="text" id="prof-hp" name="nomor_hp" value="<?= htmlspecialchars($user['nomor_telepon'] ?? '') ?>" placeholder="Contoh: 081234567890" required autocomplete="tel">
                </div>

                <div class="form-group">
                    <label>Metode Pendaftaran</label>
                    <input type="text" value="<?= htmlspecialchars($user['login_provider'] === 'google' ? 'Google OAuth' : 'Pendaftaran Mandiri (Lokal)') ?>" disabled style="background: rgba(255,255,255,0.03); cursor: not-allowed;">
                </div>

                <button type="submit" class="btn btn-primary" id="btn-save-profile" style="margin-top: 8px;">
                    <span class="material-symbols-outlined">save</span>
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- CARD KEAMANAN AKUN (CARD 3) -->
        <div class="card">
            <h2>Keamanan Akun</h2>
            
            <?php if ($user['login_provider'] === 'google'): ?>
                <!-- TAMPILAN GOOGLE LOGIN -->
                <div class="google-info-card">
                    <div class="google-logo-icon">
                        <!-- SVG Google Logo -->
                        <svg width="22" height="22" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v3.92h6.58c-.28 1.48-1.12 2.74-2.38 3.59v2.98h3.85c2.26-2.09 3.69-5.17 3.69-8.42z"/>
                            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.85-2.98c-1.08.72-2.45 1.16-4.08 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.37 24 12 24z"/>
                            <path fill="#FBBC05" d="M5.27 14.31c-.25-.72-.39-1.49-.39-2.31s.14-1.59.39-2.31V6.6H1.29C.47 8.22 0 10.06 0 12s.47 3.78 1.29 5.4l3.98-3.09z"/>
                            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.93 1.19 15.22 0 12 0 7.37 0 3.26 2.7 1.29 6.6l3.98 3.09c.95-2.85 3.6-4.94 6.73-4.94z"/>
                        </svg>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">Login Menggunakan Google</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted, rgba(255,255,255,0.6)); max-width: 320px; margin: 0 auto;">
                        Kata sandi akun Anda saat ini dikelola secara penuh oleh Google. Untuk meningkatkan perlindungan, kelola keamanan langsung pada setelan Google Akun Anda.
                    </p>
                </div>
            <?php else: ?>
                <!-- FORM GANTI PASSWORD MANUAL -->
                <form action="profil.php" method="POST" id="form-change-password">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="pass-lama">Password Lama <span style="color:#EF4444;">*</span></label>
                        <input type="password" id="pass-lama" name="password_lama" placeholder="Ketikkan password Anda saat ini" required>
                    </div>

                    <div class="form-group">
                        <label for="pass-baru">Password Baru <span style="color:#EF4444;">*</span></label>
                        <input type="password" id="pass-baru" name="password_baru" placeholder="Minimal 8 karakter" required>
                    </div>

                    <div class="form-group">
                        <label for="pass-conf">Konfirmasi Password Baru <span style="color:#EF4444;">*</span></label>
                        <input type="password" id="pass-conf" name="konfirmasi_password" placeholder="Ulangi password baru Anda" required>
                    </div>

                    <button type="submit" class="btn btn-outline" id="btn-save-password" style="margin-top: 8px;">
                        <span class="material-symbols-outlined">key</span>
                        Simpan Password Baru
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Loading State Handling saat Submit Form
const formsToWatch = ['form-update-profile', 'form-change-password'];
formsToWatch.forEach(formId => {
    const el = document.getElementById(formId);
    if (!el) return;
    el.addEventListener('submit', function() {
        const submitBtn = el.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        }
    });
});

// Otomatis Submit Form Avatar ketika input file berubah
function submitAvatarForm() {
    const form = document.getElementById('form-avatar-upload');
    if (form) {
        form.submit();
    }
}

// Sinkronisasi status deteksi Dark Mode aktif
document.addEventListener("DOMContentLoaded", function() {
    const isDark = document.documentElement.classList.contains('dark-mode');
    const textStatus = document.getElementById('darkmode-status-text');
    if (textStatus) {
        textStatus.textContent = isDark ? 'Aktif' : 'Tidak Aktif';
    }
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

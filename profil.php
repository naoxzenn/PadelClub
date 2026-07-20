<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validasi Session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/helpers/TokenHelper.php';
require_once __DIR__ . '/helpers/MailHelper.php';
/** @var mysqli $conn */

if (!function_exists('formatTanggalIndonesia')) {
    function formatTanggalIndonesia($datetime)
    {
        if (empty($datetime))
            return '-';
        $bulan = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];
        $time = strtotime($datetime);
        $tgl = date('j', $time);
        $bln = (int) date('n', $time);
        $thn = date('Y', $time);
        return $tgl . ' ' . $bulan[$bln] . ' ' . $thn;
    }
}

$user_id = (int) $_SESSION['user_id'];

// Proteksi CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Pengaturan Profil';
if (!isset($baseUrl)) {
    $baseUrl = '';
}

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
        if (isset($user['login_provider']) && $user['login_provider'] === 'google') {
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
        if (isset($user['email_verified']) && $user['email_verified']) {
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

<section class="section profile-page-section">
    <div class="container profile-redesign-container">

        <!-- NOTICE BANNER JIKA EMAIL BELUM DIVERIFIKASI -->
        <?php if (isset($user['email_verified']) && !$user['email_verified']): ?>
            <div class="alert alert-warning profile-notice-banner" style="margin-bottom: 24px;">
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

        <!-- SATU CARD BESAR UTAMA -->
        <div class="profile-main-card">

            <!-- HEADER AKUN (Foto Profil, Nama, Role Badge, Tombol Edit Profil, Tombol Ganti Password) -->
            <div class="profile-card-header">
                <div class="profile-avatar-info-group">
                    <div class="profile-avatar-wrapper">
                        <?php
                        $dispAvatar = $user['avatar'] ?? '';
                        $dispName = $user['nama_lengkap'] ?? $_SESSION['nama'] ?? 'User';
                        $dispRole = $user['role'] ?? $_SESSION['role'] ?? 'customer';
                        if (!empty($dispAvatar)):
                            if (str_starts_with($dispAvatar, 'http')): ?>
                                <img src="<?= htmlspecialchars($dispAvatar) ?>" alt="Avatar">
                            <?php else: ?>
                                <img src="<?= $baseUrl ?? '' ?>uploads/profile/<?= htmlspecialchars($dispAvatar) ?>"
                                    alt="Avatar">
                            <?php endif; ?>
                        <?php else: ?>
                            <?= strtoupper(substr($dispName, 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <div class="profile-identity-details">
                        <h2 class="profile-user-name"><?= htmlspecialchars($dispName) ?></h2>
                        <div class="profile-meta-badges">
                            <?php
                            $roleClass = $dispRole === 'admin' ? 'admin' : ($dispRole === 'kasir' ? 'kasir' : 'customer');
                            $roleLabel = ucfirst($dispRole === 'customer' ? 'User' : $dispRole);
                            ?>
                            <span class="profile-role-badge <?= $roleClass ?>">
                                <span class="material-symbols-outlined" style="font-size: 14px;">verified_user</span>
                                <?= htmlspecialchars($roleLabel) ?>
                            </span>

                            <span class="profile-status-badge">
                                <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span>
                                Aktif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- INFORMASI AKUN (Format Label : Value dengan Aligned Colons dan Thin Dividers) -->
                <div class="profile-card-body">
                    <div class="profile-section-title">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        Informasi Akun
                    </div>

                    <div class="profile-info-grid">

                        <!-- 1. Nama -->
                        <div class="profile-info-row-item">
                            <div class="profile-info-label">Nama</div>
                            <div class="profile-info-colon"></div>
                            <div class="profile-info-value">
                                <div class="profile-value-text-wrap">
                                    <span
                                        class="profile-value-text"><?= htmlspecialchars($user['nama_lengkap'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="profile-info-divider"></div>

                        <!-- 2. Username -->
                        <div class="profile-info-row-item">
                            <div class="profile-info-label">Username</div>
                            <div class="profile-info-colon"></div>
                            <div class="profile-info-value">
                                <div class="profile-value-text-wrap">
                                    <span class="profile-value-text">
                                        <?php
                                        $dispUsername = !empty($user['username']) ? $user['username'] : explode('@', $user['email'])[0];
                                        echo htmlspecialchars($dispUsername);
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="profile-info-divider"></div>

                        <!-- 3. Email -->
                        <div class="profile-info-row-item">
                            <div class="profile-info-label">Email</div>
                            <div class="profile-info-colon"></div>
                            <div class="profile-info-value">
                                <div class="profile-value-text-wrap">
                                    <span
                                        class="profile-value-text"><?= htmlspecialchars($user['email'] ?? '-') ?></span>
                                </div>
                                <?php if (isset($user['email_verified']) && $user['email_verified']): ?>
                                    <div class="profile-badge-sub-row">
                                        <span class="badge badge-success"
                                            style="font-size: 0.72rem; padding: 2px 8px; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                            <span class="material-symbols-outlined" style="font-size: 13px;">verified</span>
                                            Verified
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="profile-info-divider"></div>

                        <!-- 4. Nomor HP -->
                        <div class="profile-info-row-item">
                            <div class="profile-info-label">Nomor HP</div>
                            <div class="profile-info-colon"></div>
                            <div class="profile-info-value">
                                <div class="profile-value-text-wrap">
                                    <span class="profile-value-text">
                                        <?php
                                        $noHp = $user['nomor_telepon'] ?? $user['phone'] ?? '-';
                                        echo htmlspecialchars(!empty($noHp) ? $noHp : '-');
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="profile-info-divider"></div>

                        <!-- 5. Status -->
                        <div class="profile-info-row-item">
                            <div class="profile-info-label">Status</div>
                            <div class="profile-info-colon"></div>
                            <div class="profile-info-value">
                                <div class="profile-value-text-wrap">
                                    <span class="profile-value-text"
                                        style="color: #22c55e; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                                        <span
                                            style="width: 8px; height: 8px; border-radius: 50%; background-color: #22c55e; display: inline-block;"></span>
                                        Aktif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="profile-info-divider"></div>

                        <!-- 6. Tanggal Gabung -->
                        <div class="profile-info-row-item">
                            <div class="profile-info-label">Tanggal Gabung</div>
                            <div class="profile-info-colon"></div>
                            <div class="profile-info-value">
                                <div class="profile-value-text-wrap">
                                    <span
                                        class="profile-value-text"><?= formatTanggalIndonesia($user['created_at'] ?? date('Y-m-d')) ?></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- PUSAT PENGATURAN AKUN -->
                <div class="profile-card-body"
                    style="border-top: 1px solid var(--border); background: var(--surface-alt); padding: 24px 32px; border-bottom-left-radius: var(--radius-lg); border-bottom-right-radius: var(--radius-lg);">
                    <div class="profile-section-title" style="margin-bottom: 16px;">
                        <span class="material-symbols-outlined">settings_suggest</span>
                        Pusat Pengaturan Akun
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                        <button type="button" class="btn btn-outline"
                            onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                            style="justify-content: flex-start; gap: 10px; padding: 12px 16px; font-size: 0.88rem; background: var(--card-bg);">
                            <span class="material-symbols-outlined" style="color: var(--blue);">person</span>
                            Informasi Akun
                        </button>
                        <button type="button" class="btn btn-outline" onclick="openProfileModal('modal-edit-profile')"
                            style="justify-content: flex-start; gap: 10px; padding: 12px 16px; font-size: 0.88rem; background: var(--card-bg);">
                            <span class="material-symbols-outlined" style="color: var(--blue);">edit</span>
                            Edit Profil
                        </button>
                        <button type="button" class="btn btn-outline"
                            onclick="openProfileModal('modal-change-password')"
                            style="justify-content: flex-start; gap: 10px; padding: 12px 16px; font-size: 0.88rem; background: var(--card-bg);">
                            <span class="material-symbols-outlined" style="color: #F59E0B;">key</span>
                            Ganti Password
                        </button>
                        <?php if (($user['role'] ?? $_SESSION['role'] ?? 'customer') === 'customer'): ?>
                            <a href="dashboarduser.php?scroll=riwayat" class="btn btn-outline"
                                style="justify-content: flex-start; gap: 10px; padding: 12px 16px; font-size: 0.88rem; text-decoration: none; color: inherit; background: var(--card-bg);">
                                <span class="material-symbols-outlined" style="color: var(--green);">history</span>
                                Riwayat Booking
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
</section>

<!-- MODAL 1: EDIT PROFIL -->
<div id="modal-edit-profile" class="profile-modal-overlay" style="display: none;">
    <div class="profile-modal-box">
        <div class="profile-modal-header">
            <h3><span class="material-symbols-outlined">edit</span> Edit Informasi Profil</h3>
            <button type="button" class="profile-modal-close"
                onclick="closeProfileModal('modal-edit-profile')">&times;</button>
        </div>
        <form action="profil.php" method="POST" id="form-update-profile">
            <div class="profile-modal-body">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="prof-nama" style="font-weight: 600; margin-bottom: 6px; display: block;">Nama Lengkap
                        <span class="text-danger">*</span></label>
                    <input type="text" id="prof-nama" name="nama_lengkap" class="form-control"
                        value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required autocomplete="name">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="prof-username" style="font-weight: 600; margin-bottom: 6px; display: block;">Username
                        <span class="text-danger">*</span></label>
                    <input type="text" id="prof-username" name="username" class="form-control"
                        value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="prof-email" style="font-weight: 600; margin-bottom: 6px; display: block;">Email
                        (Read-only)</label>
                    <input type="email" id="prof-email" class="form-control"
                        value="<?= htmlspecialchars($user['email']) ?>" readonly disabled
                        style="opacity: 0.7; cursor: not-allowed;">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="prof-hp" style="font-weight: 600; margin-bottom: 6px; display: block;">Nomor HP /
                        Telepon <span class="text-danger">*</span></label>
                    <input type="text" id="prof-hp" name="nomor_hp" class="form-control"
                        value="<?= htmlspecialchars($user['nomor_telepon'] ?? '') ?>" required autocomplete="tel">
                </div>
            </div>
            <div class="profile-modal-footer">
                <button type="button" class="btn btn-outline"
                    onclick="closeProfileModal('modal-edit-profile')">Batal</button>
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined">save</span> Simpan
                    Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: GANTI PASSWORD -->
<div id="modal-change-password" class="profile-modal-overlay" style="display: none;">
    <div class="profile-modal-box">
        <div class="profile-modal-header">
            <h3><span class="material-symbols-outlined">key</span> Ganti Password</h3>
            <button type="button" class="profile-modal-close"
                onclick="closeProfileModal('modal-change-password')">&times;</button>
        </div>
        <?php if (isset($user['login_provider']) && $user['login_provider'] === 'google'): ?>
            <div class="profile-modal-body">
                <div class="alert alert-info" style="margin: 0;">
                    <span class="material-symbols-outlined">info</span>
                    <div>
                        <strong>Akun Google Login</strong>
                        <p style="margin: 4px 0 0 0;">Password dikelola secara langsung dan aman oleh Google.</p>
                    </div>
                </div>
            </div>
            <div class="profile-modal-footer">
                <button type="button" class="btn btn-outline"
                    onclick="closeProfileModal('modal-change-password')">Tutup</button>
            </div>
        <?php else: ?>
            <form action="profil.php" method="POST" id="form-change-password">
                <div class="profile-modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="pass-lama" style="font-weight: 600; margin-bottom: 6px; display: block;">Password Lama
                            <span class="text-danger">*</span></label>
                        <input type="password" id="pass-lama" name="password_lama" class="form-control"
                            placeholder="Ketikkan password saat ini" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="pass-baru" style="font-weight: 600; margin-bottom: 6px; display: block;">Password Baru
                            <span class="text-danger">*</span></label>
                        <input type="password" id="pass-baru" name="password_baru" class="form-control"
                            placeholder="Minimal 8 karakter" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="pass-conf" style="font-weight: 600; margin-bottom: 6px; display: block;">Konfirmasi
                            Password Baru <span class="text-danger">*</span></label>
                        <input type="password" id="pass-conf" name="konfirmasi_password" class="form-control"
                            placeholder="Ulangi password baru" required>
                    </div>
                </div>
                <div class="profile-modal-footer">
                    <button type="button" class="btn btn-outline"
                        onclick="closeProfileModal('modal-change-password')">Batal</button>
                    <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined">key</span> Ubah
                        Kata Sandi</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    function openProfileModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeProfileModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) {
            el.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        // Backdrop click close
        document.querySelectorAll('.profile-modal-overlay').forEach(modal => {
            modal.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeProfileModal(this.id);
                }
            });
        });

        // Escape key close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.profile-modal-overlay').forEach(m => {
                    closeProfileModal(m.id);
                });
            }
        });

        const forms = ['form-update-profile', 'form-change-password'];
        forms.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('submit', function () {
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
        document.addEventListener("DOMContentLoaded", function () {
            showToast(<?= json_encode($_SESSION['toast_msg']['text']) ?>, <?= json_encode($_SESSION['toast_msg']['type']) ?>);
        });
    </script>
    <?php unset($_SESSION['toast_msg']); ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
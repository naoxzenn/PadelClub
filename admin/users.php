<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Manajemen Staff';
$baseUrl = '../';
$msg = '';
$err = '';

// ---- POST ACTION: TAMBAH STAFF ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_staff') {
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $telp = trim($_POST['nomor_telepon']);
    $role = $_POST['role'];
    
    if ($nama && $email && $pass && in_array($role, ['admin', 'kasir'])) {
        // Cek email unique
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='" . mysqli_real_escape_string($conn, $email) . "'");
        if (mysqli_num_rows($check) > 0) {
            $err = 'Alamat email tersebut sudah terdaftar.';
        } else {
            // Gunakan plain text atau bcrypt password (karena login.php saat ini menggunakan perbandingan string langsung)
            $s = mysqli_prepare($conn, "INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role) VALUES (?, ?, ?, ?, ?)");
            if ($s) {
                mysqli_stmt_bind_param($s, 'sssss', $nama, $email, $pass, $telp, $role);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
                $msg = 'Akun Staff baru (' . $role . ') berhasil ditambahkan.';
            } else {
                $err = 'Gagal menyimpan akun staff baru.';
            }
        }
    } else {
        $err = 'Mohon lengkapi semua bidang isian dengan benar.';
    }
}

// Fetch staff accounts (admin and cashier roles)
$staffs = mysqli_fetch_all(mysqli_query($conn, "
    SELECT id, nama_lengkap, email, nomor_telepon, role, created_at 
    FROM users 
    WHERE role IN ('admin', 'kasir') 
    ORDER BY role, nama_lengkap
"), MYSQLI_ASSOC);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <?php if ($msg): ?>
            <div class="alert alert-success" style="margin-bottom: 24px;"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger" style="margin-bottom: 24px;"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Manajemen Staff</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Buat dan kelola akun operasional Administrator dan Kasir PadelClub.</p>
        </div>

        <div class="admin-grid-layout">
            
            <!-- Left: Add Staff Form -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Tambah Staff Baru</h2>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="tambah_staff">
                    
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Masukkan nama..." required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" id="email" name="email" placeholder="staff@padelclub.com" required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>

                    <div class="form-group">
                        <label for="password">Password Akses</label>
                        <input type="password" id="password" name="password" placeholder="Masukkan password..." required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>

                    <div class="form-group">
                        <label for="nomor_telepon">Nomor Telepon</label>
                        <input type="text" id="nomor_telepon" name="nomor_telepon" placeholder="Contoh: 0812..." style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>

                    <div class="form-group">
                        <label for="role">Hak Akses / Peran</label>
                        <select id="role" name="role" required style="padding: 10px 12px; font-size: 0.88rem;">
                            <option value="kasir">Kasir</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 0.9rem;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem; vertical-align:middle; margin-right:4px;">person_add</span> Tambahkan Staff
                    </button>
                </form>
            </div>

            <!-- Right: Staff Accounts List -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Daftar Staff Terdaftar</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Hak Akses</th>
                                <th>Tanggal Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffs as $s): ?>
                                <tr>
                                    <td>#<?= $s['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($s['nama_lengkap']) ?></strong></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= htmlspecialchars($s['nomor_telepon'] ?? '-') ?></td>
                                    <td>
                                        <span class="role-badge <?= $s['role'] ?>" style="font-size: 0.68rem; font-weight: 700; padding: 3px 8px; border-radius:4px; text-transform:uppercase;">
                                            <?= $s['role'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

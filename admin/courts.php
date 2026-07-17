<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Manajemen Lapangan';
$baseUrl = '../';
$msg = '';
$err = '';

// ---- POST ACTIONS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'tambah_lapangan') {
        $nama  = trim($_POST['nama_lapangan']);
        $tipe  = $_POST['tipe_lapangan'];
        $harga = (float)$_POST['harga_per_jam'];
        $desk  = trim($_POST['deskripsi']);
        
        if ($nama && in_array($tipe, ['Indoor', 'Outdoor']) && $harga > 0) {
            $s = mysqli_prepare($conn, "INSERT INTO courts (nama_lapangan, tipe_lapangan, harga_per_jam, deskripsi, status) VALUES (?, ?, ?, ?, 'aktif')");
            if ($s) {
                mysqli_stmt_bind_param($s, 'ssds', $nama, $tipe, $harga, $desk);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
                $msg = 'Lapangan baru berhasil ditambahkan.';
            } else {
                $err = 'Gagal menyimpan lapangan baru.';
            }
        } else {
            $err = 'Mohon lengkapi semua input dengan benar.';
        }
    } elseif ($_POST['action'] === 'toggle_court') {
        $cid = (int)$_POST['court_id'];
        $cs  = $_POST['status'];
        
        if (in_array($cs, ['aktif', 'nonaktif'])) {
            $s = mysqli_prepare($conn, "UPDATE courts SET status=? WHERE id=?");
            if ($s) {
                mysqli_stmt_bind_param($s, 'si', $cs, $cid);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
                $msg = 'Status lapangan berhasil diperbarui.';
            } else {
                $err = 'Gagal memperbarui status lapangan.';
            }
        }
    }
}

// Fetch all courts
$courts = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM courts ORDER BY nama_lapangan"), MYSQLI_ASSOC);
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
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Manajemen Lapangan</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Kelola daftar lapangan padel, atur harga, deskripsi, dan status operasional lapangan.</p>
        </div>

        <div class="admin-grid-layout">
            
            <!-- Left: Add Court Form -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Tambah Lapangan Baru</h2>
                <form method="POST" id="form-tambah-lapangan" style="margin: 0;">
                    <input type="hidden" name="action" value="tambah_lapangan">
                    
                    <div class="form-group">
                        <label for="nama_lapangan">Nama Lapangan</label>
                        <input type="text" id="nama_lapangan" name="nama_lapangan" placeholder="Contoh: Lapangan E" required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>
                    
                    <div class="form-group">
                        <label for="tipe_lapangan">Tipe Lapangan</label>
                        <select id="tipe_lapangan" name="tipe_lapangan" required style="padding: 10px 12px; font-size: 0.88rem;">
                            <option value="Indoor">Indoor</option>
                            <option value="Outdoor">Outdoor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="harga_per_jam">Harga per Jam (Rp)</label>
                        <input type="number" id="harga_per_jam" name="harga_per_jam" placeholder="150000" min="0" required style="padding: 10px 12px; font-size: 0.88rem;">
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi Singkat</label>
                        <textarea id="deskripsi" name="deskripsi" placeholder="Deskripsi lapangan..." style="padding: 10px 12px; font-size: 0.88rem; height:80px; resize:none;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success btn-block" style="padding: 12px; font-size: 0.9rem;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem; vertical-align:middle; margin-right:4px;">add_box</span> Tambah Lapangan
                    </button>
                </form>
            </div>

            <!-- Right: Courts List -->
            <div class="card" style="margin: 0; padding: 24px;">
                <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy); margin-bottom: 20px;">Daftar Lapangan</h2>
                <div class="table-responsive">
                    <table id="tabel-lapangan" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Lapangan</th>
                                <th>Tipe</th>
                                <th>Harga/Jam</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courts as $c): ?>
                                <tr>
                                    <td>#<?= $c['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($c['nama_lapangan']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($c['tipe_lapangan']) ?>" style="font-size: 0.65rem; font-weight: 700; padding: 3px 8px;">
                                            <?= $c['tipe_lapangan'] ?>
                                        </span>
                                    </td>
                                    <td>Rp <?= number_format($c['harga_per_jam'], 0, ',', '.') ?></td>
                                    <td style="max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($c['deskripsi']) ?>">
                                        <?= htmlspecialchars($c['deskripsi'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <span class="<?= $c['status']==='aktif' ? 'status-confirmed' : 'status-cancelled' ?>" style="font-size:0.75rem; padding: 3px 10px;">
                                            <?= ucfirst($c['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline; margin:0;">
                                            <input type="hidden" name="action" value="toggle_court">
                                            <input type="hidden" name="court_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $c['status']==='aktif' ? 'nonaktif' : 'aktif' ?>">
                                            <button type="submit" class="btn btn-sm <?= $c['status']==='aktif' ? 'btn-warning' : 'btn-success' ?>" style="padding: 5px 10px; font-size: 0.72rem; border-radius: 6px;">
                                                <?= $c['status']==='aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($courts)): ?>
                                <tr><td colspan="7" style="text-align:center; color:#aaa; padding:24px;">Belum ada lapangan terdaftar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Verifikasi Pembayaran';
$baseUrl = '../';
$msg = '';
$err = '';

// ---- POST ACTION: VERIFIKASI PEMBAYARAN ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifikasi_payment') {
    $pid = (int)$_POST['payment_id'];
    $sv  = $_POST['status_verifikasi'];
    $allowed_sv = ['menunggu', 'terverifikasi', 'ditolak'];
    
    if (in_array($sv, $allowed_sv)) {
        $s = mysqli_prepare($conn, "UPDATE payments SET status_verifikasi=? WHERE id=?");
        if ($s) {
            mysqli_stmt_bind_param($s, 'si', $sv, $pid);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            
            // Auto confirm booking jika terverifikasi
            if ($sv === 'terverifikasi') {
                $bp = mysqli_query($conn, "SELECT booking_id FROM payments WHERE id=$pid");
                $bp = mysqli_fetch_assoc($bp);
                if ($bp) {
                    updateBookingVerification($conn, $bp['booking_id'], 'confirmed', $_SESSION['user_id']);
                }
            } elseif ($sv === 'ditolak') {
                $bp = mysqli_query($conn, "SELECT booking_id FROM payments WHERE id=$pid");
                $bp = mysqli_fetch_assoc($bp);
                if ($bp) {
                    updateBookingVerification($conn, $bp['booking_id'], 'cancelled', $_SESSION['user_id']);
                }
            }
            $msg = 'Verifikasi pembayaran #' . $pid . ' berhasil diperbarui.';
        } else {
            $err = 'Gagal memperbarui verifikasi pembayaran.';
        }
    }
}

// ---- GET PARAMS ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_verif = isset($_GET['status_verifikasi']) ? trim($_GET['status_verifikasi']) : '';

$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'waktu_bayar';
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// Sort whitelist
$allowed_sort = [
    'booking_id' => 'p.booking_id',
    'nama_lengkap' => 'u.nama_lengkap',
    'metode_bayar' => 'p.metode_bayar',
    'jumlah_bayar' => 'p.jumlah_bayar',
    'waktu_bayar' => 'p.waktu_bayar',
    'status_verifikasi' => 'p.status_verifikasi'
];
$sort_col = isset($allowed_sort[$sort]) ? $allowed_sort[$sort] : 'p.waktu_bayar';

// Conditions
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = "(u.nama_lengkap LIKE ? OR u.email LIKE ? OR p.metode_bayar LIKE ?)";
    $like_search = "%$search%";
    $params[] = $like_search;
    $params[] = $like_search;
    $params[] = $like_search;
    $types .= 'sss';
}
if ($status_verif !== '') {
    $conditions[] = "p.status_verifikasi = ?";
    $params[] = $status_verif;
    $types .= 's';
}

$where_clause = '';
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Count total
$count_query = "
    SELECT COUNT(*) 
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    $where_clause
";
$stmt = mysqli_prepare($conn, $count_query);
if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total_rows);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
} else {
    $total_rows = 0;
}

$total_pages = ceil($total_rows / $limit);
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $limit;

// Fetch records
$data_query = "
    SELECT p.*, u.nama_lengkap, u.email, c.nama_lapangan, b.tanggal_booking
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN courts c ON b.court_id = c.id
    $where_clause
    ORDER BY $sort_col $order
    LIMIT ? OFFSET ?
";
$stmt = mysqli_prepare($conn, $data_query);
$payments = [];
if ($stmt) {
    $bind_types = $types . 'ii';
    $bind_params = array_merge($params, [$limit, $offset]);
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $payments = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// Helper functions for URLs
function getSortUrl($col) {
    global $sort, $order, $search, $status_verif;
    $new_order = ($sort === $col && strtolower($order) === 'asc') ? 'desc' : 'asc';
    return "?sort=$col&order=$new_order" . 
        ($search !== '' ? "&search=" . urlencode($search) : '') .
        ($status_verif !== '' ? "&status_verifikasi=" . urlencode($status_verif) : '');
}

function getPageUrl($p) {
    global $sort, $order, $search, $status_verif;
    return "?page=$p" .
        "&sort=$sort&order=" . strtolower($order) .
        ($search !== '' ? "&search=" . urlencode($search) : '') .
        ($status_verif !== '' ? "&status_verifikasi=" . urlencode($status_verif) : '');
}

function sortIcon($col) {
    global $sort, $order;
    if ($sort === $col) {
        return strtolower($order) === 'asc' ? '▲' : '▼';
    }
    return '↕';
}
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
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Verifikasi Pembayaran</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Konfirmasi pembayaran transfer bank dan cash dari pelanggan.</p>
        </div>

        <!-- Filter Form -->
        <div class="card" style="padding: 20px; margin-bottom: 24px;">
            <form method="GET" action="payments.php" class="admin-filters-grid">
                <div class="form-group" style="margin:0;">
                    <label for="search" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Cari Pelanggan/Metode</label>
                    <input type="text" id="search" name="search" placeholder="Nama, email, metode..." value="<?= htmlspecialchars($search) ?>" style="padding: 9px 12px; font-size: 0.88rem;">
                </div>

                <div class="form-group" style="margin:0;">
                    <label for="status_verifikasi" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Status Verifikasi</label>
                    <select id="status_verifikasi" name="status_verifikasi" style="padding: 9px 12px; font-size: 0.88rem;">
                        <option value="">Semua Status</option>
                        <option value="menunggu" <?= $status_verif === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="terverifikasi" <?= $status_verif === 'terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                        <option value="ditolak" <?= $status_verif === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 16px; font-size: 0.85rem; height: 42px;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem;">filter_alt</span> Saring
                    </button>
                    <?php if ($search !== '' || $status_verif !== ''): ?>
                        <a href="payments.php" class="btn btn-outline" style="padding: 10px 16px; font-size: 0.85rem; height: 42px; display:inline-flex; align-items:center; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="card" style="padding: 24px;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><a href="<?= getSortUrl('booking_id') ?>" style="color:inherit; text-decoration:none;">Booking # <?= sortIcon('booking_id') ?></a></th>
                            <th><a href="<?= getSortUrl('nama_lengkap') ?>" style="color:inherit; text-decoration:none;">Customer <?= sortIcon('nama_lengkap') ?></a></th>
                            <th>Lapangan</th>
                            <th>Tanggal Main</th>
                            <th><a href="<?= getSortUrl('metode_bayar') ?>" style="color:inherit; text-decoration:none;">Metode <?= sortIcon('metode_bayar') ?></a></th>
                            <th><a href="<?= getSortUrl('jumlah_bayar') ?>" style="color:inherit; text-decoration:none;">Jumlah <?= sortIcon('jumlah_bayar') ?></a></th>
                            <th>Bukti Transfer</th>
                            <th><a href="<?= getSortUrl('status_verifikasi') ?>" style="color:inherit; text-decoration:none;">Status Verif <?= sortIcon('status_verifikasi') ?></a></th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): 
                            $sv = $p['status_verifikasi'];
                            $sc = $sv === 'terverifikasi' ? 'confirmed' : ($sv === 'ditolak' ? 'cancelled' : 'pending');
                        ?>
                            <tr>
                                <td>#<?= $p['booking_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nama_lengkap']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($p['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($p['nama_lapangan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($p['tanggal_booking'])) ?></td>
                                <td>
                                    <span class="badge" style="background:<?= $p['metode_bayar']==='Transfer' ? '#EEF6FF; color:#0EA5E9;' : '#F0FDF4; color:#16A34A;' ?> font-weight:700;">
                                        <?= $p['metode_bayar'] ?>
                                    </span>
                                </td>
                                <td>Rp <?= number_format($p['jumlah_bayar'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($p['bukti_transfer']): ?>
                                        <a href="../uploads/bukti_transfer/<?= htmlspecialchars($p['bukti_transfer']) ?>" target="_blank" class="btn btn-sm btn-outline" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 6px; display:inline-flex; align-items:center; gap:4px; text-decoration:none;">
                                            <span class="material-symbols-outlined" style="font-size:1rem;">visibility</span> Lihat Bukti
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#aaa; font-style:italic;">Cash / Tanpa Bukti</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-<?= $sc ?>"><?= ucfirst($sv) ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="display:flex; gap:4px; align-items:center; margin:0;">
                                        <input type="hidden" name="action" value="verifikasi_payment">
                                        <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                        <select name="status_verifikasi" style="padding:4px 6px; font-size:13px; border:1px solid var(--border); border-radius:3px;">
                                            <option value="menunggu" <?= $sv==='menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                            <option value="terverifikasi" <?= $sv==='terverifikasi' ? 'selected' : '' ?>>Terverifikasi</option>
                                            <option value="ditolak" <?= $sv==='ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-success" style="padding: 6px 10px; font-size: 0.75rem;">Verif</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="9" style="text-align:center; color:#aaa; padding:24px;">Tidak ada transaksi pembayaran yang ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <?php if ($total_pages > 1): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; flex-wrap:wrap; gap:12px;">
                    <span style="font-size:0.85rem; color:var(--text-muted);">
                        Menampilkan Halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong> (Total <strong><?= $total_rows ?></strong> Transaksi)
                    </span>
                    <div style="display:flex; gap:4px;">
                        <?php if ($page > 1): ?>
                            <a href="<?= getPageUrl($page - 1) ?>" class="btn btn-outline btn-sm" style="padding: 6px 12px; display:inline-flex; align-items:center; text-decoration:none;">Sebelumnya</a>
                        <?php endif; ?>
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <a href="<?= getPageUrl($i) ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="padding: 6px 12px; text-decoration:none;"><?= $i ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="<?= getPageUrl($page + 1) ?>" class="btn btn-outline btn-sm" style="padding: 6px 12px; display:inline-flex; align-items:center; text-decoration:none;">Selanjutnya</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Manajemen Booking';
$baseUrl = '../';
$msg = '';
$err = '';

// ---- POST ACTION: UPDATE STATUS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bid = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    $allowed = ['pending', 'confirmed', 'cancelled'];
    
    if (in_array($status, $allowed)) {
        updateBookingVerification($conn, $bid, $status, $_SESSION['user_id']);
        $msg = 'Status booking #' . $bid . ' berhasil diubah ke ' . $status . '.';
    }
}

// ---- GET PARAMS FOR FILTERS, SEARCH, SORT, PAGINATION ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$tanggal = isset($_GET['tanggal']) ? trim($_GET['tanggal']) : '';
$court_id = isset($_GET['court_id']) ? (int)$_GET['court_id'] : 0;

$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// Sort whitelist
$allowed_sort = [
    'id' => 'b.id',
    'nama_lengkap' => 'u.nama_lengkap',
    'nama_lapangan' => 'c.nama_lapangan',
    'tanggal_booking' => 'b.tanggal_booking',
    'total_harga' => 'b.total_harga',
    'status' => 'b.status',
    'created_at' => 'b.created_at'
];
$sort_col = isset($allowed_sort[$sort]) ? $allowed_sort[$sort] : 'b.created_at';

// Build Query Conditions
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = "(u.nama_lengkap LIKE ? OR u.email LIKE ? OR c.nama_lapangan LIKE ?)";
    $like_search = "%$search%";
    $params[] = $like_search;
    $params[] = $like_search;
    $params[] = $like_search;
    $types .= 'sss';
}
if ($status !== '') {
    $conditions[] = "b.status = ?";
    $params[] = $status;
    $types .= 's';
}
if ($tanggal !== '') {
    $conditions[] = "b.tanggal_booking = ?";
    $params[] = $tanggal;
    $types .= 's';
}
if ($court_id > 0) {
    $conditions[] = "b.court_id = ?";
    $params[] = $court_id;
    $types .= 'i';
}

$where_clause = '';
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// Count total rows
$count_query = "SELECT COUNT(*) FROM bookings b JOIN courts c ON b.court_id = c.id JOIN users u ON b.user_id = u.id $where_clause";
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
    SELECT b.*, c.nama_lapangan, u.nama_lengkap, u.email, u.nomor_telepon
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    JOIN users u ON b.user_id = u.id
    $where_clause
    ORDER BY $sort_col $order
    LIMIT ? OFFSET ?
";
$stmt = mysqli_prepare($conn, $data_query);
$bookings = [];
if ($stmt) {
    $bind_types = $types . 'ii';
    $bind_params = array_merge($params, [$limit, $offset]);
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $bookings = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// Fetch courts list for filter dropdown
$courts_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_lapangan FROM courts ORDER BY nama_lapangan"), MYSQLI_ASSOC);

// Helper URLs
function getSortUrl($col) {
    global $sort, $order, $search, $status, $tanggal, $court_id;
    $new_order = ($sort === $col && strtolower($order) === 'asc') ? 'desc' : 'asc';
    return "?sort=$col&order=$new_order" . 
        ($search !== '' ? "&search=" . urlencode($search) : '') .
        ($status !== '' ? "&status=" . urlencode($status) : '') .
        ($tanggal !== '' ? "&tanggal=" . urlencode($tanggal) : '') .
        ($court_id > 0 ? "&court_id=$court_id" : '');
}

function getPageUrl($p) {
    global $sort, $order, $search, $status, $tanggal, $court_id;
    return "?page=$p" .
        "&sort=$sort&order=" . strtolower($order) .
        ($search !== '' ? "&search=" . urlencode($search) : '') .
        ($status !== '' ? "&status=" . urlencode($status) : '') .
        ($tanggal !== '' ? "&tanggal=" . urlencode($tanggal) : '') .
        ($court_id > 0 ? "&court_id=$court_id" : '');
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
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">
        
        <?php if ($msg): ?>
            <div class="alert alert-success" style="margin-bottom: 24px;"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger" style="margin-bottom: 24px;"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Daftar Booking Lapangan</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Lihat, saring, cari, dan ubah status reservasi lapangan padel.</p>
        </div>

        <!-- Filters Form -->
        <div class="card" style="padding: 20px; margin-bottom: 24px;">
            <form method="GET" action="bookings.php" class="admin-filters-grid">
                <!-- Search bar -->
                <div class="form-group" style="margin:0;">
                    <label for="search" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Cari Pelanggan/Lapangan</label>
                    <input type="text" id="search" name="search" placeholder="Nama, email, lapangan..." value="<?= htmlspecialchars($search) ?>" style="padding: 9px 12px; font-size: 0.88rem;">
                </div>

                <!-- Status Filter -->
                <div class="form-group" style="margin:0;">
                    <label for="status" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Filter Status</label>
                    <select id="status" name="status" style="padding: 9px 12px; font-size: 0.88rem;">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>

                <!-- Tanggal Filter -->
                <div class="form-group" style="margin:0;">
                    <label for="tanggal" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Tanggal Main</label>
                    <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" style="padding: 9px 12px; font-size: 0.88rem;">
                </div>

                <!-- Lapangan Filter -->
                <div class="form-group" style="margin:0;">
                    <label for="court_id" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Pilih Lapangan</label>
                    <select id="court_id" name="court_id" style="padding: 9px 12px; font-size: 0.88rem;">
                        <option value="0">Semua Lapangan</option>
                        <?php foreach ($courts_list as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= $court_id === (int)$cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['nama_lapangan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Actions buttons -->
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 16px; font-size: 0.85rem; height: 42px;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem;">filter_alt</span> Saring
                    </button>
                    <?php if ($search !== '' || $status !== '' || $tanggal !== '' || $court_id > 0): ?>
                        <a href="bookings.php" class="btn btn-outline" style="padding: 10px 16px; font-size: 0.85rem; height: 42px; display:inline-flex; align-items:center; text-decoration:none;">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="card" style="padding: 24px;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th><a href="<?= getSortUrl('id') ?>" style="color:inherit; text-decoration:none;">ID <?= sortIcon('id') ?></a></th>
                            <th><a href="<?= getSortUrl('nama_lengkap') ?>" style="color:inherit; text-decoration:none;">Customer <?= sortIcon('nama_lengkap') ?></a></th>
                            <th><a href="<?= getSortUrl('nama_lapangan') ?>" style="color:inherit; text-decoration:none;">Lapangan <?= sortIcon('nama_lapangan') ?></a></th>
                            <th><a href="<?= getSortUrl('tanggal_booking') ?>" style="color:inherit; text-decoration:none;">Tanggal <?= sortIcon('tanggal_booking') ?></a></th>
                            <th>Waktu</th>
                            <th><a href="<?= getSortUrl('total_harga') ?>" style="color:inherit; text-decoration:none;">Total <?= sortIcon('total_harga') ?></a></th>
                            <th><a href="<?= getSortUrl('status') ?>" style="color:inherit; text-decoration:none;">Status <?= sortIcon('status') ?></a></th>
                            <th>Check-in</th>
                            <th>Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>#<?= $b['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($b['nama_lengkap']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($b['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($b['nama_lapangan']) ?></td>
                                <td><?= date('d/m/Y', strtotime($b['tanggal_booking'])) ?></td>
                                <td><?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?></td>
                                <td>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($b['status'] === 'cancelled'): ?>
                                        <span class="status-cancelled">
                                            <i class="bi bi-x-circle-fill"></i> Dibatalkan
                                            <?php if (!empty($b['cancelled_at'])): ?>
                                            <br><small style="font-size:.7rem;color:#b91c1c;">(<?= date('d/m H:i', strtotime($b['cancelled_at'])) ?>)</small>
                                            <?php endif; ?>
                                        </span>
                                    <?php elseif ($b['status'] === 'confirmed'): ?>
                                        <span class="status-confirmed"><i class="bi bi-check-circle-fill"></i> Confirmed</span>
                                    <?php else: ?>
                                        <span class="status-pending"><i class="bi bi-hourglass-split"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['checkin_status'] === 'Checked In'): ?>
                                        <span style="display:inline-flex; align-items:center; gap:4px; background:rgba(34,197,94,0.1); color:var(--green-dark); padding:3px 10px; border-radius:6px; font-size:0.78rem; font-weight:700;">
                                            <span class="material-symbols-outlined" style="font-size:0.9rem;">how_to_reg</span> Hadir
                                        </span>
                                        <?php if (!empty($b['checkin_time'])): ?>
                                            <div style="font-size:0.7rem; color:var(--text-muted); margin-top:2px;"><?= date('H:i', strtotime($b['checkin_time'])) ?> WIB</div>
                                        <?php endif; ?>
                                    <?php elseif ($b['status'] === 'confirmed' && $b['payment_status'] === 'Verified'): ?>
                                        <span style="display:inline-flex; align-items:center; gap:4px; background:rgba(245,158,11,0.1); color:#D97706; padding:3px 10px; border-radius:6px; font-size:0.78rem; font-weight:700;">
                                            <span class="material-symbols-outlined" style="font-size:0.9rem;">schedule</span> Belum
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.78rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:flex; gap:4px; align-items:center; margin:0;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                        <select name="status" style="padding:4px 6px; font-size:13px; border:1px solid var(--border); border-radius:3px;">
                                            <option value="pending" <?= $b['status']==='pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="confirmed" <?= $b['status']==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="cancelled" <?= $b['status']==='cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary" style="padding: 6px 10px; font-size: 0.75rem;">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="9" style="text-align:center; color:#aaa; padding:24px;">Tidak ada data booking yang cocok.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <?php if ($total_pages > 1): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; flex-wrap:wrap; gap:12px;">
                    <span style="font-size:0.85rem; color:var(--text-muted);">
                        Menampilkan Halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong> (Total <strong><?= $total_rows ?></strong> Baris)
                    </span>
                    <div style="display:flex; gap:4px;">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <a href="<?= getPageUrl($page - 1) ?>" class="btn btn-outline btn-sm" style="padding: 6px 12px; display:inline-flex; align-items:center; text-decoration:none;">Sebelumnya</a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <a href="<?= getPageUrl($i) ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="padding: 6px 12px; text-decoration:none;"><?= $i ?></a>
                        <?php endfor; ?>

                        <!-- Next Page -->
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

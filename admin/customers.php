<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Daftar Customers';
$baseUrl = '../';

// ---- GET PARAMS ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// Whitelist sorting columns
$allowed_sort = [
    'nama_lengkap' => 'u.nama_lengkap',
    'email' => 'u.email',
    'nomor_telepon' => 'u.nomor_telepon',
    'total_booking' => 'total_booking',
    'created_at' => 'u.created_at'
];
$sort_col = isset($allowed_sort[$sort]) ? $allowed_sort[$sort] : 'u.created_at';

// Conditions
$conditions = ["u.role = 'customer'"];
$params = [];
$types = '';

if ($search !== '') {
    $conditions[] = "(u.nama_lengkap LIKE ? OR u.email LIKE ? OR u.nomor_telepon LIKE ?)";
    $like_search = "%$search%";
    $params[] = $like_search;
    $params[] = $like_search;
    $params[] = $like_search;
    $types .= 'sss';
}

$where_clause = "WHERE " . implode(" AND ", $conditions);

// Count query
$count_query = "SELECT COUNT(*) FROM users u $where_clause";
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
    SELECT u.*, COUNT(b.id) AS total_booking 
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    $where_clause
    GROUP BY u.id 
    ORDER BY $sort_col $order
    LIMIT ? OFFSET ?
";
$stmt = mysqli_prepare($conn, $data_query);
$customers = [];
if ($stmt) {
    $bind_types = $types . 'ii';
    $bind_params = array_merge($params, [$limit, $offset]);
    mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $customers = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// Helpers
function getSortUrl($col) {
    global $sort, $order, $search;
    $new_order = ($sort === $col && strtolower($order) === 'asc') ? 'desc' : 'asc';
    return "?sort=$col&order=$new_order" . ($search !== '' ? "&search=" . urlencode($search) : '');
}

function getPageUrl($p) {
    global $sort, $order, $search;
    return "?page=$p" .
        "&sort=$sort&order=" . strtolower($order) .
        ($search !== '' ? "&search=" . urlencode($search) : '');
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
        
        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Daftar Customers</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Kelola data pengguna terdaftar dengan peran customer dan monitor tingkat keaktifan booking mereka.</p>
        </div>

        <!-- Filter Form -->
        <div class="card" style="padding: 20px; margin-bottom: 24px;">
            <form method="GET" action="customers.php" class="admin-search-form">
                <div class="form-group" style="margin:0;">
                    <label for="search" style="font-size: 0.7rem; font-weight: 700; margin-bottom: 6px;">Cari Nama, Email, atau Telepon</label>
                    <input type="text" id="search" name="search" placeholder="Masukkan kata kunci pencarian..." value="<?= htmlspecialchars($search) ?>" style="padding: 9px 12px; font-size: 0.88rem;">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 16px; font-size: 0.85rem; height: 42px;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem;">search</span> Cari
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="customers.php" class="btn btn-outline" style="padding: 10px 16px; font-size: 0.85rem; height: 42px; display:inline-flex; align-items:center; text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="card" style="padding: 24px;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#No</th>
                            <th><a href="<?= getSortUrl('nama_lengkap') ?>" style="color:inherit; text-decoration:none;">Nama Lengkap <?= sortIcon('nama_lengkap') ?></a></th>
                            <th><a href="<?= getSortUrl('email') ?>" style="color:inherit; text-decoration:none;">Email <?= sortIcon('email') ?></a></th>
                            <th><a href="<?= getSortUrl('nomor_telepon') ?>" style="color:inherit; text-decoration:none;">Nomor Telepon <?= sortIcon('nomor_telepon') ?></a></th>
                            <th><a href="<?= getSortUrl('total_booking') ?>" style="color:inherit; text-decoration:none;">Total Booking <?= sortIcon('total_booking') ?></a></th>
                            <th><a href="<?= getSortUrl('created_at') ?>" style="color:inherit; text-decoration:none;">Tanggal Bergabung <?= sortIcon('created_at') ?></a></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $i => $u): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['nomor_telepon'] ?? '-') ?></td>
                                <td>
                                    <span class="badge" style="background:#EEF6FF; color:#0EA5E9; font-weight:700; font-size:0.8rem; padding: 4px 10px;">
                                        <?= $u['total_booking'] ?> Booking
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($customers)): ?>
                            <tr><td colspan="6" style="text-align:center; color:#aaa; padding:24px;">Tidak ada data customer yang ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Control -->
            <?php if ($total_pages > 1): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; flex-wrap:wrap; gap:12px;">
                    <span style="font-size:0.85rem; color:var(--text-muted);">
                        Menampilkan Halaman <strong><?= $page ?></strong> dari <strong><?= $total_pages ?></strong> (Total <strong><?= $total_rows ?></strong> Customer)
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

<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Dashboard Kasir';
$baseUrl = '../';
$msg = '';
$msg_type = 'success';

// ---- AKSI ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'confirm_booking') {
        $bid = (int)$_POST['booking_id'];
        // Cek status booking
        $check_res = mysqli_query($conn, "SELECT status FROM bookings WHERE id=$bid");
        $chk = mysqli_fetch_assoc($check_res);
        if ($chk && $chk['status'] === 'cancelled') {
            $msg = 'Gagal memproses: Booking #' . $bid . ' telah dibatalkan.';
            $msg_type = 'error';
        } else {
            $s = mysqli_prepare($conn, "UPDATE bookings SET status='confirmed' WHERE id=?");
            if ($s) {
                mysqli_stmt_bind_param($s, 'i', $bid);
                if (mysqli_stmt_execute($s)) {
                    $msg = 'Booking #' . $bid . ' berhasil dikonfirmasi.';
                } else {
                    $msg = 'Gagal mengonfirmasi booking.';
                    $msg_type = 'error';
                }
                mysqli_stmt_close($s);
            } else {
                die("Query error: " . mysqli_error($conn));
            }
        }
    } elseif ($action === 'cancel_booking') {
        $bid = (int)$_POST['booking_id'];
        $s = mysqli_prepare($conn, "UPDATE bookings SET status='cancelled' WHERE id=?");
        if ($s) {
            mysqli_stmt_bind_param($s, 'i', $bid);
            if (mysqli_stmt_execute($s)) {
                // Cancel payment too if exists
                mysqli_query($conn, "UPDATE payments SET status_verifikasi='ditolak', payment_status='unpaid' WHERE booking_id=$bid");
                $msg = 'Booking #' . $bid . ' berhasil dibatalkan.';
            } else {
                $msg = 'Gagal membatalkan booking.';
                $msg_type = 'error';
            }
            mysqli_stmt_close($s);
        } else {
            die("Query error: " . mysqli_error($conn));
        }
    } elseif ($action === 'pay_cash') {
        $bid = (int)$_POST['booking_id'];
        
        // Ambil data booking untuk hitung total harga
        $res = mysqli_query($conn, "SELECT total_harga, status FROM bookings WHERE id=$bid");
        $booking = mysqli_fetch_assoc($res);
        
        if ($booking) {
            if ($booking['status'] === 'cancelled') {
                $msg = 'Gagal memproses pembayaran: Booking #' . $bid . ' telah dibatalkan.';
                $msg_type = 'error';
            } else {
                $jumlah = (float)$booking['total_harga'];
                $cashier_id = $_SESSION['user_id'];
                $payment_date = date('Y-m-d H:i:s');
                $receipt_number = 'REC-' . date('Ymd') . '-' . sprintf('%04d', $bid);

                // Cek apakah data pembayaran sudah ada
                $check = mysqli_query($conn, "SELECT id FROM payments WHERE booking_id=$bid");
                $payment_row = mysqli_fetch_assoc($check);
                
                if ($payment_row) {
                    // Update
                    $stmt = mysqli_prepare($conn,
                        "UPDATE payments SET 
                            jumlah_bayar = ?, 
                            metode_bayar = 'Cash', 
                            status_verifikasi = 'terverifikasi', 
                            payment_status = 'paid', 
                            payment_date = ?, 
                            cashier_id = ?, 
                            receipt_number = ? 
                         WHERE booking_id = ?"
                    );
                } else {
                    // Insert
                    $stmt = mysqli_prepare($conn,
                        "INSERT INTO payments (booking_id, jumlah_bayar, metode_bayar, status_verifikasi, payment_status, payment_date, cashier_id, receipt_number) 
                         VALUES (?, ?, 'Cash', 'terverifikasi', 'paid', ?, ?, ?)"
                    );
                }
                
                if ($stmt) {
                    if ($payment_row) {
                        mysqli_stmt_bind_param($stmt, 'dsisi', $jumlah, $payment_date, $cashier_id, $receipt_number, $bid);
                    } else {
                        mysqli_stmt_bind_param($stmt, 'idsis', $bid, $jumlah, $payment_date, $cashier_id, $receipt_number);
                    }
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Update booking status to confirmed
                        mysqli_query($conn, "UPDATE bookings SET status='confirmed' WHERE id=$bid");
                        $msg = 'Pembayaran Cash Berhasil! Struk pembayaran telah dibuat dengan nomor ' . $receipt_number . '.';
                        
                        // Tambahkan tanda bahwa struk siap dicetak
                        mysqli_query($conn, "UPDATE payments SET receipt_printed=0 WHERE booking_id=$bid");
                    } else {
                        $msg = 'Gagal memproses pembayaran cash.';
                        $msg_type = 'error';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    die("Query error: " . mysqli_error($conn));
                }
            }
        } else {
            $msg = 'Booking tidak ditemukan.';
            $msg_type = 'error';
        }
    }
}

// ---- AMBIL DATA STATISTIK ----
// 1. Total Cash Income Today
$resIncome = mysqli_query($conn, "SELECT SUM(jumlah_bayar) FROM payments WHERE metode_bayar = 'Cash' AND status_verifikasi = 'terverifikasi' AND DATE(payment_date) = CURDATE()");
$incomeToday = (float)(mysqli_fetch_row($resIncome)[0] ?? 0);

// 2. Pending Confirmations
$resPending = mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
$pendingCount = (int)(mysqli_fetch_row($resPending)[0] ?? 0);

// 3. Total Bookings Today
$resTodayBookings = mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()");
$bookingsTodayCount = (int)(mysqli_fetch_row($resTodayBookings)[0] ?? 0);

// ---- AMBIL DATA BOOKING PENDING (CONFIRMATION TAB) ----
$pendingBookings = mysqli_fetch_all(mysqli_query($conn,
    "SELECT b.*, c.nama_lapangan, u.nama_lengkap, u.email, u.nomor_telepon
     FROM bookings b
     JOIN courts c ON b.court_id = c.id
     JOIN users u ON b.user_id = u.id
     WHERE b.status = 'pending'
     ORDER BY b.created_at DESC"
), MYSQLI_ASSOC);

// ---- AMBIL DATA BOOKING UNTUK PEMBAYARAN (PAYMENT TAB) ----
$unpaidBookings = mysqli_fetch_all(mysqli_query($conn,
    "SELECT b.*, c.nama_lapangan, u.nama_lengkap, u.email, u.nomor_telepon,
            p.metode_bayar, p.status_verifikasi, p.payment_status
     FROM bookings b
     JOIN courts c ON b.court_id = c.id
     JOIN users u ON b.user_id = u.id
     LEFT JOIN payments p ON p.booking_id = b.id
     WHERE b.status != 'cancelled' AND (p.payment_status IS NULL OR p.payment_status != 'paid')
     ORDER BY b.created_at DESC"
), MYSQLI_ASSOC);

// ---- AMBIL DATA STRUK / RECEIPTS (RECEIPT TAB) ----
$receipts = mysqli_fetch_all(mysqli_query($conn,
    "SELECT b.id AS booking_id, b.tanggal_booking, b.jam_mulai, b.jam_selesai,
            c.nama_lapangan, u.nama_lengkap,
            p.receipt_number, p.jumlah_bayar, p.payment_date, p.receipt_printed
     FROM payments p
     JOIN bookings b ON p.booking_id = b.id
     JOIN courts c ON b.court_id = c.id
     JOIN users u ON b.user_id = u.id
     WHERE p.payment_status = 'paid'
     ORDER BY p.payment_date DESC"
), MYSQLI_ASSOC);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 24px;">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Statistik Grid -->
        <div class="dashboard-stat-grid">
            <div class="dashboard-stat-card">
                <div class="stat-card-icon success-icon">
                    <span class="material-symbols-outlined">payments</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value">Rp <?= number_format($incomeToday, 0, ',', '.') ?></span>
                    <span class="stat-card-label">Pendapatan Cash Hari Ini</span>
                </div>
            </div>
            <div class="dashboard-stat-card">
                <div class="stat-card-icon warning-icon">
                    <span class="material-symbols-outlined">hourglass_empty</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $pendingCount ?></span>
                    <span class="stat-card-label">Pending Konfirmasi</span>
                </div>
            </div>
            <div class="dashboard-stat-card">
                <div class="stat-card-icon">
                    <span class="material-symbols-outlined">calendar_today</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $bookingsTodayCount ?></span>
                    <span class="stat-card-label">Booking Hari Ini</span>
                </div>
            </div>
        </div>

        <!-- TABS KASIR -->
        <div class="tabs" id="kasir-tabs" style="margin-bottom: 24px;">
            <button class="tab-btn active" onclick="showTab('tab-overview', this)">Dashboard</button>
            <button class="tab-btn" onclick="showTab('tab-booking-confirm', this)">Booking</button>
            <button class="tab-btn" onclick="showTab('tab-payment', this)">Pembayaran</button>
            <button class="tab-btn" onclick="showTab('tab-receipt', this)">Cetak Struk</button>
            <button class="tab-btn" onclick="showTab('tab-profil', this)">Profil</button>
        </div>

        <!-- TAB: DASHBOARD OVERVIEW -->
        <div id="tab-overview" class="tab-content active">
            <div class="card">
                <h2>Selamat Datang, Kasir PadelClub!</h2>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Gunakan menu navigasi di sebelah kiri atau tab di atas untuk mengelola transaksi di lapangan secara langsung.</p>
                
                <h3 style="margin-bottom: 12px; color: var(--navy); font-weight: 700;">Aktivitas Terakhir</h3>
                <div class="table-responsive">
                    <table style="width:100%;">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Lapangan</th>
                                <th>Total Harga</th>
                                <th>Metode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentQuery = mysqli_query($conn, "
                                SELECT b.id, u.nama_lengkap, c.nama_lapangan, b.total_harga, p.metode_bayar, b.status
                                FROM bookings b
                                JOIN courts c ON b.court_id = c.id
                                JOIN users u ON b.user_id = u.id
                                LEFT JOIN payments p ON p.booking_id = b.id
                                ORDER BY b.created_at DESC LIMIT 5
                            ");
                            while ($r = mysqli_fetch_assoc($recentQuery)):
                            ?>
                                <tr>
                                    <td>#<?= $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                                    <td>Rp <?= number_format($r['total_harga'], 0, ',', '.') ?></td>
                                    <td><?= $r['metode_bayar'] ?? '<span style="color:#aaa;">Belum bayar</span>' ?></td>
                                    <td><span class="status-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: BOOKING CONFIRMATION -->
        <div id="tab-booking-confirm" class="tab-content">
            <div class="card">
                <h2>Konfirmasi Booking (Pending)</h2>
                <p style="color: var(--text-muted); margin-bottom: 16px;">Tinjau dan konfirmasi pesanan lapangan customer yang berstatus pending.</p>
                <div class="table-responsive">
                    <table id="tabel-booking-confirm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Total Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingBookings as $pb): ?>
                                <tr>
                                    <td>#<?= $pb['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($pb['nama_lengkap']) ?></strong><br>
                                        <small><?= htmlspecialchars($pb['nomor_telepon'] ?? $pb['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($pb['nama_lapangan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($pb['tanggal_booking'])) ?></td>
                                    <td><?= substr($pb['jam_mulai'],0,5) ?> - <?= substr($pb['jam_selesai'],0,5) ?></td>
                                    <td>Rp <?= number_format($pb['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="confirm_booking">
                                                <input type="hidden" name="booking_id" value="<?= $pb['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Konfirmasi</button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan booking ini?');">
                                                <input type="hidden" name="action" value="cancel_booking">
                                                <input type="hidden" name="booking_id" value="<?= $pb['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Batalkan</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pendingBookings)): ?>
                                <tr><td colspan="7" style="text-align:center; color:#aaa; padding:16px;">Tidak ada booking pending konfirmasi saat ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PAYMENT (CASH) -->
        <div id="tab-payment" class="tab-content">
            <div class="card">
                <h2>Pembayaran Tunai (Cash)</h2>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Cari booking berdasarkan nama customer atau ID booking untuk memproses pembayaran langsung di kasir.</p>
                
                <div class="search-box-wrap" style="margin-bottom: 24px; position:relative; max-width: 400px;">
                    <input type="text" id="pencarian-booking" placeholder="Cari nama customer, email, lapangan..." 
                           style="width:100%; padding:10px 16px 10px 40px; border-radius:var(--radius-md); border:1px solid var(--border);"
                           onkeyup="filterBookingList()">
                    <span class="material-symbols-outlined" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted);">search</span>
                </div>

                <div class="table-responsive">
                    <table id="tabel-cash-payment">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Lapangan</th>
                                <th>Tanggal Main</th>
                                <th>Jam</th>
                                <th>Total Tagihan</th>
                                <th>Status Bayar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="list-unpaid-bookings">
                            <?php foreach ($unpaidBookings as $ub): ?>
                                <tr class="booking-row-item">
                                    <td class="search-id">#<?= $ub['id'] ?></td>
                                    <td class="search-name">
                                        <strong><?= htmlspecialchars($ub['nama_lengkap']) ?></strong><br>
                                        <small><?= htmlspecialchars($ub['email']) ?></small>
                                    </td>
                                    <td class="search-court"><?= htmlspecialchars($ub['nama_lapangan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($ub['tanggal_booking'])) ?></td>
                                    <td><?= substr($ub['jam_mulai'],0,5) ?> - <?= substr($ub['jam_selesai'],0,5) ?></td>
                                    <td>Rp <?= number_format($ub['total_harga'], 0, ',', '.') ?></td>
                                    <td><span class="badge-status unpaid">Belum Lunas</span></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Proses pembayaran tunai sebesar Rp <?= number_format($ub['total_harga'], 0, ',', '.') ?> untuk booking #<?= $ub['id'] ?>?');">
                                            <input type="hidden" name="action" value="pay_cash">
                                            <input type="hidden" name="booking_id" value="<?= $ub['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Bayar Cash</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($unpaidBookings)): ?>
                                <tr><td colspan="8" style="text-align:center; color:#aaa; padding:16px;">Tidak ada booking yang membutuhkan pembayaran tunai saat ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: RECEIPT / STRUK -->
        <div id="tab-receipt" class="tab-content">
            <div class="card">
                <h2>Cetak Struk Pembayaran</h2>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Daftar transaksi lunas. Anda dapat mencetak struk format PDF untuk diberikan kepada customer.</p>
                
                <div class="table-responsive">
                    <table id="tabel-receipts">
                        <thead>
                            <tr>
                                <th>Nomor Struk</th>
                                <th>ID Booking</th>
                                <th>Nama Customer</th>
                                <th>Lapangan</th>
                                <th>Total Bayar</th>
                                <th>Tanggal Bayar</th>
                                <th>Cetak Struk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receipts as $rc): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($rc['receipt_number']) ?></code></td>
                                    <td>#<?= $rc['booking_id'] ?></td>
                                    <td><?= htmlspecialchars($rc['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($rc['nama_lapangan']) ?></td>
                                    <td><strong>Rp <?= number_format($rc['jumlah_bayar'], 0, ',', '.') ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($rc['payment_date'])) ?></td>
                                    <td>
                                        <a href="generate_receipt.php?booking_id=<?= $rc['booking_id'] ?>" 
                                           target="_blank" class="btn btn-sm btn-secondary" style="display:inline-flex; align-items:center; gap:6px;">
                                            <span class="material-symbols-outlined" style="font-size:16px;">picture_as_pdf</span> PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($receipts)): ?>
                                <tr><td colspan="7" style="text-align:center; color:#aaa; padding:16px;">Belum ada transaksi pembayaran yang selesai.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PROFIL -->
        <div id="tab-profil" class="tab-content">
            <div class="card">
                <h2>Profil Kasir</h2>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Informasi akun kasir Anda yang terdaftar pada sistem PadelClub.</p>
                
                <?php
                $user_id = $_SESSION['user_id'];
                $stmt_prof = mysqli_prepare($conn, "SELECT nama_lengkap, email, nomor_telepon, role, created_at FROM users WHERE id = ?");
                if ($stmt_prof) {
                    mysqli_stmt_bind_param($stmt_prof, 'i', $user_id);
                    mysqli_stmt_execute($stmt_prof);
                    $profil_res = mysqli_stmt_get_result($stmt_prof);
                    $profil = mysqli_fetch_assoc($profil_res);
                    mysqli_stmt_close($stmt_prof);
                } else {
                    $profil = null;
                }
                ?>
                <?php if ($profil): ?>
                    <div style="max-width: 500px; display: grid; grid-template-columns: 150px 1fr; gap: 12px 24px; padding: 20px 0;">
                        <div style="font-weight: 600; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 8px;">Nama Lengkap</div>
                        <div style="border-bottom: 1px solid var(--border); padding-bottom: 8px;"><?= htmlspecialchars($profil['nama_lengkap']) ?></div>
                        
                        <div style="font-weight: 600; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 8px;">Alamat Email</div>
                        <div style="border-bottom: 1px solid var(--border); padding-bottom: 8px;"><?= htmlspecialchars($profil['email']) ?></div>
                        
                        <div style="font-weight: 600; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 8px;">Nomor Telepon</div>
                        <div style="border-bottom: 1px solid var(--border); padding-bottom: 8px;"><?= htmlspecialchars($profil['nomor_telepon'] ?? '-') ?></div>
                        
                        <div style="font-weight: 600; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 8px;">Peran (Role)</div>
                        <div style="border-bottom: 1px solid var(--border); padding-bottom: 8px;"><span class="role-badge kasir" style="background:#3B82F6; color:#fff; padding:2px 8px; border-radius:12px; font-size:12px; font-weight:600;">Kasir</span></div>
                        
                        <div style="font-weight: 600; color: var(--navy); padding-bottom: 8px;">Bergabung Sejak</div>
                        <div style="padding-bottom: 8px;"><?= date('d F Y, H:i', strtotime($profil['created_at'])) ?></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">Gagal mengambil data profil kasir.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<script>
function showTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    if (btn) {
        btn.classList.add('active');
    }
}

function filterBookingList() {
    const query = document.getElementById('pencarian-booking').value.toLowerCase();
    const rows = document.querySelectorAll('.booking-row-item');
    
    rows.forEach(row => {
        const id = row.querySelector('.search-id').textContent.toLowerCase();
        const name = row.querySelector('.search-name').textContent.toLowerCase();
        const court = row.querySelector('.search-court').textContent.toLowerCase();
        
        if (id.includes(query) || name.includes(query) || court.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const tabMap = {
            'booking-confirm': 'tab-booking-confirm',
            'payment': 'tab-payment',
            'receipt': 'tab-receipt',
            'profil': 'tab-profil'
        };
        const tabId = tabMap[tab];
        if (tabId) {
            const btn = document.querySelector(`[onclick*="${tabId}"]`);
            if (btn) {
                btn.click();
            } else {
                showTab(tabId, null);
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'customer') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'kasir') {
        header('Location: kasir/dashboard.php');
    }
    exit;
}
require_once __DIR__ . '/config/koneksi.php';
/** @var mysqli $conn */

$pageTitle = 'Beranda';
$baseUrl = '';



// Filter tipe lapangan (dari search bar) — tanggal & jam masih bersifat tampilan,
// karena sistem belum punya pengecekan ketersediaan real-time per slot.
$filterTipe = $_GET['tipe'] ?? '';
$filterTanggal = $_GET['tanggal'] ?? '';
$filterJam = $_GET['jam'] ?? '';

if (in_array($filterTipe, ['Indoor', 'Outdoor'], true)) {
    $stmtC = mysqli_prepare($conn, "SELECT * FROM courts WHERE status='aktif' AND tipe_lapangan = ? ORDER BY nama_lapangan");
    if ($stmtC) {
        mysqli_stmt_bind_param($stmtC, 's', $filterTipe);
        mysqli_stmt_execute($stmtC);
        $courts = mysqli_fetch_all(mysqli_stmt_get_result($stmtC), MYSQLI_ASSOC);
        mysqli_stmt_close($stmtC);
    } else {
        die("Query error: " . mysqli_error($conn));
    }
} else {
    $result = mysqli_query($conn, "SELECT * FROM courts WHERE status='aktif' ORDER BY tipe_lapangan, nama_lapangan");
    $courts = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Statistik ringkas untuk hero
$totalMember = (int) (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='customer'"))[0] ?? 0);
$totalBookingDone = (int) (mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM bookings WHERE status='confirmed'"))[0] ?? 0);
$totalLapangan = count($courts);

// Gambar lapangan — di-mapping berdasarkan tipe karena tabel courts belum punya kolom foto.
$imgIndoor = [
    'https://lh3.googleusercontent.com/aida-public/AB6AXuBSCIY9tXWWAR8oLSUXjTH37NBn19MYl9OWTUEKpJpciJr2os-hLQH2ZGzBOlirBjamAxtixIiWi6xQpu6NqaKY1R9mksH43K2ajM0RC2Li20JUO1xis1Wt-WR7nhYokvGBqxnlA-pO0BUS_oJyKizd9rPv7ymtSSPgRtg3c6bQJJWQHpnsGE4mDZ4fshuQz7XdudTmEqgpnaibNdv5tza2DYqq6x32t_wUNy9327DEYhCkE2AxBghtSe1PeEosSoV5BCV_h-yr73j8',
    'https://lh3.googleusercontent.com/aida-public/AB6AXuDYFjPFZbQMNYkj2UO_WyllqDjPTkVQN2xMGy851LX2Qf-n7LA22hFNQ4fPtnTCUZdgURKgfBGyTEaqDYxkKpMKUPunDjY85LBrq2kPv_RE72ZvaElkG_vnQG5a7DiREN1WfV1KlAG12kZHDakqt76xCXr-SIljf08Q5TXuyHmkukflcXdOrOwqpVqtRgYc3o1taexAdkCcU7lglsKmqqBHpQ84w0gKtfuILqQ8_ZqdkWhqJIvuV6CxneGAgTtxO6n48pCsSO0LbalF',
];
$imgOutdoor = [
    'https://lh3.googleusercontent.com/aida-public/AB6AXuAKrxj2uE1yr6ds8wxxv_aBb_8Gs1yCzaXd-wxLuYqR__4yXjAN3hJSTTvVzhEfjIVW07XJA8rzsDAwhzB9fTXjKgLw1H3xZe0SAO6EfYdFE3aN21SPyMrzCsMrCXhKeEXXLcUQUZ6NkQJ9kZKgziCCjPWJoacmFcWr5lDjwV6Ob1CO9qUhofZ5qUdIJNEGEJO4PhWeQnVOCvP88h-lBNXFGbOzGAbAqeszi3ejWH_0KDwxZQitWfLKfqdq37Fp46j1ZejJSlLdKq-m',
];
$imgIdxIndoor = 0;
$imgIdxOutdoor = 0;
?>
<?php include_once __DIR__ . '/includes/header.php'; ?>

<!-- Hero section -->
<header class="hero">
    <div class="hero-bg"
        style="background-image:url('https://lh3.googleusercontent.com/aida-public/AB6AXuCrPOZIYAwRmKTaO0PSdNGCgidt7tqaR3zcSJhxGcllwwH4aLWnUw20kT7jEUsXTILfNe5l-W_UvbpMyuDHy-VtAzHvWFaA-CoJcflE-nil8LXuPbpcdd7bErE5LkFo6MrZGRL0A7lKCjQVIBw8dqPrqPgeDKAiCwWWYiHLETJkpub6ux8sBVCDjlf5e0ajldRmEnF9skVttMksDpHDN4EMahLBAT2VAHAphfPQl7XrwzWs2WawitPFletSA6xfUKvpQFqqSf6LMhro');">
    </div>
    <div class="container hero-content">
        <h1>Main Padel Lebih Seru Bersama Komunitas Terbaik</h1>
        <p class="lead">Booking lapangan dalam hitungan detik, pantau riwayat transaksimu, dan temukan lapangan indoor
            maupun outdoor di PadelClub.</p>
        <div class="hero-actions">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                <a href="booking.php" class="btn btn-primary">Booking Sekarang</a>
                <a href="dashboarduser.php" class="btn btn-secondary glass-panel">Riwayat Saya</a>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-primary">Booking Sekarang</a>
                <a href="login.php" class="btn btn-secondary glass-panel">Masuk</a>
            <?php else: ?>
                <a href="dashboardadmin.php" class="btn btn-primary">Ke Dashboard Admin</a>
            <?php endif; ?>
        </div>

        <div class="hero-stats">
            <div class="stat glass-panel">
                <div class="number"><?= $totalLapangan ?>+</div>
                <div class="label">Lapangan</div>
            </div>
            <div class="stat glass-panel">
                <div class="number"><?= $totalMember ?>+</div>
                <div class="label">Member</div>
            </div>
            <div class="stat glass-panel">
                <div class="number"><?= $totalBookingDone ?>+</div>
                <div class="label">Booking Selesai</div>
            </div>
            <div class="stat glass-panel">
                <div class="number">Indoor &amp; Outdoor</div>
                <div class="label">Tipe Lapangan</div>
            </div>
        </div>
    </div>
</header>

<div class="container search-float">
    <form class="search-card" method="GET" action="index.php">
        <div class="form-group">
            <label for="tanggal">Pilih Tanggal</label>
            <input type="date" id="tanggal" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>">
        </div>
        <div class="form-group">
            <label for="jam">Jam Main</label>
            <select id="jam" name="jam">
                <option value="" <?= $filterJam === '' ? 'selected' : '' ?>>Semua Jam</option>
                <option value="08:00-10:00" <?= $filterJam === '08:00-10:00' ? 'selected' : '' ?>>08:00 - 10:00</option>
                <option value="10:00-12:00" <?= $filterJam === '10:00-12:00' ? 'selected' : '' ?>>10:00 - 12:00</option>
                <option value="16:00-18:00" <?= $filterJam === '16:00-18:00' ? 'selected' : '' ?>>16:00 - 18:00</option>
            </select>
        </div>
        <div class="form-group">
            <label for="tipe">Tipe Lapangan</label>
            <select id="tipe" name="tipe">
                <option value="" <?= $filterTipe === '' ? 'selected' : '' ?>>Semua Lapangan</option>
                <option value="Indoor" <?= $filterTipe === 'Indoor' ? 'selected' : '' ?>>Indoor</option>
                <option value="Outdoor" <?= $filterTipe === 'Outdoor' ? 'selected' : '' ?>>Outdoor</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Cari Lapangan</button>
    </form>
</div>

<div class="container">
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'logout'): ?>
            <div class="alert alert-success">Anda berhasil keluar. Sampai jumpa!</div>
        <?php elseif ($_GET['msg'] === 'cancelled'): ?>
            <div class="alert alert-warning">Booking Anda telah dibatalkan.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!--Lapangan unggulan section-->
<section class="section" id="lapangan" style="padding-top: 0;">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>Lapangan Unggulan</h2>
                <p>Pilih lapangan terbaik sesuai gaya bermainmu</p>
            </div>
            <?php if ($filterTipe): ?>
                <a href="index.php" class="see-all">Reset Filter <span class="material-symbols-outlined">close</span></a>
            <?php endif; ?>
        </div>

        <?php if (empty($courts)): ?>
            <div class="alert alert-info">Belum ada lapangan tersedia untuk filter ini.</div>
        <?php else: ?>
            <div class="court-grid-img">
                <?php foreach ($courts as $court): ?>
                    <?php
                    if ($court['tipe_lapangan'] === 'Indoor') {
                        $img = $imgIndoor[$imgIdxIndoor % count($imgIndoor)];
                        $imgIdxIndoor++;
                    } else {
                        $img = $imgOutdoor[$imgIdxOutdoor % count($imgOutdoor)];
                        $imgIdxOutdoor++;
                    }
                    ?>
                    <div class="court-card">
                        <div class="court-card-img-wrap">
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($court['nama_lapangan']) ?>">
                            <span
                                class="badge badge-<?= strtolower($court['tipe_lapangan']) ?>"><?= $court['tipe_lapangan'] ?></span>
                        </div>
                        <div class="court-card-body">
                            <h3 style="font-size:1.1rem; font-weight:700; color:var(--navy); margin-bottom:8px;">
                                <?= htmlspecialchars($court['nama_lapangan']) ?>
                            </h3>
                            <p class="court-desc"><?= htmlspecialchars($court['deskripsi'] ?? '-') ?></p>
                            <div class="detail-row" style="border:none; padding:0; margin-bottom:16px;">
                                <span class="court-price" style="margin-bottom:0;">
                                    Rp <?= number_format($court['harga_per_jam'], 0, ',', '.') ?>
                                    <small>/jam</small>
                                </span>
                            </div>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                                <a href="booking.php?court_id=<?= $court['id'] ?>" class="btn btn-primary btn-block">Booking
                                    Sekarang</a>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn btn-secondary btn-block">Login untuk Booking</a>
                            <?php else: ?>
                                <span class="btn btn-secondary btn-block" style="cursor:default;">Mode Admin</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Kenapa PadelClub section -->
<section class="section section-white">
    <div class="container">
        <div class="section-title-center">
            <h2>Kenapa PadelClub?</h2>
            <p>Fasilitas kelas dunia yang dirancang khusus untuk kenyamanan dan performa maksimal pemain padel masa
                kini.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">public</span></div>
                <h3>Standard Internasional</h3>
                <p>Lapangan kami menggunakan rumput sintetis dan sistem kaca berstandar turnamen.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">bolt</span></div>
                <h3>Instant Booking</h3>
                <p>Proses booking 100% digital, langsung tersimpan ke akun dan dashboard kamu.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">groups</span></div>
                <h3>Komunitas Aktif</h3>
                <p>Gampang cari lawan main lewat sesama member yang aktif booking tiap minggu.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">payments</span></div>
                <h3>Pembayaran Fleksibel</h3>
                <p>Transfer bank dengan upload bukti, atau bayar cash langsung di tempat.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">sports_tennis</span></div>
                <h3>Indoor &amp; Outdoor</h3>
                <p>Pilih sesuai mood — lapangan ber-AC atau outdoor dengan suasana terbuka.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><span class="material-symbols-outlined">shield</span></div>
                <h3>Aman &amp; Terverifikasi</h3>
                <p>Setiap pembayaran diverifikasi langsung oleh tim admin sebelum dikonfirmasi.</p>
            </div>
        </div>
    </div>
</section>

<!--Cara booking section-->
<section class="section">
    <div class="container">
        <div class="section-title-center">
            <h2>Cara Booking</h2>
            <p>Empat langkah simpel dari daftar sampai main di lapangan.</p>
        </div>
        <div class="steps-grid">
            <div>
                <div class="step-num">1</div>
                <strong>Daftar / Login</strong>
                <p>Buat akun atau login ke sistem.</p>
            </div>
            <div>
                <div class="step-num">2</div>
                <strong>Pilih Lapangan</strong>
                <p>Pilih lapangan yang tersedia.</p>
            </div>
            <div>
                <div class="step-num">3</div>
                <strong>Pilih Paket</strong>
                <p>Tentukan waktu dan paket bermain.</p>
            </div>
            <div>
                <div class="step-num">4</div>
                <strong>Bayar</strong>
                <p>Transfer atau bayar tunai di tempat.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA section -->
<section class="container" style="padding-bottom: 64px;">
    <div class="cta-banner">
        <h2>Siap Main Padel Hari Ini?</h2>
        <p>Booking lapangan favoritmu sekarang, gampang dan cuma butuh beberapa klik.</p>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
            <a href="booking.php" class="btn btn-primary">Booking Sekarang</a>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn btn-primary">Daftar &amp; Booking</a>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
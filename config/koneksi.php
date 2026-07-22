<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'MyPadel');

// Buat koneksi
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// ---- AUTO MIGRATION (DEVELOPMENT/PRODUCTION SCHEMA SYNC) ----
// 1. Alter users table to support 'kasir' role
$check_role = mysqli_query($conn, "DESCRIBE users");
if ($check_role) {
    while ($row = mysqli_fetch_assoc($check_role)) {
        if ($row['Field'] === 'role' && strpos($row['Type'], 'kasir') === false) {
            mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'kasir', 'customer') DEFAULT 'customer'");
        }
    }
}

// 1a. Alter users table to support Google Login & avatar
$check_users = mysqli_query($conn, "DESCRIBE users");
if ($check_users) {
    $user_cols = [];
    while ($row = mysqli_fetch_assoc($check_users)) {
        $user_cols[] = $row['Field'];
    }
    if (!in_array('google_id', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL AFTER role");
    }
    if (!in_array('avatar', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER google_id");
    }
    if (!in_array('login_provider', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN login_provider ENUM('local', 'google') DEFAULT 'local' AFTER avatar");
    }
    if (!in_array('email_verified', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER login_provider");
    }
    if (!in_array('verification_token', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL AFTER email_verified");
    }
    if (!in_array('verified_at', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN verified_at DATETIME NULL AFTER verification_token");
    }
    if (!in_array('reset_token', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL AFTER verified_at");
    }
    if (!in_array('reset_expired_at', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN reset_expired_at DATETIME NULL AFTER reset_token");
    }
    if (!in_array('username', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN username VARCHAR(150) NULL UNIQUE AFTER email");
    }
    if (!in_array('last_login', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER reset_expired_at");
    }
    if (!in_array('updated_at', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER last_login");
    }
    if (!in_array('phone', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER nomor_telepon");
        mysqli_query($conn, "UPDATE users SET phone = nomor_telepon WHERE phone IS NULL AND nomor_telepon IS NOT NULL");
    }
    if (!in_array('full_name', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN full_name VARCHAR(150) NULL AFTER nama_lengkap");
        mysqli_query($conn, "UPDATE users SET full_name = nama_lengkap WHERE full_name IS NULL AND nama_lengkap IS NOT NULL");
    }
    // Kolom untuk autentikasi REST API (token-based, terpisah dari session web)
    if (!in_array('api_token', $user_cols)) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN api_token VARCHAR(64) NULL UNIQUE AFTER reset_expired_at");
    }
}

// 1b. Add cancelled_at column to bookings table (for soft cancel feature)
$check_bookings = mysqli_query($conn, "DESCRIBE bookings");
if ($check_bookings) {
    $booking_cols = [];
    while ($row = mysqli_fetch_assoc($check_bookings)) {
        $booking_cols[] = $row['Field'];
    }
    if (!in_array('cancelled_at', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN cancelled_at DATETIME NULL AFTER status");
    }
}


// 2. Alter payments table to add necessary columns
$check_pay = mysqli_query($conn, "DESCRIBE payments");
if ($check_pay) {
    $cols = [];
    while ($row = mysqli_fetch_assoc($check_pay)) {
        $cols[] = $row['Field'];
    }
    if (!in_array('payment_status', $cols)) {
        mysqli_query($conn, "ALTER TABLE payments ADD COLUMN payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid'");
    }
    if (!in_array('payment_date', $cols)) {
        mysqli_query($conn, "ALTER TABLE payments ADD COLUMN payment_date DATETIME NULL");
    }
    if (!in_array('cashier_id', $cols)) {
        mysqli_query($conn, "ALTER TABLE payments ADD COLUMN cashier_id INT NULL");
    }
    if (!in_array('receipt_number', $cols)) {
        mysqli_query($conn, "ALTER TABLE payments ADD COLUMN receipt_number VARCHAR(100) NULL");
    }
    if (!in_array('receipt_printed', $cols)) {
        mysqli_query($conn, "ALTER TABLE payments ADD COLUMN receipt_printed TINYINT(1) DEFAULT 0");
    }
    if (!in_array('created_at', $cols)) {
        mysqli_query($conn, "ALTER TABLE payments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    }
    if (!in_array('updated_at', $cols)) {
        mysqli_query($conn, "ALTER TABLE payments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
    // Check if metode_bayar column definition contains 'QRIS'
    $check_mb = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'metode_bayar'");
    if ($check_mb) {
        $row_mb = mysqli_fetch_assoc($check_mb);
        if ($row_mb && strpos($row_mb['Type'], 'QRIS') === false) {
            mysqli_query($conn, "UPDATE payments SET metode_bayar = 'QRIS' WHERE metode_bayar = 'Transfer'");
            mysqli_query($conn, "ALTER TABLE payments MODIFY COLUMN metode_bayar ENUM('QRIS', 'Cash', 'Transfer') NOT NULL");
        }
    }
}

// 3. Ensure default test users exist and have plain-text passwords for development mode
$check_admin = mysqli_query($conn, "SELECT id FROM users WHERE email='admin@MyPadel.com'");
if (mysqli_num_rows($check_admin) > 0) {
    mysqli_query($conn, "UPDATE users SET password='password' WHERE email='admin@MyPadel.com'");
} else {
    mysqli_query($conn, "INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role) VALUES ('Administrator', 'admin@MyPadel.com', 'password', '081234567890', 'admin')");
}

$check_kasir = mysqli_query($conn, "SELECT id FROM users WHERE email='kasir@MyPadel.com'");
if (mysqli_num_rows($check_kasir) === 0) {
    mysqli_query($conn, "INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role) VALUES ('Kasir Utama', 'kasir@MyPadel.com', 'password', '089876543210', 'kasir')");
}

// 4. Ensure backup_logs table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filesize BIGINT NOT NULL,
    created_by VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    browser VARCHAR(255) NOT NULL,
    note TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// ---- INIZIALISASI PDO KONEKSI GLOBAL ----
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Koneksi PDO gagal: " . $e->getMessage());
}

// ---- MIGRASI STRUKTUR TABEL BOOKINGS UNTUK QR & CHECK-IN ----
$check_bookings = mysqli_query($conn, "DESCRIBE bookings");
if ($check_bookings) {
    $booking_cols = [];
    while ($row = mysqli_fetch_assoc($check_bookings)) {
        $booking_cols[] = $row['Field'];
    }
    if (!in_array('booking_code', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN booking_code VARCHAR(40) UNIQUE NULL AFTER status");
    }
    if (!in_array('checkin_token', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN checkin_token CHAR(64) NULL AFTER booking_code");
        mysqli_query($conn, "ALTER TABLE bookings ADD KEY idx_checkin_token (checkin_token)");
    }
    if (!in_array('checkin_generated_at', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN checkin_generated_at DATETIME NULL AFTER checkin_token");
    }
    if (!in_array('payment_status', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN payment_status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending' AFTER checkin_generated_at");
    }
    if (!in_array('checkin_status', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN checkin_status ENUM('Not Checked In', 'Checked In') DEFAULT 'Not Checked In' AFTER payment_status");
    }
    if (!in_array('checkin_time', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN checkin_time DATETIME NULL AFTER checkin_status");
    }
    if (!in_array('verified_at', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN verified_at DATETIME NULL AFTER checkin_time");
    }
    if (!in_array('verified_by', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN verified_by INT NULL AFTER verified_at");
    }
    if (!in_array('checkin_ip', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN checkin_ip VARCHAR(45) NULL AFTER verified_by");
    }
    if (!in_array('checkin_browser', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN checkin_browser VARCHAR(255) NULL AFTER checkin_ip");
    }
    if (!in_array('checkin_by', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN checkin_by INT NULL AFTER checkin_browser");
    }
}

// ---- MIGRASI TABEL OPERATING_HOURS & HOLIDAYS ----
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS `operating_hours` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `day_of_week` TINYINT(4) NOT NULL COMMENT '1=Senin ... 7=Minggu',
  `open_time` TIME NOT NULL,
  `close_time` TIME NOT NULL,
  `open_time_2` TIME NULL,
  `close_time_2` TIME NULL,
  `is_open` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_day_of_week` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$ophours_cols_res = mysqli_query($conn, "SHOW COLUMNS FROM operating_hours");
if ($ophours_cols_res) {
    $ophours_cols = [];
    while ($col = mysqli_fetch_assoc($ophours_cols_res)) {
        $ophours_cols[] = $col['Field'];
    }
    if (!in_array('open_time_2', $ophours_cols)) {
        mysqli_query($conn, "ALTER TABLE operating_hours ADD COLUMN open_time_2 TIME NULL AFTER close_time");
    }
    if (!in_array('close_time_2', $ophours_cols)) {
        mysqli_query($conn, "ALTER TABLE operating_hours ADD COLUMN close_time_2 TIME NULL AFTER open_time_2");
    }
}

$check_ophours = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM operating_hours");
if ($check_ophours) {
    $row_ophours = mysqli_fetch_assoc($check_ophours);
    if ((int)$row_ophours['cnt'] === 0) {
        mysqli_query($conn, "INSERT INTO `operating_hours` (`day_of_week`, `open_time`, `close_time`, `is_open`) VALUES
            (1, '07:00:00', '22:00:00', 1),
            (2, '07:00:00', '22:00:00', 1),
            (3, '07:00:00', '22:00:00', 1),
            (4, '07:00:00', '22:00:00', 1),
            (5, '07:00:00', '22:00:00', 1),
            (6, '06:00:00', '23:00:00', 1),
            (7, '06:00:00', '23:00:00', 1)
        ");
    }
}

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `holiday_date` DATE NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ---- HELPER SINKRONISASI PEMBAYARAN DAN STATUS BOOKING ----
if (!function_exists('updateBookingVerification')) {
    function updateBookingVerification($conn, $booking_id, $status, $verifier_id = null, $sendEmail = true, $metode_bayar = null, $cashier_id = null) {
        $status = strtolower(trim($status));
        $booking_id = (int)$booking_id;
        if ($booking_id <= 0) return false;

        // Normalize status aliases
        if (in_array($status, ['confirmed', 'terverifikasi', 'lunas'])) {
            $normalizedStatus = 'confirmed';
        } elseif (in_array($status, ['rejected_pending', 'gagal'])) {
            $normalizedStatus = 'rejected_pending';
        } elseif (in_array($status, ['cancelled', 'ditolak', 'batal'])) {
            $normalizedStatus = 'cancelled';
        } else {
            $normalizedStatus = 'pending';
        }

        // Fetch current booking & payment details
        $resB = mysqli_query($conn, "SELECT total_harga, booking_code, checkin_token FROM bookings WHERE id = $booking_id");
        if (!$resB || mysqli_num_rows($resB) == 0) return false;
        $bookingRow = mysqli_fetch_assoc($resB);
        $total_harga = (float)$bookingRow['total_harga'];

        $resP = mysqli_query($conn, "SELECT id, receipt_number, metode_bayar FROM payments WHERE booking_id = $booking_id");
        $paymentRow = $resP ? mysqli_fetch_assoc($resP) : null;

        $now = date('Y-m-d H:i:s');

        if ($normalizedStatus === 'confirmed') {
            // Check checkin_token generation (must be generated once with random_bytes(32))
            $tokenUpdateSql = "";
            if (empty($bookingRow['checkin_token'])) {
                $newToken = bin2hex(random_bytes(32));
                $tokenUpdateSql = ", checkin_token = '$newToken', checkin_generated_at = '$now'";
            }

            // 1. Update Bookings table
            $code = $bookingRow['booking_code'];
            if (empty($code)) {
                $code = 'BK' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 14));
                while (true) {
                    $check = mysqli_query($conn, "SELECT id FROM bookings WHERE booking_code = '$code'");
                    if (mysqli_num_rows($check) == 0) break;
                    $code = 'BK' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 14));
                }
            }
            $vby = $verifier_id ? (int)$verifier_id : 'NULL';
            mysqli_query($conn, "UPDATE bookings SET 
                status = 'confirmed',
                booking_code = '$code',
                payment_status = 'Verified',
                verified_at = '$now',
                verified_by = $vby
                $tokenUpdateSql
                WHERE id = $booking_id");

            // 2. Sync Payments table
            $metode = $metode_bayar ? mysqli_real_escape_string($conn, $metode_bayar) : ($paymentRow['metode_bayar'] ?? 'QRIS');
            $cid = $cashier_id ? (int)$cashier_id : ($verifier_id ? (int)$verifier_id : 'NULL');
            $rec_num = 'REC-' . date('Ymd') . '-' . sprintf('%04d', $booking_id);

            if (!$paymentRow) {
                mysqli_query($conn, "INSERT INTO payments 
                    (booking_id, jumlah_bayar, metode_bayar, status_verifikasi, payment_status, waktu_bayar, payment_date, cashier_id, receipt_number) 
                    VALUES 
                    ($booking_id, $total_harga, '$metode', 'terverifikasi', 'paid', '$now', '$now', $cid, '$rec_num')");
            } else {
                $rec = !empty($paymentRow['receipt_number']) ? mysqli_real_escape_string($conn, $paymentRow['receipt_number']) : $rec_num;
                mysqli_query($conn, "UPDATE payments SET 
                    status_verifikasi = 'terverifikasi',
                    payment_status = 'paid',
                    waktu_bayar = IFNULL(waktu_bayar, '$now'),
                    payment_date = IFNULL(payment_date, '$now'),
                    metode_bayar = '$metode',
                    cashier_id = IFNULL($cid, cashier_id),
                    receipt_number = '$rec'
                    WHERE booking_id = $booking_id");
            }

        } elseif ($normalizedStatus === 'rejected_pending') {
            // 1. Update Bookings table
            mysqli_query($conn, "UPDATE bookings SET 
                status = 'pending',
                payment_status = 'Rejected'
                WHERE id = $booking_id");

            // 2. Sync Payments table
            $metode = $metode_bayar ? mysqli_real_escape_string($conn, $metode_bayar) : ($paymentRow['metode_bayar'] ?? 'QRIS');
            if (!$paymentRow) {
                mysqli_query($conn, "INSERT INTO payments 
                    (booking_id, jumlah_bayar, metode_bayar, status_verifikasi, payment_status, waktu_bayar, payment_date) 
                    VALUES 
                    ($booking_id, $total_harga, '$metode', 'ditolak', 'unpaid', '$now', '$now')");
            } else {
                mysqli_query($conn, "UPDATE payments SET 
                    status_verifikasi = 'ditolak',
                    payment_status = 'unpaid',
                    waktu_bayar = IFNULL(waktu_bayar, '$now'),
                    payment_date = IFNULL(payment_date, '$now'),
                    metode_bayar = '$metode'
                    WHERE booking_id = $booking_id");
            }

        } elseif ($normalizedStatus === 'cancelled') {
            // 1. Update Bookings table
            mysqli_query($conn, "UPDATE bookings SET 
                status = 'cancelled',
                payment_status = 'Rejected'
                WHERE id = $booking_id");

            // 2. Sync Payments table
            if ($paymentRow) {
                mysqli_query($conn, "UPDATE payments SET 
                    status_verifikasi = 'ditolak',
                    payment_status = 'unpaid'
                    WHERE booking_id = $booking_id");
            }

        } else {
            // Pending reset
            // 1. Update Bookings table
            mysqli_query($conn, "UPDATE bookings SET 
                status = 'pending',
                payment_status = 'Pending'
                WHERE id = $booking_id");

            // 2. Sync Payments table
            if ($paymentRow) {
                mysqli_query($conn, "UPDATE payments SET 
                    status_verifikasi = 'menunggu',
                    payment_status = 'unpaid'
                    WHERE booking_id = $booking_id");
            }
        }

        // 3. Send email notification based on status
        if ($sendEmail && ($normalizedStatus === 'confirmed' || $normalizedStatus === 'cancelled' || $normalizedStatus === 'rejected_pending')) {
            $resDetails = mysqli_query($conn, "
                SELECT b.id, b.tanggal_booking, b.jam_mulai, b.jam_selesai, b.total_harga, b.booking_code,
                       c.nama_lapangan, u.nama_lengkap, u.email
                FROM bookings b
                JOIN courts c ON b.court_id = c.id
                JOIN users u ON b.user_id = u.id
                WHERE b.id = $booking_id
            ");
            if ($resDetails) {
                $details = mysqli_fetch_assoc($resDetails);
                if ($details) {
                    require_once __DIR__ . '/../helpers/MailHelper.php';
                    $emailData = [
                        'nama_lengkap' => $details['nama_lengkap'],
                        'id' => $details['id'],
                        'nama_lapangan' => $details['nama_lapangan'],
                        'tanggal_booking' => $details['tanggal_booking'],
                        'jam_mulai' => $details['jam_mulai'],
                        'jam_selesai' => $details['jam_selesai'],
                        'total_harga' => $details['total_harga'],
                        'booking_code' => $details['booking_code'] ?? '',
                        'reason' => $normalizedStatus === 'rejected_pending' ? 'Simulasi pembayaran QRIS dibatalkan/gagal oleh pengguna.' : ($normalizedStatus === 'cancelled' ? 'Pembayaran ditolak atau booking dibatalkan oleh admin/petugas.' : '')
                    ];

                    if ($normalizedStatus === 'confirmed') {
                        MailHelper::send($details['email'], 'Pembayaran Terverifikasi & Booking Confirmed - PadelClub', 'payment-verified', $emailData);
                    } else {
                        MailHelper::send($details['email'], 'Booking Lapangan Dibatalkan - PadelClub', 'payment-rejected', $emailData);
                    }
                }
            }
        }

        return true;
    }
}

// ---- AUTO SINKRONISASI DATA LAMA JIKA ADA YANG MISSING ----
$fallback_res = mysqli_query($conn, "SELECT id FROM bookings WHERE status = 'confirmed' AND booking_code IS NULL");
if ($fallback_res && mysqli_num_rows($fallback_res) > 0) {
    while ($row = mysqli_fetch_assoc($fallback_res)) {
        updateBookingVerification($conn, $row['id'], 'confirmed', null, false);
    }
}

// ---- AUTO SINKRONISASI TOKEN UNTUK BOOKING VERIFIED LAMA ----
$fallback_token_res = mysqli_query($conn, "SELECT id FROM bookings WHERE payment_status = 'Verified' AND (checkin_token IS NULL OR checkin_token = '')");
if ($fallback_token_res && mysqli_num_rows($fallback_token_res) > 0) {
    while ($row = mysqli_fetch_assoc($fallback_token_res)) {
        $bid = (int)$row['id'];
        $t = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');
        mysqli_query($conn, "UPDATE bookings SET checkin_token = '$t', checkin_generated_at = '$now' WHERE id = $bid AND (checkin_token IS NULL OR checkin_token = '')");
    }
}

/**
 * Helper Global: Format durasi booking menjadi jam dan menit yang ramah pengguna
 * Contoh:
 * 180 menit -> 3 Jam
 * 177 menit -> 2 Jam 57 Menit
 * 150 menit -> 2 Jam 30 Menit
 * 60 menit  -> 1 Jam
 * 45 menit  -> 45 Menit
 * 30 menit  -> 30 Menit
 * 
 * @param mixed $param1 Total menit (int/float), float jam desimal, atau jam_mulai (string)
 * @param string|null $jamSelesai Jam selesai (string) jika param1 adalah jam_mulai
 * @return string Output terformat
 */
if (!function_exists('formatDurasi')) {
    function formatDurasi($param1, $jamSelesai = null) {
        $totalMenit = 0;

        if ($jamSelesai !== null && !empty($param1)) {
            // Evaluasi jam_mulai dan jam_selesai
            $t1 = strtotime((string)$param1);
            $t2 = strtotime((string)$jamSelesai);
            if ($t1 !== false && $t2 !== false && $t2 > $t1) {
                $totalMenit = (int)round(($t2 - $t1) / 60);
            }
        } elseif (is_numeric($param1)) {
            $val = (float)$param1;
            // Jika angka <= 24, anggap jam desimal (misal 2.95 jam = 177 menit, 1.5 jam = 90 menit)
            // Jika angka > 24, anggap menit langsung (misal 177 menit, 180 menit)
            if ($val <= 24.0) {
                $totalMenit = (int)round($val * 60);
            } else {
                $totalMenit = (int)round($val);
            }
        }

        if ($totalMenit <= 0) {
            return '0 Menit';
        }

        $jam = (int)floor($totalMenit / 60);
        $menit = (int)($totalMenit % 60);

        if ($jam > 0 && $menit > 0) {
            return $jam . ' Jam ' . $menit . ' Menit';
        } elseif ($jam > 0) {
            return $jam . ' Jam';
        } else {
            return $menit . ' Menit';
        }
    }
}
?>
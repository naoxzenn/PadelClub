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
    if (!in_array('payment_status', $booking_cols)) {
        mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN payment_status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending' AFTER booking_code");
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

// ---- HELPER SINKRONISASI PEMBAYARAN DAN STATUS BOOKING ----
if (!function_exists('updateBookingVerification')) {
    function updateBookingVerification($conn, $booking_id, $status, $verifier_id = null) {
        $status = strtolower($status);
        $booking_id = (int)$booking_id;
        
        if ($status === 'confirmed') {
            $res = mysqli_query($conn, "SELECT booking_code FROM bookings WHERE id = $booking_id");
            $row = mysqli_fetch_assoc($res);
            if ($row && empty($row['booking_code'])) {
                $code = 'BK' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 14));
                while (true) {
                    $check = mysqli_query($conn, "SELECT id FROM bookings WHERE booking_code = '$code'");
                    if (mysqli_num_rows($check) == 0) break;
                    $code = 'BK' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 14));
                }
                $now = date('Y-m-d H:i:s');
                $vby = $verifier_id ? (int)$verifier_id : 'NULL';
                mysqli_query($conn, "UPDATE bookings SET 
                    status = 'confirmed',
                    booking_code = '$code',
                    payment_status = 'Verified',
                    verified_at = '$now',
                    verified_by = $vby
                    WHERE id = $booking_id");
            } else {
                mysqli_query($conn, "UPDATE bookings SET 
                    status = 'confirmed',
                    payment_status = 'Verified'
                    WHERE id = $booking_id");
            }
        } elseif ($status === 'cancelled') {
            mysqli_query($conn, "UPDATE bookings SET 
                status = 'cancelled',
                payment_status = 'Rejected'
                WHERE id = $booking_id");
        } else {
            mysqli_query($conn, "UPDATE bookings SET 
                status = 'pending',
                payment_status = 'Pending'
                WHERE id = $booking_id");
        }
    }
}

// ---- AUTO SINKRONISASI DATA LAMA JIKA ADA YANG MISSING ----
$fallback_res = mysqli_query($conn, "SELECT id FROM bookings WHERE status = 'confirmed' AND booking_code IS NULL");
if ($fallback_res && mysqli_num_rows($fallback_res) > 0) {
    while ($row = mysqli_fetch_assoc($fallback_res)) {
        updateBookingVerification($conn, $row['id'], 'confirmed');
    }
}
?>
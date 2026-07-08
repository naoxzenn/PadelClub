<?php
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

// Migrasi otomatis skema database.
$check_role = mysqli_query($conn, "DESCRIBE users");
if ($check_role) {
    while ($row = mysqli_fetch_assoc($check_role)) {
        if ($row['Field'] === 'role' && strpos($row['Type'], 'kasir') === false) {
            mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'kasir', 'customer') DEFAULT 'customer'");
        }
    }
}

// Migrasi otomatis kolom cancelled_at di tabel bookings
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
?>

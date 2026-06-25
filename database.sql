-- ============================================
-- DATABASE: MyPadel
-- Sistem Informasi Booking Lapangan Padel
-- ============================================

CREATE DATABASE IF NOT EXISTS MyPadel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE MyPadel;

-- Tabel USERS
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nomor_telepon VARCHAR(20),
    role ENUM('admin','customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel COURTS
CREATE TABLE IF NOT EXISTS courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lapangan VARCHAR(100) NOT NULL,
    tipe_lapangan ENUM('Indoor','Outdoor') NOT NULL,
    harga_per_jam DECIMAL(10,2) NOT NULL,
    deskripsi TEXT,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel BOOKINGS
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    court_id INT NOT NULL,
    tanggal_booking DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    paket ENUM('per_jam','per_match') DEFAULT 'per_jam',
    sewa_raket TINYINT(1) DEFAULT 0,
    catatan TEXT,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
);

-- Tabel PAYMENTS
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    waktu_bayar DATETIME DEFAULT CURRENT_TIMESTAMP,
    jumlah_bayar DECIMAL(10,2) NOT NULL,
    metode_bayar ENUM('Transfer','Cash') NOT NULL,
    bukti_transfer VARCHAR(255),
    status_verifikasi ENUM('menunggu','terverifikasi','ditolak') DEFAULT 'menunggu',
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- ============================================
-- DATA AWAL (SEED DATA)
-- ============================================

-- Admin default
-- Password: admin123
-- Hash dibuat dengan: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role) VALUES
('Administrator', 'admin@MyPadel.com', '$2y$10$TKh8H1.PfKwMZBbHqaVpK.Y6WPwO7a7Oj6zVn5.TNzeMf1B9n6NK6', '081234567890', 'admin');

-- Data lapangan
INSERT INTO courts (nama_lapangan, tipe_lapangan, harga_per_jam, deskripsi) VALUES
('Lapangan A', 'Indoor', 150000, 'Lapangan indoor ber-AC dengan lantai vinyl premium'),
('Lapangan B', 'Indoor', 150000, 'Lapangan indoor standar dengan pencahayaan LED'),
('Lapangan C', 'Outdoor', 100000, 'Lapangan outdoor dengan view taman yang indah'),
('Lapangan D', 'Outdoor', 100000, 'Lapangan outdoor dengan permukaan artificial grass');

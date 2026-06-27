-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 27 Jun 2026 pada 13.56
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mypadel`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `court_id` int(11) NOT NULL,
  `tanggal_booking` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `paket` enum('per_jam','per_match') DEFAULT 'per_jam',
  `sewa_raket` tinyint(1) DEFAULT 0,
  `catatan` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `court_id`, `tanggal_booking`, `jam_mulai`, `jam_selesai`, `total_harga`, `paket`, `sewa_raket`, `catatan`, `status`, `created_at`) VALUES
(1, 2, 2, '2026-05-25', '16:00:00', '20:00:00', 600000.00, '', 0, '', 'confirmed', '2026-05-13 04:41:00'),
(2, 3, 2, '2026-06-03', '20:00:00', '22:00:00', 350000.00, '', 1, '', 'confirmed', '2026-05-13 04:48:41'),
(3, 3, 1, '2026-05-20', '18:00:00', '20:00:00', 300000.00, '', 0, '', 'confirmed', '2026-05-13 07:03:52'),
(4, 3, 2, '2026-05-21', '20:00:00', '22:00:00', 350000.00, '', 1, '', 'pending', '2026-05-13 07:05:19'),
(5, 3, 1, '2026-05-20', '20:00:00', '22:00:00', 300000.00, '', 0, '', 'confirmed', '2026-05-13 07:25:24'),
(6, 4, 3, '2026-06-27', '20:30:00', '22:30:00', 200000.00, '', 0, '', 'cancelled', '2026-06-27 10:15:17'),
(7, 4, 2, '2026-06-29', '18:00:00', '21:30:00', 525000.00, '', 0, '', 'cancelled', '2026-06-27 10:30:26'),
(8, 4, 3, '2026-06-29', '18:00:00', '20:00:00', 200000.00, '', 0, '', 'cancelled', '2026-06-27 10:32:36'),
(9, 4, 2, '2026-06-29', '15:00:00', '19:00:00', 600000.00, '', 0, '', 'confirmed', '2026-06-27 10:44:03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `courts`
--

CREATE TABLE `courts` (
  `id` int(11) NOT NULL,
  `nama_lapangan` varchar(100) NOT NULL,
  `tipe_lapangan` enum('Indoor','Outdoor') NOT NULL,
  `harga_per_jam` decimal(10,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `courts`
--

INSERT INTO `courts` (`id`, `nama_lapangan`, `tipe_lapangan`, `harga_per_jam`, `deskripsi`, `status`, `created_at`) VALUES
(1, 'Lapangan A', 'Indoor', 150000.00, 'Lapangan indoor ber-AC dengan lantai vinyl premium', 'aktif', '2026-05-13 04:33:07'),
(2, 'Lapangan B', 'Indoor', 150000.00, 'Lapangan indoor standar dengan pencahayaan LED', 'aktif', '2026-05-13 04:33:07'),
(3, 'Lapangan C', 'Outdoor', 100000.00, 'Lapangan outdoor dengan view taman yang indah', 'aktif', '2026-05-13 04:33:07'),
(4, 'Lapangan D', 'Outdoor', 100000.00, 'Lapangan outdoor dengan permukaan artificial grass', 'aktif', '2026-05-13 04:33:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `waktu_bayar` datetime DEFAULT current_timestamp(),
  `jumlah_bayar` decimal(10,2) NOT NULL,
  `metode_bayar` enum('Transfer','Cash') NOT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `status_verifikasi` enum('menunggu','terverifikasi','ditolak') DEFAULT 'menunggu',
  `payment_status` enum('unpaid','paid') DEFAULT 'unpaid',
  `payment_date` datetime DEFAULT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `receipt_printed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `waktu_bayar`, `jumlah_bayar`, `metode_bayar`, `bukti_transfer`, `status_verifikasi`, `payment_status`, `payment_date`, `cashier_id`, `receipt_number`, `receipt_printed`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-05-13 11:41:09', 600000.00, 'Cash', '', 'terverifikasi', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-06-27 11:49:50'),
(2, 2, '2026-05-13 11:48:45', 350000.00, 'Cash', '', 'menunggu', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-06-27 11:49:50'),
(3, 3, '2026-05-13 14:04:06', 300000.00, 'Cash', '', 'menunggu', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-06-27 11:49:50'),
(4, 4, '2026-05-13 14:19:15', 350000.00, 'Transfer', 'bukti_4_1778656755.jpg', 'menunggu', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-06-27 11:49:50'),
(5, 5, '2026-05-13 14:26:24', 300000.00, 'Transfer', 'bukti_5_1778657184.jpg', 'terverifikasi', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-06-27 11:49:50'),
(6, 9, '2026-06-27 17:44:13', 600000.00, 'Cash', '', 'terverifikasi', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-06-27 11:49:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `role` enum('admin','kasir','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `email`, `password`, `nomor_telepon`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@padelmania.com', 'adminmania', '081234567890', 'admin', '2026-05-13 04:33:05'),
(2, 'zaenal arief', 'zaenlips4@gmail.com', '$2y$10$uigHox52Zori5fUOE5H7z.BLJHTmD3W5sDASU/0Hph5KnUnDduEO6', '0812971392', 'admin', '2026-05-13 04:40:08'),
(3, 'Arga Putra', 'argagamteng@gmail.com', '$2y$10$2OVYZ5XOJ7vlDC/tVc5AxeSiV0ZoVfUOi93aoqTF2mL/4bHRoAnHK', '018472189', 'customer', '2026-05-13 04:47:33'),
(4, 'Almer', 'Almer45@gmail.com', '$2y$10$bIECJZYxCvm7aoYXIXG1DeHn/M7aFrF/pqH3oXPnrAdaCDSZSjm.G', '082233009810', 'customer', '2026-06-27 10:13:26'),
(5, 'amba', 'amba69@gmail.com', '123456', '0893292183', 'customer', '2026-06-27 11:11:55'),
(6, 'Kasir PadelClub', 'kasir@padelclub.com', 'kasir123', '081234567890', 'kasir', '2026-06-27 11:55:15');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `court_id` (`court_id`);

--
-- Indeks untuk tabel `courts`
--
ALTER TABLE `courts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `courts`
--
ALTER TABLE `courts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `User` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lapangan` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`);

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

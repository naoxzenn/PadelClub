-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Jul 2026 pada 03.52
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
-- Struktur dari tabel `backup_logs`
--

CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filesize` bigint(20) NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('success','failed') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `browser` varchar(255) NOT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `backup_logs`
--

INSERT INTO `backup_logs` (`id`, `filename`, `filesize`, `created_by`, `created_at`, `status`, `ip_address`, `browser`, `note`) VALUES
(1, 'backup_2026-07-15_19-39.zip', 2754, 'admin', '2026-07-15 12:39:15', 'success', '127.0.0.1', 'Firefox', 'Backup berhasil dibuat secara manual.');

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
  `booking_code` varchar(40) DEFAULT NULL,
  `checkin_token` char(64) DEFAULT NULL,
  `checkin_generated_at` datetime DEFAULT NULL,
  `payment_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `checkin_status` enum('Not Checked In','Checked In') DEFAULT 'Not Checked In',
  `checkin_time` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `checkin_ip` varchar(45) DEFAULT NULL,
  `checkin_browser` varchar(255) DEFAULT NULL,
  `checkin_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `court_id`, `tanggal_booking`, `jam_mulai`, `jam_selesai`, `total_harga`, `paket`, `sewa_raket`, `catatan`, `status`, `booking_code`, `checkin_token`, `checkin_generated_at`, `payment_status`, `checkin_status`, `checkin_time`, `verified_at`, `verified_by`, `checkin_ip`, `checkin_browser`, `checkin_by`, `cancelled_at`, `created_at`) VALUES
(1, 2, 2, '2026-05-25', '16:00:00', '20:00:00', 600000.00, '', 0, '', 'confirmed', 'BK2B9335F8491697', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-05-13 04:41:00'),
(2, 3, 2, '2026-06-03', '20:00:00', '22:00:00', 350000.00, '', 1, '', 'confirmed', 'BK74950CB4B9CE2B', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-05-13 04:48:41'),
(3, 3, 1, '2026-05-20', '18:00:00', '20:00:00', 300000.00, '', 0, '', 'confirmed', 'BKD05B1C0A7A5899', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-05-13 07:03:52'),
(4, 3, 2, '2026-05-21', '20:00:00', '22:00:00', 350000.00, '', 1, '', 'confirmed', 'BK87E563C250964E', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-05-13 07:05:19'),
(5, 3, 1, '2026-05-20', '20:00:00', '22:00:00', 300000.00, '', 0, '', 'confirmed', 'BK06614591F0C5B8', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-05-13 07:25:24'),
(6, 4, 3, '2026-06-27', '20:30:00', '22:30:00', 200000.00, '', 0, '', 'cancelled', NULL, NULL, NULL, 'Pending', 'Not Checked In', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 10:15:17'),
(7, 4, 2, '2026-06-29', '18:00:00', '21:30:00', 525000.00, '', 0, '', 'cancelled', NULL, NULL, NULL, 'Pending', 'Not Checked In', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 10:30:26'),
(8, 4, 3, '2026-06-29', '18:00:00', '20:00:00', 200000.00, '', 0, '', 'cancelled', NULL, NULL, NULL, 'Pending', 'Not Checked In', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-27 10:32:36'),
(9, 4, 2, '2026-06-29', '15:00:00', '19:00:00', 600000.00, '', 0, '', 'confirmed', 'BK3593F42730EE17', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-06-27 10:44:03'),
(10, 5, 1, '2026-07-28', '16:00:00', '20:45:00', 762500.00, '', 1, '', 'confirmed', 'BK6B43A3725A2EAD', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-06-27 12:30:39'),
(11, 9, 2, '2026-08-04', '08:45:00', '10:30:00', 262500.00, '', 0, '', 'confirmed', 'BK51D0FA1E152C2F', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-07-08 07:01:42'),
(12, 12, 1, '2026-10-01', '12:00:00', '16:00:00', 600000.00, '', 0, '', 'confirmed', 'BKBD613D57B14EB5', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 18:40:59', NULL, NULL, NULL, NULL, NULL, '2026-07-15 14:35:43'),
(13, 13, 2, '2026-07-22', '19:00:00', '22:00:00', 450000.00, '', 0, '', 'confirmed', 'BK335390D06E1AA7', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 21:01:02', 10, NULL, NULL, NULL, NULL, '2026-07-17 18:38:55'),
(14, 12, 1, '2026-07-23', '08:00:00', '12:00:00', 600000.00, '', 0, '', 'confirmed', 'BK5EDF088482D36C', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-17 21:02:39', NULL, NULL, NULL, NULL, NULL, '2026-07-17 19:02:23'),
(15, 13, 3, '2026-07-19', '09:00:00', '13:00:00', 400000.00, '', 0, '', 'confirmed', 'BKC2BF37334B9223', NULL, NULL, 'Verified', 'Checked In', '2026-07-19 08:21:58', '2026-07-17 21:45:34', NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/12.0 Mobile/15A372 Safari/604.1', 11, NULL, '2026-07-17 19:45:19'),
(16, 13, 2, '2026-07-19', '14:00:00', '19:00:00', 750000.00, '', 0, '', 'confirmed', 'BK2FCCDF157D24C3', NULL, NULL, 'Verified', 'Checked In', '2026-07-19 08:22:04', '2026-07-17 22:30:36', NULL, '127.0.0.1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/12.0 Mobile/15A372 Safari/604.1', 11, NULL, '2026-07-17 20:30:01'),
(17, 12, 2, '2026-07-20', '19:00:00', '21:00:00', 300000.00, '', 0, '', 'confirmed', 'BK49AFCDA32F16FC', NULL, NULL, 'Verified', 'Not Checked In', NULL, '2026-07-19 16:42:13', NULL, NULL, NULL, NULL, NULL, '2026-07-19 14:41:50');

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
  `metode_bayar` enum('QRIS','Cash','Transfer') NOT NULL,
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
(4, 4, '2026-05-13 14:19:15', 350000.00, '', 'bukti_4_1778656755.jpg', 'menunggu', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-07-17 18:38:16'),
(5, 5, '2026-05-13 14:26:24', 300000.00, '', 'bukti_5_1778657184.jpg', 'terverifikasi', 'unpaid', NULL, NULL, NULL, 0, '2026-06-27 11:49:50', '2026-07-17 18:38:16'),
(6, 9, '2026-06-27 17:44:13', 600000.00, 'Cash', '', 'terverifikasi', 'paid', '2026-06-27 14:01:52', 6, 'REC-20260627-0009', 1, '2026-06-27 11:49:50', '2026-06-27 12:01:57'),
(7, 10, '2026-06-27 19:30:46', 762500.00, 'Cash', '', 'terverifikasi', 'paid', '2026-07-02 08:26:45', 6, 'REC-20260702-0010', 1, '2026-06-27 12:30:46', '2026-07-02 06:26:47'),
(8, 11, '2026-07-08 14:01:56', 262500.00, 'Cash', '', 'menunggu', 'unpaid', NULL, NULL, NULL, 0, '2026-07-08 07:01:56', '2026-07-08 07:01:56'),
(9, 12, '2026-07-15 21:35:55', 600000.00, 'Cash', '', 'menunggu', 'unpaid', NULL, NULL, NULL, 0, '2026-07-15 14:35:55', '2026-07-15 14:35:55'),
(10, 13, '2026-07-18 01:39:39', 450000.00, 'QRIS', 'bukti_13_1784313579.jpg', 'menunggu', 'unpaid', NULL, NULL, NULL, 0, '2026-07-17 18:39:39', '2026-07-17 18:39:39'),
(11, 14, '2026-07-18 02:02:39', 600000.00, 'QRIS', NULL, 'terverifikasi', 'paid', '2026-07-18 02:02:39', NULL, NULL, 1, '2026-07-17 19:02:39', '2026-07-17 19:37:50'),
(12, 15, '2026-07-17 21:45:34', 400000.00, 'QRIS', NULL, 'terverifikasi', 'paid', '2026-07-17 21:45:34', NULL, 'REC-20260717-0015', 0, '2026-07-17 19:45:34', '2026-07-17 19:45:34'),
(13, 16, '2026-07-17 22:30:36', 750000.00, 'QRIS', NULL, 'terverifikasi', 'paid', '2026-07-17 22:30:36', NULL, 'REC-20260717-0016', 0, '2026-07-17 20:30:36', '2026-07-17 20:30:36'),
(14, 17, '2026-07-19 16:42:13', 300000.00, 'QRIS', NULL, 'terverifikasi', 'paid', '2026-07-19 16:42:13', NULL, 'REC-20260719-0017', 0, '2026-07-19 14:42:13', '2026-07-19 14:42:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(150) DEFAULT NULL,
  `clerk_user_id` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','kasir','customer') NOT NULL DEFAULT 'customer',
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `login_provider` enum('local','google') DEFAULT 'local',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expired_at` datetime DEFAULT NULL,
  `api_token` varchar(64) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `full_name`, `email`, `username`, `clerk_user_id`, `password`, `nomor_telepon`, `phone`, `role`, `google_id`, `avatar`, `login_provider`, `email_verified`, `verification_token`, `verified_at`, `reset_token`, `reset_expired_at`, `api_token`, `last_login`, `updated_at`, `created_at`) VALUES
(1, 'Administrator', 'Administrator', 'admin@padelmania.com', NULL, NULL, 'adminmania', '081234567890', '081234567890', 'admin', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-05-13 04:33:05'),
(2, 'zaenal arief', 'zaenal arief', 'zaenlips4@gmail.com', NULL, NULL, '$2y$10$uigHox52Zori5fUOE5H7z.BLJHTmD3W5sDASU/0Hph5KnUnDduEO6', '0812971392', '0812971392', 'admin', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-05-13 04:40:08'),
(3, 'Arga Putra', 'Arga Putra', 'argagamteng@gmail.com', NULL, NULL, '$2y$10$2OVYZ5XOJ7vlDC/tVc5AxeSiV0ZoVfUOi93aoqTF2mL/4bHRoAnHK', '018472189', '018472189', 'customer', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-05-13 04:47:33'),
(4, 'Almer', 'Almer', 'Almer45@gmail.com', NULL, NULL, '$2y$10$bIECJZYxCvm7aoYXIXG1DeHn/M7aFrF/pqH3oXPnrAdaCDSZSjm.G', '082233009810', '082233009810', 'customer', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-06-27 10:13:26'),
(5, 'amba', 'amba', 'amba69@gmail.com', NULL, NULL, '123456', '0893292183', '0893292183', 'customer', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-06-27 11:11:55'),
(6, 'Kasir PadelClub', 'Kasir PadelClub', 'kasir@padelclub.com', NULL, NULL, 'kasir123', '081234567890', '081234567890', 'kasir', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-06-27 11:55:15'),
(7, 'Administrator', 'Administrator', 'admin@MyPadel.com', NULL, NULL, 'password', '081234567890', '081234567890', 'admin', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-06-27 12:00:29'),
(8, 'Kasir Utama', 'Kasir Utama', 'kasir@MyPadel.com', NULL, NULL, '$2y$10$RWXUM/kZLY2Yd15RYB1X4uqQ8iaiowRgS7O6Ei.eF2H2DUiVGs00e', '089876543210', '089876543210', 'kasir', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-06-27 12:00:29'),
(9, 'zupar', 'zupar', 'zupar55@gmail.com', NULL, NULL, '123456', '0832193009', '0832193009', 'customer', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:32:38', '2026-07-08 06:42:46'),
(10, 'kasir', 'kasir', 'padel@padelclub.com', NULL, NULL, '123456', '08269696969', '08269696969', 'kasir', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, '2026-07-19 21:43:37', '2026-07-19 14:43:37', '2026-07-08 07:56:53'),
(11, 'admin', 'admin', 'ambamin@padelclub.com', NULL, NULL, '123456', '0826789015', '0826789015', 'admin', NULL, NULL, 'local', 0, NULL, NULL, NULL, NULL, NULL, '2026-07-19 22:01:37', '2026-07-19 15:01:37', '2026-07-08 07:58:01'),
(12, 'Zainl Zee', 'Zainl Zee', 'zainlzee52@gmail.com', NULL, NULL, '097605a25392277b618954e3fb5bd0cf', NULL, NULL, 'customer', '106968381419847277419', 'https://lh3.googleusercontent.com/a/ACg8ocIZM1I6iAe356kGv00_yuBcBZrQP3WJ9WJ2vQ_GJn-DnJ6hHw=s96-c', 'google', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 09:36:03', '2026-07-15 07:30:23'),
(13, 'Zaenal Arief', 'Zaenal Arief', 'zainlzee72@gmail.com', 'zznael', NULL, '$2y$10$upVtI4YzGeunNm3/eG8FIucFeQQ.n7BATbxwh07ABV64PcrcDO7Fe', '082233009810', '082233009810', 'customer', NULL, NULL, 'local', 1, NULL, '2026-07-17 19:36:54', NULL, NULL, NULL, '2026-07-19 22:02:54', '2026-07-19 15:02:54', '2026-07-17 17:35:25'),
(14, 'Testing Customer', NULL, 'customer@MyPadel.com', 'testcustomer', NULL, '$2y$10$hEiYCR39i2Epm0Gr5vsHruDeeb4O7B3Gf2bcqqoJ7EpvVAWu/TNl.', '081234567890', NULL, 'customer', NULL, NULL, 'local', 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-18 13:49:33', '2026-07-20 05:00:00');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `backup_logs`
--
ALTER TABLE `backup_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `court_id` (`court_id`),
  ADD KEY `idx_checkin_token` (`checkin_token`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `clerk_user_id` (`clerk_user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `api_token` (`api_token`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `backup_logs`
--
ALTER TABLE `backup_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `courts`
--
ALTER TABLE `courts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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

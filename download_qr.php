<?php
// download_qr.php - Download QR Code as PNG

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/helpers/QRHelper.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die("Kode booking tidak valid.");
}

$model = new BookingModel($pdo);
$booking = $model->getBookingByCode($code);

if (!$booking) {
    die("Booking tidak ditemukan.");
}

// Security: Customer can only download their own QR Code
if ($_SESSION['role'] === 'customer' && $booking['user_id'] != $_SESSION['user_id']) {
    die("Akses ditolak. Anda tidak memiliki izin untuk mengunduh QR Code ini.");
}

// QR Code is only available for verified bookings
if ($booking['payment_status'] !== 'Verified') {
    die("QR Code belum tersedia. Pembayaran belum diverifikasi.");
}

$checkinUrl = QRHelper::generateCheckinUrl($code);
$pngBytes = QRHelper::generateQRCodeBytes($checkinUrl);

if (empty($pngBytes)) {
    die("Gagal membuat QR Code.");
}

// Set headers to force download
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="qr-padelclub-' . $code . '.png"');
header('Content-Length: ' . strlen($pngBytes));
echo $pngBytes;
exit;

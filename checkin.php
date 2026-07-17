<?php
// checkin.php - Entrypoint for QR Code Scan Check-in

session_start();

// Petugas (admin or kasir) must be logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'], true)) {
    // If not logged in, redirect to login page with return URL to this exact page and query string
    $redirect_url = 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . $redirect_url);
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/controllers/CheckinController.php';

$code = $_GET['code'] ?? '';

// Instantiating controller with the PDO instance from koneksi.php
$controller = new CheckinController($pdo);
$controller->handleCheckin($code);

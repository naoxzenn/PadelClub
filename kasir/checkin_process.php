<?php
// kasir/checkin_process.php — AJAX Handler for QR Check-in (Kasir)
// Returns JSON only. Called from kasir/checkin.php via fetch().

session_start();
header('Content-Type: application/json; charset=utf-8');

// Auth guard
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'kasir'], true)) {
    echo json_encode(['error' => 'Akses ditolak. Silakan login kembali.']);
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../models/BookingModel.php';

$model  = new BookingModel($pdo);
$action = $_REQUEST['action'] ?? '';
$token  = trim($_REQUEST['token'] ?? $_REQUEST['code'] ?? '');

if (empty($token)) {
    echo json_encode(['error' => 'QR / Token check-in tidak boleh kosong.']);
    exit;
}

// ============================================================
// ACTION: lookup — fetch booking info by checkin_token & validate
// ============================================================
if ($action === 'lookup') {
    $booking = $model->getBookingByCheckinToken($token);
    if (!$booking) {
        $booking = $model->getBookingByCode($token);
    }

    if (!$booking) {
        echo json_encode(['error' => 'QR tidak valid. Booking tidak ditemukan.']);
        exit;
    }

    $reason     = '';
    $canCheckin = false;
    $isCheckedIn = ($booking['checkin_status'] === 'Checked In');

    if ($booking['status'] === 'cancelled') {
        $reason = 'QR tidak valid. Booking telah dibatalkan.';
    } elseif ($booking['payment_status'] !== 'Verified') {
        $reason = 'QR belum aktif. Pembayaran belum diverifikasi.';
    } elseif ($isCheckedIn) {
        $reason = 'QR sudah digunakan.';
        $canCheckin = false;
    } else {
        $canCheckin = true;
    }

    $checkinDetails = null;
    if ($isCheckedIn && !empty($booking['checkin_time'])) {
        $checkinDetails = [
            'tanggal' => date('d F Y', strtotime($booking['checkin_time'])),
            'jam'     => date('H:i:s', strtotime($booking['checkin_time'])) . ' WIB',
            'kasir'   => $booking['checkin_by_name'] ?? 'Petugas Kasir'
        ];
    }

    echo json_encode([
        'success'         => true,
        'can_checkin'     => $canCheckin,
        'reason'          => $reason,
        'is_checked_in'   => $isCheckedIn,
        'checkin_details' => $checkinDetails,
        'booking'         => [
            'checkin_token'   => $booking['checkin_token'],
            'booking_code'    => $booking['booking_code'],
            'customer_name'   => $booking['nama_lengkap'],
            'court_name'      => $booking['nama_lapangan'] . ' (' . $booking['tipe_lapangan'] . ')',
            'tanggal_fmt'     => date('d F Y', strtotime($booking['tanggal_booking'])),
            'jam_mulai'       => substr($booking['jam_mulai'], 0, 5),
            'jam_selesai'     => substr($booking['jam_selesai'], 0, 5),
            'payment_status'  => $booking['payment_status'],
            'checkin_status'  => $booking['checkin_status'],
            'checkin_time_fmt'=> !empty($booking['checkin_time']) ? date('d/m/Y H:i:s', strtotime($booking['checkin_time'])) : '',
            'checkin_by_name' => $booking['checkin_by_name'] ?? ''
        ]
    ]);
    exit;
}

// ============================================================
// ACTION: checkin — perform the actual check-in by token
// ============================================================
if ($action === 'checkin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = $model->getBookingByCheckinToken($token);
    if (!$booking) {
        $booking = $model->getBookingByCode($token);
    }

    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'QR tidak valid. Booking tidak ditemukan.']);
        exit;
    }
    if ($booking['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'error' => 'Booking telah dibatalkan.']);
        exit;
    }
    if ($booking['payment_status'] !== 'Verified') {
        echo json_encode(['success' => false, 'error' => 'QR belum aktif. Pembayaran belum diverifikasi.']);
        exit;
    }
    if ($booking['checkin_status'] === 'Checked In') {
        $checkinTime = !empty($booking['checkin_time']) ? date('d F Y H:i:s', strtotime($booking['checkin_time'])) : '-';
        $kasirName = $booking['checkin_by_name'] ?? 'Petugas Kasir';
        echo json_encode([
            'success' => false,
            'error'   => "QR sudah digunakan pada $checkinTime WIB oleh $kasirName."
        ]);
        exit;
    }

    $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
    $browser    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $petugasId  = (int)$_SESSION['user_id'];

    $tokenKey = !empty($booking['checkin_token']) ? $booking['checkin_token'] : $token;
    $success = $model->checkinByToken($tokenKey, $ip, $browser, $petugasId);
    if (!$success && !empty($booking['booking_code'])) {
        $success = $model->checkin($booking['booking_code'], $ip, $browser, $petugasId);
    }

    if ($success) {
        // Refresh booking data for response
        $updated = $model->getBookingById($booking['id']);
        echo json_encode([
            'success' => true,
            'booking' => [
                'booking_code'  => $updated['booking_code'],
                'customer_name' => $updated['nama_lengkap'],
                'court_name'    => $updated['nama_lapangan'],
                'checkin_time'  => date('d F Y H:i:s', strtotime($updated['checkin_time'])),
                'kasir_name'    => $updated['checkin_by_name'] ?? 'Kasir'
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal menyimpan check-in. Coba lagi.']);
    }
    exit;
}

// Fallback
echo json_encode(['error' => 'Aksi tidak dikenal.']);


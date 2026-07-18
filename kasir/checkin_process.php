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
$code   = trim($_REQUEST['code'] ?? '');

if (empty($code)) {
    echo json_encode(['error' => 'Kode booking tidak boleh kosong.']);
    exit;
}

// ============================================================
// ACTION: lookup — fetch booking info and validate eligibility
// ============================================================
if ($action === 'lookup') {
    $booking = $model->getBookingByCode($code);

    if (!$booking) {
        echo json_encode(['error' => 'Booking tidak ditemukan. Pastikan kode sudah benar.']);
        exit;
    }

    $reason     = '';
    $canCheckin = false;

    if ($booking['status'] === 'cancelled') {
        $reason = 'Booking telah dibatalkan.';
    } elseif ($booking['payment_status'] !== 'Verified') {
        $reason = 'Pembayaran belum terverifikasi. QR tidak aktif.';
    } else {
        $today       = date('Y-m-d');
        $bookingDate = $booking['tanggal_booking'];

        if ($bookingDate > $today) {
            $reason = 'Booking belum berlaku. Dijadwalkan ' . date('d F Y', strtotime($bookingDate)) . '.';
        } elseif ($bookingDate < $today) {
            $reason = 'Booking sudah kadaluarsa (tanggal ' . date('d F Y', strtotime($bookingDate)) . ').';
        } elseif ($booking['checkin_status'] === 'Checked In') {
            // Already checked in — show info, don't block
            $canCheckin = false;
        } else {
            $canCheckin = true;
        }
    }

    $checkinTimeFmt = '';
    if (!empty($booking['checkin_time'])) {
        $checkinTimeFmt = date('d/m/Y H:i', strtotime($booking['checkin_time']));
    }

    echo json_encode([
        'success'     => true,
        'can_checkin' => $canCheckin,
        'reason'      => $reason,
        'booking'     => [
            'booking_code'    => $booking['booking_code'],
            'customer_name'   => $booking['nama_lengkap'],
            'court_name'      => $booking['nama_lapangan'] . ' (' . $booking['tipe_lapangan'] . ')',
            'tanggal_fmt'     => date('d F Y', strtotime($booking['tanggal_booking'])),
            'jam_mulai'       => substr($booking['jam_mulai'], 0, 5),
            'jam_selesai'     => substr($booking['jam_selesai'], 0, 5),
            'payment_status'  => $booking['payment_status'],
            'checkin_status'  => $booking['checkin_status'],
            'checkin_time_fmt'=> $checkinTimeFmt,
        ]
    ]);
    exit;
}

// ============================================================
// ACTION: checkin — perform the actual check-in
// ============================================================
if ($action === 'checkin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking = $model->getBookingByCode($code);

    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking tidak ditemukan.']);
        exit;
    }
    if ($booking['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'error' => 'Booking telah dibatalkan.']);
        exit;
    }
    if ($booking['payment_status'] !== 'Verified') {
        echo json_encode(['success' => false, 'error' => 'Pembayaran belum diverifikasi.']);
        exit;
    }
    if ($booking['checkin_status'] === 'Checked In') {
        echo json_encode(['success' => false, 'error' => 'Pelanggan sudah check-in sebelumnya.']);
        exit;
    }

    $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
    $browser    = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $petugasId  = (int)$_SESSION['user_id'];

    $success = $model->checkin($code, $ip, $browser, $petugasId);

    if ($success) {
        // Refresh booking data for response
        $updated = $model->getBookingByCode($code);
        echo json_encode([
            'success' => true,
            'booking' => [
                'booking_code'  => $updated['booking_code'],
                'customer_name' => $updated['nama_lengkap'],
                'court_name'    => $updated['nama_lapangan'],
                'checkin_time'  => date('H:i', strtotime($updated['checkin_time'])),
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal menyimpan check-in. Coba lagi.']);
    }
    exit;
}

// Fallback
echo json_encode(['error' => 'Aksi tidak dikenal.']);

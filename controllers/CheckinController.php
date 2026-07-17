<?php
// controllers/CheckinController.php

require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

class CheckinController {
    private $bookingModel;

    public function __construct($pdo) {
        $this->bookingModel = new BookingModel($pdo);
    }

    public function handleCheckin($code) {
        $error_msg = '';
        $success_msg = '';
        $booking = null;

        $code = trim($code);
        if (empty($code)) {
            $error_msg = 'Booking Code tidak boleh kosong.';
        } else {
            $booking = $this->bookingModel->getBookingByCode($code);
            if (!$booking) {
                $error_msg = 'Booking tidak ditemukan.';
            } else {
                // 1. Status Check: Booking cannot be cancelled
                if ($booking['status'] === 'cancelled') {
                    $error_msg = 'Booking Tidak Berlaku (Telah Dibatalkan).';
                }
                // 2. Verification Check: Payment must be verified
                elseif ($booking['payment_status'] !== 'Verified') {
                    $error_msg = 'QR Tidak Valid. Pembayaran belum diverifikasi.';
                }
                // 3. Time Validation: Booking must be for today
                else {
                    $today = date('Y-m-d');
                    $bookingDate = $booking['tanggal_booking'];
                    
                    if ($bookingDate > $today) {
                        $error_msg = 'Belum Berlaku. Booking ini dijadwalkan untuk tanggal ' . date('d F Y', strtotime($bookingDate)) . '.';
                    } elseif ($bookingDate < $today) {
                        $error_msg = 'Sudah Kadaluarsa. Booking ini dijadwalkan untuk tanggal ' . date('d F Y', strtotime($bookingDate)) . '.';
                    }
                    // 4. One-Time Check: Cannot check-in twice
                    elseif ($booking['checkin_status'] === 'Checked In') {
                        $error_msg = 'Sudah Pernah Digunakan. Check-in dilakukan sebelumnya pada ' . date('d/m/Y H:i:s', strtotime($booking['checkin_time'])) . '.';
                    }
                }
            }
        }

        // Handle POST form submission to perform check-in
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_checkin') {
            if (!$error_msg && $booking) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $petugas_id = $_SESSION['user_id'];
                
                $success = $this->bookingModel->checkin($code, $ip, $browser, $petugas_id);
                if ($success) {
                    $success_msg = 'Check-in berhasil dikonfirmasi!';
                    // Refresh booking data
                    $booking = $this->bookingModel->getBookingByCode($code);
                } else {
                    $error_msg = 'Gagal memproses check-in. Silakan coba lagi.';
                }
            }
        }

        // Render check-in view
        $pageTitle = 'Digital Check-in';
        $baseUrl = '';
        include __DIR__ . '/../views/admin/checkin.php';
    }
}

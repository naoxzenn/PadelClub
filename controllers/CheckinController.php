<?php
// controllers/CheckinController.php

require_once __DIR__ . '/../models/BookingModel.php';
require_once __DIR__ . '/../helpers/QRHelper.php';

class CheckinController {
    private $bookingModel;

    public function __construct($pdo) {
        $this->bookingModel = new BookingModel($pdo);
    }

    public function handleCheckin($tokenKey) {
        $error_msg = '';
        $success_msg = '';
        $booking = null;
        $already_checked_in = false;

        $tokenKey = trim($tokenKey);
        if (empty($tokenKey)) {
            $error_msg = 'QR tidak valid.';
        } else {
            // First lookup by checkin_token, fallback to booking_code
            $booking = $this->bookingModel->getBookingByCheckinToken($tokenKey);
            if (!$booking) {
                $booking = $this->bookingModel->getBookingByCode($tokenKey);
            }

            if (!$booking) {
                $error_msg = 'QR tidak valid.';
            } else {
                // 1. Status Check: Booking cannot be cancelled
                if ($booking['status'] === 'cancelled') {
                    $error_msg = 'QR tidak valid. Booking telah dibatalkan.';
                }
                // 2. Verification Check: Payment status must be Verified
                elseif ($booking['payment_status'] !== 'Verified') {
                    $error_msg = 'QR belum aktif.';
                }
                // 3. One-Time Check: Already checked in
                elseif ($booking['checkin_status'] === 'Checked In') {
                    $already_checked_in = true;
                    $error_msg = 'QR sudah digunakan.';
                }
            }
        }

        // Handle POST form submission to perform check-in
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_checkin') {
            if (!$error_msg && $booking && $booking['checkin_status'] !== 'Checked In') {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $petugas_id = $_SESSION['user_id'];
                
                $checkinToken = !empty($booking['checkin_token']) ? $booking['checkin_token'] : $tokenKey;
                $success = $this->bookingModel->checkinByToken($checkinToken, $ip, $browser, $petugas_id);
                if (!$success) {
                    $success = $this->bookingModel->checkin($booking['booking_code'], $ip, $browser, $petugas_id);
                }

                if ($success) {
                    $success_msg = 'Check-in berhasil dikonfirmasi!';
                    // Refresh booking data
                    $booking = $this->bookingModel->getBookingById($booking['id']);
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

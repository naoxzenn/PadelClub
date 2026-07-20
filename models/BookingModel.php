<?php
// models/BookingModel.php

class BookingModel {
    private $pdo;

    public function __construct($pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            global $pdo;
            $this->pdo = $pdo;
        }
    }

    /**
     * Fetch booking by code
     * @param string $booking_code
     * @return array|false
     */
    public function getBookingByCode($booking_code) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, c.nama_lapangan, c.tipe_lapangan, u.nama_lengkap, u.email, u.nomor_telepon,
                   p.nama_lengkap as checkin_by_name, v.nama_lengkap as verified_by_name
            FROM bookings b
            JOIN courts c ON b.court_id = c.id
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users p ON b.checkin_by = p.id
            LEFT JOIN users v ON b.verified_by = v.id
            WHERE b.booking_code = :booking_code
        ");
        $stmt->execute([':booking_code' => $booking_code]);
        return $stmt->fetch();
    }

    /**
     * Fetch booking by checkin_token
     * @param string $token
     * @return array|false
     */
    public function getBookingByCheckinToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, c.nama_lapangan, c.tipe_lapangan, u.nama_lengkap, u.email, u.nomor_telepon,
                   p.nama_lengkap as checkin_by_name, v.nama_lengkap as verified_by_name
            FROM bookings b
            JOIN courts c ON b.court_id = c.id
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users p ON b.checkin_by = p.id
            LEFT JOIN users v ON b.verified_by = v.id
            WHERE b.checkin_token = :token
        ");
        $stmt->execute([':token' => trim($token)]);
        return $stmt->fetch();
    }

    /**
     * Fetch booking by ID
     * @param int $booking_id
     * @return array|false
     */
    public function getBookingById($booking_id) {
        $stmt = $this->pdo->prepare("
            SELECT b.*, c.nama_lapangan, c.tipe_lapangan, u.nama_lengkap, u.email, u.nomor_telepon,
                   p.nama_lengkap as checkin_by_name, v.nama_lengkap as verified_by_name
            FROM bookings b
            JOIN courts c ON b.court_id = c.id
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users p ON b.checkin_by = p.id
            LEFT JOIN users v ON b.verified_by = v.id
            WHERE b.id = :id
        ");
        $stmt->execute([':id' => $booking_id]);
        return $stmt->fetch();
    }

    /**
     * Verify payment status and sync details
     * @param int $booking_id
     * @param string $status
     * @param int|null $verifier_id
     * @return bool
     */
    public function verifyPayment($booking_id, $status, $verifier_id = null) {
        $status = strtolower($status);
        if ($status === 'confirmed') {
            $booking = $this->getBookingById($booking_id);
            if ($booking) {
                $token = $booking['checkin_token'];
                if (empty($token)) {
                    $token = bin2hex(random_bytes(32));
                }
                $code = $booking['booking_code'];
                if (empty($code)) {
                    $code = 'BK' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 14));
                    while (true) {
                        $check = $this->pdo->prepare("SELECT id FROM bookings WHERE booking_code = ?");
                        $check->execute([$code]);
                        if ($check->rowCount() === 0) break;
                        $code = 'BK' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 14));
                    }
                }

                $stmt = $this->pdo->prepare("
                    UPDATE bookings SET
                        status = 'confirmed',
                        booking_code = :code,
                        checkin_token = IFNULL(checkin_token, :token),
                        checkin_generated_at = IFNULL(checkin_generated_at, NOW()),
                        payment_status = 'Verified',
                        verified_at = IFNULL(verified_at, NOW()),
                        verified_by = IFNULL(verified_by, :verifier_id)
                    WHERE id = :id
                ");
                return $stmt->execute([
                    ':code' => $code,
                    ':token' => $token,
                    ':verifier_id' => $verifier_id,
                    ':id' => $booking_id
                ]);
            }
        } elseif ($status === 'cancelled') {
            $stmt = $this->pdo->prepare("
                UPDATE bookings SET
                    status = 'cancelled',
                    payment_status = 'Rejected'
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $booking_id]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE bookings SET
                    status = 'pending',
                    payment_status = 'Pending'
                WHERE id = :id
            ");
            return $stmt->execute([':id' => $booking_id]);
        }
    }

    /**
     * Ensure checkin_token is generated for a verified booking
     * @param int $booking_id
     * @return string|false
     */
    public function ensureCheckinToken($booking_id) {
        $booking = $this->getBookingById($booking_id);
        if (!$booking) return false;
        if (!empty($booking['checkin_token'])) {
            return $booking['checkin_token'];
        }
        if ($booking['payment_status'] === 'Verified') {
            $token = bin2hex(random_bytes(32));
            $stmt = $this->pdo->prepare("
                UPDATE bookings SET checkin_token = :token, checkin_generated_at = NOW() WHERE id = :id AND checkin_token IS NULL
            ");
            $stmt->execute([':token' => $token, ':id' => $booking_id]);
            return $token;
        }
        return false;
    }

    /**
     * Process digital check-in by booking code
     * @param string $booking_code
     * @param string $ip
     * @param string $browser
     * @param int $petugas_id
     * @return bool
     */
    public function checkin($booking_code, $ip, $browser, $petugas_id) {
        $stmt = $this->pdo->prepare("
            UPDATE bookings SET
                checkin_status = 'Checked In',
                checkin_time = NOW(),
                checkin_ip = :ip,
                checkin_browser = :browser,
                checkin_by = :petugas_id
            WHERE booking_code = :booking_code AND payment_status = 'Verified' AND status = 'confirmed' AND checkin_status = 'Not Checked In'
        ");
        return $stmt->execute([
            ':ip' => $ip,
            ':browser' => $browser,
            ':petugas_id' => $petugas_id,
            ':booking_code' => $booking_code
        ]);
    }

    /**
     * Process digital check-in by checkin_token
     * @param string $token
     * @param string $ip
     * @param string $browser
     * @param int $petugas_id
     * @return bool
     */
    public function checkinByToken($token, $ip, $browser, $petugas_id) {
        $stmt = $this->pdo->prepare("
            UPDATE bookings SET
                checkin_status = 'Checked In',
                checkin_time = NOW(),
                checkin_ip = :ip,
                checkin_browser = :browser,
                checkin_by = :petugas_id
            WHERE checkin_token = :token AND payment_status = 'Verified' AND checkin_status = 'Not Checked In'
        ");
        return $stmt->execute([
            ':ip' => $ip,
            ':browser' => $browser,
            ':petugas_id' => $petugas_id,
            ':token' => trim($token)
        ]);
    }

    /**
     * Fetch list of bookings with date filter, check-in status, and search query
     * @param string $dateFilter ('today', 'tomorrow', 'all')
     * @param string $statusFilter ('all', 'checked_in', 'not_checked_in')
     * @param string $search
     * @return array
     */
    public function getCheckinList($dateFilter = 'today', $statusFilter = 'all', $search = '') {
        $sql = "
            SELECT b.*, u.nama_lengkap as customer_name, c.nama_lapangan, c.tipe_lapangan
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN courts c ON b.court_id = c.id
            WHERE b.status = 'confirmed'
        ";
        $params = [];

        // Date Filter
        if ($dateFilter === 'today') {
            $sql .= " AND b.tanggal_booking = :today";
            $params[':today'] = date('Y-m-d');
        } elseif ($dateFilter === 'tomorrow') {
            $sql .= " AND b.tanggal_booking = :tomorrow";
            $params[':tomorrow'] = date('Y-m-d', strtotime('+1 day'));
        }

        // Status Filter
        if ($statusFilter === 'checked_in') {
            $sql .= " AND b.checkin_status = 'Checked In'";
        } elseif ($statusFilter === 'not_checked_in') {
            $sql .= " AND b.checkin_status = 'Not Checked In'";
        }

        // Search Query
        if (!empty($search)) {
            $sql .= " AND (b.booking_code LIKE :search 
                        OR u.nama_lengkap LIKE :search 
                        OR c.nama_lapangan LIKE :search 
                        OR b.tanggal_booking LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY b.tanggal_booking ASC, b.jam_mulai ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get checkin stats for admin list view
     * @return array
     */
    public function getCheckinStats() {
        $today = date('Y-m-d');
        
        // Total bookings today
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tanggal_booking = ? AND status = 'confirmed'");
        $stmt->execute([$today]);
        $total = (int)$stmt->fetchColumn();

        // Checked in today
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tanggal_booking = ? AND status = 'confirmed' AND checkin_status = 'Checked In'");
        $stmt->execute([$today]);
        $checked = (int)$stmt->fetchColumn();

        // Not checked in today
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tanggal_booking = ? AND status = 'confirmed' AND checkin_status = 'Not Checked In'");
        $stmt->execute([$today]);
        $unchecked = (int)$stmt->fetchColumn();

        $rate = $total > 0 ? round(($checked / $total) * 100, 1) : 0;

        return [
            'total_today' => $total,
            'checked_today' => $checked,
            'unchecked_today' => $unchecked,
            'attendance_rate' => $rate
        ];
    }

    /**
     * Get or create checkin_token for a booking
     * @param array|int|string $booking
     * @return string Token
     */
    public function getOrCreateCheckinToken($booking) {
        return getOrCreateCheckinToken($booking, $this->pdo);
    }
}

/**
 * Global Helper: Get or create checkin_token for a booking
 * Used by booking-detail.php, invoice.php, download_qr.php, checkin.php, scanner kasir
 * @param array|int|string $booking Array booking, booking ID, or booking_code
 * @param PDO|null $pdoInstance
 * @return string Token
 */
if (!function_exists('getOrCreateCheckinToken')) {
    function getOrCreateCheckinToken($booking, $pdoInstance = null) {
        if (empty($pdoInstance)) {
            global $pdo;
            $pdoInstance = $pdo;
        }

        if (!$pdoInstance) {
            return '';
        }

        if (is_numeric($booking) || is_string($booking)) {
            if (is_numeric($booking)) {
                $stmt = $pdoInstance->prepare("SELECT id, checkin_token, payment_status FROM bookings WHERE id = ?");
                $stmt->execute([(int)$booking]);
            } else {
                $stmt = $pdoInstance->prepare("SELECT id, checkin_token, payment_status FROM bookings WHERE booking_code = ? OR checkin_token = ?");
                $stmt->execute([$booking, $booking]);
            }
            $booking = $stmt->fetch();
        }

        if (!$booking || empty($booking['id'])) {
            return '';
        }

        if (!empty($booking['checkin_token'])) {
            return $booking['checkin_token'];
        }

        // Generate secure 64-hex token using random_bytes(32)
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');

        $stmt = $pdoInstance->prepare("
            UPDATE bookings SET 
                checkin_token = :token,
                checkin_generated_at = IFNULL(checkin_generated_at, :now)
            WHERE id = :id AND (checkin_token IS NULL OR checkin_token = '')
        ");
        $stmt->execute([
            ':token' => $token,
            ':now'   => $now,
            ':id'    => $booking['id']
        ]);

        return $token;
    }
}

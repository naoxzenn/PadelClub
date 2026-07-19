<?php
// api/v1/bookings.php
// Resource endpoint untuk data booking lapangan padel.
//
// Semua request wajib menyertakan header: Authorization: Bearer <token>
//
// Endpoint tersedia:
//   GET  /api/v1/bookings.php              → list booking
//                                            customer: hanya booking milik sendiri
//                                            admin/kasir: semua booking
//                                            Query param opsional: ?status=pending|confirmed|cancelled
//   GET  /api/v1/bookings.php?id=123       → detail satu booking
//   POST /api/v1/bookings.php              → buat booking baru
//                                            Body JSON: { court_id, tanggal, jam_mulai, jam_selesai }

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../models/BookingModel.php';
require_once __DIR__ . '/../config/ApiResponse.php';
require_once __DIR__ . '/../config/ApiAuth.php';

// ---- Autentikasi: wajib Bearer token ----
$currentUser = ApiAuth::requireAuth($pdo);

$method = $_SERVER['REQUEST_METHOD'];

// ========================================================
// GET — ambil list booking atau detail satu booking
// ========================================================
if ($method === 'GET') {

    // --- Detail satu booking: GET ?id=123 ---
    if (isset($_GET['id'])) {
        $bookingId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$bookingId || $bookingId <= 0) {
            ApiResponse::error('Parameter id tidak valid. Harus berupa bilangan bulat positif.');
        }

        $bookingModel = new BookingModel($pdo);
        $booking = $bookingModel->getBookingById($bookingId);

        if (!$booking) {
            ApiResponse::notFound("Booking dengan id $bookingId tidak ditemukan.");
        }

        // Role check: customer hanya bisa lihat booking miliknya sendiri
        if ($currentUser['role'] === 'customer' && (int)$booking['user_id'] !== (int)$currentUser['id']) {
            ApiResponse::forbidden('Anda hanya dapat melihat detail booking milik Anda sendiri.');
        }

        // Bersihkan field sensitif sebelum dikirim ke client
        unset($booking['password'], $booking['api_token']);

        ApiResponse::success($booking, 'Berhasil mengambil detail booking.');
    }

    // --- List booking: GET (tanpa ?id) ---

    // Validasi query param status jika ada
    $statusFilter = null;
    if (isset($_GET['status'])) {
        $allowedStatuses = ['pending', 'confirmed', 'cancelled'];
        $statusInput = strtolower(trim($_GET['status']));
        if (!in_array($statusInput, $allowedStatuses, true)) {
            ApiResponse::error(
                'Filter status tidak valid. Nilai yang diterima: ' . implode(', ', $allowedStatuses)
            );
        }
        $statusFilter = $statusInput;
    }

    // Bangun query berdasarkan role
    $sql = "
        SELECT b.id, b.user_id, b.court_id, c.nama_lapangan, c.tipe_lapangan,
               b.tanggal_booking, b.jam_mulai, b.jam_selesai,
               b.total_harga, b.paket, b.sewa_raket,
               b.status, b.booking_code, b.payment_status,
               b.catatan, b.created_at,
               u.nama_lengkap AS nama_pemesan, u.email AS email_pemesan
        FROM bookings b
        JOIN courts c ON b.court_id = c.id
        JOIN users u ON b.user_id = u.id
        WHERE 1=1
    ";
    $params = [];

    // Customer hanya lihat booking miliknya sendiri
    if ($currentUser['role'] === 'customer') {
        $sql .= " AND b.user_id = :user_id";
        $params[':user_id'] = $currentUser['id'];
    }

    // Filter status opsional
    if ($statusFilter !== null) {
        $sql .= " AND b.status = :status";
        $params[':status'] = $statusFilter;
    }

    $sql .= " ORDER BY b.tanggal_booking DESC, b.jam_mulai DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();
    } catch (PDOException $e) {
        ApiResponse::serverError('Gagal mengambil data booking.');
    }

    ApiResponse::success(
        $bookings,
        'Berhasil mengambil daftar booking. Total: ' . count($bookings) . ' data.'
    );
}

// ========================================================
// POST — buat booking baru
// ========================================================
if ($method === 'POST') {

    // Baca dan parse JSON body
    $rawBody = file_get_contents('php://input');
    $body = json_decode($rawBody, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
        ApiResponse::error('Body request tidak valid. Kirim JSON dengan Content-Type: application/json.');
    }

    // ---- Validasi input wajib ----
    $courtId   = isset($body['court_id']) ? (int)$body['court_id'] : 0;
    $tanggal   = trim($body['tanggal'] ?? '');
    $jamMulai  = trim($body['jam_mulai'] ?? '');
    $jamSelesai = trim($body['jam_selesai'] ?? '');

    $errors = [];

    if ($courtId <= 0) {
        $errors[] = 'court_id wajib diisi dan harus bilangan bulat positif.';
    }
    if (empty($tanggal)) {
        $errors[] = 'tanggal wajib diisi (format: YYYY-MM-DD).';
    }
    if (empty($jamMulai)) {
        $errors[] = 'jam_mulai wajib diisi (format: HH:MM).';
    }
    if (empty($jamSelesai)) {
        $errors[] = 'jam_selesai wajib diisi (format: HH:MM).';
    }

    if (!empty($errors)) {
        ApiResponse::error(implode(' | ', $errors));
    }

    // ---- Validasi format tanggal ----
    $dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $tanggal) {
        ApiResponse::error('Format tanggal tidak valid. Gunakan format YYYY-MM-DD (contoh: 2026-08-15).');
    }

    // Booking tidak boleh di masa lalu
    $today = new DateTime('today');
    if ($dateObj < $today) {
        ApiResponse::error('Tanggal booking tidak boleh di masa lalu.');
    }

    // ---- Validasi format jam ----
    $jamMulaiObj = DateTime::createFromFormat('H:i', $jamMulai);
    $jamSelesaiObj = DateTime::createFromFormat('H:i', $jamSelesai);

    if (!$jamMulaiObj) {
        ApiResponse::error('Format jam_mulai tidak valid. Gunakan format HH:MM (contoh: 08:00).');
    }
    if (!$jamSelesaiObj) {
        ApiResponse::error('Format jam_selesai tidak valid. Gunakan format HH:MM (contoh: 10:00).');
    }

    // jam_selesai harus setelah jam_mulai
    if ($jamSelesaiObj <= $jamMulaiObj) {
        ApiResponse::error('jam_selesai harus lebih besar dari jam_mulai.');
    }

    // Hitung durasi dalam jam (untuk kalkulasi harga)
    $durasiMenit = ($jamSelesaiObj->getTimestamp() - $jamMulaiObj->getTimestamp()) / 60;
    $durasiJam = $durasiMenit / 60;

    if ($durasiJam < 1) {
        ApiResponse::error('Durasi booking minimal 1 jam.');
    }

    // ---- Validasi lapangan: cek apakah ada dan statusnya aktif ----
    try {
        $stmt = $pdo->prepare(
            "SELECT id, nama_lapangan, tipe_lapangan, harga_per_jam, status 
             FROM courts 
             WHERE id = :court_id 
             LIMIT 1"
        );
        $stmt->execute([':court_id' => $courtId]);
        $court = $stmt->fetch();
    } catch (PDOException $e) {
        ApiResponse::serverError('Gagal memvalidasi data lapangan.');
    }

    if (!$court) {
        ApiResponse::notFound("Lapangan dengan id $courtId tidak ditemukan.");
    }

    if ($court['status'] !== 'aktif') {
        ApiResponse::error("Lapangan '{$court['nama_lapangan']}' sedang tidak aktif dan tidak dapat dibooking.");
    }

    // ---- Cek ketersediaan lapangan (tidak boleh overlap dengan booking existing) ----
    // Overlap terjadi jika: jam_mulai_baru < jam_selesai_existing AND jam_selesai_baru > jam_mulai_existing
    try {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) 
             FROM bookings 
             WHERE court_id = :court_id 
               AND tanggal_booking = :tanggal 
               AND status IN ('pending', 'confirmed')
               AND jam_mulai < :jam_selesai 
               AND jam_selesai > :jam_mulai"
        );
        $stmt->execute([
            ':court_id'   => $courtId,
            ':tanggal'    => $tanggal,
            ':jam_selesai' => $jamSelesai . ':00',
            ':jam_mulai'  => $jamMulai . ':00',
        ]);
        $overlapCount = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        ApiResponse::serverError('Gagal memeriksa ketersediaan lapangan.');
    }

    if ($overlapCount > 0) {
        ApiResponse::error(
            "Lapangan '{$court['nama_lapangan']}' sudah dibooking pada waktu tersebut. Pilih jam atau tanggal lain.",
            409 // Conflict
        );
    }

    // ---- Hitung total harga ----
    $totalHarga = (float)$court['harga_per_jam'] * $durasiJam;

    // ---- Insert booking baru ----
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO bookings 
                (user_id, court_id, tanggal_booking, jam_mulai, jam_selesai, total_harga, status, created_at)
             VALUES 
                (:user_id, :court_id, :tanggal, :jam_mulai, :jam_selesai, :total_harga, 'pending', NOW())"
        );
        $stmt->execute([
            ':user_id'     => $currentUser['id'],
            ':court_id'    => $courtId,
            ':tanggal'     => $tanggal,
            ':jam_mulai'   => $jamMulai . ':00',
            ':jam_selesai' => $jamSelesai . ':00',
            ':total_harga' => $totalHarga,
        ]);
        $newBookingId = (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        ApiResponse::serverError('Gagal membuat booking. Coba lagi nanti.');
    }

    // Ambil data booking yang baru dibuat untuk dikembalikan ke client
    $bookingModel = new BookingModel($pdo);
    $newBooking = $bookingModel->getBookingById($newBookingId);

    // Bersihkan field sensitif
    unset($newBooking['password'], $newBooking['api_token']);

    ApiResponse::success(
        $newBooking,
        "Booking berhasil dibuat dengan status 'pending'. Silakan lakukan pembayaran untuk konfirmasi.",
        201 // Created
    );
}

// ========================================================
// Method tidak didukung
// ========================================================
ApiResponse::error('Metode ' . $method . ' tidak didukung untuk endpoint ini. Gunakan GET atau POST.', 405);

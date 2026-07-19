<?php
// api/v1/courts.php
// Resource endpoint untuk data lapangan padel.
//
// Semua request wajib menyertakan header: Authorization: Bearer <token>
//
// Endpoint tersedia:
//   GET /api/v1/courts.php                        → list semua lapangan beserta status aktif/nonaktif
//   GET /api/v1/courts.php?id=1&tanggal=2026-07-20 → cek slot yang sudah terpakai di tanggal tersebut
//                                                    Client dapat menghitung slot kosong dari data ini.

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../config/ApiResponse.php';
require_once __DIR__ . '/../config/ApiAuth.php';

// ---- Autentikasi: wajib Bearer token ----
$currentUser = ApiAuth::requireAuth($pdo);

$method = $_SERVER['REQUEST_METHOD'];

// Hanya izinkan GET
if ($method !== 'GET') {
    ApiResponse::error('Metode ' . $method . ' tidak didukung. Gunakan GET.', 405);
}

// ========================================================
// GET ?id=1&tanggal=2026-07-20 — cek ketersediaan slot lapangan
// ========================================================
if (isset($_GET['id']) && isset($_GET['tanggal'])) {

    $courtId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $tanggal = trim($_GET['tanggal']);

    // Validasi court_id
    if (!$courtId || $courtId <= 0) {
        ApiResponse::error('Parameter id tidak valid. Harus berupa bilangan bulat positif.');
    }

    // Validasi format tanggal
    $dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $tanggal) {
        ApiResponse::error('Format tanggal tidak valid. Gunakan format YYYY-MM-DD (contoh: 2026-07-20).');
    }

    // Ambil data lapangan
    try {
        $stmt = $pdo->prepare(
            "SELECT id, nama_lapangan, tipe_lapangan, harga_per_jam, deskripsi, status 
             FROM courts 
             WHERE id = :court_id 
             LIMIT 1"
        );
        $stmt->execute([':court_id' => $courtId]);
        $court = $stmt->fetch();
    } catch (PDOException $e) {
        ApiResponse::serverError('Gagal mengambil data lapangan.');
    }

    if (!$court) {
        ApiResponse::notFound("Lapangan dengan id $courtId tidak ditemukan.");
    }

    // Ambil semua booking di tanggal tersebut yang masih aktif (pending atau confirmed)
    // Ini menunjukkan slot waktu yang SUDAH TERPAKAI
    try {
        $stmt = $pdo->prepare(
            "SELECT b.id, b.jam_mulai, b.jam_selesai, b.status, b.paket
             FROM bookings b
             WHERE b.court_id = :court_id 
               AND b.tanggal_booking = :tanggal 
               AND b.status IN ('pending', 'confirmed')
             ORDER BY b.jam_mulai ASC"
        );
        $stmt->execute([
            ':court_id' => $courtId,
            ':tanggal'  => $tanggal,
        ]);
        $bookedSlots = $stmt->fetchAll();
    } catch (PDOException $e) {
        ApiResponse::serverError('Gagal mengambil data ketersediaan lapangan.');
    }

    // Hitung slot per jam yang tersedia (operasional 06:00–22:00)
    $operasionalMulai = 6;  // 06:00
    $operasionalSelesai = 22; // 22:00
    $allSlots = [];

    for ($jam = $operasionalMulai; $jam < $operasionalSelesai; $jam++) {
        $slotMulai   = sprintf('%02d:00', $jam);
        $slotSelesai = sprintf('%02d:00', $jam + 1);
        $isAvailable = true;

        // Cek apakah slot jam ini overlap dengan booking yang ada
        foreach ($bookedSlots as $booked) {
            // Slot terpakai jika: slot_mulai < jam_selesai_booking AND slot_selesai > jam_mulai_booking
            $bookedMulai   = substr($booked['jam_mulai'], 0, 5);   // HH:MM
            $bookedSelesai = substr($booked['jam_selesai'], 0, 5);  // HH:MM

            if ($slotMulai < $bookedSelesai && $slotSelesai > $bookedMulai) {
                $isAvailable = false;
                break;
            }
        }

        $allSlots[] = [
            'jam_mulai'  => $slotMulai,
            'jam_selesai' => $slotSelesai,
            'tersedia'   => $isAvailable,
        ];
    }

    ApiResponse::success(
        [
            'lapangan'     => [
                'id'           => (int)$court['id'],
                'nama_lapangan' => $court['nama_lapangan'],
                'tipe_lapangan' => $court['tipe_lapangan'],
                'harga_per_jam' => (float)$court['harga_per_jam'],
                'status'       => $court['status'],
            ],
            'tanggal'      => $tanggal,
            'slots'        => $allSlots,
            'booked_ranges' => array_map(function ($b) {
                return [
                    'jam_mulai'  => substr($b['jam_mulai'], 0, 5),
                    'jam_selesai' => substr($b['jam_selesai'], 0, 5),
                    'status'     => $b['status'],
                ];
            }, $bookedSlots),
        ],
        "Berhasil mengambil data ketersediaan lapangan '{$court['nama_lapangan']}' pada tanggal $tanggal."
    );
}

// ========================================================
// GET — list semua lapangan
// ========================================================
if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare(
            "SELECT id, nama_lapangan, tipe_lapangan, harga_per_jam, deskripsi, status, created_at
             FROM courts
             ORDER BY id ASC"
        );
        $stmt->execute();
        $courts = $stmt->fetchAll();
    } catch (PDOException $e) {
        ApiResponse::serverError('Gagal mengambil data lapangan.');
    }

    // Cast tipe data numerik
    $courts = array_map(function ($c) {
        $c['id'] = (int)$c['id'];
        $c['harga_per_jam'] = (float)$c['harga_per_jam'];
        return $c;
    }, $courts);

    ApiResponse::success(
        $courts,
        'Berhasil mengambil daftar lapangan. Total: ' . count($courts) . ' lapangan.'
    );
}

ApiResponse::error('Metode ' . $method . ' tidak didukung untuk endpoint ini.', 405);

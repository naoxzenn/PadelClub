<?php
// api/index.php
// Entry point API PadelClub.
// File ini menyambut request yang masuk ke /api/ dan memberikan informasi dasar API.
// Akses endpoint langsung ke path file masing-masing di /api/v1/

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tangani preflight request (OPTIONS) dari browser
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Selamat datang di REST API PadelClub',
    'data'    => [
        'versi'      => 'v1',
        'deskripsi'  => 'REST API untuk sistem booking lapangan padel',
        'endpoints'  => [
            'auth'     => '/api/v1/auth.php',
            'bookings' => '/api/v1/bookings.php',
            'courts'   => '/api/v1/courts.php',
        ],
        'dokumentasi' => '/api/README.md',
        'autentikasi' => 'Bearer Token — dapatkan token via POST /api/v1/auth.php',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

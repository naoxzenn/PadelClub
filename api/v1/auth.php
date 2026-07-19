<?php
// api/v1/auth.php
// Endpoint login untuk mendapatkan API token.
// Token ini TERPISAH dari session web — tidak mengganggu alur login web yang sudah ada.
//
// Endpoint:
//   POST /api/v1/auth.php
//   Body JSON: { "email": "...", "password": "..." }
//   Response: { "success": true, "data": { "token": "...", "user": {...} }, "message": "Login berhasil" }

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../config/ApiResponse.php';

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error('Metode tidak diizinkan. Gunakan POST.', 405);
}

// ---- Baca dan parse JSON body ----
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
    ApiResponse::error('Body request tidak valid. Kirim JSON dengan Content-Type: application/json.');
}

// ---- Validasi input ----
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (empty($email) || empty($password)) {
    ApiResponse::error('Email dan password wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ApiResponse::error('Format email tidak valid.');
}

// ---- Cari user berdasarkan email (PDO prepared statement) ----
try {
    $stmt = $pdo->prepare(
        "SELECT id, nama_lengkap, email, password, role 
         FROM users 
         WHERE email = :email 
         LIMIT 1"
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    // Jangan expose detail error DB ke client
    ApiResponse::serverError('Gagal memproses login. Coba lagi nanti.');
}

// ---- Verifikasi password menggunakan AuthHelper yang sudah ada ----
// AuthHelper::verifyPassword() menangani bcrypt hash + plain text fallback
if (!$user || !AuthHelper::verifyPassword($password, $user['password'])) {
    ApiResponse::error('Email atau password salah.', 401);
}

// ---- Generate API token baru ----
// bin2hex(random_bytes(32)) menghasilkan 64 karakter hex yang cryptographically secure
try {
    $token = bin2hex(random_bytes(32));
} catch (Exception $e) {
    ApiResponse::serverError('Gagal menghasilkan token. Coba lagi nanti.');
}

// ---- Simpan token ke DB ----
try {
    $stmt = $pdo->prepare(
        "UPDATE users SET api_token = :token WHERE id = :id"
    );
    $stmt->execute([
        ':token' => $token,
        ':id'    => $user['id'],
    ]);
} catch (PDOException $e) {
    ApiResponse::serverError('Gagal menyimpan token. Coba lagi nanti.');
}

// ---- Kirim response sukses ----
// TIDAK expose field sensitif: password, api_token tidak dimasukkan ke data user
ApiResponse::success(
    [
        'token' => $token,
        'user'  => [
            'id'          => (int)$user['id'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email'       => $user['email'],
            'role'        => $user['role'],
        ],
    ],
    'Login berhasil. Gunakan token di header Authorization: Bearer <token>',
    200
);

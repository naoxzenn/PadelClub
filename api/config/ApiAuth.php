<?php
// api/config/ApiAuth.php
// Middleware autentikasi untuk REST API menggunakan Bearer token.
// Token disimpan di kolom api_token tabel users, TERPISAH dari session web existing.

require_once __DIR__ . '/ApiResponse.php';

class ApiAuth {

    /**
     * Ambil dan validasi Bearer token dari header Authorization.
     * Query ke tabel users menggunakan PDO prepared statement.
     *
     * @param PDO $pdo Koneksi PDO global
     * @return array|null Data user (id, nama_lengkap, email, role) jika valid, null jika tidak
     */
    public static function authenticate(PDO $pdo): ?array {
        // 1. Ambil header Authorization
        $authHeader = '';

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            // Fallback untuk beberapa konfigurasi Apache
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? '';
        }

        // 2. Validasi format "Bearer <token>"
        if (empty($authHeader) || !preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($authHeader), $matches)) {
            return null;
        }

        $token = $matches[1];

        // 3. Cari user berdasarkan token di DB
        // Tidak expose field sensitif (password, api_token) di return value
        $stmt = $pdo->prepare(
            "SELECT id, nama_lengkap, email, role 
             FROM users 
             WHERE api_token = :token 
             LIMIT 1"
        );
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Wajib login — langsung kirim response 401 dan hentikan eksekusi jika token tidak valid.
     * Gunakan di awal setiap endpoint yang membutuhkan autentikasi.
     *
     * @param PDO $pdo Koneksi PDO global
     * @return array Data user yang sudah terautentikasi (id, nama_lengkap, email, role)
     */
    public static function requireAuth(PDO $pdo): array {
        $user = self::authenticate($pdo);

        if (!$user) {
            ApiResponse::unauthorized('Token tidak valid atau tidak ada. Silakan login terlebih dahulu via POST /api/v1/auth.php');
        }

        return $user;
    }

    /**
     * Cek apakah role user termasuk dalam daftar role yang diizinkan.
     * Langsung kirim response 403 dan hentikan eksekusi jika role tidak sesuai.
     *
     * @param array $user          Data user dari requireAuth()
     * @param array $allowedRoles  Contoh: ['admin', 'kasir'] atau ['admin']
     */
    public static function requireRole(array $user, array $allowedRoles): void {
        if (!in_array($user['role'], $allowedRoles, true)) {
            ApiResponse::forbidden(
                "Akses ditolak. Endpoint ini hanya untuk role: " . implode(', ', $allowedRoles)
            );
        }
    }
}

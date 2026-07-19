<?php
// api/config/ApiResponse.php
// Helper class untuk menghasilkan response JSON yang konsisten di seluruh endpoint API.
// Semua method langsung mengirim response dan menghentikan eksekusi (exit).

class ApiResponse {

    /**
     * Kirim response sukses
     * @param mixed  $data       Data yang dikembalikan ke client
     * @param string $message    Pesan sukses dalam Bahasa Indonesia
     * @param int    $statusCode HTTP status code (default: 200)
     */
    public static function success($data, string $message = 'Berhasil', int $statusCode = 200): void {
        self::send([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Kirim response error umum
     * @param string $message    Pesan error dalam Bahasa Indonesia
     * @param int    $statusCode HTTP status code (default: 400)
     * @param mixed  $data       Data tambahan (opsional, biasanya null)
     */
    public static function error(string $message, int $statusCode = 400, $data = null): void {
        self::send([
            'success' => false,
            'data'    => $data,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Kirim response 401 Unauthorized — token tidak ada atau tidak valid
     * @param string $message Pesan error
     */
    public static function unauthorized(string $message = 'Token tidak valid atau tidak ada'): void {
        self::send([
            'success' => false,
            'data'    => null,
            'message' => $message,
        ], 401);
    }

    /**
     * Kirim response 403 Forbidden — role tidak punya akses
     * @param string $message Pesan error
     */
    public static function forbidden(string $message = 'Akses ditolak, role Anda tidak diizinkan'): void {
        self::send([
            'success' => false,
            'data'    => null,
            'message' => $message,
        ], 403);
    }

    /**
     * Kirim response 404 Not Found
     * @param string $message Pesan error
     */
    public static function notFound(string $message = 'Data tidak ditemukan'): void {
        self::send([
            'success' => false,
            'data'    => null,
            'message' => $message,
        ], 404);
    }

    /**
     * Kirim response 500 Internal Server Error
     * @param string $message Pesan error
     */
    public static function serverError(string $message = 'Terjadi kesalahan pada server'): void {
        self::send([
            'success' => false,
            'data'    => null,
            'message' => $message,
        ], 500);
    }

    /**
     * Core method: set header Content-Type, HTTP status code, encode JSON, lalu exit.
     * Private — hanya dipanggil oleh method di atas.
     * @param array $body       Body response sebagai array PHP
     * @param int   $statusCode HTTP status code
     */
    private static function send(array $body, int $statusCode): void {
        // Header CORS — izinkan akses dari domain lain (berguna untuk mobile app / frontend terpisah)
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Tangani preflight request (OPTIONS) dari browser
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

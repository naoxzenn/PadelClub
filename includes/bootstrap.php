<?php
/**
 * Bootstrap — harus di-require di awal setiap halaman public.
 * Menangani: session, env (.env via koneksi.php), Clerk config, $baseUrl.
 */

// Session (hanya sekali)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi DB + load .env
require_once __DIR__ . '/../config/koneksi.php';

// Clerk config
$clerkConfig = require __DIR__ . '/../config/clerk.php';

// Base URL default (dipakai oleh header/footer untuk asset & link)
if (!isset($baseUrl)) {
    $baseUrl = '';
}

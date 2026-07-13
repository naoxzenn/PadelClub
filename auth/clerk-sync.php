<?php
/**
 * auth/clerk-sync.php
 * Bridge Clerk session → PHP session.
 *
 * Menerima POST dengan Authorization: Bearer <clerk_session_token>.
 * Verifikasi via Clerk Backend API, lalu find-or-create user di MySQL.
 * Set $_SESSION dan return JSON.
 */

header('Content-Type: application/json');

// Bootstrap (session + DB + env)
require_once __DIR__ . '/../includes/bootstrap.php';
/** @var mysqli $conn */
/** @var array $clerkConfig */

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Ambil token dari header Authorization
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (empty($authHeader) || stripos($authHeader, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid Authorization header']);
    exit;
}
$token = trim(substr($authHeader, 7));

// --- Decode JWT payload untuk mendapatkan sub (clerk user id) ---
$parts = explode('.', $token);
if (count($parts) !== 3) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token format']);
    exit;
}
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
if (!$payload || empty($payload['sub'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token payload']);
    exit;
}

$clerkUserId = $payload['sub'];

// --- Verifikasi user via Clerk Backend API ---
$secretKey = $clerkConfig['secret_key'] ?? '';
if (empty($secretKey)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Clerk secret key not configured']);
    exit;
}

$ch = curl_init("https://api.clerk.com/v1/users/{$clerkUserId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 || empty($response)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to verify user with Clerk API',
        'detail' => $curlError ?: "HTTP {$httpCode}"
    ]);
    exit;
}

$clerkUser = json_decode($response, true);
if (!$clerkUser || empty($clerkUser['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid Clerk user data']);
    exit;
}

// --- Ekstrak data user dari Clerk ---
$clerkEmail = '';
if (!empty($clerkUser['email_addresses'])) {
    // Ambil primary email
    $primaryId = $clerkUser['primary_email_address_id'] ?? '';
    foreach ($clerkUser['email_addresses'] as $ea) {
        if ($ea['id'] === $primaryId) {
            $clerkEmail = $ea['email_address'];
            break;
        }
    }
    // Fallback ke email pertama
    if (empty($clerkEmail)) {
        $clerkEmail = $clerkUser['email_addresses'][0]['email_address'];
    }
}

$clerkName = trim(($clerkUser['first_name'] ?? '') . ' ' . ($clerkUser['last_name'] ?? ''));
if (empty($clerkName)) {
    $clerkName = $clerkEmail; // fallback
}

if (empty($clerkEmail)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No email found in Clerk user data']);
    exit;
}

// --- Find or create user di MySQL ---
$user = null;

// 1. Cari by clerk_user_id
$stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, role FROM users WHERE clerk_user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $clerkUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// 2. Jika belum ada, cari by email
if (!$user) {
    $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, role, clerk_user_id FROM users WHERE email = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $clerkEmail);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    if ($user) {
        // Link clerk_user_id ke user existing
        if (empty($user['clerk_user_id'])) {
            $stmtU = mysqli_prepare($conn, "UPDATE users SET clerk_user_id = ? WHERE id = ?");
            if ($stmtU) {
                mysqli_stmt_bind_param($stmtU, 'si', $clerkUserId, $user['id']);
                mysqli_stmt_execute($stmtU);
                mysqli_stmt_close($stmtU);
            }
        }
    }
}

// 3. Jika tetap belum ada, buat user baru (role: customer)
if (!$user) {
    $defaultPass = ''; // Clerk user tidak butuh password lokal
    $defaultPhone = '';
    $role = 'customer';

    $stmtI = mysqli_prepare($conn,
        "INSERT INTO users (nama_lengkap, email, clerk_user_id, password, nomor_telepon, role) VALUES (?, ?, ?, ?, ?, ?)"
    );
    if ($stmtI) {
        mysqli_stmt_bind_param($stmtI, 'ssssss', $clerkName, $clerkEmail, $clerkUserId, $defaultPass, $defaultPhone, $role);
        if (mysqli_stmt_execute($stmtI)) {
            $newId = mysqli_insert_id($conn);
            $user = [
                'id' => $newId,
                'nama_lengkap' => $clerkName,
                'role' => $role,
            ];
        }
        mysqli_stmt_close($stmtI);
    }
}

if (!$user) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create or find user']);
    exit;
}

// --- Set PHP session ---
$_SESSION['user_id'] = $user['id'];
$_SESSION['nama']    = $user['nama_lengkap'];
$_SESSION['role']    = $user['role'];

// --- Redirect berdasarkan role ---
$redirect = '/PadelClub/dashboarduser.php';
if ($user['role'] === 'admin') {
    $redirect = '/PadelClub/admin/dashboard.php';
} elseif ($user['role'] === 'kasir') {
    $redirect = '/PadelClub/kasir/dashboard.php';
}

echo json_encode([
    'success'  => true,
    'redirect' => $redirect,
    'user'     => [
        'id'   => $user['id'],
        'nama' => $user['nama_lengkap'],
        'role' => $user['role'],
    ]
]);

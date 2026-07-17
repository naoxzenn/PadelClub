<?php
// auth/google-callback.php
session_start();
require_once __DIR__ . '/../config/oauth.php';
/** @var mysqli $conn */

// 1. CSRF State Verification
$state = $_GET['state'] ?? '';
if (empty($state) || $state !== ($_SESSION['oauth_state'] ?? '')) {
    unset($_SESSION['oauth_state']);
    die('Galat keamanan: Token state CSRF tidak valid.');
}
unset($_SESSION['oauth_state']);

$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: ../login.php?error=no_auth_code');
    exit;
}

try {
    $client = getGoogleClient();
    $token = $client->fetchAccessTokenWithAuthCode($code);
    
    if (isset($token['error'])) {
        throw new Exception($token['error_description'] ?? $token['error']);
    }
    
    $client->setAccessToken($token);
    
    // Ambil data profil dari Google OAuth2 Service
    $googleService = new Google\Service\Oauth2($client);
    $googleUser = $googleService->userinfo->get();
    
    $googleId = $googleUser->id;
    $email = $googleUser->email;
    $namaLengkap = $googleUser->name;
    $avatar = $googleUser->picture;
    
    if (empty($email)) {
        throw new Exception("Tidak dapat mengambil alamat email dari akun Google.");
    }
    
    // Cek apakah email sudah ada di database
    $stmt = mysqli_prepare($conn, "SELECT id, nama_lengkap, role, password, google_id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($user) {
        // Email sudah terdaftar. Hubungkan dengan akun Google jika belum.
        $userId = $user['id'];
        $stmtUpdate = mysqli_prepare($conn, "UPDATE users SET google_id = ?, avatar = ?, login_provider = 'google', email_verified = 1 WHERE id = ?");
        if (!$stmtUpdate) {
            throw new Exception("Database error: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmtUpdate, 'ssi', $googleId, $avatar, $userId);
        mysqli_stmt_execute($stmtUpdate);
        mysqli_stmt_close($stmtUpdate);
        
        $role = $user['role'];
        $nama = $user['nama_lengkap'];
    } else {
        // Akun belum ada, buat akun customer baru.
        // Hasilkan password acak yang aman (karena mereka login via Google)
        try {
            $randomPassword = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $randomPassword = md5(uniqid(rand(), true));
        }
        
        $role = 'customer';
        $stmtInsert = mysqli_prepare($conn, 
            "INSERT INTO users (nama_lengkap, email, password, nomor_telepon, role, google_id, avatar, login_provider, email_verified) 
             VALUES (?, ?, ?, NULL, ?, ?, ?, 'google', 1)"
        );
        if (!$stmtInsert) {
            throw new Exception("Database error: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmtInsert, 'ssssss', $namaLengkap, $email, $randomPassword, $role, $googleId, $avatar);
        mysqli_stmt_execute($stmtInsert);
        $userId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtInsert);
        
        $nama = $namaLengkap;
    }
    
    // Login user dengan membuat session (Session Fixation Protection)
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['nama'] = $nama;
    $_SESSION['role'] = $role;
    
    // Redirect sesuai role
    if ($role === 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($role === 'kasir') {
        header('Location: ../kasir/dashboard.php');
    } else {
        header('Location: ../dashboarduser.php');
    }
    exit;
    
} catch (Exception $e) {
    // Log error dan redirect ke halaman login dengan detil pesan kesalahan
    error_log("Google OAuth Error: " . $e->getMessage());
    header('Location: ../login.php?error=oauth_failed&details=' . urlencode($e->getMessage()));
    exit;
}

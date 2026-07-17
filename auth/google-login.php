<?php
// auth/google-login.php
session_start();
require_once __DIR__ . '/../config/oauth.php';

$client = getGoogleClient();

// CSRF State Protection
try {
    $state = bin2hex(random_bytes(16));
} catch (Exception $e) {
    $state = md5(uniqid(rand(), true));
}

$_SESSION['oauth_state'] = $state;
$client->setState($state);

$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;

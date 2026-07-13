<?php
/**
 * auth/sso-callback.php
 * Halaman callback untuk OAuth redirect dari Clerk (Google, dll).
 * Clerk JS menyelesaikan OAuth handshake, lalu auto-sync di clerk-js.php
 * meng-handle sinkronisasi ke PHP session dan redirect ke dashboard.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Memproses Login...';
$baseUrl = '';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="auth-wrapper">
    <div class="auth-box" style="text-align:center; padding:48px 32px;">
        <noscript>
            <div class="alert alert-danger">JavaScript harus diaktifkan untuk login via Google.</div>
        </noscript>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

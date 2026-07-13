<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = "Clerk Test";
$baseUrl = "";

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Clerk Auth Test</h2>
        <?php if (function_exists('isClerkConfigured') && isClerkConfigured()): ?>
            <p class="sub">Publishable Key: <code><?= substr($clerkConfig['publishable_key'], 0, 15) ?>...</code></p>

            <div id="clerk-status" style="margin:16px 0;"></div>

            <button type="button" id="btn-google-test" class="btn btn-secondary btn-block" style="display:flex; align-items:center; justify-content:center; gap:10px; padding:12px; font-size:14px; border:1px solid #ddd; background:#fff; cursor:pointer; border-radius:var(--radius-sm); transition:all .2s;" onmouseover="this.style.background='#f8fafc';this.style.borderColor='#0EA5E9'" onmouseout="this.style.background='#fff';this.style.borderColor='#ddd'">
                <svg width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                Test Masuk dengan Google
            </button>

            <script>
                (async function() {
                    const statusEl = document.getElementById('clerk-status');
                    try {
                        const clerk = await initClerk();
                        if (clerk.user) {
                            statusEl.innerHTML = '<div class="alert alert-success">✅ Clerk aktif — login sebagai: <strong>' +
                                clerk.user.primaryEmailAddress.emailAddress + '</strong></div>';
                            document.getElementById('btn-google-test').style.display = 'none';
                        } else {
                            statusEl.innerHTML = '<div class="alert alert-warning">Clerk loaded tapi belum sign-in.</div>';
                        }
                    } catch (err) {
                        statusEl.innerHTML = '<div class="alert alert-danger">❌ Clerk error: ' + err.message + '</div>';
                    }
                })();

                document.getElementById('btn-google-test')?.addEventListener('click', async function() {
                    const btn = this;
                    btn.disabled = true;
                    btn.innerHTML = '<span style="display:inline-block;width:18px;height:18px;border:2px solid #E2E8F0;border-top-color:#0EA5E9;border-radius:50%;animation:clkSpin .6s linear infinite;"></span> Menghubungkan...';
                    try {
                        const clerk = await initClerk();
                        const signIn = await clerk.client.signIn.create({
                            strategy: 'oauth_google',
                            redirectUrl: window.location.origin + '/PadelClub/auth/sso-callback.php',
                            actionCompleteRedirectUrl: window.location.origin + '/PadelClub/clerk-test.php'
                        });
                        const extUrl = signIn.firstFactorVerification.externalVerificationRedirectURL;
                        if (extUrl) {
                            window.location.href = extUrl;
                        } else {
                            throw new Error('No redirect URL');
                        }
                    } catch (err) {
                        console.error('OAuth error:', err);
                        btn.disabled = false;
                        btn.textContent = 'Gagal. Coba lagi';
                        if (typeof showToast === 'function') {
                            showToast('Error: ' + (err.message || 'Unknown'), 'error');
                        }
                    }
                });
            </script>
        <?php else: ?>
            <div class="alert alert-danger">
                Clerk belum dikonfigurasi. Pastikan <code>CLERK_PUBLISHABLE_KEY</code> sudah diset di file <code>.env</code>.
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="alert alert-success" style="margin-top: 16px;">
                PHP Session aktif: user_id=<?= $_SESSION['user_id'] ?>, nama=<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>, role=<?= htmlspecialchars($_SESSION['role'] ?? '') ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 16px;">
                PHP Session belum aktif (belum ada user_id).
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
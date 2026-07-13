<?php
/**
 * Clerk JS loader — include di <head> via header.php.
 * Hanya render jika Clerk sudah dikonfigurasi.
 * Menyediakan:
 *   - Clerk JS pinned version (v5)
 *   - initClerk() async helper
 *   - Auto-sync Clerk session → PHP session
 *   - Loading overlay saat proses sync
 */
if (!isset($clerkConfig)) {
    return;
}
if (!function_exists('isClerkConfigured') || !isClerkConfigured()) {
    return;
}
$clerkPk = htmlspecialchars($clerkConfig['publishable_key']);
$hasPhpSession = isset($_SESSION['user_id']) ? 'true' : 'false';
$isSsoCallback = (basename($_SERVER['PHP_SELF']) === 'sso-callback.php');
?>
<script
    async
    crossorigin="anonymous"
    data-clerk-publishable-key="<?= $clerkPk ?>"
    src="https://unpkg.com/@clerk/clerk-js@5/dist/clerk.browser.js">
</script>
<script>
/**
 * Global Clerk initializer — await ini sebelum operasi Clerk apapun.
 * @returns {Promise<Clerk>}
 */
async function initClerk() {
    // Tunggu sampai Clerk global tersedia (script async)
    while (typeof Clerk === 'undefined' || typeof Clerk.load !== 'function') {
        await new Promise(r => setTimeout(r, 50));
    }
    await Clerk.load();
    return Clerk;
}

/** Tampilkan loading overlay branded */
function showClerkLoader(msg) {
    if (document.getElementById('clerk-loader-overlay')) return;
    const overlay = document.createElement('div');
    overlay.id = 'clerk-loader-overlay';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:40px 48px;box-shadow:0 16px 40px -10px rgba(15,23,42,.15);text-align:center;max-width:360px;width:90%;">
            <div style="width:44px;height:44px;border:3px solid #E2E8F0;border-top-color:#0EA5E9;border-radius:50%;animation:clkSpin .7s linear infinite;margin:0 auto 18px;"></div>
            <p style="margin:0;font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:600;color:#0F172A;">${msg || 'Memproses...'}</p>
            <p style="margin:6px 0 0;font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:#64748B;">Mohon tunggu sebentar</p>
        </div>`;
    Object.assign(overlay.style, {
        position:'fixed',top:'0',left:'0',width:'100%',height:'100%',
        background:'rgba(248,250,252,.85)',backdropFilter:'blur(4px)',
        display:'flex',alignItems:'center',justifyContent:'center',
        zIndex:'99999'
    });
    document.body.appendChild(overlay);
}

/** Hapus loading overlay */
function hideClerkLoader() {
    const el = document.getElementById('clerk-loader-overlay');
    if (el) {
        el.style.opacity = '0';
        el.style.transition = 'opacity .3s';
        setTimeout(() => el.remove(), 300);
    }
}

/**
 * Auto-sync: jika Clerk punya user aktif tapi PHP session belum ada,
 * kirim token ke backend untuk sinkronisasi.
 */
(async function clerkAutoSync() {
    const hasPhpSession = <?= $hasPhpSession ?>;
    const isSsoCallback = <?= $isSsoCallback ? 'true' : 'false' ?>;

    try {
        const clerk = await initClerk();

        // Jika ini halaman SSO callback, proses redirect callback dulu
        if (isSsoCallback) {
            showClerkLoader('Memverifikasi akun...');
            try {
                await clerk.handleRedirectCallback({
                    afterSignInUrl: '/PadelClub/dashboarduser.php',
                    afterSignUpUrl: '/PadelClub/dashboarduser.php',
                    redirectUrl: '/PadelClub/dashboarduser.php'
                });
            } catch (cbErr) {
                console.warn('handleRedirectCallback:', cbErr);
            }
            // Tunggu sebentar agar Clerk punya user setelah callback
            await new Promise(r => setTimeout(r, 500));
        }

        if (clerk.user && !hasPhpSession) {
            // User signed in di Clerk tapi belum punya PHP session
            showClerkLoader('Menyinkronkan sesi...');
            const token = await clerk.session.getToken();
            if (!token) { hideClerkLoader(); return; }

            const res = await fetch('/PadelClub/auth/clerk-sync.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({})
            });

            const data = await res.json();
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
                return; // jangan hideClerkLoader, sedang redirect
            }
            hideClerkLoader();
        } else if (!clerk.user && hasPhpSession) {
            // Clerk signed out tapi PHP session masih ada — bersihkan
            window.location.href = '/PadelClub/logout.php';
        }
    } catch (err) {
        console.error('Clerk auto-sync error:', err);
        hideClerkLoader();
    }
})();
</script>
<style>@keyframes clkSpin{to{transform:rotate(360deg)}}</style>

<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'customer') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'kasir') {
        header('Location: kasir/dashboard.php');
    }
    exit;
}
$pageTitle = 'Tentang Kami';
$baseUrl = '';
?>
<?php include 'includes/header.php'; ?>

<!-- Bootstrap 5 CSS & Bootstrap Icons CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/* --- DESIGN SYSTEM & BRANDING --- */
:root {
    --padel-green: #2E7D32;
    --padel-green-hover: #1b5e20;
    --padel-green-light: rgba(46, 125, 50, 0.08);
    --padel-navy: #0F172A;
    --padel-gray: #F8FAFC;
    --padel-border: #E2E8F0;
    --padel-text-muted: #64748B;
}

/* --- OVERRIDES UNTUK MENCEGAH KONFLIK BOOTSTRAP --- */
/* Reset list default untuk menu navigasi dan footer agar tidak menjorok akibat reset Bootstrap */
ul {
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Fix header navbar clash dengan Bootstrap */
#main-nav {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 1000 !important;
    height: var(--nav-height) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 0 24px !important;
    background: rgba(255, 255, 255, .72) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    border-bottom: 1px solid rgba(226, 232, 240, .8) !important;
    box-sizing: border-box !important;
}
@media (min-width: 1024px) {
    #main-nav {
        padding: 0 64px !important;
    }
}
#main-nav.nav {
    flex-wrap: nowrap !important;
}
#main-nav .logo {
    font-size: 1.4rem !important;
    font-weight: 800 !important;
    text-decoration: none !important;
}
#main-nav ul.nav-links {
    display: none !important;
    margin-bottom: 0 !important;
    padding-left: 0 !important;
    list-style: none !important;
}
@media (min-width: 900px) {
    #main-nav ul.nav-links {
        display: flex !important;
    }
}
#main-nav ul.nav-links li {
    margin-bottom: 0 !important;
}
#main-nav ul.nav-links a {
    font-weight: 600 !important;
    font-size: .95rem !important;
    color: var(--text-muted) !important;
    padding-bottom: 4px !important;
    border-bottom: 2px solid transparent !important;
    text-decoration: none !important;
    transition: color .2s, border-color .2s !important;
}
#main-nav ul.nav-links a:hover,
#main-nav ul.nav-links a.active {
    color: var(--blue) !important;
    border-bottom-color: var(--blue) !important;
}

/* Fix mobile nav links margin & padding */
#mobile-nav a {
    text-decoration: none !important;
}

/* Fix footer styling agar tetap sesuai tema asal */
.site-footer {
    border-top: 1px solid var(--padel-border) !important;
    background: #fff !important;
    color: var(--padel-navy) !important;
}
.site-footer a {
    text-decoration: none !important;
}
.site-footer ul li {
    margin-bottom: 8px !important;
}

/* --- TEMA & STYLE HALAMAN TENTANG (ABOUT) --- */
.about-wrapper {
    margin-top: var(--nav-height);
    background-color: var(--padel-gray);
    overflow-x: hidden;
}

/* 1. Hero Tentang */
.hero-tentang {
    background: radial-gradient(ellipse at 0% 0%, rgba(46, 125, 50, 0.25) 0%, transparent 60%),
                radial-gradient(ellipse at 100% 100%, rgba(14, 165, 233, 0.15) 0%, transparent 60%),
                var(--padel-navy);
    color: #ffffff;
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}
.hero-tentang::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0.03;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-tagline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--padel-green);
    background-color: rgba(46, 125, 50, 0.15);
    padding: 6px 14px;
    border-radius: 99px;
    margin-bottom: 24px;
}
.hero-title {
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
}
.hero-subtitle {
    font-size: 1.15rem;
    color: rgba(255, 255, 255, 0.8);
    max-width: 600px;
    margin-top: 16px;
    line-height: 1.7;
}

/* 2. Custom Card Modern */
.padel-card {
    background: #ffffff;
    border: 1px solid rgba(46, 125, 50, 0.08);
    border-radius: 20px;
    padding: 2.2rem;
    box-shadow: 0 10px 30px -15px rgba(15, 23, 42, 0.05);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
}
.padel-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px -15px rgba(46, 125, 50, 0.15);
    border-color: rgba(46, 125, 50, 0.2);
}

/* 3. Icon Badge Kustom */
.icon-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background-color: var(--padel-green-light);
    color: var(--padel-green);
    font-size: 1.7rem;
    margin-bottom: 24px;
    transition: transform 0.3s ease;
}
.padel-card:hover .icon-badge {
    transform: scale(1.1) rotate(5deg);
    background-color: var(--padel-green);
    color: #ffffff;
}

/* 4. Tentang Kami List */
.tentang-list-item {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
}
.tentang-list-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background-color: var(--padel-green-light);
    color: var(--padel-green);
    font-size: 1.2rem;
    font-weight: bold;
}
.tentang-list-text h4 {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--padel-navy);
    margin-bottom: 4px;
}
.tentang-list-text p {
    font-size: 0.92rem;
    color: var(--padel-text-muted);
    margin-bottom: 0;
    line-height: 1.5;
}

/* 5. Custom Button Padel */
.btn-padel-primary {
    background-color: var(--padel-green) !important;
    border: none !important;
    color: #ffffff !important;
    font-weight: 600 !important;
    padding: 12px 28px !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 14px rgba(46, 125, 50, 0.25) !important;
    transition: all 0.25s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
}
.btn-padel-primary:hover {
    background-color: var(--padel-green-hover) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(46, 125, 50, 0.35) !important;
}
.btn-padel-outline {
    background-color: transparent !important;
    border: 2px solid var(--padel-green) !important;
    color: var(--padel-green) !important;
    font-weight: 600 !important;
    padding: 10px 28px !important;
    border-radius: 12px !important;
    transition: all 0.25s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
}
.btn-padel-outline:hover {
    background-color: var(--padel-green) !important;
    color: #ffffff !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 14px rgba(46, 125, 50, 0.15) !important;
}

/* 6. Syarat & Ketentuan Numbered List */
.terms-item {
    display: flex;
    gap: 16px;
    padding: 14px 0;
    border-bottom: 1px solid var(--padel-border);
}
.terms-item:last-child {
    border-bottom: none;
}
.terms-number {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: var(--padel-green-light);
    color: var(--padel-green);
    font-weight: 700;
    font-size: 0.9rem;
}
.terms-text {
    font-size: 1rem;
    color: var(--padel-navy);
    align-self: center;
    line-height: 1.5;
}

/* 7. FAQ Accordion Custom Styling */
.faq-accordion .accordion-item {
    border: 1px solid rgba(46, 125, 50, 0.08) !important;
    border-radius: 16px !important;
    margin-bottom: 14px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02) !important;
}
.faq-accordion .accordion-item:first-of-type,
.faq-accordion .accordion-item:last-of-type {
    border-radius: 16px !important;
}
.faq-accordion .accordion-button {
    font-weight: 600 !important;
    font-size: 1.05rem !important;
    color: var(--padel-navy) !important;
    background-color: #ffffff !important;
    padding: 20px 24px !important;
    box-shadow: none !important;
    border: none !important;
    text-align: left !important;
}
.faq-accordion .accordion-button:focus {
    box-shadow: none !important;
}
.faq-accordion .accordion-button:not(.collapsed) {
    color: #ffffff !important;
    background-color: var(--padel-green) !important;
}
.faq-accordion .accordion-button::after {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230F172A'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E") !important;
    transition: transform 0.2s ease-in-out !important;
}
.faq-accordion .accordion-button:not(.collapsed)::after {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E") !important;
    transform: rotate(-180deg) !important;
}
.faq-accordion .accordion-body {
    background-color: #FAFAFA !important;
    color: var(--padel-text-muted) !important;
    font-size: 0.95rem !important;
    line-height: 1.65 !important;
    padding: 20px 24px !important;
    border-top: 1px solid rgba(46, 125, 50, 0.08) !important;
}

/* 8. Call To Action Banner */
.cta-banner-new {
    background: radial-gradient(ellipse at 0% 100%, rgba(46, 125, 50, 0.3) 0%, transparent 60%),
                radial-gradient(ellipse at 100% 0%, rgba(14, 165, 233, 0.15) 0%, transparent 60%),
                var(--padel-navy);
    color: #ffffff;
    border-radius: 24px;
    padding: 60px 40px;
    position: relative;
    overflow: hidden;
}
.cta-banner-new::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0.02;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

/* Section Spacing Helper */
.about-section {
    padding: 80px 0;
}
.about-section-white {
    background-color: #ffffff;
    padding: 80px 0;
}
.section-title-line {
    width: 60px;
    height: 4px;
    background-color: var(--padel-green);
    margin: 16px auto 0 auto;
    border-radius: 2px;
}
.section-title-line-left {
    width: 60px;
    height: 4px;
    background-color: var(--padel-green);
    margin: 16px 0 0 0;
    border-radius: 2px;
}
</style>

<div class="about-wrapper">
    <!-- ==================== SECTION 1: HERO TENTANG ==================== -->
    <header class="hero-tentang">
        <div class="container position-relative" style="z-index: 2;">
            <div class="row align-items-center">
                <div class="col-lg-7 text-center text-lg-start mb-5 mb-lg-0">
                    <span class="hero-tagline">
                        <i class="bi bi-info-circle-fill"></i> Tentang PadelClub
                    </span>
                    <h1 class="hero-title">Tentang PadelClub</h1>
                    <p class="hero-subtitle">Platform reservasi lapangan padel yang membantu pelanggan melakukan pemesanan lapangan dengan cepat, mudah, aman, dan efisien.</p>
                    <div class="mt-4 pt-2">
                        <a href="booking.php" class="btn-padel-primary text-decoration-none me-3">
                            <i class="bi bi-calendar-check-fill"></i> Booking Sekarang
                        </a>
                        <a href="contact.php" class="btn-padel-outline text-decoration-none">
                            Hubungi Kami
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center d-flex justify-content-center">
                    <!-- SVG Ilustrasi Raket Padel & Lapangan Modern -->
                    <svg width="340" height="340" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 15px 30px rgba(46, 125, 50, 0.25)); max-width: 100%;">
                        <!-- Glow Background -->
                        <circle cx="200" cy="200" r="160" fill="url(#hero-glow)" opacity="0.15"/>
                        <!-- Court Line grid -->
                        <path d="M100 130H300M100 270H300M200 130V270M100 200H300" stroke="#2E7D32" stroke-width="3" stroke-linecap="round" opacity="0.3"/>
                        <rect x="80" y="80" width="240" height="240" rx="16" stroke="#2E7D32" stroke-width="4" opacity="0.4"/>
                        <!-- Padel Racket -->
                        <g transform="translate(140, 110) rotate(-15)">
                            <!-- Handle shadow -->
                            <rect x="52" y="110" width="16" height="70" rx="8" fill="#1E293B"/>
                            <rect x="52" y="150" width="16" height="30" rx="3" fill="#2E7D32"/>
                            <!-- Racket Body -->
                            <ellipse cx="60" cy="70" rx="55" ry="60" fill="url(#racket-gradient)" stroke="#2E7D32" stroke-width="6"/>
                            <!-- Inner holes pattern -->
                            <circle cx="60" cy="45" r="4" fill="#0F172A"/>
                            <circle cx="45" cy="55" r="4" fill="#0F172A"/>
                            <circle cx="60" cy="58" r="4" fill="#0F172A"/>
                            <circle cx="75" cy="55" r="4" fill="#0F172A"/>
                            <circle cx="35" cy="70" r="4" fill="#0F172A"/>
                            <circle cx="50" cy="71" r="4" fill="#0F172A"/>
                            <circle cx="70" cy="71" r="4" fill="#0F172A"/>
                            <circle cx="85" cy="70" r="4" fill="#0F172A"/>
                            <circle cx="45" cy="87" r="4" fill="#0F172A"/>
                            <circle cx="60" cy="85" r="4" fill="#0F172A"/>
                            <circle cx="75" cy="87" r="4" fill="#0F172A"/>
                            <circle cx="60" cy="98" r="4" fill="#0F172A"/>
                        </g>
                        <!-- Padel Ball -->
                        <g transform="translate(230, 210)">
                            <circle cx="30" cy="30" r="26" fill="#CCFF00"/>
                            <!-- Ball curve lines -->
                            <path d="M12 18C16.5 24.5 24 27.5 31.5 27M48 42C43.5 35.5 36 32.5 28.5 33" stroke="#2E7D32" stroke-width="2.5" stroke-linecap="round"/>
                        </g>
                        <!-- Definitions -->
                        <defs>
                            <radialGradient id="hero-glow" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(200 200) rotate(90) scale(160)">
                                <stop stop-color="#2E7D32"/>
                                <stop offset="1" stop-color="#0EA5E9" stop-opacity="0"/>
                            </radialGradient>
                            <linearGradient id="racket-gradient" x1="5" y1="10" x2="115" y2="130" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#34D399"/>
                                <stop offset="1" stop-color="#059669"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
    </header>

    <!-- ==================== SECTION 2: TENTANG KAMI ==================== -->
    <section class="about-section-white">
        <div class="container">
            <div class="row align-items-center">
                <!-- Kolom Kiri: Gambar Representatif -->
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="position-relative" style="border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.08);">
                        <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuDYFjPFZbQMNYkj2UO_WyllqDjPTkVQN2xMGy851LX2Qf-n7LA22hFNQ4fPtnTCUZdgURKgfBGyTEaqDYxkKpMKUPunDjY85LBrq2kPv_RE72ZvaElkG_vnQG5a7DiREN1WfV1KlAG12kZHDakqt76xCXr-SIljf08Q5TXuyHmkukflcXdOrOwqpVqtRgYc3o1taexAdkCcU7lglsKmqqBHpQ84w0gKtfuILqQ8_ZqdkWhqJIvuV6CxneGAgTtxO6n48pCsSO0LbalF" alt="PadelClub Court & Team" class="img-fluid w-100" style="object-fit: cover; aspect-ratio: 4/3; display: block;">
                        <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(46, 125, 50, 0.1), rgba(15, 23, 42, 0.45));"></div>
                    </div>
                </div>
                <!-- Kolom Kanan: Poin Nilai Pelayanan -->
                <div class="col-lg-6 ps-lg-5">
                    <div class="text-start">
                        <span style="font-size: 0.8rem; font-weight: 700; color: var(--padel-green); letter-spacing: 0.1em; text-transform: uppercase;">Siapa Kami</span>
                        <h2 class="mt-2 mb-3" style="font-weight: 800; color: var(--padel-navy); font-size: 2.2rem; letter-spacing: -0.02em;">Lebih Dari Sekadar Sistem Pemesanan</h2>
                        <p class="text-muted mb-4" style="line-height: 1.7;">Kami berkomitmen untuk menyediakan wadah olahraga terbaik bagi semua pemain padel. Melalui platform reservasi yang modern, kami terus meningkatkan kemudahan dan kenyamanan bermain Anda.</p>
                        
                        <!-- List Item 1 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Reservasi Lapangan Padel</h4>
                                <p>PadelClub merupakan sistem reservasi lapangan padel terintegrasi yang memudahkan akses jadwal lapangan kapan saja.</p>
                            </div>
                        </div>

                        <!-- List Item 2 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Kemudahan Booking</h4>
                                <p>Memudahkan pelanggan dalam melakukan proses booking dengan pilihan durasi bermain yang fleksibel sesuai kebutuhan.</p>
                            </div>
                        </div>

                        <!-- List Item 3 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Informasi Jadwal Lapangan</h4>
                                <p>Menyediakan informasi jadwal lapangan secara transparan dan up-to-date untuk menghindari bentrokan waktu.</p>
                            </div>
                        </div>

                        <!-- List Item 4 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <i class="bi bi-wallet2"></i>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Dukungan Proses Pembayaran</h4>
                                <p>Mendukung transaksi digital yang aman dengan konfirmasi pembayaran otomatis yang mempercepat validasi booking.</p>
                            </div>
                        </div>

                        <!-- List Item 5 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Membantu Manajemen Pengelola</h4>
                                <p>Membantu pengelola dalam mengatur reservasi, jadwal, data lapangan, serta pencatatan transaksi secara efisien.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 3: KEBIJAKAN PRIVASI ==================== -->
    <section class="about-section">
        <div class="container">
            <div class="text-center mb-5">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--padel-green); letter-spacing: 0.1em; text-transform: uppercase;">Keamanan &amp; Privasi</span>
                <h2 class="mt-2 mb-2" style="font-weight: 800; color: var(--padel-navy); font-size: 2.2rem; letter-spacing: -0.02em;">Kebijakan Privasi</h2>
                <p class="text-muted mx-auto" style="max-width: 580px;">Kami sangat menjaga kerahasiaan dan privasi data Anda. Berikut adalah cara kami mengelola informasi tersebut.</p>
                <div class="section-title-line"></div>
            </div>

            <div class="row g-4">
                <!-- Card 1: Data yang dikumpulkan -->
                <div class="col-md-6 col-lg-3">
                    <div class="padel-card text-start">
                        <div class="icon-badge">
                            <i class="bi bi-file-earmark-person"></i>
                        </div>
                        <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--padel-navy); margin-bottom: 12px;">Data Dikumpulkan</h4>
                        <ul class="text-muted d-flex flex-column gap-2" style="font-size: 0.92rem; line-height: 1.6;">
                            <li><i class="bi bi-dot text-success"></i> Nama lengkap</li>
                            <li><i class="bi bi-dot text-success"></i> Alamat email</li>
                            <li><i class="bi bi-dot text-success"></i> Nomor telepon</li>
                            <li><i class="bi bi-dot text-success"></i> Data detail reservasi</li>
                        </ul>
                    </div>
                </div>

                <!-- Card 2: Penggunaan data -->
                <div class="col-md-6 col-lg-3">
                    <div class="padel-card text-start">
                        <div class="icon-badge">
                            <i class="bi bi-gear-wide-connected"></i>
                        </div>
                        <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--padel-navy); margin-bottom: 12px;">Penggunaan Data</h4>
                        <ul class="text-muted d-flex flex-column gap-2" style="font-size: 0.92rem; line-height: 1.6;">
                            <li><i class="bi bi-dot text-success"></i> Memproses reservasi</li>
                            <li><i class="bi bi-dot text-success"></i> Konfirmasi booking</li>
                            <li><i class="bi bi-dot text-success"></i> Pelayanan pelanggan</li>
                            <li><i class="bi bi-dot text-success"></i> Pengembangan sistem</li>
                        </ul>
                    </div>
                </div>

                <!-- Card 3: Keamanan data -->
                <div class="col-md-6 col-lg-3">
                    <div class="padel-card text-start">
                        <div class="icon-badge">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--padel-navy); margin-bottom: 12px;">Keamanan Data</h4>
                        <ul class="text-muted d-flex flex-column gap-2" style="font-size: 0.92rem; line-height: 1.6;">
                            <li><i class="bi bi-dot text-success"></i> Disimpan dengan aman</li>
                            <li><i class="bi bi-dot text-success"></i> Enkripsi data sensitif</li>
                            <li><i class="bi bi-dot text-success"></i> Tidak dijual ke pihak ketiga</li>
                            <li><i class="bi bi-dot text-success"></i> Kebutuhan operasional</li>
                        </ul>
                    </div>
                </div>

                <!-- Card 4: Hak pengguna -->
                <div class="col-md-6 col-lg-3">
                    <div class="padel-card text-start">
                        <div class="icon-badge">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--padel-navy); margin-bottom: 12px;">Hak Pengguna</h4>
                        <ul class="text-muted d-flex flex-column gap-2" style="font-size: 0.92rem; line-height: 1.6;">
                            <li><i class="bi bi-dot text-success"></i> Melihat data profil</li>
                            <li><i class="bi bi-dot text-success"></i> Memperbarui informasi</li>
                            <li><i class="bi bi-dot text-success"></i> Hapus akun (ketentuan)</li>
                            <li><i class="bi bi-dot text-success"></i> Kontrol privasi penuh</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 4: SYARAT DAN KETENTUAN ==================== -->
    <section class="about-section-white">
        <div class="container">
            <div class="text-center mb-5">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--padel-green); letter-spacing: 0.1em; text-transform: uppercase;">Pedoman Bermain</span>
                <h2 class="mt-2 mb-2" style="font-weight: 800; color: var(--padel-navy); font-size: 2.2rem; letter-spacing: -0.02em;">Syarat &amp; Ketentuan</h2>
                <p class="text-muted mx-auto" style="max-width: 580px;">Harap membaca peraturan penggunaan fasilitas dan sistem kami dengan saksama demi kelancaran bersama.</p>
                <div class="section-title-line"></div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="padel-card" style="padding: 2.5rem 3rem;">
                        <div class="d-flex flex-column">
                            <!-- Rule 1 -->
                            <div class="terms-item">
                                <div class="terms-number">1</div>
                                <div class="terms-text">Pengguna wajib memiliki akun terdaftar yang valid di PadelClub untuk melakukan booking.</div>
                            </div>
                            <!-- Rule 2 -->
                            <div class="terms-item">
                                <div class="terms-number">2</div>
                                <div class="terms-text">Reservasi lapangan harus mengikuti jadwal slot bermain yang masih tersedia di sistem.</div>
                            </div>
                            <!-- Rule 3 -->
                            <div class="terms-item">
                                <div class="terms-number">3</div>
                                <div class="terms-text">Pembayaran wajib diselesaikan sesuai dengan nominal dan metode pembayaran resmi yang disediakan.</div>
                            </div>
                            <!-- Rule 4 -->
                            <div class="terms-item">
                                <div class="terms-number">4</div>
                                <div class="terms-text">Reservasi dianggap sah dan masuk dalam antrean jadwal setelah status pembayaran berhasil terverifikasi oleh sistem.</div>
                            </div>
                            <!-- Rule 5 -->
                            <div class="terms-item">
                                <div class="terms-number">5</div>
                                <div class="terms-text">Pembatalan reservasi serta pengembalian dana mengikuti kebijakan waktu pembatalan yang berlaku di PadelClub.</div>
                            </div>
                            <!-- Rule 6 -->
                            <div class="terms-item">
                                <div class="terms-number">6</div>
                                <div class="terms-text">Pengguna bertanggung jawab penuh atas kebenaran data identitas pribadi serta data kontak yang diberikan.</div>
                            </div>
                            <!-- Rule 7 -->
                            <div class="terms-item">
                                <div class="terms-number">7</div>
                                <div class="terms-text">Pihak pengelola berhak memindahkan atau mengubah jadwal reservasi apabila terjadi kondisi kahar (force majeure) tertentu.</div>
                            </div>
                            <!-- Rule 8 -->
                            <div class="terms-item">
                                <div class="terms-number">8</div>
                                <div class="terms-text">Setiap pemain wajib menjaga ketertiban, kebersihan, serta fasilitas penunjang di area lapangan PadelClub.</div>
                            </div>
                            <!-- Rule 9 -->
                            <div class="terms-item">
                                <div class="terms-number">9</div>
                                <div class="terms-text">Pelanggaran berat terhadap peraturan keamanan lapangan dapat mengakibatkan pembatalan booking secara sepihak oleh pengelola.</div>
                            </div>
                            <!-- Rule 10 -->
                            <div class="terms-item">
                                <div class="terms-number">10</div>
                                <div class="terms-text">Dengan menggunakan layanan website ini, pengguna dianggap menyetujui seluruh syarat dan ketentuan yang berlaku.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 5: FAQ (ACCORDION BOOTSTRAP) ==================== -->
    <section class="about-section">
        <div class="container">
            <div class="text-center mb-5">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--padel-green); letter-spacing: 0.1em; text-transform: uppercase;">Ada Pertanyaan?</span>
                <h2 class="mt-2 mb-2" style="font-weight: 800; color: var(--padel-navy); font-size: 2.2rem; letter-spacing: -0.02em;">Pertanyaan Umum (FAQ)</h2>
                <p class="text-muted mx-auto" style="max-width: 580px;">Temukan jawaban dari hal-hal yang sering ditanyakan seputar layanan dan operasional PadelClub.</p>
                <div class="section-title-line"></div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Bootstrap Accordion Murni -->
                    <div class="accordion faq-accordion" id="accordionFaq">
                        
                        <!-- FAQ Item 1 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    1. Bagaimana cara melakukan booking?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Pilih menu <strong>Booking</strong> pada navigasi, tentukan lapangan, tanggal, dan jam yang Anda inginkan. Setelah itu, pilih paket sewa tambahan jika diperlukan dan selesaikan proses pembayaran Anda.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    2. Apakah harus memiliki akun?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Ya, Anda diwajibkan mendaftar dan memiliki akun aktif di PadelClub agar transaksi serta riwayat booking Anda tercatat dengan aman dalam dashboard sistem.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 3 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    3. Bagaimana cara pembayaran?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Kami mendukung metode pembayaran transfer bank dan pembayaran digital lainnya. Setelah melakukan transfer, harap unggah bukti transfer di dashboard saya untuk proses verifikasi.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 4 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    4. Apakah booking dapat dibatalkan?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Pembatalan dapat dilakukan dengan menghubungi admin kami maksimal 24 jam sebelum jadwal bermain untuk memperoleh opsi penjadwalan ulang (reschedule) atau refund sesuai kebijakan.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 5 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    5. Kapan booking dianggap berhasil?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Booking Anda dianggap berhasil secara penuh ketika kasir atau sistem kami telah memverifikasi bukti transfer pembayaran Anda, dan status pesanan berubah menjadi 'Confirmed'.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 6 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSix">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                    6. Apakah bisa memilih jam bermain?
                                </button>
                            </h2>
                            <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Tentu saja. Di formulir booking, Anda dapat secara fleksibel menentukan tanggal bermain dan memilih slot jam mulai hingga jam selesai bermain selama lapangan masih tersedia.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 7 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSeven">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                    7. Apakah tersedia riwayat booking?
                                </button>
                            </h2>
                            <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Ya. Seluruh data transaksi, riwayat booking yang aktif, maupun yang telah selesai, dapat dipantau di halaman dashboard profil pengguna ('Dashboard Saya').
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 8 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingEight">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                    8. Bagaimana jika terjadi kendala pembayaran?
                                </button>
                            </h2>
                            <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Bila terjadi gangguan teknis saat melakukan pembayaran, silakan simpan tangkapan layar bukti transfer lalu kirimkan langsung ke tim bantuan kami melalui kontak WhatsApp admin.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 9 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingNine">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                                    9. Apakah data pribadi saya aman?
                                </button>
                            </h2>
                            <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Keamanan Anda adalah prioritas kami. Semua data kontak dan transaksi disimpan menggunakan metode penyimpanan yang terenkripsi dan tidak akan pernah dibagikan kepada pihak ketiga.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 10 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTen">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                                    10. Bagaimana menghubungi admin?
                                </button>
                            </h2>
                            <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen" data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    Anda dapat berkunjung ke menu <strong>Kontak</strong> untuk mengisi form bantuan, atau langsung mengklik link chat WhatsApp resmi yang tertera di bagian penutup halaman ini.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 6: PENUTUP ==================== -->
    <section class="about-section-white text-center pb-5">
        <div class="container">
            <div class="cta-banner-new text-center">
                <div class="position-relative" style="z-index: 2;">
                    <i class="bi bi-circle-square" style="font-size: 2.8rem; color: var(--padel-green); margin-bottom: 20px; display: inline-block; opacity: 0.85;"></i>
                    <h2 style="font-weight: 800; font-size: 2.2rem; margin-bottom: 12px; letter-spacing: -0.02em;">Siap Bermain Padel?</h2>
                    <p style="color: rgba(255,255,255,0.85); font-size: 1.1rem; max-width: 600px; margin: 0 auto 36px auto; line-height: 1.7;">
                        Nikmati pengalaman reservasi lapangan yang cepat, mudah, dan nyaman bersama PadelClub.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="booking.php" class="btn-padel-primary text-decoration-none">
                            <i class="bi bi-calendar-plus-fill"></i> Booking Sekarang
                        </a>
                        <a href="contact.php" class="btn-padel-outline text-decoration-none" style="border-color: rgba(255,255,255,0.4) !important; color: #ffffff !important;">
                            Hubungi Kami
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Bootstrap 5 JavaScript Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<?php include 'includes/footer.php'; ?>

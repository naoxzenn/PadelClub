<?php
session_start();
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

<div class="about-wrapper">
    <!-- ==================== SECTION 1: HERO TENTANG ==================== -->
    <header class="hero-tentang">
        <div class="container about-hero-container">
            <div class="about-hero-grid">
                <div class="about-hero-left">
                    <span class="hero-tagline">
                        <span class="material-symbols-outlined" style="font-size:1rem;">info</span> Tentang PadelClub
                    </span>
                    <h1 class="hero-title">Tentang PadelClub</h1>
                    <p class="hero-subtitle">Platform reservasi lapangan padel yang membantu pelanggan melakukan
                        pemesanan lapangan dengan cepat, mudah, aman, dan efisien.</p>
                    <div class="hero-actions">
                        <a href="booking.php" class="btn btn-primary">
                            <span class="material-symbols-outlined">calendar_month</span> Booking Sekarang
                        </a>
                        <a href="contact.php" class="btn btn-secondary glass-panel">
                            Hubungi Kami
                        </a>
                    </div>
                </div>
                <div class="about-hero-right">
                    <!-- SVG Ilustrasi Raket Padel & Lapangan Modern -->
                    <svg width="340" height="340" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg"
                        class="about-svg-glow">
                        <!-- Glow Background -->
                        <circle cx="200" cy="200" r="160" fill="url(#hero-glow)" opacity="0.15" />
                        <!-- Court Line grid -->
                        <path d="M100 130H300M100 270H300M200 130V270M100 200H300" stroke="#22C55E" stroke-width="3"
                            stroke-linecap="round" opacity="0.3" />
                        <rect x="80" y="80" width="240" height="240" rx="16" stroke="#22C55E" stroke-width="4"
                            opacity="0.4" />
                        <!-- Padel Racket -->
                        <g transform="translate(140, 110) rotate(-15)">
                            <!-- Handle shadow -->
                            <rect x="52" y="110" width="16" height="70" rx="8" fill="#1E293B" />
                            <rect x="52" y="150" width="16" height="30" rx="3" fill="#22C55E" />
                            <!-- Racket Body -->
                            <ellipse cx="60" cy="70" rx="55" ry="60" fill="url(#racket-gradient)" stroke="#22C55E"
                                stroke-width="6" />
                            <!-- Inner holes pattern -->
                            <circle cx="60" cy="45" r="4" fill="#0F172A" />
                            <circle cx="45" cy="55" r="4" fill="#0F172A" />
                            <circle cx="60" cy="58" r="4" fill="#0F172A" />
                            <circle cx="75" cy="55" r="4" fill="#0F172A" />
                            <circle cx="35" cy="70" r="4" fill="#0F172A" />
                            <circle cx="50" cy="71" r="4" fill="#0F172A" />
                            <circle cx="70" cy="71" r="4" fill="#0F172A" />
                            <circle cx="85" cy="70" r="4" fill="#0F172A" />
                            <circle cx="45" cy="87" r="4" fill="#0F172A" />
                            <circle cx="60" cy="85" r="4" fill="#0F172A" />
                            <circle cx="75" cy="87" r="4" fill="#0F172A" />
                            <circle cx="60" cy="98" r="4" fill="#0F172A" />
                        </g>
                        <!-- Padel Ball -->
                        <g transform="translate(230, 210)">
                            <circle cx="30" cy="30" r="26" fill="#CCFF00" />
                            <!-- Ball curve lines -->
                            <path d="M12 18C16.5 24.5 24 27.5 31.5 27M48 42C43.5 35.5 36 32.5 28.5 33" stroke="#22C55E"
                                stroke-width="2.5" stroke-linecap="round" />
                        </g>
                        <!-- Definitions -->
                        <defs>
                            <radialGradient id="hero-glow" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse"
                                gradientTransform="translate(200 200) rotate(90) scale(160)">
                                <stop stop-color="#22C55E" />
                                <stop offset="1" stop-color="#0EA5E9" stop-opacity="0" />
                            </radialGradient>
                            <linearGradient id="racket-gradient" x1="5" y1="10" x2="115" y2="130"
                                gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#22C55E" />
                                <stop offset="1" stop-color="#0EA5E9" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
    </header>

    <!-- ==================== SECTION 2: TENTANG KAMI ==================== -->
    <section class="section section-white">
        <div class="container">
            <div class="about-split-grid">
                <!-- Kolom Kiri: Gambar Representatif -->
                <div class="about-img-column">
                    <div class="about-img-frame">
                        <img src="assets/images/about/about-padel-01.webp" alt="PadelClub Court & Team"
                            class="about-img-content">
                        <div class="about-img-overlay-grad"></div>
                    </div>
                </div>
                <!-- Kolom Kanan: Poin Nilai Pelayanan -->
                <div class="about-text-column">
                    <span class="about-text-tag">Siapa Kami</span>
                    <h2 class="about-title-large">Lebih Dari Sekadar Sistem Pemesanan</h2>
                    <p class="about-lead-text">Kami berkomitmen untuk menyediakan wadah olahraga terbaik bagi semua
                        pemain padel. Melalui platform reservasi yang modern, kami terus meningkatkan kemudahan dan
                        kenyamanan bermain Anda.</p>

                    <div class="about-list">
                        <!-- List Item 1 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <span class="material-symbols-outlined">devices</span>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Reservasi Lapangan Padel</h4>
                                <p>PadelClub merupakan sistem reservasi lapangan padel terintegrasi yang memudahkan
                                    akses jadwal lapangan kapan saja.</p>
                            </div>
                        </div>

                        <!-- List Item 2 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <span class="material-symbols-outlined">calendar_month</span>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Kemudahan Booking</h4>
                                <p>Memudahkan pelanggan dalam melakukan proses booking dengan pilihan durasi bermain
                                    yang fleksibel sesuai kebutuhan.</p>
                            </div>
                        </div>

                        <!-- List Item 3 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <span class="material-symbols-outlined">schedule</span>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Informasi Jadwal Lapangan</h4>
                                <p>Menyediakan informasi jadwal lapangan secara transparan dan up-to-date untuk
                                    menghindari bentrokan waktu.</p>
                            </div>
                        </div>

                        <!-- List Item 4 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <span class="material-symbols-outlined">payments</span>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Dukungan Proses Pembayaran</h4>
                                <p>Mendukung transaksi digital yang aman dengan konfirmasi pembayaran otomatis yang
                                    mempercepat validasi booking.</p>
                            </div>
                        </div>

                        <!-- List Item 5 -->
                        <div class="tentang-list-item">
                            <div class="tentang-list-icon">
                                <span class="material-symbols-outlined">shield</span>
                            </div>
                            <div class="tentang-list-text">
                                <h4>Membantu Manajemen Pengelola</h4>
                                <p>Membantu pengelola dalam mengatur reservasi, jadwal, data lapangan, serta pencatatan
                                    transaksi secara efisien.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 3: KEBIJAKAN PRIVASI ==================== -->
    <section class="section">
        <div class="container">
            <div class="about-section-head-center">
                <span class="about-text-tag">Keamanan &amp; Privasi</span>
                <h2 class="about-title-large">Kebijakan Privasi</h2>
                <p class="about-desc-limited">Kami sangat menjaga kerahasiaan dan privasi data Anda. Berikut adalah cara
                    kami mengelola informasi tersebut.</p>
                <div class="section-title-line"></div>
            </div>

            <div class="about-cards-grid">
                <!-- Card 1: Data yang dikumpulkan -->
                <div class="padel-card">
                    <div class="icon-badge">
                        <span class="material-symbols-outlined">badge</span>
                    </div>
                    <h4 class="about-card-title-sub">Data Dikumpulkan</h4>
                    <ul class="about-card-list-items">
                        <li>Nama lengkap</li>
                        <li>Alamat email</li>
                        <li>Nomor telepon</li>
                        <li>Data detail reservasi</li>
                    </ul>
                </div>

                <!-- Card 2: Penggunaan data -->
                <div class="padel-card">
                    <div class="icon-badge">
                        <span class="material-symbols-outlined">settings_suggest</span>
                    </div>
                    <h4 class="about-card-title-sub">Penggunaan Data</h4>
                    <ul class="about-card-list-items">
                        <li>Memproses reservasi</li>
                        <li>Konfirmasi booking</li>
                        <li>Pelayanan pelanggan</li>
                        <li>Pengembangan sistem</li>
                    </ul>
                </div>

                <!-- Card 3: Keamanan data -->
                <div class="padel-card">
                    <div class="icon-badge">
                        <span class="material-symbols-outlined">verified_user</span>
                    </div>
                    <h4 class="about-card-title-sub">Keamanan Data</h4>
                    <ul class="about-card-list-items">
                        <li>Disimpan dengan aman</li>
                        <li>Enkripsi data sensitif</li>
                        <li>Tidak dijual ke pihak ketiga</li>
                        <li>Kebutuhan operasional</li>
                    </ul>
                </div>

                <!-- Card 4: Hak pengguna -->
                <div class="padel-card">
                    <div class="icon-badge">
                        <span class="material-symbols-outlined">manage_accounts</span>
                    </div>
                    <h4 class="about-card-title-sub">Hak Pengguna</h4>
                    <ul class="about-card-list-items">
                        <li>Melihat data profil</li>
                        <li>Memperbarui informasi</li>
                        <li>Hapus akun (ketentuan)</li>
                        <li>Kontrol privasi penuh</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 4: SYARAT DAN KETENTUAN ==================== -->
    <section class="section section-white">
        <div class="container">
            <div class="about-section-head-center">
                <span class="about-text-tag">Pedoman Bermain</span>
                <h2 class="about-title-large">Syarat &amp; Ketentuan</h2>
                <p class="about-desc-limited">Harap membaca peraturan penggunaan fasilitas dan sistem kami dengan
                    saksama demi kelancaran bersama.</p>
                <div class="section-title-line"></div>
            </div>

            <div class="about-terms-wrapper">
                <div class="padel-card about-card-large-pad">
                    <div class="terms-list">
                        <!-- Rule 1 -->
                        <div class="terms-item">
                            <div class="terms-number">1</div>
                            <div class="terms-text">Pengguna wajib memiliki akun terdaftar yang valid di PadelClub untuk
                                melakukan booking.</div>
                        </div>
                        <!-- Rule 2 -->
                        <div class="terms-item">
                            <div class="terms-number">2</div>
                            <div class="terms-text">Reservasi lapangan harus mengikuti jadwal slot bermain yang masih
                                tersedia di sistem.</div>
                        </div>
                        <!-- Rule 3 -->
                        <div class="terms-item">
                            <div class="terms-number">3</div>
                            <div class="terms-text">Pembayaran wajib diselesaikan sesuai dengan nominal dan metode
                                pembayaran resmi yang disediakan.</div>
                        </div>
                        <!-- Rule 4 -->
                        <div class="terms-item">
                            <div class="terms-number">4</div>
                            <div class="terms-text">Reservasi dianggap sah dan masuk dalam antrean jadwal setelah status
                                pembayaran berhasil terverifikasi oleh sistem.</div>
                        </div>
                        <!-- Rule 5 -->
                        <div class="terms-item">
                            <div class="terms-number">5</div>
                            <div class="terms-text">Pembatalan reservasi serta pengembalian dana mengikuti kebijakan
                                waktu pembatalan yang berlaku di PadelClub.</div>
                        </div>
                        <!-- Rule 6 -->
                        <div class="terms-item">
                            <div class="terms-number">6</div>
                            <div class="terms-text">Pengguna bertanggung jawab penuh atas kebenaran data identitas
                                pribadi serta data kontak yang diberikan.</div>
                        </div>
                        <!-- Rule 7 -->
                        <div class="terms-item">
                            <div class="terms-number">7</div>
                            <div class="terms-text">Pihak pengelola berhak memindahkan atau mengubah jadwal reservasi
                                apabila terjadi kondisi kahar (force majeure) tertentu.</div>
                        </div>
                        <!-- Rule 8 -->
                        <div class="terms-item">
                            <div class="terms-number">8</div>
                            <div class="terms-text">Setiap pemain wajib menjaga ketertiban, kebersihan, serta fasilitas
                                penunjang di area lapangan PadelClub.</div>
                        </div>
                        <!-- Rule 9 -->
                        <div class="terms-item">
                            <div class="terms-number">9</div>
                            <div class="terms-text">Pelanggaran berat terhadap peraturan keamanan lapangan dapat
                                mengakibatkan pembatalan booking secara sepihak oleh pengelola.</div>
                        </div>
                        <!-- Rule 10 -->
                        <div class="terms-item">
                            <div class="terms-number">10</div>
                            <div class="terms-text">Dengan menggunakan layanan website ini, pengguna dianggap menyetujui
                                seluruh syarat dan ketentuan yang berlaku.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 5: FAQ ==================== -->
    <section class="section">
        <div class="container">
            <div class="about-section-head-center">
                <span class="about-text-tag">Ada Pertanyaan?</span>
                <h2 class="about-title-large">Pertanyaan Umum (FAQ)</h2>
                <p class="about-desc-limited">Temukan jawaban dari hal-hal yang sering ditanyakan seputar layanan dan
                    operasional PadelClub.</p>
                <div class="section-title-line"></div>
            </div>

            <div class="about-faq-wrapper">
                <!-- FAQ Item 1 -->
                <details class="faq-details" open>
                    <summary>1. Bagaimana cara melakukan booking?</summary>
                    <div class="faq-content">
                        Pilih menu <strong>Booking</strong> pada navigasi, tentukan lapangan, tanggal, dan jam yang Anda
                        inginkan. Setelah itu, pilih paket sewa tambahan jika diperlukan dan selesaikan proses
                        pembayaran Anda.
                    </div>
                </details>

                <!-- FAQ Item 2 -->
                <details class="faq-details">
                    <summary>2. Apakah harus memiliki akun?</summary>
                    <div class="faq-content">
                        Ya, Anda diwajibkan mendaftar dan memiliki akun aktif di PadelClub agar transaksi serta riwayat
                        booking Anda tercatat dengan aman dalam dashboard sistem.
                    </div>
                </details>

                <!-- FAQ Item 3 -->
                <details class="faq-details">
                    <summary>3. Bagaimana cara pembayaran?</summary>
                    <div class="faq-content">
                        Kami mendukung metode pembayaran transfer bank dan pembayaran digital lainnya. Setelah melakukan
                        transfer, harap unggah bukti transfer di dashboard saya untuk proses verifikasi.
                    </div>
                </details>

                <!-- FAQ Item 4 -->
                <details class="faq-details">
                    <summary>4. Apakah booking dapat dibatalkan?</summary>
                    <div class="faq-content">
                        Pembatalan dapat dilakukan dengan menghubungi admin kami maksimal 24 jam sebelum jadwal bermain
                        untuk memperoleh opsi penjadwalan ulang (reschedule) atau refund sesuai kebijakan.
                    </div>
                </details>

                <!-- FAQ Item 5 -->
                <details class="faq-details">
                    <summary>5. Kapan booking dianggap berhasil?</summary>
                    <div class="faq-content">
                        Booking Anda dianggap berhasil secara penuh ketika kasir atau sistem kami telah memverifikasi
                        bukti transfer pembayaran Anda, dan status pesanan berubah menjadi 'Confirmed'.
                    </div>
                </details>

                <!-- FAQ Item 6 -->
                <details class="faq-details">
                    <summary>6. Apakah bisa memilih jam bermain?</summary>
                    <div class="faq-content">
                        Tentu saja. Di formulir booking, Anda dapat secara fleksibel menentukan tanggal bermain dan
                        memilih slot jam mulai hingga jam selesai bermain selama lapangan masih tersedia.
                    </div>
                </details>

                <!-- FAQ Item 7 -->
                <details class="faq-details">
                    <summary>7. Apakah tersedia riwayat booking?</summary>
                    <div class="faq-content">
                        Ya. Seluruh data transaksi, riwayat booking yang aktif, maupun yang telah selesai, dapat
                        dipantau di halaman dashboard profil pengguna ('Dashboard Saya').
                    </div>
                </details>

                <!-- FAQ Item 8 -->
                <details class="faq-details">
                    <summary>8. Bagaimana jika terjadi kendala pembayaran?</summary>
                    <div class="faq-content">
                        Bila terjadi gangguan teknis saat melakukan pembayaran, silakan simpan tangkapan layar bukti
                        transfer lalu kirimkan langsung ke tim bantuan kami melalui kontak WhatsApp admin.
                    </div>
                </details>

                <!-- FAQ Item 9 -->
                <details class="faq-details">
                    <summary>9. Apakah data pribadi saya aman?</summary>
                    <div class="faq-content">
                        Keamanan Anda adalah prioritas kami. Semua data kontak dan transaksi disimpan menggunakan metode
                        penyimpanan yang terenkripsi dan tidak akan pernah dibagikan kepada pihak ketiga.
                    </div>
                </details>

                <!-- FAQ Item 10 -->
                <details class="faq-details">
                    <summary>10. Bagaimana menghubungi admin?</summary>
                    <div class="faq-content">
                        Anda dapat berkunjung ke menu <strong>Kontak</strong> untuk mengisi form bantuan, atau langsung
                        mengklik link chat WhatsApp resmi yang tertera di bagian penutup halaman ini.
                    </div>
                </details>
            </div>
        </div>
    </section>

    <!-- ==================== SECTION 6: PENUTUP ==================== -->
    <section class="container" style="padding-bottom: 64px;">
        <div class="cta-banner">
            <h2>Siap Bermain Padel?</h2>
            <p>Nikmati pengalaman reservasi lapangan yang cepat, mudah, dan nyaman bersama PadelClub.</p>
            <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin-top:28px;">
                <a href="booking.php" class="btn btn-primary">
                    <span class="material-symbols-outlined">calendar_month</span> Booking Sekarang
                </a>
                <a href="contact.php" class="btn btn-secondary glass-panel">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
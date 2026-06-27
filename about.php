<?php
session_start();
$pageTitle = 'Tentang Kami';
$baseUrl = '';
?>
<?php include 'includes/header.php'; ?>

<!-- HERO -->
<header class="about-hero">
    <div class="container" style="position:relative; z-index:1;">
        <div class="fade-up">
            <span style="display:inline-flex; align-items:center; gap:8px; font-size:.8rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.5); margin-bottom:20px;">
                <span class="material-symbols-outlined" style="font-size:1rem; color:var(--green);">sports_tennis</span>
                Tentang PadelClub
            </span>
            <h1>Membangun Komunitas<br><span style="background:var(--gradient);-webkit-background-clip:text;background-clip:text;color:transparent;">Padel Terbaik</span> Indonesia</h1>
            <p class="lead">Kami percaya bahwa setiap orang berhak mendapatkan pengalaman bermain padel yang luar biasa — dengan fasilitas premium, booking yang mudah, dan komunitas yang mendukung.</p>
            <div style="display:flex; gap:12px; margin-top:32px; flex-wrap:wrap;">
                <a href="booking.php" class="btn btn-primary" style="background:var(--gradient); color:#fff; border:none;">
                    <span class="material-symbols-outlined">calendar_month</span> Book Sekarang
                </a>
                <a href="contact.php" class="btn btn-outline" style="color:rgba(255,255,255,.85); border-color:rgba(255,255,255,.3);">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </div>
</header>

<!-- WHO WE ARE -->
<section class="section">
    <div class="container">
        <div class="who-we-are-grid">
            <div class="who-we-are-img fade-up">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuDYFjPFZbQMNYkj2UO_WyllqDjPTkVQN2xMGy851LX2Qf-n7LA22hFNQ4fPtnTCUZdgURKgfBGyTEaqDYxkKpMKUPunDjY85LBrq2kPv_RE72ZvaElkG_vnQG5a7DiREN1WfV1KlAG12kZHDakqt76xCXr-SIljf08Q5TXuyHmkukflcXdOrOwqpVqtRgYc3o1taexAdkCcU7lglsKmqqBHpQ84w0gKtfuILqQ8_ZqdkWhqJIvuV6CxneGAgTtxO6n48pCsSO0LbalF" alt="Tim PadelClub">
                <div class="img-overlay"></div>
            </div>
            <div class="fade-up" style="--delay:.1s">
                <span style="font-size:.75rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--blue); margin-bottom:12px; display:block;">Siapa Kami</span>
                <h2 style="font-size:1.9rem; font-weight:800; letter-spacing:-.02em; color:var(--navy); margin-bottom:16px;">Lebih dari Sekadar Lapangan Padel</h2>
                <p style="color:var(--text-muted); line-height:1.75; margin-bottom:16px;">PadelClub lahir dari kecintaan mendalam terhadap olahraga padel dan keinginan untuk membuat pengalaman bermain menjadi lebih mudah, menyenangkan, dan profesional bagi semua orang.</p>
                <p style="color:var(--text-muted); line-height:1.75; margin-bottom:16px;">Sejak berdiri, kami telah melayani ribuan pemain dari berbagai latar belakang — mulai dari pemula yang baru mengenal padel hingga atlet berpengalaman yang berkompetisi di level nasional.</p>
                <p style="color:var(--text-muted); line-height:1.75; margin-bottom:28px;">Dengan visi menjadi platform padel terkemuka di Indonesia, kami terus berinovasi dalam teknologi booking, fasilitas lapangan, dan pengembangan komunitas.</p>

                <div class="who-we-are-stats">
                    <div class="stat-chip">
                        <div class="num">5+</div>
                        <div class="lbl">Tahun Pengalaman</div>
                    </div>
                    <div class="stat-chip">
                        <div class="num">10+</div>
                        <div class="lbl">Lapangan Premium</div>
                    </div>
                    <div class="stat-chip">
                        <div class="num">5K+</div>
                        <div class="lbl">Member Aktif</div>
                    </div>
                    <div class="stat-chip">
                        <div class="num">99%</div>
                        <div class="lbl">Kepuasan Member</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHY CHOOSE US -->
<section class="section" style="background:#fff;">
    <div class="container">
        <div class="section-title-center fade-up">
            <h2>Mengapa Memilih PadelClub?</h2>
            <p>Kami menghadirkan standar tertinggi dalam setiap aspek pengalaman bermain padel Anda.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card fade-up">
                <div class="feature-icon" style="background:rgba(34,197,94,.1); color:var(--green);">
                    <span class="material-symbols-outlined">workspace_premium</span>
                </div>
                <h3>Lapangan Premium</h3>
                <p>Lapangan berstandar internasional dengan rumput sintetis berkualitas tinggi dan kaca tempered berstandar turnamen resmi.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon">
                    <span class="material-symbols-outlined">bolt</span>
                </div>
                <h3>Booking Mudah & Cepat</h3>
                <p>Sistem booking digital 100% — pilih lapangan, tanggal, dan jam dalam hitungan detik. Konfirmasi langsung tersimpan ke dashboard Anda.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon" style="background:rgba(245,158,11,.1); color:#D97706;">
                    <span class="material-symbols-outlined">support_agent</span>
                </div>
                <h3>Layanan Profesional</h3>
                <p>Tim staff kami yang terlatih siap membantu setiap kebutuhan Anda, dari pertanyaan booking hingga rekomendasi jadwal terbaik.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon" style="background:rgba(239,68,68,.1); color:#DC2626;">
                    <span class="material-symbols-outlined">star</span>
                </div>
                <h3>Pengalaman Terbaik</h3>
                <p>Setiap detail dirancang untuk memastikan Anda mendapatkan pengalaman bermain padel yang tak terlupakan, dari area parkir hingga fasilitas shower.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon" style="background:rgba(168,85,247,.1); color:#9333EA;">
                    <span class="material-symbols-outlined">groups</span>
                </div>
                <h3>Komunitas Aktif</h3>
                <p>Bergabunglah dengan ribuan member aktif yang siap menjadi lawan main setiap saat. Temukan teman baru dan tingkatkan permainan Anda.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon" style="background:rgba(14,165,233,.1); color:var(--blue);">
                    <span class="material-symbols-outlined">shield</span>
                </div>
                <h3>Aman & Terpercaya</h3>
                <p>Setiap transaksi terenkripsi dan terverifikasi. Data dan pembayaran Anda 100% aman bersama kami.</p>
            </div>
        </div>
    </div>
</section>

<!-- OUR MISSION -->
<section class="section">
    <div class="container">
        <div class="mission-card fade-up">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:start;">
                <div>
                    <span style="font-size:.75rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.5); margin-bottom:12px; display:block; position:relative;z-index:1;">Misi Kami</span>
                    <h2>Menghadirkan Padel untuk Semua</h2>
                    <p>Misi kami sederhana: membuat olahraga padel dapat diakses, dinikmati, dan dicintai oleh semua kalangan masyarakat Indonesia. Kami percaya bahwa padel bukan hanya olahraga — ini adalah gaya hidup yang menyatukan komunitas.</p>
                    <ul class="mission-list" style="margin-top:28px;">
                        <li>
                            <span class="material-symbols-outlined check">check_circle</span>
                            <span>Menyediakan fasilitas lapangan padel berkualitas dunia yang dapat diakses oleh semua kalangan</span>
                        </li>
                        <li>
                            <span class="material-symbols-outlined check">check_circle</span>
                            <span>Mengembangkan ekosistem padel yang inklusif, kompetitif, dan menyenangkan</span>
                        </li>
                        <li>
                            <span class="material-symbols-outlined check">check_circle</span>
                            <span>Memanfaatkan teknologi untuk memberikan pengalaman booking yang seamless dan efisien</span>
                        </li>
                        <li>
                            <span class="material-symbols-outlined check">check_circle</span>
                            <span>Mendukung pertumbuhan komunitas padel di seluruh Indonesia dengan program-program inovatif</span>
                        </li>
                    </ul>
                </div>
                <div style="position:relative; z-index:1;">
                    <div style="background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:var(--radius-lg); padding:28px; margin-bottom:16px;">
                        <span class="material-symbols-outlined" style="color:var(--green); font-size:2rem; margin-bottom:12px; display:block;">visibility</span>
                        <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:8px;">Visi</h3>
                        <p style="font-size:.9rem; color:rgba(255,255,255,.75);">Menjadi platform padel nomor satu di Indonesia yang menghubungkan jutaan pemain dengan fasilitas terbaik di seluruh nusantara.</p>
                    </div>
                    <div style="background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:var(--radius-lg); padding:28px;">
                        <span class="material-symbols-outlined" style="color:var(--blue); font-size:2rem; margin-bottom:12px; display:block;">favorite</span>
                        <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:8px;">Komitmen</h3>
                        <p style="font-size:.9rem; color:rgba(255,255,255,.75);">Kami berkomitmen untuk terus berinovasi, mendengarkan masukan member, dan memberikan standar layanan tertinggi setiap harinya.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="container" style="padding-bottom:64px;">
    <div class="cta-banner fade-up">
        <span class="material-symbols-outlined" style="font-size:2.5rem; margin-bottom:16px; display:block; opacity:.4;">sports_tennis</span>
        <h2>Siap Bergabung dengan Komunitas PadelClub?</h2>
        <p>Daftarkan diri Anda sekarang dan nikmati fasilitas padel premium bersama ribuan member kami.</p>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
            <a href="booking.php" class="btn btn-primary">
                <span class="material-symbols-outlined">calendar_month</span> Book Sekarang
            </a>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
            <div style="display:inline-flex; gap:12px; flex-wrap:wrap; justify-content:center;">
                <a href="register.php" class="btn btn-primary">Daftar Sekarang</a>
                <a href="contact.php" class="btn btn-outline" style="color:#fff; border-color:rgba(255,255,255,.35);">Hubungi Kami</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

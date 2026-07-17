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
$pageTitle = 'Kontak';
$baseUrl = '';
?>
<?php include 'includes/header.php'; ?>

<!-- HERO -->
<section class="page-header">
    <div class="container">
        <div class="fade-up">
            <span style="display:inline-flex; align-items:center; gap:8px; font-size:.75rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--blue); margin-bottom:12px;">
                <span class="material-symbols-outlined" style="font-size:1rem;">contact_mail</span>
                Hubungi Kami
            </span>
            <h1>Kami Senang Mendengar dari Anda</h1>
            <p>Ada pertanyaan, saran, atau sekadar ingin menyapa? Tim kami siap membantu Anda setiap saat.</p>
        </div>
    </div>
</section>

<!-- CONTACT GRID -->
<section class="section">
    <div class="container">
        <div class="contact-grid">

            <!-- INFO CARD -->
            <div class="contact-info-card fade-up">
                <h3>Informasi Kontak</h3>
                <p>Temukan kami di sini atau kirim pesan melalui form.</p>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <span class="material-symbols-outlined">location_on</span>
                    </div>
                    <div>
                        <strong>Alamat</strong>
                        <span>Jl. Tulang Bawang Sel. No.26, Kadipiro, <br>Kec. Banjarsari,Kota Surakarta, Jawa Tengah 57136</span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <span class="material-symbols-outlined">phone</span>
                    </div>
                    <div>
                        <strong>Nomor Telepon</strong>
                        <span>+62 812-3456-7890<br>+62 21-8888-9999</span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <span class="material-symbols-outlined">mail</span>
                    </div>
                    <div>
                        <strong>Email</strong>
                        <span>info@padelclub.id<br>booking@padelclub.id</span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">
                        <span class="material-symbols-outlined">schedule</span>
                    </div>
                    <div>
                        <strong>Jam Operasional</strong>
                        <span>Senin – Jumat: 07.00 – 22.00 WIB<br>Sabtu – Minggu: 06.00 – 23.00 WIB</span>
                    </div>
                </div>

                <!-- Social links -->
                <div style="margin-top:32px; position:relative; z-index:1;">
                    <p style="font-size:.78rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,.4); margin-bottom:14px;">Ikuti Kami</p>
                    <div style="display:flex; gap:10px;">
                        <a href="#" style="width:40px;height:40px;border-radius:var(--radius-full);background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.8);transition:background .2s;" aria-label="Instagram">
                            <span class="material-symbols-outlined" style="font-size:1.1rem;">photo_camera</span>
                        </a>
                        <a href="#" style="width:40px;height:40px;border-radius:var(--radius-full);background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.8);transition:background .2s;" aria-label="TikTok">
                            <span class="material-symbols-outlined" style="font-size:1.1rem;">videocam</span>
                        </a>
                        <a href="#" style="width:40px;height:40px;border-radius:var(--radius-full);background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.8);transition:background .2s;" aria-label="WhatsApp">
                            <span class="material-symbols-outlined" style="font-size:1.1rem;">chat</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- CONTACT FORM -->
            <div class="contact-form-card fade-up">
                <h3>Kirim Pesan</h3>
                <form id="contact-form" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cf-name">Nama Lengkap <span style="color:#EF4444;">*</span></label>
                            <input type="text" id="cf-name" name="name" placeholder="Nama lengkap Anda" autocomplete="name">
                            <div class="field-error" id="err-name">Nama wajib diisi.</div>
                        </div>
                        <div class="form-group">
                            <label for="cf-email">Email <span style="color:#EF4444;">*</span></label>
                            <input type="email" id="cf-email" name="email" placeholder="email@contoh.com" autocomplete="email">
                            <div class="field-error" id="err-email">Masukkan alamat email yang valid.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cf-subject">Subjek <span style="color:#EF4444;">*</span></label>
                        <select id="cf-subject" name="subject">
                            <option value="">-- Pilih Subjek --</option>
                            <option value="booking">Pertanyaan Booking</option>
                            <option value="payment">Pembayaran</option>
                            <option value="facility">Fasilitas Lapangan</option>
                            <option value="complaint">Keluhan / Feedback</option>
                            <option value="partnership">Kerjasama</option>
                            <option value="other">Lainnya</option>
                        </select>
                        <div class="field-error" id="err-subject">Silakan pilih subjek.</div>
                    </div>

                    <div class="form-group">
                        <label for="cf-message">Pesan <span style="color:#EF4444;">*</span></label>
                        <textarea id="cf-message" name="message" rows="5" placeholder="Tuliskan pesan Anda di sini..."></textarea>
                        <div class="field-error" id="err-message">Pesan wajib diisi (minimal 10 karakter).</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="btn-send">
                        <span class="material-symbols-outlined">send</span>
                        Kirim Pesan
                    </button>
                </form>
            </div>
        </div>

        <!-- MAP — Universitas Muhammadiyah PKU Surakarta, Jl. Tulang Bawang Selatan No. 26, Kadipiro, Banjarsari, Surakarta -->
        <div class="map-embed fade-up">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.6723!2d110.8222!3d-7.5369!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a164e27a2c58d%3A0x9c95e988e6f7e56!2sUniversitas%20Muhammadiyah%20PKU%20Surakarta!5e0!3m2!1sid!2sid!4v1719480000000!5m2!1sid!2sid"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Lokasi PadelClub — Universitas Muhammadiyah PKU Surakarta"
            ></iframe>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="container" style="padding-bottom:64px;">
    <div class="cta-banner fade-up">
        <h2>Langsung Booking Lapangan?</h2>
        <p>Tidak perlu menunggu — pilih lapangan dan book sekarang dalam hitungan detik.</p>
        <a href="<?= isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer' ? 'booking.php' : 'register.php' ?>" class="btn btn-primary">
            <span class="material-symbols-outlined">calendar_month</span>
            <?= isset($_SESSION['user_id']) ? 'Book Sekarang' : 'Daftar & Book' ?>
        </a>
    </div>
</section>

<script>
(function() {
    const form = document.getElementById('contact-form');
    const btnSend = document.getElementById('btn-send');

    function validate() {
        let valid = true;

        // Name
        const name = document.getElementById('cf-name');
        const errName = document.getElementById('err-name');
        if (!name.value.trim()) {
            name.classList.add('invalid');
            errName.classList.add('show');
            valid = false;
        } else {
            name.classList.remove('invalid');
            errName.classList.remove('show');
        }

        // Email
        const email = document.getElementById('cf-email');
        const errEmail = document.getElementById('err-email');
        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRe.test(email.value.trim())) {
            email.classList.add('invalid');
            errEmail.classList.add('show');
            valid = false;
        } else {
            email.classList.remove('invalid');
            errEmail.classList.remove('show');
        }

        // Subject
        const subject = document.getElementById('cf-subject');
        const errSubject = document.getElementById('err-subject');
        if (!subject.value) {
            subject.classList.add('invalid');
            errSubject.classList.add('show');
            valid = false;
        } else {
            subject.classList.remove('invalid');
            errSubject.classList.remove('show');
        }

        // Message
        const message = document.getElementById('cf-message');
        const errMessage = document.getElementById('err-message');
        if (message.value.trim().length < 10) {
            message.classList.add('invalid');
            errMessage.classList.add('show');
            valid = false;
        } else {
            message.classList.remove('invalid');
            errMessage.classList.remove('show');
        }

        return valid;
    }

    // Real-time clear on input
    ['cf-name','cf-email','cf-subject','cf-message'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function() {
            this.classList.remove('invalid');
            const err = document.getElementById('err-' + id.replace('cf-',''));
            if (err) err.classList.remove('show');
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!validate()) return;

        // Simulate async send
        btnSend.classList.add('btn-loading');
        btnSend.disabled = true;

        setTimeout(function() {
            btnSend.classList.remove('btn-loading');
            btnSend.disabled = false;
            form.reset();
            showToast('Pesan Anda berhasil terkirim! Kami akan segera menghubungi Anda.', 'success', 5000);
        }, 1500);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>

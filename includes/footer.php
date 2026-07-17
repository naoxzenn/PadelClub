<?php
$role = $_SESSION['role'] ?? '';
?>
<?php if ($role === 'admin' || $role === 'kasir'): ?>
    </div> <!-- Close dashboard-content -->
    </main> <!-- Close dashboard-main -->
    </div> <!-- Close dashboard-container -->

    <script>
        // Sidebar toggle for mobile/responsive dashboard
        const toggleSidebar = () => {
            document.querySelector('.dashboard-sidebar')?.classList.toggle('show');
        };
        document.getElementById('sidebar-toggle')?.addEventListener('click', toggleSidebar);
        document.getElementById('sidebar-toggle-admin')?.addEventListener('click', toggleSidebar);

        // Close sidebar on outside click on mobile
        document.addEventListener('click', function (e) {
            const sidebar = document.querySelector('.dashboard-sidebar');
            const toggle1 = document.getElementById('sidebar-toggle');
            const toggle2 = document.getElementById('sidebar-toggle-admin');
            const isClickInsideSidebar = sidebar && sidebar.contains(e.target);
            const isClickToggle1 = toggle1 && toggle1.contains(e.target);
            const isClickToggle2 = toggle2 && toggle2.contains(e.target);
            if (sidebar && sidebar.classList.contains('show') && !isClickInsideSidebar && !isClickToggle1 && !isClickToggle2) {
                sidebar.classList.remove('show');
            }
        });
    </script>
    </body>

    </html>
<?php else: ?>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand gradient-text">PadelClub</div>
                    <p class="footer-desc">Membangun ekosistem padel modern bagi komunitas yang berjiwa muda dan
                        berprestasi.</p>
                    <div class="footer-socials">
                        <a href="#" aria-label="Instagram"><span class="material-symbols-outlined">photo_camera</span></a>
                        <a href="#" aria-label="TikTok"><span class="material-symbols-outlined">videocam</span></a>
                        <a href="#" aria-label="WhatsApp"><span class="material-symbols-outlined">chat</span></a>
                    </div>
                </div>
                <div>
                    <h4>Navigasi</h4>
                    <ul>
                        <li><a href="<?= $baseUrl ?? '' ?>index.php">Beranda</a></li>
                        <li><a href="<?= $baseUrl ?? '' ?>about.php">Tentang Kami</a></li>
                        <li><a href="<?= $baseUrl ?? '' ?>contact.php">Kontak</a></li>
                        <li><a href="<?= $baseUrl ?? '' ?>booking.php">Booking</a></li>
                        <li><a href="<?= $baseUrl ?? '' ?>login.php">Masuk</a></li>
                        <li><a href="<?= $baseUrl ?? '' ?>register.php">Daftar</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Bantuan</h4>
                    <ul>
                        <li><a href="contact.php">Hubungi Kami</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">Syarat &amp; Ketentuan</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Newsletter</h4>
                    <p class="footer-desc" style="margin-bottom:14px;">Dapatkan info promo &amp; jadwal lapangan terbaru
                        setiap minggunya.</p>
                    <form class="footer-newsletter" onsubmit="return false;">
                        <input type="email" placeholder="Email kamu">
                        <button type="submit" aria-label="Subscribe"><span
                                class="material-symbols-outlined">send</span></button>
                    </form>
                </div>
            </div>

            <div class="footer-bottom">
                &copy; <?= date('Y') ?> PadelClub Premium. Booking lapangan padel terbaik Indonesia.
            </div>
        </div>
    </footer>

    <script>
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) entry.target.classList.add('visible');
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));
    </script>


    </body>

    </html>
<?php endif; ?>
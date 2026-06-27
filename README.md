# 🎾 PadelClub – Sistem Booking Lapangan Padel Multi-Role

Aplikasi web booking lapangan padel berbasis **PHP Native**, **MySQL**, dan **Vanilla CSS**. Sistem ini mengusung arsitektur multi-role yang membagi akses pengguna menjadi tiga peran: **Admin**, **Kasir**, dan **Customer**.

---

## 🚀 Fitur Utama

### 👤 Customer (Halaman Publik & Area Member)
- **Beranda & Pencarian Lapangan:** Halaman utama interaktif lengkap dengan navigasi premium, pencarian tipe lapangan (Indoor/Outdoor), dan status statistik real-time.
- **Tentang Kami (About Page):** Desain visual modern yang menampilkan profil perusahaan, visi/misi, testimonial pelanggan, galeri aktivitas, dan alasan memilih PadelClub.
- **Hubungi Kami (Contact Page):** Form kontak interaktif dengan feedback toast, informasi kontak resmi, dan integrasi Google Maps responsif yang mengarah ke lokasi baru.
- **Pemesanan Lapangan (Booking):** Form pemesanan slot waktu bermain dengan validasi ketersediaan dan pencegahan konflik jadwal secara real-time.
- **Pilihan Paket Pembayaran:** Opsi pemesanan fleksibel berdasarkan **Per Jam** atau **Per Match** ditambah dengan sewa peralatan (Raket).
- **Pembayaran Mandiri:** Rincian pembayaran otomatis dengan dukungan transfer bank dan fitur upload bukti pembayaran.
- **Dashboard Riwayat Pemesanan:** Pantau status transaksi (`pending`, `confirmed`, `cancelled`), riwayat tagihan, dan pembatalan pesanan yang masih berstatus pending.

### 💼 Kasir (Kasir Area)
- **Dashboard Statistik Kasir:** Pantau ringkasan pendapatan tunai harian, total pesanan pending, dan total transaksi hari ini.
- **Konfirmasi Booking Instan:** Validasi dan konfirmasi pemesanan customer langsung dari dashboard kasir.
- **Penerimaan Pembayaran Tunai (Cash):** Catat pembayaran tunai secara langsung di kasir, secara otomatis memperbarui status pesanan menjadi `paid`, dan mengaitkan riwayat transaksi dengan akun kasir yang bertugas.
- **Cetak Struk Resmi (PDF Receipt):** Cetak bukti pembayaran secara dinamis ke dalam format PDF resmi menggunakan library **Dompdf**.
- **Profil Kasir:** Lihat data pribadi akun kasir yang sedang aktif.

### 🛡️ Administrator (Admin Area)
- **Dashboard Analitik Lengkap:** Menampilkan ringkasan seluruh status booking, total pendapatan, grafik visual, dan transaksi terbaru.
- **Verifikasi Pembayaran Transfer:** Periksa bukti transfer yang diunggah customer dan verifikasi pembayaran untuk melakukan auto-konfirmasi pesanan.
- **Manajemen Lapangan:** Tambah lapangan baru (Indoor/Outdoor) beserta spesifikasi harga per jam dan deskripsi, serta aktifkan/nonaktifkan lapangan secara dinamis.
- **Manajemen Pengguna:** Lihat dan kelola daftar customer terdaftar pada sistem.

---

## 🛠️ Persyaratan Sistem

- **PHP 7.4+** (Direkomendasikan PHP 8.x)
- **MySQL 5.7+** atau **MariaDB**
- **Composer** (untuk dependensi Dompdf)
- Web Server lokal seperti **XAMPP**, **Laragon**, **WAMP**, atau konfigurasi Apache/Nginx.

---

## ⚙️ Cara Instalasi & Setup (Self-Healing System)

Aplikasi ini dilengkapi dengan mekanisme **Self-Healing** yang secara otomatis akan memeriksa skema database, membuat tabel-tabel yang diperlukan, dan melakukan *seeding* data awal saat aplikasi pertama kali dijalankan.

1. **Unduh & Letakkan Kode Sumber:**
   Letakkan folder project ini di direktori web server Anda (misal `C:\xampp\htdocs\padelmania\`).

2. **Instal Dependensi (Vendor):**
   Pastikan Anda menginstal dependensi composer untuk Dompdf dengan menjalankan perintah berikut di terminal folder project:
   ```bash
   composer install
   ```

3. **Konfigurasi Database:**
   Buka berkas `config/koneksi.php` dan sesuaikan kredensial server database MySQL Anda:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Password database Anda
   define('DB_NAME', 'MyPadel');
   ```

4. **Jalankan Inisialisasi Otomatis (Setup):**
   Buka browser Anda dan akses halaman setup untuk memigrasi dan melakukan *seeding* database secara otomatis:
   ```
   http://localhost/padelmania/setup.php
   ```
   *Atau jalankan skrip setup langsung via CLI:*
   ```bash
   php setup.php
   ```
   *(Setelah inisialisasi berhasil, disarankan untuk menghapus atau mengganti nama berkas `setup.php` demi keamanan).*

---

## 🔑 Kredensial Uji Coba (Development Mode)

Aplikasi akan otomatis menginisialisasi akun percobaan berikut melalui proses *seeding*:

| Role | Email | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@MyPadel.com` | `password` | Mengelola Lapangan, User, & Verifikasi Transfer |
| **Kasir** | `kasir@MyPadel.com` | `password` | Konfirmasi Booking, Terima Cash, & Cetak Struk PDF |
| **Customer** | *(Daftar Mandiri)* | *(Bebas)* | Melihat Beranda, Melakukan Booking, & Upload Transfer |

> ⚠️ **Catatan Pengembangan:** Saat ini mekanisme hashing password dinonaktifkan (`plain-text`) untuk mempermudah dan mempercepat proses pengujian fungsionalitas login. Sebelum dipublikasikan ke production, pastikan untuk mengaktifkan kembali `password_hash()` dan `password_verify()` pada berkas `login.php` dan `register.php`.

---

## 📁 Struktur Direktori Project

```
padelmania/
├── admin/
│   └── dashboard.php         # Dashboard Admin (Kelola Booking, Lapangan, & User)
├── assets/
│   ├── style.css             # Desain CSS Premium global & dashboard
│   └── images/               # Media / Aset Gambar
├── config/
│   └── koneksi.php           # Koneksi DB, Migrasi Skema Otomatis, & Seeders
├── includes/
│   ├── header.php            # Navigasi Publik & Sidebar Dashboard Dinamis
│   └── footer.php            # Layout kaki halaman & interaksi script
├── kasir/
│   ├── dashboard.php         # Dashboard Kasir (Konfirmasi, Kasir Cash, & Profil)
│   └── generate_receipt.php  # Generator struk PDF pembayaran cash
├── uploads/
│   └── bukti_transfer/       # Direktori penyimpanan unggahan bukti transfer
├── vendor/                   # Paket dependensi composer (Dompdf, Sabberworm, dll.)
├── about.php                 # Halaman Informasi Tentang PadelClub
├── contact.php               # Halaman Kontak & Lokasi PadelClub
├── booking.php               # Form Booking tanggal & jam bermain
├── pilih_paket.php           # Pemilihan paket sewa (Per jam / Per match)
├── rincian_pembayaran.php    # Formulir upload transfer customer
├── dashboarduser.php         # Dashboard riwayat transaksi customer
├── login.php                 # Autentikasi Masuk Akun
├── register.php              # Registrasi Akun Customer
├── logout.php                # Sesi Keluar
├── setup.php                 # Skrip migrasi manual database
└── composer.json             # Konfigurasi dependensi project PHP
```

---

## 🛡️ Keamanan & Kualitas Kode

- **Prepared Statements:** Seluruh query dinamis yang menggunakan data input pengguna menggunakan *Prepared Statement* (`mysqli_prepare`) untuk mencegah kerentanan SQL Injection.
- **Validasi Input & Upload:** File upload bukti transfer dibatasi maksimal berukuran **2MB** dan hanya menerima ekstensi format `.jpg`, `.jpeg`, `.png`, dan `.pdf`.
- **Role-Based Access Control (RBAC):** Setiap halaman dilindungi dengan session middleware untuk mencegah akses paksa (force navigation) oleh user yang tidak memiliki otorisasi yang sesuai.
- **Standardisasi Imports:** Menggunakan path absolut berbasis `__DIR__` untuk menjamin stabilitas impor file konfigurasi di berbagai jenis server.
- **Intelephense Type Annotations:** Kode PHP dilengkapi dengan anotasi variabel bertipe `/** @var mysqli $conn */` untuk kejelasan analisis statis editor.

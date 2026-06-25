# MyPadel – Sistem Booking Lapangan Padel

Aplikasi web booking lapangan padel berbasis PHP Native & MySQL.

## Persyaratan

- PHP 7.4+ (atau PHP 8.x)
- MySQL 5.7+ / MariaDB
- Web server (XAMPP, Laragon, WAMP, atau Apache/Nginx)

## Cara Instalasi

### 1. Setup Database

1. Buka **phpMyAdmin** atau MySQL CLI
2. Jalankan file `database.sql`:
   ```sql
   SOURCE /path/to/MyPadel/database.sql;
   ```
   Atau import melalui phpMyAdmin: **Import → Pilih file `database.sql` → Go**

### 2. Konfigurasi Koneksi

Edit file `config/koneksi.php` sesuai konfigurasi database Anda:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // username MySQL
define('DB_PASS', '');           // password MySQL
define('DB_NAME', 'MyPadel');
```

### 3. Jalankan Aplikasi

- Tempatkan folder `MyPadel` di dalam `htdocs` (XAMPP) atau `www` (WAMP/Laragon)
- Akses via browser: `http://localhost/MyPadel/`

## Akun Default

| Role  | Email                    | Password   |
|-------|--------------------------|------------|
| Admin | admin@MyPadel.com     | password   |

> **Catatan:** Password default admin di seed data adalah `password`. Ubah setelah pertama login dengan mengganti hash di database.

## Struktur Folder

```
MyPadel/
├── config/
│   └── koneksi.php          # Konfigurasi database
├── includes/
│   ├── header.php           # Header HTML (navbar)
│   └── footer.php           # Footer HTML
├── assets/
│   └── style.css            # CSS global
├── uploads/
│   └── bukti_transfer/      # Upload bukti transfer
├── index.php                # Halaman beranda (daftar lapangan)
├── register.php             # Registrasi user
├── login.php                # Login
├── logout.php               # Logout
├── booking.php              # Form booking lapangan
├── pilih_paket.php          # Pilih paket & hitung harga
├── rincian_pembayaran.php   # Detail & upload bukti pembayaran
├── dashboarduser.php        # Dashboard customer
├── dashboardadmin.php       # Dashboard admin
└── database.sql             # Skema & data awal database
```

## Fitur

### Customer
- Registrasi & Login
- Lihat daftar lapangan (Indoor/Outdoor)
- Booking lapangan berdasarkan tanggal & jam
- Pilih paket (Per Jam / Per Match) + opsi sewa raket
- Upload bukti transfer
- Lihat riwayat & status booking
- Batalkan booking (status pending)

### Admin
- Dashboard statistik (total booking, pendapatan, dsb.)
- Kelola semua booking (ubah status)
- Verifikasi pembayaran customer
- Tambah/nonaktifkan lapangan
- Lihat daftar pengguna

## Logika Harga

| Paket      | Harga                               |
|------------|-------------------------------------|
| Per Jam    | harga_per_jam × durasi_jam          |
| Per Match  | Rp 250.000 (tetap)                  |
| Sewa Raket | +Rp 50.000                         |

## Keamanan

- Password di-hash dengan `password_hash()` (bcrypt)
- Verifikasi login dengan `password_verify()`
- Semua query menggunakan **Prepared Statement** mysqli
- Akses halaman dibatasi berdasarkan role (session)
- Upload file divalidasi (ekstensi & ukuran)

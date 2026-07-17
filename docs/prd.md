# Product Requirement Document (PRD)

# PadelClub Management System

Versi : 1.0

Status : Development

---

# 1. Pendahuluan

## Latar Belakang

PadelClub Management System merupakan aplikasi berbasis web yang dikembangkan untuk membantu proses pengelolaan penyewaan lapangan padel secara digital. Sistem ini menyediakan layanan booking online, pengelolaan pembayaran, dashboard administrasi, autentikasi pengguna, serta berbagai fitur pendukung untuk meningkatkan efisiensi operasional.

Sistem dikembangkan menggunakan PHP Native, MySQL, dan Composer dengan arsitektur modular sehingga mudah dikembangkan di masa mendatang.

---

# 2. Tujuan Pengembangan

Tujuan utama sistem adalah:

- Digitalisasi proses penyewaan lapangan.
- Mempermudah customer melakukan booking.
- Mempermudah admin mengelola transaksi.
- Menyediakan dashboard analitik.
- Menyediakan sistem pembayaran yang lebih modern.
- Menyediakan sistem autentikasi yang aman.
- Menyediakan sistem backup database.
- Menyediakan REST API untuk pengembangan selanjutnya.
- Menjadi aplikasi yang siap digunakan pada perangkat Desktop maupun Mobile.

---

# 3. Teknologi

Backend

- PHP Native
- PDO
- Composer
- MySQL

Frontend

- HTML5
- CSS3
- JavaScript
- Responsive Design
- Dark Mode

Library

- Google OAuth
- PHPMailer
- Endroid QR Code
- DomPDF
- PhpSpreadsheet
- Chart.js

---

# 4. Aktor Sistem

## Customer

Hak akses:

- Registrasi
- Login
- Login Google
- Booking Lapangan
- Upload Bukti Pembayaran
- Melihat Riwayat Booking
- Download Invoice
- Melihat QR Code Booking
- Edit Profil

---

## Admin

Hak akses:

- Dashboard
- Kelola Booking
- Kelola Customer
- Kelola Lapangan
- Verifikasi Pembayaran
- Backup Database
- Restore Database
- Export Data
- Melihat Statistik
- Mengelola User
- Check In Customer

---

## Kasir

Hak akses:

- Dashboard
- Verifikasi Pembayaran
- Melihat Booking Hari Ini
- Scan QR Customer
- Check In Customer

---

# 5. Modul Sistem

Modul yang akan dikembangkan:

- Authentication
- Dashboard
- Booking
- Payment
- QR Code
- Customer
- Court Management
- User Management
- Reporting
- Analytics
- Backup Database
- Export Data
- REST API
- Email Notification

---

# 6. Roadmap Pengembangan

## Tahap 1 (Selesai)

- Login Manual
- Registrasi
- Booking Lapangan
- Dashboard
- Upload Bukti Pembayaran
- Verifikasi Pembayaran
- Responsive Layout
- Dark Mode

Status:

✅ Selesai

---

## Tahap 2 (Sedang Dikembangkan)

- Google OAuth
- Backup Database
- Export Excel
- REST API
- Email Notification
- Reset Password
- Email Verification

Status:

🟡 Progress

---

## Tahap 3

- QR Code Digital Check-in
- Dashboard Analytics
- Scan QR Camera
- Backup Otomatis
- Restore Database

Status:

🔜 Planned

---

# 7. Fitur

---

# 7.1 Booking Lapangan

## Deskripsi

Customer dapat melakukan penyewaan lapangan secara online.

Alur:

Customer Login

↓

Pilih Lapangan

↓

Pilih Tanggal

↓

Pilih Jam

↓

Booking

↓

Status Pending

---

# 7.2 Pembayaran QRIS (Simulasi)

Customer melakukan pembayaran menggunakan QRIS Dummy.

Admin melakukan verifikasi.

Status berubah menjadi:

Pending

↓

Verified

---

# 7.3 Upload Bukti Pembayaran

Customer mengunggah bukti transfer.

Admin memverifikasi.

---

# 7.4 Google OAuth

Customer dapat login menggunakan akun Google.

---

# 7.5 QR Code Digital Check-In

## Deskripsi

Setiap booking yang telah diverifikasi akan memiliki QR Code.

QR berisi:

checkin.php?code=TOKEN_BOOKING

Bukan menggunakan ID.

Contoh:

BK9AX23KD8F4M2Q

---

Alur:

Booking

↓

Verified

↓

QR dibuat

↓

Customer membuka Riwayat Booking

↓

QR ditampilkan

↓

Petugas scan

↓

Check In

↓

Status berubah menjadi Checked In

---

Validasi

- Booking ditemukan
- Booking aktif
- Pembayaran Verified
- Belum Check In
- Belum Kadaluarsa

---

# 7.6 Dashboard Analytics

Dashboard Admin menampilkan:

- Booking Bulanan
- Pendapatan
- Lapangan Terfavorit
- Customer Baru
- Status Pembayaran
- Persentase Kehadiran

Menggunakan:

Chart.js

---

# 7.7 Backup Database

Admin dapat:

- Backup Database
- Download Backup
- Restore Backup
- Hapus Backup

Backup menghasilkan:

SQL

atau

ZIP

---

# 7.8 Export Data

Export:

Booking

Customer

Pembayaran

Lapangan

Format:

- PDF
- Excel
- CSV

---

# 7.9 REST API

Endpoint:

GET /api/booking

GET /api/customer

GET /api/court

GET /api/payment

Response:

JSON

---

# 7.10 Email Notification

Menggunakan PHPMailer.

Email dikirim saat:

- Registrasi
- Booking
- Pembayaran Diverifikasi
- Booking Ditolak
- Reset Password

---

# 7.11 Reset Password

Alur:

Klik Lupa Password

↓

Masukkan Email

↓

Email dikirim

↓

Klik Link

↓

Password Baru

---

# 7.12 Verifikasi Email

Saat registrasi.

User harus klik link verifikasi.

Status akun berubah menjadi aktif.

---

# 7.13 Dark Mode

Tema:

Light

Dark

Disimpan menggunakan LocalStorage.

---

# 7.14 Responsive

Mendukung:

Desktop

Laptop

Tablet

Mobile

---

# 8. Struktur Folder

Target struktur project:

controllers/

models/

views/

helpers/

config/

storage/

logs/

backup/

api/

assets/

docs/

---

# 9. Kebutuhan Non-Fungsional

Keamanan

- Password Hash
- PDO Prepared Statement
- Session Management
- Google OAuth
- Booking Token
- QR Token

Performa

- Responsive
- Lazy Loading bila diperlukan
- Query Optimal

Kompatibilitas

- Chrome
- Firefox
- Edge

---

# 10. Prioritas Pengembangan

Prioritas Tinggi

- Google OAuth
- Backup Database
- QR Code Check In
- Dashboard Analytics

Prioritas Menengah

- REST API
- Export Excel
- Email Notification

Prioritas Rendah

- Backup Otomatis
- Mobile App
- Push Notification

---

# 11. Checklist Implementasi

Authentication

- [x] Login
- [x] Register
- [x] Google OAuth
- [ ] Email Verification
- [ ] Reset Password
- [ ] Email Notification

Booking

- [x] Booking
- [x] Upload Bukti
- [x] Verifikasi
- [ ] QR Check In

Dashboard

- [x] Dashboard Admin
- [x] Dashboard Customer
- [x] Dashboard Kasir
- [x] Analytics

Database

- [x] Backup
- [x] Restore

Export

- [x] PDF
- [x] Excel
- [ ] CSV

API

- [ ] REST API

Email

- [ ] Booking
- [ ] Payment
- [ ] Reset Password

---

# 12. Aturan Pengembangan

Seluruh fitur baru wajib:

- Mengikuti struktur project yang sudah ada.
- Tidak mengubah fitur lama tanpa alasan yang jelas.
- Menggunakan PDO dan Prepared Statement.
- Menggunakan arsitektur modular (Controller, Model, Helper).
- Mendukung Dark Mode dan Light Mode.
- Responsif pada Desktop, Tablet, dan Mobile.
- Konsisten dengan desain UI/UX PadelClub.
- Memiliki validasi input dan penanganan error yang baik.
- Tidak merusak fitur yang sudah berjalan.

---

# 13. Catatan Pengembangan

Sebelum setiap implementasi fitur baru:

1. Analisis struktur project yang sudah ada.
2. Identifikasi file yang perlu dibuat.
3. Identifikasi file yang perlu dimodifikasi.
4. Jelaskan dampak perubahan terhadap sistem.
5. Implementasikan fitur dengan perubahan seminimal mungkin.
6. Lakukan pengujian regresi agar fitur lama tetap berfungsi.
7. Perbarui checklist implementasi pada dokumen ini setelah fitur selesai.
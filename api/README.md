# Dokumentasi REST API PadelClub (v1)

Dokumentasi resmi untuk REST API PadelClub Management System. API ini menyediakan endpoint untuk autentikasi, manajemen dan ketersediaan lapangan, serta pemesanan (booking) lapangan padel.

---

## 1. Informasi Umum

- **Base URL**: `http://localhost/PadelClub/api`
- **Versi API**: `v1` (Prefix path: `/api/v1/`)
- **Format Request Body**: `JSON` (`Content-Type: application/json; charset=utf-8`)
- **Format Response**: `JSON` (`Content-Type: application/json; charset=utf-8`)
- **Encoding**: UTF-8 (`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`)
- **CORS**: Diizinkan dari mana saja (`Access-Control-Allow-Origin: *`)
- **Preflight Check**: Method `OPTIONS` didukung dengan status HTTP `204 No Content`

---

## 2. Format Response Standar

Seluruh endpoint API mengembalikan struktur JSON yang konsisten yang dikelola oleh helper `ApiResponse.php`.

### Response Sukses
```json
{
  "success": true,
  "data": { ... },
  "message": "Pesan deskriptif sukses dalam Bahasa Indonesia"
}
```

### Response Gagal / Error
```json
{
  "success": false,
  "data": null,
  "message": "Pesan deskriptif kesalahan dalam Bahasa Indonesia"
}
```

---

## 3. Tabel Status Code HTTP

Berikut adalah seluruh HTTP status code yang digunakan secara eksplisit dalam kode API PadelClub:

| Status Code | Label | Deskripsi / Penggunaan dalam API |
| :--- | :--- | :--- |
| **200** | OK | Request berhasil diproses (misal: GET list, GET detail, POST login). |
| **201** | Created | Resource baru berhasil dibuat (POST booking baru). |
| **204** | No Content | Preflight request `OPTIONS` berhasil ditangani. |
| **400** | Bad Request | Parameter/input tidak valid, format salah, atau tanggal di masa lalu. |
| **401** | Unauthorized | Token tidak dikirim, format token salah, atau kredensial login keliru. |
| **403** | Forbidden | Akses ditolak karena role pengguna tidak diizinkan mengakses resource tersebut. |
| **404** | Not Found | Resource (lapangan/booking) dengan ID tertentu tidak ditemukan. |
| **405** | Method Not Allowed | Method HTTP tidak didukung pada endpoint tersebut (misal: POST ke `courts.php`). |
| **409** | Conflict | Bentrok jadwal booking (slot lapangan sudah terisi pada waktu tersebut). |
| **500** | Internal Server Error | Kesalahan koneksi database atau kegagalan internal server. |

---

## 4. Mekanisme Autentikasi

Autentikasi API berbasis **Bearer Token** yang dikelola oleh class `ApiAuth.php`. Token ini disimpan pada kolom `api_token` di tabel `users` dan **terpisah dari session web biasa**.

### A. Mendapatkan Token
Kirim request `POST` dengan kredensial email & password ke `/api/v1/auth.php`.

```bash
curl -X POST http://localhost/PadelClub/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "amba69@gmail.com",
    "password": "123456"
  }'
```

### B. Menggunakan Token
Sertakan token 64 karakter hex pada header HTTP `Authorization` untuk setiap request yang membutuhkan autentikasi:

```http
Authorization: Bearer <64_karakter_hex_token>
```

### C. Pembatalan Token Lama
> **Penting**: Setiap kali pengguna melakukan login ulang via `POST /api/v1/auth.php`, token baru akan digenerate dan meng-overwrite kolom `api_token` lama di database. Token lama secara otomatis menjadi invalid (401 Unauthorized).

---

## 5. Dokumentasi Endpoint API

---

### 5.1 Auth Endpoint (`/api/v1/auth.php`)

Digunakan untuk melakukan otentikasi akun pengguna dan mendapatkan Bearer Token.

- **HTTP Method**: `POST`
- **Autentikasi**: Tidak Perlu (Public)

#### Request Example (cURL)
```bash
curl -X POST http://localhost/PadelClub/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "amba69@gmail.com",
    "password": "123456"
  }'
```

#### Parameter Input (JSON Body)
| Field | Tipe Data | Wajib | Format | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `email` | String | Ya | Email valid | Alamat email terdaftar pengguna |
| `password` | String | Ya | Plain text | Password akun pengguna |

#### Response Sukses (200 OK)
```json
{
  "success": true,
  "data": {
    "token": "4f8a9e2b1c3d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a",
    "user": {
      "id": 5,
      "nama_lengkap": "amba",
      "email": "amba69@gmail.com",
      "role": "customer"
    }
  },
  "message": "Login berhasil. Gunakan token di header Authorization: Bearer <token>"
}
```

#### Response Gagal

##### 1. Method Not Allowed (405 Method Not Allowed)
```json
{
  "success": false,
  "data": null,
  "message": "Metode tidak diizinkan. Gunakan POST."
}
```

##### 2. JSON Body Tidak Valid (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Body request tidak valid. Kirim JSON dengan Content-Type: application/json."
}
```

##### 3. Field Kosong (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Email dan password wajib diisi."
}
```

##### 4. Format Email Invalid (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Format email tidak valid."
}
```

##### 5. Email atau Password Salah (401 Unauthorized)
```json
{
  "success": false,
  "data": null,
  "message": "Email atau password salah."
}
```

---

### 5.2 Courts Endpoint — List Lapangan (`GET /api/v1/courts.php`)

Mengambil daftar seluruh lapangan padel yang terdaftar di sistem.

- **HTTP Method**: `GET`
- **Autentikasi**: Wajib (`Bearer Token`)
- **Role yang Diizinkan**: `customer`, `kasir`, `admin`

#### Request Example (cURL)
```bash
curl -X GET http://localhost/PadelClub/api/v1/courts.php \
  -H "Authorization: Bearer 4f8a9e2b1c3d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a"
```

#### Parameter Query
*Tidak ada parameter query wajib.*

#### Response Sukses (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nama_lapangan": "Lapangan Padel Alpha (Indoor)",
      "tipe_lapangan": "Indoor Panoramic Glass",
      "harga_per_jam": 150000,
      "deskripsi": "Lapangan indoor berstandar internasional dengan pencahayaan LED premium.",
      "status": "aktif",
      "created_at": "2026-05-13 04:30:00"
    },
    {
      "id": 2,
      "nama_lapangan": "Lapangan Padel Beta (Outdoor)",
      "tipe_lapangan": "Outdoor Turf",
      "harga_per_jam": 120000,
      "deskripsi": "Lapangan outdoor dengan pemandangan terbuka dan rumput sintetis kelas 1.",
      "status": "aktif",
      "created_at": "2026-05-13 04:30:00"
    }
  ],
  "message": "Berhasil mengambil daftar lapangan. Total: 2 lapangan."
}
```

#### Response Gagal

##### 1. Token Tidak Dikirim / Invalid (401 Unauthorized)
```json
{
  "success": false,
  "data": null,
  "message": "Token tidak valid atau tidak ada. Silakan login terlebih dahulu via POST /api/v1/auth.php"
}
```

##### 2. Method Tidak Diizinkan (405 Method Not Allowed)
```json
{
  "success": false,
  "data": null,
  "message": "Metode POST tidak didukung. Gunakan GET."
}
```

---

### 5.3 Courts Endpoint — Cek Ketersediaan Slot (`GET /api/v1/courts.php?id={id}&tanggal={YYYY-MM-DD}`)

Mengecek status ketersediaan slot jam operasional (06:00 - 22:00) untuk lapangan tertentu pada tanggal spesifik.

- **HTTP Method**: `GET`
- **Autentikasi**: Wajib (`Bearer Token`)
- **Role yang Diizinkan**: `customer`, `kasir`, `admin`

#### Request Example (cURL)
```bash
curl -X GET "http://localhost/PadelClub/api/v1/courts.php?id=1&tanggal=2026-08-20" \
  -H "Authorization: Bearer 4f8a9e2b1c3d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a"
```

#### Parameter Query
| Parameter | Tipe Data | Wajib | Format | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `id` | Integer | Ya | Bilangan bulat > 0 | ID Lapangan yang ingin dicek |
| `tanggal` | String | Ya | `YYYY-MM-DD` | Tanggal reservasi |

#### Response Sukses (200 OK)
```json
{
  "success": true,
  "data": {
    "lapangan": {
      "id": 1,
      "nama_lapangan": "Lapangan Padel Alpha (Indoor)",
      "tipe_lapangan": "Indoor Panoramic Glass",
      "harga_per_jam": 150000,
      "status": "aktif"
    },
    "tanggal": "2026-08-20",
    "slots": [
      {
        "jam_mulai": "06:00",
        "jam_selesai": "07:00",
        "tersedia": true
      },
      {
        "jam_mulai": "08:00",
        "jam_selesai": "09:00",
        "tersedia": false
      },
      {
        "jam_mulai": "09:00",
        "jam_selesai": "10:00",
        "tersedia": false
      }
    ],
    "booked_ranges": [
      {
        "jam_mulai": "08:00",
        "jam_selesai": "10:00",
        "status": "confirmed"
      }
    ]
  },
  "message": "Berhasil mengambil data ketersediaan lapangan 'Lapangan Padel Alpha (Indoor)' pada tanggal 2026-08-20."
}
```

#### Response Gagal

##### 1. Parameter ID Invalid (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Parameter id tidak valid. Harus berupa bilangan bulat positif."
}
```

##### 2. Format Tanggal Invalid (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Format tanggal tidak valid. Gunakan format YYYY-MM-DD (contoh: 2026-07-20)."
}
```

##### 3. Lapangan Tidak Ditemukan (404 Not Found)
```json
{
  "success": false,
  "data": null,
  "message": "Lapangan dengan id 99 tidak ditemukan."
}
```

---

### 5.4 Bookings Endpoint — List Booking (`GET /api/v1/bookings.php`)

Mengambil daftar booking.
- Pengguna dengan role **`customer`** hanya melihat daftar booking miliknya sendiri (`user_id = token.user_id`).
- Pengguna dengan role **`admin`** atau **`kasir`** melihat seluruh booking dari semua pelanggan.

- **HTTP Method**: `GET`
- **Autentikasi**: Wajib (`Bearer Token`)
- **Role yang Diizinkan**: `customer`, `kasir`, `admin`

#### Request Example (cURL)
```bash
# Ambil semua booking milik sendiri (customer) atau seluruh booking (admin/kasir)
curl -X GET http://localhost/PadelClub/api/v1/bookings.php \
  -H "Authorization: Bearer 4f8a9e2b1c3d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a"

# Filter berdasarkan status
curl -X GET "http://localhost/PadelClub/api/v1/bookings.php?status=confirmed" \
  -H "Authorization: Bearer 4f8a9e2b1c3d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a"
```

#### Parameter Query (Opsional)
| Parameter | Tipe Data | Wajib | Format / Nilai | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `status` | String | Tidak | `pending`, `confirmed`, `cancelled` | Filter status pemesanan |

#### Response Sukses (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "user_id": 5,
      "court_id": 1,
      "nama_lapangan": "Lapangan Padel Alpha (Indoor)",
      "tipe_lapangan": "Indoor Panoramic Glass",
      "tanggal_booking": "2026-08-20",
      "jam_mulai": "08:00:00",
      "jam_selesai": "10:00:00",
      "total_harga": 300000,
      "paket": "reguler",
      "sewa_raket": 0,
      "status": "pending",
      "booking_code": "BK-20260820-0012",
      "payment_status": "Unpaid",
      "catatan": null,
      "created_at": "2026-07-19 14:30:00",
      "nama_pemesan": "amba",
      "email_pemesan": "amba69@gmail.com"
    }
  ],
  "message": "Berhasil mengambil daftar booking. Total: 1 data."
}
```

#### Response Gagal

##### Filter Status Invalid (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Filter status tidak valid. Nilai yang diterima: pending, confirmed, cancelled"
}
```

---

### 5.5 Bookings Endpoint — Detail Booking (`GET /api/v1/bookings.php?id={id}`)

Mengambil detail satu data booking berdasarkan ID.

- **HTTP Method**: `GET`
- **Autentikasi**: Wajib (`Bearer Token`)
- **Role yang Diizinkan**:
  - `customer` (Hanya untuk ID booking milik sendiri)
  - `kasir`, `admin` (Dapat melihat seluruh ID booking)

#### Request Example (cURL)
```bash
curl -X GET "http://localhost/PadelClub/api/v1/bookings.php?id=12" \
  -H "Authorization: Bearer 4f8a9e2b1c3d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a"
```

#### Parameter Query
| Parameter | Tipe Data | Wajib | Format | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `id` | Integer | Ya | Bilangan bulat > 0 | ID Booking yang ingin diambil |

#### Response Sukses (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 12,
    "user_id": 5,
    "court_id": 1,
    "nama_lapangan": "Lapangan Padel Alpha (Indoor)",
    "tipe_lapangan": "Indoor Panoramic Glass",
    "harga_per_jam": 150000,
    "tanggal_booking": "2026-08-20",
    "jam_mulai": "08:00:00",
    "jam_selesai": "10:00:00",
    "total_harga": 300000,
    "paket": "reguler",
    "sewa_raket": 0,
    "status": "pending",
    "booking_code": "BK-20260820-0012",
    "payment_status": "Unpaid",
    "checkin_status": "Unchecked",
    "catatan": null,
    "created_at": "2026-07-19 14:30:00",
    "nama_pemesan": "amba",
    "email_pemesan": "amba69@gmail.com",
    "nomor_telepon": "0893292183"
  },
  "message": "Berhasil mengambil detail booking."
}
```

#### Response Gagal

##### 1. Parameter ID Invalid (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Parameter id tidak valid. Harus berupa bilangan bulat positif."
}
```

##### 2. Booking Tidak Ditemukan (404 Not Found)
```json
{
  "success": false,
  "data": null,
  "message": "Booking dengan id 999 tidak ditemukan."
}
```

##### 3. Akses Ditolak — Booking Milik Orang Lain (403 Forbidden)
```json
{
  "success": false,
  "data": null,
  "message": "Anda hanya dapat melihat detail booking milik Anda sendiri."
}
```

---

### 5.6 Bookings Endpoint — Buat Booking Baru (`POST /api/v1/bookings.php`)

Membuat reservasi/booking lapangan padel baru. Total harga dihitung secara otomatis berdasarkan `harga_per_jam` lapangan dikali durasi jam booking.

- **HTTP Method**: `POST`
- **Autentikasi**: Wajib (`Bearer Token`)
- **Role yang Diizinkan**: `customer`, `kasir`, `admin`

#### Request Example (cURL)
```bash
curl -X POST http://localhost/PadelClub/api/v1/bookings.php \
  -H "Authorization: Bearer 4f8a9e2b1c3d5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a" \
  -H "Content-Type: application/json" \
  -d '{
    "court_id": 1,
    "tanggal": "2026-08-20",
    "jam_mulai": "08:00",
    "jam_selesai": "10:00"
  }'
```

#### Parameter Input (JSON Body)
| Field | Tipe Data | Wajib | Format | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `court_id` | Integer | Ya | Bilangan bulat > 0 | ID Lapangan yang ingin dibooking |
| `tanggal` | String | Ya | `YYYY-MM-DD` | Tanggal booking (tidak boleh di masa lalu) |
| `jam_mulai` | String | Ya | `HH:MM` (misal `08:00`) | Waktu mulai bermain |
| `jam_selesai` | String | Ya | `HH:MM` (misal `10:00`) | Waktu selesai bermain (harus > jam_mulai, min 1 jam) |

#### Response Sukses (201 Created)
```json
{
  "success": true,
  "data": {
    "id": 15,
    "user_id": 5,
    "court_id": 1,
    "nama_lapangan": "Lapangan Padel Alpha (Indoor)",
    "tipe_lapangan": "Indoor Panoramic Glass",
    "harga_per_jam": 150000,
    "tanggal_booking": "2026-08-20",
    "jam_mulai": "08:00:00",
    "jam_selesai": "10:00:00",
    "total_harga": 300000,
    "status": "pending",
    "booking_code": "BK-20260820-0015",
    "payment_status": "Unpaid",
    "created_at": "2026-07-19 15:00:00",
    "nama_pemesan": "amba",
    "email_pemesan": "amba69@gmail.com"
  },
  "message": "Booking berhasil dibuat dengan status 'pending'. Silakan lakukan pembayaran untuk konfirmasi."
}
```

#### Response Gagal

##### 1. Parameter Wajib Kosong (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "court_id wajib diisi dan harus bilangan bulat positif. | tanggal wajib diisi (format: YYYY-MM-DD)."
}
```

##### 2. Tanggal di Masa Lalu (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Tanggal booking tidak boleh di masa lalu."
}
```

##### 3. Waktu Selesai <= Waktu Mulai (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "jam_selesai harus lebih besar dari jam_mulai."
}
```

##### 4. Durasi Kurang dari 1 Jam (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Durasi booking minimal 1 jam."
}
```

##### 5. Lapangan Tidak Aktif (400 Bad Request)
```json
{
  "success": false,
  "data": null,
  "message": "Lapangan 'Lapangan Padel Alpha (Indoor)' sedang tidak aktif dan tidak dapat dibooking."
}
```

##### 6. Jadwal Bentrok / Overlap (409 Conflict)
```json
{
  "success": false,
  "data": null,
  "message": "Lapangan 'Lapangan Padel Alpha (Indoor)' sudah dibooking pada waktu tersebut. Pilih jam atau tanggal lain."
}
```

---

## 6. Tabel Matriks Hak Akses (RBAC)

Hak akses setiap endpoint diatur secara ketat berdasarkan method dan role pengguna terautentikasi:

| Endpoint | Method | Role `customer` | Role `kasir` | Role `admin` |
| :--- | :---: | :---: | :---: | :---: |
| `/api/v1/auth.php` | `POST` | Public | Public | Public |
| `/api/v1/courts.php` | `GET` | Ya | Ya | Ya |
| `/api/v1/courts.php?id={id}&tanggal={date}` | `GET` | Ya | Ya | Ya |
| `/api/v1/bookings.php` | `GET` | Ya *(Milik Sendiri)* | Ya *(Semua Data)* | Ya *(Semua Data)* |
| `/api/v1/bookings.php?id={id}` | `GET` | Ya *(Milik Sendiri)* | Ya *(Semua Data)* | Ya *(Semua Data)* |
| `/api/v1/bookings.php` | `POST` | Ya | Ya | Ya |

---

## 7. Akun Uji Coba (Testing Accounts)

Gunakan akun uji coba berikut yang tersedia di database `mypadel.sql`:

| Role | Email | Password | Kegunaan Uji Coba |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@padelmania.com` | `adminmania` | Tes akses seluruh data booking, ketersediaan lapangan, & admin privileges |
| **Kasir** | `kasir@padelclub.com` | `kasir123` | Tes akses kasir/petugas operasional |
| **Customer** | `amba69@gmail.com` | `123456` | Tes pembuatan booking baru & filtrasi booking milik sendiri |

---

## 8. Catatan Teknis & Keamanan

1. **Pembuatan Token Cryptographic**:
   API token dibuat menggunakan `bin2hex(random_bytes(32))`, menghasilkan 64 karakter heksadesimal yang aman secara kriptografis (*cryptographically secure pseudorandom*).
2. **Perlindungan Kolom Sensitif**:
   Informasi rahasia seperti hash `password` dan `api_token` dihapus (`unset()`) dari payload response JSON sebelum dikirim ke client.
3. **Penyaringan Prepared Statements (PDO)**:
   Seluruh query SQL pada API dijalankan menggunakan PDO Prepared Statements (`:param`) dengan `EMULATE_PREPARES => false` untuk mencegah celah **SQL Injection**.
4. **Isolasi Token API dari Web Session**:
   Token API disimpan pada kolom `api_token` di tabel `users`. Mekanisme ini berjalan independen dari session PHP web (`$_SESSION['user_id']`), sehingga penggunaan API tidak mengganggu alur login/logout di web admin/customer.

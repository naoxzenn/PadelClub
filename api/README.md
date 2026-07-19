# REST API PadelClub — Dokumentasi Endpoint

Dokumentasi lengkap untuk REST API sistem booking lapangan padel PadelClub.

---

## Informasi Umum

| Item | Nilai |
|------|-------|
| Base URL | `http://localhost/PadelClub/api` |
| Format Request | `application/json` |
| Format Response | `application/json` |
| Autentikasi | Bearer Token (header `Authorization: Bearer <token>`) |
| Encoding | UTF-8 |

### Format Response Standar

Semua endpoint mengembalikan format JSON yang konsisten:

```json
// Sukses
{
  "success": true,
  "data": { ... },
  "message": "Pesan sukses dalam Bahasa Indonesia"
}

// Gagal
{
  "success": false,
  "data": null,
  "message": "Pesan error dalam Bahasa Indonesia"
}
```

### HTTP Status Code

| Kode | Arti |
|------|------|
| 200 | OK — Request berhasil |
| 201 | Created — Data berhasil dibuat |
| 400 | Bad Request — Input tidak valid |
| 401 | Unauthorized — Token tidak ada atau tidak valid |
| 403 | Forbidden — Role tidak memiliki akses |
| 404 | Not Found — Data tidak ditemukan |
| 405 | Method Not Allowed — Metode HTTP tidak didukung |
| 409 | Conflict — Konflik data (misalnya: slot sudah terpakai) |
| 500 | Internal Server Error — Kesalahan pada server |

---

## Autentikasi

API menggunakan **Bearer Token** yang terpisah dari session login web.

### Cara Mendapatkan Token

```bash
curl -X POST http://localhost/PadelClub/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@MyPadel.com", "password": "password"}'
```

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "token": "a1b2c3d4e5f6...64karakter...",
    "user": {
      "id": 1,
      "nama_lengkap": "Administrator",
      "email": "admin@MyPadel.com",
      "role": "admin"
    }
  },
  "message": "Login berhasil. Gunakan token di header Authorization: Bearer <token>"
}
```

**Response Gagal (401):**
```json
{
  "success": false,
  "data": null,
  "message": "Email atau password salah."
}
```

### Cara Menggunakan Token

Sertakan token di setiap request menggunakan header `Authorization`:

```bash
curl http://localhost/PadelClub/api/v1/courts.php \
  -H "Authorization: Bearer a1b2c3d4e5f6...64karakter..."
```

> **Catatan:** Token baru akan digenerate setiap kali login. Token lama otomatis tidak berlaku.

---

## Endpoint: Lapangan (`courts.php`)

### GET — List Semua Lapangan

Mengembalikan daftar semua lapangan beserta status aktif/nonaktif.

**Request:**
```bash
curl http://localhost/PadelClub/api/v1/courts.php \
  -H "Authorization: Bearer <token>"
```

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nama_lapangan": "Lapangan A",
      "tipe_lapangan": "Indoor",
      "harga_per_jam": 150000,
      "deskripsi": "Lapangan indoor ber-AC dengan lantai vinyl premium",
      "status": "aktif",
      "created_at": "2026-05-13 04:33:07"
    },
    {
      "id": 3,
      "nama_lapangan": "Lapangan C",
      "tipe_lapangan": "Outdoor",
      "harga_per_jam": 100000,
      "deskripsi": "Lapangan outdoor dengan view taman yang indah",
      "status": "aktif",
      "created_at": "2026-05-13 04:33:07"
    }
  ],
  "message": "Berhasil mengambil daftar lapangan. Total: 4 lapangan."
}
```

---

### GET — Cek Ketersediaan Slot Lapangan

Mengembalikan slot per jam (06:00–22:00) yang tersedia dan sudah terpakai pada tanggal tertentu.

**Request:**
```bash
curl "http://localhost/PadelClub/api/v1/courts.php?id=1&tanggal=2026-07-25" \
  -H "Authorization: Bearer <token>"
```

**Query Parameters:**

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| `id` | integer | Ya | ID lapangan |
| `tanggal` | string | Ya | Format: `YYYY-MM-DD` |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "lapangan": {
      "id": 1,
      "nama_lapangan": "Lapangan A",
      "tipe_lapangan": "Indoor",
      "harga_per_jam": 150000,
      "status": "aktif"
    },
    "tanggal": "2026-07-25",
    "slots": [
      { "jam_mulai": "06:00", "jam_selesai": "07:00", "tersedia": true },
      { "jam_mulai": "07:00", "jam_selesai": "08:00", "tersedia": true },
      { "jam_mulai": "08:00", "jam_selesai": "09:00", "tersedia": false },
      { "jam_mulai": "09:00", "jam_selesai": "10:00", "tersedia": false }
    ],
    "booked_ranges": [
      {
        "jam_mulai": "08:00",
        "jam_selesai": "10:00",
        "status": "confirmed"
      }
    ]
  },
  "message": "Berhasil mengambil data ketersediaan lapangan 'Lapangan A' pada tanggal 2026-07-25."
}
```

**Response Gagal — Lapangan tidak ditemukan (404):**
```json
{
  "success": false,
  "data": null,
  "message": "Lapangan dengan id 99 tidak ditemukan."
}
```

---

## Endpoint: Booking (`bookings.php`)

### GET — List Booking

**Customer:** Hanya melihat booking milik sendiri.  
**Admin / Kasir:** Melihat semua booking.

Support filter opsional via query param `?status=`.

**Request:**
```bash
# Semua booking
curl http://localhost/PadelClub/api/v1/bookings.php \
  -H "Authorization: Bearer <token>"

# Filter hanya yang confirmed
curl "http://localhost/PadelClub/api/v1/bookings.php?status=confirmed" \
  -H "Authorization: Bearer <token>"
```

**Query Parameters:**

| Parameter | Tipe | Wajib | Nilai Valid | Deskripsi |
|-----------|------|-------|-------------|-----------|
| `status` | string | Tidak | `pending`, `confirmed`, `cancelled` | Filter status booking |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 9,
      "user_id": 4,
      "court_id": 2,
      "nama_lapangan": "Lapangan B",
      "tipe_lapangan": "Indoor",
      "tanggal_booking": "2026-06-29",
      "jam_mulai": "15:00:00",
      "jam_selesai": "19:00:00",
      "total_harga": "600000.00",
      "paket": "per_jam",
      "sewa_raket": "0",
      "status": "confirmed",
      "booking_code": "BKA1B2C3D4E5F6G7",
      "payment_status": "Verified",
      "catatan": null,
      "created_at": "2026-06-27 10:44:03",
      "nama_pemesan": "Almer",
      "email_pemesan": "Almer45@gmail.com"
    }
  ],
  "message": "Berhasil mengambil daftar booking. Total: 1 data."
}
```

---

### GET — Detail Satu Booking

**Request:**
```bash
curl "http://localhost/PadelClub/api/v1/bookings.php?id=9" \
  -H "Authorization: Bearer <token>"
```

**Query Parameters:**

| Parameter | Tipe | Wajib | Deskripsi |
|-----------|------|-------|-----------|
| `id` | integer | Ya | ID booking |

**Response Sukses (200):**
```json
{
  "success": true,
  "data": {
    "id": 9,
    "user_id": 4,
    "court_id": 2,
    "nama_lapangan": "Lapangan B",
    "tipe_lapangan": "Indoor",
    "tanggal_booking": "2026-06-29",
    "jam_mulai": "15:00:00",
    "jam_selesai": "19:00:00",
    "total_harga": "600000.00",
    "status": "confirmed",
    "booking_code": "BKA1B2C3D4E5F6G7",
    "nama_lengkap": "Almer",
    "email": "Almer45@gmail.com",
    "nomor_telepon": "082233009810"
  },
  "message": "Berhasil mengambil detail booking."
}
```

**Response Gagal — Booking tidak ditemukan (404):**
```json
{
  "success": false,
  "data": null,
  "message": "Booking dengan id 999 tidak ditemukan."
}
```

**Response Gagal — Customer akses booking orang lain (403):**
```json
{
  "success": false,
  "data": null,
  "message": "Anda hanya dapat melihat detail booking milik Anda sendiri."
}
```

---

### POST — Buat Booking Baru

Membuat booking baru dengan status `pending`. Validasi ketersediaan lapangan dilakukan otomatis.

**Request:**
```bash
curl -X POST http://localhost/PadelClub/api/v1/bookings.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "court_id": 1,
    "tanggal": "2026-08-15",
    "jam_mulai": "10:00",
    "jam_selesai": "12:00"
  }'
```

**Body JSON:**

| Field | Tipe | Wajib | Format | Deskripsi |
|-------|------|-------|--------|-----------|
| `court_id` | integer | Ya | Bilangan bulat positif | ID lapangan |
| `tanggal` | string | Ya | `YYYY-MM-DD` | Tanggal booking (tidak boleh masa lalu) |
| `jam_mulai` | string | Ya | `HH:MM` | Jam mulai (contoh: `08:00`) |
| `jam_selesai` | string | Ya | `HH:MM` | Jam selesai — harus > jam_mulai, minimal selisih 1 jam |

**Response Sukses (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 10,
    "user_id": 3,
    "court_id": 1,
    "nama_lapangan": "Lapangan A",
    "tipe_lapangan": "Indoor",
    "tanggal_booking": "2026-08-15",
    "jam_mulai": "10:00:00",
    "jam_selesai": "12:00:00",
    "total_harga": "300000.00",
    "status": "pending",
    "booking_code": null,
    "nama_lengkap": "Arga Putra",
    "email": "argagamteng@gmail.com"
  },
  "message": "Booking berhasil dibuat dengan status 'pending'. Silakan lakukan pembayaran untuk konfirmasi."
}
```

**Response Gagal — Slot sudah terpakai (409 Conflict):**
```json
{
  "success": false,
  "data": null,
  "message": "Lapangan 'Lapangan A' sudah dibooking pada waktu tersebut. Pilih jam atau tanggal lain."
}
```

**Response Gagal — Validasi input (400):**
```json
{
  "success": false,
  "data": null,
  "message": "jam_selesai harus lebih besar dari jam_mulai."
}
```

---

## Error Autentikasi

**Token tidak ada (401):**
```json
{
  "success": false,
  "data": null,
  "message": "Token tidak valid atau tidak ada. Silakan login terlebih dahulu via POST /api/v1/auth.php"
}
```

**Token tidak valid (401):**
```json
{
  "success": false,
  "data": null,
  "message": "Token tidak valid atau tidak ada. Silakan login terlebih dahulu via POST /api/v1/auth.php"
}
```

---

## Daftar Role & Hak Akses

| Endpoint | customer | kasir | admin |
|----------|----------|-------|-------|
| POST `/api/v1/auth.php` | ✅ | ✅ | ✅ |
| GET `/api/v1/courts.php` | ✅ | ✅ | ✅ |
| GET `/api/v1/courts.php?id=&tanggal=` | ✅ | ✅ | ✅ |
| GET `/api/v1/bookings.php` | ✅ (milik sendiri) | ✅ (semua) | ✅ (semua) |
| GET `/api/v1/bookings.php?id=` | ✅ (milik sendiri) | ✅ (semua) | ✅ (semua) |
| POST `/api/v1/bookings.php` | ✅ | ✅ | ✅ |

---

## Akun Test

| Email | Password | Role |
|-------|----------|------|
| `admin@MyPadel.com` | `password` | admin |
| `kasir@MyPadel.com` | `password` | kasir |

> **Catatan:** Password akun test di atas adalah plain text (mode development). Akun real pengguna menggunakan bcrypt hash.

---

## Catatan Teknis

- **Token** digenerate dengan `bin2hex(random_bytes(32))` — 64 karakter hex, cryptographically secure.
- **Token baru** digenerate setiap kali login — token lama otomatis tidak berlaku.
- **Session web** tidak terpengaruh — API token dan session web benar-benar terpisah.
- **Semua query** menggunakan PDO prepared statement dengan named parameter — aman dari SQL Injection.
- **Field sensitif** (`password`, `api_token`) tidak pernah muncul di response JSON manapun.

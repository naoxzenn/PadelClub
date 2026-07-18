<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'kasir' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';
/** @var mysqli $conn */

use Dompdf\Dompdf;
use Dompdf\Options;

$booking_id = (int) ($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    die("Booking ID tidak valid.");
}

// Ambil data lengkap booking + pembayaran
$stmt = mysqli_prepare($conn, "
    SELECT b.*, c.nama_lapangan, c.tipe_lapangan, c.harga_per_jam,
           u.nama_lengkap, u.email, u.nomor_telepon,
           p.receipt_number, p.jumlah_bayar, p.payment_date, p.metode_bayar,
           k.nama_lengkap AS nama_kasir
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    JOIN users u ON b.user_id = u.id
    JOIN payments p ON p.booking_id = b.id
    LEFT JOIN users k ON p.cashier_id = k.id
    WHERE b.id = ? AND p.payment_status = 'paid'
");
if (!$stmt) {
    die("Query error: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, 'i', $booking_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    die("Data pembayaran lunas untuk Booking ID #$booking_id tidak ditemukan.");
}

// Tandai struk telah dicetak
mysqli_query($conn, "UPDATE payments SET receipt_printed=1 WHERE booking_id=$booking_id");

// Render HTML struk
ob_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran PadelClub #<?= htmlspecialchars($data['receipt_number']) ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .receipt-card {
            max-width: 450px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 25px;
            border-radius: 8px;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #ddd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 22px;
            margin: 0;
            color: #1e293b;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 11px;
        }

        .title {
            text-align: center;
            margin-bottom: 20px;
        }

        .title h2 {
            font-size: 14px;
            margin: 0;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 12px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .info-table td.label {
            color: #64748b;
            width: 35%;
        }

        .info-table td.value {
            color: #1e293b;
            font-weight: bold;
        }

        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 15px 0;
        }

        .booking-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .booking-details th {
            text-align: left;
            padding: 8px;
            background-color: #f8fafc;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }

        .booking-details td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .summary-table td {
            padding: 6px 8px;
            font-size: 12px;
        }

        .summary-table td.total-label {
            text-align: right;
            width: 60%;
            font-weight: bold;
            color: #1e293b;
        }

        .summary-table td.total-value {
            text-align: right;
            font-weight: 800;
            font-size: 15px;
            color: #10b981;
        }

        .status-stamp {
            display: inline-block;
            padding: 4px 12px;
            border: 2px solid #10b981;
            color: #10b981;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
            font-size: 12px;
            margin-top: 15px;
            letter-spacing: 1px;
        }

        .footer {
            text-align: center;
            border-top: 2px dashed #ddd;
            padding-top: 15px;
            margin-top: 25px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="receipt-card">
        <div class="header">
            <h1>PadelClub</h1>
            <p>Telp: +62 822-3300-9810 | email: padelclub4@gmail.com</p>
        </div>

        <div class="title">
            <h2>Struk Pembayaran Resmi</h2>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Nomor Struk</td>
                <td class="value"><strong><?= htmlspecialchars($data['receipt_number']) ?></strong></td>
            </tr>
            <tr>
                <td class="label">Tanggal Bayar</td>
                <td class="value"><?= date('d M Y H:i', strtotime($data['payment_date'])) ?> WIB</td>
            </tr>
            <tr>
                <td class="label">Kasir Pemroses</td>
                <td class="value"><?= htmlspecialchars($data['nama_kasir'] ?? 'Online') ?></td>
            </tr>
            <tr>
                <td class="label">Nama Customer</td>
                <td class="value"><?= htmlspecialchars($data['nama_lengkap']) ?>
                    (<?= htmlspecialchars($data['nomor_telepon'] ?? '-') ?>)</td>
            </tr>
        </table>

        <div class="divider"></div>

        <h3 style="font-size: 12px; color: #1e293b; margin: 0 0 10px;">Rincian Booking</h3>
        <table class="booking-details">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: right;">Durasi / Harga</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($data['nama_lapangan']) ?></strong>
                        (<?= htmlspecialchars($data['tipe_lapangan']) ?>)<br>
                        <span
                            style="font-size: 10px; color: #64748b;"><?= date('d/m/Y', strtotime($data['tanggal_booking'])) ?>
                            | <?= substr($data['jam_mulai'], 0, 5) ?> - <?= substr($data['jam_selesai'], 0, 5) ?></span>
                    </td>
                    <?php
                    $mulai = new DateTime($data['jam_mulai']);
                    $selesai = new DateTime($data['jam_selesai']);
                    $diff = $mulai->diff($selesai);
                    $durasi = $diff->h + ($diff->i / 60);
                    ?>
                    <td style="text-align: right; white-space: nowrap;"><?= $durasi ?> jam x Rp
                        <?= number_format($data['harga_per_jam'], 0, ',', '.') ?></td>
                    <td style="text-align: right;">Rp <?= number_format($data['total_harga'], 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="total-label">Subtotal</td>
                <td style="text-align: right; color: #1e293b;">Rp
                    <?= number_format($data['total_harga'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td class="total-label">Total Bayar (CASH)</td>
                <td class="total-value">Rp <?= number_format($data['jumlah_bayar'], 0, ',', '.') ?></td>
            </tr>
        </table>

        <div style="text-align: center;">
            <span class="status-stamp">Lunas (Paid)</span>
        </div>

        <div class="footer">
            <p>Terima kasih atas pembayaran Anda!</p>
            <p style="font-weight: bold; color: #3b82f6;">Selamat Bermain di PadelClub!</p>
            <p style="font-size: 9px; margin-top: 10px; color: #cbd5e1;">Struk ini diterbitkan secara elektronik dan sah
                sebagai bukti pembayaran.</p>
        </div>
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

// Setup Dompdf options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Render PDF (A5 portrait format or letter format)
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();

// Output streamed PDF
$dompdf->stream($data['receipt_number'] . '.pdf', ["Attachment" => false]);
exit;
?>
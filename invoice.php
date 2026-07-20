<?php
// invoice.php - Generate PDF Invoice with QR Code using Dompdf

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/models/BookingModel.php';
require_once __DIR__ . '/helpers/QRHelper.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$param = $_GET['t'] ?? $_GET['code'] ?? '';

if (empty($param)) {
    die("Kode booking tidak valid.");
}

$model = new BookingModel($pdo);
$booking = $model->getBookingByCheckinToken($param);
if (!$booking) {
    $booking = $model->getBookingByCode($param);
}

if (!$booking) {
    die("Booking tidak ditemukan.");
}

$code = $booking['booking_code'] ?? $param;

// Security Validation
if ($_SESSION['role'] === 'customer' && $booking['user_id'] != $_SESSION['user_id']) {
    die("Akses ditolak. Anda tidak memiliki izin untuk mengunduh invoice ini.");
}

if ($booking['payment_status'] !== 'Verified') {
    die("Invoice belum tersedia karena pembayaran belum diverifikasi.");
}

// Fetch payment method from database
$stmtPay = $pdo->prepare("SELECT metode_bayar FROM payments WHERE booking_id = :booking_id LIMIT 1");
$stmtPay->execute([':booking_id' => $booking['id']]);
$payInfo = $stmtPay->fetch();
$metodeBayar = $payInfo ? $payInfo['metode_bayar'] : 'Transfer Bank';

$displayMetode = 'Transfer Bank';
if ($metodeBayar === 'QRIS') {
    $displayMetode = 'QRIS';
} elseif ($metodeBayar === 'Cash') {
    $displayMetode = 'Cash / Tunai';
}


// Generate base64 QR Code using checkin_token
$token = getOrCreateCheckinToken($booking, $pdo);
$checkinUrl = QRHelper::generateCheckinUrl($token);
$qrDataUri = QRHelper::generateQRCodeDataUri($checkinUrl);

// Render HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice PadelClub - <?= htmlspecialchars($code) ?></title>
    <style>
        html, body {
            max-width: 100vw;
            overflow-x: hidden;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .invoice-box {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
        }

        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
        }

        .header-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #0EA5E9;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }

        .company-info {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: 800;
            color: #0F172A;
            text-transform: uppercase;
            margin: 0;
        }

        .invoice-code {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
            color: #0ea5e9;
            margin-top: 4px;
        }

        .details {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .details td {
            padding: 6px 0;
            vertical-align: top;
            font-size: 12px;
        }

        .details td.label {
            color: #64748b;
            width: 30%;
        }

        .details td.value {
            font-weight: bold;
            color: #0F172A;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #f8fafc;
            color: #64748b;
            padding: 10px 12px;
            font-weight: 600;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
        }

        .summary-container {
            display: table;
            width: 100%;
            margin-top: 15px;
        }

        .summary-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .summary-right {
            display: table-cell;
            width: 40%;
            text-align: right;
            vertical-align: top;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 5px 8px;
            font-size: 12px;
        }

        .summary-table td.total-lbl {
            font-weight: bold;
            text-align: right;
        }

        .summary-table td.total-val {
            font-weight: 800;
            font-size: 15px;
            text-align: right;
            color: #0ea5e9;
        }

        .stamp-lunas {
            display: inline-block;
            border: 3px solid #22c55e;
            color: #22c55e;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 14px;
            letter-spacing: 1px;
            transform: rotate(-5deg);
            margin-top: 10px;
        }

        .qr-section {
            text-align: center;
            border-top: 2px dashed #e2e8f0;
            padding-top: 25px;
            margin-top: 30px;
        }

        .qr-img {
            width: 140px;
            height: 140px;
            background: #fff;
            padding: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            display: inline-block;
        }

        .qr-caption {
            font-size: 11px;
            color: #64748b;
            margin-top: 8px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 30px;
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <div class="header">
            <div class="header-left">
                <div class="logo">PadelClub</div>
                <div class="company-info">
                    Telp: +62 812-3456-7890 | Email: padelclub4.gmail.com
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">Invoice Resmi</div>
                <div class="invoice-code"><?= htmlspecialchars($code) ?></div>
            </div>
        </div>

        <table class="details">
            <tr>
                <td class="label">Nama Pemesan</td>
                <td class="value"><?= htmlspecialchars($booking['nama_lengkap']) ?></td>
            </tr>
            <tr>
                <td class="label">Email / Telp</td>
                <td class="value"><?= htmlspecialchars($booking['email']) ?> /
                    <?= htmlspecialchars($booking['nomor_telepon'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Cetak</td>
                <td class="value"><?= date('d F Y H:i') ?> WIB</td>
            </tr>
            <tr>
                <td class="label">Metode Pembayaran</td>
                <td class="value"><?= htmlspecialchars($displayMetode) ?> (Verified)</td>
            </tr>
        </table>

        <div class="table-responsive">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Rincian Item</th>
                        <th>Tipe</th>
                        <th style="text-align: right;">Biaya</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Sewa Lapangan: <?= htmlspecialchars($booking['nama_lapangan']) ?></strong><br>
                            <small>Tanggal: <?= date('d/m/Y', strtotime($booking['tanggal_booking'])) ?> | Jam:
                                <?= substr($booking['jam_mulai'], 0, 5) ?> - <?= substr($booking['jam_selesai'], 0, 5) ?>
                                WIB</small>
                        </td>
                        <td><?= $booking['tipe_lapangan'] ?></td>
                        <td style="text-align: right;">Rp
                            <?= number_format($booking['total_harga'] - ($booking['sewa_raket'] ? 50000 : 0), 0, ',', '.') ?>
                        </td>
                    </tr>
                    <?php if ($booking['sewa_raket']): ?>
                        <tr>
                            <td><strong>Sewa Raket Tambahan</strong><br><small>1 Set Raket Padel Premium</small></td>
                            <td>Tambahan</td>
                            <td style="text-align: right;">Rp 50.000</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="summary-container">
            <div class="summary-left">
                <div class="stamp-lunas">Lunas / Verified</div>
            </div>
            <div class="summary-right">
                <table class="summary-table">
                    <tr>
                        <td class="total-lbl">Subtotal:</td>
                        <td style="text-align: right;">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="total-lbl">Total Pembayaran:</td>
                        <td class="total-val">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="qr-section">
            <img class="qr-img" src="<?= $qrDataUri ?>" alt="Check-in QR Code">
            <div class="qr-caption">Scan QR Code di atas menggunakan kamera petugas di lokasi untuk Check-in bermain.
            </div>
        </div>

        <div class="footer">
            Terima kasih telah berolahraga di PadelClub. Invoice ini sah diterbitkan secara elektronik.
        </div>
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

// Setup Dompdf options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // enable image loading
$options->set('defaultFont', 'sans-serif');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output streaming
$dompdf->stream('invoice-' . $code . '.pdf', ["Attachment" => false]);
exit;

<?php
// QRHelper v6 API Test - run from project root
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_HOST'] = 'localhost';

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/QRHelper.php';

echo '=== QRHelper v6 API Test ===' . PHP_EOL;

$url = QRHelper::generateCheckinUrl('BKTEST123');
echo 'URL: ' . $url . PHP_EOL;

$dataUri = QRHelper::generateQRCodeDataUri($url);
echo 'DataUri: ' . (str_starts_with($dataUri, 'data:image/png;base64,') ? 'OK (len=' . strlen($dataUri) . ')' : 'FAIL: ' . substr($dataUri, 0, 80)) . PHP_EOL;

$bytes = QRHelper::generateQRCodeBytes($url);
echo 'Bytes: ' . (strlen($bytes) > 100 ? 'OK (len=' . strlen($bytes) . ')' : 'FAIL') . PHP_EOL;

$b64 = QRHelper::generateQRCodeBase64($url);
echo 'Base64: ' . (strlen($b64) > 100 ? 'OK (len=' . strlen($b64) . ')' : 'FAIL') . PHP_EOL;

$inv = QRHelper::generateQRCodeForInvoice($url);
echo 'Invoice DataUri: ' . (str_starts_with($inv, 'data:image/png;base64,') ? 'OK' : 'FAIL') . PHP_EOL;

$img = QRHelper::renderQRCodeImg($url, 200);
echo 'ImgTag: ' . (str_contains($img, '<img') ? 'OK' : 'FAIL') . PHP_EOL;

$empty = QRHelper::generateQRCodeDataUri('');
echo 'EmptyGuard: ' . ($empty === '' ? 'OK (returns empty string)' : 'FAIL') . PHP_EOL;

$svg = QRHelper::generateQRCodeSvg($url);
echo 'SVG: ' . (str_contains($svg, '<svg') ? 'OK (len=' . strlen($svg) . ')' : 'FAIL: ' . substr($svg, 0, 80)) . PHP_EOL;

echo PHP_EOL . '=== All tests complete ===' . PHP_EOL;

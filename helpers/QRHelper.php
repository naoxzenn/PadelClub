<?php
// helpers/QRHelper.php

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QRHelper
{
    /**
     * Check if application is running in APP_DEBUG mode
     * @return bool
     */
    private static function isDebugMode(): bool
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            return true;
        }
        if (!empty($_ENV['APP_DEBUG']) && strtolower((string)$_ENV['APP_DEBUG']) !== 'false' && $_ENV['APP_DEBUG'] !== '0') {
            return true;
        }
        if (!empty($_SERVER['APP_DEBUG']) && strtolower((string)$_SERVER['APP_DEBUG']) !== 'false' && $_SERVER['APP_DEBUG'] !== '0') {
            return true;
        }
        return false;
    }

    /**
     * Generate Check-in URL using checkin_token
     * @param string $token
     * @return string
     */
    public static function generateCheckinUrl($token)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host . '/PadelClub/checkin.php?t=' . urlencode($token);
    }

    /**
     * Generate QR Data URI using Endroid QR Code v6.0.9
     * @param string $text
     * @param int $size
     * @param int $margin
     * @return string
     */
    public static function generateQRCodeDataUri($text, int $size = 220, int $margin = 10)
    {
        if (empty($text)) {
            error_log("QRHelper Error: Payload text is empty.");
            if (self::isDebugMode()) {
                throw new \InvalidArgumentException("QR Code payload text cannot be empty.");
            }
            return '';
        }

        try {
            $qrCode = new QrCode(
                data: $text,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: $size,
                margin: $margin
            );

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            return $result->getDataUri();

        } catch (\Throwable $e) {
            error_log("QRHelper DataURI Exception: " . $e->getMessage());

            if (self::isDebugMode()) {
                throw $e;
            }

            return '';
        }
    }

    /**
     * Generate PNG Bytes using Endroid QR Code v6.0.9
     * @param string $text
     * @param int $size
     * @param int $margin
     * @return string
     */
    public static function generateQRCodeBytes($text, int $size = 300, int $margin = 15)
    {
        if (empty($text)) {
            error_log("QRHelper Error: Payload text is empty.");
            if (self::isDebugMode()) {
                throw new \InvalidArgumentException("QR Code payload text cannot be empty.");
            }
            return '';
        }

        try {
            $qrCode = new QrCode(
                data: $text,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: $size,
                margin: $margin
            );

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            return $result->getString();

        } catch (\Throwable $e) {
            error_log("QRHelper Bytes Exception: " . $e->getMessage());

            if (self::isDebugMode()) {
                throw $e;
            }

            return '';
        }
    }
}
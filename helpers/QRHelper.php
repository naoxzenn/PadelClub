<?php
// helpers/QRHelper.php

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\ErrorCorrectionLevel;

class QRHelper {
    /**
     * Build check-in URL for QR code
     * @param string $booking_code
     * @return string
     */
    public static function generateCheckinUrl($booking_code) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . "://" . $host . "/PadelClub/checkin.php?code=" . $booking_code;
    }

    /**
     * Generate QR Code as Base64 Data URI
     * @param string $text
     * @return string
     */
    public static function generateQRCodeDataUri($text) {
        try {
            $qrCode = QrCode::create($text)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->setSize(200)
                ->setMargin(10)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            return $result->getDataUri();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Generate QR Code raw PNG bytes
     * @param string $text
     * @return string
     */
    public static function generateQRCodeBytes($text) {
        try {
            $qrCode = QrCode::create($text)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->setSize(300)
                ->setMargin(15)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            return $result->getString();
        } catch (\Exception $e) {
            return '';
        }
    }
}

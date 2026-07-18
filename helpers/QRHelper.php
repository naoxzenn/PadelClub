<?php
// helpers/QRHelper.php

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class QRHelper
{
    /**
     * Generate Check-in URL
     */
    public static function generateCheckinUrl($booking_code)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https'
            : 'http';

        $host = $_SERVER['HTTP_HOST'];

        return $protocol . '://' . $host . '/PadelClub/checkin.php?code=' . urlencode($booking_code);
    }

    /**
     * Generate QR Data URI
     */
    public static function generateQRCodeDataUri($text)
    {
        try {

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($text)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->size(220)
                ->margin(10)
                ->build();

            return $result->getDataUri();

        } catch (\Throwable $e) {

            error_log($e->getMessage());

            return '';

        }
    }

    /**
     * Generate PNG Bytes
     */
    public static function generateQRCodeBytes($text)
    {
        try {

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($text)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->size(300)
                ->margin(15)
                ->build();

            return $result->getString();

        } catch (\Throwable $e) {

            error_log($e->getMessage());

            return '';

        }
    }
}
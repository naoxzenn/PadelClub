<?php
// helpers/BackupHelper.php

class BackupHelper {
    /**
     * Generate automatic backup filename based on current time
     * @param string $extension
     * @return string
     */
    public static function generateFilename($extension = 'sql') {
        date_default_timezone_set('Asia/Jakarta');
        return 'backup_' . date('Y-m-d_H-i') . '.' . $extension;
    }

    /**
     * Format file size in bytes to human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatSize($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Check if directory is writable and calculate available space
     * @param string $dir
     * @return array
     */
    public static function checkStorage($dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return ['status' => false, 'message' => "Folder backup tidak ditemukan dan gagal dibuat."];
            }
        }

        if (!is_writable($dir)) {
            return ['status' => false, 'message' => "Folder backup tidak memiliki izin menulis (write permission)."];
        }

        $freeSpace = disk_free_space($dir);
        if ($freeSpace === false) {
            $freeSpace = 100 * 1024 * 1024; // Mock fallback space (100MB) if PHP is restricted
        }

        return [
            'status' => true,
            'free_space' => $freeSpace,
            'free_space_formatted' => self::formatSize($freeSpace)
        ];
    }

    /**
     * Create a ZIP file containing the SQL dump
     * @param string $sqlPath
     * @param string $zipPath
     * @return bool
     */
    public static function createZip($sqlPath, $zipPath) {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($sqlPath, basename($sqlPath));
            $zip->close();
            return true;
        }

        return false;
    }

    /**
     * Delete file from filesystem safely
     * @param string $filePath
     * @return bool
     */
    public static function deleteFile($filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Extract browser name from User Agent string
     * @param string $userAgent
     * @return string
     */
    public static function getBrowserName($userAgent) {
        if (empty($userAgent)) {
            return 'Unknown Browser';
        }
        
        $browser = "Unknown Browser";
        $browsers = [
            '/msie/i'      => 'Internet Explorer',
            '/firefox/i'   => 'Firefox',
            '/safari/i'    => 'Safari',
            '/chrome/i'    => 'Chrome',
            '/edge/i'      => 'Edge',
            '/opera/i'     => 'Opera',
            '/netscape/i'  => 'Netscape',
            '/maxthon/i'   => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i'    => 'Mobile Browser'
        ];

        foreach ($browsers as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $browser = $value;
            }
        }
        
        return $browser;
    }
}

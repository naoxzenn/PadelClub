<?php
// cron_backup.php
// Cron entrypoint for background database backup automation

// Enforce CLI access only
if (php_sapi_name() !== 'cli') {
    header("HTTP/1.1 403 Forbidden");
    echo "Akses ditolak. Script ini hanya dapat dijalankan melalui Command Line Interface (CLI).";
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/helpers/BackupHelper.php';
require_once __DIR__ . '/models/BackupModel.php';
require_once __DIR__ . '/controllers/BackupController.php';

$controller = new BackupController();
$settings = $controller->getSettings();

if ($settings['auto_backup'] === 'off') {
    echo "[" . date('Y-m-d H:i:s') . "] Auto backup dinonaktifkan.\n";
    exit;
}

$lastRun = $settings['last_run'];
$shouldBackup = false;

if (!$lastRun) {
    $shouldBackup = true;
} else {
    $lastTimestamp = strtotime($lastRun);
    $diffSeconds = time() - $lastTimestamp;
    
    switch ($settings['auto_backup']) {
        case 'daily':
            // Check if last run was on a different calendar day
            if (date('Ymd', $lastTimestamp) !== date('Ymd')) {
                $shouldBackup = true;
            }
            break;
        case 'weekly':
            // Check if 7 days (604800 seconds) have passed
            if ($diffSeconds >= 604800) {
                $shouldBackup = true;
            }
            break;
        case 'monthly':
            // Check if 30 days (2592000 seconds) have passed
            if ($diffSeconds >= 2592000) {
                $shouldBackup = true;
            }
            break;
    }
}

if ($shouldBackup) {
    echo "[" . date('Y-m-d H:i:s') . "] Memulai auto backup otomatis (" . $settings['auto_backup'] . ")...\n";
    
    $res = $controller->createBackup('Sistem (Cron Job)');
    
    if ($res['status']) {
        $settings['last_run'] = date('Y-m-d H:i:s');
        $controller->saveSettings($settings);
        echo "[" . date('Y-m-d H:i:s') . "] Auto backup sukses: " . $res['filename'] . " (" . $res['filesize'] . ")\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Auto backup gagal: " . $res['message'] . "\n";
    }
} else {
    echo "[" . date('Y-m-d H:i:s') . "] Belum saatnya menjalankan backup otomatis. Terakhir berjalan: " . ($lastRun ?? 'Never') . "\n";
}

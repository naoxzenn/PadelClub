<?php
// models/BackupModel.php

class BackupModel {
    private $pdo;

    public function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new Exception("Koneksi PDO Gagal: " . $e->getMessage());
        }
    }

    public function getTables() {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getTableStructure($table) {
        $stmt = $this->pdo->query("SHOW CREATE TABLE " . $table);
        $row = $stmt->fetch();
        return $row['Create Table'] ?? '';
    }

    public function getTableData($table) {
        $stmt = $this->pdo->query("SELECT * FROM " . $table);
        return $stmt->fetchAll();
    }

    public function executeQuery($sql) {
        return $this->pdo->exec($sql);
    }

    public function logBackup($filename, $filesize, $createdBy, $status, $ipAddress, $browser, $note = '') {
        $stmt = $this->pdo->prepare("INSERT INTO backup_logs (filename, filesize, created_by, status, ip_address, browser, note) VALUES (:filename, :filesize, :created_by, :status, :ip_address, :browser, :note)");
        return $stmt->execute([
            ':filename' => $filename,
            ':filesize' => $filesize,
            ':created_by' => $createdBy,
            ':status' => $status,
            ':ip_address' => $ipAddress,
            ':browser' => $browser,
            ':note' => $note
        ]);
    }

    public function getBackupHistory($search = '', $filter = '', $offset = 0, $limit = 10) {
        $sql = "SELECT * FROM backup_logs WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (filename LIKE :search OR created_by LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($filter)) {
            if ($filter === 'today') {
                $sql .= " AND DATE(created_at) = CURDATE()";
            } elseif ($filter === '7days') {
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($filter === '30days') {
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Bind parameters
        if (!empty($search)) {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLogsCount($search = '', $filter = '') {
        $sql = "SELECT COUNT(*) FROM backup_logs WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (filename LIKE :search OR created_by LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($filter)) {
            if ($filter === 'today') {
                $sql .= " AND DATE(created_at) = CURDATE()";
            } elseif ($filter === '7days') {
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($filter === '30days') {
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getBackupStats() {
        // Total count (success only)
        $totalCount = (int)$this->pdo->query("SELECT COUNT(*) FROM backup_logs WHERE status = 'success'")->fetchColumn();

        // Total filesize
        $totalSize = (int)$this->pdo->query("SELECT COALESCE(SUM(filesize), 0) FROM backup_logs WHERE status = 'success'")->fetchColumn();

        // Last backup details
        $lastBackup = $this->pdo->query("SELECT created_at FROM backup_logs WHERE status = 'success' ORDER BY created_at DESC LIMIT 1")->fetchColumn();

        // Backup today count
        $backupToday = (int)$this->pdo->query("SELECT COUNT(*) FROM backup_logs WHERE status = 'success' AND DATE(created_at) = CURDATE()")->fetchColumn();

        // Determine health
        $health = 'red';
        if ($lastBackup) {
            $daysSinceLastBackup = (time() - strtotime($lastBackup)) / (60 * 60 * 24);
            if ($daysSinceLastBackup <= 7) {
                $health = 'green';
            } else {
                $health = 'yellow';
            }
        } else {
            // Check if there are failed attempts
            $failedCount = (int)$this->pdo->query("SELECT COUNT(*) FROM backup_logs WHERE status = 'failed'")->fetchColumn();
            if ($failedCount > 0) {
                $health = 'red';
            } else {
                $health = 'yellow'; // Default to warning if never backed up
            }
        }

        return [
            'total_count' => $totalCount,
            'total_size' => $totalSize,
            'last_backup' => $lastBackup ? date('d M Y H:i', strtotime($lastBackup)) : '-',
            'backup_today' => $backupToday,
            'health' => $health
        ];
    }

    public function getSettings() {
        $file = __DIR__ . '/../config/backup_settings.json';
        if (!file_exists($file)) {
            $default = [
                'auto_backup' => 'off', // off, daily, weekly, monthly
                'last_run' => null
            ];
            file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT));
            return $default;
        }
        return json_decode(file_get_contents($file), true);
    }

    public function saveSettings($settings) {
        $file = __DIR__ . '/../config/backup_settings.json';
        return file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
    }

    public function getPdo() {
        return $this->pdo;
    }
}

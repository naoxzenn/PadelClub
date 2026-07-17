<?php
// controllers/BackupController.php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class BackupController {
    private $model;
    private $backupDir;

    public function __construct() {
        $this->model = new BackupModel();
        $this->backupDir = __DIR__ . '/../storage/backups/';
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create manual database backup (SQL + ZIP)
     * @param string $adminName
     * @return array
     */
    public function createBackup($adminName) {
        // Boost limit
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browser = BackupHelper::getBrowserName($userAgent);

        // Validate storage
        $storage = BackupHelper::checkStorage($this->backupDir);
        if (!$storage['status']) {
            $this->model->logBackup('failed_backup.zip', 0, $adminName, 'failed', $ipAddress, $browser, $storage['message']);
            return ['status' => false, 'message' => $storage['message']];
        }

        try {
            $tables = $this->model->getTables();
            $sqlContent = "-- PadelClub Database Backup\n";
            $sqlContent .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
            $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $pdo = $this->model->getPdo();

            foreach ($tables as $table) {
                // Skip backup_logs from dump to prevent infinite logs loop
                if ($table === 'backup_logs') continue;

                $sqlContent .= "-- --------------------------------------------------------\n";
                $sqlContent .= "-- Table structure for table `$table`\n";
                $sqlContent .= "-- --------------------------------------------------------\n";
                $sqlContent .= "DROP TABLE IF EXISTS `$table`;\n";
                
                $structure = $this->model->getTableStructure($table);
                $sqlContent .= $structure . ";\n\n";

                $sqlContent .= "-- Dumping data for table `$table`\n";
                $data = $this->model->getTableData($table);
                
                if (count($data) > 0) {
                    $cols = array_keys($data[0]);
                    $escapedCols = array_map(function($c) { return "`$c`"; }, $cols);
                    $sqlContent .= "INSERT INTO `$table` (" . implode(', ', $escapedCols) . ") VALUES \n";
                    
                    $valueLines = [];
                    foreach ($data as $row) {
                        $values = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = $pdo->quote($val);
                            }
                        }
                        $valueLines[] = "(" . implode(', ', $values) . ")";
                    }
                    
                    $sqlContent .= implode(",\n", $valueLines) . ";\n\n";
                }
            }

            $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Save SQL temporary file
            $sqlFilename = BackupHelper::generateFilename('sql');
            $sqlPath = $this->backupDir . $sqlFilename;
            file_put_contents($sqlPath, $sqlContent);

            // Create ZIP
            $zipFilename = BackupHelper::generateFilename('zip');
            $zipPath = $this->backupDir . $zipFilename;
            
            $zipSuccess = BackupHelper::createZip($sqlPath, $zipPath);

            // Clean up temporary SQL file
            BackupHelper::deleteFile($sqlPath);

            if ($zipSuccess) {
                $filesize = filesize($zipPath);
                $this->model->logBackup($zipFilename, $filesize, $adminName, 'success', $ipAddress, $browser, 'Backup berhasil dibuat secara manual.');
                return [
                    'status' => true, 
                    'message' => 'Backup database berhasil diselesaikan.',
                    'filename' => $zipFilename,
                    'filesize' => BackupHelper::formatSize($filesize)
                ];
            } else {
                throw new Exception("Gagal mengompresi file SQL menjadi ZIP.");
            }

        } catch (Exception $e) {
            $this->model->logBackup('failed_backup.zip', 0, $adminName, 'failed', $ipAddress, $browser, $e->getMessage());
            return ['status' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Download backup file securely
     * @param string $filename
     */
    public function downloadBackup($filename) {
        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $filePath = $this->backupDir . $filename;

        if (file_exists($filePath) && is_file($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            header("HTTP/1.0 404 Not Found");
            echo "File backup tidak ditemukan.";
            exit;
        }
    }

    /**
     * Restore database from backup file
     * @param string $filename
     * @param string $adminName
     * @return array
     */
    public function restoreBackup($filename, $adminName) {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $filename = basename($filename);
        $zipPath = $this->backupDir . $filename;

        if (!file_exists($zipPath)) {
            return ['status' => false, 'message' => "File backup tidak ditemukan."];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['status' => false, 'message' => "Gagal membuka file ZIP backup."];
        }

        // Extract to temporary folder
        $tempExtractPath = $this->backupDir . 'temp_restore/';
        if (!file_exists($tempExtractPath)) {
            mkdir($tempExtractPath, 0755, true);
        }

        $zip->extractTo($tempExtractPath);
        $zip->close();

        // Find the SQL file inside
        $files = scandir($tempExtractPath);
        $sqlFile = null;
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $sqlFile = $tempExtractPath . $file;
                break;
            }
        }

        if (!$sqlFile || !file_exists($sqlFile)) {
            // Cleanup
            $this->cleanRestoreTemp($tempExtractPath);
            return ['status' => false, 'message' => "Tidak ada file SQL di dalam file ZIP backup."];
        }

        try {
            $sqlContent = file_get_contents($sqlFile);
            $pdo = $this->model->getPdo();

            // Run restore
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            
            // Execute as multiple queries
            // Split by ';' but avoid splitting on text contents (rough parser handles simple structures)
            // A more robust way is querying directly
            $queries = explode(";\n", $sqlContent);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $pdo->exec($query);
                }
            }
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

            // Logging activity
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $browser = BackupHelper::getBrowserName($userAgent);
            
            $this->model->logBackup($filename, filesize($zipPath), $adminName, 'success', $ipAddress, $browser, 'Database berhasil di-restore.');

            // Cleanup
            $this->cleanRestoreTemp($tempExtractPath);

            return ['status' => true, 'message' => "Database berhasil di-restore ke kondisi tanggal backup."];

        } catch (Exception $e) {
            $this->cleanRestoreTemp($tempExtractPath);
            return ['status' => false, 'message' => "Gagal menjalankan SQL restore: " . $e->getMessage()];
        }
    }

    private function cleanRestoreTemp($dir) {
        if (file_exists($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                unlink($dir . '/' . $file);
            }
            rmdir($dir);
        }
    }

    /**
     * Delete backup file
     * @param string $filename
     * @return array
     */
    public function deleteBackup($filename) {
        $filename = basename($filename);
        $filePath = $this->backupDir . $filename;

        if (BackupHelper::deleteFile($filePath)) {
            return ['status' => true, 'message' => "File backup berhasil dihapus."];
        }
        return ['status' => false, 'message' => "Gagal menghapus file atau file tidak ditemukan."];
    }

    /**
     * Export data of specified tables to CSV, Excel, or PDF
     * @param string $type bookings|users|courts|payments
     * @param string $format csv|excel|pdf
     */
    public function exportData($type, $format) {
        $validTypes = ['bookings', 'users', 'courts', 'payments'];
        if (!in_array($type, $validTypes)) {
            die("Tipe data tidak valid.");
        }

        $pdo = $this->model->getPdo();
        $data = [];
        $headers = [];
        $title = "";

        switch ($type) {
            case 'bookings':
                $title = "Laporan Data Booking PadelClub";
                $headers = ['ID', 'Pelanggan', 'Lapangan', 'Tgl Main', 'Jam Mulai', 'Jam Selesai', 'Total Harga', 'Status', 'Tgl Transaksi'];
                $stmt = $pdo->query("
                    SELECT b.id, u.nama_lengkap, c.nama_lapangan, b.tanggal_booking, b.jam_mulai, b.jam_selesai, b.total_harga, b.status, b.created_at 
                    FROM bookings b
                    JOIN users u ON b.user_id = u.id
                    JOIN courts c ON b.court_id = c.id
                    ORDER BY b.created_at DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_NUM);
                break;

            case 'users':
                $title = "Laporan Data Customer PadelClub";
                $headers = ['ID', 'Nama Lengkap', 'Email', 'No Telepon', 'Role', 'Provider Login', 'Tgl Registrasi'];
                $stmt = $pdo->query("
                    SELECT id, nama_lengkap, email, nomor_telepon, role, login_provider, created_at 
                    FROM users 
                    ORDER BY created_at DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_NUM);
                break;

            case 'courts':
                $title = "Laporan Data Lapangan PadelClub";
                $headers = ['ID', 'Nama Lapangan', 'Tipe Lapangan', 'Tarif Per Jam', 'Deskripsi'];
                $stmt = $pdo->query("
                    SELECT id, nama_lapangan, tipe_lapangan, harga_per_jam, deskripsi 
                    FROM courts
                ");
                $data = $stmt->fetchAll(PDO::FETCH_NUM);
                break;

            case 'payments':
                $title = "Laporan Transaksi Pembayaran PadelClub";
                $headers = ['ID', 'ID Booking', 'Jumlah Bayar', 'Status Verifikasi', 'Status Bayar', 'No Struk', 'Kasir Pengonfirmasi', 'Waktu Bayar'];
                $stmt = $pdo->query("
                    SELECT p.id, p.booking_id, p.jumlah_bayar, p.status_verifikasi, p.payment_status, p.receipt_number, COALESCE(u.nama_lengkap, '-'), p.waktu_bayar 
                    FROM payments p
                    LEFT JOIN users u ON p.cashier_id = u.id
                    ORDER BY p.waktu_bayar DESC
                ");
                $data = $stmt->fetchAll(PDO::FETCH_NUM);
                break;
        }

        $filename = "export_" . $type . "_" . date('Ymd_His');

        if ($format === 'csv') {
            $this->outputCsv($filename . ".csv", $headers, $data);
        } elseif ($format === 'excel') {
            $this->outputExcel($filename . ".xlsx", $title, $headers, $data);
        } elseif ($format === 'pdf') {
            $this->outputPdf($filename . ".pdf", $title, $headers, $data);
        } else {
            die("Format ekspor tidak didukung.");
        }
    }

    private function outputCsv($filename, $headers, $data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    private function outputExcel($filename, $title, $headers, $data) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Export');

        // Styles
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . chr(65 + count($headers) - 1) . '1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // Headers
        $colIdx = 0;
        foreach ($headers as $header) {
            $colLetter = chr(65 + $colIdx);
            $sheet->setCellValue($colLetter . '3', $header);
            $sheet->getStyle($colLetter . '3')->getFont()->setBold(true);
            $sheet->getStyle($colLetter . '3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
            $colIdx++;
        }

        // Data Rows
        $rowIdx = 4;
        foreach ($data as $row) {
            $colIdx = 0;
            foreach ($row as $val) {
                $colLetter = chr(65 + $colIdx);
                $sheet->setCellValue($colLetter . $rowIdx, $val);
                $colIdx++;
            }
            $rowIdx++;
        }

        // Auto size columns
        foreach (range(0, count($headers) - 1) as $col) {
            $sheet->getColumnDimension(chr(65 + $col))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    private function outputPdf($filename, $title, $headers, $data) {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        
        $dompdf = new Dompdf($options);

        // Generate clean report HTML
        $html = '
        <html>
        <head>
            <style>
                body { font-family: sans-serif; font-size: 11px; color: #333; }
                h2 { text-align: center; color: #1e293b; margin-bottom: 20px; font-size: 16px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background-color: #0ea5e9; color: white; font-weight: bold; text-align: left; padding: 8px 6px; border: 1px solid #ddd; }
                td { padding: 6px; border: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f8fafc; }
                .footer { text-align: right; margin-top: 30px; font-size: 9px; color: #64748b; }
            </style>
        </head>
        <body>
            <h2>' . htmlspecialchars($title) . '</h2>
            <table>
                <thead>
                    <tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '  </tr>
                </thead>
                <tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $val) {
                $html .= '<td>' . htmlspecialchars($val ?? '-') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '
                </tbody>
            </table>
            <div class="footer">Dicetak oleh Admin PadelClub pada ' . date('d F Y H:i') . '</div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }

    public function getHistory($search = '', $filter = '', $offset = 0, $limit = 10) {
        return $this->model->getBackupHistory($search, $filter, $offset, $limit);
    }

    public function getHistoryCount($search = '', $filter = '') {
        return $this->model->getLogsCount($search, $filter);
    }

    public function getStats() {
        return $this->model->getBackupStats();
    }

    public function getSettings() {
        return $this->model->getSettings();
    }

    public function saveSettings($settings) {
        return $this->model->saveSettings($settings);
    }
}

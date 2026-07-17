<?php
// admin/restore.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../helpers/BackupHelper.php';
require_once __DIR__ . '/../models/BackupModel.php';
require_once __DIR__ . '/../controllers/BackupController.php';

$controller = new BackupController();

// Handle upload backup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['backup_file']['tmp_name'];
        $fileName = $_FILES['backup_file']['name'];
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        
        if ($ext === 'zip') {
            $destPath = __DIR__ . '/../storage/backups/' . basename($fileName);
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $_SESSION['toast_msg'] = "File backup berhasil diunggah.";
                $_SESSION['toast_type'] = "success";
            } else {
                $_SESSION['toast_msg'] = "Gagal memindahkan file ke folder backup.";
                $_SESSION['toast_type'] = "error";
            }
        } else {
            $_SESSION['toast_msg'] = "Hanya file ZIP hasil backup PadelClub yang didukung.";
            $_SESSION['toast_type'] = "error";
        }
    } else {
        $_SESSION['toast_msg'] = "Gagal mengunggah file.";
        $_SESSION['toast_type'] = "error";
    }
    header('Location: restore.php');
    exit;
}

// Handle execute restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restore') {
    $res = $controller->restoreBackup($_POST['file'], $_SESSION['nama'] ?? 'Admin');
    $_SESSION['toast_msg'] = $res['message'];
    $_SESSION['toast_type'] = $res['status'] ? 'success' : 'error';
    header('Location: restore.php');
    exit;
}

$pageTitle = 'Restore Database';
$baseUrl = '../';

// Scan directory for available ZIP backups
$backups = [];
$dir = __DIR__ . '/../storage/backups/';
if (file_exists($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'zip' && strpos($file, 'backup_') === 0) {
            $filePath = $dir . $file;
            $backups[] = [
                'filename' => $file,
                'filesize' => BackupHelper::formatSize(filesize($filePath)),
                'date' => date('d M Y H:i', filemtime($filePath)),
                'raw_date' => filemtime($filePath)
            ];
        }
    }
    usort($backups, function($a, $b) {
        return $b['raw_date'] - $a['raw_date'];
    });
}

include __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <!-- Header -->
        <div style="margin-bottom: 28px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Restore Database</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Kembalikan kondisi database PadelClub ke titik cadangan (backup) tertentu. Pastikan Anda memilih file yang benar.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 24px; align-items: start;">
            <!-- Upload Panel -->
            <div class="card" style="padding: 28px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin-bottom: 16px;">Unggah File Backup</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 20px;">
                    Unggah file cadangan berekstensi <strong>.zip</strong> dari komputer lokal Anda untuk ditambahkan ke daftar restorasi.
                </p>
                <form method="POST" action="restore.php" enctype="multipart/form-data" style="margin: 0;">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="backup_file">Pilih File ZIP</label>
                        <input type="file" id="backup_file" name="backup_file" accept=".zip" required style="padding: 8px 12px; font-size: 0.85rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 0.88rem; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 700;">
                        <span class="material-symbols-outlined">upload_file</span> Unggah File
                    </button>
                </form>
            </div>

            <!-- Backups Available Table -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <div style="padding: 24px 32px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin: 0;">Daftar File Cadangan yang Tersedia</h2>
                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;"><?= count($backups) ?> File Ditemukan</span>
                </div>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; margin: 0;">
                        <thead>
                            <tr style="background: var(--surface-alt);">
                                <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">No</th>
                                <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Nama File</th>
                                <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Waktu Modifikasi</th>
                                <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Ukuran</th>
                                <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border); text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($backups) === 0): ?>
                                <tr>
                                    <td colspan="5" style="padding: 40px; text-align: center; color: var(--text-muted);">
                                        <span class="material-symbols-outlined" style="font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.5;">cloud_off</span>
                                        Tidak ada file backup ZIP yang terdeteksi di folder storage.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $no = 1;
                                foreach ($backups as $backup): 
                                ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding: 16px 24px;"><?= $no++ ?></td>
                                        <td style="padding: 16px 24px; font-weight: 600; color: var(--navy);"><?= htmlspecialchars($backup['filename']) ?></td>
                                        <td style="padding: 16px 24px;"><?= $backup['date'] ?></td>
                                        <td style="padding: 16px 24px;"><?= $backup['filesize'] ?></td>
                                        <td style="padding: 16px 24px; text-align: center;">
                                            <button onclick="openRestoreModal('<?= htmlspecialchars($backup['filename']) ?>', '<?= $backup['date'] ?>', '<?= $backup['filesize'] ?>')" class="btn btn-secondary" style="padding: 6px 16px; font-size: 0.82rem; border-color: var(--blue); color: var(--blue); font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                                <span class="material-symbols-outlined" style="font-size: 1rem;">settings_backup_restore</span> Restore
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Restore Confirmation Modal -->
<div id="restore-modal" class="modal-backdrop" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; padding: 20px;">
    <div class="modal-box" style="background:var(--card-bg); border-radius:var(--radius-lg); padding:32px; width:100%; max-width:480px; box-shadow:var(--shadow-md); display: block; border: 1px solid var(--border);">
        <h3 style="font-size:1.25rem; font-weight:800; color:#EF4444; margin-bottom:16px; display:flex; align-items:center; gap:10px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <span class="material-symbols-outlined" style="font-size: 1.8rem;">warning</span> Peringatan Restorasi
        </h3>
        
        <div style="background: rgba(239, 68, 68, 0.08); border-left: 4px solid #EF4444; padding: 14px 18px; border-radius: 4px; margin-bottom: 20px;">
            <p style="color: #B91C1C; font-weight: 700; font-size: 0.88rem; margin: 0 0 6px 0;">Seluruh data saat ini akan diganti!</p>
            <p style="color: #DC2626; font-size: 0.82rem; margin: 0; line-height: 1.4;">
                Proses restore akan menghapus skema database saat ini dan menggantinya dengan data dari file cadangan yang dipilih.
            </p>
        </div>

        <div style="margin-bottom: 20px; font-size: 0.88rem;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 4px 0; color: var(--text-muted); width: 30%;">File:</td>
                    <td style="padding: 4px 0; font-weight: 700; color: var(--navy);" id="modal-filename"></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: var(--text-muted);">Tanggal:</td>
                    <td style="padding: 4px 0; font-weight: 600;" id="modal-date"></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: var(--text-muted);">Ukuran:</td>
                    <td style="padding: 4px 0; font-weight: 600;" id="modal-size"></td>
                </tr>
            </table>
        </div>

        <form method="POST" action="restore.php" onsubmit="showLoading()" style="margin: 0;">
            <input type="hidden" name="action" value="restore">
            <input type="hidden" name="file" id="modal-file-input">
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="verification_text" style="color: var(--text-muted); font-size: 0.75rem;">Ketik <strong>RESTORE</strong> untuk mengonfirmasi:</label>
                <input type="text" id="verification_text" required oninput="validateVerification(this.value)" autocomplete="off" placeholder="RESTORE" style="padding: 10px 12px; font-size: 0.9rem; text-align: center; font-weight: 700; border-color: #EF4444;">
            </div>

            <div style="display:flex; justify-content:flex-end; gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeRestoreModal()" style="padding:10px 20px; font-size:0.88rem;">Batal</button>
                <button type="submit" id="btn-submit-restore" disabled class="btn btn-primary" style="background:#EF4444; border-color:#EF4444; color:#fff; padding:10px 20px; font-size:0.88rem; font-weight:700;">Pulihkan Sekarang</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" style="display:none; position:fixed; z-index:10000; top:0; left:0; width:100%; height:100%; background:rgba(11, 15, 25, 0.85); align-items:center; justify-content:center; flex-direction:column;">
    <div style="border: 4px solid rgba(14, 165, 233, 0.2); border-left-color: var(--blue); width: 50px; height: 50px; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px;"></div>
    <h3 style="color:#fff; font-weight:700; font-size:1.2rem; margin:0 0 8px 0;">Merestorasi Database...</h3>
    <p style="color:rgba(255,255,255,0.7); font-size:0.9rem; margin:0;">Mohon jangan menutup halaman ini atau mematikan server XAMPP.</p>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($_SESSION['toast_msg'])): ?>
        showToast(<?= json_encode($_SESSION['toast_msg']) ?>, <?= json_encode($_SESSION['toast_type'] ?? 'success') ?>);
        <?php 
            unset($_SESSION['toast_msg']); 
            unset($_SESSION['toast_type']); 
        ?>
    <?php endif; ?>
});

function openRestoreModal(filename, date, size) {
    document.getElementById('modal-filename').textContent = filename;
    document.getElementById('modal-date').textContent = date;
    document.getElementById('modal-size').textContent = size;
    document.getElementById('modal-file-input').value = filename;
    document.getElementById('verification_text').value = '';
    document.getElementById('btn-submit-restore').disabled = true;
    
    document.getElementById('restore-modal').style.display = 'flex';
}

function closeRestoreModal() {
    document.getElementById('restore-modal').style.display = 'none';
}

function validateVerification(value) {
    const btn = document.getElementById('btn-submit-restore');
    if (value === 'RESTORE') {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
}

function showLoading() {
    document.getElementById('restore-modal').style.display = 'none';
    document.getElementById('loading-overlay').style.display = 'flex';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

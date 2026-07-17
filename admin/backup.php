<?php
// admin/backup.php
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

// Handle AJAX backup request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    header('Content-Type: application/json');
    $res = $controller->createBackup($_SESSION['nama'] ?? 'Admin');
    echo json_encode($res);
    exit;
}

// Handle secure download request
if (isset($_GET['action']) && $_GET['action'] === 'download' && !empty($_GET['file'])) {
    $controller->downloadBackup($_GET['file']);
    exit;
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['file'])) {
    $res = $controller->deleteBackup($_POST['file']);
    $_SESSION['toast_msg'] = $res['message'];
    $_SESSION['toast_type'] = $res['status'] ? 'success' : 'error';
    header('Location: backup.php');
    exit;
}

$pageTitle = 'Backup Database';
$baseUrl = '../';

// Search, Filter, Pagination
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$page = max((int)($_GET['page'] ?? 1), 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$history = $controller->getHistory($search, $filter, $offset, $limit);
$totalLogs = $controller->getHistoryCount($search, $filter);
$totalPages = ceil($totalLogs / $limit);

$stats = $controller->getStats();
$healthColor = 'var(--green)';
$healthLabel = 'Sehat (Backup Aktif)';
$healthIcon = 'check_circle';

if ($stats['health'] === 'yellow') {
    $healthColor = '#F59E0B';
    $healthLabel = 'Perhatian (> 7 Hari Belum Backup)';
    $healthIcon = 'warning';
} elseif ($stats['health'] === 'red') {
    $healthColor = '#EF4444';
    $healthLabel = 'Bahaya (Backup Gagal / Kosong)';
    $healthIcon = 'error';
}

include __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <!-- Header -->
        <div style="margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Backup Database</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Cadangkan seluruh skema dan data database PadelClub Anda secara berkala untuk keamanan data.</p>
            </div>
            <div>
                <button onclick="runBackup()" class="btn btn-primary" style="padding: 12px 24px; font-size: 0.9rem; border-radius: var(--radius-md); display: flex; align-items: center; gap: 8px; font-weight: 700; box-shadow: var(--shadow-glow);">
                    <span class="material-symbols-outlined">backup</span> Backup Sekarang
                </button>
            </div>
        </div>

        <!-- Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px;">
            <!-- Health Card -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: <?= $healthColor ?>15; color: <?= $healthColor ?>;">
                    <span class="material-symbols-outlined"><?= $healthIcon ?></span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size: 1.05rem; color: <?= $healthColor ?>; font-weight: 800;"><?= $healthLabel ?></span>
                    <span class="stat-card-label">Kesehatan Backup</span>
                </div>
            </div>

            <!-- Total Backup -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">folder_zip</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $stats['total_count'] ?> File</span>
                    <span class="stat-card-label">Total Backup Berhasil</span>
                </div>
            </div>

            <!-- Last Backup -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">schedule</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value" style="font-size: 1.15rem;"><?= $stats['last_backup'] ?></span>
                    <span class="stat-card-label">Backup Terakhir</span>
                </div>
            </div>

            <!-- Total Size -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">sd_card</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= BackupHelper::formatSize($stats['total_size']) ?></span>
                    <span class="stat-card-label">Ukuran Total Backup</span>
                </div>
            </div>

            <!-- Backup Today -->
            <div class="dashboard-stat-card">
                <div class="stat-card-icon" style="background: rgba(14, 165, 233, 0.08); color: var(--blue);">
                    <span class="material-symbols-outlined">today</span>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $stats['backup_today'] ?> Kali</span>
                    <span class="stat-card-label">Backup Hari Ini</span>
                </div>
            </div>
        </div>

        <!-- Filter & Search Panel -->
        <div class="card" style="padding: 24px; margin-bottom: 24px;">
            <form method="GET" action="backup.php" style="display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin: 0;">
                <div style="display: flex; gap: 12px; align-items: center; flex: 1; min-width: 280px;">
                    <div style="position: relative; flex: 1;">
                        <span class="material-symbols-outlined" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1.2rem;">search</span>
                        <input type="text" name="search" placeholder="Cari nama file atau admin..." value="<?= htmlspecialchars($search) ?>" style="padding: 10px 16px 10px 42px; font-size: 0.88rem; margin: 0;">
                    </div>
                    <select name="filter" style="width: auto; padding: 10px 32px 10px 16px; font-size: 0.88rem; margin: 0;">
                        <option value="">Semua Waktu</option>
                        <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="7days" <?= $filter === '7days' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                        <option value="30days" <?= $filter === '30days' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.88rem;">Filter</button>
                </div>
                <?php if (!empty($search) || !empty($filter)): ?>
                    <div>
                        <a href="backup.php" class="btn btn-outline" style="padding: 10px 20px; font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">close</span> Reset
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- History Table -->
        <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
            <div style="padding: 24px 32px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin: 0;">Riwayat Cadangan Database</h2>
                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;"><?= $totalLogs ?> Catatan Ditemukan</span>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; margin: 0;">
                    <thead>
                        <tr style="background: var(--surface-alt);">
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">No</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Nama File</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Tanggal</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Ukuran</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Status</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Dibuat Oleh</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border); text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history) === 0): ?>
                            <tr>
                                <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
                                    <span class="material-symbols-outlined" style="font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.5;">database_off</span>
                                    Belum ada riwayat backup database yang sesuai.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = $offset + 1;
                            foreach ($history as $row): 
                                $isSuccess = $row['status'] === 'success';
                                $exists = file_exists(__DIR__ . '/../storage/backups/' . $row['filename']);
                            ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 16px 24px;"><?= $no++ ?></td>
                                    <td style="padding: 16px 24px; font-weight: 600; color: var(--navy);"><?= htmlspecialchars($row['filename']) ?></td>
                                    <td style="padding: 16px 24px;"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td style="padding: 16px 24px;"><?= $isSuccess ? BackupHelper::formatSize($row['filesize']) : '-' ?></td>
                                    <td style="padding: 16px 24px;">
                                        <?php if ($isSuccess): ?>
                                            <span class="status-confirmed" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 4px; font-weight: 600;">Sukses</span>
                                        <?php else: ?>
                                            <span class="status-cancelled" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 4px; font-weight: 600;">Gagal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px 24px; font-weight: 500;"><?= htmlspecialchars($row['created_by']) ?></td>
                                    <td style="padding: 16px 24px; text-align: center;">
                                        <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                            <?php if ($isSuccess && $exists): ?>
                                                <a href="backup.php?action=download&file=<?= urlencode($row['filename']) ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; display: flex; align-items: center; gap: 4px;">
                                                    <span class="material-symbols-outlined" style="font-size: 1rem;">download</span> Download
                                                </a>
                                                <a href="restore.php?file=<?= urlencode($row['filename']) ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; border-color: var(--blue); color: var(--blue); display: flex; align-items: center; gap: 4px;">
                                                    <span class="material-symbols-outlined" style="font-size: 1rem;">restore</span> Restore
                                                </a>
                                                <button onclick="confirmDelete('<?= htmlspecialchars($row['filename']) ?>')" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; border-color: #EF4444; color: #EF4444; display: flex; align-items: center; gap: 4px;">
                                                    <span class="material-symbols-outlined" style="font-size: 1rem;">delete</span> Hapus
                                                </button>
                                            <?php elseif ($isSuccess && !$exists): ?>
                                                <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">File Terhapus di Disk</span>
                                            <?php else: ?>
                                                <span style="font-size: 0.8rem; color: #EF4444;">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="padding: 24px 32px; display: flex; justify-content: center; border-top: 1px solid var(--border);">
                    <div style="display: flex; gap: 8px;">
                        <?php if ($page > 1): ?>
                            <a href="backup.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem;">Sebelumnya</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="backup.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="padding: 8px 16px; font-size: 0.82rem;"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="backup.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem;">Berikutnya</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- Backup Progress Modal -->
<div id="backup-modal" class="modal-backdrop" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; padding: 20px;">
    <div class="modal-box" style="background:var(--card-bg); border-radius:var(--radius-lg); padding:32px; width:100%; max-width:480px; box-shadow:var(--shadow-md); display: block; border: 1px solid var(--border);">
        <h3 style="font-size:1.25rem; font-weight:800; color:var(--navy); margin-bottom:20px; display:flex; align-items:center; gap:10px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <span class="material-symbols-outlined" style="color:var(--blue); font-size: 1.8rem;">backup</span> Backup Database
        </h3>
        <p id="backup-step-text" style="color:var(--text-muted); font-size:0.95rem; margin-bottom:20px; line-height: 1.5;">Mempersiapkan Backup...</p>
        
        <div style="background: var(--surface-alt); height:10px; border-radius:6px; overflow:hidden; margin-bottom:24px; border: 1px solid var(--border);">
            <div id="backup-progress-bar" style="background: var(--gradient); height:100%; width:0%; transition:width 0.3s ease;"></div>
        </div>
        
        <div style="display:flex; justify-content:flex-end; gap: 10px;">
            <button id="btn-close-backup-modal" class="btn btn-primary" style="display:none; padding:10px 20px; font-size:0.88rem;" onclick="closeBackupModal()">Tutup Halaman</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-backdrop" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); align-items:center; justify-content:center; padding: 20px;">
    <div class="modal-box" style="background:var(--card-bg); border-radius:var(--radius-lg); padding:32px; width:100%; max-width:440px; box-shadow:var(--shadow-md); display: block; border: 1px solid var(--border);">
        <h3 style="font-size:1.25rem; font-weight:800; color:#EF4444; margin-bottom:16px; display:flex; align-items:center; gap:10px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <span class="material-symbols-outlined" style="font-size: 1.8rem;">warning</span> Konfirmasi Hapus
        </h3>
        <p style="color:var(--text); font-size:0.95rem; margin-bottom:24px; line-height: 1.5;">Apakah Anda yakin ingin menghapus file backup <span id="delete-filename-text" style="font-weight:700; color:var(--navy);"></span>? Tindakan ini tidak dapat dibatalkan.</p>
        
        <form method="POST" action="backup.php" style="margin: 0; display:flex; justify-content:flex-end; gap: 12px;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="file" id="delete-file-input">
            <button type="button" class="btn btn-outline" onclick="closeDeleteModal()" style="padding:10px 20px; font-size:0.88rem;">Batal</button>
            <button type="submit" class="btn btn-primary" style="background:#EF4444; border-color:#EF4444; color:#fff; padding:10px 20px; font-size:0.88rem;">Hapus Permanen</button>
        </form>
    </div>
</div>

<script>
// Trigger Toast if present in session redirect
document.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($_SESSION['toast_msg'])): ?>
        showToast(<?= json_encode($_SESSION['toast_msg']) ?>, <?= json_encode($_SESSION['toast_type'] ?? 'success') ?>);
        <?php 
            unset($_SESSION['toast_msg']); 
            unset($_SESSION['toast_type']); 
        ?>
    <?php endif; ?>
});

function runBackup() {
    const modal = document.getElementById('backup-modal');
    const stepText = document.getElementById('backup-step-text');
    const progressBar = document.getElementById('backup-progress-bar');
    const closeBtn = document.getElementById('btn-close-backup-modal');
    
    modal.style.display = 'flex';
    closeBtn.style.display = 'none';
    progressBar.style.width = '0%';
    progressBar.style.background = 'var(--gradient)';
    
    let steps = [
        { text: "Mempersiapkan Backup...", progress: 15 },
        { text: "Mengambil Struktur Database...", progress: 35 },
        { text: "Mengambil Data...", progress: 55 },
        { text: "Membuat SQL...", progress: 75 },
        { text: "Mengompres ZIP...", progress: 90 }
    ];
    
    let currentStep = 0;
    
    let interval = setInterval(() => {
        if (currentStep < steps.length) {
            stepText.textContent = steps[currentStep].text;
            progressBar.style.width = steps[currentStep].progress + '%';
            currentStep++;
        }
    }, 500);
    
    // AJAX Request
    const formData = new FormData();
    formData.append('action', 'create_backup');
    
    fetch('backup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(interval);
        if (data.status) {
            progressBar.style.width = '100%';
            stepText.innerHTML = `
                <div style="display:flex; align-items:center; gap:8px; color:var(--green); font-weight:700; margin-bottom:12px;">
                    <span class="material-symbols-outlined">check_circle</span> Backup Berhasil
                </div>
                File: <strong>${data.filename}</strong><br>
                Ukuran: <strong>${data.filesize}</strong><br>
                Lokasi: <span style="font-size:0.8rem; color:var(--text-muted);">/storage/backups/</span>
            `;
            closeBtn.style.display = 'block';
            showToast(data.message, 'success');
        } else {
            progressBar.style.width = '100%';
            progressBar.style.background = '#EF4444';
            stepText.innerHTML = `
                <div style="display:flex; align-items:center; gap:8px; color:#EF4444; font-weight:700; margin-bottom:12px;">
                    <span class="material-symbols-outlined">error</span> Backup Gagal
                </div>
                Detail Error: <span style="color:#EF4444;">${data.message}</span>
            `;
            closeBtn.style.display = 'block';
            showToast(data.message || 'Backup database gagal.', 'error');
        }
    })
    .catch(error => {
        clearInterval(interval);
        progressBar.style.width = '100%';
        progressBar.style.background = '#EF4444';
        stepText.innerHTML = `
            <div style="display:flex; align-items:center; gap:8px; color:#EF4444; font-weight:700; margin-bottom:12px;">
                <span class="material-symbols-outlined">wifi_off</span> Koneksi Terputus
            </div>
            Gagal menghubungkan ke server. Silakan periksa koneksi XAMPP Anda.
        `;
        closeBtn.style.display = 'block';
        showToast('Koneksi bermasalah. Gagal backup.', 'error');
    });
}

function closeBackupModal() {
    document.getElementById('backup-modal').style.display = 'none';
    window.location.reload();
}

function confirmDelete(filename) {
    document.getElementById('delete-filename-text').textContent = filename;
    document.getElementById('delete-file-input').value = filename;
    document.getElementById('delete-modal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

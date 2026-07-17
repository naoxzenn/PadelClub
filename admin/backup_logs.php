<?php
// admin/backup_logs.php
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

$pageTitle = 'Log Backup';
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

include __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <!-- Header -->
        <div style="margin-bottom: 28px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Audit Log Aktivitas Backup</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Pantau seluruh riwayat transaksi pembuatan cadangan data dan restorasi database demi tujuan kepatuhan keamanan (security auditing).</p>
        </div>

        <!-- Filter & Search Panel -->
        <div class="card" style="padding: 24px; margin-bottom: 24px;">
            <form method="GET" action="backup_logs.php" style="display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin: 0;">
                <div style="display: flex; gap: 12px; align-items: center; flex: 1; min-width: 280px;">
                    <div style="position: relative; flex: 1;">
                        <span class="material-symbols-outlined" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1.2rem;">search</span>
                        <input type="text" name="search" placeholder="Cari nama file, admin, atau catatan..." value="<?= htmlspecialchars($search) ?>" style="padding: 10px 16px 10px 42px; font-size: 0.88rem; margin: 0;">
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
                        <a href="backup_logs.php" class="btn btn-outline" style="padding: 10px 20px; font-size: 0.88rem; display: flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 1.1rem;">close</span> Reset
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
            <div style="padding: 24px 32px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--navy); margin: 0;">Log Transaksi Sistem</h2>
                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;"><?= $totalLogs ?> Aktivitas Tercatat</span>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; margin: 0;">
                    <thead>
                        <tr style="background: var(--surface-alt);">
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">No</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Waktu</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Nama File</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Ukuran</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Status</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">IP Address</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Browser</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Dibuat Oleh</th>
                            <th style="padding: 16px 24px; font-weight: 700; color: var(--navy); border-bottom: 1px solid var(--border);">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history) === 0): ?>
                            <tr>
                                <td colspan="9" style="padding: 40px; text-align: center; color: var(--text-muted);">
                                    <span class="material-symbols-outlined" style="font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.5;">history_toggle_off</span>
                                    Belum ada catatan aktivitas backup.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = $offset + 1;
                            foreach ($history as $row): 
                                $isSuccess = $row['status'] === 'success';
                            ?>
                                <tr style="border-bottom: 1px solid var(--border); font-size: 0.88rem;">
                                    <td style="padding: 16px 24px;"><?= $no++ ?></td>
                                    <td style="padding: 16px 24px; white-space: nowrap;"><?= date('d M Y H:i:s', strtotime($row['created_at'])) ?></td>
                                    <td style="padding: 16px 24px; font-weight: 600; color: var(--navy);"><?= htmlspecialchars($row['filename']) ?></td>
                                    <td style="padding: 16px 24px;"><?= $isSuccess && $row['filesize'] > 0 ? BackupHelper::formatSize($row['filesize']) : '-' ?></td>
                                    <td style="padding: 16px 24px;">
                                        <?php if ($isSuccess): ?>
                                            <span class="status-confirmed" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 4px; font-weight: 600;">Sukses</span>
                                        <?php else: ?>
                                            <span class="status-cancelled" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 4px; font-weight: 600;">Gagal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px 24px; font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($row['ip_address']) ?></td>
                                    <td style="padding: 16px 24px;"><?= htmlspecialchars($row['browser']) ?></td>
                                    <td style="padding: 16px 24px; font-weight: 500;"><?= htmlspecialchars($row['created_by']) ?></td>
                                    <td style="padding: 16px 24px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['note'] ?? '') ?>">
                                        <?= htmlspecialchars($row['note'] ?? '') ?>
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
                            <a href="backup_logs.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem;">Sebelumnya</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="backup_logs.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="padding: 8px 16px; font-size: 0.82rem;"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="backup_logs.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem;">Berikutnya</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
// admin/holidays.php - Pengelolaan Hari Libur Venue (Mobile Responsive & Standalone Modals)

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../helpers/HolidayHelper.php';

$pageTitle = 'Pengelolaan Hari Libur';
$baseUrl = '../';
$msg = '';
$error = '';

// Handle Actions: Add, Edit, Toggle, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. ADD HOLIDAY
    if ($action === 'add_holiday') {
        $date        = trim($_POST['holiday_date'] ?? '');
        $name        = trim($_POST['holiday_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isClosed    = isset($_POST['is_closed']) ? (int)$_POST['is_closed'] : 1;

        if (empty($date)) {
            $error = 'Tanggal hari libur wajib diisi.';
        } elseif (empty($name)) {
            $error = 'Nama hari libur wajib diisi.';
        } else {
            // Check unique date
            $stmtCheck = $pdo->prepare("SELECT id FROM holidays WHERE holiday_date = ? LIMIT 1");
            $stmtCheck->execute([$date]);
            if ($stmtCheck->rowCount() > 0) {
                $error = 'Hari libur pada tanggal ' . date('d F Y', strtotime($date)) . ' sudah terdaftar.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO holidays (holiday_date, holiday_name, description, is_closed)
                        VALUES (:date, :name, :desc, :closed)
                    ");
                    $stmt->execute([
                        ':date'   => $date,
                        ':name'   => $name,
                        ':desc'   => $description,
                        ':closed' => $isClosed
                    ]);
                    $msg = 'Hari libur baru berhasil ditambahkan.';
                } catch (\Throwable $e) {
                    error_log("Add holiday error: " . $e->getMessage());
                    $error = 'Gagal menambahkan hari libur.';
                }
            }
        }
    }

    // 2. EDIT HOLIDAY
    if ($action === 'edit_holiday') {
        $id          = (int)($_POST['id'] ?? 0);
        $date        = trim($_POST['holiday_date'] ?? '');
        $name        = trim($_POST['holiday_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isClosed    = isset($_POST['is_closed']) ? (int)$_POST['is_closed'] : 1;

        if ($id <= 0) {
            $error = 'ID hari libur tidak valid.';
        } elseif (empty($date)) {
            $error = 'Tanggal hari libur wajib diisi.';
        } elseif (empty($name)) {
            $error = 'Nama hari libur wajib diisi.';
        } else {
            // Check unique date excluding current record
            $stmtCheck = $pdo->prepare("SELECT id FROM holidays WHERE holiday_date = ? AND id != ? LIMIT 1");
            $stmtCheck->execute([$date, $id]);
            if ($stmtCheck->rowCount() > 0) {
                $error = 'Hari libur pada tanggal ' . date('d F Y', strtotime($date)) . ' sudah terdaftar.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE holidays SET
                            holiday_date = :date,
                            holiday_name = :name,
                            description  = :desc,
                            is_closed    = :closed
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':date'   => $date,
                        ':name'   => $name,
                        ':desc'   => $description,
                        ':closed' => $isClosed,
                        ':id'     => $id
                    ]);
                    $msg = 'Data hari libur berhasil diperbarui.';
                } catch (\Throwable $e) {
                    error_log("Edit holiday error: " . $e->getMessage());
                    $error = 'Gagal memperbarui data hari libur.';
                }
            }
        }
    }

    // 3. TOGGLE STATUS
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE holidays SET is_closed = IF(is_closed = 1, 0, 1) WHERE id = ?");
                $stmt->execute([$id]);
                $msg = 'Status hari libur berhasil diubah.';
            } catch (\Throwable $e) {
                $error = 'Gagal mengubah status hari libur.';
            }
        }
    }

    // 4. DELETE HOLIDAY
    if ($action === 'delete_holiday') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM holidays WHERE id = ?");
                $stmt->execute([$id]);
                $msg = 'Hari libur berhasil dihapus dari database.';
            } catch (\Throwable $e) {
                $error = 'Gagal menghapus hari libur.';
            }
        }
    }
}

// Fetch list of holidays ordered by date
$holidays = [];
try {
    $stmt = $pdo->query("SELECT * FROM holidays ORDER BY holiday_date ASC");
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    error_log("Fetch holidays error: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
?>

<style>
/* STANDALONE RESPONSIVE MODAL & PAGE STYLING FOR HOLIDAYS */
.holiday-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 99999 !important;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    overflow-y: auto;
}

.holiday-modal-backdrop.active {
    display: flex !important;
}

.holiday-modal-dialog {
    background: var(--card-bg, #ffffff);
    border-radius: var(--radius-lg, 16px);
    width: 100%;
    max-width: 480px;
    max-height: calc(100vh - 32px);
    max-height: calc(100dvh - 32px);
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid var(--border, #E2E8F0);
    padding: 24px;
    margin: auto;
    animation: holidayModalSlide 0.25s ease-out;
}

@keyframes holidayModalSlide {
    from { transform: translateY(15px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@media (max-width: 640px) {
    .holiday-modal-dialog {
        padding: 18px;
        border-radius: 14px;
    }
    .holiday-table th, .holiday-table td {
        padding: 10px 12px !important;
        font-size: 0.85rem !important;
    }
    .holiday-action-btns {
        flex-direction: column !important;
        gap: 4px !important;
    }
    .holiday-top-header {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    .holiday-add-btn {
        width: 100% !important;
        justify-content: center !important;
    }
}
</style>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <!-- Top Header & Breadcrumb -->
        <div class="holiday-top-header" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div>
                <a href="settings.php" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_back</span> Kembali ke Pengaturan Sistem
                </a>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--navy); margin-bottom: 4px; display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--blue);">event_busy</span>
                    Pengelolaan Hari Libur Venue
                </h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Daftar tanggal venue tutup / hari libur khusus untuk validasi reservasi.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary holiday-add-btn" onclick="openAddHolidayModal()" style="padding: 10px 20px; font-size: 0.9rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-outlined" style="font-size: 1.2rem;">add</span> + Tambah Hari Libur
                </button>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 600;"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 600;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Holidays Table Card -->
        <div class="card" style="padding: 20px; border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 30px;">
            <?php if (empty($holidays)): ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <span class="material-symbols-outlined" style="font-size: 3.5rem; color: var(--border); display: block; margin-bottom: 12px;">event_available</span>
                    <h3 style="font-size: 1.1rem; color: var(--navy); margin-bottom: 6px;">Belum Ada Hari Libur Terdaftar</h3>
                    <p style="font-size: 0.88rem; max-width: 400px; margin: 0 auto 16px auto;">Tambahkan tanggal hari libur agar reservasi pada tanggal tersebut otomatis ditolak oleh sistem.</p>
                    <button type="button" class="btn btn-primary" onclick="openAddHolidayModal()">
                        + Tambah Hari Libur Pertama
                    </button>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto; width: 100%;">
                    <table class="holiday-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                <th style="padding: 12px 16px;">Tanggal</th>
                                <th style="padding: 12px 16px;">Nama Hari Libur</th>
                                <th style="padding: 12px 16px;">Keterangan</th>
                                <th style="padding: 12px 16px;">Status</th>
                                <th style="padding: 12px 16px; text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($holidays as $h): ?>
                                <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s ease;" onmouseover="this.style.background='rgba(0,0,0,0.015)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 14px 16px; font-weight: 700; color: var(--navy); white-space: nowrap;">
                                        <?= date('d F Y', strtotime($h['holiday_date'])) ?>
                                    </td>
                                    <td style="padding: 14px 16px; font-weight: 700; color: var(--blue);">
                                        <?= htmlspecialchars($h['holiday_name'] ?? $h['title'] ?? 'Hari Libur') ?>
                                    </td>
                                    <td style="padding: 14px 16px; color: var(--text-muted);">
                                        <?= htmlspecialchars($h['description'] ?? '-') ?>
                                    </td>
                                    <td style="padding: 14px 16px; white-space: nowrap;">
                                        <?php if (!isset($h['is_closed']) || (int)$h['is_closed'] === 1): ?>
                                            <span class="status-cancelled" style="padding: 4px 12px; font-size: 0.76rem; font-weight: 700; border-radius: var(--radius-full); background: rgba(239, 68, 68, 0.15); color: #EF4444;">Venue Tutup</span>
                                        <?php else: ?>
                                            <span class="status-confirmed" style="padding: 4px 12px; font-size: 0.76rem; font-weight: 700; border-radius: var(--radius-full); background: rgba(148, 163, 184, 0.15); color: #64748B;">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                        <div class="holiday-action-btns" style="display: inline-flex; gap: 6px;">
                                            <!-- EDIT -->
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="openEditHolidayModal(<?= $h['id'] ?>, '<?= $h['holiday_date'] ?>', '<?= htmlspecialchars(addslashes($h['holiday_name'] ?? $h['title'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($h['description'] ?? '')) ?>', <?= $h['is_closed'] ?? 1 ?>)">
                                                <span class="material-symbols-outlined" style="font-size: 0.95rem; vertical-align: middle;">edit</span> Edit
                                            </button>

                                            <!-- TOGGLE -->
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" title="Ubah status aktif/nonaktif">
                                                    <span class="material-symbols-outlined" style="font-size: 0.95rem; vertical-align: middle;">sync</span>
                                                    <?= (!isset($h['is_closed']) || (int)$h['is_closed'] === 1) ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>

                                            <!-- DELETE VIA DEDICATED MODAL -->
                                            <button type="button" class="btn btn-sm" 
                                                    style="background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.2);" 
                                                    onclick="openDeleteHolidayModal(<?= $h['id'] ?>, '<?= htmlspecialchars(addslashes($h['holiday_name'] ?? $h['title'] ?? 'Hari Libur')) ?>', '<?= date('d F Y', strtotime($h['holiday_date'])) ?>')" 
                                                    title="Hapus Hari Libur">
                                                <span class="material-symbols-outlined" style="font-size: 0.95rem; vertical-align: middle;">delete</span> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- STANDALONE RESPONSIVE MODAL TAMBAH HARI LIBUR -->
<div class="holiday-modal-backdrop" id="modal-add-holiday">
    <div class="holiday-modal-dialog">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--blue);">event_available</span>
                Tambah Hari Libur Baru
            </h3>
            <button type="button" onclick="closeHolidayModal('modal-add-holiday')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add_holiday">

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="add_holiday_date" style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Tanggal Libur</label>
                <input type="date" id="add_holiday_date" name="holiday_date" required style="width: 100%; padding: 9px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="add_holiday_name" style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Nama Hari Libur</label>
                <input type="text" id="add_holiday_name" name="holiday_name" placeholder="Contoh: HUT Kemerdekaan RI" required style="width: 100%; padding: 9px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="add_description" style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Keterangan (Opsional)</label>
                <textarea id="add_description" name="description" rows="2" placeholder="Contoh: Hari Libur Nasional / Maintenance Venue" style="width: 100%; padding: 9px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 6px;">Status Operasional</label>
                <div style="display: flex; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                        <input type="radio" name="is_closed" value="1" checked>
                        <span style="font-weight: 700; color: #EF4444;">Venue Tutup (Aktif)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                        <input type="radio" name="is_closed" value="0">
                        <span style="font-weight: 700; color: #64748B;">Nonaktif</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 16px;">
                <button type="button" class="btn btn-outline" onclick="closeHolidayModal('modal-add-holiday')" style="padding: 9px 18px; font-size: 0.88rem;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 9px 22px; font-size: 0.88rem; font-weight: 700;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- STANDALONE RESPONSIVE MODAL EDIT HARI LIBUR -->
<div class="holiday-modal-backdrop" id="modal-edit-holiday">
    <div class="holiday-modal-dialog">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--blue);">edit_calendar</span>
                Edit Hari Libur
            </h3>
            <button type="button" onclick="closeHolidayModal('modal-edit-holiday')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="edit_holiday">
            <input type="hidden" name="id" id="edit_id">

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="edit_holiday_date" style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Tanggal Libur</label>
                <input type="date" id="edit_holiday_date" name="holiday_date" required style="width: 100%; padding: 9px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="edit_holiday_name" style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Nama Hari Libur</label>
                <input type="text" id="edit_holiday_name" name="holiday_name" required style="width: 100%; padding: 9px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="edit_description" style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Keterangan (Opsional)</label>
                <textarea id="edit_description" name="description" rows="2" style="width: 100%; padding: 9px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 6px;">Status Operasional</label>
                <div style="display: flex; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                        <input type="radio" name="is_closed" id="edit_closed_1" value="1">
                        <span style="font-weight: 700; color: #EF4444;">Venue Tutup (Aktif)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                        <input type="radio" name="is_closed" id="edit_closed_0" value="0">
                        <span style="font-weight: 700; color: #64748B;">Nonaktif</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 16px;">
                <button type="button" class="btn btn-outline" onclick="closeHolidayModal('modal-edit-holiday')" style="padding: 9px 18px; font-size: 0.88rem;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 9px 22px; font-size: 0.88rem; font-weight: 700;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS HARI LIBUR -->
<div class="holiday-modal-backdrop" id="modal-confirm-delete-holiday">
    <div class="holiday-modal-dialog" style="max-width: 420px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h3 style="font-size: 1.1rem; font-weight: 800; color: #EF4444; margin: 0; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="font-size: 1.3rem;">warning</span> Konfirmasi Hapus
            </h3>
            <button type="button" onclick="closeHolidayModal('modal-confirm-delete-holiday')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>

        <p style="font-size: 0.92rem; color: var(--navy); margin-bottom: 8px; line-height: 1.4;">
            Apakah Anda yakin ingin menghapus hari libur <strong id="delete_holiday_name_txt" style="color: var(--blue);"></strong> (<span id="delete_holiday_date_txt"></span>)?
        </p>
        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 20px;">
            Tindakan ini tidak dapat dibatalkan. Reservasi pada tanggal ini akan kembali diizinkan jika venue buka.
        </p>

        <form method="POST" action="">
            <input type="hidden" name="action" value="delete_holiday">
            <input type="hidden" name="id" id="delete_holiday_id">

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 14px;">
                <button type="button" class="btn btn-outline" onclick="closeHolidayModal('modal-confirm-delete-holiday')" style="padding: 8px 16px; font-size: 0.85rem;">Batal</button>
                <button type="submit" class="btn" style="background: #EF4444; color: #ffffff; padding: 8px 20px; font-size: 0.85rem; font-weight: 700; border: none;">Ya, Hapus Sekarang</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddHolidayModal() {
    const backdrop = document.getElementById('modal-add-holiday');
    if (backdrop) {
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function openEditHolidayModal(id, date, name, description, isClosed) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_holiday_date').value = date;
    document.getElementById('edit_holiday_name').value = name;
    document.getElementById('edit_description').value = description;

    if (isClosed == 1) {
        document.getElementById('edit_closed_1').checked = true;
    } else {
        document.getElementById('edit_closed_0').checked = true;
    }

    const backdrop = document.getElementById('modal-edit-holiday');
    if (backdrop) {
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function openDeleteHolidayModal(id, name, dateFormatted) {
    document.getElementById('delete_holiday_id').value = id;
    document.getElementById('delete_holiday_name_txt').innerText = name;
    document.getElementById('delete_holiday_date_txt').innerText = dateFormatted;

    const backdrop = document.getElementById('modal-confirm-delete-holiday');
    if (backdrop) {
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeHolidayModal(id) {
    const backdrop = document.getElementById(id);
    if (backdrop) {
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modals when clicking outside modal-dialog
document.addEventListener('click', function(e) {
    ['modal-add-holiday', 'modal-edit-holiday', 'modal-confirm-delete-holiday'].forEach(function(id) {
        const backdrop = document.getElementById(id);
        if (e.target === backdrop) {
            closeHolidayModal(id);
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

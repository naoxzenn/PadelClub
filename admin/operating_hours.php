<?php
// admin/operating_hours.php - Pengelolaan Jam Operasional Venue (Multi-Shift & Mobile Responsive)

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../helpers/OperatingHoursHelper.php';

$pageTitle = 'Jam Operasional Venue';
$baseUrl = '../';
$msg = '';
$error = '';

// Handle POST Form Submission (Update Jam Operasional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_operating_hours') {
    $dayOfWeek = (int) ($_POST['day_of_week'] ?? 0);
    $isOpen = isset($_POST['is_open']) ? (int) $_POST['is_open'] : 0;

    $openTime = trim($_POST['open_time'] ?? '');
    $closeTime = trim($_POST['close_time'] ?? '');

    $hasShift2 = isset($_POST['enable_shift2']) && (int) $_POST['enable_shift2'] === 1;
    $openTime2 = trim($_POST['open_time_2'] ?? '');
    $closeTime2 = trim($_POST['close_time_2'] ?? '');

    if ($dayOfWeek < 1 || $dayOfWeek > 7) {
        $error = 'Hari tidak valid.';
    } elseif ($isOpen === 1 && (empty($openTime) || empty($closeTime))) {
        $error = 'Jam buka dan jam tutup Sesi 1 wajib diisi.';
    } elseif ($isOpen === 1 && $closeTime <= $openTime) {
        $error = 'Jam tutup Sesi 1 harus lebih besar dari jam buka Sesi 1.';
    } elseif ($isOpen === 1 && $hasShift2 && (empty($openTime2) || empty($closeTime2))) {
        $error = 'Jam buka dan jam tutup Sesi 2 wajib diisi jika Sesi 2 diaktifkan.';
    } elseif ($isOpen === 1 && $hasShift2 && $closeTime2 <= $openTime2) {
        $error = 'Jam tutup Sesi 2 harus lebih besar dari jam buka Sesi 2.';
    } elseif ($isOpen === 1 && $hasShift2 && $openTime2 < $closeTime) {
        $error = 'Jam buka Sesi 2 harus setelah jam tutup Sesi 1 (setelah waktu istirahat).';
    } else {
        // Ensure HH:MM:00
        if (!empty($openTime) && strlen($openTime) === 5)
            $openTime .= ':00';
        if (!empty($closeTime) && strlen($closeTime) === 5)
            $closeTime .= ':00';

        $valOpen2 = ($hasShift2 && !empty($openTime2)) ? (strlen($openTime2) === 5 ? $openTime2 . ':00' : $openTime2) : null;
        $valClose2 = ($hasShift2 && !empty($closeTime2)) ? (strlen($closeTime2) === 5 ? $closeTime2 . ':00' : $closeTime2) : null;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO operating_hours (day_of_week, open_time, close_time, open_time_2, close_time_2, is_open)
                VALUES (:day, :open, :close, :open2, :close2, :is_open)
                ON DUPLICATE KEY UPDATE
                    open_time    = VALUES(open_time),
                    close_time   = VALUES(close_time),
                    open_time_2  = VALUES(open_time_2),
                    close_time_2 = VALUES(close_time_2),
                    is_open      = VALUES(is_open)
            ");
            $stmt->execute([
                ':day' => $dayOfWeek,
                ':open' => $openTime ?: '07:00:00',
                ':close' => $closeTime ?: '22:00:00',
                ':open2' => $valOpen2,
                ':close2' => $valClose2,
                ':is_open' => $isOpen
            ]);
            $msg = 'Jam operasional hari ' . OperatingHoursHelper::getDayName($dayOfWeek) . ' berhasil diperbarui.';
        } catch (\Throwable $e) {
            error_log("Update operating_hours error: " . $e->getMessage());
            $error = 'Gagal memperbarui jam operasional.';
        }
    }
}

// Fetch schedule for all 7 days
$schedule = [];
for ($i = 1; $i <= 7; $i++) {
    $date = date('Y-m-d', strtotime("2026-01-0" . (4 + $i)));
    $schedule[$i] = OperatingHoursHelper::getOperatingHoursByDate($date, $pdo);
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    /* STANDALONE RESPONSIVE MODAL & PAGE STYLING */
    .ophours-modal-backdrop {
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

    .ophours-modal-backdrop.active {
        display: flex !important;
    }

    .ophours-modal-dialog {
        background: var(--card-bg, #ffffff);
        border-radius: var(--radius-lg, 16px);
        width: 100%;
        max-width: 520px;
        max-height: calc(100vh - 32px);
        max-height: calc(100dvh - 32px);
        overflow-y: auto;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        border: 1px solid var(--border, #E2E8F0);
        padding: 24px;
        margin: auto;
        animation: ophoursModalSlide 0.25s ease-out;
    }

    @keyframes ophoursModalSlide {
        from {
            transform: translateY(15px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @media (max-width: 640px) {
        .ophours-modal-dialog {
            padding: 18px;
            border-radius: 14px;
        }

        .ophours-grid-2col {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
        }

        .ophours-table th,
        .ophours-table td {
            padding: 10px 12px !important;
            font-size: 0.85rem !important;
        }
    }
</style>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <!-- Header & Navigation -->
        <div
            style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div>
                <a href="settings.php" class="btn btn-outline"
                    style="padding: 6px 14px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 12px;">
                    <span class="material-symbols-outlined" style="font-size: 1.1rem;">arrow_back</span> Kembali ke
                    Pengaturan Sistem
                </a>
                <h1
                    style="font-size: 1.75rem; font-weight: 800; color: var(--navy); margin-bottom: 4px; display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-outlined" style="font-size: 2rem; color: var(--blue);">schedule</span>
                    Jam Operasional Venue
                </h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">Atur jam buka, tutup, dan sesi
                    operasional/istirahat venue PadelClub.</p>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-success"
                style="margin-bottom: 20px; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 600;">
                <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"
                style="margin-bottom: 20px; padding: 12px 16px; border-radius: var(--radius-md); font-weight: 600;">
                <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Schedule Table Card -->
        <div class="card"
            style="padding: 20px; border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 30px;">
            <div style="overflow-x: auto; width: 100%;">
                <table class="ophours-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr
                            style="border-bottom: 2px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 12px 16px;">Hari</th>
                            <th style="padding: 12px 16px;">Jam Operasional (Sesi 1)</th>
                            <th style="padding: 12px 16px;">Sesi 2 / Setelah Istirahat</th>
                            <th style="padding: 12px 16px;">Status</th>
                            <th style="padding: 12px 16px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedule as $dayOfWeek => $row): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s ease;"
                                onmouseover="this.style.background='rgba(0,0,0,0.015)'"
                                onmouseout="this.style.background='transparent'">
                                <td style="padding: 14px 16px; font-weight: 700; color: var(--navy); white-space: nowrap;">
                                    <?= htmlspecialchars($row['day_name']) ?>
                                </td>

                                <td
                                    style="padding: 14px 16px; font-family: monospace; font-size: 0.92rem; white-space: nowrap;">
                                    <?php if ($row['is_open']): ?>
                                        <span
                                            style="font-weight: 700; color: var(--navy);"><?= substr($row['open_time'], 0, 5) ?></span>
                                        – <span
                                            style="font-weight: 700; color: var(--navy);"><?= substr($row['close_time'], 0, 5) ?></span>
                                        WIB
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>

                                <td
                                    style="padding: 14px 16px; font-family: monospace; font-size: 0.92rem; white-space: nowrap;">
                                    <?php if ($row['is_open'] && !empty($row['has_shift2'])): ?>
                                        <span
                                            style="font-weight: 700; color: var(--blue);"><?= substr($row['open_time_2'], 0, 5) ?></span>
                                        – <span
                                            style="font-weight: 700; color: var(--blue);"><?= substr($row['close_time_2'], 0, 5) ?></span>
                                        WIB
                                    <?php elseif ($row['is_open']): ?>
                                        <span style="color: var(--text-muted); font-size: 0.82rem; font-style: italic;">Nonstop
                                            (Satu Sesi)</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>

                                <td style="padding: 14px 16px; white-space: nowrap;">
                                    <?php if ($row['is_open']): ?>
                                        <span class="status-confirmed"
                                            style="padding: 4px 12px; font-size: 0.76rem; font-weight: 700; border-radius: var(--radius-full); background: rgba(34, 197, 94, 0.15); color: var(--green);">Buka</span>
                                    <?php else: ?>
                                        <span class="status-cancelled"
                                            style="padding: 4px 12px; font-size: 0.76rem; font-weight: 700; border-radius: var(--radius-full); background: rgba(239, 68, 68, 0.15); color: #EF4444;">Tutup</span>
                                    <?php endif; ?>
                                </td>

                                <td style="padding: 14px 16px; text-align: right; white-space: nowrap;">
                                    <button type="button" class="btn btn-sm btn-outline"
                                        onclick="openEditOpModal(<?= $dayOfWeek ?>, '<?= htmlspecialchars($row['day_name']) ?>', '<?= substr($row['open_time'], 0, 5) ?>', '<?= substr($row['close_time'], 0, 5) ?>', <?= $row['is_open'] ?>, <?= !empty($row['has_shift2']) ? 1 : 0 ?>, '<?= substr($row['open_time_2'] ?? '', 0, 5) ?>', '<?= substr($row['close_time_2'] ?? '', 0, 5) ?>')">
                                        <span class="material-symbols-outlined"
                                            style="font-size: 1rem; vertical-align: middle;">edit</span> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<!-- STANDALONE RESPONSIVE MODAL EDIT JAM OPERASIONAL -->
<div class="ophours-modal-backdrop" id="modal-edit-ophours">
    <div class="ophours-modal-dialog">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
            <h3
                style="font-size: 1.15rem; font-weight: 800; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 8px;">
                <span class="material-symbols-outlined" style="color: var(--blue);">edit_calendar</span>
                Edit Jam Operasional <span id="modal-day-name" style="color: var(--blue);"></span>
            </h3>
            <button type="button" onclick="closeOpModal('modal-edit-ophours')"
                style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="update_operating_hours">
            <input type="hidden" name="day_of_week" id="input_day_of_week">

            <!-- Status Operasional -->
            <div class="form-group" style="margin-bottom: 18px;">
                <label
                    style="font-size: 0.85rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 8px;">Status
                    Operasional</label>
                <div style="display: flex; gap: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                        <input type="radio" name="is_open" id="status_open_1" value="1"
                            onchange="toggleOpFormFields(true)">
                        <span style="font-weight: 700; color: var(--green);">Buka</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                        <input type="radio" name="is_open" id="status_open_0" value="0"
                            onchange="toggleOpFormFields(false)">
                        <span style="font-weight: 700; color: #EF4444;">Tutup (Seharian)</span>
                    </label>
                </div>
            </div>

            <div id="op_time_fields_container">
                <!-- SESI 1 (JAM UTAMA) -->
                <div
                    style="background: rgba(241, 245, 249, 0.6); padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px; border: 1px solid var(--border);">
                    <div
                        style="font-size: 0.82rem; font-weight: 800; color: var(--navy); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-outlined"
                            style="font-size: 1.1rem; color: var(--blue);">wb_sunny</span> Sesi 1 (Jam Utama)
                    </div>
                    <div class="ophours-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group" style="margin: 0;">
                            <label for="input_open_time"
                                style="font-size: 0.82rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Jam
                                Buka</label>
                            <input type="time" id="input_open_time" name="open_time"
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        </div>

                        <div class="form-group" style="margin: 0;">
                            <label for="input_close_time"
                                style="font-size: 0.82rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Jam
                                Tutup / Istirahat</label>
                            <input type="time" id="input_close_time" name="close_time"
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        </div>
                    </div>
                </div>

                <!-- CHECKBOX TOGGLE SESI 2 (ISTIRAHAT / SHIFT 2) -->
                <div style="margin-bottom: 16px;">
                    <label
                        style="display: flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 700; color: var(--navy); cursor: pointer;">
                        <input type="checkbox" id="chk_enable_shift2" name="enable_shift2" value="1"
                            onchange="toggleShift2Fields(this.checked)">
                        <span>Aktifkan Sesi 2 (Buka Kembali Setelah Istirahat)</span>
                    </label>
                </div>

                <!-- SESI 2 (OPSIONAL AFTER ISTIRAHAT) -->
                <div id="shift2_container"
                    style="background: rgba(238, 242, 255, 0.6); padding: 14px; border-radius: var(--radius-md); margin-bottom: 20px; border: 1px solid rgba(99, 102, 241, 0.2); display: none;">
                    <div
                        style="font-size: 0.82rem; font-weight: 800; color: var(--blue); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-outlined"
                            style="font-size: 1.1rem; color: var(--blue);">nights_stay</span> Sesi 2 (Setelah Istirahat)
                    </div>
                    <div class="ophours-grid-2col" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group" style="margin: 0;">
                            <label for="input_open_time_2"
                                style="font-size: 0.82rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Jam
                                Buka Sesi 2</label>
                            <input type="time" id="input_open_time_2" name="open_time_2"
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        </div>

                        <div class="form-group" style="margin: 0;">
                            <label for="input_close_time_2"
                                style="font-size: 0.82rem; font-weight: 700; color: var(--navy); display: block; margin-bottom: 4px;">Jam
                                Tutup Akhir</label>
                            <input type="time" id="input_close_time_2" name="close_time_2"
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                        </div>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border); padding-top: 16px;">
                <button type="button" class="btn btn-outline" onclick="closeOpModal('modal-edit-ophours')"
                    style="padding: 9px 18px; font-size: 0.88rem;">Batal</button>
                <button type="submit" class="btn btn-primary"
                    style="padding: 9px 22px; font-size: 0.88rem; font-weight: 700;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditOpModal(dayOfWeek, dayName, openTime, closeTime, isOpen, hasShift2, openTime2, closeTime2) {
        document.getElementById('input_day_of_week').value = dayOfWeek;
        document.getElementById('modal-day-name').innerText = dayName;
        document.getElementById('input_open_time').value = openTime;
        document.getElementById('input_close_time').value = closeTime;

        const radOpen = document.getElementById('status_open_1');
        const radClose = document.getElementById('status_open_0');

        if (isOpen == 1) {
            radOpen.checked = true;
            toggleOpFormFields(true);
        } else {
            radClose.checked = true;
            toggleOpFormFields(false);
        }

        const chkShift2 = document.getElementById('chk_enable_shift2');
        if (hasShift2 == 1) {
            chkShift2.checked = true;
            document.getElementById('input_open_time_2').value = openTime2;
            document.getElementById('input_close_time_2').value = closeTime2;
            toggleShift2Fields(true);
        } else {
            chkShift2.checked = false;
            document.getElementById('input_open_time_2').value = '';
            document.getElementById('input_close_time_2').value = '';
            toggleShift2Fields(false);
        }

        const backdrop = document.getElementById('modal-edit-ophours');
        if (backdrop) {
            backdrop.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeOpModal(id) {
        const backdrop = document.getElementById(id);
        if (backdrop) {
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    function toggleOpFormFields(isOpen) {
        const container = document.getElementById('op_time_fields_container');
        if (container) {
            container.style.display = isOpen ? 'block' : 'none';
        }
    }

    function toggleShift2Fields(enabled) {
        const container = document.getElementById('shift2_container');
        if (container) {
            container.style.display = enabled ? 'block' : 'none';
        }
    }

    // Close modal when clicking outside dialog
    document.addEventListener('click', function (e) {
        const backdrop = document.getElementById('modal-edit-ophours');
        if (e.target === backdrop) {
            closeOpModal('modal-edit-ophours');
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php
// admin/export.php
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

// Handle export trigger
if (isset($_GET['action']) && $_GET['action'] === 'export' && !empty($_GET['type']) && !empty($_GET['format'])) {
    $controller->exportData($_GET['type'], $_GET['format']);
    exit;
}

$pageTitle = 'Export Data';
$baseUrl = '../';

include __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top: 10px;">
    <div class="container" style="max-width: 100%; padding: 0;">

        <!-- Header -->
        <div style="margin-bottom: 28px;">
            <h1 style="font-size: 1.8rem; font-weight: 800; color: var(--navy); margin-bottom: 6px;">Ekspor Data Sistem</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Unduh salinan data sistem PadelClub ke format Excel (.xlsx), CSV (.csv), atau dokumen PDF (.pdf) untuk keperluan pelaporan atau analisis eksternal.</p>
        </div>

        <div class="dashboard-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr)); gap: 24px;">
            <!-- Bookings Card -->
            <div class="card" style="padding: 32px; display: flex; flex-direction: column; height: 100%;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <span class="material-symbols-outlined" style="font-size: 2.2rem; color: var(--blue); background: rgba(14, 165, 233, 0.08); padding: 10px; border-radius: var(--radius-md);">calendar_month</span>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--navy); margin: 0;">Data Booking</h3>
                </div>
                <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 24px; flex-grow: 1;">
                    Riwayat pemesanan lapangan oleh customer, berisi info jadwal sewa, harga sewa, dan status pemesanan.
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: auto;">
                    <a href="export.php?action=export&type=bookings&format=excel" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.88rem; padding: 12px;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem;">grid_on</span> Ekspor ke Excel
                    </a>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <a href="export.php?action=export&type=bookings&format=csv" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">text_snippet</span> CSV
                        </a>
                        <a href="export.php?action=export&type=bookings&format=pdf" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px; border-color: #EF4444; color: #EF4444;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">picture_as_pdf</span> PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Customers Card -->
            <div class="card" style="padding: 32px; display: flex; flex-direction: column; height: 100%;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <span class="material-symbols-outlined" style="font-size: 2.2rem; color: var(--blue); background: rgba(14, 165, 233, 0.08); padding: 10px; border-radius: var(--radius-md);">groups</span>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--navy); margin: 0;">Data Pelanggan</h3>
                </div>
                <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 24px; flex-grow: 1;">
                    Data akun pengguna yang terdaftar di sistem PadelClub, termasuk customer, kasir, dan administrator.
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: auto;">
                    <a href="export.php?action=export&type=users&format=excel" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.88rem; padding: 12px;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem;">grid_on</span> Ekspor ke Excel
                    </a>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <a href="export.php?action=export&type=users&format=csv" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">text_snippet</span> CSV
                        </a>
                        <a href="export.php?action=export&type=users&format=pdf" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px; border-color: #EF4444; color: #EF4444;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">picture_as_pdf</span> PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Courts Card -->
            <div class="card" style="padding: 32px; display: flex; flex-direction: column; height: 100%;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <span class="material-symbols-outlined" style="font-size: 2.2rem; color: var(--blue); background: rgba(14, 165, 233, 0.08); padding: 10px; border-radius: var(--radius-md);">sports_tennis</span>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--navy); margin: 0;">Data Lapangan</h3>
                </div>
                <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 24px; flex-grow: 1;">
                    Daftar informasi lapangan yang dikelola oleh klub, meliputi tipe lapangan (Indoor/Outdoor) dan tarif dasar per jam.
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: auto;">
                    <a href="export.php?action=export&type=courts&format=excel" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.88rem; padding: 12px;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem;">grid_on</span> Ekspor ke Excel
                    </a>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <a href="export.php?action=export&type=courts&format=csv" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">text_snippet</span> CSV
                        </a>
                        <a href="export.php?action=export&type=courts&format=pdf" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px; border-color: #EF4444; color: #EF4444;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">picture_as_pdf</span> PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Payments Card -->
            <div class="card" style="padding: 32px; display: flex; flex-direction: column; height: 100%;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <span class="material-symbols-outlined" style="font-size: 2.2rem; color: var(--blue); background: rgba(14, 165, 233, 0.08); padding: 10px; border-radius: var(--radius-md);">payments</span>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--navy); margin: 0;">Data Pembayaran</h3>
                </div>
                <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 24px; flex-grow: 1;">
                    Laporan detail transaksi pembayaran uang sewa lapangan, status verifikasi, nomor struk, dan pencatatan kasir.
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: auto;">
                    <a href="export.php?action=export&type=payments&format=excel" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.88rem; padding: 12px;">
                        <span class="material-symbols-outlined" style="font-size:1.15rem;">grid_on</span> Ekspor ke Excel
                    </a>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <a href="export.php?action=export&type=payments&format=csv" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">text_snippet</span> CSV
                        </a>
                        <a href="export.php?action=export&type=payments&format=pdf" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.8rem; padding: 10px; border-color: #EF4444; color: #EF4444;">
                            <span class="material-symbols-outlined" style="font-size:1rem;">picture_as_pdf</span> PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

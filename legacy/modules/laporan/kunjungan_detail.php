<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$kunjunganId = (int) ($_GET['id'] ?? 0);
if (!$kunjunganId) {
    die("ID Kunjungan tidak valid.");
}

$stmt = db()->prepare(
    "SELECT k.*, p.no_mr, p.nama AS pasien, p.jenis_kelamin, p.tgl_lahir, p.alergi, p.no_passport, p.gol_darah,
            po.nama AS poli, d.nama AS dokter,
            a.nama AS asuransi, c.nama AS corporate
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     LEFT JOIN asuransi a ON a.id = k.asuransi_id
     LEFT JOIN corporate c ON c.id = k.corporate_id
     WHERE k.id = ?"
);
$stmt->execute([$kunjunganId]);
$kj = $stmt->fetch();

if (!$kj) {
    die("Kunjungan tidak ditemukan.");
}

$pageTitle = 'Detail Kunjungan ' . e($kj['no_kunjungan']);
require_once __DIR__ . '/../../includes/header.php';

// Fetch Related Data
$rmId = db()->query("SELECT id FROM rekam_medis WHERE kunjungan_id = $kunjunganId")->fetchColumn();
$tindakan = $rmId ? db()->query("SELECT * FROM rm_tindakan WHERE rekam_medis_id = $rmId")->fetchAll() : [];

$labOrders = db()->query("SELECT lod.*, lp.nama FROM lab_order_detail lod JOIN lab_order lo ON lo.id = lod.lab_order_id JOIN lab_pemeriksaan lp ON lp.id = lod.pemeriksaan_id WHERE lo.kunjungan_id = $kunjunganId")->fetchAll();
$radOrders = db()->query("SELECT rod.*, rp.nama FROM rad_order_detail rod JOIN rad_order ro ON ro.id = rod.rad_order_id JOIN rad_pemeriksaan rp ON rp.id = rod.pemeriksaan_id WHERE ro.kunjungan_id = $kunjunganId")->fetchAll();
$resep = db()->query("SELECT rd.*, o.nama FROM resep_detail rd JOIN resep r ON r.id = rd.resep_id JOIN obat o ON o.id = rd.obat_id WHERE r.kunjungan_id = $kunjunganId")->fetchAll();

$umur = $kj['tgl_lahir'] ? (int) ((time() - strtotime($kj['tgl_lahir'])) / 31556952) . ' th' : '-';
?>

<div class="page-toolbar no-print">
  <div>
    <div class="pt-title">Detail Kunjungan</div>
    <div class="pt-sub">Informasi lengkap mengenai pendaftaran dan layanan kunjungan</div>
  </div>
  <div class="pt-actions">
    <a class="btn-back" href="<?= legacy_url('modules/laporan/index.php?jenis=kunjungan') ?>"><?= app_icon('chevron') ?> Kembali ke Laporan</a>
    <button type="button" class="btn btn-light" onclick="window.print()"><?= app_icon('print') ?> Cetak</button>
  </div>
</div>

<div style="margin-top:18px; display:flex; flex-direction:column; gap:14px; width:100%;">
    
    <div style="display:flex; gap:14px;">
        <!-- Detail Pasien -->
        <div class="card" style="flex:1;">
            <h3 style="margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">Identitas Pasien</h3>
            <table class="table-compact" style="width:100%;">
                <tr>
                    <td style="width:130px; color:var(--muted);">No. MR</td><td><b><?= e($kj['no_mr']) ?></b></td>
                    <td style="width:130px; color:var(--muted);">No. Passport/Kitas</td><td><?= e($kj['no_passport'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td style="color:var(--muted);">Nama Pasien</td><td><b><?= e($kj['pasien']) ?></b></td>
                    <td style="color:var(--muted);">Gol. Darah</td><td><?= e($kj['gol_darah'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td style="color:var(--muted);">Jenis Kelamin</td><td><?= $kj['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                    <td style="color:var(--muted);">Umur</td><td><?= $umur ?></td>
                </tr>
                <tr>
                    <td style="color:var(--muted);">Tanggal Lahir</td><td><?= tgl_id($kj['tgl_lahir']) ?></td>
                    <td style="color:var(--muted);">Alergi</td>
                    <td>
                        <?php if (!empty($kj['alergi'])): ?>
                            <span class="badge badge-red"><?= e($kj['alergi']) ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Info Pendaftaran -->
        <div class="card" style="flex:1;">
            <h3 style="margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">Informasi Pendaftaran</h3>
            <table class="table-compact" style="width:100%;">
                <tr>
                    <td style="width:120px; color:var(--muted);">No. Kunjungan</td><td><b><?= e($kj['no_kunjungan']) ?></b></td>
                    <td style="width:100px; color:var(--muted);">Tanggal</td><td><?= tgl_id($kj['tgl_kunjungan']) ?></td>
                </tr>
                <tr>
                    <td style="color:var(--muted);">Tujuan Poli</td><td><?= e($kj['poli']) ?></td>
                    <td style="color:var(--muted);">Dokter</td><td><?= e($kj['dokter'] ?? 'Belum ditentukan') ?></td>
                </tr>
                <tr>
                    <td style="color:var(--muted);">Penjamin</td>
                    <td>
                        <span class="badge badge-gray"><?= e(strtoupper($kj['jenis_penjamin'])) ?></span>
                        <?php 
                            if ($kj['jenis_penjamin'] === 'asuransi') echo ' - ' . e($kj['asuransi']);
                            if ($kj['jenis_penjamin'] === 'corporate') echo ' - ' . e($kj['corporate']);
                        ?>
                    </td>
                    <td style="color:var(--muted);">No. Jaminan</td><td><?= e($kj['no_jaminan'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td style="color:var(--muted);">Keluhan Awal</td><td colspan="3"><?= nl2br(e($kj['keluhan_awal'] ?? '-')) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div style="display:flex; gap:14px;">
        <!-- Permintaan Lab -->
        <div class="card" style="flex:1;">
            <h3 style="margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">Permintaan Laboratorium</h3>
            <?php if (!$labOrders): ?>
                <p style="color:var(--muted); font-size:0.9em;">Tidak ada permintaan lab.</p>
            <?php else: ?>
                <table class="simple-table" style="width:100%; font-size:0.95em;">
                    <thead><tr><th style="width:45%;">Pemeriksaan</th><th style="text-align:center; width:10%;">Qty</th><th style="width:45%; text-align:center;">Hasil</th></tr></thead>
                    <tbody>
                        <?php foreach ($labOrders as $l): ?>
                            <tr><td><?= e($l['nama']) ?></td><td style="text-align:center;"><?= (int)$l['qty'] ?></td><td style="text-align:center;"><?= e($l['hasil'] ?: '-') ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Permintaan Radiologi -->
        <div class="card" style="flex:1;">
            <h3 style="margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">Permintaan Radiologi</h3>
            <?php if (!$radOrders): ?>
                <p style="color:var(--muted); font-size:0.9em;">Tidak ada permintaan radiologi.</p>
            <?php else: ?>
                <table class="simple-table" style="width:100%; font-size:0.95em;">
                    <thead><tr><th style="width:45%;">Pemeriksaan</th><th style="text-align:center; width:10%;">Qty</th><th style="width:45%; text-align:center;">Hasil</th></tr></thead>
                    <tbody>
                        <?php foreach ($radOrders as $r): ?>
                            <tr><td><?= e($r['nama']) ?></td><td style="text-align:center;"><?= (int)$r['qty'] ?></td><td style="text-align:center;"><?= e($r['hasil'] ?: '-') ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:flex; gap:14px;">
        <!-- Medical Service -->
        <div class="card" style="flex:1;">
            <h3 style="margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">Medical Service (Tindakan)</h3>
            <?php if (!$tindakan): ?>
                <p style="color:var(--muted); font-size:0.9em;">Tidak ada medical service yang didaftarkan.</p>
            <?php else: ?>
                <table class="simple-table" style="width:100%; font-size:0.95em;">
                    <thead><tr><th style="width:45%;">Nama Layanan</th><th style="text-align:center; width:10%;">Qty</th><th style="width:45%;"></th></tr></thead>
                    <tbody>
                        <?php foreach ($tindakan as $t): ?>
                            <tr><td><?= e($t['nama_tindakan']) ?></td><td style="text-align:center;"><?= (int)$t['qty'] ?></td><td></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Resep Obat -->
        <div class="card" style="flex:1;">
            <h3 style="margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:8px;">Resep Obat</h3>
            <?php if (!$resep): ?>
                <p style="color:var(--muted); font-size:0.9em;">Tidak ada resep obat yang didaftarkan.</p>
            <?php else: ?>
                <table class="simple-table" style="width:100%; font-size:0.95em;">
                    <thead><tr><th>Nama Obat</th><th>Aturan Pakai</th><th style="text-align:center; width:60px;">Qty</th></tr></thead>
                    <tbody>
                        <?php foreach ($resep as $r): ?>
                            <tr>
                                <td><?= e($r['nama']) ?></td>
                                <td><?= e($r['dosis']) ?> <?= e($r['aturan_pakai']) ?></td>
                                <td style="text-align:center;"><?= (int)$r['qty'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top:20px;text-align:right" class="no-print">
        <a href="<?= legacy_url('modules/billing/proses.php?kunjungan_id=' . $kunjunganId) ?>" class="btn btn-green">
            <?= app_icon('billing') ?> Proses Billing
        </a>
    </div>

</div>

<style>
.table-compact td { padding: 4px 8px; vertical-align: top; }
.simple-table { border-collapse: collapse; }
.simple-table th { padding: 10px 8px; text-align: left; border-bottom: 2px solid var(--border); color: var(--muted); text-transform: uppercase; font-size: 0.85em; font-weight: 600; }
.simple-table td { padding: 10px 8px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.simple-table tr:last-child td { border-bottom: none; }
@media print{
  .sidebar,.topbar,.footer,.no-print {display:none !important}
  .main{margin-left:0; padding:0;}
  body {background:#fff;}
  .card {box-shadow:none; border:1px solid #ddd; margin-bottom:10px; page-break-inside: avoid;}
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

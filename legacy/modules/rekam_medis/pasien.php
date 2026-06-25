<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('dokter');
$pageTitle = 'Rekam Medis Pasien';

$pasienId = (int) ($_GET['pasien_id'] ?? 0);
$p = db()->prepare("SELECT p.*, kp.nama AS kelompok FROM pasien p
     LEFT JOIN kelompok_pasien kp ON kp.id = p.kelompok_id WHERE p.id = ?");
$p->execute([$pasienId]);
$p = $p->fetch();
if (!$p) { set_flash('danger', 'Pasien tidak ditemukan.'); legacy_redirect('modules/rekam_medis/index.php'); }

// Daftar kunjungan + diagnosa primer
$kunjungan = db()->prepare(
    "SELECT k.id, k.no_kunjungan, k.tgl_kunjungan, k.status, po.nama AS poli, d.nama AS dokter,
            (SELECT GROUP_CONCAT(rd.diagnosa SEPARATOR ', ')
             FROM rm_diagnosa rd JOIN rekam_medis rm ON rm.id = rd.rekam_medis_id
             WHERE rm.kunjungan_id = k.id) AS diagnosa
     FROM kunjungan k
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.pasien_id = ?
     ORDER BY k.tgl_kunjungan DESC, k.id DESC");
$kunjungan->execute([$pasienId]);
$kunjungan = $kunjungan->fetchAll();

// Riwayat diagnosa unik (ringkasan)
$riwayatDiag = db()->prepare(
    "SELECT DISTINCT rd.kode_icd10, rd.diagnosa
     FROM rm_diagnosa rd JOIN rekam_medis rm ON rm.id = rd.rekam_medis_id
     JOIN kunjungan k ON k.id = rm.kunjungan_id
     WHERE k.pasien_id = ? ORDER BY rd.diagnosa");
$riwayatDiag->execute([$pasienId]);
$riwayatDiag = $riwayatDiag->fetchAll();

$umur = $p['tgl_lahir'] ? (int) ((time() - strtotime($p['tgl_lahir'])) / 31556952) . ' tahun' : '-';
$badge = ['menunggu' => 'badge-orange', 'periksa' => 'badge-blue', 'penunjang' => 'badge-blue',
    'farmasi' => 'badge-blue', 'billing' => 'badge-blue', 'pembayaran' => 'badge-orange',
    'selesai' => 'badge-green', 'batal' => 'badge-red'];

require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/rekam_medis/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Daftar Pasien</a>

<!-- Identitas -->
<div class="card" style="margin-top:14px">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:var(--fs-title);font-weight:700"><?= e($p['nama']) ?></div>
      <div style="color:var(--muted);margin-top:4px">
        No. MR <b><?= e($p['no_mr']) ?></b> &middot; <?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
        &middot; <?= $umur ?> &middot; <?= e($p['kelompok'] ?? 'Umum') ?>
      </div>
      <div style="color:var(--muted)">
        <?= tgl_id($p['tgl_lahir']) ?> &middot; <?= e($p['telepon'] ?? '-') ?>
        <?php if ($p['gol_darah'] && $p['gol_darah'] !== '-'): ?> &middot; Gol. Darah <?= e($p['gol_darah']) ?><?php endif; ?>
      </div>
      <?php if (!empty($p['alamat'])): ?><div style="color:var(--muted)"><?= e($p['alamat']) ?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
      <?php if (!empty($p['alergi'])): ?><span class="badge badge-red"><?= app_icon("alert") ?> Alergi: <?= e($p['alergi']) ?></span><?php endif; ?>
    </div>
  </div>
</div>

<!-- Riwayat diagnosa ringkas -->
<?php if ($riwayatDiag): ?>
<div class="card" style="margin-top:14px">
  <h3 style="margin-bottom:10px">Riwayat Diagnosa</h3>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach ($riwayatDiag as $d): ?>
      <span class="badge badge-blue"><?= $d['kode_icd10'] ? e($d['kode_icd10']) . ' — ' : '' ?><?= e($d['diagnosa']) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Daftar kunjungan -->
<div class="section-title">Riwayat Kunjungan (<?= count($kunjungan) ?>)</div>
<div class="table-wrap">
  <table class="datatable dt-noscroll" style="width:100%">
    <thead>
      <tr><th>Tanggal</th><th>No. Kunjungan</th><th>Poli</th><th>Dokter</th><th>Diagnosa</th><th>Status</th><th class="col-actions">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($kunjungan as $k): ?>
        <tr>
          <td><?= tgl_id($k['tgl_kunjungan']) ?></td>
          <td><?= e($k['no_kunjungan']) ?></td>
          <td><?= e($k['poli']) ?></td>
          <td><?= e($k['dokter'] ?? '-') ?></td>
          <td><?= e($k['diagnosa'] ?? '-') ?></td>
          <td><span class="badge <?= $badge[$k['status']] ?? 'badge-gray' ?>"><?= e(ucfirst($k['status'])) ?></span></td>
          <td class="cell-actions"><div class="cell-actions-inner"><a class="btn btn-sm" href="<?= legacy_url('modules/rekam_medis/detail.php?kunjungan_id=' . $k['id']) ?>">Lihat Detail</a></div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

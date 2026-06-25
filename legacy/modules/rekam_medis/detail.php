<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('dokter', 'superadmin');
$pageTitle = 'Detail Rekam Medis';

$kunjunganId = (int) ($_GET['kunjungan_id'] ?? 0);
$kj = db()->prepare(
    "SELECT k.*, p.no_mr, p.nama AS pasien, p.jenis_kelamin, p.tgl_lahir, p.alergi,
            po.nama AS poli, d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.id = ?");
$kj->execute([$kunjunganId]);
$kj = $kj->fetch();
if (!$kj) { set_flash('danger', 'Kunjungan tidak ditemukan.'); legacy_redirect('modules/rekam_medis/index.php'); }

$rm = db()->prepare("SELECT * FROM rekam_medis WHERE kunjungan_id=?");
$rm->execute([$kunjunganId]); $rm = $rm->fetch() ?: [];
$rmId = $rm['id'] ?? 0;

$diag = $tind = [];
if ($rmId) {
    $s = db()->prepare("SELECT * FROM rm_diagnosa WHERE rekam_medis_id=?"); $s->execute([$rmId]); $diag = $s->fetchAll();
    $s = db()->prepare("SELECT * FROM rm_tindakan WHERE rekam_medis_id=?"); $s->execute([$rmId]); $tind = $s->fetchAll();
}
$lab = db()->prepare("SELECT lp.nama, lod.hasil, lod.nilai_rujukan, lod.qty FROM lab_order_detail lod
    JOIN lab_order lo ON lo.id=lod.lab_order_id JOIN lab_pemeriksaan lp ON lp.id=lod.pemeriksaan_id
    WHERE lo.kunjungan_id=?"); $lab->execute([$kunjunganId]); $lab = $lab->fetchAll();
$rad = db()->prepare("SELECT rp.nama, rod.hasil, rod.qty FROM rad_order_detail rod
    JOIN rad_order ro ON ro.id=rod.rad_order_id JOIN rad_pemeriksaan rp ON rp.id=rod.pemeriksaan_id
    WHERE ro.kunjungan_id=?"); $rad->execute([$kunjunganId]); $rad = $rad->fetchAll();
$resep = db()->prepare("SELECT o.nama, rd.qty, rd.dosis, rd.aturan_pakai FROM resep_detail rd
    JOIN resep r ON r.id=rd.resep_id JOIN obat o ON o.id=rd.obat_id WHERE r.kunjungan_id=?");
$resep->execute([$kunjunganId]); $resep = $resep->fetchAll();

$umur = $kj['tgl_lahir'] ? (int) ((time() - strtotime($kj['tgl_lahir'])) / 31556952) . ' th' : '-';
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center">
  <a href="<?= legacy_url('modules/rekam_medis/pasien.php?pasien_id=' . $kj['pasien_id']) ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Riwayat Pasien</a>
  <button class="btn btn-light btn-sm" onclick="window.print()"><?= app_icon("print") ?> Cetak</button>
</div>

<div class="card" style="margin-top:14px">
  <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:var(--fs-sub);font-weight:700"><?= e($kj['pasien']) ?></div>
      <div style="color:var(--muted)">No. MR <b><?= e($kj['no_mr']) ?></b> &middot; <?= $kj['jenis_kelamin'] === 'L' ? 'L' : 'P' ?> &middot; <?= $umur ?></div>
      <div style="color:var(--muted)"><?= tgl_id($kj['tgl_kunjungan']) ?> &middot; <?= e($kj['poli']) ?> &middot; <?= e($kj['dokter'] ?? '-') ?></div>
    </div>
    <div style="text-align:right">
      <span class="badge badge-blue"><?= e($kj['no_kunjungan']) ?></span>
      <?php if (!empty($kj['alergi'])): ?><br><span class="badge badge-red" style="margin-top:6px"><?= app_icon("alert") ?> Alergi: <?= e($kj['alergi']) ?></span><?php endif; ?>
    </div>
  </div>
</div>

<?php if (!$rmId): ?>
  <div class="alert alert-warning" style="margin-top:14px">Belum ada rekam medis untuk kunjungan ini (pasien belum diperiksa).</div>
<?php else: ?>

<!-- Vital sign -->
<div class="card" style="margin-top:14px">
  <h3 style="margin-bottom:10px">Tanda Vital</h3>
  <div style="display:flex;flex-wrap:wrap;gap:24px;color:var(--muted)">
    <div>Tekanan Darah<br><b style="color:var(--text);font-size:16px"><?= e($rm['tekanan_darah'] ?: '-') ?></b></div>
    <div>Suhu<br><b style="color:var(--text);font-size:16px"><?= e($rm['suhu'] ?: '-') ?> °C</b></div>
    <div>Nadi<br><b style="color:var(--text);font-size:16px"><?= e($rm['nadi'] ?: '-') ?> x/mnt</b></div>
    <div>Berat<br><b style="color:var(--text);font-size:16px"><?= e($rm['berat_badan'] ?: '-') ?> kg</b></div>
    <div>Tinggi<br><b style="color:var(--text);font-size:16px"><?= e($rm['tinggi_badan'] ?: '-') ?> cm</b></div>
  </div>
</div>

<!-- SOAP -->
<div class="card" style="margin-top:14px">
  <h3 style="margin-bottom:10px">Rekam Medis (SOAP)</h3>
  <div class="form-row">
    <div><b>S — Subjective</b><p style="color:var(--muted);white-space:pre-line"><?= e($rm['subjective'] ?: '-') ?></p></div>
    <div><b>O — Objective</b><p style="color:var(--muted);white-space:pre-line"><?= e($rm['objective'] ?: '-') ?></p></div>
  </div>
  <div class="form-row" style="margin-top:10px">
    <div><b>A — Assessment</b><p style="color:var(--muted);white-space:pre-line"><?= e($rm['assessment'] ?: '-') ?></p></div>
    <div><b>P — Plan</b><p style="color:var(--muted);white-space:pre-line"><?= e($rm['plan'] ?: '-') ?></p></div>
  </div>
  <?php if (!empty($rm['edukasi'])): ?><div style="margin-top:10px"><b>Edukasi</b><p style="color:var(--muted)"><?= e($rm['edukasi']) ?></p></div><?php endif; ?>
</div>

<div class="form-row" style="margin-top:14px">
  <!-- Diagnosa -->
  <div class="card">
    <h3 style="margin-bottom:10px">Diagnosa (ICD-10)</h3>
    <?php if (!$diag): ?><p style="color:var(--muted)">-</p><?php else: foreach ($diag as $d): ?>
      <div style="padding:6px 0;border-bottom:1px solid var(--border)">
        <span class="badge <?= $d['jenis'] === 'primer' ? 'badge-blue' : 'badge-gray' ?>"><?= e(ucfirst($d['jenis'])) ?></span>
        <?= $d['kode_icd10'] ? '<code>' . e($d['kode_icd10']) . '</code> ' : '' ?><?= e($d['diagnosa']) ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <!-- Tindakan -->
  <div class="card">
    <h3 style="margin-bottom:10px">Medical Service (ICD-9-CM)</h3>
    <?php if (!$tind): ?><p style="color:var(--muted)">-</p><?php else: foreach ($tind as $t): ?>
      <div style="padding:6px 0;border-bottom:1px solid var(--border)"><?= e($t['nama_tindakan']) ?> <span style="color:var(--muted)">x<?= (int)$t['qty'] ?></span></div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="form-row" style="margin-top:14px">
  <!-- Lab -->
  <div class="card">
    <h3 style="margin-bottom:10px">Hasil Laboratorium</h3>
    <?php if (!$lab): ?><p style="color:var(--muted)">-</p><?php else: ?>
      <table style="width:100%"><thead><tr><th>Pemeriksaan</th><th>Hasil</th><th>Rujukan</th></tr></thead><tbody>
      <?php foreach ($lab as $l): ?><tr><td><?= e($l['nama']) ?><?php if ((int)$l['qty'] > 1): ?> <span style="color:var(--muted)">x<?= (int)$l['qty'] ?></span><?php endif; ?></td><td><b><?= e($l['hasil'] ?: '-') ?></b></td><td style="color:var(--muted)"><?= e($l['nilai_rujukan'] ?: '-') ?></td></tr><?php endforeach; ?>
      </tbody></table>
    <?php endif; ?>
  </div>
  <!-- Radiologi -->
  <div class="card">
    <h3 style="margin-bottom:10px">Hasil Radiologi</h3>
    <?php if (!$rad): ?><p style="color:var(--muted)">-</p><?php else: foreach ($rad as $r): ?>
      <div style="padding:6px 0;border-bottom:1px solid var(--border)"><b><?= e($r['nama']) ?></b><?php if ((int)$r['qty'] > 1): ?> <span style="color:var(--muted)">x<?= (int)$r['qty'] ?></span><?php endif; ?><br><span style="color:var(--muted)"><?= e($r['hasil'] ?: '-') ?></span></div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Resep -->
<div class="card" style="margin-top:14px">
  <h3 style="margin-bottom:10px">Resep Obat</h3>
  <?php if (!$resep): ?><p style="color:var(--muted)">-</p><?php else: ?>
    <table style="width:100%"><thead><tr><th>Obat</th><th>Qty</th><th>Dosis</th><th>Aturan Pakai</th></tr></thead><tbody>
    <?php foreach ($resep as $r): ?><tr><td><?= e($r['nama']) ?></td><td><?= (int)$r['qty'] ?></td><td><?= e($r['dosis'] ?: '-') ?></td><td><?= e($r['aturan_pakai'] ?: '-') ?></td></tr><?php endforeach; ?>
    </tbody></table>
  <?php endif; ?>
</div>

<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

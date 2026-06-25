<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi');
$pageTitle = 'Antrian Farmasi';

$rows = db()->query(
    "SELECT k.id AS kunjungan_id, k.no_kunjungan, k.no_antrian, k.tgl_kunjungan,
            p.no_mr, p.nama AS pasien, po.kode AS poli_kode, po.nama AS poli,
            r.id AS resep_id, r.status AS resep_status,
            (SELECT COUNT(*) FROM resep_detail rd WHERE rd.resep_id=r.id) AS jml_obat
     FROM resep r
     JOIN kunjungan k ON k.id = r.kunjungan_id
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     WHERE k.status='farmasi' AND r.status IN ('baru','disiapkan')
     ORDER BY k.tgl_kunjungan, k.no_antrian")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= app_icon("pills") ?> Antrian Farmasi</div>
    <div class="pt-sub"><?= count($rows) ?> resep menunggu penyerahan</div>
  </div>
  <!-- <div class="pt-actions">
    <a class="btn btn-light" href="<?= legacy_url('modules/pelayanan/index.php') ?>"><?= app_icon("pelayanan") ?> Antrian Pemeriksaan</a>
  </div> -->
</div>

<div class="table-wrap" style="margin-top:18px">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th>Antrian</th><th>No. MR</th><th>Pasien</th><th>Poli</th><th>Jml Obat</th><th>Status</th><th class="col-actions">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['poli_kode']) ?>-<?= str_pad($r['no_antrian'], 3, '0', STR_PAD_LEFT) ?></b></td>
          <td><?= e($r['no_mr']) ?></td>
          <td><?= e($r['pasien']) ?></td>
          <td><?= e($r['poli']) ?></td>
          <td><?= (int) $r['jml_obat'] ?> item</td>
          <td><span class="badge badge-orange"><?= e(ucfirst($r['resep_status'])) ?></span></td>
          <td class="cell-actions"><div class="cell-actions-inner"><a class="btn btn-sm" href="<?= legacy_url('modules/pelayanan/farmasi_serah.php?resep_id=' . $r['resep_id']) ?>">Siapkan &amp; Serahkan</a></div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('registrasi', 'admin', 'superadmin');
$pageTitle = 'Registrasi';

$tgl = $_GET['tgl'] ?? date('Y-m-d');

$stmt = db()->prepare(
    "SELECT k.id, k.no_kunjungan, k.no_antrian, k.status, k.jenis_penjamin,
            p.no_mr, p.nama AS pasien, po.kode AS poli_kode, po.nama AS poli,
            d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.tgl_kunjungan = ?
     ORDER BY k.id DESC");
$stmt->execute([$tgl]);
$rows = $stmt->fetchAll();

$badgeMap = [
    'menunggu' => 'badge-orange', 'periksa' => 'badge-blue', 'penunjang' => 'badge-blue',
    'farmasi'  => 'badge-blue', 'billing' => 'badge-blue', 'pembayaran' => 'badge-orange',
    'selesai'  => 'badge-green', 'batal' => 'badge-red',
];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title">Registrasi Kunjungan</div>
    <div class="pt-sub"><?= tgl_id($tgl) ?> &middot; <?= count($rows) ?> kunjungan</div>
  </div>
  <div class="pt-actions">
    <form method="get" class="toolbar-filter">
      <span class="ico"><?= app_icon('calendar') ?></span>
      <input type="date" name="tgl" value="<?= e($tgl) ?>" class="form-control" onchange="this.form.submit()">
    </form>
    <a class="btn" href="<?= legacy_url('modules/registrasi/daftar.php') ?>"><?= app_icon('plus') ?> Pendaftaran Baru</a>
  </div>
</div>

<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th>Antrian</th><th>No. Kunjungan</th><th>No. MR</th><th>Pasien</th>
          <th>Poli</th><th>Dokter</th><th>Penjamin</th><th>Status</th><th class="col-actions">Aksi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['poli_kode']) ?>-<?= str_pad($r['no_antrian'], 3, '0', STR_PAD_LEFT) ?></b></td>
          <td><?= e($r['no_kunjungan']) ?></td>
          <td><?= e($r['no_mr']) ?></td>
          <td><?= e($r['pasien']) ?></td>
          <td><?= e($r['poli']) ?></td>
          <td><?= e($r['dokter'] ?? '-') ?></td>
          <td><span class="badge badge-gray"><?= e(strtoupper($r['jenis_penjamin'])) ?></span></td>
          <td><span class="badge <?= $badgeMap[$r['status']] ?? 'badge-gray' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
          <td class="cell-actions"><div class="cell-actions-inner"><a class="btn btn-sm btn-light" href="<?= legacy_url('modules/registrasi/cetak_antrian.php?id=' . $r['id']) ?>" target="_blank"><?= app_icon('print') ?> Kartu</a></div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

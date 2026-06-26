<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('registrasi', 'admin', 'superadmin');
$pageTitle = t('pages.registration');

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
    <div class="pt-title"><?= e(t('pages.registration_visits')) ?></div>
    <div class="pt-sub"><?= tgl_id($tgl) ?> &middot; <?= e(t('pages.visits_count', ['count' => count($rows)])) ?></div>
  </div>
  <div class="pt-actions">
    <form method="get" class="toolbar-filter">
      <span class="ico"><?= app_icon('calendar') ?></span>
      <input type="date" name="tgl" value="<?= e($tgl) ?>" class="form-control" onchange="this.form.submit()">
    </form>
    <a class="btn" href="<?= legacy_url('modules/registrasi/daftar.php') ?>"><?= app_icon('plus') ?> <?= e(t('common.new_registration_btn')) ?></a>
  </div>
</div>

<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th><?= e(t('common.queue')) ?></th><th><?= e(t('common.visit_no')) ?></th><th><?= e(t('common.mr_no')) ?></th><th><?= e(t('app.patient')) ?></th>
          <th><?= e(t('app.poli')) ?></th><th><?= e(t('app.doctor')) ?></th><th><?= e(t('common.insurance')) ?></th><th><?= e(t('app.status')) ?></th><th class="col-actions"><?= e(t('common.action')) ?></th></tr>
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
          <td><span class="badge <?= $badgeMap[$r['status']] ?? 'badge-gray' ?>"><?= e(status_label($r['status'])) ?></span></td>
          <td class="cell-actions"></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

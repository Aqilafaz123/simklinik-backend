<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('dokter', 'farmasi');

if (current_role() === 'farmasi') {
    legacy_redirect('modules/pelayanan/farmasi.php');
}

$pageTitle = t('pages.service');
$tgl = $_GET['tgl'] ?? date('Y-m-d');

$poliId = current_role() === 'dokter' ? current_poli_id() : null;

$sql =
    "SELECT k.id, k.no_kunjungan, k.no_antrian, k.status, k.keluhan_awal,
            p.no_mr, p.nama AS pasien, p.alergi, po.kode AS poli_kode, po.nama AS poli, d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.tgl_kunjungan = ? AND k.status IN ('menunggu','periksa','penunjang')";
$params = [$tgl];
if ($poliId) { $sql .= " AND k.poli_id = ?"; $params[] = $poliId; }
$sql .= " ORDER BY po.nama, k.no_antrian";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$badge = ['menunggu' => 'badge-orange', 'periksa' => 'badge-blue', 'penunjang' => 'badge-blue'];

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e(t('pages.service')) ?></div>
    <div class="pt-sub"><?= tgl_id($tgl) ?> &middot; <?= e(t('common.exam_queue_count', ['count' => count($rows)])) ?></div>
  </div>
  <div class="pt-actions">
    <form method="get" class="toolbar-filter">
      <span class="ico"><?= app_icon('calendar') ?></span>
      <input type="date" name="tgl" value="<?= e($tgl) ?>" class="form-control" onchange="this.form.submit()">
    </form>
  </div>
</div>

<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th><?= e(t('common.queue')) ?></th><th><?= e(t('common.mr_no')) ?></th><th><?= e(t('app.patient')) ?></th><th><?= e(t('app.poli')) ?></th><th><?= e(t('app.doctor')) ?></th>
          <th><?= e(t('common.complaint')) ?></th><th><?= e(t('app.status')) ?></th><th class="col-actions"><?= e(t('common.action')) ?></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['poli_kode']) ?>-<?= str_pad($r['no_antrian'], 3, '0', STR_PAD_LEFT) ?></b></td>
          <td><?= e($r['no_mr']) ?></td>
          <td><?= e($r['pasien']) ?>
            <?php if (!empty($r['alergi'])): ?><br><span class="badge badge-red"><?= e(t('common.allergy')) ?>: <?= e($r['alergi']) ?></span><?php endif; ?>
          </td>
          <td><?= e($r['poli']) ?></td>
          <td><?= e($r['dokter'] ?? '-') ?></td>
          <td><?= e($r['keluhan_awal'] ?? '-') ?></td>
          <td><span class="badge <?= $badge[$r['status']] ?? 'badge-gray' ?>"><?= e(status_label($r['status'])) ?></span></td>
          <td class="cell-actions"><div class="cell-actions-inner"><a class="btn btn-sm" href="<?= legacy_url('modules/pelayanan/periksa.php?kunjungan_id=' . $r['id']) ?>"><?= app_icon('pelayanan') ?> <?= e(t('common.examine')) ?></a></div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

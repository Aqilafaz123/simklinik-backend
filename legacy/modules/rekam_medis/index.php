<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('dokter', 'superadmin');
$pageTitle = t('pages.medical_records');

$rows = db()->query(
    "SELECT p.id, p.no_mr, p.nama, p.jenis_kelamin, p.tgl_lahir, p.telepon, p.alergi,
            COUNT(k.id) AS jml, MAX(k.tgl_kunjungan) AS last_visit
     FROM pasien p
     LEFT JOIN kunjungan k ON k.pasien_id = p.id
     GROUP BY p.id ORDER BY p.nama")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e(t('pages.medical_records_patients')) ?></div>
    <div class="pt-sub"><?= e(t('pages.patients_count', ['count' => count($rows)])) ?></div>
  </div>
</div>

<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th><?= e(t('common.mr_no')) ?></th><th><?= e(t('common.patient_name')) ?></th><th><?= e(t('common.gender')) ?></th><th style="text-align:left;"><?= e(t('common.phone')) ?></th><th><?= e(t('common.allergy')) ?></th>
          <th><?= e(t('common.visit_count')) ?></th><th><?= e(t('common.last_visit')) ?></th><th class="col-actions"><?= e(t('common.action')) ?></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td><b><?= e($p['no_mr']) ?></b></td>
          <td><?= e($p['nama']) ?></td>
          <td><?= $p['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
          <td><?= e($p['telepon'] ?? '-') ?></td>
          <td><?= !empty($p['alergi']) ? '<span class="badge badge-red">' . e($p['alergi']) . '</span>' : '-' ?></td>
          <td><?= (int) $p['jml'] ?></td>
          <td><?= $p['last_visit'] ? tgl_id($p['last_visit']) : '-' ?></td>
          <td class="cell-actions"><div class="cell-actions-inner"><a class="btn btn-sm" href="<?= legacy_url('modules/rekam_medis/pasien.php?pasien_id=' . $p['id']) ?>"><?= app_icon('rekam') ?> <?= e(t('common.view')) ?></a></div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

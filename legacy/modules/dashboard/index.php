<?php
require_once __DIR__ . '/../../includes/header.php';

$canPatientData = in_array(current_role(), ['superadmin', 'admin', 'registrasi'], true);
$pasienTerbaru = [];
if ($canPatientData) {
    $pasienTerbaru = db()->query(
        "SELECT p.id, p.no_mr, p.nama, p.jenis_kelamin, p.tgl_lahir, p.telepon, kp.nama AS kelompok
         FROM pasien p
         LEFT JOIN kelompok_pasien kp ON kp.id = p.kelompok_id
         ORDER BY p.id DESC LIMIT 10")->fetchAll();
}
?>
<?php if ($canPatientData): ?>
<div class="dash-section-head">
  <div class="section-title" style="margin:0"><?= e(t('menu.patient_data')) ?></div>
  <div class="dash-section-actions">
    <a class="btn btn-sm btn-light" href="<?= legacy_url('modules/registrasi/pasien.php') ?>"><?= e(t('common.view_all')) ?></a>
    <a class="btn" href="<?= legacy_url('modules/registrasi/pasien_form.php') ?>"><?= app_icon('plus') ?> <?= e(t('common.new_patient')) ?></a>
  </div>
</div>
<div class="table-wrap">
  <table class="datatable no-auto-num" style="width:100%">
    <thead>
      <tr>
        <th><?= e(t('common.mr_no')) ?></th>
        <th><?= e(t('common.name')) ?></th>
        <th><?= e(t('common.gender')) ?></th>
        <th><?= e(t('common.birth_date')) ?></th>
        <th><?= e(t('common.phone')) ?></th>
        <th><?= e(t('common.group')) ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pasienTerbaru as $p): ?>
        <tr>
          <td><b><?= e($p['no_mr']) ?></b></td>
          <td><?= e($p['nama']) ?></td>
          <td><?= $p['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
          <td><?= tgl_id($p['tgl_lahir']) ?></td>
          <td><?= e($p['telepon'] ?? '-') ?></td>
          <td><?= e($p['kelompok'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

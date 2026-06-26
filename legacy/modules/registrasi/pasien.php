<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('registrasi', 'admin', 'superadmin');
$pageTitle = t('pages.patient_data');

$rows = db()->query(
    "SELECT p.*, kp.nama AS kelompok FROM pasien p
     LEFT JOIN kelompok_pasien kp ON kp.id = p.kelompok_id
     ORDER BY p.nama")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e(t('pages.patient_data')) ?></div>
    <div class="pt-sub"><?= e(t('common.patients_registered', ['count' => count($rows)])) ?></div>
  </div>
  <div class="pt-actions">
    <a class="btn" href="<?= legacy_url('modules/registrasi/pasien_form.php') ?>"><?= app_icon('plus') ?> <?= e(t('common.new_patient')) ?></a>
  </div>
</div>

<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th><?= e(t('common.mr_no')) ?></th><th><?= e(t('common.name')) ?></th><th><?= e(t('common.gender')) ?></th><th><?= e(t('common.birth_date')) ?></th>
          <th><?= e(t('common.group')) ?></th><th class="no-sort col-actions col-actions-wide"><?= e(t('common.action')) ?></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $p): ?>
        <tr>
          <td><b><?= e($p['no_mr']) ?></b></td>
          <td><?= e($p['nama']) ?><?php if ($p['nik']): ?><br><small style="color:var(--muted)">NIK: <?= e($p['nik']) ?></small><?php endif; ?></td>
          <td><?= $p['jenis_kelamin'] === 'L' ? 'L' : 'P' ?></td>
          <td><?= tgl_id($p['tgl_lahir']) ?></td>

          <td><?= e($p['kelompok'] ?? '-') ?></td>
          <td class="cell-actions cell-actions-wide">
            <div class="cell-actions-inner">
            <a class="btn btn-sm btn-light btn-icon" href="<?= legacy_url('modules/registrasi/pasien_detail.php?id=' . $p['id']) ?>"
               data-modal-url="<?= legacy_url('modules/registrasi/pasien_detail.php?id=' . $p['id'] . '&modal=1') ?>"
               data-modal-title="<?= e(t('pages.patient_detail')) ?>" title="<?= e(t('common.view_detail')) ?>"><?= app_icon('eye') ?></a>
            <a class="btn btn-sm btn-light btn-icon" href="<?= legacy_url('modules/registrasi/pasien_form.php?id=' . $p['id']) ?>" title="<?= e(t('common.edit')) ?>"><?= app_icon('pencil') ?></a>
            <a class="btn btn-sm" href="<?= legacy_url('modules/registrasi/daftar.php?pasien_id=' . $p['id']) ?>"><?= e(t('common.register_visit')) ?></a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="modal-overlay" id="dataModal" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="dataModalTitle">
    <div class="modal-head">
      <div class="modal-title" id="dataModalTitle"><?= e(t('pages.patient_detail')) ?></div>
      <button type="button" class="modal-close" data-modal-close aria-label="<?= e(t('common.close')) ?>">&times;</button>
    </div>
    <div class="modal-body" id="dataModalBody"></div>
  </div>
</div>
<script>
(function () {
  var overlay = document.getElementById('dataModal');
  var box     = document.getElementById('dataModalBody');
  var titleEl = document.getElementById('dataModalTitle');
  var defaultTitle = <?= json_encode(t('pages.patient_detail'), JSON_UNESCAPED_UNICODE) ?>;
  var loadingHtml = <?= json_encode('<div class="modal-loading">' . t('common.loading') . '</div>', JSON_UNESCAPED_UNICODE) ?>;

  function open(url, title) {
    titleEl.textContent = title || defaultTitle;
    box.innerHTML = loadingHtml;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.text(); })
      .then(function (html) { box.innerHTML = html; });
  }
  function close() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    box.innerHTML = '';
  }

  document.addEventListener('click', function (ev) {
    var opener = ev.target.closest('[data-modal-url]');
    if (opener) { ev.preventDefault(); open(opener.getAttribute('data-modal-url'), opener.getAttribute('data-modal-title')); return; }
    if (ev.target.closest('[data-modal-close]')) close();
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && overlay.classList.contains('open')) close();
  });
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

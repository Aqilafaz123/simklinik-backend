<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('superadmin');
$pageTitle = t('pages.users_roles');
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    sim_csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === (int) $me['id']) {
        set_flash('danger', 'Tidak bisa menghapus akun yang sedang login.');
    } else {
        try {
            db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            set_flash('success', 'Pengguna dihapus.');
        } catch (Throwable $ex) {
            set_flash('danger', 'Tidak bisa dihapus karena data terkait transaksi.');
        }
    }
    legacy_redirect('modules/pengaturan/users.php');
}

$rows = db()->query(
    "SELECT u.*, r.kode AS role_kode, r.nama AS role_nama, po.nama AS poli_nama
     FROM users u JOIN roles r ON r.id=u.role_id
     LEFT JOIN poli po ON po.id=u.poli_id ORDER BY u.nama")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= app_icon("users") ?> <?= e(t('pages.users_roles')) ?></div>
    <div class="pt-sub"><?= e(t('common.users_registered', ['count' => count($rows)])) ?></div>
  </div>
  <div class="pt-actions">
    <a class="btn" href="<?= legacy_url('modules/pengaturan/user_form.php') ?>"
       data-modal-url="<?= legacy_url('modules/pengaturan/user_form.php?modal=1') ?>"
       data-modal-title="<?= e(t('common.add_user')) ?>"><?= app_icon("plus") ?> <?= e(t('common.add_user')) ?></a>
  </div>
</div>

<div class="table-wrap" style="margin-top:18px">
  <table class="datatable dt-noscroll" style="width:100%">
    <thead><tr><th><?= e(t('common.user_col')) ?></th><th><?= e(t('common.role_col')) ?></th><th><?= e(t('app.status')) ?></th><th><?= e(t('common.last_login')) ?></th><th class="no-sort col-actions"><?= e(t('common.action')) ?></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $u): $self = (int)$u['id'] === (int)$me['id']; ?>
        <tr>
          <td>
            <div class="cell-user">
              <span class="cell-avatar"><?= strtoupper(substr($u['nama'], 0, 1)) ?></span>
              <div>
                <div class="cu-name"><?= e($u['nama']) ?><?= $self ? ' <span class="badge badge-blue">' . e(t('common.you')) . '</span>' : '' ?></div>
                <div class="cu-sub">@<?= e($u['username']) ?></div>
              </div>
            </div>
          </td>
          <td><span class="badge badge-gray"><?= e($u['role_nama']) ?></span><?php if ($u['role_kode'] === 'dokter' && !empty($u['poli_nama'])): ?> <small style="color:var(--muted)"><?= e($u['poli_nama']) ?></small><?php endif; ?></td>
          <td><span class="badge <?= $u['status'] === 'aktif' ? 'badge-green' : 'badge-red' ?>"><?= e(active_status_label($u['status'])) ?></span></td>
          <td><?= $u['last_login'] ? tgl_id($u['last_login'], true) : '-' ?></td>
          <td class="cell-actions">
            <div class="cell-actions-inner">
            <a class="btn btn-sm btn-light" href="<?= legacy_url('modules/pengaturan/user_form.php?id=' . $u['id']) ?>"
               data-modal-url="<?= legacy_url('modules/pengaturan/user_form.php?id=' . $u['id'] . '&modal=1') ?>"
               data-modal-title="<?= e(t('common.edit_user')) ?>"><?= e(t('common.edit')) ?></a>
            <?php if (!$self): ?>
            <form method="post" onsubmit="return confirm(<?= json_encode(t('common.delete_user_confirm'), JSON_UNESCAPED_UNICODE) ?>)">
              <?= sim_csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn btn-sm btn-red" type="submit"><?= e(t('common.delete')) ?></button>
            </form>
            <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal tambah/edit pengguna -->
<div class="modal-overlay" id="dataModal" aria-hidden="true">
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="dataModalTitle">
    <div class="modal-head">
      <div class="modal-title" id="dataModalTitle"><?= e(t('common.user_col')) ?></div>
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

  function open(url, title) {
    titleEl.textContent = title || <?= json_encode(t('common.user_col'), JSON_UNESCAPED_UNICODE) ?>;
    box.innerHTML = <?= json_encode('<div class="modal-loading">' . t('common.loading') . '</div>', JSON_UNESCAPED_UNICODE) ?>;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.text(); })
      .then(function (html) { box.innerHTML = html; bind(); var el = box.querySelector('input:not([disabled]),select,textarea'); if (el) el.focus(); });
  }
  function close() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    box.innerHTML = '';
  }
  function bind() {
    var form = box.querySelector('form[data-modal-form]');
    if (!form) return;
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var btn = form.querySelector('[type=submit]');
      if (btn) btn.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) {
          var ct = r.headers.get('content-type') || '';
          if (ct.indexOf('application/json') > -1) {
            return r.json().then(function (j) { if (j.ok) location.reload(); });
          }
          return r.text().then(function (html) { box.innerHTML = html; bind(); });
        })
        .catch(function () { if (btn) btn.disabled = false; });
    });
  }

  document.addEventListener('click', function (ev) {
    var opener = ev.target.closest('[data-modal-url]');
    if (opener) { ev.preventDefault(); open(opener.getAttribute('data-modal-url'), opener.getAttribute('data-modal-title')); return; }
    if (ev.target.closest('[data-modal-close]')) close(); // klik luar TIDAK menutup (cegah data hilang)
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && overlay.classList.contains('open')) close();
  });
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

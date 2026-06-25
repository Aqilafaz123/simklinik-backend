<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('superadmin');
$pageTitle = t('pages.clinic_profile');

$keys = ['clinic_name', 'clinic_unit', 'clinic_address'];

$val = [];
foreach ($keys as $k) {
    $s = db()->prepare("SELECT v FROM setting WHERE k=?"); $s->execute([$k]);
    $val[$k] = $s->fetchColumn() ?: '';
}
$s = db()->prepare("SELECT v FROM setting WHERE k='clinic_logo'"); $s->execute();
$logo = $s->fetchColumn() ?: '';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $upsert = db()->prepare("INSERT INTO setting (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");

    // Logo: hapus, atau unggah file baru
    if (($_POST['remove_logo'] ?? '') === '1') {
        if ($logo && is_file(upload_fs_path($logo))) @unlink(upload_fs_path($logo));
        $logo = '';
    } elseif (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $f = $_FILES['logo'];
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $info = @getimagesize($f['tmp_name']);
        $mime = $info['mime'] ?? '';
        if (!isset($allowed[$mime])) {
            $errors[] = t('common.err_logo_format');
        } elseif ($f['size'] > 20 * 1024 * 1024) {
            $errors[] = t('common.err_logo_size');
        } else {
            if (!is_dir(UPLOAD_PATH)) @mkdir(UPLOAD_PATH, 0775, true);
            $newName = 'clinic_logo_' . time() . '.' . $allowed[$mime];
            if (move_uploaded_file($f['tmp_name'], UPLOAD_PATH . '/' . $newName)) {
                if ($logo && is_file(upload_fs_path($logo))) @unlink(upload_fs_path($logo));
                $logo = 'uploads/' . $newName;
            } else {
                $errors[] = t('common.err_logo_upload');
            }
        }
    }

    if (!$errors) {
        foreach ($keys as $k) {
            $val[$k] = trim($_POST[$k] ?? '');
            $upsert->execute([$k, $val[$k]]);
        }
        $upsert->execute(['clinic_logo', $logo]);
        set_flash('success', t('common.clinic_profile_saved'));
        legacy_redirect('modules/pengaturan/profil.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e(t('pages.clinic_profile')) ?></div>
    <div class="pt-sub"><?= e(t('common.clinic_profile_sub')) ?></div>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= sim_csrf_field() ?>
  <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" hidden id="logoInput">

  <!-- Hero -->
  <div class="pf-hero" style="margin-top:18px">
    <div class="pf-cover"></div>
    <div class="pf-body">
      <div class="pf-avatar-wrap" onclick="document.getElementById('logoInput').click()" title="<?= e(t('common.change_logo')) ?>">
        <?php if ($logo): ?>
          <img src="<?= legacy_url($logo) ?>" id="logoPreview" class="pf-avatar pf-logo" alt="<?= e(t('common.clinic_logo')) ?>">
        <?php else: ?>
          <span class="pf-avatar pf-avatar-initial pf-logo" id="logoPreview" style="background:linear-gradient(135deg,#6366f1,#2563eb)"><?= app_icon("hospital") ?></span>
        <?php endif; ?>
        <span class="pf-cam"><?= app_icon("plus") ?></span>
      </div>
      <div class="pf-id">
        <div class="pf-name">
          <?= e($val['clinic_name'] ?: CLINIC_NAME) ?>
          <?php if ($val['clinic_unit']): ?><span class="badge badge-blue"><?= e($val['clinic_unit']) ?></span><?php endif; ?>
        </div>
        <div class="pf-meta">
          <?= app_icon("hospital") ?>
          <?= $val['clinic_address'] ? e($val['clinic_address']) : e(t('common.address_not_set')) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Form identitas -->
  <div class="card" style="margin-top:16px">
    <div class="step-head">
      <div class="step-num acc-blue"><?= app_icon("hospital") ?></div>
      <div><div class="st-title"><?= e(t('common.clinic_identity_title')) ?></div><div class="st-sub"><?= e(t('common.clinic_identity_sub')) ?></div></div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label><?= e(t('common.clinic_name')) ?></label>
        <input type="text" name="clinic_name" class="form-control" value="<?= e($val['clinic_name']) ?>" placeholder="<?= e(t('common.clinic_name_placeholder')) ?>">
      </div>
      <div class="form-group">
        <label><?= e(t('common.clinic_unit')) ?></label>
        <input type="text" name="clinic_unit" class="form-control" value="<?= e($val['clinic_unit']) ?>" placeholder="<?= e(t('common.clinic_unit_placeholder')) ?>">
      </div>
    </div>
    <div class="form-group">
      <label><?= e(t('common.clinic_address')) ?></label>
      <textarea name="clinic_address" class="form-control" rows="3" placeholder="<?= e(t('common.clinic_address_placeholder')) ?>"><?= e($val['clinic_address']) ?></textarea>
    </div>
    <!-- <div class="form-group">
      <label>Logo Klinik</label>
      <button type="button" class="btn btn-light" style="width:fit-content" onclick="document.getElementById('logoInput').click()">
        <?= app_icon("plus") ?> Pilih Logo
      </button>
      <small style="color:var(--muted);display:block;margin-top:6px">PNG / JPG / WEBP &middot; maks 20 MB. Klik logo di atas juga bisa.</small>
      <?php if ($logo): ?>
        <label class="logo-remove" style="margin-top:10px"><input type="checkbox" name="remove_logo" value="1"> Hapus logo saat disimpan</label>
      <?php endif; ?>
    </div> -->

    <div style="display:flex;justify-content:flex-end;border-top:1px solid var(--border);margin-top:6px;padding-top:16px">
      <button class="btn" type="submit"><?= app_icon("save") ?> <?= e(t('common.save_profile')) ?></button>
    </div>
  </div>
</form>

<script>
(function () {
  var input = document.getElementById('logoInput');
  if (!input) return;
  input.addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;
    var url = URL.createObjectURL(file);
    var el = document.getElementById('logoPreview');
    if (el && el.tagName === 'IMG') { el.src = url; return; }
    var img = document.createElement('img');
    img.id = 'logoPreview';
    img.className = 'pf-avatar pf-logo';
    img.alt = <?= json_encode(t('common.logo_preview'), JSON_UNESCAPED_UNICODE) ?>;
    img.src = url;
    if (el) el.replaceWith(img);
  });
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

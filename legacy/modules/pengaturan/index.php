<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('superadmin');
$pageTitle = t('pages.settings');

$jmlUser = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= app_icon("pengaturan") ?> <?= e(t('pages.settings')) ?></div>
    <div class="pt-sub"><?= e(t('common.settings_sub')) ?></div>
  </div>
</div>

<div class="cards" style="margin-top:18px">
  <a class="card stat" style="text-decoration:none" href="<?= legacy_url('modules/pengaturan/profil.php') ?>">
    <div><div style="font-size:var(--fs-sub);font-weight:600"><?= e(t('pages.clinic_profile')) ?></div><div class="lbl"><?= e(t('common.clinic_profile_card_sub')) ?></div></div>
    <div class="ico bg-blue" style="font-size:24px"><?= app_icon("hospital") ?> </div>
  </a>
  <a class="card stat" style="text-decoration:none" href="<?= legacy_url('modules/pengaturan/users.php') ?>">
    <div><div style="font-size:var(--fs-sub);font-weight:600"><?= e(t('common.users_and_roles')) ?></div><div class="lbl"><?= e(t('common.users_count_label', ['count' => $jmlUser])) ?></div></div>
    <div class="ico bg-green" style="font-size:24px"><?= app_icon("users") ?> </div>
  </a>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

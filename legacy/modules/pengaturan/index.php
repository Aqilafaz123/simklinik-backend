<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('superadmin');
$pageTitle = 'Pengaturan';

$jmlUser = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= app_icon("pengaturan") ?> Pengaturan</div>
    <div class="pt-sub">Manajemen sistem &amp; konfigurasi aplikasi.</div>
  </div>
</div>

<div class="cards" style="margin-top:18px">
  <a class="card stat" style="text-decoration:none" href="<?= legacy_url('modules/pengaturan/profil.php') ?>">
    <div><div style="font-size:var(--fs-sub);font-weight:600">Profil Klinik</div><div class="lbl">Nama, unit, alamat (tampil di struk)</div></div>
    <div class="ico bg-blue" style="font-size:24px"><?= app_icon("hospital") ?> </div>
  </a>
  <a class="card stat" style="text-decoration:none" href="<?= legacy_url('modules/pengaturan/users.php') ?>">
    <div><div style="font-size:var(--fs-sub);font-weight:600">Pengguna &amp; Role</div><div class="lbl"><?= $jmlUser ?> akun pengguna</div></div>
    <div class="ico bg-green" style="font-size:24px"><?= app_icon("users") ?> </div>
  </a>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();                       // semua role boleh buka profilnya sendiri
$pageTitle = 'Profil Saya';

$me = current_user();
$u  = db()->prepare("SELECT u.*, r.nama AS role_nama FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?");
$u->execute([$me['id']]);
$u = $u->fetch();
if (!$u) { logout(); legacy_redirect('auth/login.php'); }

$errors = [];
$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'update_profil') {
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telepon  = trim($_POST['telepon'] ?? '');
        if (mb_strlen($nama) < 3) $errors[] = 'Nama minimal 3 karakter.';
        if ($username === '') $errors[] = 'Username wajib diisi.';
        elseif ($username !== $u['username']) {
            $dup = db()->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            $dup->execute([$username, $u['id']]);
            if ($dup->fetch()) $errors[] = 'Username sudah dipakai.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

        $avatar = $u['avatar'];
        if (!$errors && !empty($_FILES['avatar']['name']) && ($_FILES['avatar']['error'] ?? 1) === UPLOAD_ERR_OK) {
            $f = $_FILES['avatar'];
            $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            $info = @getimagesize($f['tmp_name']);
            $mime = $info['mime'] ?? '';
            if (!isset($allowed[$mime]))        $errors[] = 'Foto harus PNG, JPG, WEBP, atau GIF.';
            elseif ($f['size'] > 20 * 1024 * 1024) $errors[] = 'Ukuran foto maksimal 20 MB.';
            else {
                $dir = UPLOAD_PATH . '/avatars';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $new = 'avatar_' . $u['id'] . '_' . time() . '.' . $allowed[$mime];
                if (move_uploaded_file($f['tmp_name'], $dir . '/' . $new)) {
                    if ($avatar && is_file(upload_fs_path($avatar))) @unlink(upload_fs_path($avatar));
                    $avatar = 'uploads/avatars/' . $new;
                } else $errors[] = 'Gagal mengunggah foto.';
            }
        }

        if (!$errors) {
            db()->prepare("UPDATE users SET nama=?, username=?, email=?, telepon=?, avatar=? WHERE id=?")
                ->execute([$nama, $username, $email ?: null, $telepon ?: null, $avatar, $u['id']]);
            $_SESSION['user']['nama']     = $nama;
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['avatar']   = $avatar;
            set_flash('success', 'Profil berhasil diperbarui.');
            legacy_redirect('modules/akun/profil.php');
        }
    } elseif ($aksi === 'ganti_password') {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $con = $_POST['confirm_password'] ?? '';
        if (!password_verify($cur, $u['password'])) $errors[] = 'Password saat ini tidak sesuai.';
        elseif (mb_strlen($new) < 6)                $errors[] = 'Password baru minimal 6 karakter.';
        elseif ($new !== $con)                      $errors[] = 'Konfirmasi password tidak cocok.';
        else {
            db()->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([password_hash($new, PASSWORD_BCRYPT), $u['id']]);
            set_flash('success', 'Password berhasil diubah.');
            legacy_redirect('modules/akun/profil.php');
        }
    }
}

$inisial = strtoupper(mb_substr($u['nama'], 0, 1));
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title">Profil Saya</div>
    <div class="pt-sub">Kelola informasi akun &amp; keamanan Anda</div>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<!-- Hero -->
<div class="pf-hero" style="margin-top:18px">
  <div class="pf-cover"></div>
  <div class="pf-body">
    <div class="pf-avatar-wrap" onclick="document.getElementById('avatarInput').click()" title="Ganti foto">
      <?php if (!empty($u['avatar'])): ?>
        <img src="<?= legacy_url($u['avatar']) ?>" id="avatarPreview" class="pf-avatar" alt="">
      <?php else: ?>
        <span class="pf-avatar pf-avatar-initial" id="avatarPreviewBox"><?= e($inisial) ?></span>
      <?php endif; ?>
      <span class="pf-cam"><?= app_icon('plus') ?></span>
    </div>
    <div class="pf-id">
      <div class="pf-name"><?= e($u['nama']) ?> <span class="badge badge-blue"><?= e($u['role_nama']) ?></span></div>
      <div class="pf-meta">
        <?= app_icon('user') ?> @<?= e($u['username']) ?>
        <?php if (!empty($u['email'])): ?><span class="sep">&middot;</span><?= e($u['email']) ?><?php endif; ?>
        <span class="sep">&middot;</span><?= app_icon('calendar') ?> Bergabung <?= tgl_id($u['created_at']) ?>
      </div>
    </div>
  </div>
</div>

<div class="pf-grid">
  <!-- Kolom kiri: form -->
  <div class="pf-col">
    <div class="card">
      <div class="step-head">
        <div class="step-num acc-blue"><?= app_icon('user') ?></div>
        <div><div class="st-title">Informasi Akun</div><div class="st-sub">Nama, foto, dan kontak</div></div>
      </div>
      <form method="post" enctype="multipart/form-data">
        <?= sim_csrf_field() ?>
        <input type="hidden" name="aksi" value="update_profil">
        <input type="file" name="avatar" id="avatarInput" accept="image/*" hidden onchange="previewAvatar(this)">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" value="<?= e($u['nama']) ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= e($u['username']) ?>" required autocomplete="username">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($u['email'] ?? '') ?>" placeholder="nama@email.com">
          </div>
        </div>
        <div class="form-group">
          <label>No. HP / Telepon</label>
          <input type="text" name="telepon" class="form-control" value="<?= e($u['telepon'] ?? '') ?>" placeholder="08xx" inputmode="tel">
        </div>
        <div style="display:flex;justify-content:flex-end;border-top:1px solid var(--border);margin-top:16px;padding-top:16px">
          <button class="btn" type="submit"><?= app_icon('save') ?> Simpan Perubahan</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:16px">
      <div class="step-head">
        <div class="step-num acc-orange"><?= app_icon('logout') ?></div>
        <div><div class="st-title">Keamanan</div><div class="st-sub">Ubah password akun</div></div>
      </div>
      <form method="post">
        <?= sim_csrf_field() ?>
        <input type="hidden" name="aksi" value="ganti_password">
        <div class="form-group">
          <label>Password Saat Ini</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password Baru</label>
            <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="Min. 6 karakter">
          </div>
          <div class="form-group">
            <label>Konfirmasi Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;border-top:1px solid var(--border);margin-top:16px;padding-top:16px">
          <button class="btn btn-outline" type="submit"><?= app_icon('save') ?> Ubah Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Kolom kanan: akses cepat -->
  <div class="pf-col-side">
    <div class="card">
      <div class="step-head">
        <div class="step-num acc-purple"><?= app_icon('dashboard') ?></div>
        <div><div class="st-title">Akses Cepat</div></div>
      </div>
      <div class="pf-quicks">
        <a class="pf-quick" href="<?= legacy_url('modules/dashboard/index.php') ?>">
          <span class="qic"><?= app_icon('dashboard') ?></span>
          <span><span class="q-t">Dashboard</span><span class="q-s">Ringkasan klinik</span></span>
          <span class="q-go"><?= app_icon('chevron') ?></span>
        </a>
        <?php if (current_role() === 'admin'): ?>
        <a class="pf-quick" href="<?= legacy_url('modules/pengaturan/users.php') ?>">
          <span class="qic"><?= app_icon('users') ?></span>
          <span><span class="q-t">Pengguna &amp; Role</span><span class="q-s">Kelola pengguna</span></span>
          <span class="q-go"><?= app_icon('chevron') ?></span>
        </a>
        <a class="pf-quick" href="<?= legacy_url('modules/pengaturan/profil.php') ?>">
          <span class="qic"><?= app_icon('hospital') ?></span>
          <span><span class="q-t">Profil Klinik</span><span class="q-s">Identitas &amp; logo</span></span>
          <span class="q-go"><?= app_icon('chevron') ?></span>
        </a>
        <?php endif; ?>
        <form method="post" action="/logout" class="pf-quick-form" onsubmit="return confirm('Keluar dari aplikasi?')">
          <input type="hidden" name="_token" value="<?= csrf_token() ?>">
          <button type="submit" class="pf-quick pf-quick-danger">
            <span class="qic"><?= app_icon('logout') ?></span>
            <span><span class="q-t">Keluar</span><span class="q-s">Logout dari akun</span></span>
            <span class="q-go"><?= app_icon('chevron') ?></span>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function previewAvatar(input){
  if (input.files && input.files[0]) {
    var rd = new FileReader();
    rd.onload = function(e){
      var img = document.getElementById('avatarPreview');
      if (img) { img.src = e.target.result; return; }
      var box = document.getElementById('avatarPreviewBox');
      if (box) box.outerHTML = '<img src="'+e.target.result+'" id="avatarPreview" class="pf-avatar" alt="">';
    };
    rd.readAsDataURL(input.files[0]);
  }
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

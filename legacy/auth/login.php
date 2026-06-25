<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';

if (is_logged_in()) {
    legacy_redirect('modules/dashboard/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (attempt_login($username, $password)) {
        legacy_redirect('modules/dashboard/index.php');
    } else {
        $error = 'Username atau password salah.';
    }
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login &middot; <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= legacy_url('assets/css/login.css') ?>?v=<?= @filemtime(ASSETS_FS_PATH . '/css/login.css') ?>">
</head>
<body class="login-page">
<div class="login-split">

  <!-- ============ Panel kiri: branding ============ -->
  <section class="login-brand">
    <span class="lb-badge"><?= app_icon('shield') ?> Sistem Informasi Manajemen Klinik</span>

    <h1 class="lb-title">Pelayanan Klinik yang<br>Cepat, Aman, dan<br>Terintegrasi</h1>
    <p class="lb-sub">
      SIM Klinik menghadirkan sistem manajemen klinik terintegrasi untuk registrasi
      pasien, pemeriksaan, farmasi, hingga pembayaran secara cepat, akurat, dan profesional.
    </p>

    <div class="lb-features">
      <div class="lb-feat">
        <span class="lb-feat-ico"><?= app_icon('registrasi') ?></span>
        <div>
          <h4>Registrasi &amp; Antrian Pasien</h4>
          <p>Pendaftaran pasien dan papan antrian yang rapi serta real-time.</p>
        </div>
      </div>
      <div class="lb-feat">
        <span class="lb-feat-ico"><?= app_icon('rekam') ?></span>
        <div>
          <h4>Rekam Medis &amp; Pelayanan</h4>
          <p>Pemeriksaan dokter, resep obat, dan rekam medis terpusat.</p>
        </div>
      </div>
      <div class="lb-feat">
        <span class="lb-feat-ico"><?= app_icon('money') ?></span>
        <div>
          <h4>Billing &amp; Keuangan Aman</h4>
          <p>Tagihan, invoice, dan pembayaran dengan akses sesuai peran.</p>
        </div>
      </div>
    </div>

    <div class="lb-foot">
      <div class="lb-foot-name"><?= e(CLINIC_NAME) ?><small><?= e(CLINIC_UNIT) ?></small></div>
      <span class="lb-secure"><?= app_icon('shield') ?> Akses Aman Aktif</span>
    </div>
  </section>

  <!-- ============ Panel kanan: form login ============ -->
  <section class="login-form-side">
    <div class="login-card">

      <div class="lc-logo">
        <img src="<?= legacy_url('assets/img/logo.png') ?>" alt="<?= e(APP_NAME) ?>">
        <div class="lc-logo-name">SIM <span>Klinik</span></div>
        <div class="lc-logo-sub"><?= APP_FULL ?></div>
      </div>

      <h2 class="lc-welcome">Selamat Datang</h2>
      <p class="lc-welcome-sub">Silakan login untuk mengakses SIM Klinik<br>secara aman dan profesional.</p>

      <?php if ($flash): ?>
        <div class="lc-alert warn"><?= e($flash['msg']) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="lc-alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <?= sim_csrf_field() ?>

        <div class="lc-field">
          <label>Username</label>
          <div class="lc-input">
            <?= app_icon('user') ?>
            <input type="text" name="username" placeholder="Masukkan username" autofocus
                   value="<?= e($_POST['username'] ?? '') ?>">
          </div>
        </div>

        <div class="lc-field">
          <label>Password</label>
          <div class="lc-input">
            <?= app_icon('shield') ?>
            <input type="password" name="password" id="pwd" placeholder="Masukkan password">
            <button type="button" class="lc-eye" onclick="togglePwd(this)" aria-label="Tampilkan/sembunyikan password"><?= app_icon('eye') ?></button>
          </div>
        </div>

        <label class="lc-remember">
          <input type="checkbox" name="remember" value="1"> Ingat saya
        </label>

        <button type="submit" class="lc-btn"><?= app_icon('logout') ?> Login ke Sistem</button>
      </form>

      <div class="lc-note">
        <?= app_icon('shield') ?>
        <span>Pastikan akun dan password hanya digunakan oleh pengguna berwenang
        untuk menjaga keamanan data pasien dan informasi klinik.</span>
      </div>

      <div class="lc-foot">
        &copy; <?= date('Y') ?> <b><?= APP_NAME ?></b><br>
        Dikelola oleh <?= e(CLINIC_NAME) ?>
      </div>
    </div>
  </section>

</div>

<script>
function togglePwd(btn){
  var i = document.getElementById('pwd');
  i.type = i.type === 'password' ? 'text' : 'password';
  btn.style.color = i.type === 'text' ? '#2563eb' : '';
}
</script>
</body>
</html>

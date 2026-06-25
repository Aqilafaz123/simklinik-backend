<?php
/**
 * Layout header + sidebar. Sertakan di awal tiap halaman terproteksi.
 * Set $pageTitle sebelum require file ini.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/menu.php';
require_once __DIR__ . '/icons.php';
require_login();

$pageTitle = $pageTitle ?? APP_NAME;
$user      = current_user();
$role      = current_role();
$current   = $_SERVER['SCRIPT_NAME'] ?? '';
$flash     = get_flash();

// Jumlah data per kelompok Master Data (untuk badge di menu) — hanya superadmin.
$masterCounts = [];
$curMasterGroup = null;
if ($role === 'superadmin') {
    require_once __DIR__ . '/../modules/master/entities.php';
    $mEnts = master_entities();
    foreach ($mEnts as $mEnt) {
        try {
            $masterCounts[$mEnt['group']] = ($masterCounts[$mEnt['group']] ?? 0)
                + (int) db()->query("SELECT COUNT(*) FROM {$mEnt['table']}")->fetchColumn();
        } catch (Throwable $e) { /* tabel belum ada -> abaikan */ }
    }
    // Kelompok master yang sedang aktif (untuk highlight anak menu)
    if (str_contains($current, 'modules/master/index.php')) {
        $eSlug = $_GET['e'] ?? '';
        if (isset($mEnts[$eSlug])) {
            $curMasterGroup = $mEnts[$eSlug]['group'];
        } else {
            $g = $_GET['g'] ?? '';
            $curMasterGroup = ($g !== '' && isset($masterCounts[$g])) ? $g : array_key_first($masterCounts);
        }
    }
}

// Jumlah laporan per kelompok (badge anak menu Laporan) sesuai hak akses role.
require_once __DIR__ . '/../modules/laporan/reports.php';
$lapCounts = [];
foreach (laporan_list_for($role) as $lSlug => $lr) {
    $lapCounts[$lr['group']] = ($lapCounts[$lr['group']] ?? 0) + 1;
}

// Kelompok laporan yang sedang aktif (untuk highlight anak menu Laporan).
$curLaporanGroup = null;
if (str_contains($current, 'modules/laporan/index.php')) {
    $lJenis = $_GET['jenis'] ?? '';
    if ($lJenis !== '' && laporan_can($role, $lJenis)) {
        $curLaporanGroup = laporan_get($lJenis)['group'];
    } else {
        $lg = $_GET['g'] ?? '';
        $curLaporanGroup = ($lg !== '' && isset($lapCounts[$lg]))
            ? $lg : array_key_first($lapCounts);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> &middot; <?= APP_NAME ?></title>
  <script>(function(){var t=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
  <link rel="stylesheet" href="<?= legacy_url('assets/vendor/datatables.min.css') ?>">
  <link rel="stylesheet" href="<?= legacy_url('assets/css/style.css') ?>?v=<?= @filemtime(ASSETS_FS_PATH . '/css/style.css') ?>">
</head>
<body>
<div class="layout" id="appLayout">
  <script>if(localStorage.getItem('sidebar')==='collapsed'){document.getElementById('appLayout').classList.add('collapsed');}</script>
  <aside class="sidebar" id="appSidebar">
    <div class="brand">
      <span class="brand-ico<?= CLINIC_LOGO ? ' has-logo' : '' ?>"><?php if (CLINIC_LOGO): ?><img src="<?= legacy_url(CLINIC_LOGO) ?>" alt=""><?php else: ?><?= app_icon('plus') ?><?php endif; ?></span>
      <span class="brand-text"><?= APP_NAME ?><small><?= CLINIC_NAME ?></small></span>
    </div>
    <nav>
      <?php foreach (get_menu() as $grup): ?>
        <?php
          // Saring item sesuai role
          $items = array_filter($grup['items'], fn($it) =>
              $role === 'superadmin' || in_array($role, $it['roles'], true));
          if (!$items) continue;
        ?>
        <?php if (!empty($grup['grup'])): ?>
          <div class="label"><?= e($grup['grup']) ?></div>
        <?php endif; ?>
        <?php foreach ($items as $it): ?>
          <?php
            // Default: cocokkan folder modul. Item bisa menimpa dgn 'match' bila
            // beberapa menu berada di folder yang sama (mis. Profil & Pengguna).
            $matchKey = $it['match'] ?? ('/' . explode('/', $it['url'])[1] . '/');
            $active = str_contains($current, $matchKey) ? 'active' : '';
          ?>
          <?php
            // Saring anak menu sesuai role (anak boleh punya 'roles' sendiri).
            $children = !empty($it['children'])
                ? array_filter($it['children'], fn($c) =>
                    !isset($c['roles']) || $role === 'superadmin' || in_array($role, $c['roles'], true))
                : [];
          ?>
          <?php if ($children): ?>
            <?php
              // sub-menu (dropdown). Buka otomatis bila grup ini sedang aktif
              // (folder cocok) atau salah satu anak sedang aktif.
              $open = ($active !== '');
              foreach ($children as $c) {
                  if (str_contains($current, $c['url'])) { $open = true; break; }
              }
            ?>
            <div class="nav-group<?= $open ? ' open' : '' ?>">
              <div class="nav-parent<?= $active ? ' active' : '' ?>">
                <button type="button" class="np-link" onclick="toggleNavGroup(this)" title="<?= e($it['label']) ?>">
                  <span class="ico"><?= app_icon($it['ico']) ?></span>
                  <span class="txt"><?= e($it['label']) ?></span>
                </button>
                <button type="button" class="np-caret" onclick="toggleNavGroup(this)" aria-label="Buka/tutup sub-menu"><?= app_icon('chevron') ?></button>
              </div>
              <div class="nav-sub">
                <?php foreach ($children as $ci => $c): ?>
                  <?php
                    if (isset($c['badge_group'])) {
                        $ca = $c['badge_group'] === $curMasterGroup ? 'active' : '';
                    } elseif (isset($c['lap_group'])) {
                        $ca = $c['lap_group'] === $curLaporanGroup ? 'active' : '';
                    } else {
                        $ca = str_contains($current, $c['url']) ? 'active' : '';
                    }
                  ?>
                  <a class="<?= $ca ?>" href="<?= legacy_url($c['url']) ?>"><span class="dot"></span><span class="txt"><?= e($c['label']) ?></span>
                    <?php if (isset($c['badge_group'])): ?><span class="cnt c<?= ($ci % 6) + 1 ?>"><?= (int) ($masterCounts[$c['badge_group']] ?? 0) ?></span>
                    <?php elseif (isset($c['lap_group'])): ?><span class="cnt c<?= ($ci % 6) + 1 ?>"><?= (int) ($lapCounts[$c['lap_group']] ?? 0) ?></span><?php endif; ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php else: ?>
            <a class="<?= $active ?>" href="<?= legacy_url($it['url']) ?>" title="<?= e($it['label']) ?>">
              <span class="ico"><?= app_icon($it['ico']) ?></span> <span class="txt"><?= e($it['label']) ?></span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <form method="post" action="/logout" onsubmit="return confirm('Keluar dari aplikasi?')">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        <button type="submit" class="logout-link" title="Keluar">
          <span class="ico"><?= app_icon('logout') ?></span> <span class="txt">Keluar</span>
        </button>
      </form>
    </div>
  </aside>

  <button type="button" class="sidebar-backdrop" onclick="closeSidebar()" aria-label="Tutup menu" tabindex="-1"></button>

  <div class="main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" type="button" id="menuToggle" onclick="toggleSidebar()" title="Sembunyikan/tampilkan menu" aria-expanded="false" aria-controls="appSidebar">
          <span class="mt-bars"><?= app_icon('menu') ?></span>
          <span class="mt-x"><?= app_icon('close') ?></span>
        </button>
        <div class="topbar-search">
          <span class="ts-ico"><?= app_icon('search') ?></span>
          <input type="search" id="menuSearch" placeholder="Cari menu..." autocomplete="off">
        </div>
      </div>
      <div class="topbar-right">
        <!-- <button class="topbar-icon" type="button" id="themeToggle" title="Mode siang/malam">
          <span class="ti-sun"><?= app_icon('sun') ?></span><span class="ti-moon"><?= app_icon('moon') ?></span>
        </button> -->
        <!-- <button class="topbar-icon" type="button" id="fullscreenToggle" title="Layar penuh"><?= app_icon('expand') ?></button> -->
        <!-- <button class="topbar-icon" type="button" title="Notifikasi"><?= app_icon('bell') ?><span class="ti-badge">3</span></button> -->
        <div class="user-dropdown" id="userDropdown">
          <button type="button" class="user-chip" onclick="toggleUserMenu(event)" aria-haspopup="true" aria-expanded="false" title="Menu akun">
            <div class="avatar"><?php if (!empty($user['avatar'])): ?><img src="<?= legacy_url($user['avatar']) ?>" alt=""><?php else: ?><?= strtoupper(substr($user['nama'], 0, 1)) ?><?php endif; ?></div>
            <div class="user-meta">
              <div class="user-name"><?= e($user['nama']) ?></div>
              <div class="user-role"><?= e($user['role_nama']) ?></div>
            </div>
            <span class="user-caret"><?= app_icon('chevron') ?></span>
          </button>
          <div class="user-menu" role="menu">
            <a href="<?= legacy_url('modules/akun/profil.php') ?>" role="menuitem">
              <span class="ico"><?= app_icon('user') ?></span> Profil Saya
            </a>
            <div class="user-menu-sep"></div>
            <form method="post" action="/logout" role="menuitem" class="danger"
                  onsubmit="return confirm('Keluar dari aplikasi?')">
              <input type="hidden" name="_token" value="<?= csrf_token() ?>">
              <button type="submit" class="user-menu-btn">
                <span class="ico"><?= app_icon('logout') ?></span> Keluar
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>
    <main class="content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : ($flash['type'] === 'info' ? 'info' : 'success'))) ?>">
          <?= e($flash['msg']) ?>
        </div>
      <?php endif; ?>

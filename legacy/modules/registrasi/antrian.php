<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/icons.php';
require_role('registrasi');

$today = date('Y-m-d');
// Antrian per poli hari ini (yang masih menunggu / sedang diperiksa)
$rows = db()->prepare(
    "SELECT po.kode, po.nama AS poli, k.no_antrian, k.status, p.nama AS pasien
     FROM kunjungan k
     JOIN poli po ON po.id = k.poli_id
     JOIN pasien p ON p.id = k.pasien_id
     WHERE k.tgl_kunjungan = ? AND k.status IN ('menunggu','periksa')
     ORDER BY po.nama, k.no_antrian");
$rows->execute([$today]);
$data = [];
foreach ($rows->fetchAll() as $r) {
    $data[$r['poli']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="15">
  <title>Papan Antrian &middot; <?= CLINIC_NAME ?></title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Segoe UI',Arial,sans-serif;background:#0f2c4d;color:#fff;min-height:100vh;padding:24px}
    h1{display:flex;align-items:center;justify-content:center;gap:10px;font-size:26px;margin-bottom:4px}
    h1 svg{width:28px;height:28px;stroke:#60a5fa}
    .sub{text-align:center;color:#94a3b8;margin-bottom:24px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
    .poli{background:#173d68;border-radius:12px;padding:18px}
    .poli h2{font-size:18px;border-bottom:1px solid rgba(255,255,255,.15);padding-bottom:10px;margin-bottom:12px}
    .row{display:flex;justify-content:space-between;align-items:center;padding:8px 0}
    .no{font-size:22px;font-weight:800;color:#60a5fa}
    .periksa{color:#34d399}
    .nama{color:#cbd5e1;font-size:14px}
    .badge{font-size:11px;padding:2px 8px;border-radius:10px;background:#1e40af}
    .badge.on{background:#15803d}
    .empty{color:#94a3b8;font-size:14px;padding:8px 0}
  </style>
</head>
<body>
  <h1><?= app_icon('registrasi') ?> <?= CLINIC_NAME ?></h1>
  <div class="sub">Papan Antrian &middot; <?= tgl_id($today) ?> &middot; <span id="jam"></span></div>
  <div class="grid">
    <?php if (!$data): ?>
      <div class="poli"><div class="empty">Belum ada antrian aktif hari ini.</div></div>
    <?php else: foreach ($data as $poli => $list): ?>
      <div class="poli">
        <h2><?= e($poli) ?></h2>
        <?php foreach ($list as $r): ?>
          <div class="row">
            <span class="no <?= $r['status'] === 'periksa' ? 'periksa' : '' ?>">
              <?= e($r['kode']) ?>-<?= str_pad($r['no_antrian'], 3, '0', STR_PAD_LEFT) ?>
            </span>
            <span class="nama"><?= e($r['pasien']) ?></span>
            <span class="badge <?= $r['status'] === 'periksa' ? 'on' : '' ?>"><?= e(ucfirst($r['status'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <script>
    function tick(){ document.getElementById('jam').textContent = new Date().toLocaleTimeString('id-ID'); }
    tick(); setInterval(tick, 1000);
  </script>
</body>
</html>

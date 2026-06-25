<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/icons.php';
require_role('registrasi', 'admin', 'superadmin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT k.no_kunjungan, k.no_antrian, k.tgl_kunjungan, k.jenis_penjamin, k.created_at,
            p.no_mr, p.nama AS pasien, po.kode AS poli_kode, po.nama AS poli, d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.id = ?");
$stmt->execute([$id]);
$k = $stmt->fetch();
if (!$k) { set_flash('danger', 'Kunjungan tidak ditemukan.'); legacy_redirect('modules/registrasi/index.php'); }
$noAntrian = $k['poli_kode'] . '-' . str_pad($k['no_antrian'], 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= e(t('common.card')) ?> <?= e($noAntrian) ?></title>
  <style>
    body{font-family:'Segoe UI',Arial,sans-serif;background:#f1f5f9;display:flex;
      justify-content:center;padding:30px}
    .ticket{background:#fff;width:320px;border:1px solid #cbd5e1;border-radius:10px;
      padding:24px;text-align:center}
    .ticket h2{font-size:16px;margin:0}
    .ticket h2 svg{width:1em;height:1em;vertical-align:-.14em}
    .actions svg{width:16px;height:16px;vertical-align:-3px}
    .ticket .clinic{color:#64748b;font-size:12px;margin-bottom:14px}
    .ticket .label{color:#64748b;font-size:13px;margin-top:14px}
    .ticket .antrian{font-size:54px;font-weight:800;color:#2563eb;letter-spacing:1px}
    .ticket table{width:100%;font-size:13px;margin-top:14px;text-align:left;border-collapse:collapse}
    .ticket td{padding:4px 0}
    .ticket td:first-child{color:#64748b;width:42%}
    .actions{margin-top:16px}
    .actions button,.actions a{padding:8px 16px;border:none;border-radius:8px;cursor:pointer;
      font-size:14px;text-decoration:none}
    .btn-print{background:#2563eb;color:#fff}
    .btn-back{background:#e2e8f0;color:#1e293b}
    hr{border:none;border-top:1px dashed #cbd5e1;margin:14px 0}
    @media print{body{background:#fff;padding:0}.actions{display:none}.ticket{border:none}}
  </style>
</head>
<body>
  <div class="ticket">
    <h2><?= app_icon('hospital') ?> <?= CLINIC_NAME ?></h2>
    <div class="clinic"><?= e(t('common.queue_card')) ?></div>
    <hr>
    <div class="label"><?= e(t('common.queue_number')) ?></div>
    <div class="antrian"><?= e($noAntrian) ?></div>
    <div class="label"><?= e($k['poli']) ?></div>
    <hr>
    <table>
      <tr><td><?= e(t('common.visit_no')) ?></td><td><b><?= e($k['no_kunjungan']) ?></b></td></tr>
      <tr><td><?= e(t('common.mr_no')) ?></td><td><?= e($k['no_mr']) ?></td></tr>
      <tr><td><?= e(t('app.patient')) ?></td><td><?= e($k['pasien']) ?></td></tr>
      <tr><td><?= e(t('app.doctor')) ?></td><td><?= e($k['dokter'] ?? '-') ?></td></tr>
      <tr><td><?= e(t('common.insurance')) ?></td><td><?= e(strtoupper($k['jenis_penjamin'])) ?></td></tr>
      <tr><td><?= e(t('common.date')) ?></td><td><?= tgl_id($k['tgl_kunjungan']) ?></td></tr>
      <tr><td><?= e(t('common.registered_at')) ?></td><td><?= date('H:i', strtotime($k['created_at'])) ?></td></tr>
    </table>
    <hr>
    <div style="font-size:12px;color:#64748b"><?= e(t('common.wait_message')) ?></div>
    <div class="actions">
      <button class="btn-print" onclick="window.print()"><?= app_icon('print') ?> <?= e(t('common.print')) ?></button>
      <a class="btn-back" href="<?= legacy_url('modules/registrasi/index.php') ?>"><?= e(t('common.done')) ?></a>
    </div>
  </div>
</body>
</html>

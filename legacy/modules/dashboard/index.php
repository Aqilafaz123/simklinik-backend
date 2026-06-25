<?php
require_once __DIR__ . '/../../includes/header.php';

$today = date('Y-m-d');

$poliId = current_role() === 'dokter' ? current_poli_id() : null;
$poliKunjungan = $poliId ? ' AND k.poli_id = ' . (int) $poliId : '';

$pasienHariIni = db()->query(
    "SELECT COUNT(*) FROM kunjungan k WHERE k.tgl_kunjungan = '$today'$poliKunjungan")->fetchColumn();
$antrianAktif = db()->query(
    "SELECT COUNT(*) FROM kunjungan k WHERE k.tgl_kunjungan = '$today'
     AND k.status IN ('menunggu','periksa','penunjang')$poliKunjungan")->fetchColumn();
$totalPasien = db()->query("SELECT COUNT(*) FROM pasien")->fetchColumn();
$pendapatanHariIni = db()->query(
    "SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
     WHERE DATE(tanggal) = '$today' AND status = 'valid'")->fetchColumn();
$stokMenipis = db()->query(
    "SELECT COUNT(*) FROM obat WHERE stok <= stok_minimal AND status='aktif'")->fetchColumn();

$antrian = db()->query(
    "SELECT k.no_antrian, k.status, p.nama AS pasien, po.nama AS poli, d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.tgl_kunjungan = '$today'$poliKunjungan
     ORDER BY k.no_antrian DESC LIMIT 10")->fetchAll();

$badgeMap = [
    'menunggu'   => 'badge-orange', 'periksa' => 'badge-blue',
    'penunjang'  => 'badge-blue',  'farmasi' => 'badge-blue',
    'billing'    => 'badge-blue',  'pembayaran' => 'badge-orange',
    'selesai'    => 'badge-green', 'batal' => 'badge-red',
];
?>
<div class="cards">
  <div class="card stat">
    <div><div class="num"><?= (int) $pasienHariIni ?></div><div class="lbl"><?= e(t('app.patients_today')) ?></div></div>
    <div class="ico bg-blue"><?= app_icon('users') ?></div>
  </div>
  <div class="card stat">
    <div><div class="num"><?= (int) $antrianAktif ?></div><div class="lbl"><?= e(t('app.active_queue')) ?></div></div>
    <div class="ico bg-orange"><?= app_icon('ticket') ?></div>
  </div>
  <div class="card stat">
    <div><div class="num"><?= rupiah($pendapatanHariIni) ?></div><div class="lbl"><?= e(t('app.revenue_today')) ?></div></div>
    <div class="ico bg-green"><?= app_icon('money') ?></div>
  </div>
  <div class="card stat">
    <div><div class="num"><?= (int) $totalPasien ?></div><div class="lbl"><?= e(t('app.total_patients')) ?></div></div>
    <div class="ico bg-purple"><?= app_icon('idcard') ?></div>
  </div>
  <div class="card stat">
    <div><div class="num"><?= (int) $stokMenipis ?></div><div class="lbl"><?= e(t('app.low_stock')) ?></div></div>
    <div class="ico bg-red"><?= app_icon('pills') ?></div>
  </div>
</div>

<div class="section-title"><?= e(t('app.queue_today')) ?></div>
<div class="table-wrap">
  <table class="datatable no-auto-num" style="width:100%">
    <thead>
      <tr>
        <th><?= e(t('app.queue_no')) ?></th>
        <th><?= e(t('app.patient')) ?></th>
        <th><?= e(t('app.poli')) ?></th>
        <th><?= e(t('app.doctor')) ?></th>
        <th><?= e(t('app.status')) ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($antrian as $a): ?>
        <tr>
          <td><b><?= e($a['poli']) ?>-<?= str_pad($a['no_antrian'], 3, '0', STR_PAD_LEFT) ?></b></td>
          <td><?= e($a['pasien']) ?></td>
          <td><?= e($a['poli']) ?></td>
          <td><?= e($a['dokter'] ?? '-') ?></td>
          <td><span class="badge <?= $badgeMap[$a['status']] ?? 'badge-gray' ?>"><?= e(status_label($a['status'])) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
/**
 * Detail tagihan (read-only) — tampil sebagai modal premium dari Billing index.
 * Mode modal (?modal=1 / fetch): kirim fragment saja, tanpa header & sidebar.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/billing_lib.php';
require_once __DIR__ . '/../../includes/icons.php'; // app_icon() dipakai juga di mode modal (tanpa header.php)
require_role('kasir', 'admin', 'superadmin');

$modal = isset($_GET['modal'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
$kunjunganId = (int) ($_GET['kunjungan_id'] ?? 0);

$kj = db()->prepare(
    "SELECT k.*, p.no_mr, p.nama AS pasien, po.nama AS poli, d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.id = ?");
$kj->execute([$kunjunganId]);
$kj = $kj->fetch();

if (!$kj) {
    if ($modal) { echo '<div class="bd-empty">Kunjungan tidak ditemukan.</div>'; exit; }
    set_flash('danger', 'Kunjungan tidak ditemukan.');
    legacy_redirect('modules/billing/index.php');
}

// Billing tersimpan (jika ada)
$billing = db()->prepare("SELECT * FROM billing WHERE kunjungan_id=?");
$billing->execute([$kunjunganId]);
$billing = $billing->fetch() ?: null;
$isFinal = $billing && $billing['status'] === 'final';

// Rincian: jika final pakai detail tersimpan; jika belum, hitung live
if ($billing) {
    $s = db()->prepare("SELECT kategori,item_code,deskripsi,qty,tarif,subtotal FROM billing_detail WHERE billing_id=? ORDER BY id");
    $s->execute([$billing['id']]);
    $detailLines = $s->fetchAll();
    $subtotal = (float) $billing['subtotal'];
    $diskon   = (float) $billing['diskon'];
    $total    = (float) $billing['total'];
    $administrasi = 0;
    foreach ($detailLines as $l) if ($l['kategori'] === 'administrasi') $administrasi += (float) $l['subtotal'];
} else {
    $detailLines  = collect_billing_lines($kunjunganId);
    $administrasi = 0;
    $subtotal = array_sum(array_column($detailLines, 'subtotal'));
    $diskon   = 0;
    $total    = $subtotal;
}
$svcOnly = array_sum(array_column(array_filter($detailLines, fn($l) => $l['kategori'] !== 'administrasi'), 'subtotal'));

$statusBadge = ['billing' => 'badge-orange', 'pembayaran' => 'badge-blue', 'selesai' => 'badge-green'];

if (!$modal) {
    $pageTitle = t('pages.billing_detail');
    require_once __DIR__ . '/../../includes/header.php';
    echo '<a href="' . legacy_url('modules/billing/index.php') . '" class="btn btn-light btn-sm">' . app_icon('arrowleft') . ' ' . e(t('common.back')) . '</a>';
    echo '<div class="card" style="margin-top:14px">';
}
?>
<div class="bill-detail">

  <!-- Hero: identitas pasien & status -->
  <div class="bd-hero">
    <div class="bd-hero-glow"></div>
    <div class="bd-hero-main">
      <div class="bd-avatar"><?= app_icon('idcard') ?></div>
      <div>
        <div class="bd-pasien"><?= e($kj['pasien']) ?></div>
        <div class="bd-meta">
          <span><?= app_icon('user') ?> No. MR <b><?= e($kj['no_mr']) ?></b></span>
          <span><?= app_icon('hospital') ?> <?= e($kj['poli']) ?></span>
          <span><?= app_icon('pelayanan') ?> <?= e($kj['dokter'] ?? '-') ?></span>
        </div>
      </div>
    </div>
    <div class="bd-hero-side">
      <div class="bd-kunjungan">No. <?= e($kj['no_kunjungan']) ?></div>
      <span class="badge <?= $statusBadge[$kj['status']] ?? 'badge-gray' ?>"><?= e(status_label($kj['status'])) ?></span>
      <?php if ($isFinal): ?><span class="badge badge-green"><?= e(t('common.final')) ?></span><?php endif; ?>
    </div>
  </div>

  <!-- Rincian layanan -->
  <div class="bd-section">
    <div class="bd-section-title"><?= app_icon('billing') ?> <?= e(t('common.service_breakdown')) ?></div>
    <div class="bd-table-wrap">
      <table class="bd-table">
        <thead>
          <tr><th><?= e(t('common.category')) ?></th><th><?= e(t('common.code')) ?></th><th><?= e(t('common.description')) ?></th><th class="num"><?= e(t('common.qty')) ?></th>
              <th class="num"><?= e(t('common.rate')) ?></th><th class="num"><?= e(t('common.subtotal')) ?></th></tr>
        </thead>
        <tbody>
          <?php if (!$detailLines): ?>
            <tr><td colspan="6" class="bd-empty"><?= e(t('common.no_services_yet')) ?></td></tr>
          <?php else: foreach ($detailLines as $l): ?>
            <tr>
              <td><span class="badge badge-gray"><?= e(billing_kategori_label($l['kategori'])) ?></span></td>
              <td><code class="bd-code"><?= e($l['item_code'] ?? '') ?></code></td>
              <td><?= e($l['deskripsi']) ?></td>
              <td class="num"><?= (int) $l['qty'] ?></td>
              <td class="num"><?= rupiah($l['tarif']) ?></td>
              <td class="num bold"><?= rupiah($l['subtotal']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ringkasan total -->
  <div class="bd-summary">
    <div class="bd-sum-row"><span><?= e(t('common.service_subtotal')) ?></span><b><?= rupiah($svcOnly) ?></b></div>
    <?php if ($administrasi > 0): ?>
      <div class="bd-sum-row"><span><?= e(t('common.admin_fee')) ?></span><b><?= rupiah($administrasi) ?></b></div>
    <?php endif; ?>
    <?php if ($diskon > 0): ?>
      <div class="bd-sum-row disc"><span><?= e(t('common.discount')) ?></span><b>&minus; <?= rupiah($diskon) ?></b></div>
    <?php endif; ?>
    <div class="bd-sum-total">
      <span><?= e(t('common.total_bill_upper')) ?></span>
      <span class="bd-total-val"><?= rupiah($total) ?></span>
    </div>
  </div>

  <?php if ($modal): ?>
  <div class="bd-actions">
    <button type="button" class="btn btn-light" data-modal-close><?= app_icon('close') ?> <?= e(t('common.close')) ?></button>
    <?php if ($kj['status'] === 'pembayaran'): ?>
      <a class="btn" href="<?= legacy_url('modules/keuangan/index.php') ?>"><?= app_icon('keuangan') ?> <?= e(t('common.to_payment')) ?></a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php
if (!$modal) {
    echo '</div>';
    require_once __DIR__ . '/../../includes/footer.php';
}

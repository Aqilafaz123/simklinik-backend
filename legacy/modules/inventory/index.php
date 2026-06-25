<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi', 'superadmin');
$pageTitle = t('pages.inventory');

$obat = db()->query(
    "SELECT o.*, k.nama AS kategori, s.nama AS satuan
     FROM obat o LEFT JOIN obat_kategori k ON k.id=o.kategori_id
     LEFT JOIN obat_satuan s ON s.id=o.satuan_id
     ORDER BY o.nama")->fetchAll();

$totalObat = count($obat);
$menipis = 0; $nilaiStok = 0;
foreach ($obat as $o) {
    if ($o['stok'] <= $o['stok_minimal']) $menipis++;
    $nilaiStok += $o['stok'] * $o['harga_beli'];
}
$expSoon = db()->query(
    "SELECT COUNT(*) FROM obat_batch WHERE stok>0 AND tgl_expired IS NOT NULL
     AND tgl_expired <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e(t('pages.inventory')) ?></div>
    <div class="pt-sub"><?= e(t('common.inventory_sub')) ?></div>
  </div>
  <div class="pt-actions">
    <a class="btn btn-light" href="<?= legacy_url('modules/inventory/penyesuaian.php') ?>"><?= app_icon('pengaturan') ?> <?= e(t('common.adjustment')) ?></a>
    <a class="btn" href="<?= legacy_url('modules/inventory/pembelian.php') ?>"><?= app_icon('inventory') ?> <?= e(t('common.medicine_purchase')) ?></a>
  </div>
</div>

<div class="cards" style="margin-top:16px">
  <div class="card stat"><div><div class="num"><?= $totalObat ?></div><div class="lbl"><?= e(t('common.medicine_types')) ?></div></div><div class="ico bg-blue"><?= app_icon('pills') ?></div></div>
  <div class="card stat"><div><div class="num"><?= $menipis ?></div><div class="lbl"><?= e(t('app.low_stock')) ?></div></div><div class="ico bg-red"><?= app_icon('bell') ?></div></div>
  <div class="card stat"><div><div class="num"><?= rupiah($nilaiStok) ?></div><div class="lbl"><?= e(t('common.stock_value')) ?></div></div><div class="ico bg-green"><?= app_icon('money') ?></div></div>
  <div class="card stat"><div><div class="num"><?= (int) $expSoon ?></div><div class="lbl"><?= e(t('common.expiring_soon')) ?></div></div><div class="ico bg-orange"><?= app_icon('calendar') ?></div></div>
</div>

<div class="section-title"><?= e(t('common.medicine_stock_list')) ?></div>
<div class="table-wrap">
  <table class="datatable dt-noscroll no-auto-num" style="width:100%">
    <thead>
      <tr><th><?= e(t('common.code')) ?></th><th><?= e(t('common.medicine_name')) ?></th><th><?= e(t('common.category')) ?></th>
          <th style="text-align:center"><?= e(t('common.stock')) ?></th><th style="text-align:center"><?= e(t('common.min_stock')) ?></th>
          <th style="text-align:right"><?= e(t('common.buy_price')) ?></th><th style="text-align:right"><?= e(t('common.sell_price')) ?></th>
          <th style="text-align:center"><?= e(t('app.status')) ?></th><th class="no-sort col-actions"><?= e(t('common.action')) ?></th></tr>
    </thead>
    <tbody>
      <?php foreach ($obat as $o): $low = $o['stok'] <= $o['stok_minimal']; ?>
        <tr>
          <td><code><?= e($o['kode']) ?></code></td>
          <td><?= e($o['nama']) ?></td>
          <td><?= e($o['kategori'] ?? '-') ?></td>
          <td style="text-align:center"><b><?= (int) $o['stok'] ?></b> <?= e($o['satuan'] ?? '') ?></td>
          <td style="text-align:center"><?= (int) $o['stok_minimal'] ?></td>
          <td style="text-align:right"><?= rupiah($o['harga_beli']) ?></td>
          <td style="text-align:right"><?= rupiah($o['harga_jual']) ?></td>
          <td style="text-align:center"><span class="badge <?= $low ? 'badge-red' : 'badge-green' ?>"><?= $low ? e(t('common.stock_low')) : e(t('common.stock_ok')) ?></span></td>
          <td class="cell-actions"><div class="cell-actions-inner"><a class="btn btn-sm btn-light" href="<?= legacy_url('modules/inventory/kartu_stok.php?obat_id=' . $o['id']) ?>"><?= e(t('common.stock_card')) ?></a></div></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

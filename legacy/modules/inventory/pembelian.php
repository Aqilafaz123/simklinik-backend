<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi', 'superadmin');
$pageTitle = t('common.medicine_purchase');

$rows = db()->query(
    "SELECT pb.id, pb.no_beli, pb.tanggal, pb.total, pb.keterangan, s.nama AS supplier,
            (SELECT COUNT(*) FROM pembelian_detail d WHERE d.pembelian_id=pb.id) AS jml
     FROM pembelian pb LEFT JOIN supplier s ON s.id=pb.supplier_id
     ORDER BY pb.id DESC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
  <div>
    <a href="<?= legacy_url('modules/inventory/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> <?= e(t('pages.inventory')) ?></a>
    <h2 style="display:inline-block;margin:0 0 0 8px"><?= app_icon("truck") ?> <?= e(t('common.medicine_purchase')) ?></h2>
  </div>
  <a class="btn" href="<?= legacy_url('modules/inventory/pembelian_form.php') ?>"><?= app_icon("plus") ?> <?= e(t('common.new_purchase')) ?></a>
</div>

<div class="table-wrap" style="margin-top:16px">
  <table class="datatable no-auto-num" style="width:100%">
    <thead>
      <tr><th><?= e(t('common.purchase_no')) ?></th><th><?= e(t('common.date')) ?></th><th><?= e(t('common.supplier')) ?></th><th><?= e(t('common.item_count')) ?></th>
          <th style="text-align:right"><?= e(t('common.amount')) ?></th><th><?= e(t('common.notes')) ?></th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['no_beli']) ?></b></td>
          <td><?= tgl_id($r['tanggal']) ?></td>
          <td><?= e($r['supplier'] ?? '-') ?></td>
          <td><?= (int) $r['jml'] ?> <?= e(t('common.items')) ?></td>
          <td style="text-align:right"><?= rupiah($r['total']) ?></td>
          <td><?= e($r['keterangan'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

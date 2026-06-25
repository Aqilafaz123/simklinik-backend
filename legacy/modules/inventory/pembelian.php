<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi');
$pageTitle = 'Pembelian Obat';

$rows = db()->query(
    "SELECT pb.id, pb.no_beli, pb.tanggal, pb.total, pb.keterangan, s.nama AS supplier,
            (SELECT COUNT(*) FROM pembelian_detail d WHERE d.pembelian_id=pb.id) AS jml
     FROM pembelian pb LEFT JOIN supplier s ON s.id=pb.supplier_id
     ORDER BY pb.id DESC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
  <div>
    <a href="<?= legacy_url('modules/inventory/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Inventory</a>
    <h2 style="display:inline-block;margin:0 0 0 8px"><?= app_icon("truck") ?> Pembelian Obat</h2>
  </div>
  <a class="btn" href="<?= legacy_url('modules/inventory/pembelian_form.php') ?>"><?= app_icon("plus") ?> Pembelian Baru</a>
</div>

<div class="table-wrap" style="margin-top:16px">
  <table class="datatable no-auto-num" style="width:100%">
    <thead>
      <tr><th>No. Beli</th><th>Tanggal</th><th>Supplier</th><th>Jml Item</th>
          <th style="text-align:right">Total</th><th>Keterangan</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= e($r['no_beli']) ?></b></td>
          <td><?= tgl_id($r['tanggal']) ?></td>
          <td><?= e($r['supplier'] ?? '-') ?></td>
          <td><?= (int) $r['jml'] ?> item</td>
          <td style="text-align:right"><?= rupiah($r['total']) ?></td>
          <td><?= e($r['keterangan'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

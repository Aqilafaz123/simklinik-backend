<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi', 'superadmin');
$pageTitle = t('common.new_purchase');
$user = current_user();

$supplier = db()->query("SELECT id,nama FROM supplier ORDER BY nama")->fetchAll();
$obat = db()->query("SELECT id,nama,harga_beli,stok FROM obat WHERE status='aktif' ORDER BY nama")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $supplierId = (int) ($_POST['supplier_id'] ?? 0) ?: null;
    $tanggal    = $_POST['tanggal'] ?? date('Y-m-d');
    $ket        = trim($_POST['keterangan'] ?? '') ?: null;
    $oId   = $_POST['obat_id'] ?? []; $oQty = $_POST['qty'] ?? [];
    $oHrg  = $_POST['harga_beli'] ?? []; $oBatch = $_POST['no_batch'] ?? []; $oExp = $_POST['tgl_expired'] ?? [];

    $items = [];
    foreach ($oId as $i => $oid) {
        $oid = (int) $oid; $qty = (int) ($oQty[$i] ?? 0);
        if (!$oid || $qty <= 0) continue;
        $items[] = [
            'obat_id' => $oid, 'qty' => $qty,
            'harga' => (float) ($oHrg[$i] ?? 0),
            'batch' => trim($oBatch[$i] ?? '') ?: null,
            'exp'   => ($oExp[$i] ?? '') !== '' ? $oExp[$i] : null,
        ];
    }
    if (!$items) $errors[] = 'Minimal satu obat dengan qty > 0.';

    if (!$errors) {
        try {
            db()->beginTransaction();
            $total = 0;
            foreach ($items as $it) $total += $it['qty'] * $it['harga'];
            $noBeli = generate_no('BELI', 'pembelian', 'no_beli');
            db()->prepare("INSERT INTO pembelian (no_beli,supplier_id,tanggal,total,keterangan,user_id) VALUES (?,?,?,?,?,?)")
              ->execute([$noBeli, $supplierId, $tanggal, $total, $ket, $user['id']]);
            $pembelianId = (int) db()->lastInsertId();

            $insDet  = db()->prepare("INSERT INTO pembelian_detail (pembelian_id,obat_id,no_batch,tgl_expired,qty,harga_beli,subtotal) VALUES (?,?,?,?,?,?,?)");
            $insBatch = db()->prepare("INSERT INTO obat_batch (obat_id,no_batch,tgl_expired,stok,harga_beli) VALUES (?,?,?,?,?)");
            $insMut  = db()->prepare("INSERT INTO stok_mutasi (obat_id,jenis,qty,stok_akhir,ref_tabel,ref_id,keterangan,user_id) VALUES (?,?,?,?,?,?,?,?)");
            foreach ($items as $it) {
                $sub = $it['qty'] * $it['harga'];
                $insDet->execute([$pembelianId, $it['obat_id'], $it['batch'], $it['exp'], $it['qty'], $it['harga'], $sub]);
                $insBatch->execute([$it['obat_id'], $it['batch'], $it['exp'], $it['qty'], $it['harga']]);
                db()->prepare("UPDATE obat SET stok = stok + ?, harga_beli = ? WHERE id = ?")
                  ->execute([$it['qty'], $it['harga'], $it['obat_id']]);
                $stokAkhir = (int) db()->query("SELECT stok FROM obat WHERE id={$it['obat_id']}")->fetchColumn();
                $insMut->execute([$it['obat_id'], 'masuk', $it['qty'], $stokAkhir, 'pembelian', $pembelianId, 'Pembelian ' . $noBeli, $user['id']]);
            }
            db()->commit();
            set_flash('success', "Pembelian $noBeli tersimpan. Stok obat diperbarui.");
            legacy_redirect('modules/inventory/pembelian.php');
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/inventory/pembelian.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> <?= e(t('common.purchase')) ?></a>

<?php if ($errors): ?><div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<form method="post" style="margin-top:14px">
  <?= sim_csrf_field() ?>
  <div class="card">
    <div class="form-row">
      <div class="form-group"><label><?= e(t('common.supplier')) ?></label>
        <select name="supplier_id" class="form-control">
          <option value=""><?= e(t('common.select_supplier')) ?></option>
          <?php foreach ($supplier as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['nama']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label><?= e(t('common.date')) ?></label><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="form-group"><label><?= e(t('common.notes')) ?></label><input type="text" name="keterangan" class="form-control"></div>
  </div>

  <div class="card" style="margin-top:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3><?= e(t('common.purchased_list')) ?></h3>
      <button type="button" class="btn btn-sm" onclick="addRow()"><?= app_icon("plus") ?> <?= e(t('common.add_medicine')) ?></button>
    </div>
    <table class="table-inline-form"><thead><tr>
      <th class="col-obat"><?= e(t('common.medicine')) ?></th><th class="col-batch"><?= e(t('common.batch_no')) ?></th><th class="col-exp"><?= e(t('common.expiry')) ?></th>
      <th class="col-qty"><?= e(t('common.qty')) ?></th><th class="col-harga"><?= e(t('common.buy_price')) ?></th><th class="col-del"></th>
    </tr></thead><tbody id="rows"></tbody></table>
  </div>

  <div style="margin:18px 0 40px"><button type="submit" class="btn btn-green"><?= app_icon("save") ?> <?= e(t('common.save_purchase')) ?></button></div>
</form>

<script>
var optObat = `<option value=""><?= e(t('common.select_medicine')) ?></option><?php foreach ($obat as $o): ?><option value="<?= $o['id'] ?>" data-harga="<?= (int)$o['harga_beli'] ?>"><?= e($o['nama']) ?> (<?= e(t('common.stock')) ?> <?= (int)$o['stok'] ?>)</option><?php endforeach; ?>`;
function addRow(){
  document.getElementById('rows').insertAdjacentHTML('beforeend',
    `<tr>
      <td><select class="form-control" name="obat_id[]" onchange="isiHarga(this)">${optObat}</select></td>
      <td><input class="form-control" name="no_batch[]"></td>
      <td><input type="date" class="form-control" name="tgl_expired[]"></td>
      <td><input type="number" min="1" class="form-control input-qty" name="qty[]" value="1"></td>
      <td><input type="number" min="0" step="any" class="form-control" name="harga_beli[]" value="0"></td>
      <td><button type="button" class="btn btn-sm btn-red" onclick="this.closest('tr').remove()"><?= app_icon("close") ?> </button></td>
    </tr>`);
}
function isiHarga(sel){
  var h = sel.options[sel.selectedIndex].getAttribute('data-harga') || 0;
  sel.closest('tr').querySelector('input[name="harga_beli[]"]').value = h;
}
addRow();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

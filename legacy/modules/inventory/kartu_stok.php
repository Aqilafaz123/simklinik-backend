<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi');
$pageTitle = 'Kartu Stok';

$obatId = (int) ($_GET['obat_id'] ?? 0);
$o = db()->prepare("SELECT o.*, k.nama AS kategori, s.nama AS satuan FROM obat o
     LEFT JOIN obat_kategori k ON k.id=o.kategori_id LEFT JOIN obat_satuan s ON s.id=o.satuan_id
     WHERE o.id=?");
$o->execute([$obatId]); $o = $o->fetch();
if (!$o) { set_flash('danger', 'Obat tidak ditemukan.'); legacy_redirect('modules/inventory/index.php'); }

$mutasi = db()->prepare(
    "SELECT m.*, u.nama AS petugas FROM stok_mutasi m LEFT JOIN users u ON u.id=m.user_id
     WHERE m.obat_id=? ORDER BY m.id DESC");
$mutasi->execute([$obatId]); $mutasi = $mutasi->fetchAll();

$batch = db()->prepare("SELECT * FROM obat_batch WHERE obat_id=? AND stok>0 ORDER BY tgl_expired");
$batch->execute([$obatId]); $batch = $batch->fetchAll();

$jenisBadge = ['masuk' => 'badge-green', 'keluar' => 'badge-red', 'opname' => 'badge-blue', 'penyesuaian' => 'badge-orange'];
require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/inventory/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Inventory</a>

<div class="card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:var(--fs-sub);font-weight:700"><?= e($o['nama']) ?></div>
    <div style="color:var(--muted)">Kode <code><?= e($o['kode']) ?></code> &middot; <?= e($o['kategori'] ?? '-') ?> &middot; satuan <?= e($o['satuan'] ?? '-') ?></div>
  </div>
  <div style="text-align:right">
    <div class="num" style="font-size:30px;font-weight:700"><?= (int) $o['stok'] ?></div>
    <div class="lbl" style="color:var(--muted)">stok saat ini (min <?= (int)$o['stok_minimal'] ?>)</div>
  </div>
</div>

<?php if ($batch): ?>
<div class="card" style="margin-top:14px">
  <h3 style="margin-bottom:10px">Batch Aktif</h3>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    <?php foreach ($batch as $b): $exp = $b['tgl_expired'];
      $soon = $exp && strtotime($exp) <= strtotime('+30 day'); ?>
      <span class="badge <?= $soon ? 'badge-red' : 'badge-gray' ?>">
        <?= e($b['no_batch'] ?? 'tanpa batch') ?> &middot; stok <?= (int)$b['stok'] ?>
        <?= $exp ? ' &middot; exp ' . tgl_id($exp) : '' ?>
      </span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="section-title">Riwayat Mutasi Stok</div>
<div class="table-wrap">
  <table class="datatable" style="width:100%">
    <thead><tr><th>Waktu</th><th>Jenis</th><th>Qty</th><th>Stok Akhir</th><th>Referensi</th><th>Keterangan</th><th>Petugas</th></tr></thead>
    <tbody>
      <?php foreach ($mutasi as $m): ?>
        <tr>
          <td><?= tgl_id($m['tanggal'], true) ?></td>
          <td><span class="badge <?= $jenisBadge[$m['jenis']] ?? 'badge-gray' ?>"><?= e(ucfirst($m['jenis'])) ?></span></td>
          <td><b style="color:<?= $m['qty'] >= 0 ? 'var(--green)' : 'var(--red)' ?>"><?= ($m['qty'] >= 0 ? '+' : '') . (int)$m['qty'] ?></b></td>
          <td><?= (int) $m['stok_akhir'] ?></td>
          <td><?= e($m['ref_tabel'] ?? '-') ?></td>
          <td><?= e($m['keterangan'] ?? '-') ?></td>
          <td><?= e($m['petugas'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

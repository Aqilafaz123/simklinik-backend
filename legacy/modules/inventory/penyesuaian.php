<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi');
$pageTitle = 'Penyesuaian Stok';
$user = current_user();

$obat = db()->query("SELECT id,nama,stok FROM obat WHERE status='aktif' ORDER BY nama")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $obatId = (int) ($_POST['obat_id'] ?? 0);
    $fisik  = (int) ($_POST['stok_fisik'] ?? -1);
    $ket    = trim($_POST['keterangan'] ?? '') ?: 'Stok opname';

    $row = db()->prepare("SELECT stok FROM obat WHERE id=?"); $row->execute([$obatId]);
    $sistem = $row->fetchColumn();
    if ($sistem === false) $errors[] = 'Obat tidak ditemukan.';
    if ($fisik < 0) $errors[] = 'Stok fisik harus diisi (>= 0).';

    if (!$errors) {
        $selisih = $fisik - (int) $sistem;
        try {
            db()->beginTransaction();
            db()->prepare("UPDATE obat SET stok=? WHERE id=?")->execute([$fisik, $obatId]);
            db()->prepare("INSERT INTO stok_mutasi (obat_id,jenis,qty,stok_akhir,ref_tabel,keterangan,user_id)
                           VALUES (?, 'opname', ?, ?, 'opname', ?, ?)")
              ->execute([$obatId, $selisih, $fisik, $ket, $user['id']]);
            db()->commit();
            set_flash('success', 'Penyesuaian tersimpan. Selisih: ' . ($selisih >= 0 ? '+' : '') . $selisih . '.');
            legacy_redirect('modules/inventory/kartu_stok.php?obat_id=' . $obatId);
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $ex->getMessage();
        }
    }
}

// riwayat opname terbaru
$riwayat = db()->query(
    "SELECT m.tanggal, o.nama AS obat, m.qty, m.stok_akhir, m.keterangan, u.nama AS petugas
     FROM stok_mutasi m JOIN obat o ON o.id=m.obat_id LEFT JOIN users u ON u.id=m.user_id
     WHERE m.jenis='opname' ORDER BY m.id DESC LIMIT 50")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/inventory/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> Inventory</a>

<?php if ($errors): ?><div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<div class="card" style="max-width:560px;margin-top:14px">
  <h2 style="margin-bottom:6px"><?= app_icon("scale") ?> Penyesuaian / Stok Opname</h2>
  <p style="color:var(--muted);margin-bottom:16px">Masukkan jumlah fisik hasil hitung. Sistem akan mencatat selisihnya.</p>
  <form method="post">
    <?= sim_csrf_field() ?>
    <div class="form-group"><label>Obat *</label>
      <select name="obat_id" id="obat" class="form-control" required onchange="showStok()">
        <option value="">- Pilih Obat -</option>
        <?php foreach ($obat as $o): ?><option value="<?= $o['id'] ?>" data-stok="<?= (int)$o['stok'] ?>"><?= e($o['nama']) ?> (stok sistem <?= (int)$o['stok'] ?>)</option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Stok Sistem Saat Ini</label>
      <input class="form-control" id="stokSistem" value="-" disabled style="background:#f1f5f9"></div>
    <div class="form-group"><label>Stok Fisik (hasil hitung) *</label>
      <input type="number" min="0" name="stok_fisik" class="form-control" required></div>
    <div class="form-group"><label>Keterangan</label>
      <input type="text" name="keterangan" class="form-control" placeholder="cth: Stok opname bulanan / koreksi rusak"></div>
    <button class="btn btn-green" type="submit"><?= app_icon("save") ?> Simpan Penyesuaian</button>
  </form>
</div>

<div class="section-title">Riwayat Penyesuaian</div>
<div class="table-wrap">
  <table class="datatable" style="width:100%">
    <thead><tr><th>Waktu</th><th>Obat</th><th>Selisih</th><th>Stok Akhir</th><th>Keterangan</th><th>Petugas</th></tr></thead>
    <tbody>
      <?php foreach ($riwayat as $r): ?>
        <tr>
          <td><?= tgl_id($r['tanggal'], true) ?></td>
          <td><?= e($r['obat']) ?></td>
          <td><span class="badge <?= $r['qty'] >= 0 ? 'badge-green' : 'badge-red' ?>"><?= ($r['qty'] >= 0 ? '+' : '') . (int)$r['qty'] ?></span></td>
          <td><?= (int) $r['stok_akhir'] ?></td>
          <td><?= e($r['keterangan'] ?? '-') ?></td>
          <td><?= e($r['petugas'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
function showStok(){
  var s = document.getElementById('obat');
  document.getElementById('stokSistem').value = s.options[s.selectedIndex].getAttribute('data-stok') || '-';
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

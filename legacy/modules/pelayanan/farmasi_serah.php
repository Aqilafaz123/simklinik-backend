<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('farmasi');
$pageTitle = t('pages.drug_dispense');
$user = current_user();

$resepId = (int) ($_GET['resep_id'] ?? $_POST['resep_id'] ?? 0);

$resep = db()->prepare(
    "SELECT r.*, k.id AS kunjungan_id, k.no_kunjungan, k.status AS kj_status,
            p.no_mr, p.nama AS pasien, p.alergi
     FROM resep r
     JOIN kunjungan k ON k.id = r.kunjungan_id
     JOIN pasien p ON p.id = k.pasien_id
     WHERE r.id = ?");
$resep->execute([$resepId]);
$resep = $resep->fetch();
if (!$resep) { set_flash('danger', 'Resep tidak ditemukan.'); legacy_redirect('modules/pelayanan/farmasi.php'); }

$detail = db()->prepare(
    "SELECT rd.*, o.nama AS obat, o.stok, o.satuan_id, s.nama AS satuan
     FROM resep_detail rd
     JOIN obat o ON o.id = rd.obat_id
     LEFT JOIN obat_satuan s ON s.id = o.satuan_id
     WHERE rd.resep_id = ?");
$detail->execute([$resepId]);
$detail = $detail->fetchAll();

// cek kecukupan stok
$kurang = [];
foreach ($detail as $d) {
    if ($d['qty'] > $d['stok']) $kurang[] = $d['obat'] . " (butuh {$d['qty']}, stok {$d['stok']})";
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    if ($resep['status'] === 'diserahkan') {
        $errors[] = 'Resep ini sudah diserahkan sebelumnya.';
    } elseif ($kurang) {
        $errors[] = 'Stok tidak mencukupi: ' . implode(', ', $kurang);
    } else {
        try {
            db()->beginTransaction();
            $updStok = db()->prepare("UPDATE obat SET stok = stok - ? WHERE id = ?");
            $insMut  = db()->prepare(
                "INSERT INTO stok_mutasi (obat_id,jenis,qty,stok_akhir,ref_tabel,ref_id,keterangan,user_id)
                 VALUES (?, 'keluar', ?, ?, 'resep', ?, ?, ?)");
            foreach ($detail as $d) {
                $stokAkhir = (int) $d['stok'] - (int) $d['qty'];
                $updStok->execute([$d['qty'], $d['obat_id']]);
                $insMut->execute([$d['obat_id'], -(int) $d['qty'], $stokAkhir, $resepId,
                    'Resep ' . $resep['no_kunjungan'], $user['id']]);
            }
            db()->prepare("UPDATE resep SET status='diserahkan' WHERE id=?")->execute([$resepId]);
            db()->prepare("UPDATE kunjungan SET status='billing' WHERE id=?")->execute([$resep['kunjungan_id']]);
            db()->commit();
            set_flash('success', 'Obat berhasil diserahkan & stok diperbarui. Pasien diteruskan ke Billing.');
            legacy_redirect('modules/pelayanan/farmasi.php');
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $errors[] = 'Gagal menyerahkan: ' . $ex->getMessage();
        }
    }
}

$total = 0; foreach ($detail as $d) $total += $d['subtotal'];

require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/pelayanan/farmasi.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> <?= e(t('common.back')) ?></a>

<div class="card" style="margin-top:14px">
  <div style="font-size:var(--fs-sub);font-weight:700"><?= e($resep['pasien']) ?></div>
  <div style="color:var(--muted)">No. MR <b><?= e($resep['no_mr']) ?></b> &middot; <?= e($resep['no_kunjungan']) ?></div>
  <?php if (!empty($resep['alergi'])): ?><span class="badge badge-red" style="margin-top:8px"><?= app_icon("alert") ?> Alergi: <?= e($resep['alergi']) ?></span><?php endif; ?>
</div>

<?php if ($errors): ?><div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
<?php if ($kurang && !$errors): ?><div class="alert alert-warning" style="margin-top:14px"><?= app_icon("alert") ?> Stok tidak mencukupi: <?= e(implode(', ', $kurang)) ?></div><?php endif; ?>

<div class="section-title">Rincian Resep</div>
<div class="table-wrap">
  <table style="width:100%">
    <thead><tr><th>Obat</th><th>Qty</th><th>Dosis</th><th>Aturan Pakai</th><th>Stok Saat Ini</th><th style="text-align:right">Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($detail as $d): ?>
        <tr>
          <td><?= e($d['obat']) ?></td>
          <td><?= (int) $d['qty'] ?> <?= e($d['satuan'] ?? '') ?></td>
          <td><?= e($d['dosis'] ?? '-') ?></td>
          <td><?= e($d['aturan_pakai'] ?? '-') ?></td>
          <td><?= (int) $d['stok'] ?> <?= $d['qty'] > $d['stok'] ? '<span class="badge badge-red">kurang</span>' : '' ?></td>
          <td style="text-align:right"><?= rupiah($d['subtotal']) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="5" style="text-align:right;font-weight:700">Total Obat</td><td style="text-align:right;font-weight:700"><?= rupiah($total) ?></td></tr>
    </tbody>
  </table>
</div>

<?php if (!empty($resep['catatan'])): ?><p style="margin-top:10px;color:var(--muted)">Catatan: <?= e($resep['catatan']) ?></p><?php endif; ?>

<form method="post" style="margin:18px 0 40px" onsubmit="return confirm('Serahkan obat & potong stok? Tindakan ini tidak bisa dibatalkan.')">
  <?= sim_csrf_field() ?>
  <input type="hidden" name="resep_id" value="<?= (int) $resepId ?>">
  <button type="submit" class="btn btn-green" <?= $kurang || $resep['status']==='diserahkan' ? 'disabled style="opacity:.5"' : '' ?>>
    <?= app_icon("check") ?> Serahkan Obat &amp; Potong Stok
  </button>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

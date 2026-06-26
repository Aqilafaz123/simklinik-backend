<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('dokter');
$pageTitle = t('pages.exam');

$kunjunganId = (int) ($_GET['kunjungan_id'] ?? $_POST['kunjungan_id'] ?? 0);

// Data kunjungan + pasien
$stmt = db()->prepare(
    "SELECT k.*, p.no_mr, p.nama AS pasien, p.jenis_kelamin, p.tgl_lahir, p.alergi,
            po.nama AS poli, d.nama AS dokter
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     WHERE k.id = ?");
$stmt->execute([$kunjunganId]);
$kj = $stmt->fetch();
if (!$kj) { set_flash('danger', 'Kunjungan tidak ditemukan.'); legacy_redirect('modules/pelayanan/index.php'); }

// Master untuk pilihan
$mTindakan = db()->query("SELECT id,nama,tarif FROM tindakan WHERE status='aktif' ORDER BY nama")->fetchAll();
$mObat     = db()->query("SELECT id,nama,harga_beli,markup_persen,stok FROM obat WHERE status='aktif' ORDER BY nama")->fetchAll();
$mLab      = db()->query("SELECT id,nama,nilai_rujukan,tarif,markup_persen FROM lab_pemeriksaan WHERE status='aktif' ORDER BY nama")->fetchAll();
$mRad      = db()->query("SELECT id,nama,tarif,markup_persen FROM rad_pemeriksaan WHERE status='aktif' ORDER BY nama")->fetchAll();
$mDiag     = db()->query("SELECT id,nama,tarif FROM diag_pemeriksaan WHERE status='aktif' ORDER BY nama")->fetchAll();
$mFisio    = db()->query("SELECT id,nama,tarif FROM fisio_pemeriksaan WHERE status='aktif' ORDER BY nama")->fetchAll();

// ---------------- SIMPAN ----------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $aksi = $_POST['aksi'] ?? 'simpan';   // simpan | selesai
    try {
        db()->beginTransaction();

        // 1) Upsert rekam medis (SOAP + vital)
        $rm = db()->prepare("SELECT id FROM rekam_medis WHERE kunjungan_id=?");
        $rm->execute([$kunjunganId]);
        $rmId = $rm->fetchColumn();
        $fields = [
            'subjective' => trim($_POST['subjective'] ?? ''), 'objective' => trim($_POST['objective'] ?? ''),
            'assessment' => trim($_POST['assessment'] ?? ''), 'plan' => trim($_POST['plan'] ?? ''),
            'edukasi' => trim($_POST['edukasi'] ?? ''), 'tekanan_darah' => trim($_POST['tekanan_darah'] ?? ''),
            'suhu' => trim($_POST['suhu'] ?? ''), 'nadi' => trim($_POST['nadi'] ?? ''),
            'berat_badan' => trim($_POST['berat_badan'] ?? ''), 'tinggi_badan' => trim($_POST['tinggi_badan'] ?? ''),
        ];
        if ($rmId) {
            db()->prepare("UPDATE rekam_medis SET dokter_id=?,subjective=?,objective=?,assessment=?,plan=?,
                edukasi=?,tekanan_darah=?,suhu=?,nadi=?,berat_badan=?,tinggi_badan=? WHERE id=?")
              ->execute([$kj['dokter_id'], $fields['subjective'], $fields['objective'], $fields['assessment'],
                  $fields['plan'], $fields['edukasi'], $fields['tekanan_darah'], $fields['suhu'],
                  $fields['nadi'], $fields['berat_badan'], $fields['tinggi_badan'], $rmId]);
        } else {
            db()->prepare("INSERT INTO rekam_medis (kunjungan_id,dokter_id,subjective,objective,assessment,plan,
                edukasi,tekanan_darah,suhu,nadi,berat_badan,tinggi_badan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
              ->execute([$kunjunganId, $kj['dokter_id'], $fields['subjective'], $fields['objective'],
                  $fields['assessment'], $fields['plan'], $fields['edukasi'], $fields['tekanan_darah'],
                  $fields['suhu'], $fields['nadi'], $fields['berat_badan'], $fields['tinggi_badan']]);
            $rmId = (int) db()->lastInsertId();
        }

        // 2) Diagnosa (ganti total)
        db()->prepare("DELETE FROM rm_diagnosa WHERE rekam_medis_id=?")->execute([$rmId]);
        $dKode = $_POST['diag_kode'] ?? []; $dNama = $_POST['diag_nama'] ?? []; $dJenis = $_POST['diag_jenis'] ?? [];
        $insDiag = db()->prepare("INSERT INTO rm_diagnosa (rekam_medis_id,kode_icd10,diagnosa,jenis) VALUES (?,?,?,?)");
        foreach ($dNama as $i => $nm) {
            $nm = trim($nm); if ($nm === '') continue;
            $insDiag->execute([$rmId, trim($dKode[$i] ?? '') ?: null, $nm,
                in_array($dJenis[$i] ?? 'primer', ['primer','sekunder'], true) ? $dJenis[$i] : 'primer']);
        }

        // 3) Tindakan (ganti total)
        db()->prepare("DELETE FROM rm_tindakan WHERE rekam_medis_id=?")->execute([$rmId]);
        $tId = $_POST['tind_id'] ?? []; $tQty = $_POST['tind_qty'] ?? [];
        $tarifMap = []; foreach ($mTindakan as $t) $tarifMap[$t['id']] = ['nama' => $t['nama'], 'tarif' => $t['tarif']];
        $insTind = db()->prepare("INSERT INTO rm_tindakan (rekam_medis_id,tindakan_id,nama_tindakan,qty,tarif,subtotal) VALUES (?,?,?,?,?,?)");
        foreach ($tId as $i => $tid) {
            $tid = (int) $tid; if (!$tid || !isset($tarifMap[$tid])) continue;
            $qty = max(1, (int) ($tQty[$i] ?? 1));
            $tarif = (float) $tarifMap[$tid]['tarif'];
            $insTind->execute([$rmId, $tid, $tarifMap[$tid]['nama'], $qty, $tarif, $tarif * $qty]);
        }

        // 4) Lab order (ganti total)
        db()->prepare("DELETE lod FROM lab_order_detail lod JOIN lab_order lo ON lo.id=lod.lab_order_id WHERE lo.kunjungan_id=?")->execute([$kunjunganId]);
        db()->prepare("DELETE FROM lab_order WHERE kunjungan_id=?")->execute([$kunjunganId]);
        $labPick = $_POST['lab_pick'] ?? []; $labHasil = $_POST['lab_hasil'] ?? []; $labQtyIn = $_POST['lab_qty'] ?? [];
        if ($labPick) {
            db()->prepare("INSERT INTO lab_order (kunjungan_id,status) VALUES (?, 'permintaan')")->execute([$kunjunganId]);
            $labOrderId = (int) db()->lastInsertId();
            $insLab = db()->prepare("INSERT INTO lab_order_detail (lab_order_id,pemeriksaan_id,hasil,nilai_rujukan,tarif,qty,subtotal) VALUES (?,?,?,?,?,?,?)");
            $labMap = []; foreach ($mLab as $l) $labMap[$l['id']] = $l;
            foreach ($labPick as $pid) {
                $pid = (int) $pid; if (!isset($labMap[$pid])) continue;
                $qty = max(1, (int) ($labQtyIn[$pid] ?? 1)); 
                $base = (float) $labMap[$pid]['tarif'];
                $markup = (float) ($labMap[$pid]['markup_persen'] ?? 0);
                $tarif = $base + ($base * $markup / 100);
                $insLab->execute([$labOrderId, $pid, trim($labHasil[$pid] ?? '') ?: null,
                    $labMap[$pid]['nilai_rujukan'], $tarif, $qty, $tarif * $qty]);
            }
        }

        // 5) Radiologi order (ganti total)
        db()->prepare("DELETE rod FROM rad_order_detail rod JOIN rad_order ro ON ro.id=rod.rad_order_id WHERE ro.kunjungan_id=?")->execute([$kunjunganId]);
        db()->prepare("DELETE FROM rad_order WHERE kunjungan_id=?")->execute([$kunjunganId]);
        $radPick = $_POST['rad_pick'] ?? []; $radHasil = $_POST['rad_hasil'] ?? []; $radQtyIn = $_POST['rad_qty'] ?? [];
        if ($radPick) {
            db()->prepare("INSERT INTO rad_order (kunjungan_id,status) VALUES (?, 'permintaan')")->execute([$kunjunganId]);
            $radOrderId = (int) db()->lastInsertId();
            $insRad = db()->prepare("INSERT INTO rad_order_detail (rad_order_id,pemeriksaan_id,hasil,tarif,qty,subtotal) VALUES (?,?,?,?,?,?)");
            $radMap = []; foreach ($mRad as $r) $radMap[$r['id']] = $r;
            foreach ($radPick as $pid) {
                $pid = (int) $pid; if (!isset($radMap[$pid])) continue;
                $qty = max(1, (int) ($radQtyIn[$pid] ?? 1)); 
                $base = (float) $radMap[$pid]['tarif'];
                $markup = (float) ($radMap[$pid]['markup_persen'] ?? 0);
                $tarif = $base + ($base * $markup / 100);
                $insRad->execute([$radOrderId, $pid, trim($radHasil[$pid] ?? '') ?: null, $tarif, $qty, $tarif * $qty]);
            }
        }

        // 5b) Diagnostik order (ganti total)
        db()->prepare("DELETE dod FROM diag_order_detail dod JOIN diag_order o ON o.id=dod.diag_order_id WHERE o.kunjungan_id=?")->execute([$kunjunganId]);
        db()->prepare("DELETE FROM diag_order WHERE kunjungan_id=?")->execute([$kunjunganId]);
        $diagPick = $_POST['diag_pick'] ?? []; $diagHasil = $_POST['diag_hasil'] ?? []; $diagQtyIn = $_POST['diag_qty'] ?? [];
        if ($diagPick) {
            db()->prepare("INSERT INTO diag_order (kunjungan_id,status) VALUES (?, 'permintaan')")->execute([$kunjunganId]);
            $diagOrderId = (int) db()->lastInsertId();
            $insDiagO = db()->prepare("INSERT INTO diag_order_detail (diag_order_id,pemeriksaan_id,hasil,tarif,qty,subtotal) VALUES (?,?,?,?,?,?)");
            $diagMap = []; foreach ($mDiag as $x) $diagMap[$x['id']] = $x;
            foreach ($diagPick as $pid) {
                $pid = (int) $pid; if (!isset($diagMap[$pid])) continue;
                $qty = max(1, (int) ($diagQtyIn[$pid] ?? 1)); $tarif = (float) $diagMap[$pid]['tarif'];
                $insDiagO->execute([$diagOrderId, $pid, trim($diagHasil[$pid] ?? '') ?: null, $tarif, $qty, $tarif * $qty]);
            }
        }

        // 5c) Fisioterapi order (ganti total)
        db()->prepare("DELETE fod FROM fisio_order_detail fod JOIN fisio_order o ON o.id=fod.fisio_order_id WHERE o.kunjungan_id=?")->execute([$kunjunganId]);
        db()->prepare("DELETE FROM fisio_order WHERE kunjungan_id=?")->execute([$kunjunganId]);
        $fisioPick = $_POST['fisio_pick'] ?? []; $fisioHasil = $_POST['fisio_hasil'] ?? []; $fisioQtyIn = $_POST['fisio_qty'] ?? [];
        if ($fisioPick) {
            db()->prepare("INSERT INTO fisio_order (kunjungan_id,status) VALUES (?, 'permintaan')")->execute([$kunjunganId]);
            $fisioOrderId = (int) db()->lastInsertId();
            $insFisioO = db()->prepare("INSERT INTO fisio_order_detail (fisio_order_id,pemeriksaan_id,hasil,tarif,qty,subtotal) VALUES (?,?,?,?,?,?)");
            $fisioMap = []; foreach ($mFisio as $x) $fisioMap[$x['id']] = $x;
            foreach ($fisioPick as $pid) {
                $pid = (int) $pid; if (!isset($fisioMap[$pid])) continue;
                $qty = max(1, (int) ($fisioQtyIn[$pid] ?? 1)); $tarif = (float) $fisioMap[$pid]['tarif'];
                $insFisioO->execute([$fisioOrderId, $pid, trim($fisioHasil[$pid] ?? '') ?: null, $tarif, $qty, $tarif * $qty]);
            }
        }

        // 6) Resep (ganti total)
        db()->prepare("DELETE rd FROM resep_detail rd JOIN resep r ON r.id=rd.resep_id WHERE r.kunjungan_id=?")->execute([$kunjunganId]);
        db()->prepare("DELETE FROM resep WHERE kunjungan_id=?")->execute([$kunjunganId]);
        $oId = $_POST['obat_id'] ?? []; $oQty = $_POST['obat_qty'] ?? [];
        $oDosis = $_POST['obat_dosis'] ?? []; $oAturan = $_POST['obat_aturan'] ?? [];
        $jmlResep = 0;
        $obatMap = []; foreach ($mObat as $o) $obatMap[$o['id']] = $o;
        $validObat = [];
        foreach ($oId as $i => $oid) {
            $oid = (int) $oid; if (!$oid || !isset($obatMap[$oid])) continue;
            $validObat[] = [$oid, max(1, (int) ($oQty[$i] ?? 1)), trim($oDosis[$i] ?? ''), trim($oAturan[$i] ?? '')];
        }
        if ($validObat) {
            db()->prepare("INSERT INTO resep (kunjungan_id,dokter_id,status,catatan) VALUES (?,?, 'baru', ?)")
              ->execute([$kunjunganId, $kj['dokter_id'], trim($_POST['resep_catatan'] ?? '') ?: null]);
            $resepId = (int) db()->lastInsertId();
            $insResep = db()->prepare("INSERT INTO resep_detail (resep_id,obat_id,qty,dosis,aturan_pakai,harga,subtotal) VALUES (?,?,?,?,?,?,?)");
            foreach ($validObat as $v) {
                [$oid, $qty, $dosis, $aturan] = $v;
                $base = (float) $obatMap[$oid]['harga_beli'];
                $markup = (float) ($obatMap[$oid]['markup_persen'] ?? 0);
                $harga = $base + ($base * $markup / 100);
                $insResep->execute([$resepId, $oid, $qty, $dosis ?: null, $aturan ?: null, $harga, $harga * $qty]);
                $jmlResep++;
            }
        }

        // 7) Status kunjungan
        if ($aksi === 'selesai') {
            $newStatus = $jmlResep > 0 ? 'farmasi' : 'billing';
            db()->prepare("UPDATE kunjungan SET status=? WHERE id=?")->execute([$newStatus, $kunjunganId]);
            db()->commit();
            set_flash('success', 'Pemeriksaan selesai. Pasien diteruskan ke ' .
                ($newStatus === 'farmasi' ? 'Farmasi.' : 'Billing.'));
            legacy_redirect('modules/pelayanan/index.php');
        } else {
            db()->prepare("UPDATE kunjungan SET status='periksa' WHERE id=?")->execute([$kunjunganId]);
            db()->commit();
            set_flash('success', 'Data pemeriksaan tersimpan (draft).');
            legacy_redirect('modules/pelayanan/periksa.php?kunjungan_id=' . $kunjunganId);
        }
    } catch (Throwable $ex) {
        if (db()->inTransaction()) db()->rollBack();
        $errors[] = 'Gagal menyimpan: ' . $ex->getMessage();
    }
}

// ---------------- LOAD data tersimpan untuk form ----------------
$rmRow = db()->prepare("SELECT * FROM rekam_medis WHERE kunjungan_id=?");
$rmRow->execute([$kunjunganId]); $rmRow = $rmRow->fetch() ?: [];
$rmId2 = $rmRow['id'] ?? 0;

$diagRows = []; $tindRows = [];
if ($rmId2) {
    $s = db()->prepare("SELECT * FROM rm_diagnosa WHERE rekam_medis_id=?"); $s->execute([$rmId2]); $diagRows = $s->fetchAll();
    $s = db()->prepare("SELECT * FROM rm_tindakan WHERE rekam_medis_id=?"); $s->execute([$rmId2]); $tindRows = $s->fetchAll();
}
// lab/rad terpilih + hasil
$labSel = []; $labQty = []; $s = db()->prepare("SELECT lod.pemeriksaan_id,lod.hasil,lod.qty FROM lab_order_detail lod JOIN lab_order lo ON lo.id=lod.lab_order_id WHERE lo.kunjungan_id=?");
$s->execute([$kunjunganId]); foreach ($s->fetchAll() as $r) { $labSel[$r['pemeriksaan_id']] = $r['hasil']; $labQty[$r['pemeriksaan_id']] = (int)$r['qty']; }
$radSel = []; $radQty = []; $s = db()->prepare("SELECT rod.pemeriksaan_id,rod.hasil,rod.qty FROM rad_order_detail rod JOIN rad_order ro ON ro.id=rod.rad_order_id WHERE ro.kunjungan_id=?");
$s->execute([$kunjunganId]); foreach ($s->fetchAll() as $r) { $radSel[$r['pemeriksaan_id']] = $r['hasil']; $radQty[$r['pemeriksaan_id']] = (int)$r['qty']; }
$diagSel = []; $diagQty = []; $s = db()->prepare("SELECT dod.pemeriksaan_id,dod.hasil,dod.qty FROM diag_order_detail dod JOIN diag_order o ON o.id=dod.diag_order_id WHERE o.kunjungan_id=?");
$s->execute([$kunjunganId]); foreach ($s->fetchAll() as $r) { $diagSel[$r['pemeriksaan_id']] = $r['hasil']; $diagQty[$r['pemeriksaan_id']] = (int)$r['qty']; }
$fisioSel = []; $fisioQty = []; $s = db()->prepare("SELECT fod.pemeriksaan_id,fod.hasil,fod.qty FROM fisio_order_detail fod JOIN fisio_order o ON o.id=fod.fisio_order_id WHERE o.kunjungan_id=?");
$s->execute([$kunjunganId]); foreach ($s->fetchAll() as $r) { $fisioSel[$r['pemeriksaan_id']] = $r['hasil']; $fisioQty[$r['pemeriksaan_id']] = (int)$r['qty']; }
// resep
$resepRows = []; $resepCatatan = '';
$s = db()->prepare("SELECT r.catatan, rd.* FROM resep r JOIN resep_detail rd ON rd.resep_id=r.id WHERE r.kunjungan_id=?");
$s->execute([$kunjunganId]); $resepRows = $s->fetchAll();
if ($resepRows) $resepCatatan = $resepRows[0]['catatan'] ?? '';

$rv = fn($k) => e($rmRow[$k] ?? '');
$umur = $kj['tgl_lahir'] ? (int) ((time() - strtotime($kj['tgl_lahir'])) / 31556952) . ' th' : '-';

require_once __DIR__ . '/../../includes/header.php';
?>
<a href="<?= legacy_url('modules/pelayanan/index.php') ?>" class="btn btn-light btn-sm"><?= app_icon("arrowleft") ?> <?= e(t('common.back_to_queue')) ?></a>

<!-- Identitas pasien -->
<div class="card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:var(--fs-sub);font-weight:700"><?= e($kj['pasien']) ?></div>
    <div style="color:var(--muted)">
      No. MR <b><?= e($kj['no_mr']) ?></b> &middot; <?= $kj['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
      &middot; <?= $umur ?> &middot; <?= e($kj['poli']) ?> &middot; <?= e($kj['dokter'] ?? 'Dokter belum ditentukan') ?>
    </div>
    <div style="color:var(--muted);margin-top:4px">Keluhan awal: <?= e($kj['keluhan_awal'] ?? '-') ?></div>
  </div>
  <div style="text-align:right">
    <div class="badge badge-blue">No. <?= e($kj['no_kunjungan']) ?></div>
    <?php if (!empty($kj['alergi'])): ?><br><span class="badge badge-red" style="margin-top:6px"><?= app_icon("alert") ?> Alergi: <?= e($kj['alergi']) ?></span><?php endif; ?>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<form method="post" id="formPeriksa">
  <?= sim_csrf_field() ?>
  <input type="hidden" name="kunjungan_id" value="<?= (int) $kunjunganId ?>">
  <input type="hidden" name="aksi" id="aksi" value="simpan">

  <!-- Vital sign -->
  <div class="card" style="margin-top:14px">
    <h3 style="margin-bottom:12px">Tanda Vital</h3>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px">
      <div class="form-group"><label>Tekanan Darah</label><input class="form-control" name="tekanan_darah" value="<?= $rv('tekanan_darah') ?>" placeholder="120/80"></div>
      <div class="form-group"><label>Suhu (°C)</label><input class="form-control" name="suhu" value="<?= $rv('suhu') ?>" placeholder="36.5"></div>
      <div class="form-group"><label>Nadi (x/mnt)</label><input class="form-control" name="nadi" value="<?= $rv('nadi') ?>" placeholder="80"></div>
      <div class="form-group"><label>Berat (kg)</label><input class="form-control" name="berat_badan" value="<?= $rv('berat_badan') ?>"></div>
      <div class="form-group"><label>Tinggi (cm)</label><input class="form-control" name="tinggi_badan" value="<?= $rv('tinggi_badan') ?>"></div>
    </div>
  </div>

  <!-- SOAP -->
  <div class="card" style="margin-top:14px">
    <h3 style="margin-bottom:12px">Rekam Medis (SOAP)</h3>
    <div class="form-row">
      <div class="form-group"><label>S — Subjective (Anamnesa / Keluhan)</label><textarea class="form-control" name="subjective" rows="3"><?= $rv('subjective') ?></textarea></div>
      <div class="form-group"><label>O — Objective (Pemeriksaan Fisik)</label><textarea class="form-control" name="objective" rows="3"><?= $rv('objective') ?></textarea></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>A — Assessment (Penilaian)</label><textarea class="form-control" name="assessment" rows="3"><?= $rv('assessment') ?></textarea></div>
      <div class="form-group"><label>P — Plan (Rencana Terapi)</label><textarea class="form-control" name="plan" rows="3"><?= $rv('plan') ?></textarea></div>
    </div>
    <div class="form-group"><label>Edukasi Pasien</label><textarea class="form-control" name="edukasi" rows="2"><?= $rv('edukasi') ?></textarea></div>
  </div>

  <!-- Diagnosa -->
  <div class="card" style="margin-top:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>Diagnosa (ICD-10)</h3>
      <button type="button" class="btn btn-sm" onclick="addDiag()"><?= app_icon("plus") ?> <?= e(t('common.add_diagnosis')) ?></button>
    </div>
    <table style="width:100%"><thead><tr><th style="width:160px">Kode ICD-10</th><th>Diagnosa</th><th style="width:180px">Jenis</th><th style="width:50px"></th></tr></thead>
      <tbody id="diagBody">
      <?php foreach ($diagRows as $d): ?>
        <tr>
          <td><input class="form-control" name="diag_kode[]" value="<?= e($d['kode_icd10']) ?>"></td>
          <td><input class="form-control" name="diag_nama[]" value="<?= e($d['diagnosa']) ?>"></td>
          <td><select class="form-control" name="diag_jenis[]"><option value="primer" <?= $d['jenis']==='primer'?'selected':'' ?>>Primer</option><option value="sekunder" <?= $d['jenis']==='sekunder'?'selected':'' ?>>Sekunder</option></select></td>
          <td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon("close") ?> </button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Tindakan -->
  <div class="card" style="margin-top:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>Medical Service</h3>
      <button type="button" class="btn btn-sm" onclick="addTind()"><?= app_icon("plus") ?> <?= e(t('common.add_medical_service')) ?></button>
    </div>
    <table style="width:100%"><thead><tr><th>Medical Service</th><th style="width:120px">Qty</th><th style="width:50px"></th></tr></thead>
      <tbody id="tindBody">
      <?php foreach ($tindRows as $t): ?>
        <tr>
          <td><select class="form-control" name="tind_id[]"><?php foreach ($mTindakan as $m): ?><option value="<?= $m['id'] ?>" <?= $m['id']==$t['tindakan_id']?'selected':'' ?>><?= e($m['nama']) ?> — <?= rupiah($m['tarif']) ?></option><?php endforeach; ?></select></td>
          <td><input type="number" min="1" class="form-control" name="tind_qty[]" value="<?= (int)$t['qty'] ?>"></td>
          <td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon("close") ?> </button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Penunjang Lab & Radiologi -->
  <div class="form-row" style="margin-top:14px">
    <div class="card">
      <h3 style="margin-bottom:12px">Permintaan Laboratorium</h3>
      <?php foreach ($mLab as $l): $sel = array_key_exists($l['id'], $labSel); ?>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
          <input type="checkbox" name="lab_pick[]" value="<?= $l['id'] ?>" id="lab<?= $l['id'] ?>" <?= $sel?'checked':'' ?> style="width:20px;height:20px">
          <label for="lab<?= $l['id'] ?>" style="flex:1;margin:0"><?= e($l['nama']) ?> <small style="color:var(--muted)">(<?= e($l['nilai_rujukan'] ?? '-') ?>)</small></label>
          <input type="number" min="1" class="form-control" style="width:70px" name="lab_qty[<?= $l['id'] ?>]" value="<?= (int)($labQty[$l['id']] ?? 1) ?>" title="Qty">
          <input class="form-control" style="width:130px" name="lab_hasil[<?= $l['id'] ?>]" value="<?= e($labSel[$l['id']] ?? '') ?>" placeholder="hasil">
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <h3 style="margin-bottom:12px">Permintaan Radiologi</h3>
      <?php foreach ($mRad as $r): $sel = array_key_exists($r['id'], $radSel); ?>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
          <input type="checkbox" name="rad_pick[]" value="<?= $r['id'] ?>" id="rad<?= $r['id'] ?>" <?= $sel?'checked':'' ?> style="width:20px;height:20px">
          <label for="rad<?= $r['id'] ?>" style="flex:1;margin:0"><?= e($r['nama']) ?></label>
          <input type="number" min="1" class="form-control" style="width:70px" name="rad_qty[<?= $r['id'] ?>]" value="<?= (int)($radQty[$r['id']] ?? 1) ?>" title="Qty">
          <input class="form-control" style="width:130px" name="rad_hasil[<?= $r['id'] ?>]" value="<?= e($radSel[$r['id']] ?? '') ?>" placeholder="hasil">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Penunjang Diagnostik & Fisioterapi -->
  <div class="form-row" style="margin-top:14px">
    <div class="card">
      <h3 style="margin-bottom:12px">Permintaan Diagnostik</h3>
      <?php if (!$mDiag): ?><p style="color:var(--muted);margin:0">Belum ada data pemeriksaan diagnostik di Master Data.</p><?php endif; ?>
      <?php foreach ($mDiag as $x): $sel = array_key_exists($x['id'], $diagSel); ?>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
          <input type="checkbox" name="diag_pick[]" value="<?= $x['id'] ?>" id="diag<?= $x['id'] ?>" <?= $sel?'checked':'' ?> style="width:20px;height:20px">
          <label for="diag<?= $x['id'] ?>" style="flex:1;margin:0"><?= e($x['nama']) ?></label>
          <input type="number" min="1" class="form-control" style="width:70px" name="diag_qty[<?= $x['id'] ?>]" value="<?= (int)($diagQty[$x['id']] ?? 1) ?>" title="Qty">
          <input class="form-control" style="width:130px" name="diag_hasil[<?= $x['id'] ?>]" value="<?= e($diagSel[$x['id']] ?? '') ?>" placeholder="hasil">
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <h3 style="margin-bottom:12px">Permintaan Fisioterapi</h3>
      <?php if (!$mFisio): ?><p style="color:var(--muted);margin:0">Belum ada data layanan fisioterapi di Master Data.</p><?php endif; ?>
      <?php foreach ($mFisio as $x): $sel = array_key_exists($x['id'], $fisioSel); ?>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
          <input type="checkbox" name="fisio_pick[]" value="<?= $x['id'] ?>" id="fisio<?= $x['id'] ?>" <?= $sel?'checked':'' ?> style="width:20px;height:20px">
          <label for="fisio<?= $x['id'] ?>" style="flex:1;margin:0"><?= e($x['nama']) ?></label>
          <input type="number" min="1" class="form-control" style="width:70px" name="fisio_qty[<?= $x['id'] ?>]" value="<?= (int)($fisioQty[$x['id']] ?? 1) ?>" title="Qty">
          <input class="form-control" style="width:130px" name="fisio_hasil[<?= $x['id'] ?>]" value="<?= e($fisioSel[$x['id']] ?? '') ?>" placeholder="hasil">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Resep -->
  <div class="card" style="margin-top:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>Resep Obat</h3>
      <button type="button" class="btn btn-sm" onclick="addObat()"><?= app_icon("plus") ?> <?= e(t('common.add_medicine')) ?></button>
    </div>

    <table style="width:100%"><thead><tr><th>Obat</th><th style="width:120px">Qty</th><th style="width:120px">Dosis</th><th>Aturan Pakai</th><th style="width:50px"></th></tr></thead>
      <tbody id="obatBody">
      <?php foreach ($resepRows as $r): ?>
        <tr>
          <td><select class="form-control" name="obat_id[]"><?php foreach ($mObat as $m): ?><option value="<?= $m['id'] ?>" <?= $m['id']==$r['obat_id']?'selected':'' ?>><?= e($m['nama']) ?> (stok <?= (int)$m['stok'] ?>)</option><?php endforeach; ?></select></td>
          <td><input type="number" min="1" class="form-control" name="obat_qty[]" value="<?= (int)$r['qty'] ?>"></td>
          <td><input class="form-control" name="obat_dosis[]" value="<?= e($r['dosis']) ?>" placeholder="3x1"></td>
          <td><input class="form-control" name="obat_aturan[]" value="<?= e($r['aturan_pakai']) ?>" placeholder="sesudah makan"></td>
          <td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon("close") ?> </button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="form-group" style="margin-top:12px"><label>Catatan Resep</label><input class="form-control" name="resep_catatan" value="<?= e($resepCatatan) ?>"></div>
  </div>

  <div style="display:flex;gap:10px;margin:20px 0 40px">
    <button type="submit" class="btn btn-light" onclick="document.getElementById('aksi').value='simpan'"><?= app_icon("save") ?> <?= e(t('common.save_draft')) ?></button>
    <button type="submit" class="btn btn-green" onclick="document.getElementById('aksi').value='selesai'"><?= app_icon("check") ?> Selesai Periksa</button>
  </div>
</form>

<!-- Template baris (untuk JS) -->
<script>
var optTind = `<?php foreach ($mTindakan as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['nama']) ?> — <?= rupiah($m['tarif']) ?></option><?php endforeach; ?>`;
var optObat = `<?php foreach ($mObat as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['nama']) ?> (stok <?= (int)$m['stok'] ?>)</option><?php endforeach; ?>`;

function delRow(btn){ btn.closest('tr').remove(); }
function addDiag(){
  document.getElementById('diagBody').insertAdjacentHTML('beforeend',
    `<tr><td><input class="form-control" name="diag_kode[]"></td>`+
    `<td><input class="form-control" name="diag_nama[]"></td>`+
    `<td><select class="form-control" name="diag_jenis[]"><option value="primer">Primer</option><option value="sekunder">Sekunder</option></select></td>`+
    `<td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon("close") ?> </button></td></tr>`);
}
function addTind(){
  document.getElementById('tindBody').insertAdjacentHTML('beforeend',
    `<tr><td><select class="form-control" name="tind_id[]">${optTind}</select></td>`+
    `<td><input type="number" min="1" class="form-control" name="tind_qty[]" value="1"></td>`+
    `<td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon("close") ?> </button></td></tr>`);
}
function addObat(){
  document.getElementById('obatBody').insertAdjacentHTML('beforeend',
    `<tr><td><select class="form-control" name="obat_id[]">${optObat}</select></td>`+
    `<td><input type="number" min="1" class="form-control" name="obat_qty[]" value="1"></td>`+
    `<td><input class="form-control" name="obat_dosis[]" placeholder="3x1"></td>`+
    `<td><input class="form-control" name="obat_aturan[]" placeholder="sesudah makan"></td>`+
    `<td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon("close") ?> </button></td></tr>`);
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

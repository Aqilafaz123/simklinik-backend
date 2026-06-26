<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('registrasi', 'admin', 'superadmin');
$pageTitle = t('pages.new_registration');

$user = current_user();
$poli      = db()->query("SELECT id, kode, nama FROM poli WHERE status='aktif' ORDER BY nama")->fetchAll();
$dokter    = db()->query("SELECT id, nama, poli_id FROM dokter WHERE status='aktif' ORDER BY nama")->fetchAll();
$asuransi  = db()->query("SELECT id, nama FROM asuransi WHERE status='aktif' ORDER BY nama")->fetchAll();
$corporate = db()->query("SELECT id, nama FROM corporate WHERE status='aktif' ORDER BY nama")->fetchAll();

// Master data for service tables
$mTindakan = db()->query("SELECT id,nama,tarif FROM tindakan WHERE status='aktif' ORDER BY nama")->fetchAll();
$mObat     = db()->query("SELECT id,nama,harga_beli,markup_persen,stok FROM obat WHERE status='aktif' ORDER BY nama")->fetchAll();
$mLab      = db()->query("SELECT id,nama,nilai_rujukan,tarif,markup_persen FROM lab_pemeriksaan WHERE status='aktif' ORDER BY nama")->fetchAll();
$mRad      = db()->query("SELECT id,nama,tarif,markup_persen FROM rad_pemeriksaan WHERE status='aktif' ORDER BY nama")->fetchAll();

// Pasien terpilih (dari ?pasien_id atau hasil submit)
$pasienId = (int) ($_GET['pasien_id'] ?? $_POST['pasien_id'] ?? 0);
$pasien = null;
if ($pasienId) {
    $stmt = db()->prepare("SELECT * FROM pasien WHERE id = ?");
    $stmt->execute([$pasienId]);
    $pasien = $stmt->fetch() ?: null;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sim_csrf_verify();
    $poliId   = (int) ($_POST['poli_id'] ?? 0);
    $dokterId = (int) ($_POST['dokter_id'] ?? 0) ?: null;
    $tgl      = $_POST['tgl_kunjungan'] ?? date('Y-m-d');
    $jenis    = $_POST['jenis_penjamin'] ?? 'umum';
    $keluhan  = trim($_POST['keluhan_awal'] ?? '');
    $asuransiId  = $jenis === 'asuransi' ? ((int) ($_POST['asuransi_id'] ?? 0) ?: null) : null;
    $corporateId = $jenis === 'corporate' ? ((int) ($_POST['corporate_id'] ?? 0) ?: null) : null;
    $noJaminan   = trim($_POST['no_jaminan'] ?? '') ?: null;

    if (!$pasien)  $errors[] = t('common.err_patient_not_selected');
    if (!$poliId)  $errors[] = t('common.err_clinic_required');
    if (!in_array($jenis, ['umum', 'asuransi', 'corporate', 'ar'], true)) $errors[] = t('common.err_insurance_invalid');

    if (!$errors) {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare("SELECT COALESCE(MAX(no_antrian),0)+1 FROM kunjungan WHERE poli_id=? AND tgl_kunjungan=?");
            $stmt->execute([$poliId, $tgl]);
            $noAntrian = (int) $stmt->fetchColumn();
            $noKunjungan = generate_no('KJ', 'kunjungan', 'no_kunjungan');

            db()->prepare("INSERT INTO kunjungan (no_kunjungan, pasien_id, poli_id, dokter_id, tgl_kunjungan, no_antrian, jenis_penjamin, asuransi_id, corporate_id, no_jaminan, status, keluhan_awal, user_id) VALUES (?,?,?,?,?,?,?,?,?,?, 'menunggu', ?, ?)")
              ->execute([$noKunjungan, $pasien['id'], $poliId, $dokterId, $tgl, $noAntrian, $jenis, $asuransiId, $corporateId, $noJaminan, $keluhan, $user['id']]);
            $kunjunganId = (int) db()->lastInsertId();

            // --- Medical Service (Tindakan) ---
            $mTindakanMap = []; foreach ($mTindakan as $t) $mTindakanMap[$t['id']] = $t;
            $tId = $_POST['tind_id'] ?? []; $tQty = $_POST['tind_qty'] ?? [];
            $rmId = null;
            if (array_filter($tId)) {
                db()->prepare("INSERT INTO rekam_medis (kunjungan_id,dokter_id) VALUES (?,?)")->execute([$kunjunganId, $dokterId]);
                $rmId = (int) db()->lastInsertId();
                $insTind = db()->prepare("INSERT INTO rm_tindakan (rekam_medis_id,tindakan_id,nama_tindakan,qty,tarif,subtotal) VALUES (?,?,?,?,?,?)");
                foreach ($tId as $i => $tid) {
                    $tid=(int)$tid; if(!$tid||!isset($mTindakanMap[$tid])) continue;
                    $qty=max(1,(int)($tQty[$i]??1)); $tarif=(float)$mTindakanMap[$tid]['tarif'];
                    $insTind->execute([$rmId,$tid,$mTindakanMap[$tid]['nama'],$qty,$tarif,$tarif*$qty]);
                }
            }

            // --- Lab ---
            $mLabMap = []; foreach ($mLab as $l) $mLabMap[$l['id']] = $l;
            $labPick = $_POST['lab_pick'] ?? []; $labQtyIn = $_POST['lab_qty'] ?? []; $labHasilIn = $_POST['lab_hasil'] ?? [];
            if ($labPick) {
                db()->prepare("INSERT INTO lab_order (kunjungan_id,status) VALUES (?, 'permintaan')")->execute([$kunjunganId]);
                $labOrderId = (int) db()->lastInsertId();
                $insLab = db()->prepare("INSERT INTO lab_order_detail (lab_order_id,pemeriksaan_id,hasil,nilai_rujukan,tarif,qty,subtotal) VALUES (?,?,?,?,?,?,?)");
                foreach ($labPick as $pid) { 
                    $pid=(int)$pid; if(!isset($mLabMap[$pid])) continue; 
                    $qty=max(1,(int)($labQtyIn[$pid]??1)); 
                    $hasil = trim($labHasilIn[$pid] ?? '');
                    $base = (float)$mLabMap[$pid]['tarif']; 
                    $markup = (float)($mLabMap[$pid]['markup_persen'] ?? 0);
                    $tarif = $base + ($base * $markup / 100);
                    $insLab->execute([$labOrderId,$pid,($hasil !== '' ? $hasil : null),$mLabMap[$pid]['nilai_rujukan'],$tarif,$qty,$tarif*$qty]); 
                }
            }

            // --- Radiologi ---
            $mRadMap = []; foreach ($mRad as $r) $mRadMap[$r['id']] = $r;
            $radPick = $_POST['rad_pick'] ?? []; $radQtyIn = $_POST['rad_qty'] ?? []; $radHasilIn = $_POST['rad_hasil'] ?? [];
            if ($radPick) {
                db()->prepare("INSERT INTO rad_order (kunjungan_id,status) VALUES (?, 'permintaan')")->execute([$kunjunganId]);
                $radOrderId = (int) db()->lastInsertId();
                $insRad = db()->prepare("INSERT INTO rad_order_detail (rad_order_id,pemeriksaan_id,hasil,tarif,qty,subtotal) VALUES (?,?,?,?,?,?)");
                foreach ($radPick as $pid) { 
                    $pid=(int)$pid; if(!isset($mRadMap[$pid])) continue; 
                    $qty=max(1,(int)($radQtyIn[$pid]??1)); 
                    $hasil = trim($radHasilIn[$pid] ?? '');
                    $base=(float)$mRadMap[$pid]['tarif']; 
                    $markup=(float)($mRadMap[$pid]['markup_persen']??0);
                    $tarif=$base + ($base * $markup / 100);
                    $insRad->execute([$radOrderId,$pid,($hasil !== '' ? $hasil : null),$tarif,$qty,$tarif*$qty]); 
                }
            }

            // --- Resep ---
            $mObatMap = []; foreach ($mObat as $o) $mObatMap[$o['id']] = $o;
            $oId = $_POST['obat_id'] ?? []; $oQty = $_POST['obat_qty'] ?? []; $oDosis = $_POST['obat_dosis'] ?? []; $oAturan = $_POST['obat_aturan'] ?? [];
            $validObat = [];
            foreach ($oId as $i => $oid) { $oid=(int)$oid; if(!$oid||!isset($mObatMap[$oid])) continue; $validObat[]=[$oid,max(1,(int)($oQty[$i]??1)),trim($oDosis[$i]??''),trim($oAturan[$i]??'')]; }
            if ($validObat) {
                db()->prepare("INSERT INTO resep (kunjungan_id,dokter_id,status) VALUES (?,?,'baru')")->execute([$kunjunganId, $dokterId]);
                $resepId = (int) db()->lastInsertId();
                $insResep = db()->prepare("INSERT INTO resep_detail (resep_id,obat_id,qty,dosis,aturan_pakai,harga,subtotal) VALUES (?,?,?,?,?,?,?)");
                foreach ($validObat as [$oid,$qty,$dosis,$aturan]) { 
                    $base=(float)$mObatMap[$oid]['harga_beli']; 
                    $markup=(float)($mObatMap[$oid]['markup_persen']??0);
                    $harga=$base + ($base * $markup / 100);
                    $insResep->execute([$resepId,$oid,$qty,$dosis?:null,$aturan?:null,$harga,$harga*$qty]); 
                }
            }

            db()->commit();
            set_flash('success', t('common.visit_registered_flash', ['no' => $noKunjungan, 'queue' => str_pad((string) $noAntrian, 3, '0', STR_PAD_LEFT)]));
            legacy_redirect('modules/registrasi/index.php');
        } catch (Throwable $ex) {
            if (db()->inTransaction()) db()->rollBack();
            $errors[] = t('common.err_save_visit', ['msg' => $ex->getMessage()]);
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-toolbar">
  <div>
    <div class="pt-title"><?= e(t('pages.new_registration')) ?></div>
    <div class="pt-sub"><?= e(t('common.registration_sub')) ?></div>
  </div>
  <div class="pt-actions">
    <a class="btn-back" href="<?= legacy_url('modules/registrasi/index.php') ?>"><?= app_icon('chevron') ?> <?= e(t('common.back_to_list')) ?></a>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger" style="margin-top:14px"><?= implode('<br>', array_map('e', $errors)) ?></div>
<?php endif; ?>

<!-- STEP 1: Pilih Pasien -->
<div class="card" style="margin-top:14px">
  <div class="step-head">
    <div class="step-num">1</div>
    <div><div class="st-title"><?= e(t('common.select_patient_step')) ?></div><div class="st-sub"><?= e(t('common.select_patient_sub')) ?></div></div>
  </div>
  <?php if ($pasien): ?>
    <div class="patient-box">
      <div class="pname">
        <span class="av"><?= strtoupper(substr($pasien['nama'], 0, 1)) ?></span>
        <?= e($pasien['nama']) ?>
      </div>
      <div class="patient-meta">
        <?= t('common.mr_no') ?>: <b><?= e($pasien['no_mr']) ?></b><br>
        <?= $pasien['jenis_kelamin'] === 'L' ? e(t('common.male')) : e(t('common.female')) ?> &middot;
        <?= tgl_id($pasien['tgl_lahir']) ?>
        <?php if (!empty($pasien['alergi'])): ?>
          <br><span class="badge badge-red"><?= e(t('common.allergy')) ?>: <?= e($pasien['alergi']) ?></span>
        <?php endif; ?>
      </div>
      <a class="btn btn-sm btn-light" style="margin-top:12px" href="<?= legacy_url('modules/registrasi/daftar.php') ?>"><?= app_icon('search') ?> <?= e(t('common.change_patient')) ?></a>
    </div>
  <?php else: ?>
    <form method="get" class="search-inline">
      <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" class="form-control" placeholder="<?= e(t('common.search_patient_placeholder')) ?>" autofocus>
      <button class="btn" type="submit"><?= app_icon('search') ?></button>
    </form>
    <?php
      $q = trim($_GET['q'] ?? '');
      if ($q !== '') {
          $s = db()->prepare("SELECT id, no_mr, nama FROM pasien WHERE no_mr LIKE ? OR nama LIKE ? OR nik LIKE ? ORDER BY nama LIMIT 20");
          $like = "%$q%"; $s->execute([$like, $like, $like]);
          $hasil = $s->fetchAll();
          if (!$hasil) echo '<p class="result-empty">' . e(t('common.patient_not_found')) . '</p>';
          foreach ($hasil as $h) {
              echo '<a class="result-item" href="' . legacy_url('modules/registrasi/daftar.php?pasien_id=' . $h['id']) . '">'
                 . '<span class="av">' . strtoupper(substr($h['nama'], 0, 1)) . '</span>'
                 . '<span><span class="ri-name">' . e($h['nama']) . '</span>'
                 . '<span class="ri-meta">' . e($h['no_mr']) . '</span></span></a>';
          }
      } else {
          echo '<p class="result-empty">' . e(t('common.search_patient_hint')) . '</p>';
      }
    ?>
  <?php endif; ?>
</div>

<!-- STEP 2 onwards: full form (disabled when no patient) -->
<form method="post" id="formDaftar" <?= $pasien ? '' : 'style="opacity:.5;pointer-events:none"' ?>>
  <?= sim_csrf_field() ?>
  <input type="hidden" name="pasien_id" value="<?= (int) ($pasien['id'] ?? 0) ?>">

  <!-- Poli, Dokter & Penjamin -->
  <div class="card" style="margin-top:14px">
    <div class="step-head">
      <div class="step-num">2</div>
      <div><div class="st-title"><?= e(t('common.visit_clinic_step')) ?></div><div class="st-sub"><?= e(t('common.visit_clinic_step_sub')) ?></div></div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= e(t('common.visit_date')) ?></label>
        <input type="date" name="tgl_kunjungan" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label><?= e(t('common.target_clinic')) ?> *</label>
        <select name="poli_id" id="poliSelect" class="form-control" required onchange="filterDokter()">
          <option value=""><?= e(t('common.select_clinic')) ?></option>
          <?php foreach ($poli as $po): ?>
            <option value="<?= $po['id'] ?>"><?= e($po['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= e(t('common.doctor')) ?></label>
        <select name="dokter_id" id="dokterSelect" class="form-control">
          <option value=""><?= e(t('common.select_doctor_optional')) ?></option>
          <?php foreach ($dokter as $d): ?>
            <option value="<?= $d['id'] ?>" data-poli="<?= (int) $d['poli_id'] ?>"><?= e($d['nama']) ?></option>
          <?php endforeach; ?>
        </select>
        <small id="dokterHint" style="color:var(--muted);font-size:12.5px;display:none"><?= e(t('common.select_clinic_first_hint')) ?></small>
      </div>
      <div class="form-group">
        <label><?= e(t('common.insurance_type')) ?></label>
        <select name="jenis_penjamin" id="jenis_penjamin" class="form-control" onchange="togglePenjamin()">
          <option value="umum"><?= e(t('common.general')) ?></option>
          <option value="asuransi"><?= e(t('common.private_insurance')) ?></option>
          <option value="corporate"><?= e(t('common.corporate')) ?></option>
          <option value="ar">AR</option>
        </select>
      </div>
    </div>
    <div class="form-row penjamin-extra" id="box_asuransi" style="display:none">
      <div class="form-group">
        <label><?= e(t('common.insurance')) ?></label>
        <select name="asuransi_id" class="form-control">
          <option value=""><?= e(t('common.select_option')) ?></option>
          <?php foreach ($asuransi as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['nama']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" id="box_nojaminan_a">
        <label><?= e(t('common.card_no_insurance')) ?></label>
        <input type="text" name="no_jaminan" class="form-control">
      </div>
    </div>
    <div class="form-row penjamin-extra" id="box_corporate" style="display:none">
      <div class="form-group">
        <label><?= e(t('common.company')) ?></label>
        <select name="corporate_id" class="form-control">
          <option value=""><?= e(t('common.select_option')) ?></option>
          <?php foreach ($corporate as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nama']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" id="box_nojaminan_c">
        <label><?= e(t('common.card_no_insurance')) ?></label>
        <input type="text" name="no_jaminan" class="form-control">
      </div>
    </div>
    <div class="form-group">
      <label><?= e(t('common.initial_complaint')) ?></label>
      <textarea name="keluhan_awal" class="form-control" rows="2" placeholder="<?= e(t('common.complaint_placeholder')) ?>"></textarea>
    </div>
  </div>

  <!-- Medical Service -->
  <div class="card" style="margin-top:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>Medical Service</h3>
      <button type="button" class="btn btn-sm" onclick="addTind()"><?= app_icon('plus') ?> <?= e(t('common.add_medical_service')) ?></button>
    </div>
    <table style="width:100%"><thead><tr><th>Medical Service</th><th style="width:120px">Qty</th><th style="width:50px"></th></tr></thead>
      <tbody id="tindBody"></tbody>
    </table>
  </div>

  <!-- Lab & Radiologi -->
  <div class="form-row" style="margin-top:14px">
    <div class="card">
      <h3 style="margin-bottom:12px">Permintaan Laboratorium</h3>
      <?php if (!$mLab): ?><p style="color:var(--muted);margin:0">Belum ada data lab.</p><?php endif; ?>
      <?php foreach ($mLab as $l): ?>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
          <input type="checkbox" name="lab_pick[]" value="<?= $l['id'] ?>" id="lab<?= $l['id'] ?>" style="width:20px;height:20px">
          <label for="lab<?= $l['id'] ?>" style="flex:1;margin:0"><?= e($l['nama']) ?> <small style="color:var(--muted)">(<?= e($l['nilai_rujukan'] ?? '-') ?>)</small></label>
          <input type="number" min="1" class="form-control" style="width:70px;text-align:center;" name="lab_qty[<?= $l['id'] ?>]" value="1" title="Qty">
          <input type="text" class="form-control" style="width:120px" name="lab_hasil[<?= $l['id'] ?>]" placeholder="hasil">
        </div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <h3 style="margin-bottom:12px">Permintaan Radiologi</h3>
      <?php if (!$mRad): ?><p style="color:var(--muted);margin:0">Belum ada data radiologi.</p><?php endif; ?>
      <?php foreach ($mRad as $r): ?>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
          <input type="checkbox" name="rad_pick[]" value="<?= $r['id'] ?>" id="rad<?= $r['id'] ?>" style="width:20px;height:20px">
          <label for="rad<?= $r['id'] ?>" style="flex:1;margin:0"><?= e($r['nama']) ?></label>
          <input type="number" min="1" class="form-control" style="width:70px;text-align:center;" name="rad_qty[<?= $r['id'] ?>]" value="1" title="Qty">
          <input type="text" class="form-control" style="width:120px" name="rad_hasil[<?= $r['id'] ?>]" placeholder="hasil">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Resep Obat -->
  <div class="card" style="margin-top:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h3>Resep Obat</h3>
      <button type="button" class="btn btn-sm" onclick="addObat()"><?= app_icon('plus') ?> <?= e(t('common.add_medicine')) ?></button>
    </div>
    <table style="width:100%"><thead><tr><th>Obat</th><th style="width:100px">Qty</th><th style="width:110px">Dosis</th><th>Aturan Pakai</th><th style="width:50px"></th></tr></thead>
      <tbody id="obatBody"></tbody>
    </table>
  </div>

  <!-- Tombol Simpan -->
  <div style="margin:24px 0 48px;text-align:right">
    <button type="submit" class="btn" style="min-width:160px;font-size:1rem"><?= app_icon('save') ?> Simpan</button>
  </div>
</form>

<script>
var SIM_REG_LANG = <?= json_encode(['selectDoctor' => t('common.select_doctor_optional'), 'noDoctor' => t('common.no_doctor_for_clinic')], JSON_UNESCAPED_UNICODE) ?>;
var optTind = `<?php foreach ($mTindakan as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['nama']) ?> — <?= rupiah($m['tarif']) ?></option><?php endforeach; ?>`;
var optObat = `<?php foreach ($mObat as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['nama']) ?> (stok <?= (int)$m['stok'] ?>)</option><?php endforeach; ?>`;

function togglePenjamin() {
  var v = document.getElementById('jenis_penjamin').value;
  document.querySelectorAll('.penjamin-extra').forEach(function(el){ el.style.display='none'; });
  if (v === 'asuransi') document.getElementById('box_asuransi').style.display = '';
  else if (v === 'corporate') document.getElementById('box_corporate').style.display = '';
}
var _dokterAll = (function () {
  var sel = document.getElementById('dokterSelect'), arr = [];
  Array.prototype.forEach.call(sel.options, function (o) {
    if (o.value) arr.push({ id: o.value, nama: o.text, poli: o.getAttribute('data-poli') });
  });
  return arr;
})();
function filterDokter() {
  var poli = document.getElementById('poliSelect').value;
  var sel  = document.getElementById('dokterSelect');
  var hint = document.getElementById('dokterHint');
  var prev = sel.value;
  sel.innerHTML = '<option value="">' + SIM_REG_LANG.selectDoctor + '</option>';
  var list = poli ? _dokterAll.filter(function (d) { return d.poli === poli; }) : _dokterAll;
  list.forEach(function (d) {
    var o = document.createElement('option');
    o.value = d.id; o.text = d.nama; o.setAttribute('data-poli', d.poli);
    if (d.id === prev) o.selected = true;
    sel.appendChild(o);
  });
  if (poli && list.length === 1) sel.value = list[0].id;
  hint.style.display = (poli && list.length === 0) ? '' : 'none';
  if (poli && list.length === 0) hint.textContent = SIM_REG_LANG.noDoctor;
}
function delRow(btn){ btn.closest('tr').remove(); }
function addTind(){
  document.getElementById('tindBody').insertAdjacentHTML('beforeend',
    `<tr><td><select class="form-control" name="tind_id[]">${optTind}</select></td>`+
    `<td><input type="number" min="1" class="form-control" name="tind_qty[]" value="1"></td>`+
    `<td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon('close') ?></button></td></tr>`);
}
function addObat(){
  document.getElementById('obatBody').insertAdjacentHTML('beforeend',
    `<tr><td><select class="form-control" name="obat_id[]">${optObat}</select></td>`+
    `<td><input type="number" min="1" class="form-control" name="obat_qty[]" value="1"></td>`+
    `<td><input class="form-control" name="obat_dosis[]" placeholder="3x1"></td>`+
    `<td><input class="form-control" name="obat_aturan[]" placeholder="sesudah makan"></td>`+
    `<td><button type="button" class="btn btn-sm btn-red" onclick="delRow(this)"><?= app_icon('close') ?></button></td></tr>`);
}
document.addEventListener('DOMContentLoaded', filterDokter);
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
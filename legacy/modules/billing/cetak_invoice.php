<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/keuangan_lib.php';
require_once __DIR__ . '/../../includes/billing_lib.php';
require_once __DIR__ . '/../../includes/icons.php';
require_role('kasir', 'admin', 'superadmin');

$kunjunganId = (int) ($_GET['kunjungan_id'] ?? 0);
$inv = get_or_create_invoice($kunjunganId);

if (!$inv) {
    set_flash('danger', 'Billing belum difinalisasi sehingga Invoice belum terbit.');
    legacy_redirect('modules/billing/index.php');
}

$kj = db()->prepare(
    "SELECT k.*, p.no_mr, p.nama AS pasien, po.nama AS poli, d.nama AS dokter,
            a.nama AS asuransi_nama, c.nama AS corporate_nama
     FROM kunjungan k
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     LEFT JOIN asuransi a ON a.id = k.asuransi_id
     LEFT JOIN corporate c ON c.id = k.corporate_id
     WHERE k.id = ?"
);
$kj->execute([$kunjunganId]);
$kj = $kj->fetch();

$detail = db()->prepare("SELECT kategori,item_code,deskripsi,qty,subtotal FROM billing_detail WHERE billing_id=? ORDER BY FIELD(kategori,'laboratorium','radiologi','diagnostik','fisioterapi','jasa_dokter','tindakan','administrasi','farmasi'), id");
$detail->execute([$inv['billing_id']]);
$detail = $detail->fetchAll();

// kelompokkan sesuai grup struk
$grouped = [];
foreach ($detail as $d) { $grouped[struk_grup_label($d['kategori'])][] = $d; }

$billing = db()->prepare("SELECT subtotal,diskon,total FROM billing WHERE id=?");
$billing->execute([$inv['billing_id']]); $billing = $billing->fetch();

$bank = db()->query("SELECT nama_bank,no_rekening,atas_nama,cabang FROM bank WHERE status='aktif' ORDER BY id LIMIT 1")->fetch();

$tglDate = fn($d) => date('d-M-Y', strtotime($d));
$amt = fn($v) => number_format((float) $v, 2, '.', ',');

$hasConsult = false;
foreach ($detail as $d) { if ($d['kategori'] === 'jasa_dokter') { $hasConsult = true; break; } }

$codePrefix = defined('CODE_PREFIX') ? CODE_PREFIX : 'GBK';
$signPlace  = trim(explode(',', CLINIC_ADDRESS)[0]);

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Invoice <?= e($inv['no_invoice']) ?></title>
  <style>
    body{font-family:'Segoe UI',Arial,sans-serif;background:#eef2f7;color:#1e293b;padding:24px;
      display:flex;justify-content:center}
    .paper{background:#fff;width:720px;padding:34px 40px;box-shadow:0 2px 10px rgba(0,0,0,.1)}
    .head{text-align:center;border-bottom:2px solid #1e293b;padding-bottom:10px;margin-bottom:14px}
    .head .clinic{font-size:20px;font-weight:800}
    .head .unit{font-size:12px;color:#475569}
    .head .title{font-size:16px;font-weight:700;letter-spacing:3px;margin-bottom:8px}
    .meta{display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:14px;gap:30px}
    .meta table{border-collapse:collapse}
    .meta td{padding:2px 0;vertical-align:top}
    .meta td.k{color:#64748b;padding-right:10px;white-space:nowrap}
    table.items{width:100%;border-collapse:collapse;font-size:12.5px}
    table.items th{text-align:left;border-bottom:1.5px solid #1e293b;padding:6px 4px;font-size:11.5px;text-transform:uppercase}
    table.items td{padding:4px;vertical-align:top}
    table.items .amt-h{text-align:right}
    table.items td:nth-child(4),table.items th:nth-child(4){text-align:center;padding-right:16px}
    table.items td.amt{display:flex;justify-content:space-between;gap:10px;white-space:nowrap}
    .grp td{font-weight:800;padding-top:10px;text-transform:uppercase}
    .sum{margin-top:14px;margin-left:auto;width:340px;font-size:13px}
    .sum div{display:grid;grid-template-columns:1fr auto 100px;gap:8px;padding:3px 0;align-items:baseline}
    .sum .cur{text-align:right;color:#334155;white-space:nowrap}
    .sum .val{text-align:right}
    .sum .net{font-size:16px;font-weight:800;border-top:1.5px solid #1e293b;margin-top:4px;padding-top:6px}
    .says{font-size:12.5px;margin-top:12px;border-top:1px dashed #cbd5e1;padding-top:8px;text-align:right}
    .bank{font-size:12px;margin-top:16px;color:#334155}
    .signdate{margin-top:16px;font-size:12.5px}
    .actions{margin-top:18px;text-align:center}
    .actions button,.actions a{padding:9px 18px;border:none;border-radius:8px;cursor:pointer;font-size:14px;text-decoration:none}
    .btn-print{background:#2563eb;color:#fff}.btn-back{background:#e2e8f0;color:#1e293b}
    .clinic svg{width:.95em;height:.95em;vertical-align:-.12em}
    .actions svg{width:16px;height:16px;vertical-align:-3px}
    @media print{body{background:#fff;padding:0}.actions{display:none}.paper{box-shadow:none;width:100%}}
  </style>
</head>
<body>
  <div class="paper">
    <div class="head">
      <div class="title">INVOICE</div>
      <div class="clinic"><?= app_icon('hospital') ?> <?= CLINIC_NAME ?></div>
      <div class="unit"><?= defined('CLINIC_UNIT') ? CLINIC_UNIT : '' ?></div>
    </div>

    <div class="meta">
      <table>
        <tr><td class="k">Reference</td><td>: <?= e(strtoupper($kj['poli'])) ?></td></tr>
        <tr><td colspan="2"><?= $hasConsult ? 'CONSULTATION' : 'NO CONSULTATION' ?></td></tr>
        <tr><td class="k">No. MR</td><td>: <?= e($kj['no_mr']) ?></td></tr>
        <tr><td colspan="2">Page 1 of 1</td></tr>
      </table>
      <table>
        <tr><td class="k">No. Invoice</td><td>: <?= e($inv['no_invoice']) ?></td></tr>
        <tr><td class="k">Print Date</td><td>: <?= $tglDate(date('Y-m-d')) ?></td></tr>
        <tr><td class="k">Admission Date</td><td>: <?= $tglDate($kj['created_at']) ?></td></tr>
        <tr><td class="k">Name</td><td>: <?= e($kj['pasien']) ?></td></tr>
      </table>
    </div>

    <table class="items">
      <thead>
        <tr><th style="width:88px">Date</th><th style="width:92px">Item Code</th>
            <th>Description</th><th style="width:40px">Qty</th><th class="amt-h" style="width:150px">Amount</th></tr>
      </thead>
      <tbody>
        <?php foreach (struk_grup_urutan() as $grup): if (empty($grouped[$grup])) continue; ?>
          <tr class="grp"><td colspan="2"></td><td colspan="3"><?= e($grup) ?></td></tr>
          <?php foreach ($grouped[$grup] as $d): ?>
            <tr>
              <td><?= $tglDate($kj['tgl_kunjungan']) ?></td>
              <td><?= e($d['item_code'] ?? '') ?></td>
              <td><?= e($d['deskripsi']) ?></td>
              <td><?= (int) $d['qty'] ?></td>
              <td class="amt"><span>Rp</span><span><?= $amt($d['subtotal']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!$detail): ?>
          <tr><td colspan="5" style="text-align:center;color:#64748b;padding:16px">Tidak ada rincian.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="sum">
      <div><span class="lbl">TOTAL</span><span class="cur">: Rp</span><span class="val"><?= $amt($billing['subtotal']) ?></span></div>
      <div><span class="lbl">DISCOUNT</span><span class="cur">: Rp</span><span class="val"><?= $amt($billing['diskon']) ?></span></div>
      <div class="net"><span class="lbl">NET PAYABLE</span><span class="cur">Rp</span><span class="val"><?= $amt($inv['total']) ?></span></div>
    </div>

    <div class="says"><b>Says</b> : <?= e(terbilang_en_rupiah($inv['total'])) ?></div>

    <?php if ($bank): ?>
    <div class="bank">
      <b>Bank :</b><br>
      Beneficiary Name : <?= e($bank['atas_nama']) ?><br>
      1. <?= e($bank['nama_bank']) ?> <?= e($bank['cabang']) ?> (IDR) A/c No : <?= e($bank['no_rekening']) ?>
    </div>
    <?php endif; ?>

    <div class="signdate"><?= e(strtoupper($signPlace)) ?> , <?= $tglDate(date('Y-m-d')) ?></div>

    <div class="actions">
      <button class="btn-print" onclick="window.print()"><?= app_icon('print') ?> Cetak Invoice</button>
    </div>
  </div>
</body>
</html>

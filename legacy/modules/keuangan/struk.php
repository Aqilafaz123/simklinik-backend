<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/keuangan_lib.php';
require_once __DIR__ . '/../../includes/billing_lib.php';
require_once __DIR__ . '/../../includes/icons.php';
require_role('kasir');

$invoiceId = (int) ($_GET['invoice_id'] ?? 0);
$inv = db()->prepare(
    "SELECT i.*, k.no_kunjungan, k.tgl_kunjungan, k.jenis_penjamin, k.no_jaminan, k.created_at AS admission,
            p.no_mr, p.nama AS pasien, po.nama AS poli, d.nama AS dokter,
            a.nama AS asuransi_nama, c.nama AS corporate_nama
     FROM invoice i
     JOIN kunjungan k ON k.id = i.kunjungan_id
     JOIN pasien p ON p.id = k.pasien_id
     JOIN poli po ON po.id = k.poli_id
     LEFT JOIN dokter d ON d.id = k.dokter_id
     LEFT JOIN asuransi a ON a.id = k.asuransi_id
     LEFT JOIN corporate c ON c.id = k.corporate_id
     WHERE i.id = ?");
$inv->execute([$invoiceId]);
$inv = $inv->fetch();
if (!$inv) { set_flash('danger', 'Invoice tidak ditemukan.'); legacy_redirect('modules/keuangan/index.php'); }

$detail = db()->prepare("SELECT kategori,item_code,deskripsi,qty,subtotal FROM billing_detail WHERE billing_id=? ORDER BY FIELD(kategori,'laboratorium','radiologi','diagnostik','fisioterapi','jasa_dokter','tindakan','administrasi','farmasi'), id");
$detail->execute([$inv['billing_id']]);
$detail = $detail->fetchAll();

// kelompokkan sesuai grup struk
$grouped = [];
foreach ($detail as $d) { $grouped[struk_grup_label($d['kategori'])][] = $d; }

$pmts = db()->prepare("SELECT metode,jumlah,tanggal FROM pembayaran WHERE invoice_id=? AND status='valid' ORDER BY id");
$pmts->execute([$invoiceId]);
$pmts = $pmts->fetchAll();

$billing = db()->prepare("SELECT subtotal,diskon,total FROM billing WHERE id=?");
$billing->execute([$inv['billing_id']]); $billing = $billing->fetch();

$bank = db()->query("SELECT nama_bank,no_rekening,atas_nama,cabang FROM bank WHERE status='aktif' ORDER BY id LIMIT 1")->fetch();
$sisa = (float) $inv['total'] - (float) $inv['terbayar'];
$tglDate = fn($d) => date('d-M-Y', strtotime($d));
$amt = fn($v) => number_format((float) $v, 2, '.', ','); // format angka struk: 1,234,567.00

// Ada konsultasi dokter? (kategori jasa_dokter). Bila tidak -> "NO CONSULTATION".
$hasConsult = false;
foreach ($detail as $d) { if ($d['kategori'] === 'jasa_dokter') { $hasConsult = true; break; } }

// Kode prefix klinik (di struk client "SL", di kita "GBK") & tempat tanda tangan.
$codePrefix = defined('CODE_PREFIX') ? CODE_PREFIX : 'GBK';
$signPlace  = trim(explode(',', CLINIC_ADDRESS)[0]); // kota dari alamat klinik

// Deskripsi penjamin spesifik untuk pembayaran metode 'penjamin'
// (ASURANSI: nama, BPJS: nama, CORPORATE: nama) + no. jaminan bila ada.
$penjaminLabel = (function () use ($inv) {
    switch ($inv['jenis_penjamin']) {
        case 'asuransi':  $t = 'ASURANSI: ' . ($inv['asuransi_nama'] ?: '-'); break;
        case 'bpjs':      $t = 'BPJS: ' . ($inv['asuransi_nama'] ?: 'BPJS KESEHATAN'); break;
        case 'corporate': $t = 'CORPORATE: ' . ($inv['corporate_nama'] ?: '-'); break;
        default:          $t = 'PENJAMIN';
    }
    if (!empty($inv['no_jaminan'])) $t .= ' (No. ' . $inv['no_jaminan'] . ')';
    return $t;
})();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Receipt <?= e($inv['no_invoice']) ?></title>
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
    .pay{font-size:12.5px;margin-top:12px}
    .payline{display:flex;justify-content:center;gap:48px;max-width:470px;margin:0 auto;padding:2px 0}
    .signdate{margin-top:16px;font-size:12.5px}
    .bank{font-size:12px;margin-top:16px;color:#334155}
    .stamp{display:inline-block;margin-top:10px;padding:4px 14px;border:2px solid #16a34a;color:#16a34a;
      font-weight:800;border-radius:6px;letter-spacing:2px}
    .stamp.red{border-color:#dc2626;color:#dc2626}
    .foot{font-size:11px;color:#64748b;margin-top:16px;text-align:center}
    .actions{margin-top:18px;text-align:center}
    .actions button,.actions a{padding:9px 18px;border:none;border-radius:8px;cursor:pointer;font-size:14px;text-decoration:none}
    .btn-print{background:#2563eb;color:#fff}.btn-back{background:#e2e8f0;color:#1e293b}
    .clinic svg{width:.95em;height:.95em;vertical-align:-.12em}
    .stamp svg{width:1em;height:1em;vertical-align:-.14em}
    .actions svg{width:16px;height:16px;vertical-align:-3px}
    @media print{body{background:#fff;padding:0}.actions{display:none}.paper{box-shadow:none;width:100%}}
  </style>
</head>
<body>
  <div class="paper">
    <div class="head">
      <div class="title">RECEIPT</div>
      <div class="clinic"><?= app_icon('hospital') ?> <?= CLINIC_NAME ?></div>
      <div class="unit"><?= defined('CLINIC_UNIT') ? CLINIC_UNIT : '' ?></div>
    </div>

    <div class="meta">
      <table>
        <tr><td class="k">Payment Date</td><td>: <?= $tglDate($inv['tanggal']) ?></td></tr>
        <tr><td class="k">Reference</td><td>: <?= e(strtoupper($inv['poli'])) ?></td></tr>
        <tr><td colspan="2"><?= $hasConsult ? 'CONSULTATION' : 'NO CONSULTATION' ?></td></tr>
        <tr><td class="k">No. MR</td><td>: <?= e($inv['no_mr']) ?></td></tr>
        <tr><td colspan="2">Page 1 of 1</td></tr>
      </table>
      <table>
        <tr><td class="k">No. Invoice</td><td>: <?= e($inv['no_invoice']) ?></td></tr>
        <tr><td class="k">Print Date</td><td>: <?= $tglDate(date('Y-m-d')) ?></td></tr>
        <tr><td class="k">Admission Date</td><td>: <?= $tglDate($inv['admission']) ?></td></tr>
        <tr><td class="k">Discharge Date</td><td>: <?= $tglDate($inv['tgl_kunjungan']) ?></td></tr>
        <tr><td class="k">Name</td><td>: <?= e($inv['pasien']) ?></td></tr>
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
              <td><?= $tglDate($inv['tgl_kunjungan']) ?></td>
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
      <div><span class="lbl">DOWN PAYMENT</span><span class="cur">: Rp</span><span class="val"></span></div>
      <div><span class="lbl">DISCOUNT</span><span class="cur">: Rp</span><span class="val"><?= $amt($billing['diskon']) ?></span></div>
      <div class="net"><span class="lbl">NET PAYABLE</span><span class="cur">Rp</span><span class="val"><?= $amt($inv['total']) ?></span></div>
    </div>

    <div class="says"><b>Says</b> : <?= e(terbilang_en_rupiah($inv['total'])) ?></div>

    <div class="pay">
      <?php foreach ($pmts as $pm): ?>
        <div class="payline">
          <?php if ($pm['metode'] === 'penjamin'): ?>
            <span><?= e($codePrefix) ?> - <?= e(strtoupper($penjaminLabel)) ?></span>
          <?php else: ?>
            <span><?= e($codePrefix) ?> - <?= e(strtoupper(metode_label($pm['metode']))) ?><?= $bank ? ' ' . e(strtoupper($signPlace)) : '' ?></span>
          <?php endif; ?>
          <span><?= $amt($pm['jumlah']) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if ($sisa > 0): ?>
        <div class="payline"><span>SISA</span><span><?= $amt($sisa) ?></span></div>
        <span class="stamp red">BELUM LUNAS</span>
      <?php endif; ?>
    </div>

    <?php if ($bank): ?>
    <div class="bank">
      <b>Bank :</b><br>
      Beneficiary Name : <?= e($bank['atas_nama']) ?><br>
      1. <?= e($bank['nama_bank']) ?> <?= e($bank['cabang']) ?> (IDR) A/c No : <?= e($bank['no_rekening']) ?>
    </div>
    <?php endif; ?>

    <div class="signdate"><?= e(strtoupper($signPlace)) ?> , <?= $tglDate($inv['tanggal']) ?></div>

    <div class="foot">* Payment is deemed valid if receipt sealed by the cashier is issued</div>

    <div class="actions">
      <button class="btn-print" onclick="window.print()"><?= app_icon('print') ?> Cetak</button>
      <a class="btn-back" href="<?= legacy_url('modules/keuangan/index.php') ?>">Selesai</a>
    </div>
  </div>
</body>
</html>

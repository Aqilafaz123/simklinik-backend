<?php
/**
 * Library billing: agregasi seluruh layanan satu kunjungan menjadi
 * baris-baris tagihan (jasa dokter, tindakan, lab, radiologi, farmasi).
 * Tiap baris membawa item_code (kode item) untuk ditampilkan di struk,
 * mengikuti format receipt resmi client.
 * Komponen 'administrasi' & 'diskon' ditambahkan terpisah oleh modul billing.
 */
require_once __DIR__ . '/functions.php';

/**
 * Kumpulkan baris layanan dari sumber transaksi.
 * @return array<int,array{kategori:string,item_code:string,deskripsi:string,qty:int,tarif:float,subtotal:float}>
 */
function collect_billing_lines(int $kunjunganId): array
{
    $lines = [];

    // 1) Jasa dokter (item_code = kode dokter)
    $row = db()->prepare(
        "SELECT d.kode, d.nama, d.tarif_jasa FROM kunjungan k
         JOIN dokter d ON d.id = k.dokter_id WHERE k.id = ?");
    $row->execute([$kunjunganId]);
    if ($d = $row->fetch()) {
        if ((float) $d['tarif_jasa'] > 0) {
            $lines[] = ['kategori' => 'jasa_dokter', 'item_code' => $d['kode'],
                'deskripsi' => 'Jasa Dokter — ' . $d['nama'],
                'qty' => 1, 'tarif' => (float) $d['tarif_jasa'], 'subtotal' => (float) $d['tarif_jasa']];
        }
    }

    // 2) Tindakan medis (item_code = kode tindakan)
    $s = db()->prepare(
        "SELECT t.kode, rt.nama_tindakan, rt.qty, rt.tarif, rt.subtotal
         FROM rm_tindakan rt
         JOIN rekam_medis rm ON rm.id = rt.rekam_medis_id
         LEFT JOIN tindakan t ON t.id = rt.tindakan_id
         WHERE rm.kunjungan_id = ?");
    $s->execute([$kunjunganId]);
    foreach ($s->fetchAll() as $r) {
        $lines[] = ['kategori' => 'tindakan', 'item_code' => $r['kode'] ?? '',
            'deskripsi' => $r['nama_tindakan'],
            'qty' => (int) $r['qty'], 'tarif' => (float) $r['tarif'], 'subtotal' => (float) $r['subtotal']];
    }

    // 3) Laboratorium (item_code = kode pemeriksaan lab)
    $s = db()->prepare(
        "SELECT lp.kode, lp.nama, lod.qty, lp.tarif AS base_tarif, lp.markup_persen FROM lab_order_detail lod
         JOIN lab_order lo ON lo.id = lod.lab_order_id
         JOIN lab_pemeriksaan lp ON lp.id = lod.pemeriksaan_id
         WHERE lo.kunjungan_id = ?");
    $s->execute([$kunjunganId]);
    foreach ($s->fetchAll() as $r) {
        $base = (float) $r['base_tarif'];
        $markup = (float) ($r['markup_persen'] ?? 0);
        $calcTarif = $base + ($base * $markup / 100);
        $qty = (int) $r['qty'];
        $lines[] = ['kategori' => 'laboratorium', 'item_code' => $r['kode'],
            'deskripsi' => $r['nama'],
            'qty' => $qty, 'tarif' => $calcTarif, 'subtotal' => $calcTarif * $qty];
    }

    // 4) Radiologi (item_code = kode pemeriksaan radiologi)
    $s = db()->prepare(
        "SELECT rp.kode, rp.nama, rod.qty, rp.tarif AS base_tarif, rp.markup_persen FROM rad_order_detail rod
         JOIN rad_order ro ON ro.id = rod.rad_order_id
         JOIN rad_pemeriksaan rp ON rp.id = rod.pemeriksaan_id
         WHERE ro.kunjungan_id = ?");
    $s->execute([$kunjunganId]);
    foreach ($s->fetchAll() as $r) {
        $base = (float) $r['base_tarif'];
        $markup = (float) ($r['markup_persen'] ?? 0);
        $calcTarif = $base + ($base * $markup / 100);
        $qty = (int) $r['qty'];
        $lines[] = ['kategori' => 'radiologi', 'item_code' => $r['kode'],
            'deskripsi' => $r['nama'],
            'qty' => $qty, 'tarif' => $calcTarif, 'subtotal' => $calcTarif * $qty];
    }

    // 4b) Diagnostik (item_code = kode pemeriksaan diagnostik)
    $s = db()->prepare(
        "SELECT dp.kode, dp.nama, dod.tarif, dod.qty, dod.subtotal FROM diag_order_detail dod
         JOIN diag_order do2 ON do2.id = dod.diag_order_id
         JOIN diag_pemeriksaan dp ON dp.id = dod.pemeriksaan_id
         WHERE do2.kunjungan_id = ?");
    $s->execute([$kunjunganId]);
    foreach ($s->fetchAll() as $r) {
        $lines[] = ['kategori' => 'diagnostik', 'item_code' => $r['kode'],
            'deskripsi' => $r['nama'],
            'qty' => (int) $r['qty'], 'tarif' => (float) $r['tarif'], 'subtotal' => (float) $r['subtotal']];
    }

    // 4c) Fisioterapi (item_code = kode layanan fisioterapi)
    $s = db()->prepare(
        "SELECT fp.kode, fp.nama, fod.tarif, fod.qty, fod.subtotal FROM fisio_order_detail fod
         JOIN fisio_order fo ON fo.id = fod.fisio_order_id
         JOIN fisio_pemeriksaan fp ON fp.id = fod.pemeriksaan_id
         WHERE fo.kunjungan_id = ?");
    $s->execute([$kunjunganId]);
    foreach ($s->fetchAll() as $r) {
        $lines[] = ['kategori' => 'fisioterapi', 'item_code' => $r['kode'],
            'deskripsi' => $r['nama'],
            'qty' => (int) $r['qty'], 'tarif' => (float) $r['tarif'], 'subtotal' => (float) $r['subtotal']];
    }

    // 5) Farmasi / obat (item_code = kode obat)
    $s = db()->prepare(
        "SELECT o.kode, o.nama, rd.qty, o.harga_beli, o.markup_persen FROM resep_detail rd
         JOIN resep r ON r.id = rd.resep_id
         JOIN obat o ON o.id = rd.obat_id
         WHERE r.kunjungan_id = ?");
    $s->execute([$kunjunganId]);
    foreach ($s->fetchAll() as $r) {
        $base = (float) $r['harga_beli'];
        $markup = (float) ($r['markup_persen'] ?? 0);
        $calcTarif = $base + ($base * $markup / 100);
        $qty = (int) $r['qty'];
        $lines[] = ['kategori' => 'farmasi', 'item_code' => $r['kode'],
            'deskripsi' => $r['nama'],
            'qty' => $qty, 'tarif' => $calcTarif, 'subtotal' => $calcTarif * $qty];
    }

    return $lines;
}

/** Label kategori untuk tampilan (Bahasa Indonesia) */
function billing_kategori_label(string $k): string
{
    return [
        'jasa_dokter'  => 'Jasa Dokter', 'tindakan' => 'Medical Service',
        'laboratorium' => 'Laboratorium', 'radiologi' => 'Radiologi',
        'diagnostik'   => 'Diagnostik', 'fisioterapi' => 'Fisioterapi',
        'farmasi'      => 'Farmasi', 'administrasi' => 'Administrasi',
    ][$k] ?? ucfirst($k);
}

/** Grup struk mengikuti format receipt resmi (heading kapital) */
function struk_grup_label(string $kategori): string
{
    return [
        'laboratorium' => 'LABORATORY', 'radiologi' => 'RADIOLOGY',
        'diagnostik'   => 'DIAGNOSTIC', 'fisioterapi' => 'PHYSIOTHERAPY',
        'jasa_dokter'  => 'MEDICAL SERVICE', 'tindakan' => 'MEDICAL SERVICE',
        'administrasi' => 'MEDICAL SERVICE', 'farmasi' => 'FARMASI',
    ][$kategori] ?? 'LAINNYA';
}

/** Urutan tampil grup di struk */
function struk_grup_urutan(): array
{
    return ['LABORATORY', 'RADIOLOGY', 'DIAGNOSTIC', 'PHYSIOTHERAPY', 'MEDICAL SERVICE', 'FARMASI', 'LAINNYA'];
}

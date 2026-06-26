<?php
$file = 'legacy/includes/billing_lib.php';
$content = file_get_contents($file);

// Lab
$content = preg_replace(
    '/SELECT lp\.kode, lp\.nama, lod\.tarif, lod\.qty, lod\.subtotal FROM lab_order_detail lod\s+JOIN lab_order lo ON lo\.id = lod\.lab_order_id\s+JOIN lab_pemeriksaan lp ON lp\.id = lod\.pemeriksaan_id\s+WHERE lo\.kunjungan_id = \?/',
    'SELECT lp.kode, lp.nama, lod.qty, lp.tarif AS base_tarif, lp.markup_persen FROM lab_order_detail lod
         JOIN lab_order lo ON lo.id = lod.lab_order_id
         JOIN lab_pemeriksaan lp ON lp.id = lod.pemeriksaan_id
         WHERE lo.kunjungan_id = ?',
    $content
);

$content = preg_replace(
    '/\$lines\[\] = \[\'kategori\' => \'laboratorium\', \'item_code\' => \$r\[\'kode\'\],\s+\'deskripsi\' => \$r\[\'nama\'\],\s+\'qty\' => \(int\) \$r\[\'qty\'\], \'tarif\' => \(float\) \$r\[\'tarif\'\], \'subtotal\' => \(float\) \$r\[\'subtotal\'\]\];/',
    '$base = (float) $r[\'base_tarif\'];
        $markup = (float) ($r[\'markup_persen\'] ?? 0);
        $calcTarif = $base + ($base * $markup / 100);
        $qty = (int) $r[\'qty\'];
        $lines[] = [\'kategori\' => \'laboratorium\', \'item_code\' => $r[\'kode\'],
            \'deskripsi\' => $r[\'nama\'],
            \'qty\' => $qty, \'tarif\' => $calcTarif, \'subtotal\' => $calcTarif * $qty];',
    $content
);

// Rad
$content = preg_replace(
    '/SELECT rp\.kode, rp\.nama, rod\.tarif, rod\.qty, rod\.subtotal FROM rad_order_detail rod\s+JOIN rad_order ro ON ro\.id = rod\.rad_order_id\s+JOIN rad_pemeriksaan rp ON rp\.id = rod\.pemeriksaan_id\s+WHERE ro\.kunjungan_id = \?/',
    'SELECT rp.kode, rp.nama, rod.qty, rp.tarif AS base_tarif, rp.markup_persen FROM rad_order_detail rod
         JOIN rad_order ro ON ro.id = rod.rad_order_id
         JOIN rad_pemeriksaan rp ON rp.id = rod.pemeriksaan_id
         WHERE ro.kunjungan_id = ?',
    $content
);

$content = preg_replace(
    '/\$lines\[\] = \[\'kategori\' => \'radiologi\', \'item_code\' => \$r\[\'kode\'\],\s+\'deskripsi\' => \$r\[\'nama\'\],\s+\'qty\' => \(int\) \$r\[\'qty\'\], \'tarif\' => \(float\) \$r\[\'tarif\'\], \'subtotal\' => \(float\) \$r\[\'subtotal\'\]\];/',
    '$base = (float) $r[\'base_tarif\'];
        $markup = (float) ($r[\'markup_persen\'] ?? 0);
        $calcTarif = $base + ($base * $markup / 100);
        $qty = (int) $r[\'qty\'];
        $lines[] = [\'kategori\' => \'radiologi\', \'item_code\' => $r[\'kode\'],
            \'deskripsi\' => $r[\'nama\'],
            \'qty\' => $qty, \'tarif\' => $calcTarif, \'subtotal\' => $calcTarif * $qty];',
    $content
);

// Obat
$content = preg_replace(
    '/SELECT o\.kode, o\.nama, rd\.qty, rd\.harga, rd\.subtotal FROM resep_detail rd\s+JOIN resep r ON r\.id = rd\.resep_id\s+JOIN obat o ON o\.id = rd\.obat_id\s+WHERE r\.kunjungan_id = \?/',
    'SELECT o.kode, o.nama, rd.qty, o.harga_beli, o.markup_persen FROM resep_detail rd
         JOIN resep r ON r.id = rd.resep_id
         JOIN obat o ON o.id = rd.obat_id
         WHERE r.kunjungan_id = ?',
    $content
);

$content = preg_replace(
    '/\$lines\[\] = \[\'kategori\' => \'farmasi\', \'item_code\' => \$r\[\'kode\'\],\s+\'deskripsi\' => \$r\[\'nama\'\],\s+\'qty\' => \(int\) \$r\[\'qty\'\], \'tarif\' => \(float\) \$r\[\'harga\'\], \'subtotal\' => \(float\) \$r\[\'subtotal\'\]\];/',
    '$base = (float) $r[\'harga_beli\'];
        $markup = (float) ($r[\'markup_persen\'] ?? 0);
        $calcTarif = $base + ($base * $markup / 100);
        $qty = (int) $r[\'qty\'];
        $lines[] = [\'kategori\' => \'farmasi\', \'item_code\' => $r[\'kode\'],
            \'deskripsi\' => $r[\'nama\'],
            \'qty\' => $qty, \'tarif\' => $calcTarif, \'subtotal\' => $calcTarif * $qty];',
    $content
);

file_put_contents($file, $content);
echo "Berhasil diperbarui secara paksa!";

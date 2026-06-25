<?php
/**
 * Library master data: skema penomoran item code berprefix GBK
 * (mengikuti format struk resmi client, mis. GBKLA0001).
 */
require_once __DIR__ . '/functions.php';

/** Prefix item code per jenis master */
function item_code_prefix(string $jenis): string
{
    return [
        'tindakan'   => 'GBAD',  // Medical Service
        'lab'        => 'GBLA',  // Laboratory
        'radiologi'  => 'GBRA',  // Radiology
        'obat'       => 'GBFA',  // Farmasi
        'dokter'     => 'GBDR',  // Dokter (jasa)
        'administrasi' => 'GBAD', // Administrasi
        'diagnostik'  => 'GBDG',  // Diagnostik
        'fisioterapi' => 'GBFT',  // Fisioterapi
    ][$jenis] ?? 'GBKXX';
}

/**
 * Generate item code berikutnya untuk jenis tertentu.
 * Mencari nomor terbesar yang sudah ada lalu +1 (mis. GBKLA0006).
 */
function generate_item_code(string $jenis, string $table, string $column = 'kode'): string
{
    $prefix = item_code_prefix($jenis);
    $stmt = db()->prepare("SELECT MAX($column) FROM $table WHERE $column LIKE ?");
    $stmt->execute([$prefix . '%']);
    $max = $stmt->fetchColumn();
    $next = $max ? (int) substr((string) $max, strlen($prefix)) + 1 : 1;
    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

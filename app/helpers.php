<?php

use App\Support\Icons;

if (! function_exists('app_icon')) {
    function app_icon(string $name): string
    {
        return Icons::svg($name);
    }
}

if (! function_exists('rupiah')) {
    function rupiah($angka): string
    {
        return 'Rp ' . number_format((float) $angka, 0, ',', '.');
    }
}

if (! function_exists('tgl_id')) {
    function tgl_id(?string $date, bool $withTime = false): string
    {
        if (empty($date)) {
            return '-';
        }

        $ts = strtotime($date);
        $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);

        if ($withTime) {
            $out .= ' ' . date('H:i', $ts);
        }

        return $out;
    }
}

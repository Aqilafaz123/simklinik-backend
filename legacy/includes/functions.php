<?php
/**
 * Helper legacy — aman di-load bersama Laravel (proxy /legacy/*).
 * Fungsi yang bentrok dengan Laravel memakai nama legacy_* / sim_csrf_*.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (! function_exists('legacy_url')) {
    function legacy_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'assets/')) {
            return '/' . $path;
        }

        return BASE_URL . $path;
    }
}

if (! function_exists('upload_fs_path')) {
    function upload_fs_path(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        if (str_starts_with($relativePath, 'uploads/')) {
            $relativePath = substr($relativePath, 8);
        }

        return UPLOAD_PATH . '/' . $relativePath;
    }
}

if (! function_exists('legacy_redirect')) {
    function legacy_redirect(string $path): void
    {
        header('Location: ' . legacy_url($path));
        exit;
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

if (! function_exists('set_flash')) {
    function set_flash(string $type, string $msg): void
    {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    }
}

if (! function_exists('get_flash')) {
    function get_flash(): ?array
    {
        if (! empty($_SESSION['flash'])) {
            $f = $_SESSION['flash'];
            unset($_SESSION['flash']);

            return $f;
        }

        return null;
    }
}

if (! function_exists('sim_csrf_token')) {
    function sim_csrf_token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }
}

if (! function_exists('sim_csrf_field')) {
    function sim_csrf_field(): string
    {
        return '<input type="hidden" name="csrf" value="' . sim_csrf_token() . '">';
    }
}

if (! function_exists('sim_csrf_verify')) {
    function sim_csrf_verify(): void
    {
        $token = $_POST['csrf'] ?? '';
        if (! hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(419);
            die('CSRF token tidak valid. Muat ulang halaman.');
        }
    }
}

if (! function_exists('terbilang')) {
    function terbilang($angka): string
    {
        $angka = (int) abs($angka);
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($angka < 12) {
            $hasil = $satuan[$angka];
        } elseif ($angka < 20) {
            $hasil = terbilang($angka - 10) . ' belas';
        } elseif ($angka < 100) {
            $hasil = terbilang(intdiv($angka, 10)) . ' puluh ' . terbilang($angka % 10);
        } elseif ($angka < 200) {
            $hasil = 'seratus ' . terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $hasil = terbilang(intdiv($angka, 100)) . ' ratus ' . terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $hasil = 'seribu ' . terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil = terbilang(intdiv($angka, 1000)) . ' ribu ' . terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $hasil = terbilang(intdiv($angka, 1000000)) . ' juta ' . terbilang($angka % 1000000);
        } else {
            $hasil = terbilang(intdiv($angka, 1000000000)) . ' miliar ' . terbilang($angka % 1000000000);
        }

        return trim(preg_replace('/\s+/', ' ', $hasil));
    }
}

if (! function_exists('terbilang_rupiah')) {
    function terbilang_rupiah($angka): string
    {
        $kata = trim(terbilang($angka));
        if ($kata === '') {
            $kata = 'nol';
        }

        return ucfirst($kata) . ' rupiah';
    }
}

if (! function_exists('terbilang_en')) {
    function terbilang_en($angka): string
    {
        $n = (int) round((float) $angka);
        if ($n === 0) {
            return 'Zero';
        }
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $three = function (int $num) use ($ones, $tens): string {
            $str = '';
            $h = intdiv($num, 100);
            $r = $num % 100;
            if ($h) {
                $str .= $ones[$h] . ' Hundred';
            }
            if ($r) {
                if ($str) {
                    $str .= ' And ';
                }
                if ($r < 20) {
                    $str .= $ones[$r];
                } else {
                    $str .= $tens[intdiv($r, 10)];
                    if ($r % 10) {
                        $str .= '-' . $ones[$r % 10];
                    }
                }
            }

            return $str;
        };
        $scales = ['', ' Thousand', ' Million', ' Billion', ' Trillion'];
        $groups = [];
        while ($n > 0) {
            $groups[] = $n % 1000;
            $n = intdiv($n, 1000);
        }
        $parts = [];
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            if ($groups[$i] === 0) {
                continue;
            }
            $parts[] = $three($groups[$i]) . $scales[$i];
        }

        return implode(' ', $parts);
    }
}

if (! function_exists('terbilang_en_rupiah')) {
    function terbilang_en_rupiah($angka): string
    {
        return terbilang_en($angka) . ' Rupiah';
    }
}

if (! function_exists('generate_no')) {
    function generate_no(string $prefix, string $table, string $column): string
    {
        $tgl = date('Ymd');
        $like = $prefix . '-' . $tgl . '-%';
        $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} LIKE ?");
        $stmt->execute([$like]);
        $urut = (int) $stmt->fetchColumn() + 1;

        return sprintf('%s-%s-%04d', $prefix, $tgl, $urut);
    }
}

// Alias untuk mode standalone (tanpa Laravel)
if (! defined('SIM_LEGACY_PROXY')) {
    if (! function_exists('url')) {
        function url(string $path = ''): string
        {
            return legacy_url($path);
        }
    }
    if (! function_exists('redirect')) {
        function redirect(string $path): void
        {
            legacy_redirect($path);
        }
    }
    if (! function_exists('csrf_token')) {
        function csrf_token(): string
        {
            return sim_csrf_token();
        }
    }
    if (! function_exists('csrf_field')) {
        function csrf_field(): string
        {
            return sim_csrf_field();
        }
    }
    if (! function_exists('csrf_verify')) {
        function csrf_verify(): void
        {
            sim_csrf_verify();
        }
    }
    if (! function_exists('e')) {
        function e(?string $str): string
        {
            return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
        }
    }
}

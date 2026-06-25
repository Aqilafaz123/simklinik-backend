<?php

/**
 * Satu kali: ganti helper legacy yang bentrok dengan Laravel.
 * php scripts/fix-legacy-helpers.php
 */

$root = dirname(__DIR__) . '/legacy';
$skip = realpath($root . '/includes/functions.php');

$replacements = [
    '/\bredirect\s*\(/' => 'legacy_redirect(',
    '/\burl\s*\(/' => 'legacy_url(',
    '/\bcsrf_verify\s*\(/' => 'sim_csrf_verify(',
    '/\bcsrf_field\s*\(/' => 'sim_csrf_field(',
    '/\bcsrf_token\s*\(/' => 'sim_csrf_token(',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    if (realpath($file->getPathname()) === $skip) {
        continue;
    }

    $path = $file->getPathname();
    $original = file_get_contents($path);
    $updated = $original;

    foreach ($replacements as $pattern => $replacement) {
        $updated = preg_replace($pattern, $replacement, $updated);
    }

    if ($updated !== $original) {
        file_put_contents($path, $updated);
        $changed++;
        echo "Updated: {$path}\n";
    }
}

echo "Done. {$changed} file(s) updated.\n";

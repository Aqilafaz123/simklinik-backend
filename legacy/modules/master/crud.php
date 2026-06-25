<?php
// Halaman daftar per-entitas kini digabung ke index.php (tampilan tab).
// File ini dipertahankan agar tautan lama (mis. dari form.php) tetap berfungsi.
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

$slug = $_GET['e'] ?? '';
legacy_redirect('modules/master/index.php' . ($slug !== '' ? '?e=' . urlencode($slug) : ''));

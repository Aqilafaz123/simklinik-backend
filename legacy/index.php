<?php
require_once __DIR__ . '/includes/auth.php';
legacy_redirect(is_logged_in() ? 'modules/dashboard/index.php' : 'auth/login.php');

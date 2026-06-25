<?php
require_once __DIR__ . '/../includes/auth.php';
logout();
legacy_redirect('auth/login.php');

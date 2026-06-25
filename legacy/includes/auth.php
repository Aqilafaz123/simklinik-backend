<?php
/**
 * Autentikasi & otorisasi berbasis Session PHP (custom, tanpa pihak ketiga)
 */

require_once __DIR__ . '/functions.php';

/** Coba login dengan username & password */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare(
        "SELECT u.*, r.kode AS role_kode, r.nama AS role_nama
         FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.username = ? AND u.status = 'aktif' LIMIT 1"
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // regenerate session id mencegah session fixation
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'nama'      => $user['nama'],
            'username'  => $user['username'],
            'role'      => $user['role_kode'],
            'role_nama' => $user['role_nama'],
            'avatar'    => $user['avatar'] ?? '',
            'poli_id'   => $user['poli_id'] !== null ? (int) $user['poli_id'] : null,
        ];
        db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
            ->execute([$user['id']]);
        return true;
    }
    return false;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

/** Data user yang sedang login */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Role user saat ini */
function current_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

/** Poli yang dikaitkan ke akun saat ini (untuk Dokter); null bila tidak ada */
function current_poli_id(): ?int
{
    $p = $_SESSION['user']['poli_id'] ?? null;
    return $p !== null ? (int) $p : null;
}

/** Wajib login - panggil di awal setiap halaman terproteksi */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan login terlebih dahulu.');
        legacy_redirect('auth/login.php');
    }
}

/** Cek apakah role saat ini termasuk salah satu yang diizinkan */
function has_role(string ...$roles): bool
{
    return in_array(current_role(), $roles, true);
}

/** Wajib salah satu role (admin selalu diizinkan) */
function require_role(string ...$roles): void
{
    require_login();
    if (current_role() === 'admin') return;     // admin akses semua
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        set_flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
        legacy_redirect('modules/dashboard/index.php');
    }
}

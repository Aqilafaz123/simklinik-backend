<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyController extends Controller
{
    public function __invoke(Request $request, string $path = ''): Response
    {
        $path = str_replace(['..', '\\'], '', $path);
        $normalized = ltrim($path, '/');

        if ($normalized === 'auth/logout.php') {
            return app(LoginController::class)->logout($request);
        }

        if ($normalized === 'auth/login.php') {
            return redirect()->route('login');
        }

        $file = base_path('legacy/' . $normalized);

        if (! is_file($file) || ! str_ends_with($file, '.php')) {
            abort(404);
        }

        $realBase = realpath(base_path('legacy'));
        $realFile = realpath($file);

        if (! $realFile || ! str_starts_with($realFile, $realBase)) {
            abort(404);
        }

        if (! defined('SIM_LEGACY_PROXY')) {
            define('SIM_LEGACY_PROXY', true);
        }

        $_SERVER['SCRIPT_NAME'] = '/legacy/' . ltrim($path, '/');

        require_once base_path('legacy/includes/functions.php');

        $user = $request->user();
        if ($user) {
            $user->loadMissing('role');
            $_SESSION['user'] = [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'role' => $user->role?->kode,
                'role_nama' => $user->role?->nama,
                'avatar' => $user->avatar ?? '',
                'poli_id' => $user->poli_id,
            ];
        }

        chdir(dirname($realFile));

        ob_start();
        require $realFile;
        $content = ob_get_clean();

        return response($content);
    }
}

<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\MenuService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $configured = env('FRONTEND_PATH', '../simklinik-frontend');
        $frontendPath = str_starts_with((string) $configured, DIRECTORY_SEPARATOR)
            ? $configured
            : base_path($configured);
        $frontendPath = realpath($frontendPath);

        if ($frontendPath && is_dir($frontendPath.'/resources/views')) {
            View::getFinder()->setPaths([$frontendPath.'/resources/views']);
        }

        View::composer('*', function ($view) {
            $settings = Setting::allKeyed();

            $view->with('clinicName', $settings['clinic_name'] ?? config('sim-klinik.clinic_name'));
            $view->with('clinicUnit', $settings['clinic_unit'] ?? config('sim-klinik.clinic_unit'));
            $view->with('clinicAddress', $settings['clinic_address'] ?? config('sim-klinik.clinic_address'));
            $view->with('clinicLogo', $settings['clinic_logo'] ?? '');
            $view->with('currentLocale', app()->getLocale());
        });

        View::composer(['layouts.app', 'components.sidebar'], function ($view) {
            $user = auth()->user();
            $menuService = app(MenuService::class);

            $view->with('menuGroups', $menuService->forRole($user?->roleKode()));
            $view->with('menuService', $menuService);
        });
    }
}

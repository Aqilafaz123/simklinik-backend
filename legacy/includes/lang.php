<?php
/**
 * Terjemahan untuk modul legacy PHP (delegasi ke Laravel bila tersedia).
 */

if (! function_exists('app_locale')) {
    function app_locale(): string
    {
        if (defined('SIM_LEGACY_PROXY') && function_exists('app') && app()->bound('translator')) {
            return app()->getLocale();
        }

        $locale = $_SESSION['locale'] ?? 'id';

        return in_array($locale, ['id', 'en'], true) ? $locale : 'id';
    }
}

if (! function_exists('t')) {
    function t(string $key, array $replace = []): string
    {
        if (defined('SIM_LEGACY_PROXY') && function_exists('app') && app()->bound('translator')) {
            return __($key, $replace);
        }

        static $cache = [];

        $locale = app_locale();
        if (! isset($cache[$locale])) {
            $base = dirname(__DIR__, 2) . '/lang/' . $locale;
            $cache[$locale] = [
                'app' => is_file($base . '/app.php') ? require $base . '/app.php' : [],
                'menu' => is_file($base . '/menu.php') ? require $base . '/menu.php' : [],
                'common' => is_file($base . '/common.php') ? require $base . '/common.php' : [],
                'pages' => is_file($base . '/pages.php') ? require $base . '/pages.php' : [],
                'datatable' => is_file($base . '/datatable.php') ? require $base . '/datatable.php' : [],
            ];
        }

        $segments = explode('.', $key);
        $value = $cache[$locale];
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $key;
            }
            $value = $value[$segment];
        }

        if (! is_string($value)) {
            return $key;
        }

        foreach ($replace as $search => $rep) {
            $value = str_replace(':' . $search, (string) $rep, $value);
        }

        return $value;
    }
}

if (! function_exists('role_label')) {
    function role_label(?string $kode, ?string $fallback = null): string
    {
        if ($kode) {
            $label = t('app.roles.' . $kode);
            if ($label !== 'app.roles.' . $kode) {
                return $label;
            }
        }

        return $fallback ?? ($kode ?? '');
    }
}

if (! function_exists('status_label')) {
    function status_label(string $status): string
    {
        $label = t('app.statuses.' . $status);

        return $label !== 'app.statuses.' . $status ? $label : ucfirst($status);
    }
}

if (! function_exists('payment_status_label')) {
    function payment_status_label(string $status): string
    {
        $label = t('app.payment_statuses.' . $status);

        return $label !== 'app.payment_statuses.' . $status ? $label : ucwords(str_replace('_', ' ', $status));
    }
}

if (! function_exists('resep_status_label')) {
    function resep_status_label(string $status): string
    {
        $label = t('app.resep_statuses.' . $status);

        return $label !== 'app.resep_statuses.' . $status ? $label : ucfirst($status);
    }
}

if (! function_exists('mutation_label')) {
    function mutation_label(string $jenis): string
    {
        $label = t('app.mutation_types.' . $jenis);

        return $label !== 'app.mutation_types.' . $jenis ? $label : ucfirst($jenis);
    }
}

if (! function_exists('diagnosis_type_label')) {
    function diagnosis_type_label(string $jenis): string
    {
        $label = t('app.diagnosis_types.' . $jenis);

        return $label !== 'app.diagnosis_types.' . $jenis ? $label : ucfirst($jenis);
    }
}

if (! function_exists('active_status_label')) {
    function active_status_label(string $status): string
    {
        return $status === 'aktif' ? t('common.active') : t('common.inactive');
    }
}

if (! function_exists('datatable_lang')) {
    function datatable_lang(): array
    {
        return [
            'search' => t('datatable.search'),
            'searchPlaceholder' => t('datatable.search_placeholder'),
            'lengthMenu' => t('datatable.length_menu'),
            'info' => t('datatable.info'),
            'infoEmpty' => t('datatable.info_empty'),
            'infoFiltered' => t('datatable.info_filtered'),
            'zeroRecords' => t('datatable.zero_records'),
            'emptyTable' => t('datatable.empty_table'),
            'paginate' => [
                'first' => t('datatable.paginate.first'),
                'previous' => t('datatable.paginate.previous'),
                'next' => t('datatable.paginate.next'),
                'last' => t('datatable.paginate.last'),
            ],
        ];
    }
}

if (! function_exists('datatable_lang_json')) {
    function datatable_lang_json(): string
    {
        return json_encode(datatable_lang(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}

if (! function_exists('lang_switcher_html')) {
    function lang_switcher_html(): string
    {
        $locale = app_locale();
        $idUrl = '/locale/id';
        $enUrl = '/locale/en';
        $idSel = $locale === 'id' ? ' selected' : '';
        $enSel = $locale === 'en' ? ' selected' : '';

        return '<div class="lang-picker">'
            . '<label class="lang-picker-label" for="appLangSelect">' . e(t('app.language')) . '</label>'
            . '<select id="appLangSelect" class="lang-picker-select" aria-label="' . e(t('app.language')) . '" onchange="if(this.value) window.location.href=this.value">'
            . '<option value="' . e($idUrl) . '"' . $idSel . '>' . e(t('app.lang_id')) . '</option>'
            . '<option value="' . e($enUrl) . '"' . $enSel . '>' . e(t('app.lang_en')) . '</option>'
            . '</select></div>';
    }
}

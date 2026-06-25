<?php
/**
 * Ikon SVG inline — delegasi ke App\Support\Icons bila Laravel sudah loaded.
 */
if (! function_exists('app_icon')) {
    function app_icon(string $name): string
    {
        if (class_exists(\App\Support\Icons::class)) {
            return \App\Support\Icons::svg($name);
        }

        return '<svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>';
    }
}

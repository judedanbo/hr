<?php

if (! function_exists('per_page')) {
    /**
     * Resolve the page size for a paginated list: a clamped `?per_page=`
     * query override (5–100), otherwise the configured pagination size.
     */
    function per_page(): int
    {
        $requested = request()->query('per_page');

        if (is_numeric($requested)) {
            return (int) max(5, min(100, (int) $requested));
        }

        return app(\App\Settings\GeneralSettings::class)->pagination_size;
    }
}

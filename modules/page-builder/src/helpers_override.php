<?php

/**
 * Override phpb_full_url in the GLOBAL namespace BEFORE PHPageBuilder's helpers.php is loaded.
 * 
 * This ensures all PHPageBuilder internal URLs use Laravel's url() helper,
 * which correctly accounts for the /backend/public subfolder deployment.
 * 
 * Without this, PHPageBuilder generates URLs like:
 *   https://demo-quatangtinhte.mbws.vn/admin/page-builder-lab/editor?action=store
 * Instead of the correct:
 *   https://demo-quatangtinhte.mbws.vn/backend/public/vi/admin/page-builder-lab/editor?action=store
 */

if (! function_exists('phpb_full_url')) {
    function phpb_full_url($urlRelativeToBaseUrl)
    {
        // If the URL is already a full URL, do not alter the URL
        if (strpos($urlRelativeToBaseUrl, 'http://') === 0 || strpos($urlRelativeToBaseUrl, 'https://') === 0) {
            return $urlRelativeToBaseUrl;
        }

        // Use Laravel's url() helper which includes the correct base path (/backend/public)
        return rtrim(url('/'), '/') . $urlRelativeToBaseUrl;
    }
}

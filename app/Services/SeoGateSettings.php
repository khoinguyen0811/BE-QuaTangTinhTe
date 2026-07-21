<?php

namespace App\Services;

use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Schema;

class SeoGateSettings
{
    public function strictModeEnabled(): bool
    {
        if (! Schema::hasTable('project_settings')) {
            return true;
        }

        $seo = ProjectSetting::query()
            ->where('setting_key', 'seo')
            ->value('setting_value');

        if (is_string($seo)) {
            $seo = json_decode($seo, true);
        }

        if (! is_array($seo) || ! array_key_exists('strict_post_gate', $seo)) {
            return true;
        }

        return filter_var($seo['strict_post_gate'], FILTER_VALIDATE_BOOLEAN);
    }
}

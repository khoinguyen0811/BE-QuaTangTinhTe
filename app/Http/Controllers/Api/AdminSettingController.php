<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    /**
     * Get all project settings.
     */
    public function index(Request $request)
    {
        $this->authorizeSettings($request);

        $settings = ProjectSetting::query()
            ->pluck('setting_value', 'setting_key')
            ->all();

        return ApiResponse::success($settings);
    }

    /**
     * Update project settings.
     */
    public function update(Request $request)
    {
        $this->authorizeSettings($request);

        $payload = $request->all();

        $whitelist = [
            'shop_name',
            'brand_name',
            'logo_url',
            'favicon_url',
            'contact',
            'theme_colors',
            'theme_typography',
            'seo',
            'social_links',
            'navigation_menu',
            'hero_banners',
            'home_sections',
            'home_sections_order'
        ];

        foreach ($payload as $key => $value) {
            if (in_array($key, $whitelist, true)) {
                ProjectSetting::updateOrCreate(
                    ['setting_key' => $key],
                    ['setting_value' => $value]
                );
            }
        }

        $settings = ProjectSetting::query()
            ->pluck('setting_value', 'setting_key')
            ->all();

        return ApiResponse::success($settings, 'Cập nhật cấu hình hệ thống thành công.');
    }

    /**
     * Authorize settings management access.
     */
    private function authorizeSettings(Request $request): void
    {
        $user = $request->user();
        $permissions = $user?->role?->permissions ?? [];
        $roleName = strtolower((string) ($user?->role?->name ?? ''));
        $allowedRole = str_contains($roleName, 'admin') || $roleName === 'system';

        abort_unless(
            $user && $user->role_id && (
                $user->isSuperAdmin()
                || $allowedRole
                || in_array('*', $permissions, true)
                || in_array('settings:write', $permissions, true)
                || in_array('settings:read', $permissions, true)
                || in_array('manage_settings', $permissions, true)
            ),
            403,
            'Bạn không có quyền quản lý cấu hình hệ thống.'
        );
    }
}

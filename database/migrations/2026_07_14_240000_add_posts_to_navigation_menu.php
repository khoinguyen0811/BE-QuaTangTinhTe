<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_settings')) {
            return;
        }

        $setting = DB::table('project_settings')
            ->where('setting_key', 'navigation_menu')
            ->first();

        $menu = $setting ? json_decode((string) $setting->setting_value, true) : [];
        if (! is_array($menu)) {
            $menu = [];
        }

        $hasPostsItem = collect($menu)->contains(function ($item) {
            if (! is_array($item)) {
                return false;
            }

            $path = parse_url(trim((string) ($item['href'] ?? '')), PHP_URL_PATH);

            return rtrim((string) $path, '/') === '/posts';
        });

        if ($hasPostsItem) {
            return;
        }

        $postsItem = [
            'label' => 'Bài viết',
            'href' => '/posts',
            'badge' => '',
            'visible' => true,
            'dropdown_mode' => 'single',
            'children' => [],
            'columns' => [],
        ];

        $contactIndex = collect($menu)->search(function ($item) {
            if (! is_array($item)) {
                return false;
            }

            $path = parse_url(trim((string) ($item['href'] ?? '')), PHP_URL_PATH);

            return rtrim((string) $path, '/') === '/contact';
        });

        $insertAt = $contactIndex === false ? count($menu) : (int) $contactIndex;
        array_splice($menu, $insertAt, 0, [$postsItem]);

        $values = [
            'setting_value' => json_encode($menu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ];

        if ($setting) {
            DB::table('project_settings')
                ->where('setting_key', 'navigation_menu')
                ->update($values);

            return;
        }

        DB::table('project_settings')->insert([
            'setting_key' => 'navigation_menu',
            ...$values,
        ]);
    }

    public function down(): void
    {
        // Preserve menu customizations made by administrators after this migration.
    }
};

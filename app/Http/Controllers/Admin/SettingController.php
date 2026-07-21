<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinaryService)
    {
    }

    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = ProjectSetting::query()->get()->pluck('setting_value', 'setting_key');

        $customPages = \App\Models\CustomPage::select('title', 'slug')->get();
        $categories = \App\Models\Category::select('id', 'name', 'slug')->get();
        $postCategories = \App\Models\PostCategory::select('id', 'name', 'slug')->get();

        return view('admin.settings.index', compact('settings', 'customPages', 'categories', 'postCategories'));
    }

    /**
     * Update the website settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
            'contact.phone' => 'nullable|string|max:20',
            'contact.email' => 'nullable|email|max:255',
            'contact.address' => 'nullable|string|max:500',
            'theme.primary_color' => 'nullable|string|max:7',
            'theme.layout' => 'nullable|string|in:default,compact,boxed',
            'seo.title' => 'nullable|string|max:255',
            'seo.description' => 'nullable|string|max:500',
            'seo.strict_post_gate' => 'nullable|boolean',
            'social_links.facebook' => 'nullable|url|max:255',
            'social_links.youtube' => 'nullable|url|max:255',
            'social_links.instagram' => 'nullable|url|max:255',
            'social_links.tiktok' => 'nullable|url|max:255',
            'navigation_menu' => 'nullable|string',
        ]);

        // Update basic and nested JSON columns
        ProjectSetting::updateOrCreate(
            ['setting_key' => 'shop_name'],
            ['setting_value' => $validated['shop_name']]
        );

        ProjectSetting::updateOrCreate(
            ['setting_key' => 'contact'],
            ['setting_value' => $validated['contact'] ?? []]
        );

        ProjectSetting::updateOrCreate(
            ['setting_key' => 'theme'],
            ['setting_value' => $validated['theme'] ?? []]
        );

        $existingSeo = ProjectSetting::query()
            ->where('setting_key', 'seo')
            ->value('setting_value');
        if (is_string($existingSeo)) {
            $existingSeo = json_decode($existingSeo, true);
        }
        $seoSettings = array_merge(
            is_array($existingSeo) ? $existingSeo : [],
            $validated['seo'] ?? []
        );
        $seoSettings['strict_post_gate'] = $request->has('seo.strict_post_gate')
            ? $request->boolean('seo.strict_post_gate')
            : (bool) ($seoSettings['strict_post_gate'] ?? true);

        ProjectSetting::updateOrCreate(
            ['setting_key' => 'seo'],
            ['setting_value' => $seoSettings]
        );

        ProjectSetting::updateOrCreate(
            ['setting_key' => 'social_links'],
            ['setting_value' => $validated['social_links'] ?? []]
        );

        ProjectSetting::updateOrCreate(
            ['setting_key' => 'navigation_menu'],
            ['setting_value' => $this->normalizeNavigationMenu($request->input('navigation_menu'))]
        );

        // Upload logo if uploaded
        if ($request->hasFile('logo')) {
            $logoUrl = $this->cloudinaryService->uploadFile($request->file('logo'), 'settings');
            ProjectSetting::updateOrCreate(
                ['setting_key' => 'logo_url'],
                ['setting_value' => $logoUrl]
            );
        }

        // Upload favicon if uploaded
        if ($request->hasFile('favicon')) {
            $faviconUrl = $this->cloudinaryService->uploadFile($request->file('favicon'), 'settings');
            ProjectSetting::updateOrCreate(
                ['setting_key' => 'favicon_url'],
                ['setting_value' => $faviconUrl]
            );
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật cấu hình website thành công.'
            ]);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Đã cập nhật cấu hình website thành công.');
    }

    private function normalizeNavigationMenu(?string $rawMenu): array
    {
        $decoded = json_decode($rawMenu ?: '[]', true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $mode = ($item['dropdown_mode'] ?? 'single') === 'multi' ? 'multi' : 'single';

                return [
                    'label' => trim((string) ($item['label'] ?? '')),
                    'href' => trim((string) ($item['href'] ?? '#')) ?: '#',
                    'badge' => trim((string) ($item['badge'] ?? '')),
                    'visible' => (bool) ($item['visible'] ?? true),
                    'dropdown_mode' => $mode,
                    'children' => $this->normalizeMenuLinks($item['children'] ?? []),
                    'columns' => $this->normalizeMenuColumns($item['columns'] ?? []),
                ];
            })
            ->filter(fn ($item) => $item['label'] !== '')
            ->values()
            ->all();
    }

    private function normalizeMenuColumns(mixed $columns): array
    {
        if (! is_array($columns)) {
            return [];
        }

        return collect($columns)
            ->filter(fn ($column) => is_array($column))
            ->map(fn (array $column) => [
                'title' => trim((string) ($column['title'] ?? '')),
                'items' => $this->normalizeMenuLinks($column['items'] ?? []),
            ])
            ->filter(fn ($column) => $column['title'] !== '' || count($column['items']) > 0)
            ->values()
            ->all();
    }

    private function normalizeMenuLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        return collect($links)
            ->filter(fn ($link) => is_array($link))
            ->map(fn (array $link) => [
                'label' => trim((string) ($link['label'] ?? '')),
                'href' => trim((string) ($link['href'] ?? '#')) ?: '#',
                'visible' => (bool) ($link['visible'] ?? true),
            ])
            ->filter(fn ($link) => $link['label'] !== '')
            ->values()
            ->all();
    }
}

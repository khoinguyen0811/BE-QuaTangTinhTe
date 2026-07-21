<?php

namespace App\Services;

use App\Models\CustomPage;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPage;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPageTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateCustomPageWithBuilderService
{
    /**
     * Create a CustomPage along with its PageBuilderPage bridge inside a transaction.
     *
     * @param array $data
     * @param int $userId
     * @return CustomPage
     */
    public function create(array $data, int $userId): CustomPage
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Create page builder record
            $builderPage = PageBuilderPage::create([
                'name' => $data['title'],
                'layout' => $data['layout'] ?? 'full-width',
                'data' => '{}',
                'draft_html' => '',
                'draft_css' => '',
            ]);

            // 2. Create translations for the builder page
            $locale = $data['locale'] ?? app()->getLocale() ?: 'vi';
            PageBuilderPageTranslation::create([
                'page_id' => $builderPage->id,
                'locale' => $locale,
                'title' => $data['title'],
                'meta_title' => $data['seo_title'] ?? $data['title'],
                'meta_description' => $data['seo_description'] ?? '',
                'route' => $data['slug'],
            ]);

            // 3. Create custom page record linking to the builder page
            $customPage = CustomPage::create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'builder_page_id' => $builderPage->id,
                'builder_driver' => 'laravel-pagebuilder',
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'seo_image' => $data['seo_image'] ?? null,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $customPage;
        });
    }
}

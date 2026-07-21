<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use Illuminate\Support\Facades\Cache;

class CustomPageController extends Controller
{
    public function show(string $slug)
    {
        // Caching strategy for page data layout
        $page = Cache::remember("custom_page:data:{$slug}", 3600, function () use ($slug) {
            return CustomPage::query()
                ->published()
                ->where('slug', $slug)
                ->first();
        });

        if (!$page) {
            abort(404, 'Trang không tồn tại hoặc chưa được xuất bản.');
        }

        $layout = $page->layout_published ?: ['schema_version' => 1, 'blocks' => []];

        return view('storefront.custom-pages.show', [
            'page' => $page,
            'layout' => $layout,
            'preview' => false,
        ]);
    }
}

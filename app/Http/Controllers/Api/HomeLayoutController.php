<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomeLayoutService;
use App\Support\ApiResponse;

class HomeLayoutController extends Controller
{
    public function show(HomeLayoutService $layouts)
    {
        $layout = $layouts->findOrCreate();

        return ApiResponse::success([
            'page_key' => HomeLayoutService::PAGE_KEY,
            'schema_version' => HomeLayoutService::SCHEMA_VERSION,
            'revision' => $layout?->published_revision ?? 0,
            'published_at' => $layout?->published_at?->toIso8601String(),
            'layout' => $layouts->publishedContent(),
        ])->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }
}

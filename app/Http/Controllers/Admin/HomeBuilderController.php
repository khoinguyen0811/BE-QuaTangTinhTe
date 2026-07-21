<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeBuilderController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.home-builder.index', [
            'previewUrl' => $this->storefrontUrl($request).'/?home_builder_preview=1',
        ]);
    }

    public function context(Request $request): JsonResponse
    {
        $user = $request->user();
        $canEditHome = (bool) ($user?->role_id && $user->can('manage_settings'));
        $locale = $this->defaultLocale();

        return response()->json([
            'authenticated' => (bool) $user,
            'can_edit_home' => $canEditHome,
            'builder_url' => $canEditHome
                ? $this->applicationUrl($request).'/'.$locale.'/admin/home-builder'
                : null,
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Vary' => 'Cookie',
        ]);
    }

    private function storefrontUrl(Request $request): string
    {
        $configuredFrontend = trim((string) config('app.frontend_url', ''));
        if ($configuredFrontend !== '') {
            return rtrim($configuredFrontend, '/');
        }

        $requestBase = rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');

        return preg_replace('#/backend/public$#', '', $requestBase) ?: $request->getSchemeAndHttpHost();
    }

    private function applicationUrl(Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost().$request->getBaseUrl(), '/');
    }

    private function defaultLocale(): string
    {
        $supported = array_keys(config('laravellocalization.supportedLocales', []));
        $locale = (string) config('app.locale', 'vi');

        return in_array($locale, $supported, true) ? $locale : ($supported[0] ?? 'vi');
    }
}

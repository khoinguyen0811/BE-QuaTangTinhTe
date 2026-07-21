<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SeoDiscoveryController;

Route::get('/sitemap.xml', [SeoDiscoveryController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/llms.txt', [SeoDiscoveryController::class, 'llms'])->name('seo.llms');
Route::get('/llms-full.txt', [SeoDiscoveryController::class, 'llmsFull'])->name('seo.llms-full');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect()->route('admin.login', ['locale' => config('app.locale', 'vi')]);
})->name('login');

Route::get('/storefront/editor-context', [\App\Http\Controllers\Admin\HomeBuilderController::class, 'context'])
    ->name('storefront.editor-context');

Route::get('/api/docs', [\App\Http\Controllers\Api\PublicController::class, 'docs'])->name('api.docs');
Route::get('/api/docs/openapi.json', [\App\Http\Controllers\Api\PublicController::class, 'openapi'])->name('api.docs.openapi');
Route::get('/docs/openapi.json', [\App\Http\Controllers\Api\PublicController::class, 'openapi'])->name('api.docs.openapi.legacy');

Route::get('/payment/vnpay/mock', [\App\Http\Controllers\Api\PublicController::class, 'vnpayMockPayment'])->name('vnpay.mock');
Route::post('/payment/vnpay/mock/submit', [\App\Http\Controllers\Api\PublicController::class, 'vnpayMockSubmit'])->name('vnpay.mock.submit');

Route::get('/pages/{slug}', [\App\Http\Controllers\Storefront\CustomPageController::class, 'show'])->name('pages.show');

// ── Route aliases: giữ nguyên URL cũ, render nội dung từ CustomPage database ──
Route::get('/about', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('gioi-thieu'))->name('about');
Route::get('/contact', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('lien-he'))->name('contact');
Route::get('/policies/payment', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('chinh-sach-thanh-toan'));
Route::get('/policies/shipping', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('chinh-sach-giao-hang'));
Route::get('/policies/purchase', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('chinh-sach-mua-hang'));
Route::get('/policies/return', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('chinh-sach-doi-tra'));
Route::get('/policies/refund', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('chinh-sach-hoan-tien'));
Route::get('/policies/privacy', fn () => app(\App\Http\Controllers\Storefront\CustomPageController::class)->show('chinh-sach-bao-mat'));

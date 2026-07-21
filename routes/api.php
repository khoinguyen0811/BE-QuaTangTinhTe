<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminHomeLayoutController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\HomeLayoutController;
use App\Http\Controllers\Api\PublicAuthController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\AddonWebhookController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Compatibility API Routes
|--------------------------------------------------------------------------
*/

// Plesk remote deployment database migration trigger
Route::match(['get', 'post'], '/system/migrate', [SystemController::class, 'migrate']);

// Compatibility endpoints mapped to match the static frontend client requests
Route::get('/settings', [PublicController::class, 'settings']);
Route::get('/pages/home', [HomeLayoutController::class, 'show']);
Route::get('/categories', [PublicController::class, 'categories']);
Route::get('/brands', [PublicController::class, 'brands']);
Route::get('/products', [PublicController::class, 'products']);
Route::get('/products/{id_or_slug}', [PublicController::class, 'productDetail']);
Route::get('/products/id/{id_or_slug}', [PublicController::class, 'productDetail']);
Route::get('/post-categories', [PublicController::class, 'postCategories']);
Route::get('/posts', [PublicController::class, 'posts']);
Route::get('/posts/{id_or_slug}', [PublicController::class, 'postDetail']);
Route::post('/uploads/customization', [PublicController::class, 'uploadCustomizationImage']);

// Checkout (Public)
Route::post('/orders', [PublicController::class, 'checkout']);
Route::get('/vouchers/eligible', [PublicController::class, 'eligibleVouchers']);
Route::post('/vouchers/apply', [PublicController::class, 'applyVoucher']);
Route::post('/cart/recalculate', [PublicController::class, 'recalculateCart']);

// Customer Auth APIs
Route::post('/auth/login', [PublicAuthController::class, 'login']);
Route::post('/auth/register', [PublicAuthController::class, 'register']);
Route::post('/auth/forgot-password', [PublicAuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [PublicAuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [PublicAuthController::class, 'logout']);
    Route::get('/auth/me', [PublicAuthController::class, 'me']);
    
    // User order history, detail, cancel
    Route::get('/orders', [PublicController::class, 'orderHistory']);
    Route::get('/orders/{id_or_slug}', [PublicController::class, 'orderDetail']);
    Route::put('/orders/{id}/cancel', [PublicController::class, 'cancelOrder']);

    // User address management
    Route::get('/user/addresses', [UserAddressController::class, 'index']);
    Route::post('/user/addresses', [UserAddressController::class, 'store']);
    Route::put('/user/addresses/{address}', [UserAddressController::class, 'update']);
    Route::delete('/user/addresses/{address}', [UserAddressController::class, 'destroy']);
    Route::patch('/user/addresses/{address}/set-default', [UserAddressController::class, 'setDefault']);
});

/*
|--------------------------------------------------------------------------
| Core System Original Routes (under /api/public/...)
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::get('/health', [PublicController::class, 'health']);
    Route::get('/settings', [PublicController::class, 'settings']);
    Route::get('/pages/home', [HomeLayoutController::class, 'show']);

    // Catalog routes
    Route::get('/categories', [PublicController::class, 'categories']);
    Route::get('/brands', [PublicController::class, 'brands']);
    Route::get('/products', [PublicController::class, 'products']);
    Route::get('/products/{id_or_slug}', [PublicController::class, 'productDetail']);
    Route::get('/post-categories', [PublicController::class, 'postCategories']);
    Route::get('/posts', [PublicController::class, 'posts']);
    Route::get('/posts/{id_or_slug}', [PublicController::class, 'postDetail']);

    Route::middleware('feature:review')->group(function () {
        Route::post('/products/{id_or_slug}/reviews', [PublicController::class, 'storeReview']);
    });

    // Vouchers
    Route::get('/vouchers/eligible', [PublicController::class, 'eligibleVouchers']);
    Route::post('/vouchers/apply', [PublicController::class, 'applyVoucher']);

    // Checkout & Tracking
    Route::post('/cart/recalculate', [PublicController::class, 'recalculateCart']);
    Route::post('/uploads/customization', [PublicController::class, 'uploadCustomizationImage']);
    Route::post('/orders/checkout', [PublicController::class, 'checkout']);
    Route::get('/orders/track', [PublicController::class, 'trackOrder']);
    Route::get('/payment/vnpay/ipn', [PublicController::class, 'vnpayIpn'])->name('api.payment.vnpay.ipn');
    Route::post('/webhooks/sepay-addon', [AddonWebhookController::class, 'handleWebhook'])->name('api.webhooks.sepay-addon');

    // Customer Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/register', [PublicAuthController::class, 'register']);
        Route::post('/login', [PublicAuthController::class, 'login']);
        Route::post('/forgot-password', [PublicAuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [PublicAuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [PublicAuthController::class, 'me']);
            Route::post('/logout', [PublicAuthController::class, 'logout']);
        });
    });

    // Guarded Orders history & Address management
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [PublicController::class, 'orderHistory']);
        Route::get('/orders/{order_number}', [PublicController::class, 'orderDetail']);

        Route::get('/addresses', [UserAddressController::class, 'index']);
        Route::post('/addresses', [UserAddressController::class, 'store']);
        Route::put('/addresses/{address}', [UserAddressController::class, 'update']);
        Route::delete('/addresses/{address}', [UserAddressController::class, 'destroy']);
        Route::patch('/addresses/{address}/set-default', [UserAddressController::class, 'setDefault']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/pages/home/draft', [AdminHomeLayoutController::class, 'showDraft']);
        Route::put('/pages/home/draft', [AdminHomeLayoutController::class, 'updateDraft']);
        Route::post('/pages/home/publish', [AdminHomeLayoutController::class, 'publish']);
        Route::get('/pages/home/versions', [AdminHomeLayoutController::class, 'versions']);
        Route::post('/pages/home/rollback/{revision}', [AdminHomeLayoutController::class, 'rollback']);
        Route::get('/pages/home/media', [AdminHomeLayoutController::class, 'mediaLibrary']);
        Route::post('/pages/home/media', [AdminHomeLayoutController::class, 'upload']);
        
        // System settings management
        Route::get('/settings', [\App\Http\Controllers\Api\AdminSettingController::class, 'index']);
        Route::put('/settings', [\App\Http\Controllers\Api\AdminSettingController::class, 'update']);
    });
});

Route::post('/webhooks/ghtk', [WebhookController::class, 'handleGHTK']);

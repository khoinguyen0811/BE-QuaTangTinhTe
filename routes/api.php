<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicController;
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
Route::get('/system/migrate', [SystemController::class, 'migrate']);

// Compatibility endpoints mapped to match the static frontend client requests
Route::get('/settings', [PublicController::class, 'settings']);
Route::get('/categories', [PublicController::class, 'categories']);
Route::get('/brands', [PublicController::class, 'brands']);
Route::get('/products', [PublicController::class, 'products']);
Route::get('/products/{id_or_slug}', [PublicController::class, 'productDetail']);
Route::get('/products/id/{id_or_slug}', [PublicController::class, 'productDetail']);

// Checkout (Public)
Route::post('/orders', [PublicController::class, 'checkout']);

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

    // Catalog routes
    Route::get('/categories', [PublicController::class, 'categories']);
    Route::get('/brands', [PublicController::class, 'brands']);
    Route::get('/products', [PublicController::class, 'products']);
    Route::get('/products/{id_or_slug}', [PublicController::class, 'productDetail']);

    Route::middleware('feature:review')->group(function () {
        Route::post('/products/{id_or_slug}/reviews', [PublicController::class, 'storeReview']);
    });

    // Vouchers
    Route::post('/vouchers/apply', [PublicController::class, 'applyVoucher']);

    // Checkout & Tracking
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
    });
});

Route::post('/webhooks/ghtk', [WebhookController::class, 'handleGHTK']);

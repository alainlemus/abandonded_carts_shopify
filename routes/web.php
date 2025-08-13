<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ShopifyAuthController;
use App\Http\Controllers\ShopifyWebhookController;

Route::middleware(['web'])->group(function () {
    Route::get('/register', [LandingController::class, 'index'])->name('register');
    Route::post('/register/tenant', [TenantController::class, 'register'])->name('register.tenant');
});

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('welcome'); // Asegúrate de que resources/views/welcome.blade.php exista
        })->name('home');

        Route::get('/shopify/auth', [ShopifyAuthController::class, 'redirect'])->name('shopify.auth');
        Route::get('/shopify/callback', [ShopifyAuthController::class, 'callback'])->name('shopify.callback');
        Route::post('/webhooks', [ShopifyWebhookController::class, 'handle'])->name('webhooks.shopify');
    });

}

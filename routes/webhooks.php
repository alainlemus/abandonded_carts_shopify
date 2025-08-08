<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyWebhookController;

Route::post('/webhooks/shopify', [ShopifyWebhookController::class, 'handle'])->middleware(\Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class)->name('webhooks.shopify');

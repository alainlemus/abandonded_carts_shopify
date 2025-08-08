<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\ShopifyService;
use App\Models\Shop;
use App\Models\AbandonedCart;

class SyncAbandonedCarts implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $shop;

    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
    }

    public function handle(ShopifyService $shopify)
    {
        $shopify->setAccessToken($this->shop->access_token, $this->shop->shopify_domain);
        $carts = $shopify->getAbandonedCarts($this->shop);

        foreach ($carts as $cartData) {
            $cart = AbandonedCart::updateOrCreate(
                ['shopify_cart_id' => $cartData['id']],
                [
                    'shop_id' => $this->shop->id,
                    'customer_email' => $cartData['email'] ?? 'unknown@example.com',
                    'total_price' => $cartData['total_price'] ?? 0.00,
                    'status' => 'pending',
                ]
            );

            ProcessAbandonedCart::dispatch($cart)->delay(now()->addMinutes(30));
        }
    }
}
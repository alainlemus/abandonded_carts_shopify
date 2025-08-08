<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\AbandonedCart;
use App\Services\ShopifyService;
use App\Services\WhatsAppService;
use App\Services\DiscountService;

class ProcessAbandonedCart implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $cart;

    public function __construct(AbandonedCart $cart)
    {
        $this->cart = $cart;
    }

    public function handle(ShopifyService $shopify, WhatsAppService $whatsapp, DiscountService $discount)
    {
        $shopify->setAccessToken($this->cart->shop->access_token, $this->cart->shop->shopify_domain);

        $discountCode = null;
        if ($this->cart->total_price > 100) {
            $discountCode = $discount->createDiscount($this->cart, 10, $shopify);
        }

        $message = $whatsapp->sendMessage($this->cart, $discountCode ? $discountCode->code : null);

        $this->cart->update(['status' => $message ? 'sent' : 'failed']);
    }
}
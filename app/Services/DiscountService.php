<?php
namespace App\Services;

use App\Models\Discount;

class DiscountService
{
    public function createDiscount($cart, $amount, ShopifyService $shopify)
    {
        $discountCode = $shopify->createDiscount($cart, $amount);
        if ($discountCode) {
            return Discount::create([
                'abandoned_cart_id' => $cart->id,
                'code' => $discountCode,
                'amount' => $amount,
            ]);
        }
        return null;
    }
}
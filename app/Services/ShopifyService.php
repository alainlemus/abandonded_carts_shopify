<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected $accessToken;
    protected $shopDomain;

    public function setAccessToken($token, $shopDomain)
    {
        $this->accessToken = $token;
        $this->shopDomain = $shopDomain;
        return $this;
    }

    public function getAbandonedCarts($shop)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
        ])->get("https://{$this->shopDomain}/admin/api/" . config('services.shopify.api_version') . "/checkouts.json");

        Log::info('Obteniendo carritos abandonados para shop: ' . $response);
        return $response->successful() ? $response->json()['checkouts'] : [];
    }

    public function createDiscount($cart, $amount)
    {
        $code = 'ABANDONED_' . $cart->id;
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
        ])->post("https://{$this->shopDomain}/admin/api/" . config('services.shopify.api_version') . "/price_rules.json", [
            'price_rule' => [
                'title' => $code,
                'target_type' => 'line_item',
                'target_selection' => 'all',
                'allocation_method' => 'across',
                'value_type' => 'percentage',
                'value' => -$amount,
                'customer_selection' => 'all',
                'starts_at' => now()->toIso8601String(),
            ],
        ]);

        if ($response->successful()) {
            $priceRuleId = $response->json()['price_rule']['id'];
            $discountResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
            ])->post("https://{$this->shopDomain}/admin/api/" . config('services.shopify.api_version') . "/price_rules/{$priceRuleId}/discount_codes.json", [
                'discount_code' => ['code' => $code],
            ]);

            return $discountResponse->successful() ? $code : null;
        }

        return null;
    }

    public function registerWebhook($shop, $topic, $address)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
        ])->post("https://{$this->shopDomain}/admin/api/" . config('services.shopify.api_version') . "/webhooks.json", [
            'webhook' => [
                'topic' => $topic,
                'address' => $address,
                'format' => 'json',
            ],
        ]);

        return $response->successful();
    }
}

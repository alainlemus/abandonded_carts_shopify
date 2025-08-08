<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\AbandonedCart;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $topic = $request->header('X-Shopify-Topic');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $data = $request->all();

        Log::info("Webhook received: {$topic} from {$shopDomain}", $data);

        if ($topic === 'checkouts/create' || $topic === 'checkouts/update') {
            $cartId = $data['id'];
            $customer = $data['customer'] ?? null;
            $phone = $customer['phone'] ?? null;
            $name = $customer['first_name'] ?? 'Cliente';

            if ($phone) {
                $shop = Shop::where('shopify_domain', $shopDomain)->first();
                if ($shop) {

                    tenancy()->initialize($shop->tenant);

                    $abandonedCart = AbandonedCart::updateOrCreate(
                        ['cart_id' => $cartId, 'shop_id' => $shop->id],
                        [
                            'tenant_id' => $shop->tenant_id,
                            'customer_phone' => $phone,
                            'customer_name' => $name,
                            'cart_data' => json_encode($data),
                        ]
                    );

                    //SendWhatsAppMessage::dispatch($abandonedCart);
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}

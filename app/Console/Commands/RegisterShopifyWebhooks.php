<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShopifyService;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

class RegisterShopifyWebhooks extends Command
{
    protected $signature = 'shopify:register-webhooks {--shop=}';
    protected $description = 'Register Shopify webhooks for a given shop';

    public function handle(ShopifyService $shopify)
    {
        Log::info('Iniciando el registro de webhooks de Shopify');

        $shopDomain = $this->option('shop');

        if (!$shopDomain) {
            Log::error('Se requiere el dominio de la tienda Shopify.');
            $this->error('Se requiere el dominio de la tienda Shopify.');
            return 1;
        }

        // Buscar la tienda
        //$shop = Shop::where('shopify_domain', $shopDomain)->first();
        $shop = Shop::on('mysql')->where('shopify_domain', $shopDomain)->first();

        if (!$shop) {
            Log::error("No se encontró una tienda para el dominio {$shopDomain}.");
            $this->error("No se encontró una tienda para el dominio {$shopDomain}.");
            return 1;
        }

        // Inicializar el tenant
        tenancy()->initialize($shop->tenant);

        Log::info("Registrando webhooks para la tienda {$shopDomain}.");

        $shopify->setAccessToken($shop->access_token, $shopDomain); //truena aqui

        Log::info('se ha inicializado el token de acceso para la tienda', [
            'shopDomain' => $shopDomain,
            'access_token' => $shop->access_token,
        ]);

        $webhooks = [
            [
                'topic' => 'checkouts/create',
                'address' => route('webhooks.shopify', [], true),
            ],
            [
                'topic' => 'checkouts/update',
                'address' => route('webhooks.shopify', [], true),
            ],
        ];

        foreach ($webhooks as $webhook) {
            Log::info('Intentando registrar webhook', $webhook);

            $result = $shopify->registerWebhook($shop, $webhook['topic'], $webhook['address']);

            Log::info('Resultado de registerWebhook', ['resultado' => $result]);

            if ($result) {
                $this->info("Webhook {$webhook['topic']} registrado para {$shopDomain}.");
            } else {
                $this->error("Error al registrar webhook {$webhook['topic']} para {$shopDomain}.");
                return 1;
            }
        }

        $this->info('Webhooks registrados exitosamente.');
        return 0;
    }
}

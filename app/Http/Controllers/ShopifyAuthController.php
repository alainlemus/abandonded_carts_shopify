<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Facades\Tenancy;

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Facades\Tenancy;

class ShopifyAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $shopDomain = $request->query('shop');
        if (!$shopDomain) {
            return redirect()->back()->withErrors(['shop' => 'Se requiere el dominio de la tienda Shopify.']);
        }

        $apiKey = config('services.shopify.api_key');
        $scopes = config('services.shopify.scopes');
        $redirectUri = config('services.shopify.redirect_uri');
        $nonce = Str::random(10);

        $url = "https://{$shopDomain}/admin/oauth/authorize?client_id={$apiKey}&scope={$scopes}&redirect_uri={$redirectUri}&state={$nonce}";

        session(['shopify_nonce' => $nonce, 'shopify_domain' => $shopDomain]);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        $shopDomain = $request->query('shop');
        $state = $request->query('state');
        $code = $request->query('code');
        $subdomainTenant = session('tenant_subdomain');

        Log::info('Shopify callback iniciado', [
            'shopDomain' => $shopDomain,
            'state' => $state,
            'code' => $code,
            'subdomainTenant' => $subdomainTenant,
        ]);

        $subdomain = str_replace('.myshopify.com', '', $shopDomain);

        if ($state !== session('shopify_nonce')) {
            Log::error('Estado inválido en la respuesta de Shopify');
            return redirect()->to("http://{$subdomain}.app-localhost.test/admin/{$shopDomain}/shopify-settings")
                ->withErrors(['auth' => 'Error de autenticación: estado inválido.']);
        }

        $response = Http::post("https://{$shopDomain}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.api_key'),
            'client_secret' => config('services.shopify.api_secret'),
            'code' => $code,
        ]);

        Log::info('Respuesta de Shopify', [
            'client_id' => config('services.shopify.api_key'),
            'client_secret' => config('services.shopify.api_secret'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error('Error al obtener el token de acceso de Shopify');
            return redirect()->to("http://{$subdomain}.app-localhost.test/admin/{$shopDomain}/shopify-settings")
                ->withErrors(['auth' => 'No se pudo obtener el token de acceso.']);
        }

        $data = $response->json();
        $accessToken = $data['access_token'];

        // Buscar o crear el shop
        $shop = Shop::where('shopify_domain', 'like', $subdomainTenant . '%')->first();

        //dd($subdomainTenant, $shop);

        Log::info('Shop encontrado', [
            'shop' => $shop,
        ]);

        if ($shop) {

            $tenant = $shop->tenant_id;

            Log::info('Shop encontrado', [
                'id' => $shop->id,
                'shopify_domain' => $shopDomain,
                'access_token' => $accessToken,
            ]);

            tenancy()->initialize($tenant);

            // Inicializar el tenant existente
            $shop->update(['access_token' => $accessToken, 'shopify_domain' => $shopDomain]);

        } else {

            Log::info('Shop creado',[
                'tenant_id' => $subdomain,
                'shopify_domain' => $shopDomain,
                'access_token' => $accessToken,
            ]);

            $shop = Shop::create([
                'tenant_id' => $subdomain,
                'shopify_domain' => $shopDomain,
                'access_token' => $accessToken,
            ]);

        }

        Log::info('Antes de mandar webhhok');

        $tenant_id = $shop->tenant_id;

        Artisan::call('shopify:register-webhooks', ['--shop' => $shopDomain]);

        Log::info('Ruta generada', [
            'return'=> "http://{$subdomain}.app-localhost.test/admin/{$tenant_id}/shopify-settings",
        ]);

        return redirect()->to("http://{$subdomain}.app-localhost.test/admin/{$tenant_id}/shopify-settings")
            ->with('success', 'Tienda Shopify conectada exitosamente.');
    }
}

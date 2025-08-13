<?php

namespace App\Filament\Pages;

use App\Jobs\SyncAbandonedCarts;
use Filament\Pages\Page;
use Filament\Forms;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Filament\Notifications\Notification;

class ShopifySettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static string $view = 'filament.pages.shopify-settings';
    protected static ?string $navigationLabel = 'Configuración de Shopify';

    public $shopDomain;
    public $hasShop; // Define la propiedad pública
    public $subdomain;

    public function mount(): void
    {
        $tenant = tenancy()->tenant;
        if (!$tenant) {
            Log::error('No se encontró un tenant al inicializar ShopifySettings.');
            $this->hasShop = false;
            return;
        }

        Log::info('Tenant inicializado: ' . $tenant->id);
        $this->hasShop = Shop::where('tenant_id', $tenant->id)->exists();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('shopDomain')
                ->label('Dominio de la Tienda Shopify')
                ->placeholder('ejemplo.myshopify.com')
                ->required()
                ->disabled($this->hasShop)
                ->helperText($this->hasShop ? 'Ya tienes una tienda conectada.' : 'Ingresa el dominio de tu tienda Shopify.'),
        ];
    }

    public function submit()
    {
        if ($this->hasShop) {
            $this->notify('error', 'Ya tienes una tienda conectada.');
            return;
        }

        $this->validate([
            'shopDomain' => ['required', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/'],
        ]);

        $host = request()->getHost();
        $parts = explode('.', $host);
        $this->subdomain = count($parts) > 2 ? $parts[0] : null; // null si no hay subdominio

        session(['tenant_subdomain' => $this->subdomain]);

        return redirect()->route('shopify.auth', ['shop' => $this->shopDomain]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('syncAbandonedCarts')
                ->label('Sincronizar carritos abandonados')
                ->action(function () {
                    $tenant = Auth::user()->tenant;
                    $shop = \App\Models\Shop::where('tenant_id', $tenant->id)->first();

                    if (!$shop) {
                        Notification::make()
                            ->title('No se encontró la tienda')
                            ->danger()
                            ->send();
                        return;
                    }

                    tenancy()->initialize($tenant->id);
                    \App\Jobs\SyncAbandonedCarts::dispatch($shop);

                    Notification::make()
                        ->title('Sincronización iniciada')
                        ->success()
                        ->send();
                }),
        ];
    }
}

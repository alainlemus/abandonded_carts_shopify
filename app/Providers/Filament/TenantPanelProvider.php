<?php

namespace App\Providers\Filament;

use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconCache;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tenant')
            ->path('admin')
            ->tenant(Tenant::class) // Habilita multi-tenancy con el modelo Tenant
            ->tenantDomain('{tenant}.app-localhost.test') // Define el formato del subdominio
            ->login() // Habilita la página de login
            ->registration() // Opcional: habilita el registro de usuarios
            ->passwordReset() // Opcional: habilita el restablecimiento de contraseñas
            ->emailVerification() // Opcional: habilita la verificación de correo
            ->profile() // Opcional: habilita la edición de perfil de usuario
            ->middleware([
                'web',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                //DisableBladeIconCache::class,
                DispatchServingFilamentEvent::class,
                InitializeTenancyBySubdomain::class, // Middleware para identificar tenant por subdominio
                //PreventAccessFromCentralDomains::class, // Evita acceso desde dominios centrales
            ])
            ->pages([
                //\App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\ShopifySettings::class,
            ])
            ->widgets([
                //\App\Filament\Widgets\StatsOverviewWidget::class,
            ])
            ->authMiddleware([
                Authenticate::class, // Middleware de autenticación de Filament
                //InitializeTenancyBySubdomain::class,
                //PreventAccessFromCentralDomains::class,
            ])
            ->viteTheme('resources/css/filament/tenant/theme.css') // Tema personalizado (ajusta según tu configuración)
            ->navigationItems([
                MenuItem::make()
                    ->label('Volver al sitio principal')
                    ->url('http://app-localhost.test')
                    ->icon('heroicon-o-home')
                    ->visible(fn () => true),
            ]);
    }
}

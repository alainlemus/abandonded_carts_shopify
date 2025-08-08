<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $abandonedCartCount = tenancy()->initialized ? AbandonedCart::count() : 0;
        $recoveredCartCount = tenancy()->initialized ? AbandonedCart::where('status', 'recovered')->count() : 0;
        $messagesSentCount = tenancy()->initialized ? WhatsAppMessage::where('status', 'sent')->count() : 0;

        return [
            Stat::make('Carritos Abandonados', $abandonedCartCount)
                ->description('Total de carritos detectados')
                ->descriptionIcon('heroicon-m-shopping-cart'),
            Stat::make('Mensajes Enviados', $messagesSentCount)
                ->description('Mensajes enviados exitosamente')
                ->descriptionIcon('heroicon-m-envelope'),
            Stat::make('Tasa de Recuperación', number_format(($recoveredCartCount / ($abandonedCartCount ?: 1)) * 100, 2) . '%')
                ->description('Carritos recuperados')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
        ];
    }
}

<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = ['id', 'name', 'data'];

    public function getTenantIdentifier()
    {
        return $this->shopify_domain;
    }

    public function domains()
    {
        return $this->hasMany(\Stancl\Tenancy\Database\Models\Domain::class, 'tenant_id', 'id');
    }

    public function shops()
    {
        return $this->hasMany(Shop::class, 'tenant_id', 'id');
    }

    public function abandonedCarts()
    {
        return $this->hasMany(AbandonedCart::class);
    }

    public function whatsappMessages()
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function getFilamentName(): string
    {
        Log::info('Tenant name', ['data' => $this->data, 'id' => $this->id]);

        return (string) ($this->name ?? $this->id ?? 'Tenant sin nombre');

    }

    public function suscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id', 'id');
    }

}

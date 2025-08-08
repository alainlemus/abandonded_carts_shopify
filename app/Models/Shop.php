<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $table = 'shops';

    protected $fillable = [
        'tenant_id',
        'shopify_domain',
        'access_token',
        'name',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbandonedCart extends Model
{
    protected $table = 'abandoned_carts';

    protected $fillable = ['shop_id', 'shopify_cart_id', 'customer_email', 'total_price', 'status'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function whatsappMessages()
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function discount()
    {
        return $this->hasOne(Discount::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}

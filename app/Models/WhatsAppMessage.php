<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = ['abandoned_cart_id', 'message_id', 'status', 'sent_at'];

    public function abandonedCart()
    {
        return $this->belongsTo(AbandonedCart::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discounts';

    protected $fillable = ['abandoned_cart_id', 'shop_id', 'code', 'amount'];

    public function abandonedCart()
    {
        return $this->belongsTo(AbandonedCart::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}

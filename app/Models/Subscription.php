<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'stripe_subscription_id', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

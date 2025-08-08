<?php
namespace App\Services;

use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Customer;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCustomer($user)
    {
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
        ]);

        return $customer->id;
    }

    public function createSubscription($user, $priceId)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        // Asegúrate de que el usuario tenga un Stripe customer ID
        if (!$user->stripe_id) {
            $customer = \Stripe\Customer::create([
                'email' => $user->email,
                'name' => $user->name,
            ]);
            $user->stripe_id = $customer->id;
            $user->save();
        }

        $subscription = \Stripe\Subscription::create([
            'customer' => $user->stripe_id,
            'items' => [['price' => $priceId]],
        ]);

        return $subscription->id;
    }
}
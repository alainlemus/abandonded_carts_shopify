<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\WhatsAppMessage;
use App\Models\Discount;

class WhatsAppService
{
    public function sendMessage($cart, $discountCode = null)
    {
        $phone = $this->getCustomerPhone($cart->customer_email);
        if (!$phone) {
            return null;
        }

        $template = config('services.whatsapp.template_name');
        $message = $discountCode
            ? "¡Recupera tu carrito! Usa el código {$discountCode} para un 10% de descuento."
            : "¡Recupera tu carrito! Completa tu compra ahora.";

        $response = Http::withToken(config('services.whatsapp.api_token'))
            ->post("https://graph.facebook.com/v17.0/" . config('services.whatsapp.phone_number_id') . "/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => 'es'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $message],
                            ],
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return WhatsAppMessage::create([
                'abandoned_cart_id' => $cart->id,
                'message_id' => $response->json()['messages'][0]['id'],
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return null;
    }

    protected function getCustomerPhone($email)
    {
        // Implementa lógica para obtener el número de teléfono del cliente, por ejemplo, desde Shopify
        // Esto es un ejemplo; ajusta según tu integración
        return '1234567890'; // Reemplaza con la lógica real
    }
}
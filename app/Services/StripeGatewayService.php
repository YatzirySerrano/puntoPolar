<?php

namespace App\Services;

use Stripe\StripeClient;

class StripeGatewayService
{
    public function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    public function crearIntento(float $amount, string $currency = 'mxn', array $metadata = [], array $lineItems = []): array
    {
        $intent = $this->client()->paymentIntents->create([
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
            'line_items' => $lineItems,
        ];
    }
}

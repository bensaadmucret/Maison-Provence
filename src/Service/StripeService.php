<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StripeService
{
    private string $secretKey;
    private string $publicKey;
    private string $webhookSecret;

    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] string $secretKey,
        #[Autowire('%env(STRIPE_PUBLIC_KEY)%')] string $publicKey,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET)%')] string $webhookSecret,
    ) {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
        $this->webhookSecret = $webhookSecret;

        Stripe::setApiKey($this->secretKey);
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        $total = $order->getTotal();
        if (null === $total) {
            throw new \InvalidArgumentException('Order total cannot be null');
        }

        return PaymentIntent::create([
            'amount' => $this->formatAmount($total),
            'currency' => 'eur',
            'metadata' => [
                'order_id' => $order->getId(),
            ],
        ]);
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    public function handleWebhook(string $payload, string $sigHeader): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );

            return [
                'type' => $event->type,
                'data' => $event->data->toArray(),
            ];
        } catch (\UnexpectedValueException $e) {
            throw new \Exception('Invalid payload');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new \Exception('Invalid signature');
        }
    }

    private function formatAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}

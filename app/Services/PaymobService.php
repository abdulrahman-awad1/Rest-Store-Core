<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class PaymobService
{
    protected string $baseUrl = 'https://accept.paymob.com/api';

    public function resolveIntegration(string $method): int
    {
        return match ($method) {
            'card'   => config('services.paymob.integration_card'),
            'wallet' => config('services.paymob.integration_wallet'),
            'fawry'  => config('services.paymob.integration_fawry'),
            default  => throw new \InvalidArgumentException('Invalid payment method'),
        };
    }

    public function authenticate(): string
    {
        $response = Http::post($this->baseUrl . '/auth/tokens', [
            'api_key' => config('services.paymob.api_key'),
        ])->throw();

        return $response->json('token');
    }

    public function createOrder(string $token, Order $order): string
    {
        $response = Http::post($this->baseUrl . '/ecommerce/orders', [
            'auth_token'      => $token,
            'delivery_needed'=> false,
            'amount_cents'    => $this->toCents($order->total),
            'currency'        => 'EGP',
            'items'           => [],
        ])->throw();

        return (string) $response->json('id');
    }

    public function generatePaymentKey(
        string $token,
        string $orderId,
        Order $order,
        array $billingData,
        int $integrationId
    ): string {
        $response = Http::post($this->baseUrl . '/acceptance/payment_keys', [
            'auth_token'    => $token,
            'amount_cents'  => $this->toCents($order->total),
            'expiration'    => 3600,
            'order_id'      => $orderId,
            'billing_data'  => $billingData,
            'currency'      => 'EGP',
            'integration_id'=> $integrationId,
            'redirect_url'  => route('redirect'),
        ])->throw();

        return $response->json('token');
    }

    public function buildIframeUrl(string $paymentToken, string $method): string
    {
        $iframeId = match ($method) {
            'card'   => config('services.paymob.iframe_id_card'),
            'wallet' => config('services.paymob.iframe_id_wallet'),
            default  => config('services.paymob.iframe_id_card'),
        };

        return "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}";
    }

    public function payWallet(string $paymentKey, string $phone): array
    {
        return Http::post($this->baseUrl . '/acceptance/payments/pay', [
            'source' => [
                'identifier' => $phone,
                'subtype'    => 'WALLET',
            ],
            'payment_token' => $paymentKey,
        ])->throw()->json();
    }

    public function payFawry(string $paymentKey, string $phone): array
    {
        return Http::post($this->baseUrl . '/acceptance/payments/pay', [
            'source' => [
                'identifier' => $phone,
                'subtype'    => 'AGGREGATOR',
            ],
            'payment_token' => $paymentKey,
        ])->throw()->json();
    }

    public function dispatchPayment(string $paymentKey, string $method, Order $order): array
    {
        return match ($method) {
            'wallet' => [
                'wallet' => $this->payWallet($paymentKey, $order->address->phone),
            ],
            'fawry' => [
                'fawry' => $this->payFawry($paymentKey, $order->address->phone),
            ],
            default => [
                'url' => $this->buildIframeUrl($paymentKey, $method),
            ],
        };
    }

    public function buildBillingData(Order $order): array
    {
        return [
            'apartment'     => $order->address->apartment ?? 'NA',
            'email'         => $order->user->email ?? 'guest@mail.com',
            'floor'         => $order->address->floor ?? 'NA',
            'street'        => $order->address->street ?? 'NA',
            'building'        => $order->address->street ?? 'NA',
            'city'          => $order->address->city ?? 'Cairo',
            'country'       => 'EG',
            'first_name'    => $order->user->name ?? 'Guest',
            'last_name'    => $order->user->name ?? 'Guest',
            'phone_number'  => $order->address->phone ?? '+201000000000',
        ];
    }

    public function verifyHmac(array $data): bool
    {
        $fields = [
            'amount_cents','created_at','currency','error_occured',
            'has_parent_transaction','id','integration_id','is_3d_secure',
            'is_auth','is_capture','is_refunded','is_standalone_payment',
            'is_voided','order','owner','pending','source_data_pan',
            'source_data_sub_type','source_data_type','success'
        ];

        $message = collect($fields)
            ->map(fn ($f) => Arr::get($data, $f, ''))
            ->implode('');

        $calculated = hash_hmac(
            'sha512',
            $message,
            config('services.paymob.hmac_secret')
        );

        return hash_equals($calculated, $data['hmac'] ?? '');
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}

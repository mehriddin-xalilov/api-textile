<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

/** Mijozni to'lov sahifasiga yo'naltirish havolalari. */
final class PaymentLinks
{
    public function for(Order $order, PaymentMethod $method): string
    {
        $amount = (int) round((float) $order->total); // so'm
        $return = config('payments.return_url').'?order='.$order->id;

        return match ($method) {
            PaymentMethod::Payme => $this->payme($order, $amount, $return),
            PaymentMethod::Click => $this->click($order, $amount, $return),
            PaymentMethod::Uzum => $this->uzum($order, $amount, $return),
            PaymentMethod::Cash => throw ValidationException::withMessages(['payment_method' => "Naqd to'lov uchun havola yo'q."]),
        };
    }

    private function payme(Order $order, int $amount, string $return): string
    {
        $merchant = config('payments.payme.merchant_id') ?: 'TEST_MERCHANT';
        $params = "m={$merchant};ac.order_id={$order->id};a=".($amount * 100).";c={$return}";

        return rtrim(config('payments.payme.checkout_url'), '/').'/'.base64_encode($params);
    }

    private function click(Order $order, int $amount, string $return): string
    {
        return config('payments.click.checkout_url').'?'.http_build_query([
            'service_id' => config('payments.click.service_id') ?: '0',
            'merchant_id' => config('payments.click.merchant_id') ?: '0',
            'amount' => number_format($amount, 2, '.', ''),
            'transaction_param' => $order->id,
            'return_url' => $return,
        ]);
    }

    private function uzum(Order $order, int $amount, string $return): string
    {
        return config('payments.uzum.checkout_url').'?'.http_build_query([
            'serviceId' => config('payments.uzum.service_id') ?: '0',
            'order_id' => $order->id,
            'amount' => $amount * 100,
            'redirectUrl' => $return,
        ]);
    }
}

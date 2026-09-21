<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Click SHOP-API: action=0 prepare, action=1 complete. Imzo: md5(click_trans_id+service_id+secret+merchant_trans_id[+merchant_prepare_id]+amount+action+sign_time)
 * Xato kodlari: -1 imzo, -2 summa, -3 amal, -4 allaqachon to'langan, -5 buyurtma yo'q, -6 tranzaksiya yo'q, -9 bekor qilingan.
 */
final class ClickShopService
{
    public function handle(array $p): array
    {
        $action = (int) ($p['action'] ?? -1);
        $prepareId = $action === 1 ? ($p['merchant_prepare_id'] ?? '') : '';
        $sign = md5(($p['click_trans_id'] ?? '').($p['service_id'] ?? '').config('payments.click.secret_key').($p['merchant_trans_id'] ?? '').$prepareId.($p['amount'] ?? '').$action.($p['sign_time'] ?? ''));
        if ($sign !== ($p['sign_string'] ?? '')) {
            return $this->result($p, -1, 'SIGN CHECK FAILED');
        }

        $order = Order::query()->find($p['merchant_trans_id'] ?? 0);
        if (! $order) {
            return $this->result($p, -5, 'Order not found');
        }
        if (abs((float) $p['amount'] - (float) $order->total) > 0.01) {
            return $this->result($p, -2, 'Incorrect amount');
        }
        if ($order->payment_status === PaymentStatus::Paid) {
            return $this->result($p, -4, 'Already paid');
        }
        if ($order->status->value === 'cancelled') {
            return $this->result($p, -9, 'Order cancelled');
        }

        if ($action === 0) {
            $tx = PaymentTransaction::query()->firstOrCreate(
                ['provider' => 'click', 'provider_transaction_id' => (string) $p['click_trans_id']],
                ['order_id' => $order->id, 'amount' => $p['amount'], 'state' => PaymentTransaction::STATE_CREATED, 'payload' => $p],
            );

            return $this->result($p, 0, 'Success', $tx->id);
        }

        if ($action === 1) {
            $tx = PaymentTransaction::query()->where('provider', 'click')->find($p['merchant_prepare_id'] ?? 0);
            if (! $tx) {
                return $this->result($p, -6, 'Transaction not found');
            }
            if ((int) ($p['error'] ?? 0) < 0) {
                $tx->update(['state' => PaymentTransaction::STATE_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => (int) $p['error']]);

                return $this->result($p, -9, 'Transaction cancelled', $tx->id);
            }
            if ($tx->state !== PaymentTransaction::STATE_PERFORMED) {
                DB::transaction(function () use ($tx, $order) {
                    $tx->update(['state' => PaymentTransaction::STATE_PERFORMED, 'performed_at' => now()]);
                    $order->update(['payment_status' => PaymentStatus::Paid, 'payment_method' => 'click']);
                });
            }

            return $this->result($p, 0, 'Success', $tx->id, $tx->id);
        }

        return $this->result($p, -3, 'Action not found');
    }

    private function result(array $p, int $code, string $note, ?int $prepareId = null, ?int $confirmId = null): array
    {
        return array_filter([
            'click_trans_id' => $p['click_trans_id'] ?? null, 'merchant_trans_id' => $p['merchant_trans_id'] ?? null,
            'merchant_prepare_id' => $prepareId, 'merchant_confirm_id' => $confirmId, 'error' => $code, 'error_note' => $note,
        ], fn ($v) => $v !== null);
    }
}

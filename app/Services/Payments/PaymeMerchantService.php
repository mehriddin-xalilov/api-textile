<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Payme Merchant API (JSON-RPC 2.0). Hujjat: developer.help.paycom.uz
 * Xato kodlari: -31001 summa, -31003 tranzaksiya yo'q, -31008 amal bajarib bo'lmaydi, -31050..-31099 buyurtma/hisob.
 */
final class PaymeMerchantService
{
    private const STATE_CREATED = 1;

    private const STATE_PERFORMED = 2;

    private const STATE_CANCELLED = -1;

    private const STATE_CANCELLED_AFTER_PERFORM = -2;

    public function handle(string $method, array $params): array
    {
        return match ($method) {
            'CheckPerformTransaction' => $this->checkPerform($params),
            'CreateTransaction' => $this->create($params),
            'PerformTransaction' => $this->perform($params),
            'CancelTransaction' => $this->cancel($params),
            'CheckTransaction' => $this->check($params),
            'GetStatement' => $this->statement($params),
            default => $this->error(-32601, 'Method not found'),
        };
    }

    private function order(array $params): Order|array
    {
        $order = Order::query()->find($params['account']['order_id'] ?? 0);
        if (! $order) {
            return $this->error(-31050, 'Buyurtma topilmadi', 'order_id');
        }
        if ($order->payment_status === PaymentStatus::Paid) {
            return $this->error(-31051, "Buyurtma allaqachon to'langan", 'order_id');
        }
        if ($order->status->value === 'cancelled') {
            return $this->error(-31052, 'Buyurtma bekor qilingan', 'order_id');
        }
        if ((int) $params['amount'] !== (int) round((float) $order->total * 100)) {
            return $this->error(-31001, "Summa noto'g'ri");
        }

        return $order;
    }

    private function checkPerform(array $params): array
    {
        $order = $this->order($params);

        return $order instanceof Order ? ['result' => ['allow' => true]] : $order;
    }

    private function create(array $params): array
    {
        $existing = PaymentTransaction::query()->where('provider', 'payme')->where('provider_transaction_id', $params['id'])->first();
        if ($existing) {
            if ($existing->state !== PaymentTransaction::STATE_CREATED) {
                return $this->error(-31008, 'Tranzaksiya holati mos emas');
            }
            if ($this->expired($existing)) {
                $existing->update(['state' => PaymentTransaction::STATE_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => 4]);

                return $this->error(-31008, 'Tranzaksiya muddati tugagan');
            }

            return $this->createResult($existing);
        }

        $order = $this->order($params);
        if (! $order instanceof Order) {
            return $order;
        }
        // Bitta buyurtmaga bitta faol tranzaksiya
        if (PaymentTransaction::query()->where('order_id', $order->id)->where('provider', 'payme')->where('state', PaymentTransaction::STATE_CREATED)->exists()) {
            return $this->error(-31099, 'Buyurtma uchun tranzaksiya allaqachon mavjud', 'order_id');
        }

        $tx = PaymentTransaction::query()->create([
            'order_id' => $order->id, 'provider' => 'payme', 'provider_transaction_id' => $params['id'],
            'amount' => $params['amount'] / 100, 'state' => PaymentTransaction::STATE_CREATED,
            'payload' => ['time' => $params['time']],
        ]);

        return $this->createResult($tx);
    }

    private function createResult(PaymentTransaction $tx): array
    {
        return ['result' => ['create_time' => $this->ms($tx->created_at), 'transaction' => (string) $tx->id, 'state' => self::STATE_CREATED]];
    }

    private function perform(array $params): array
    {
        $tx = $this->tx($params['id']);
        if (! $tx) {
            return $this->error(-31003, 'Tranzaksiya topilmadi');
        }
        if ($tx->state === PaymentTransaction::STATE_PERFORMED) {
            return $this->performResult($tx);
        }
        if ($tx->state !== PaymentTransaction::STATE_CREATED) {
            return $this->error(-31008, 'Tranzaksiya bekor qilingan');
        }
        if ($this->expired($tx)) {
            $tx->update(['state' => PaymentTransaction::STATE_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => 4]);

            return $this->error(-31008, 'Tranzaksiya muddati tugagan');
        }

        DB::transaction(function () use ($tx) {
            $tx->update(['state' => PaymentTransaction::STATE_PERFORMED, 'performed_at' => now()]);
            $tx->order()->update(['payment_status' => PaymentStatus::Paid, 'payment_method' => 'payme']);
        });

        return $this->performResult($tx->fresh());
    }

    private function performResult(PaymentTransaction $tx): array
    {
        return ['result' => ['transaction' => (string) $tx->id, 'perform_time' => $this->ms($tx->performed_at), 'state' => self::STATE_PERFORMED]];
    }

    private function cancel(array $params): array
    {
        $tx = $this->tx($params['id']);
        if (! $tx) {
            return $this->error(-31003, 'Tranzaksiya topilmadi');
        }
        if ($tx->state === PaymentTransaction::STATE_CANCELLED) {
            return $this->cancelResult($tx);
        }
        $wasPerformed = $tx->state === PaymentTransaction::STATE_PERFORMED;
        // Jo'natilgan buyurtmani qaytarib bo'lmaydi
        if ($wasPerformed && in_array($tx->order->status->value, ['shipped', 'delivered'], true)) {
            return $this->error(-31007, "Buyurtma bajarilgan, to'lovni qaytarib bo'lmaydi");
        }

        DB::transaction(function () use ($tx, $params, $wasPerformed) {
            $tx->update([
                'state' => PaymentTransaction::STATE_CANCELLED, 'cancelled_at' => now(), 'cancel_reason' => $params['reason'] ?? null,
                'payload' => ($tx->payload ?? []) + ['after_perform' => $wasPerformed],
            ]);
            if ($wasPerformed) {
                $tx->order()->update(['payment_status' => PaymentStatus::Refunded]);
            }
        });

        return $this->cancelResult($tx->fresh());
    }

    private function cancelResult(PaymentTransaction $tx): array
    {
        $state = ($tx->payload['after_perform'] ?? false) ? self::STATE_CANCELLED_AFTER_PERFORM : self::STATE_CANCELLED;

        return ['result' => ['transaction' => (string) $tx->id, 'cancel_time' => $this->ms($tx->cancelled_at), 'state' => $state]];
    }

    private function check(array $params): array
    {
        $tx = $this->tx($params['id']);
        if (! $tx) {
            return $this->error(-31003, 'Tranzaksiya topilmadi');
        }

        return ['result' => $this->row($tx)];
    }

    private function statement(array $params): array
    {
        $rows = PaymentTransaction::query()->where('provider', 'payme')
            ->whereBetween('created_at', [
                Carbon::createFromTimestampMs((int) $params['from']), Carbon::createFromTimestampMs((int) $params['to']),
            ])->get()->map(fn ($tx) => $this->row($tx) + ['id' => $tx->provider_transaction_id, 'time' => $tx->payload['time'] ?? $this->ms($tx->created_at), 'amount' => (int) round($tx->amount * 100), 'account' => ['order_id' => $tx->order_id]]);

        return ['result' => ['transactions' => $rows->values()->all()]];
    }

    private function row(PaymentTransaction $tx): array
    {
        $state = match ($tx->state) {
            PaymentTransaction::STATE_PERFORMED => self::STATE_PERFORMED,
            PaymentTransaction::STATE_CANCELLED => ($tx->payload['after_perform'] ?? false) ? self::STATE_CANCELLED_AFTER_PERFORM : self::STATE_CANCELLED,
            default => self::STATE_CREATED,
        };

        return [
            'create_time' => $this->ms($tx->created_at), 'perform_time' => $this->ms($tx->performed_at), 'cancel_time' => $this->ms($tx->cancelled_at),
            'transaction' => (string) $tx->id, 'state' => $state, 'reason' => $tx->cancel_reason,
        ];
    }

    private function tx(string $id): ?PaymentTransaction
    {
        return PaymentTransaction::query()->with('order')->where('provider', 'payme')->where('provider_transaction_id', $id)->first();
    }

    private function expired(PaymentTransaction $tx): bool
    {
        return $tx->created_at->diffInMilliseconds(now()) > config('payments.payme.timeout_ms');
    }

    private function ms($dt): int
    {
        return $dt ? (int) $dt->getPreciseTimestamp(3) : 0;
    }

    private function error(int $code, string $message, ?string $data = null): array
    {
        return ['error' => array_filter(['code' => $code, 'message' => ['uz' => $message, 'ru' => $message, 'en' => $message], 'data' => $data])];
    }
}

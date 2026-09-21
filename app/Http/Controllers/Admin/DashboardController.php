<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $byStatus = Order::query()
            ->select('status', DB::raw('count(*) as count'), DB::raw('coalesce(sum(total), 0) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($r) => $r->status->value);

        $revenueByDay = Order::query()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->whereNotIn('status', [OrderStatus::Cancelled->value])
            ->selectRaw('date(created_at) as day, count(*) as orders, coalesce(sum(total), 0) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $stock = ProductVariant::query()
            ->selectRaw('coalesce(sum(quantity), 0) as quantity, coalesce(sum(reserved), 0) as reserved')
            ->first();

        return ApiResponse::item([
            'orders' => [
                'total' => $byStatus->sum('count'),
                'by_status' => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [
                    $s->value => ['count' => (int) ($byStatus[$s->value]->count ?? 0), 'total' => (string) ($byStatus[$s->value]->total ?? 0), 'label' => $s->label()],
                ]),
                'today' => Order::query()->whereDate('created_at', today())->count(),
            ],
            'revenue' => [
                'total' => (string) Order::query()->where('status', OrderStatus::Delivered)->sum('total'),
                'last_30_days' => $revenueByDay,
            ],
            'stock' => [
                'quantity' => (int) $stock->quantity,
                'reserved' => (int) $stock->reserved,
                'available' => (int) $stock->quantity - (int) $stock->reserved,
                'low_stock_variants' => ProductVariant::query()->whereRaw('quantity - reserved <= 5')->count(),
            ],
            'customers' => [
                'total' => User::query()->role('customer')->count(),
                'new_this_month' => User::query()->role('customer')->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'designs' => ['total' => Design::query()->count()],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\ChangeOrderStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Orders\ChangeOrderStatusRequest;
use App\Http\Requests\Admin\Orders\UpdateOrderPaymentRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = QueryBuilder::for(Order::query()->with('user'))
            ->allowedIncludes('items')
            ->allowedFilters(
                AllowedFilter::partial('search', 'number'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('payment_status'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::callback('date_from', fn ($q, $v) => $q->whereDate('created_at', '>=', $v)),
                AllowedFilter::callback('date_to', fn ($q, $v) => $q->whereDate('created_at', '<=', $v)))
            ->allowedSorts('id', 'total', 'created_at', 'status')
            ->defaultSort('-id')
            ->withCount('items')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($orders, OrderResource::class);
    }

    public function show(Order $order): JsonResponse
    {
        return ApiResponse::item(new OrderResource($order->load([
            'user', 'items.printFile', 'items.design.preview', 'items.design.product.printAreas', 'histories.changedBy', 'transactions',
        ])));
    }

    public function changeStatus(ChangeOrderStatusRequest $request, Order $order, ChangeOrderStatus $action): JsonResponse
    {
        $action->handle($order, OrderStatus::from($request->status), $request->comment, $request->user()->id);

        return $this->show($order->fresh());
    }

    public function updatePayment(UpdateOrderPaymentRequest $request, Order $order): JsonResponse
    {
        $order->update($request->validated());

        return $this->show($order);
    }
}

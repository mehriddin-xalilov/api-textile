<?php

namespace App\Http\Controllers\Client;

use App\Actions\Orders\ChangeOrderStatus;
use App\Actions\Orders\CreateOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Orders\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Ro'yxatda kichik rasmlar ko'rsatiladi: dizayn preview yoki tayyor mahsulot fotosi
        $orders = $request->user()->orders()
            ->withCount('items')
            ->with(['items.design.preview', 'items.readyProduct.images.file'])
            ->latest('id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($orders, OrderResource::class);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return ApiResponse::item(new OrderResource($order->load(['items.design.preview', 'histories'])));
    }

    public function store(StoreOrderRequest $request, CreateOrder $action): JsonResponse
    {
        $order = $action->handle(
            $request->user(),
            $request->input('items'),
            $request->only(['recipient_name', 'recipient_phone', 'delivery_address', 'note']),
            $request->input('payment_method'),
        );

        return ApiResponse::created(new OrderResource($order->load('items')));
    }

    /** Mijoz faqat "new" holatdagi buyurtmani bekor qila oladi. */
    public function cancel(Request $request, Order $order, ChangeOrderStatus $action): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($order->status === OrderStatus::New, 422, 'Faqat yangi buyurtmani bekor qilish mumkin.');

        $action->handle($order, OrderStatus::Cancelled, 'Mijoz bekor qildi', $request->user()->id);

        return $this->show($request, $order->fresh());
    }
}

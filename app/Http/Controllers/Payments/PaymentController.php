<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\ClickShopService;
use App\Services\Payments\PaymeMerchantService;
use App\Services\Payments\PaymentLinks;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /** GET /orders/{order}/pay-url?provider=payme|click|uzum — mijoz uchun to'lov havolasi. */
    public function payUrl(Request $request, Order $order, PaymentLinks $links): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $method = PaymentMethod::from($request->validate(['provider' => ['required', 'in:payme,click,uzum']])['provider']);
        $order->update(['payment_method' => $method]);

        return ApiResponse::item(['url' => $links->for($order, $method), 'provider' => $method->value]);
    }

    /** GET /orders/{order}/payment-status — natija sahifasi uchun. */
    public function status(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return ApiResponse::item(['number' => $order->number, 'payment_status' => $order->payment_status, 'payment_method' => $order->payment_method, 'total' => $order->total]);
    }

    /** POST /payments/payme — Payme Merchant API (Basic auth Paycom:{key}). */
    public function payme(Request $request, PaymeMerchantService $service): JsonResponse
    {
        $auth = $request->header('Authorization', '');
        $expected = ['Paycom:'.config('payments.payme.key'), 'Paycom:'.config('payments.payme.test_key')];
        $given = str_starts_with($auth, 'Basic ') ? base64_decode(substr($auth, 6)) : '';
        if (! $given || ! in_array($given, array_filter($expected, fn ($e) => $e !== 'Paycom:'), true)) {
            return response()->json(['jsonrpc' => '2.0', 'id' => $request->input('id'), 'error' => ['code' => -32504, 'message' => 'Insufficient privileges']]);
        }

        $result = $service->handle((string) $request->input('method'), (array) $request->input('params', []));

        return response()->json(['jsonrpc' => '2.0', 'id' => $request->input('id')] + $result);
    }

    /** POST /payments/click/prepare va /complete — Click SHOP-API. */
    public function click(Request $request, ClickShopService $service): JsonResponse
    {
        return response()->json($service->handle($request->all()));
    }
}

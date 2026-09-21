<?php

namespace App\Http\Controllers\Client;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Design;
use App\Models\Order;
use App\Models\Review;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** Sayt izohlari: o'qish ochiq (faqat tasdiqlangan), yozish — sotib olgan foydalanuvchiga. */
class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(Review::query()->with('user')->where('status', ReviewStatus::Approved))
            ->allowedFilters(
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('ready_product_id'),
                AllowedFilter::exact('design_id'),
                AllowedFilter::exact('rating'),
            )
            ->allowedSorts('id', 'rating')
            ->defaultSort('-id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, ReviewResource::class);
    }

    /** Mahsulot (yoki tayyor dizayn) reytingi: o'rtacha baho, izohlar soni va yulduzlar taqsimoti. */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['nullable', 'integer'],
            'ready_product_id' => ['nullable', 'integer'],
            'design_id' => ['nullable', 'integer'],
        ]);

        $query = Review::query()->where('status', ReviewStatus::Approved);
        if ($request->filled('ready_product_id')) {
            $query->where('ready_product_id', $request->integer('ready_product_id'));
        } elseif ($request->filled('design_id')) {
            $query->where('design_id', $request->integer('design_id'));
        } elseif ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        /** @var Collection<int, int> $rows */
        $rows = $query->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $count = (int) $rows->sum();
        $sum = $rows->reduce(fn (int $acc, int $c, int $r): int => $acc + $r * $c, 0);

        return ApiResponse::item([
            'count' => $count,
            'average' => $count ? round($sum / $count, 2) : null,
            'breakdown' => collect(range(5, 1))->mapWithKeys(fn (int $r) => [$r => (int) ($rows[$r] ?? 0)]),
        ]);
    }

    /** O'zining izohlari (profil / buyurtma sahifasi "baho berish" holatini bilish uchun). */
    public function mine(Request $request): JsonResponse
    {
        $items = Review::query()->with('product')->where('user_id', $request->user()->id)->orderByDesc('id')->paginate($this->perPage($request));

        return ApiResponse::paginated($items, ReviewResource::class);
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $readyProductId = isset($data['ready_product_id']) ? (int) $data['ready_product_id'] : null;
        $design = isset($data['design_id']) ? Design::query()->findOrFail($data['design_id']) : null;
        $productId = $readyProductId ? null : (int) ($design?->product_id ?? $data['product_id']);

        // Faqat sotib olgan odam baho beradi: bekor qilinmagan buyurtmada shu mahsulot bo'lishi kerak
        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->when(
                $readyProductId,
                fn ($q) => $q->whereHas('items', fn ($i) => $i->where('ready_product_id', $readyProductId)),
                fn ($q) => $q->whereHas('items.variant', fn ($i) => $i->where('product_id', $productId)),
            )
            ->orderByDesc('id')
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['product_id' => __('Izoh qoldirish uchun avval shu mahsulotni buyurtma qiling.')]);
        }

        $exists = Review::query()
            ->where('user_id', $user->id)
            ->when(
                $readyProductId,
                fn ($q) => $q->where('ready_product_id', $readyProductId),
                fn ($q) => $q->where('product_id', $productId)
                    ->when($design, fn ($w) => $w->where('design_id', $design->id), fn ($w) => $w->whereNull('design_id')),
            )
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['rating' => __('Siz bu mahsulotga allaqachon baho bergansiz.')]);
        }

        $review = Review::query()->create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'ready_product_id' => $readyProductId,
            'design_id' => $design?->id,
            'order_id' => $order->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => ReviewStatus::Pending,
        ]);

        return ApiResponse::created(new ReviewResource($review->load('user')));
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        abort_if($review->user_id !== $request->user()->id, 403);
        $review->delete();

        return ApiResponse::noContent();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\B2bLead;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Admin sarlavhasidagi bildirishnomalar: yangi buyurtmalar, kutayotgan sharhlar,
 * xat havolasini bosgan B2B lidlar, yangi mijozlar. Oxirgi 7 kun, vaqt bo'yicha.
 * "O'qilgan" holati brauzerda saqlanadi (id bo'yicha).
 */
class NotificationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $since = now()->subDays(7);
        $items = [];

        foreach (Order::query()->with('user')->where('status', OrderStatus::New)->where('created_at', '>=', $since)->latest()->limit(20)->get() as $o) {
            $items[] = [
                'id' => 'order-'.$o->id,
                'type' => 'order',
                'title' => 'Yangi buyurtma',
                'description' => sprintf('%s · %s so\'m · %s', $o->number, number_format((float) $o->total, 0, '.', ' '), $o->user?->full_name ?? '—'),
                'time' => $o->created_at,
                'link' => '/orders/view/'.$o->id,
            ];
        }

        foreach (Review::query()->with('user')->where('status', ReviewStatus::Pending)->where('created_at', '>=', $since)->latest()->limit(20)->get() as $r) {
            $items[] = [
                'id' => 'review-'.$r->id,
                'type' => 'review',
                'title' => 'Yangi sharh',
                'description' => sprintf('%s · %d/5 · %s', $r->user?->full_name ?? '—', $r->rating, mb_substr((string) $r->comment, 0, 60)),
                'time' => $r->created_at,
                'link' => '/reviews?status=pending',
            ];
        }

        foreach (B2bLead::query()->whereNotNull('last_click_at')->where('last_click_at', '>=', $since)->orderByDesc('last_click_at')->limit(20)->get() as $l) {
            $items[] = [
                'id' => 'lead-'.$l->id.'-'.$l->clicks,
                'type' => 'lead',
                'title' => 'B2B lid saytga kirdi',
                'description' => sprintf('%s · %d marta', $l->company ?: $l->email, $l->clicks),
                'time' => $l->last_click_at,
                'link' => '/b2b-leads',
            ];
        }

        foreach (User::query()->where('created_at', '>=', $since)->whereDoesntHave('roles')->latest()->limit(20)->get() as $u) {
            $items[] = [
                'id' => 'user-'.$u->id,
                'type' => 'user',
                'title' => $u->studio_approved_at ? 'Yangi mijoz' : 'Konstruktor ruxsatini kutmoqda',
                'description' => sprintf('%s · %s', $u->full_name, $u->phone_number),
                'time' => $u->created_at,
                'link' => '/users/view/'.$u->id,
            ];
        }

        usort($items, fn ($a, $b) => $b['time'] <=> $a['time']);

        return ApiResponse::item(array_slice($items, 0, 30));
    }
}

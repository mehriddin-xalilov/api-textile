<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\B2bLeadResource;
use App\Models\B2bLead;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/** B2B lidlar: kim xatni ochdi, kim saytga kirdi, kim ro'yxatdan o'tdi. */
class B2bLeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = QueryBuilder::for(B2bLead::query()->with('user'))
            ->allowedFilters(
                AllowedFilter::exact('campaign'),
                AllowedFilter::exact('segment'),
                AllowedFilter::callback('clicked', fn ($q, $v) => $v ? $q->whereNotNull('first_click_at') : $q->whereNull('first_click_at')),
                AllowedFilter::callback('opened', fn ($q, $v) => $v ? $q->whereNotNull('opened_at') : $q->whereNull('opened_at')),
                AllowedFilter::callback('search', fn ($q, $v) => $q->where(fn ($w) => $w
                    ->where('company', 'ilike', "%{$v}%")->orWhere('email', 'ilike', "%{$v}%")->orWhere('phone', 'ilike', "%{$v}%"))),
            )
            ->allowedSorts('id', 'company', 'clicks', 'first_click_at', 'last_click_at', 'opened_at', 'sent_at')
            ->defaultSort('-last_click_at', '-opened_at', '-id')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, B2bLeadResource::class);
    }

    public function stats(): JsonResponse
    {
        $q = B2bLead::query();

        return ApiResponse::item([
            'total' => (clone $q)->count(),
            'sent' => (clone $q)->whereNotNull('sent_at')->count(),
            'opened' => (clone $q)->whereNotNull('opened_at')->count(),
            'clicked' => (clone $q)->whereNotNull('first_click_at')->count(),
            'registered' => (clone $q)->whereNotNull('user_id')->count(),
        ]);
    }

    public function update(Request $request, B2bLead $lead): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $lead->update($data);

        return ApiResponse::item(new B2bLeadResource($lead->load('user')));
    }
}

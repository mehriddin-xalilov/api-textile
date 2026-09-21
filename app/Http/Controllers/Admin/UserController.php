<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = QueryBuilder::for(User::class)
            ->allowedIncludes('roles', 'avatar')
            ->allowedFilters(
                AllowedFilter::partial('search', 'first_name'),
                AllowedFilter::callback('q', fn ($q, $v) => $q->where(fn ($w) => $w
                    ->where('first_name', 'ilike', "%{$v}%")
                    ->orWhere('last_name', 'ilike', "%{$v}%")
                    ->orWhere('phone_number', 'like', "%{$v}%"))),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('role', fn ($q, $v) => $q->whereHas('roles', fn ($r) => $r->where('name', $v))))
            ->allowedSorts('id', 'first_name', 'created_at', 'last_login_at')
            ->defaultSort('-id')
            ->withCount(['orders', 'designs'])
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($users, UserResource::class);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::item(new UserResource($user->load(['roles.permissions', 'permissions', 'avatar'])->loadCount(['orders', 'designs'])));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::query()->create($request->safe()->except('role_ids'));
            $user->syncRoles($request->input('role_ids', []));

            return $user;
        });

        return ApiResponse::created(new UserResource($user->load('roles')));
    }

    public function update(StoreUserRequest $request, User $user): JsonResponse
    {
        DB::transaction(function () use ($request, $user) {
            $data = $request->safe()->except('role_ids');
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $user->update($data);

            if ($request->has('role_ids')) {
                $user->syncRoles($request->input('role_ids', []));
            }
        });

        return ApiResponse::item(new UserResource($user->load('roles')));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->is($user), 422, "O'zingizni o'chira olmaysiz.");
        $user->delete();

        return ApiResponse::noContent();
    }
}

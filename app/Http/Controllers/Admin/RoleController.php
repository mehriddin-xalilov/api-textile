<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\SyncRolePermissionsRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = QueryBuilder::for(Role::class)
            ->allowedIncludes('permissions')
            ->allowedFilters(AllowedFilter::partial('search', 'name_uz'), AllowedFilter::exact('status'))
            ->allowedSorts('id', 'name', 'name_uz')
            ->defaultSort('id')
            ->withCount('users')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($roles, RoleResource::class);
    }

    public function show(Role $role): JsonResponse
    {
        return ApiResponse::item(new RoleResource($role->load('permissions')->loadCount('users')));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::query()->create($request->validated() + ['guard_name' => 'api']);

        return ApiResponse::created(new RoleResource($role));
    }

    public function update(StoreRoleRequest $request, Role $role): JsonResponse
    {
        $role->update($request->validated());

        return ApiResponse::item(new RoleResource($role));
    }

    public function destroy(Role $role): JsonResponse
    {
        abort_if($role->name === 'super-admin', 422, "super-admin rolini o'chirib bo'lmaydi.");
        $role->delete();

        return ApiResponse::noContent();
    }

    /** GET /roles/{role}/permissions — rolga biriktirilgan ruxsatlar. */
    public function permissions(Role $role): JsonResponse
    {
        return ApiResponse::collection($role->permissions()->orderBy('name')->get(), PermissionResource::class);
    }

    /** POST /roles/{role}/permissions — to'liq sinxron. */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): JsonResponse
    {
        $role->syncPermissions($request->input('permission_ids', []));

        return ApiResponse::collection($role->permissions()->orderBy('name')->get(), PermissionResource::class);
    }
}

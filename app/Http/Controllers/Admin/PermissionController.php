<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Permissions\StorePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $permissions = QueryBuilder::for(Permission::class)
            ->allowedFilters(AllowedFilter::partial('search', 'name'), AllowedFilter::exact('status'))
            ->allowedSorts('id', 'name')
            ->defaultSort('name')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($permissions, PermissionResource::class);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::query()->create($request->validated() + ['guard_name' => 'api']);

        return ApiResponse::created(new PermissionResource($permission));
    }

    public function update(StorePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update($request->safe()->except('name'));

        return ApiResponse::item(new PermissionResource($permission));
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();

        return ApiResponse::noContent();
    }
}

<?php

namespace App\Http\Requests\Admin\Roles;

use App\Http\Requests\ApiFormRequest;

class SyncRolePermissionsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}

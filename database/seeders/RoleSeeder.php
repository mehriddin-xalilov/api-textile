<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $all = Permission::query()->where('guard_name', 'api')->pluck('name');

        $roles = [
            'super-admin' => [['Super admin', 'Супер админ', 'Super admin'], $all->all()],
            'manager' => [['Menejer', 'Менеджер', 'Manager'], $all->reject(fn ($p) => str_starts_with($p, 'roles.') || str_starts_with($p, 'permissions.') || str_starts_with($p, 'users.'))->all()],
            'warehouse' => [['Omborchi', 'Кладовщик', 'Warehouse'], $all->filter(fn ($p) => str_starts_with($p, 'inventory') || str_starts_with($p, 'products.') || $p === 'dashboard.view')->all()],
            'operator' => [['Operator', 'Оператор', 'Operator'], ['dashboard.view', 'orders.list', 'orders.view', 'orders.update', 'designs.list', 'designs.view']],
            'customer' => [['Mijoz', 'Клиент', 'Customer'], []],
        ];

        foreach ($roles as $name => [[$uz, $ru, $en], $permissions]) {
            $role = Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'api'],
                ['name_uz' => $uz, 'name_ru' => $ru, 'name_en' => $en],
            );
            $role->syncPermissions($permissions);
        }
    }
}

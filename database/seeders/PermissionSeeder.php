<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission nomlari: <resurs>.<amal>. Admin panel `useAccess('<resurs>')` shu nomlarni tekshiradi.
 * Yangi resurs qo'shsang — shu yerga ham qo'sh, keyin `php artisan db:seed --class=PermissionSeeder`.
 */
class PermissionSeeder extends Seeder
{
    public const GROUPS = [
        'dashboard' => ['view'],
        'users' => ['list', 'view', 'create', 'update', 'delete'],
        'roles' => ['list', 'view', 'create', 'update', 'delete'],
        'permissions' => ['list', 'create', 'update', 'delete'],
        'settings' => ['list', 'update'],
        'banners' => ['list', 'create', 'update', 'delete'],
        'pages' => ['list', 'view', 'create', 'update', 'delete'],
        'categories' => ['list', 'view', 'create', 'update', 'delete'],
        'colors' => ['list', 'create', 'update', 'delete'],
        'sizes' => ['list', 'create', 'update', 'delete'],
        'cliparts' => ['list', 'create', 'update', 'delete'],
        'phrases' => ['list', 'create', 'update', 'delete'],
        'garment-models' => ['list', 'view', 'create', 'update', 'delete'],
        'products' => ['list', 'view', 'create', 'update', 'delete'],
        'inventory' => ['list', 'adjust'],
        'inventory-batches' => ['list', 'view', 'create', 'update', 'delete', 'receive'],
        'designs' => ['list', 'view', 'update'],
        'orders' => ['list', 'view', 'update'],
        'ready-products' => ['list', 'view', 'create', 'update', 'delete'],
        'reviews' => ['list', 'update', 'delete'],
    ];

    private const LABELS = [
        'list' => ["Ro'yxat", 'Список', 'List'],
        'view' => ["Ko'rish", 'Просмотр', 'View'],
        'create' => ['Yaratish', 'Создание', 'Create'],
        'update' => ['Tahrirlash', 'Редактирование', 'Update'],
        'delete' => ["O'chirish", 'Удаление', 'Delete'],
        'adjust' => ['Tuzatish', 'Корректировка', 'Adjust'],
        'receive' => ['Qabul qilish', 'Приём', 'Receive'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GROUPS as $group => $actions) {
            foreach ($actions as $action) {
                [$uz, $ru, $en] = self::LABELS[$action];
                $title = ucfirst(str_replace('-', ' ', $group));

                Permission::query()->firstOrCreate(
                    ['name' => "{$group}.{$action}", 'guard_name' => 'api'],
                    ['name_uz' => "{$title}: {$uz}", 'name_ru' => "{$title}: {$ru}", 'name_en' => "{$title}: {$en}"],
                );
            }
        }
    }
}

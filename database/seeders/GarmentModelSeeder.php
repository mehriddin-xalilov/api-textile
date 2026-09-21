<?php

namespace Database\Seeders;

use App\Models\File;
use App\Models\GarmentModel;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/** Standart futbolka GLB (shirt-designer, MIT) — barcha mahsulotlarga default model. */
class GarmentModelSeeder extends Seeder
{
    public function run(): void
    {
        if (GarmentModel::query()->exists()) {
            return;
        }
        // Repodagi nusxa (deploy uchun), bo'lmasa web-3d/public dan
        $source = database_path('seeders/models/shirt_model.glb');
        if (! file_exists($source)) {
            $source = base_path('../web-3d/public/models/shirt_model.glb');
        }
        if (! file_exists($source)) {
            $this->command?->warn('shirt_model.glb topilmadi (web-3d/public/models).');

            return;
        }
        Storage::disk('public')->put('models/shirt_model.glb', file_get_contents($source));
        $file = File::query()->create([
            'disk' => 'public', 'path' => 'models/shirt_model.glb', 'original_name' => 'shirt_model.glb',
            'mime' => 'model/gltf-binary', 'size' => filesize($source),
        ]);
        $model = GarmentModel::query()->create([
            'name_uz' => 'Futbolka (standart)', 'name_ru' => 'Футболка (стандарт)', 'name_en' => 'T-shirt (default)',
            'file_id' => $file->id, 'author' => 'jericNuez/shirt-designer (MIT)',
            'zones' => [
                'front' => ['position' => [0, 0.04, 0.15], 'rotation' => [0, 0, 0], 'scale' => 0.26],
                'back' => ['position' => [0, 0.04, -0.15], 'rotation' => [0, 3.1416, 0], 'scale' => 0.26],
                'sleeve_left' => ['position' => [-0.23, 0.08, 0.02], 'rotation' => [0, -1.5708, 0], 'scale' => 0.14],
                'sleeve_right' => ['position' => [0.23, 0.08, 0.02], 'rotation' => [0, 1.5708, 0], 'scale' => 0.14],
            ],
        ]);
        Product::query()->whereNull('garment_model_id')->update(['garment_model_id' => $model->id]);
    }
}

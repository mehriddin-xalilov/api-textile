<?php

namespace Tests\Feature\Client;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DesignCanvasV2Test extends TestCase
{
    public function test_3d_engine_canvas_is_accepted_and_summarized(): void
    {
        $category = Category::query()->create(['name_uz' => 'Futbolka', 'slug' => 'tshirt']);
        $product = Product::query()->create(['category_id' => $category->id, 'name_uz' => 'Klassik', 'slug' => 'classic', 'base_price' => 100000]);
        $pc = $product->colors()->create(['color_id' => Color::query()->create(['name_uz' => 'Oq', 'hex' => '#FFFFFF'])->id]);
        $user = User::factory()->create();
        $user->assignRole('customer');
        Passport::actingAs($user, ['*'], 'api');

        $payload = [
            'product_id' => $product->id, 'product_color_id' => $pc->id,
            'canvas' => [
                'version' => 2, 'engine' => 'shirt-designer-3d',
                'colors' => ['body' => '#FFFFFF', 'collar' => '#FFFFFF', 'sleevesLeft' => '#FFFFFF', 'sleevesRight' => '#FFFFFF', 'isUnified' => true],
                'layers' => [
                    ['id' => 'a', 'type' => 'text', 'zone' => 'front', 'x' => 0.5, 'y' => 0.4, 'scale' => 1, 'rotation' => -4, 'text' => 'SALOM', 'fontFamily' => 'Anton', 'fontWeight' => '700', 'fontStyle' => 'italic', 'fillColor' => '#dc2626'],
                    ['id' => 'b', 'type' => 'shape', 'zone' => 'back', 'x' => 0.5, 'y' => 0.5, 'scale' => 0.8, 'shapeType' => 'star', 'fillColor' => '#fbbf24'],
                ],
            ],
        ];

        $this->postJson('/api/v1/designs', $payload)->assertCreated()
            ->assertJsonPath('data.engine', 'shirt-designer-3d')
            ->assertJsonPath('data.summary.0.side', 'front')
            ->assertJsonPath('data.summary.0.label', '"SALOM" — Anton 700 italic')
            ->assertJsonPath('data.summary.1.side', 'back');

        // data:URL rasm rad etiladi — avval /files ga yuklanishi shart
        $payload['canvas']['layers'] = [['id' => 'c', 'type' => 'image', 'zone' => 'front', 'x' => 0.5, 'y' => 0.5, 'scale' => 1, 'src' => 'data:image/png;base64,AAAA']];
        $this->postJson('/api/v1/designs', $payload)->assertUnprocessable()->assertJsonValidationErrors('canvas.layers.0.src');
    }
}

<?php

namespace Tests\Feature\Client;

use App\Models\Category;
use App\Models\Color;
use App\Models\PrintArea;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DesignCanvasTest extends TestCase
{
    private Product $product;

    private ProductColor $color;

    private PrintArea $area;

    protected function setUp(): void
    {
        parent::setUp();
        $category = Category::query()->create(['name_uz' => 'Futbolka', 'slug' => 'tshirt']);
        $this->product = Product::query()->create(['category_id' => $category->id, 'name_uz' => 'Klassik', 'slug' => 'classic', 'base_price' => 100000]);
        $this->color = $this->product->colors()->create(['color_id' => Color::query()->create(['name_uz' => 'Oq', 'hex' => '#FFFFFF'])->id]);
        $this->area = $this->product->printAreas()->create(['side' => 'front', 'name' => "Ko'krak", 'x' => 30, 'y' => 25, 'width' => 40, 'height' => 40, 'max_width_cm' => 20, 'max_height_cm' => 30]);

        $user = User::factory()->create();
        $user->assignRole('customer');
        Passport::actingAs($user, ['*'], 'api');
    }

    public function test_valid_canvas_is_saved_and_summarized(): void
    {
        $res = $this->postJson('/api/v1/designs', $this->payload([
            ['id' => 'a', 'type' => 'text', 'print_area_id' => $this->area->id, 'x' => 0, 'y' => 0, 'width' => 50, 'height' => 10, 'text' => 'Hello', 'font_family' => 'Lobster', 'font_weight' => '400', 'font_style' => 'italic', 'fill' => '#ff0000'],
        ]));

        $res->assertCreated()
            ->assertJsonPath('data.summary.0.side', 'front')
            ->assertJsonPath('data.summary.0.area', "Ko'krak")
            ->assertJsonPath('data.summary.0.label', '"Hello" — Lobster 400 italic')
            ->assertJsonPath('data.summary.0.size_cm', '10.0 × 3.0 sm');
    }

    public function test_unknown_font_or_area_or_side_is_rejected(): void
    {
        $this->postJson('/api/v1/designs', $this->payload([
            ['id' => 'a', 'type' => 'text', 'print_area_id' => 999, 'x' => 0, 'y' => 0, 'width' => 50, 'height' => 10, 'text' => 'x', 'font_family' => 'Comic Sans'],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['canvas.sides.front.layers.0.print_area_id', 'canvas.sides.front.layers.0.font_family']);

        $this->postJson('/api/v1/designs', $this->payload([], 'back'))
            ->assertUnprocessable()->assertJsonValidationErrors('canvas.sides');
    }

    public function test_layer_outside_area_or_without_file_is_rejected(): void
    {
        $this->postJson('/api/v1/designs', $this->payload([
            ['id' => 'a', 'type' => 'image', 'print_area_id' => $this->area->id, 'x' => 0, 'y' => 0, 'width' => 120, 'height' => 10],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['canvas.sides.front.layers.0.width', 'canvas.sides.front.layers.0.file_id']);
    }

    public function test_fonts_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/fonts')->assertOk()->assertJsonPath('data.0.family', 'Inter');
    }

    private function payload(array $layers, string $side = 'front'): array
    {
        return [
            'product_id' => $this->product->id,
            'product_color_id' => $this->color->id,
            'canvas' => ['version' => 1, 'sides' => [$side => ['layers' => $layers]]],
        ];
    }
}

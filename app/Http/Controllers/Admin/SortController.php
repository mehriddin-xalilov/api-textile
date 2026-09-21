<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Clipart;
use App\Models\Color;
use App\Models\GarmentModel;
use App\Models\Page;
use App\Models\PhraseTemplate;
use App\Models\Product;
use App\Models\Size;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** PUT /{resource}/sort {ids: [3,1,2]} — admin jadvalidagi drag-drop tartibi. */
class SortController extends Controller
{
    private const MODELS = [
        'categories' => Category::class,
        'colors' => Color::class,
        'sizes' => Size::class,
        'products' => Product::class,
        'cliparts' => Clipart::class,
        'phrases' => PhraseTemplate::class,
        'garment-models' => GarmentModel::class,
        'banners' => Banner::class,
        'pages' => Page::class,
    ];

    public function __invoke(Request $request, string $resource): JsonResponse
    {
        $model = self::MODELS[$resource] ?? abort(404);

        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        $ids = array_values($request->input('ids'));
        $cases = implode(' ', array_map(fn ($i) => "when {$ids[$i]} then {$i}", array_keys($ids)));

        // Bitta UPDATE — n ta so'rov o'rniga.
        $model::query()->whereIn('id', $ids)->update(['sort' => DB::raw("case id {$cases} end")]);

        return ApiResponse::message('Tartib saqlandi');
    }
}

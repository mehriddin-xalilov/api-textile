<?php

namespace App\Http\Controllers\Client;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\PageResource;
use App\Models\Banner;
use App\Models\Page;
use App\Models\Setting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/** Sayt konfiguratsiyasi: bannerlar, footer sahifalari, aloqa. Til: ?lang= yoki Accept-Language. */
class SiteController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::item([
            'banners' => BannerResource::collection(Banner::query()->with('image')->where('status', Status::Active)->orderBy('sort')->get())->resolve(),
            'pages' => PageResource::collection(Page::query()->where('status', Status::Active)->where('in_footer', true)->orderBy('sort')->get())->resolve(),
            'contact' => Setting::all_(),
            'languages' => ['uz', 'ru', 'en'],
        ]);
    }

    public function page(Page $page): JsonResponse
    {
        abort_unless($page->status === Status::Active, 404);

        return ApiResponse::item(new PageResource($page));
    }
}

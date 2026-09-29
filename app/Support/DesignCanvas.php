<?php

namespace App\Support;

use App\Enums\ProductSide;
use App\Models\PrintArea;
use App\Models\Product;
use Illuminate\Validation\Rule;

/**
 * Konstruktor canvas JSON sxemasi (v1).
 *
 * {
 *   "version": 1,
 *   "sides": {
 *     "front": {
 *       "layers": [
 *         { "id": "l1", "type": "text",  "print_area_id": 2, "x": 12.5, "y": 30, "width": 60, "height": 20, "rotation": 0,
 *           "text": "SALOM", "font_family": "Oswald", "font_weight": "700", "font_style": "italic", "fill": "#000000", "align": "center", "letter_spacing": 0 },
 *         { "id": "l2", "type": "image", "print_area_id": 1, "x": 0, "y": 0, "width": 100, "height": 100, "rotation": 0, "file_id": 17 },
 *         { "id": "l3", "type": "clipart", "print_area_id": 1, "x": 10, "y": 10, "width": 40, "height": 40, "clipart_id": 3, "fill": "#dc2626" }
 *       ]
 *     },
 *     "back": { "layers": [] }
 *   }
 * }
 *
 * x/y/width/height — PrintArea ichida FOIZ (0..100). Shunday qilib qatlam mockup o'lchamiga bog'liq emas:
 * admin, sayt, mobil va bosma fayl generatori bir xil koordinatadan foydalanadi.
 * Fizik o'lcham: width_cm = width% * print_area.max_width_cm / 100.
 */
final class DesignCanvas
{
    public const VERSION = 1;

    public const MAX_LAYERS_PER_SIDE = 20;

    public const MAX_TEXT_LENGTH = 120;

    /** @return array<string, array<int, mixed>> Laravel validation qoidalari (`canvas.` prefiksi bilan) */
    public static function rules(?Product $product): array
    {
        // v2: 3D konstruktor (shirt-designer) — o'z qatlam modeli, foizlar emas normallashtirilgan [0..1] markaz.
        if ((int) request()->input('canvas.version') === 2) {
            return self::rulesV2();
        }

        $areaIds = $product ? $product->printAreas()->pluck('id')->all() : [];
        $fonts = collect(config('fonts'))->pluck('family')->all();
        $sides = array_map(fn (ProductSide $s) => $s->value, ProductSide::cases());

        $layer = 'canvas.sides.*.layers.*';

        return [
            'canvas.version' => ['required', 'integer', Rule::in([self::VERSION])],
            'canvas.sides' => ['required', 'array', 'min:1'],
            'canvas.sides.*' => ['array'],
            'canvas.sides.*.layers' => ['present', 'array', 'max:'.self::MAX_LAYERS_PER_SIDE],
            "{$layer}.id" => ['required', 'string', 'max:32'],
            "{$layer}.type" => ['required', Rule::in(['text', 'image', 'clipart'])],
            "{$layer}.print_area_id" => ['required', 'integer', Rule::in($areaIds)],
            "{$layer}.x" => ['required', 'numeric', 'between:0,100'],
            "{$layer}.y" => ['required', 'numeric', 'between:0,100'],
            "{$layer}.width" => ['required', 'numeric', 'between:0.5,100'],
            "{$layer}.height" => ['required', 'numeric', 'between:0.5,100'],
            "{$layer}.rotation" => ['nullable', 'numeric', 'between:-180,180'],
            // text
            "{$layer}.text" => ['required_if:'.$layer.'.type,text', 'nullable', 'string', 'max:'.self::MAX_TEXT_LENGTH],
            "{$layer}.font_family" => ['required_if:'.$layer.'.type,text', 'nullable', Rule::in($fonts)],
            "{$layer}.font_weight" => ['nullable', Rule::in(['400', '700', '900'])],
            "{$layer}.font_style" => ['nullable', Rule::in(['normal', 'italic'])],
            "{$layer}.fill" => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            "{$layer}.align" => ['nullable', Rule::in(['left', 'center', 'right'])],
            "{$layer}.letter_spacing" => ['nullable', 'numeric', 'between:-5,50'],
            // image
            "{$layer}.file_id" => ['required_if:'.$layer.'.type,image', 'nullable', 'integer', 'exists:files,id'],
            // clipart (tayyor logo, fill — rangi)
            "{$layer}.clipart_id" => ['required_if:'.$layer.'.type,clipart', 'nullable', 'integer', Rule::exists('cliparts', 'id')->where('status', 'active')],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private static function rulesV2(): array
    {
        $fonts = collect(config('fonts'))->pluck('family')->all();
        $layer = 'canvas.layers.*';

        return [
            'canvas.version' => ['required', 'integer', Rule::in([2])],
            'canvas.engine' => ['required', Rule::in(['shirt-designer-3d'])],
            'canvas.colors' => ['required', 'array'],
            'canvas.colors.body' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/i'],
            'canvas.layers' => ['present', 'array', 'max:'.(self::MAX_LAYERS_PER_SIDE * 4)],
            "{$layer}.id" => ['required', 'string', 'max:64'],
            "{$layer}.type" => ['required', Rule::in(['text', 'image', 'shape', 'badge'])],
            "{$layer}.zone" => ['required', Rule::in(['front', 'back', 'sleeve_left', 'sleeve_right'])],
            "{$layer}.x" => ['required', 'numeric', 'between:0,1'],
            "{$layer}.y" => ['required', 'numeric', 'between:0,1'],
            "{$layer}.scale" => ['required', 'numeric', 'between:0.05,10'],
            "{$layer}.rotation" => ['nullable', 'numeric', 'between:-180,180'],
            "{$layer}.text" => ['required_if:'.$layer.'.type,text', 'nullable', 'string', 'max:'.self::MAX_TEXT_LENGTH],
            "{$layer}.fontFamily" => ['required_if:'.$layer.'.type,text', 'nullable', Rule::in($fonts)],
            "{$layer}.src" => ['required_if:'.$layer.'.type,image', 'nullable', 'string', 'max:2048', 'not_regex:/^data:/'],
            "{$layer}.fileId" => ['nullable', 'integer', 'exists:files,id'],
            "{$layer}.clipartId" => ['nullable', 'integer', 'exists:cliparts,id'],
            'canvas.print_files' => ['nullable', 'array'],
            'canvas.print_files.*' => ['integer', 'exists:files,id'],
        ];
    }

    /** Tomon nomlari mahsulotdagi PrintArea tomonlari bilan mos kelishini tekshiradi. */
    public static function sidesMismatch(array $canvas, Product $product): array
    {
        if (($canvas['version'] ?? 1) === 2) {
            return [];
        }

        $allowed = $product->printAreas()->pluck('side')->map(fn (ProductSide $s) => $s->value)->unique()->all();

        return array_values(array_diff(array_keys($canvas['sides'] ?? []), $allowed));
    }

    /**
     * Admin/bosma uchun qisqa xulosa: har bir qatlam qayerda, nima.
     *
     * @return list<array{side:string, area:string, type:string, label:string, size_cm:string|null}>
     */
    public static function summary(array $canvas, Product $product): array
    {
        if (($canvas['version'] ?? 1) === 2) {
            return self::summaryV2($canvas);
        }

        $areas = $product->printAreas->keyBy('id');
        $out = [];

        foreach ($canvas['sides'] ?? [] as $side => $data) {
            foreach ($data['layers'] ?? [] as $layer) {
                /** @var PrintArea|null $area */
                $area = $areas->get($layer['print_area_id'] ?? 0);
                $sizeCm = $area && $area->max_width_cm
                    ? sprintf('%.1f × %.1f sm', $layer['width'] * $area->max_width_cm / 100, $layer['height'] * ($area->max_height_cm ?? $area->max_width_cm) / 100)
                    : null;

                $out[] = [
                    'side' => $side,
                    'area' => $area?->name ?? '—',
                    'type' => $layer['type'],
                    'label' => match ($layer['type']) {
                        'text' => sprintf('"%s" — %s %s%s', $layer['text'], $layer['font_family'], $layer['font_weight'] ?? '400', ($layer['font_style'] ?? 'normal') === 'italic' ? ' italic' : ''),
                        'clipart' => sprintf('Tayyor logo #%s%s', $layer['clipart_id'] ?? '?', isset($layer['fill']) ? " ({$layer['fill']})" : ''),
                        default => 'Logo #'.($layer['file_id'] ?? '?'),
                    },
                    'size_cm' => $sizeCm,
                ];
            }
        }

        return $out;
    }

    /**
     * 3D konstruktor (v2) bosma taxtasi: 800×1000 px ≙ 12"×16" (30.5 × 40.6 sm) — Canvas2DStage bilan bir xil.
     * Qatlam o'lchami konstruktordagi getLayerBoxSize formulasi bilan hisoblanadi (rasm 350px·scale, shakl 250px·scale).
     */
    public const V2_BOARD_PX = [800, 1000];

    public const V2_BOARD_CM = [30.5, 40.6];

    /** @return list<array{side:string, area:string, type:string, label:string, size_cm:string|null, pos_cm:string|null}> */
    private static function summaryV2(array $canvas): array
    {
        $sides = ['front' => 'front', 'back' => 'back', 'sleeve_left' => 'left_sleeve', 'sleeve_right' => 'right_sleeve'];
        $cmPerPx = self::V2_BOARD_CM[0] / self::V2_BOARD_PX[0];
        $out = [];

        foreach ($canvas['layers'] ?? [] as $layer) {
            if (($layer['visible'] ?? true) === false) {
                continue;
            }
            $scale = (float) ($layer['scale'] ?? 1);
            [$wPx, $hPx] = match ($layer['type']) {
                'image' => [350 * $scale, 350 * $scale / max(0.05, (float) ($layer['aspectRatio'] ?? 1))],
                'text' => self::textBoxPx($layer),
                default => [250 * $scale, 250 * $scale],
            };
            $x = (float) ($layer['x'] ?? 0.5) * self::V2_BOARD_CM[0];
            $y = (float) ($layer['y'] ?? 0.5) * self::V2_BOARD_CM[1];

            $out[] = [
                'side' => $sides[$layer['zone']] ?? $layer['zone'],
                'area' => sprintf('%s × %s sm', self::V2_BOARD_CM[0], self::V2_BOARD_CM[1]),
                'type' => $layer['type'] === 'text' ? 'text' : ($layer['type'] === 'image' ? 'image' : 'clipart'),
                'label' => match ($layer['type']) {
                    'text' => sprintf('"%s" — %s %s%s', str_replace("\n", ' / ', $layer['text']), $layer['fontFamily'], $layer['fontWeight'] ?? '400', ($layer['fontStyle'] ?? 'normal') === 'italic' ? ' italic' : ''),
                    'shape' => 'Shakl: '.($layer['shapeType'] ?? '?').' ('.($layer['fillColor'] ?? '').')',
                    'badge' => 'Tayyor logo #'.($layer['clipartId'] ?? '?').' ('.($layer['fillColor'] ?? '').')',
                    default => isset($layer['clipartId']) ? 'Tayyor logo #'.$layer['clipartId'] : 'Logo'.(isset($layer['fileId']) ? ' #'.$layer['fileId'] : ''),
                },
                'size_cm' => sprintf('%.1f × %.1f sm', $wPx * $cmPerPx, $hPx * $cmPerPx),
                // markaz: chapdan / yuqoridan; burilish bo'lsa gradus
                'pos_cm' => sprintf('%.1f / %.1f sm', $x, $y).(abs((float) ($layer['rotation'] ?? 0)) >= 1 ? sprintf(' · %d°', round((float) $layer['rotation'])) : ''),
            ];
        }

        return $out;
    }

    /**
     * Matn qutisi (px, 800 taxta): serverda shrift o'lchab bo'lmaydi — o'rtacha harf eni 0.58·fontSize.
     *
     * @return array{0: float, 1: float}
     */
    private static function textBoxPx(array $layer): array
    {
        $scale = (float) ($layer['scale'] ?? 1);
        $fs = (float) ($layer['fontSize'] ?? 48);
        $lines = explode("\n", (string) ($layer['text'] ?? ''));
        $w = 0.0;
        foreach ($lines as $line) {
            $w = max($w, mb_strlen($line) * $fs * 0.58 + max(0, mb_strlen($line) - 1) * (float) ($layer['letterSpacing'] ?? 0));
        }
        $h = count($lines) * $fs * (float) ($layer['lineHeight'] ?? 1.2);

        return [max(48, $w * $scale), max(32, $h * $scale)];
    }
}

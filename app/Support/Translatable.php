<?php

namespace App\Support;

/**
 * name_uz / name_ru / name_en ustunlari uchun yordamchi.
 * Bo'sh bo'lsa uz ga qaytadi.
 */
trait Translatable
{
    public function translated(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->{"{$field}_{$locale}"} ?: $this->{"{$field}_uz"};
    }
}

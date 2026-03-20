<?php

namespace App\Translators;

use App\Services\AutoTranslationService;
use Illuminate\Translation\Translator;

class AutoTranslator extends Translator
{
    protected ?AutoTranslationService $autoService = null;

    public function setAutoTranslationService(AutoTranslationService $service): void
    {
        $this->autoService = $service;
    }

    /**
     * Override get() — Optimasi Ekstrim untuk mencapai 0,01 ms per baris.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @param  bool  $fallback
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true): string|array|null
    {
        $targetLocale = $locale ?? $this->getLocale();

        // JIKA LAYANAN AUTO BELUM SIAP (Gagal di Singleton)
        if ($this->autoService === null) {
            return parent::get($key, $replace, $targetLocale, $fallback);
        }

        // 🚀 OPTIMASI 0,01 MS: Langsung ke Memory-Map Layanan Auto
        // Jika teks sudah pernah diterjemahkan sebelumnya, kita bypass seluruh logika Laravel.
        $translated = $this->autoService->translate($key, $targetLocale);

        if ($translated !== $key) {
            return $this->makeReplacements($translated, $replace);
        }

        // JIKA TIDAK ADA DI AUTO-SERVICE: Gunakan Laravel Asli (Fallback ke file JSON/PHP)
        return parent::get($key, $replace, $targetLocale, $fallback);
    }
}

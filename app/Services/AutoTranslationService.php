<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AutoTranslationService
{
    /**
     * Locale yang tidak perlu otomatis diterjemahkan (sudah ditangani manual/fallback).
     */
    protected array $skipLocales = [];

    /**
     * Pemetaan locale Laravel → kode bahasa MyMemory API.
     */
    protected array $localeMap = [
        'id' => 'id',
        'en' => 'en-GB', // British English
        'en_US' => 'en-US', // American English
        'ar' => 'ar',
        'de' => 'de',
        'fr' => 'fr',
        'es' => 'es',
        'it' => 'it',
        'ja' => 'ja',
        'ko' => 'ko',
        'zh' => 'zh-CN',
        'ru' => 'ru',
        'tr' => 'tr',
        'hi' => 'hi',
        'nl' => 'nl',
        'pt' => 'pt',
        'pt_BR' => 'pt',
        'pt_PT' => 'pt-PT',
        'vi' => 'vi',
        'th' => 'th',
        'ms' => 'ms',
        'fa' => 'fa',
        'ur' => 'ur',
        'bn' => 'bn',
        'fil' => 'tl',
        'pl' => 'pl',
        'uk' => 'uk',
        'ro' => 'ro',
        'cs' => 'cs',
        'hu' => 'hu',
        'el' => 'el',
        'sv' => 'sv',
        'da' => 'da',
        'fi' => 'fi',
        'no' => 'no',
        'hr' => 'hr',
        'sk' => 'sk',
        'bg' => 'bg',
        'lt' => 'lt',
        'lv' => 'lv',
        'et' => 'et',
        'sr' => 'sr',
        'he' => 'he',
        'sw' => 'sw',
        'my' => 'my',
        'am' => 'am',
    ];

    /**
     * Memory cache untuk menghindari pembacaan cache dari disk berulang kali dalam satu request.
     */
    protected array $memoryCache = [];

    /**
     * Terjemahkan teks dari Bahasa Indonesia ke locale target.
     * Hasil di-cache permanen (30 hari) agar tidak berulang memanggil API.
     */
    public function translate(string $text, string $targetLocale): string
    {
        if (empty(trim($text)) || is_numeric($text)) {
            return $text;
        }

        // Jika target adalah bahasa Indonesia, dan teks kemungkinan besar sudah Indonesia atau Fallback, skip
        if ($targetLocale === 'id' && (preg_match('/[a-z]/i', $text) || preg_match('/[0-9]/', $text))) {
            // Opsional: kita bisa tambahkan deteksi bahasa lebih lanjut di sini
            // Tapi untuk performa 'Sinkron', kita asumsikan ID adalah default
        }

        $sourceHash = md5($text);
        $cacheKey = 'auto_trans.id.'.$targetLocale.'.'.$sourceHash;

        // 1. Cek Memory Cache (Super Fast)
        if (isset($this->memoryCache[$cacheKey])) {
            return $this->memoryCache[$cacheKey];
        }

        // 2. Cek Cache Driver (Disk/DB Cache)
        if (Cache::has($cacheKey)) {
            $value = Cache::get($cacheKey);
            $this->memoryCache[$cacheKey] = $value;

            return $value;
        }

        // 3. Cek Database via ORM (Eloquent)
        try {
            $persistent = Translation::where('source_hash', $sourceHash)
                ->where('target_locale', $targetLocale)
                ->first(['*']);

            if ($persistent) {
                $value = $persistent->translated_text;
                Cache::put($cacheKey, $value, now()->addDays(30));
                $this->memoryCache[$cacheKey] = $value;

                return $value;
            }
        } catch (\Throwable $e) {
            // Jika database belum siap atau tabel belum ada, biarkan lanjut ke API
        }

        // 4. Panggil API (Hanya jika belum ada di manapun)
        if (strlen($text) > 500) {
            return $text;
        }

        $targetLang = $this->localeMap[$targetLocale] ?? $targetLocale;
        $translated = $this->callApi($text, $targetLang);

        // Simpan via ORM jika berhasil
        if ($translated !== $text) {
            try {
                Translation::updateOrCreate(
                    ['source_hash' => $sourceHash, 'target_locale' => $targetLocale],
                    ['source_text' => $text, 'translated_text' => $translated]
                );
            } catch (\Throwable $e) {
                //abaikan error database saat menyimpan
            }
            Cache::put($cacheKey, $translated, now()->addDays(30));
        }

        $this->memoryCache[$cacheKey] = $translated;

        return $translated;
    }

    /**
     * Panggil MyMemory Translation API (gratis, tanpa key).
     */
    protected function callApi(string $text, string $targetLang): string
    {
        try {
            // Timeout diperpendek (2 detik), skip SSL verify agar handshake network lebih kilat
            $response = Http::timeout(2)
                ->withoutVerifying()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                ->get('https://api.mymemory.translated.net/get', [
                    'q' => $text,
                    'langpair' => "id|{$targetLang}",
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['responseStatus'] ?? 0) == 200 && ! empty($data['responseData']['translatedText'])) {
                    $translated = $data['responseData']['translatedText'];

                    // Bersihkan tag XML/HTML
                    $translated = html_entity_decode(strip_tags($translated), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    // Validasi: Jika hasil malah pesan error API, abaikan
                    if (str_contains(strtoupper($translated), 'QUERY SPECIFIED') || str_contains(strtoupper($translated), 'MYMEMORY WARNING')) {
                        return $text;
                    }

                    return $translated;
                }
            }
        } catch (\Throwable $e) {
            // Silently fail
        }

        return $text; // Fallback ke teks asli jika error 403 atau lainnya
    }

    public function shouldSkip(string $locale): bool
    {
        return in_array($locale, $this->skipLocales);
    }
}

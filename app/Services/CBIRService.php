<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CBIRService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ai_core_url', 'http://127.0.0.1:5000');
    }

    public function searchByImage($imageFile, $topK = 10)
    {
        try {
            /** @var Response $response */
            $response = Http::attach(
                'image',
                file_get_contents($imageFile->getRealPath()),
                $imageFile->getClientOriginalName()
            )->post("{$this->baseUrl}/search?k={$topK}");

            if ($response->successful()) {
                $results = $response->json();

                return $results['results'] ?? [];
            }

            Log::error('AI Core search error: '.$response->body());

            return [];
        } catch (\Exception $e) {
            Log::error('AI Core connection error: '.$e->getMessage());

            return [];
        }
    }

    public function indexMedia($media)
    {
        try {
            $response = Http::post("{$this->baseUrl}/index", [
                'id' => $media->id,
                'type' => 'wo_gallery',
                'owner_id' => $media->model_id,
                'image_path' => $media->getPath(),
                'image_url' => $media->getUrl(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('AI Core indexing error: '.$e->getMessage());

            return false;
        }
    }
}

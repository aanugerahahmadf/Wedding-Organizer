<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CBIRController extends Controller
{
    /**
     * CBIR API Base URL (Python Flask Server)
     */
    private $cbirApiUrl;

    public function __construct()
    {
        $this->cbirApiUrl = config('services.cbir_api_url', 'http://127.0.0.1:5000');
    }

    /**
     * Search for similar wedding packages using image
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchSimilar(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
            'top_k' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $image = $request->file('image');
            $topK = $request->input('top_k', 10);

            // Call FastAPI AI Core with k as query parameter
            // Increase timeout for image processing
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(60)->attach(
                'image', // Changed from 'file' to 'image' to match FastAPI
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName()
            )->post($this->cbirApiUrl.'/search?k='.$topK);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CBIR service error',
                    'error' => $response->json(),
                ], 500);
            }

            $results = $response->json();

            // Fetch package details from database
            $packageIds = collect($results['results'])
                ->pluck('package_id')
                ->filter()
                ->toArray();

            $packages = Package::with(['weddingOrganizer'])
                ->whereIn('id', $packageIds)
                ->get()
                ->keyBy('id');

            // Merge CBIR results with package data
            $enrichedResults = collect($results['results'])->map(function ($item) use ($packages) {
                $packageId = $item['package_id'] ?? null;

                if ($packageId && isset($packages[$packageId])) {
                    $package = $packages[$packageId];

                    return [
                        'similarity' => $item['similarity'],
                        'distance' => $item['distance'],
                        'package' => [
                            'id' => $package->id,
                            'name' => $package->name,
                            'slug' => $package->slug,
                            'description' => $package->description,
                            'price' => $package->price,
                            'image_url' => $package->image_url,
                            'wedding_organizer' => [
                                'id' => $package->weddingOrganizer->id,
                                'name' => $package->weddingOrganizer->name,
                                'city' => $package->weddingOrganizer->city,
                            ],
                        ],
                    ];
                }

                return null;
            })->filter()->values();

            return response()->json([
                'success' => true,
                'results' => $enrichedResults,
                'total_results' => $enrichedResults->count(),
                'query_time_seconds' => $results['query_time_seconds'] ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('CBIR Search Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process image search',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Index a package image into CBIR database
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexPackage(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        try {
            $package = Package::with('weddingOrganizer')->findOrFail($request->package_id);

            // Check if package has image
            if (! $package->image_url) {
                return response()->json([
                    'success' => false,
                    'message' => 'Package does not have an image',
                ], 400);
            }

            // Prepare metadata for CBIR
            $metadata = [
                'package_id' => $package->id,
                'wedding_organizer_id' => $package->wedding_organizer_id,
                'category' => $package->category,
                'theme' => $package->theme,
                'image_url' => $package->image_url,
                'filename' => basename($package->image_url),
            ];

            // Call FastAPI AI Core to index
            $response = Http::post($this->cbirApiUrl.'/index', [
                'id' => $package->id,
                'type' => 'package',
                'owner_id' => $package->wedding_organizer_id,
                'image_path' => Storage::path($package->image_url),
                'image_url' => $package->image_url,
            ]);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to index image',
                    'error' => $response->json(),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Package image indexed successfully',
                'data' => $response->json(),
            ]);

        } catch (\Exception $e) {
            Log::error('CBIR Indexing Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to index package',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Build index for all packages
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function buildIndex()
    {
        try {
            $packages = Package::with(['weddingOrganizer.media'])->get();

            $indexed = 0;
            $errors = [];

            foreach ($packages as $package) {
                try {
                    $media = $package->weddingOrganizer?->getFirstMedia('gallery');

                    if (! $media) {
                        $errors[] = "Package {$package->id}: No gallery image found for WO";

                        continue;
                    }

                    $imagePath = $media->getPath();
                    $imageUrl = $media->getUrl();

                    if (! file_exists($imagePath)) {
                        $errors[] = "Package {$package->id}: File not found at {$imagePath}";

                        continue;
                    }

                    $response = Http::post($this->cbirApiUrl.'/index', [
                        'id' => $package->id,
                        'type' => 'package',
                        'owner_id' => $package->wedding_organizer_id,
                        'image_path' => $imagePath,
                        'image_url' => $imageUrl,
                    ]);

                    if ($response->successful()) {
                        $indexed++;
                    } else {
                        $errors[] = "Package {$package->id}: ".$response->body();
                    }

                } catch (\Exception $e) {
                    $errors[] = "Package {$package->id}: ".$e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Indexed {$indexed} out of {$packages->count()} packages",
                'indexed_count' => $indexed,
                'total_packages' => $packages->count(),
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error('CBIR Build Index Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to build index',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get CBIR index statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::get($this->cbirApiUrl.'/status');

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get CBIR stats',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ]);

        } catch (\Exception $e) {
            Log::error('CBIR Stats Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Health check for CBIR service
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function healthCheck()
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(3)->get($this->cbirApiUrl.'/status');

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'CBIR service is healthy' : 'CBIR service is down',
                'data' => $response->json(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'CBIR service is unreachable',
                'error' => $e->getMessage(),
            ], 503);
        }
    }
}

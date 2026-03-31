<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\CBIRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CBIRController extends Controller
{
    /**
     * Search for similar wedding packages using image
     *
     * @return JsonResponse
     */
    public function searchSimilar(Request $request, CBIRService $cbirService)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
            'top_k' => 'nullable|integer|min:1|max:50',
        ]);

        $topK = (int) $request->input('top_k', 10);
        $results = $cbirService->searchByImage($request->file('image'), $topK);

        $organizerIds = collect($results)->pluck('owner_id')->filter()->unique()->values();
        $packages = Package::query()
            ->with(['weddingOrganizer'])
            ->whereIn('wedding_organizer_id', $organizerIds->all())
            ->latest()
            ->get()
            ->groupBy('wedding_organizer_id');

        $enrichedResults = collect($results)->map(function (array $item) use ($packages): array {
            $package = $packages->get((int) ($item['owner_id'] ?? 0))?->first();

            return [
                'similarity' => $item['similarity'] ?? 0,
                'distance' => round(1 - (($item['score'] ?? 0)), 4),
                'package' => $package ? [
                    'id' => $package->id,
                    'name' => $package->name,
                    'slug' => $package->slug,
                    'description' => $package->description,
                    'price' => $package->price,
                    'image_url' => $package->image_url,
                    'wedding_organizer' => [
                        'id' => $package->weddingOrganizer?->id,
                        'name' => $package->weddingOrganizer?->name,
                        'city' => $package->weddingOrganizer?->city,
                    ],
                ] : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'results' => $enrichedResults,
            'total_results' => $enrichedResults->count(),
            'query_time_seconds' => 0,
        ]);
    }

    /**
     * Index a package image into CBIR database
     *
     * @return JsonResponse
     */
    public function indexPackage(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $package = Package::with('weddingOrganizer')->findOrFail($request->package_id);

        return response()->json([
            'success' => true,
            'message' => __('Index lokal bersifat on-demand, tidak memerlukan sinkronisasi eksternal.'),
            'data' => [
                'package_id' => $package->id,
                'wedding_organizer_id' => $package->wedding_organizer_id,
            ],
        ]);
    }

    /**
     * Build index for all packages
     *
     * @return JsonResponse
     */
    public function buildIndex()
    {
        $totalPackages = Package::query()->count();

        return response()->json([
            'success' => true,
            'message' => __('CBIR lokal aktif. Proses build index tidak diperlukan.'),
            'indexed_count' => $totalPackages,
            'total_packages' => $totalPackages,
            'errors' => [],
        ]);
    }

    /**
     * Get CBIR index statistics
     *
     * @return JsonResponse
     */
    public function getStats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'mode' => 'local',
                'total_packages' => Package::query()->count(),
            ],
        ]);
    }

    /**
     * Health check for CBIR service
     *
     * @return JsonResponse
     */
    public function healthCheck()
    {
        return response()->json([
            'success' => true,
            'message' => __('CBIR lokal aktif dan sehat'),
            'data' => [
                'mode' => 'local',
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeddingOrganizer;
use App\Services\CBIRService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function byText(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $query = $request->input('query');

        /** @var Collection $organizers */
        $organizers = WeddingOrganizer::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orWhere('address', 'like', "%{$query}%")
            ->get(['*']);

        $formattedResults = $organizers->map(function (\App\Models\WeddingOrganizer $wo) {
            return [
                'organizer' => $wo,
                'package' => $wo->packages()->first(['*']),
                'score' => 1.0,
                'similarity' => 100,
                'matched_image' => $wo->getFirstMediaUrl('gallery') ?: 'https://via.placeholder.com/800x400',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedResults,
        ]);
    }

    public function byImage(Request $request, CBIRService $cbirService)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        $results = $cbirService->searchByImage($request->file('image'));

        if (empty($results)) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'message' => __('Rekomendasi gambar belum ditemukan.'),
            ]);
        }

        $formattedResults = collect($results)->map(function (mixed $result) {
            $wo = WeddingOrganizer::find($result['owner_id'], ['*']);
            if (! $wo) {
                return null;
            }

            return [
                'organizer' => $wo,
                'package' => $wo->packages()->first(['*']),
                'score' => $result['score'],
                'similarity' => $result['similarity'],
                'matched_image' => $result['image_url'],
            ];
        })->filter()->values();

        return response()->json([
            'status' => 'success',
            'data' => $formattedResults,
        ]);
    }
}

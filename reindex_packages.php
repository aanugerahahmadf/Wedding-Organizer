<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cbirUrl = 'http://127.0.0.1:5000';
$packages = \App\Models\Package::all();
echo "Indexing " . $packages->count() . " Packages into AI Core...\n";

foreach ($packages as $pkg) {
    if (!$pkg->image_url) {
        echo "Skip Pakcet $pkg->id (No Image)\n";
        continue;
    }

    $relPath = $pkg->image_url;
    // Fix absolute URLs from fake seeders
    if (str_starts_with($relPath, 'http')) {
        $parts = explode('/storage/', $relPath);
        if (count($parts) > 1) {
            $relPath = $parts[1];
        } else {
            // Might be /media/
            $parts = explode('/media/', $relPath);
            if (count($parts) > 1) {
                $relPath = $parts[1]; // Just the ID and filename!
            } else {
                continue;
            }
        }
    }

    $path = storage_path('app/public/' . ltrim($relPath, '/'));
    if (!file_exists($path)) {
        // Check if it's in public/media instead of storage/app/public
        $pathMedia = public_path(str_replace('../', '', $relPath));
        if (file_exists($pathMedia)) {
            $path = $pathMedia;
        } else {
            echo "Missing Image File on disk for Package {$pkg->id}: {$path}\n";
            continue;
        }
    }

    try {
        $response = \Illuminate\Support\Facades\Http::post("{$cbirUrl}/index", [
            'id' => $pkg->id,
            'type' => 'package',           
            'owner_id' => $pkg->id,        
            'image_path' => $path,
            'image_url' => $pkg->image_url,
        ]);

        if ($response->successful()) {
            echo "✅ Indexed Package ID: {$pkg->id} ({$pkg->name})\n";
        } else {
            echo "❌ Failed to index Package ID: {$pkg->id} - " . trim($response->body()) . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ AI Server Offline! Please turn on server.py first.\n";
        die();
    }
}

try {
    \Illuminate\Support\Facades\Http::get("{$cbirUrl}/reload");
    echo "🔄 AI Core FAISS Engine Reloaded Successfully!\n";
} catch (\Exception $e) {}

echo "Done! The CBIR AI is now aware of all Packages.\n";

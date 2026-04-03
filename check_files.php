<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Mengecek Foto Pertama di Database...\n";

$media = \Spatie\MediaLibrary\MediaCollections\Models\Media::first();

if (!$media) {
    echo "GADA MEDIA SAMA SEKALI DI DATABASE.\n";
} else {
    echo "Media ID: " . $media->id . "\n";
    echo "File Name: " . $media->file_name . "\n";
    echo "URL: " . $media->getUrl() . "\n";
    echo "Path Lengkap: " . $media->getPath() . "\n";
    echo "Apakah Filenya Ada di Disk? " . (file_exists($media->getPath()) ? "ADA ✅" : "TIDAK ADA ❌") . "\n";
    
    if (!file_exists($media->getPath())) {
        echo "KESIMPULAN: Record di Database Ada, tapi File Gambarnya GADA di folder storage Anda. Silakan upload ulang di Admin Panel.\n";
    }
}

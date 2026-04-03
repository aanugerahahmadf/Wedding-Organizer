<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Mulai perbaikan Database...\n";

// 1. Pastikan Wedding Organizer id=1 ada
$wo = \App\Models\WeddingOrganizer::find(1);
if (!$wo) {
    echo "WeddingOrganizer id=1 tidak ada. Membuat yang baru...\n";
    $wo = new \App\Models\WeddingOrganizer();
    $wo->id = 1;
    $wo->name = "Devi Make Up";
    $wo->slug = "devi-make-up";
    $wo->save();
} else {
    echo "WeddingOrganizer id=1 ditemukan: " . $wo->name . "\n";
}

// 2. Paksa semua artikel untuk terhubung ke WO id=1
$affectedArticles = \App\Models\Article::where('wedding_organizer_id', '!=', 1)
    ->orWhereNull('wedding_organizer_id')
    ->update(['wedding_organizer_id' => 1]);

echo "Jumlah Artikel yang diperbaiki koneksinya: $affectedArticles\n";

// 3. Paksa semua paket untuk terhubung ke WO id=1
$affectedPackages = \App\Models\Package::where('wedding_organizer_id', '!=', 1)
    ->orWhereNull('wedding_organizer_id')
    ->update(['wedding_organizer_id' => 1]);

echo "Jumlah Paket yang diperbaiki koneksinya: $affectedPackages\n";

echo "Selesai. Silakan refresh halaman Cloud!\n";

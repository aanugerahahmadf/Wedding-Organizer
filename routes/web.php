<?php
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;
use Native\Mobile\Facades\System;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mobile/settings', function () {
    System::appSettings();

    return back();
})->name('mobile.settings')->middleware(['auth']);

Route::get('/language/switch/{locale}', [LanguageController::class, 'switch'])
    ->name('language.switch');
Route::get('/media/{path}', function (string $path) {
    if (str_contains($path, '../')) { abort(403); }
    $file = storage_path('app/public/' . $path);
    if (!file_exists($file)) { abort(404); }
    return response()->file($file, ['Content-Type' => \Illuminate\Support\Facades\File::mimeType($file)]);
})->where('path', '.*')->name('media.serve');

Route::get('/fix-my-data', function () {
    echo "Memulai Sinkronisasi Data...<br>";
    if (!\Illuminate\Support\Facades\DB::table('wedding_organizers')->where('id', 1)->exists()) {
        $brand = \Illuminate\Support\Facades\DB::table('wedding_organizers')->first();
        if ($brand) {
            \Illuminate\Support\Facades\DB::table('wedding_organizers')->where('id', $brand->id)->update(['id' => 1]);
            echo "ID Lama dipindah ke ID 1.<br>";
        } else {
            \Illuminate\Support\Facades\DB::table('wedding_organizers')->insert([
                'id' => 1, 'name' => 'Devi Make Up', 'slug' => 'devi-make-up', 'created_at' => now(), 'updated_at' => now()
            ]);
            echo "Membuat record Wedding Organizer baru.<br>";
        }
    }
    \Illuminate\Support\Facades\DB::table('articles')->update(['wedding_organizer_id' => 1]);
    \Illuminate\Support\Facades\DB::table('packages')->update(['wedding_organizer_id' => 1]);
    echo "<b>Selesai!</b> Semua Artikel dan Paket telah terikat ke Brand ID 1. Silakan refresh halaman utama.";
});
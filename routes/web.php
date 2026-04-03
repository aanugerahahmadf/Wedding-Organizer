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

/**
 * Robust Media Serving for Windows & Linux
 */
Route::get('/media/{path}', function (string $path) {
    if (str_contains($path, '..')) { abort(403); }
    // Normalisasi path agar support Windows (\) dan Linux (/)
    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    $file = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $path);
    
    if (!file_exists($file)) { 
        abort(404, "File not found logic: " . $file); 
    }
    
    return response()->file($file);
})->where('path', '.*')->name('media.serve');

/**
 * Emergency Fix Route
 */
Route::get('/fix-my-data', function () {
    try {
        $wo = \App\Models\WeddingOrganizer::find(1);
        if (!$wo) return "Gagal: Brand dengan ID=1 tidak ditemukan.";
        
        \App\Models\Article::query()->update(['wedding_organizer_id' => 1]);
        \App\Models\Package::query()->update(['wedding_organizer_id' => 1]);
        
        return "SUKSES: Seluruh data disinkronkan ke Brand ID=1 (Devi Make Up). Silakan refresh halaman User.";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
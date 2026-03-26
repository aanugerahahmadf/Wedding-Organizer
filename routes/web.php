<?php
use Laravel\Mcp\Facades\Mcp;
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

Mcp::web('/mcp/demo', \App\Mcp\Servers\PublicServer::class);
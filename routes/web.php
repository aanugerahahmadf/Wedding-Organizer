<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;
use Native\Mobile\Facades\System;

Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/login', '/admin/login')->name('login');

Route::get('/mobile/settings', function () {
    System::appSettings();

    return back();
})->name('mobile.settings')->middleware(['auth']);

Route::get('/language/switch/{locale}', [LanguageController::class, 'switch'])
    ->name('language.switch');

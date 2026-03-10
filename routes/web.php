<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::redirect('/login', '/admin/login')->name('login');

Route::get('/mobile/settings', function () {
    \Native\Mobile\Facades\System::appSettings();
    return back();
})->name('mobile.settings')->middleware(['auth']);

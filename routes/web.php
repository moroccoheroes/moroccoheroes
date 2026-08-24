<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/locale/{locale}', function (Request $request, string $locale) {
    abort_unless(array_key_exists($locale, config('locales.supported')), 404);
    $request->session()->put('locale', $locale);
    return back();
})->name('locale.switch');

require __DIR__.'/settings.php';

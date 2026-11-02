<?php

use App\Http\Controllers\Admin\HeroController;
use App\Http\Controllers\Admin\SearchRequestController;
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






Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('requests', [SearchRequestController::class, 'index'])
            ->name('requests.index');

        Route::post('requests/{searchRequest}/run', [SearchRequestController::class, 'run'])
            ->name('requests.run');

        Route::get('heroes', [HeroController::class, 'index'])
            ->name('heroes.index');
    });
require __DIR__.'/settings.php';
?>
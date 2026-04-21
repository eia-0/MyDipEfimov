<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\BondController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


Route::get('/', function () {
    return view('welcome');
})->name('welcome');


Route::get('/bond/{secid}', [BondController::class, 'show'])->name('bond.show');

// API 
Route::prefix('api')->group(function () {
    // Получение всех данных облигации
    Route::get('/bond/{secid}', [BondController::class, 'apiBond'])->name('api.bond');
    // Поиск облигаций (автодополнение)
    Route::get('/search', [ApiController::class, 'search'])->name('api.search');
});


Route::middleware(['auth'])->group(function () {
    // Главная страница портфеля
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Управление портфелем
    Route::post('/portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
    Route::delete('/portfolio/{portfolio}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

    // Избранное
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    // Аналитика
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // Рынок (поиск и добавление облигаций)
    Route::get('/market', [MarketController::class, 'index'])->name('market');

    // Профиль
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


});


require __DIR__.'/auth.php';
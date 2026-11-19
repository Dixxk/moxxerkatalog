
<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Owner\OwnerCategoryController;
use App\Http\Controllers\Owner\OwnerAuthController;
use App\Http\Controllers\Owner\OwnerDashboardController;
use App\Http\Controllers\Owner\OwnerProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// PUBLIC CATALOG
Route::get('/catalog', [CatalogController::class, 'index'])
    ->name('catalog');

Route::get('/host_live', [CatalogController::class, 'hostLive'])
    ->name('catalog.host_live');

// OWNER AUTHENTICATION
Route::prefix('owner')->name('owner.')->group(function () {

    Route::get('/setup', [OwnerAuthController::class, 'setup'])
        ->name('setup');

    Route::post('/setup', [OwnerAuthController::class, 'store'])
        ->name('setup.store');

    Route::middleware('guest:owner')->group(function () {
        Route::get('/login', [OwnerAuthController::class, 'login'])
            ->name('login');

        Route::post('/login', [OwnerAuthController::class, 'authenticate'])
            ->name('authenticate');
    });

    Route::middleware('owner')->group(function () {

        // DASHBOARD
        Route::get('/dashboard', [OwnerDashboardController::class, 'index'])
            ->name('dashboard');

        // PRODUCTS
        Route::resource('/products', OwnerProductController::class);

        // CATEGORIES
        Route::resource('/categories', OwnerCategoryController::class)
            ->except(['create', 'show', 'edit']);

        // LOGOUT
        Route::post('/logout', [OwnerAuthController::class, 'logout'])
            ->name('logout');
    });
});

// USER BREEZE
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';

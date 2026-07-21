<?php

use App\Http\Controllers\Admin\AdminContactExportController;
use App\Http\Controllers\Admin\AdminOrderExportController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\OgImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/og-image', OgImageController::class)->middleware(['signed', 'throttle:30,1'])->name('og-image');

Route::middleware('auth')->prefix('admin/exports')->group(function (): void {
    Route::get('/customers', [AdminContactExportController::class, 'customers'])->name('admin.exports.customers');
    Route::get('/orders', [AdminOrderExportController::class, 'export'])->name('admin.exports.orders');
    Route::get('/questionnaire-submissions', [AdminContactExportController::class, 'questionnaireSubmissions'])->name('admin.exports.questionnaire-submissions');
});

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

Route::middleware('auth')->prefix('admin')->group(function (): void {
    Route::get('/orders/{number}/inpost-label', [\App\Http\Controllers\Admin\InPostLabelController::class, 'download'])->name('admin.orders.inpost-label');
    Route::get('/orders/{number}/orlen-label', [\App\Http\Controllers\Admin\OrlenPaczkaLabelController::class, 'download'])->name('admin.orders.orlen-label');
    Route::post('/sidebar/save-order', [\App\Http\Controllers\Admin\SidebarController::class, 'saveOrder'])->name('admin.sidebar.save-order');
});

// Secure endpoint for database initialization on shared hosting (deployment without SSH)
Route::get('/init-db-98231', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        $migrateOut = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'HelloKostekSeeder',
            '--force' => true
        ]);
        $seedOut = \Illuminate\Support\Facades\Artisan::output();

        return response()->json([
            'success' => true,
            'migrate' => $migrateOut,
            'seed' => $seedOut
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});


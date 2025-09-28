<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ============================================================================
// PUBLIC ROUTES
// ============================================================================

// Root route - redirect based on authentication status
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Public template sharing
Route::get('template/{token}', [TemplateController::class, 'share'])->name('template.share');
Route::post('template/{token}/track-download', [TemplateController::class, 'trackDownload'])->name('template.track-download');

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================

// Dashboard (requires auth + email verification)
Route::get('dashboard', function () {
    $templates = \App\Models\Template::where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->limit(6)
        ->get();

    return Inertia::render('Dashboard', [
        'templates' => $templates
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// ============================================================================
// SUBSCRIPTION-PROTECTED ROUTES
// ============================================================================

// Template management (requires auth + email verification + active subscription)
Route::middleware(['auth', 'verified', 'subscription'])->group(function () {
    // Template CRUD routes
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
    Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::post('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
    
    // Additional template routes
    Route::post('template/{token}/generate', [TemplateController::class, 'generate'])->name('template.generate');
});

// ============================================================================
// SUPER ADMIN ROUTES
// ============================================================================

// User management (super admin only)
Route::middleware(['auth', 'verified', 'super_admin'])->group(function () {
    // User CRUD routes
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::post('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// ============================================================================
// UTILITY ROUTES
// ============================================================================

// Subscription expired page
Route::get('subscription-expired', function () {
    return Inertia::render('SubscriptionExpired');
})->name('subscription.expired');

// Debug route (development only)
Route::get('debug-image', function () {
    $template = \App\Models\Template::first();
    if ($template && $template->background_image) {
        return response()->file(storage_path('app/public/' . $template->background_image));
    }
    return 'No template found';
});

// Debug route for testing tracking
Route::get('debug-tracking', function () {
    try {
        // Check if tables exist
        $visitsTable = \Schema::hasTable('template_visits');
        $downloadsTable = \Schema::hasTable('template_downloads');
        
        return response()->json([
            'visits_table_exists' => $visitsTable,
            'downloads_table_exists' => $downloadsTable,
            'templates_count' => \App\Models\Template::count(),
            'visits_count' => $visitsTable ? \App\Models\TemplateVisit::count() : 'N/A',
            'downloads_count' => $downloadsTable ? \App\Models\TemplateDownload::count() : 'N/A'
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// ============================================================================
// ROUTE GROUPS
// ============================================================================

// Authentication routes
require __DIR__.'/auth.php';

// Settings routes
require __DIR__.'/settings.php';

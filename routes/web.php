<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
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

// CSRF token refresh endpoint
Route::get('csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('csrf-token');

// Asset test endpoint for debugging
Route::get('test-assets', function () {
    return response()->json([
        'app_url' => config('app.url'),
        'asset_logo' => asset('images/logo.png'),
        'asset_storage' => asset('storage/test.txt'),
        'filesystem_public_url' => config('filesystems.disks.public.url'),
        'logo_exists' => file_exists(public_path('images/logo.png')),
        'storage_linked' => is_link(public_path('storage')),
        'environment' => app()->environment(),
        'asset_helper' => \App\Helpers\AssetHelper::testAssets(),
    ]);
})->name('test-assets');

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================

// Dashboard (requires auth + email verification)
Route::get('dashboard', function () {
    $user = auth()->user();
    
    // Check account status and redirect with error if inactive
    if (!$user->isAccountActive()) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect()->route('login')->withErrors([
            'email' => 'Your account is inactive. Please contact support to activate your account.',
        ]);
    }

    // Check subscription status and redirect with error if expired
    if (!$user->hasActiveSubscription()) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        
        return redirect()->route('login')->withErrors([
            'email' => 'Your account subscription has expired. Please contact support to renew your subscription.',
        ]);
    }
    
    $templates = \App\Models\Template::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->limit(6)
        ->get();

    $data = [
        'templates' => $templates,
        'template_count' => \App\Models\Template::where('user_id', $user->id)->count(),
    ];

    // Add superadmin-specific data
    if ($user->isSuperAdmin()) {
        $data['user_count'] = \App\Models\User::count();
        $data['total_template_count'] = \App\Models\Template::count();
    }

    return Inertia::render('Dashboard', $data);
    
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
    
    // PDF Template routes
    Route::get('pdf-templates', [App\Http\Controllers\PdfTemplateController::class, 'index'])->name('pdf-templates.index');
    Route::post('pdf-templates/upload-excel', [App\Http\Controllers\PdfTemplateController::class, 'uploadExcel'])->name('pdf-templates.upload-excel');
    Route::post('pdf-templates/upload-pdf', [App\Http\Controllers\PdfTemplateController::class, 'uploadPdf'])->name('pdf-templates.upload-pdf');
    Route::post('pdf-templates/generate', [App\Http\Controllers\PdfTemplateController::class, 'generatePdfs'])->name('pdf-templates.generate');
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
    
    // User status management
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/update-subscription', [UserController::class, 'updateSubscription'])->name('users.update-subscription');
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

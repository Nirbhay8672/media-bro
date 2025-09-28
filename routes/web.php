<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    $templates = \App\Models\Template::where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->limit(6)
        ->get();

    return Inertia::render('Dashboard', [
        'templates' => $templates
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Template routes (protected)
Route::middleware(['auth', 'verified', 'subscription'])->group(function () {
    Route::resource('templates', TemplateController::class);
});

// User management routes (super admin only)
Route::middleware(['auth', 'verified', 'super_admin'])->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

// Subscription expired page
Route::get('subscription-expired', function () {
    return Inertia::render('SubscriptionExpired');
})->name('subscription.expired');

// Public template sharing routes
Route::get('template/{token}', [TemplateController::class, 'share'])->name('template.share');

// Debug route to test image serving
Route::get('debug-image', function () {
    $template = \App\Models\Template::first();
    if ($template && $template->background_image) {
        return response()->file(storage_path('app/public/' . $template->background_image));
    }
    return 'No template found';
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

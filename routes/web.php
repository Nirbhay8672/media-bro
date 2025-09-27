<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TemplateController;

Route::get('/', function () {
    return Inertia::render('Welcome');
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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('templates', TemplateController::class);
});

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

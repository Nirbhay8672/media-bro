<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TemplateController;

Route::post('template/{token}/generate', [TemplateController::class, 'generate'])->name('api.template.generate');


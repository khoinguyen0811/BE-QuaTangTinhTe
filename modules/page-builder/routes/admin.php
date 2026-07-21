<?php

use Illuminate\Support\Facades\Route;
use HansSchouten\LaravelPageBuilder\Http\Controllers\PageBuilderDashboardController;
use HansSchouten\LaravelPageBuilder\Http\Controllers\PageBuilderEditorController;

Route::middleware(['web', 'setLocale', 'auth', 'admin'])
    ->prefix('{locale}/admin/page-builder-lab')
    ->whereIn('locale', ['vi', 'en'])
    ->name('pagebuilder.')
    ->group(function () {
        // Dashboard and Listing
        Route::get('/', [PageBuilderDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/pages', [PageBuilderDashboardController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [PageBuilderDashboardController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageBuilderDashboardController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}/edit', [PageBuilderDashboardController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [PageBuilderDashboardController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [PageBuilderDashboardController::class, 'destroy'])->name('pages.destroy');
        Route::post('/pages/{id}/restore', [PageBuilderDashboardController::class, 'restore'])->name('pages.restore');

        // Visual Editor and GrapesJS Actions
        Route::any('/editor', [PageBuilderEditorController::class, 'handleEditorAction'])->name('editor.action');
        Route::get('/pages/{page}/builder', [PageBuilderEditorController::class, 'builder'])->name('pages.builder');
        Route::put('/pages/{page}/autosave', [PageBuilderEditorController::class, 'autosave'])->name('pages.autosave');
        Route::post('/pages/{page}/publish', [PageBuilderEditorController::class, 'publish'])->name('pages.publish');
        Route::post('/pages/{page}/unpublish', [PageBuilderEditorController::class, 'unpublish'])->name('pages.unpublish');
        
        // Preview Route
        Route::get('/pages/{page}/preview', [PageBuilderEditorController::class, 'preview'])->name('pages.preview');
    });

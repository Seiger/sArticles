<?php

use Illuminate\Support\Facades\Route;
use Seiger\sArticles\Controllers\sArticlesController;

Route::middleware('mgr')->prefix('sarticles/')->name('sArticles.')->group(function () {
    Route::get('', [sArticlesController::class, 'index']);
    Route::post('upload-file', [sArticlesController::class, 'uploadFile'])->name('upload-file');
    Route::post('upload-download', [sArticlesController::class, 'uploadDownload'])->name('upload-download');
    Route::get('upload', [sArticlesController::class, 'addYoutube'])->name('addyoutube');
    Route::post('sort', [sArticlesController::class, 'sortGallery'])->name('sort');
    Route::post('delete', [sArticlesController::class, 'delete'])->name('delete');
    Route::get('translate', [sArticlesController::class, 'getTranslate'])->name('gettranslate');
    Route::post('translate', [sArticlesController::class, 'setTranslate'])->name('settranslate');
});
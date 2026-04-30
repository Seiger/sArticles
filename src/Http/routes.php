<?php

use Illuminate\Support\Facades\Route;
use Seiger\sArticles\sArticles;

Route::prefix('sarticles')->name('sArticles.')->group(function () {
    Route::post('comment-approve', [sArticles::class, 'approveComment'])->name('comment-approve');
    Route::post('article-publish', [sArticles::class, 'publishArticle'])->name('article-publish');
});

/*Route::prefix('sarticles/')->name('sArticles.')->group(function () {
    Route::get('poll/{pollid}', [sArticlesController::class, 'showPoll'])->name('poll');
});
*/

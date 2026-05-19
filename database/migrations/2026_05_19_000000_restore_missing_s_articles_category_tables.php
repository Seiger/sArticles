<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restore category tables that may be absent on upgraded installations.
 *
 * Early sArticles installations can have the original package migration marked as already
 * executed before category tables were added to that migration file. Laravel will not replay an
 * executed migration after the file changes, so production upgrades can miss the category table and
 * the article/category pivot even though fresh installs create them correctly.
 *
 * This migration is intentionally additive and guarded by `Schema::hasTable()` checks. It repairs
 * incomplete schemas without dropping or rewriting existing article, tag, feature, or translation
 * data.
 */
class RestoreMissingSArticlesCategoryTables extends Migration
{
    /**
     * Create missing category storage for legacy or partially upgraded sites.
     *
     * The category dictionary and article/category pivot are created only when absent. Existing
     * installations that already have these tables are left untouched, including their indexes,
     * records, and any project-level customizations.
     *
     * @return void
     * @since 2.1.0
     */
    public function up(): void
    {
        if (!Schema::hasTable('s_articles_categories')) {
            Schema::create('s_articles_categories', function (Blueprint $table) {
                $table->id('catid');
                $table->integer('position')->default(0);
                $table->string('alias', 255)->index();
                $table->string('cover', 255)->default('');
                $table->string('base', 255)->default('');
                $table->mediumText('base_content')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('s_article_categories')) {
            Schema::create('s_article_categories', function (Blueprint $table) {
                $table->integer('article')->index();
                $table->integer('category')->index();
            });
        }
    }

    /**
     * Leave restored production data in place when rolling back.
     *
     * This migration repairs missing historical tables. Dropping them on rollback could remove
     * categories or article/category assignments that were already used after the repair, so rollback
     * is intentionally a no-op.
     *
     * @return void
     * @since 2.1.0
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
}

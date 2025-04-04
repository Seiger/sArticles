<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Query\Expression;

class CreateSArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('s_articles', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('published')->default(0)->index();
            $table->integer('parent')->default(0)->index();
            $table->integer('author_id')->default(0)->index();
            $table->integer('views')->default(0)->index();
            $table->integer('position')->default(0);
            $table->integer('rating')->default(5);
            $table->string('alias', 255)->index();
            $table->string('cover', 255)->default('');
            $table->string('type')->default('article');
            $table->jsonb('relevants')->nullable();
            $table->jsonb('tmplvars')->nullable();
            $table->jsonb('votes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('s_article_translates', function (Blueprint $table) {
            $table->id('tid');
            $table->integer('article')->index();
            $table->string('lang', 4)->default('base');
            $table->string('pagetitle', 100)->default('');
            $table->string('longtitle', 255)->default('');
            $table->mediumText('introtext')->default('');
            $table->mediumText('description')->default('');
            $table->longText('content')->default('');
            $table->string('seotitle', 100)->default('');
            $table->string('seodescription', 255)->default('');
            $table->enum('seorobots', ['index,follow', 'noindex,nofollow'])->default('index,follow');
            $table->jsonb('builder');
            $table->jsonb('constructor');
            $table->timestamps();
        });

        Schema::create('s_articles_features', function (Blueprint $table) {
            $table->id('fid');
            $table->integer('position')->default(0);
            $table->string('alias', 255)->index();
            $table->string('badge', 255)->default('');
            $table->string('color', 100)->default('');
            $table->string('base', 255)->default('');
            $table->timestamps();
        });

        Schema::create('s_article_features', function (Blueprint $table) {
            $table->integer('article')->index();
            $table->integer('feature')->index();
        });

        Schema::create('s_articles_tags', function (Blueprint $table) {
            $table->id('tagid');
            $table->integer('position')->default(0);
            $table->string('alias', 255)->index();
            $table->string('base', 255)->default('');
            $table->mediumText('base_content')->default('');
            $table->timestamps();
        });

        Schema::create('s_article_tags', function (Blueprint $table) {
            $table->integer('article')->index();
            $table->integer('tag')->index();
        });

        Schema::create('s_articles_categories', function (Blueprint $table) {
            $table->id('catid');
            $table->integer('position')->default(0);
            $table->string('alias', 255)->index();
            $table->string('cover', 255)->default('');
            $table->string('base', 255)->default('');
            $table->mediumText('base_content')->default('');
            $table->timestamps();
        });

        Schema::create('s_article_categories', function (Blueprint $table) {
            $table->integer('article')->index();
            $table->integer('category')->index();
        });

        Schema::create('s_articles_authors', function (Blueprint $table) {
            $table->id('autid');
            $table->string('alias', 255)->index();
            $table->string('gender', 255)->default('man');
            $table->string('image', 255)->default('');
            $table->string('base_name', 255)->default('');
            $table->string('base_lastname', 255)->default('');
            $table->string('base_office', 255)->default('');
            $table->timestamps();
        });

        Schema::create('s_article_comments', function (Blueprint $table) {
            $table->id('comid');
            $table->integer('article_id')->default(0)->index();
            $table->integer('user_id')->default(0)->index();
            $table->integer('approved')->default(0)->index();
            $table->string('lang', 4)->default('base');
            $table->mediumText('comment')->default('');
            $table->timestamps();
        });

        Schema::create('s_articles_polls', function (Blueprint $table) {
            $table->id('pollid');
            $table->jsonb('question');
            $table->jsonb('answers');
            $table->jsonb('votes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('s_articles_polls');
        Schema::dropIfExists('s_article_comments');
        Schema::dropIfExists('s_articles_authors');
        Schema::dropIfExists('s_article_tags');
        Schema::dropIfExists('s_articles_tags');
        Schema::dropIfExists('s_article_features');
        Schema::dropIfExists('s_articles_features');
        Schema::dropIfExists('s_article_translates');
        Schema::dropIfExists('s_articles');
    }
}

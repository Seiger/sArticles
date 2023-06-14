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
            $table->integer('views')->default(0)->index();
            $table->integer('position')->default(0);
            $table->string('alias', 255)->index();
            $table->string('cover', 255)->default('');
            $table->jsonb('tmplvars')->default(new Expression('(JSON_ARRAY())'));
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
            $table->longText('content')->default('');
            $table->string('seotitle', 100)->default('');
            $table->string('seodescription', 255)->default('');
            $table->enum('seorobots', ['index,follow', 'noindex,nofollow'])->default('index,follow');
            $table->jsonb('constructor')->default(new Expression('(JSON_ARRAY())'));
            $table->timestamps();
        });

        Schema::create('s_a_features', function (Blueprint $table) {
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('s_article_features');
        Schema::dropIfExists('s_a_features');
        Schema::dropIfExists('s_article_translates');
        Schema::dropIfExists('s_articles');
    }
}

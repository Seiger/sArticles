<?php namespace Seiger\sArticles\Models;

use Illuminate\Database\Eloquent\Model;

class sArticlesPoll extends Model
{
    protected $primaryKey = 'pollid';

    protected $casts = [
        "question" => "array"
    ];
}

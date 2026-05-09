<?php namespace Seiger\sArticles\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * sArticlesPoll package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticlesPoll extends Model
{
    protected $primaryKey = 'pollid';

    protected $casts = [
        "question" => "array"
    ];
}

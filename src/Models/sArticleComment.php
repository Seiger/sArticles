<?php namespace Seiger\sArticles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;

/**
 * sArticleComment package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticleComment extends Model
{
    protected $primaryKey = 'comid';
}

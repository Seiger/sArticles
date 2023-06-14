<?php namespace Seiger\sArticles\Facades;

use Illuminate\Support\Facades\Facade;

class sArticles extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sArticles';
    }
}
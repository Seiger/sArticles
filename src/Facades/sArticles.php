<?php namespace Seiger\sArticles\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * sArticles package component.
 *
 * Documents the responsibilities owned by this sArticles component so manager, frontend,
 * and integration code can be maintained without guessing where behavior belongs.
 */
class sArticles extends Facade
{
    /**
     * Get facade accessor for the records manager flow.
     *
     * This helper keeps package-specific data shaping close to the evo-ui table or modal that
     * consumes it.
     */
    protected static function getFacadeAccessor()
    {
        return 'sArticles';
    }
}

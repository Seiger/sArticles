<?php
/**
 * Plugin for Seiger Offers Management Module for Evolution CMS admin panel.
 */

use Illuminate\Support\Arr;
use Seiger\sOffers\Models\sArticle;

/**
 * Catch the offer by alias
 */
Event::listen('evolution.OnPageNotFound', function($params) {
    $goTo = false;
    $aliasArr = request()->segments();
    if ($aliasArr[0] == evo()->getConfig('lang', 'uk')) {
        unset($aliasArr[0]);
    }
    $alias = implode('/', $aliasArr);

    $goTo = Arr::exists(sOffers::documentListing(), $alias);

    if (!$goTo && evo()->getLoginUserID('mgr')) {
        $alias = Arr::last($aliasArr);
        $offer = sOffers::getOfferByAlias($alias ?? '');

        if ($offer && isset($offer->offer) && (int)$offer->offer > 0) {
            $goTo = true;
        }
    }

    if ($goTo) {
        evo()->sendForward(evo()->getConfig('s_offers_resource', 1));
        exit();
    }
});

/*
 * Get document fields and add to array of resource fields
 */
Event::listen('evolution.OnAfterLoadDocumentObject', function($params) {
    $aliasArr = request()->segments();
    if (isset($aliasArr[0]) && $aliasArr[0] == evo()->getConfig('lang', 'uk')) {
        unset($aliasArr[0]);
    }
    $alias = implode('/', $aliasArr);
    $document = sOffers::documentListing()[$alias] ?? false;

    if (!$document && evo()->getLoginUserID('mgr')) {
        $alias = Arr::last($aliasArr);
        $offer = sOffers::getOfferByAlias($alias ?? '');

        if ($offer && isset($offer->offer) && (int)$offer->offer > 0) {
            $document = (int)$offer->offer;
        }
    }

    if ($document) {
        $offer = sArticle::find($document);
        $offer->constructor = data_is_json($offer->constructor, true);
        $offer->tmplvars = data_is_json($offer->tmplvars, true);

        if ($offer->tmplvars && count($offer->tmplvars)) {
            foreach ($offer->tmplvars as $name => $value) {
                if (isset($params['documentObject'][$name]) && is_array($params['documentObject'][$name])) {
                    $params['documentObject'][$name][1] = $value;
                }
            }
        }

        unset($offer->tmplvars);

        evo()->documentObject = array_merge($params['documentObject'], Arr::dot($offer->toArray()));
    }
});
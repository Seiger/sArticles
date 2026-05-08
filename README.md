# sArticles for Evolution CMS
[![Latest Stable Version](https://img.shields.io/packagist/v/seiger/sarticles?label=version)](https://packagist.org/packages/seiger/sarticles)
[![CMS Evolution](https://img.shields.io/badge/CMS-Evolution-brightgreen.svg)](https://github.com/evolution-cms/evolution)
![PHP version](https://img.shields.io/packagist/php-v/seiger/sarticles)
[![License](https://img.shields.io/packagist/l/seiger/sarticles)](https://packagist.org/packages/seiger/sarticles)
[![Issues](https://img.shields.io/github/issues/Seiger/sarticles)](https://github.com/Seiger/sarticles/issues)
[![Stars](https://img.shields.io/packagist/stars/Seiger/sarticles)](https://packagist.org/packages/seiger/sarticles)
[![Total Downloads](https://img.shields.io/packagist/dt/seiger/sarticles)](https://packagist.org/packages/seiger/sarticles)

**sArticles** Blog News and Articles Management Module for Evolution CMS admin panel.

> sArticles 1.x is the stable maintenance line for the current Evolution CMS manager UI.
> The upcoming 2.x line will include the new evo-ui based design.

## Versions

| Version | Branch | Status | Notes |
| --- | --- | --- | --- |
| 1.x | `1.x` | Stable / maintenance | Current manager UI, bug fixes only |
| 2.x | `2.x` | In development | New evo-ui based interface |

## Install by artisan package installer

Run in you /core/ folder:

```console
php artisan package:installrequire seiger/sarticles "^1.0"
```

Generate the config file in **core/custom/config/cms/settings** with
name **sarticles.php** the file should return a
comma-separated list of templates.

```console
php artisan vendor:publish --provider="Seiger\sArticles\sArticlesServiceProvider"
```

Create only the sArticles database structure with command:

```console
php artisan migrate --path=vendor/seiger/sarticles/database/migrations --force
```

## Events

```php
/*
 * Set default value for sArticles field
 */
Event::listen('evolution.sArticlesManagerValueEvent', function($params) {
    $result = '';
    if ($params['type'] == 'article') {
        if ($params['field'] == 'description') {
            $result = '<p></p>';
        }
    }
    return $result;
});
```

```php
/*
 * Add some html after the field
 */
Event::listen('evolution.sArticlesManagerAddAfterEvent', function($params) {
    $result = '';
    if ($params['type'] == 'idea') {
        if ($params['field'] == 'published_at') {
            $result = '';
        }
    }
    return $result;
});
```

# sArticles for Evolution CMS 3
[![Latest Stable Version](https://img.shields.io/packagist/v/seiger/sarticles?label=version)](https://packagist.org/packages/seiger/sarticles)
[![CMS Evolution](https://img.shields.io/badge/CMS-Evolution-brightgreen.svg)](https://github.com/evolution-cms/evolution)
![PHP version](https://img.shields.io/packagist/php-v/seiger/sarticles)
[![License](https://img.shields.io/packagist/l/seiger/sarticles)](https://packagist.org/packages/seiger/sarticles)
[![Issues](https://img.shields.io/github/issues/Seiger/sarticles)](https://github.com/Seiger/sarticles/issues)
[![Stars](https://img.shields.io/packagist/stars/Seiger/sarticles)](https://packagist.org/packages/seiger/sarticles)
[![Total Downloads](https://img.shields.io/packagist/dt/seiger/sarticles)](https://packagist.org/packages/seiger/sarticles)

**sArticles** Blog News and Articles Management Module for Evolution CMS admin panel.

## Install by artisan package installer

Run in you /core/ folder:

```console
php artisan package:installrequire seiger/sarticles "*"
```

Generate the config file in **core/custom/config/cms/settings** with
name **sarticles.php** the file should return a
comma-separated list of templates.

```console
php artisan vendor:publish --provider="Seiger\sArticles\sArticlesServiceProvider"
```

Run make DB structure with command:

```console
php artisan migrate
```

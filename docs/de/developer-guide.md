# Entwicklerhandbuch

## Architektur

sArticles ist ein EvoUI + Livewire Modul. `ModulePanel` steuert die Tabs, EvoUI rendert Tabellen und Formulare, und `src/Tables/*TableData.php` liefert Daten und Business-Logik.

## Installation

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

Extras-Umgebung:

```console
php artisan extras extras "sArticles"
```

## Konfiguration

Runtime-Datei:

```text
core/custom/config/seiger/settings/sArticles.php
```

UI-Presets:

- `config/articles/table.php`
- `config/articles/modal.php`
- `config/*/table.php`
- `config/settings/form.php`

## Integrationen

`SeoIntegration` arbeitet mit sSeo, resource type `article`, domain key `default`. Ohne sLang wird SEO im Base-Flow gespeichert, mit sLang pro Sprache.

`LangIntegration` liest Sprachen aus sLang, setzt die Standardsprache zuerst und erzeugt Tabs `lang_<code>`.

## Editoren

```php
sArticles::config('general.editor', 'system')
```

`system` nutzt EVO `which_editor`; andere Werte mussen registrierte Editoren sein.

## Frontend API

```php
$articles = sArticles::all(10);
$article = sArticles::getArticleByAlias('alias');
```

Methoden: `all`, `comments`, `getArticle`, `getArticleByAlias`, `resolveArticleByUri`, `trackView`, `showPoll`, `ratingVotes`, `setComment`, `publishArticle`, `config`.

## Regeln

- `EVO_*` fur Evolution-Pfade nutzen.
- sArticles-Logik in sArticles halten.
- EvoUI generisch halten.
- Nach Anderungen PHP lint ausfuhren.

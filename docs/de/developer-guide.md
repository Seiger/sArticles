# Entwicklerhandbuch

## Architektur

sArticles ist ein EvoUI + Livewire Modul. `ModulePanel` steuert die Tabs, EvoUI rendert Tabellen und Formulare, und `src/Tables/*TableData.php` liefert Daten und Business-Logik.

## Installation

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --tag=evo-ui --force
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

Extras-Umgebung:

```console
php artisan extras extras "sArticles"
```

## Upgrade von 1.x auf 2.x

Die offentliche API `sArticles::` bleibt verfugbar, aber 2.x registriert den Alias direkt in
`Seiger\sArticles\sArticlesServiceProvider`.

Entfernen Sie nach dem Upgrade von 1.x die alte generierte Alias-Datei, falls sie im Projekt
vorhanden ist:

```text
core/custom/config/app/aliases/sArticles.php
```

Fugen Sie fur sArticles keinen `extra.laravel.aliases` Eintrag in `composer.json` hinzu.
Provider Discovery bleibt in `extra.laravel.providers`; `extra.laravel.priority` sollte nur bei
einer echten Provider-Load-Order-Anforderung gesetzt werden.

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

sArticles speichert keine sSeo-Feldliste und keine sSeo-Speicherlogik im Paket. Es stellt Events
bereit, uber die sSeo Tab, Felder, Defaults, Options, Frontend Document Data und Speicherung
erganzt. Resource type: `article`. In Multisite-Projekten muss der Domain Key zur Site gehoren,
der der Artikel gehort. Ohne sLang wird SEO im Base-Flow gespeichert, mit sLang pro Sprache.

Wichtige Events:

```php
evo()->invokeEvent('sArticlesManagerModalDefaultsEvent', compact('article', 'content', 'data'));
evo()->invokeEvent('sArticlesManagerModalDataEvent', compact('article', 'content', 'data'));
evo()->invokeEvent('sArticlesManagerModalTabsEvent', compact('article', 'content', 'data'));
evo()->invokeEvent('sArticlesManagerModalFieldsEvent', compact('article', 'content', 'data'));
evo()->invokeEvent('sArticlesManagerModalOptionsEvent', compact('article', 'content', 'data'));
evo()->invokeEvent('sArticlesAfterContentSave', compact('article', 'content', 'data'));
evo()->invokeEvent('sArticlesOnBeforeLoadDocumentObject', [
    'article' => $article,
    'documentObject' => $documentObject,
]);
```

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

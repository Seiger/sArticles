# Przewodnik dewelopera

## Architektura

sArticles jest modulem EvoUI + Livewire. `ModulePanel` zarzadza zakladkami, EvoUI renderuje tabele i formularze, a `src/Tables/*TableData.php` dostarcza dane i logike biznesowa.

## Instalacja

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

W srodowisku Extras:

```console
php artisan extras extras "sArticles"
```

## Aktualizacja z 1.x do 2.x

Publiczne API `sArticles::` pozostaje dostepne, ale w 2.x alias jest rejestrowany bezposrednio w
`Seiger\sArticles\sArticlesServiceProvider`.

Po aktualizacji z 1.x usun stary wygenerowany plik aliasu, jesli istnieje w projekcie:

```text
core/custom/config/app/aliases/sArticles.php
```

Nie dodawaj `extra.laravel.aliases` dla sArticles w `composer.json`. Provider discovery pozostaje
w `extra.laravel.providers`; `extra.laravel.priority` dodawaj tylko wtedy, gdy pojawi sie realne
wymaganie dotyczace kolejnosci ladowania providerow.

## Konfiguracja

Plik runtime:

```text
core/custom/config/seiger/settings/sArticles.php
```

Presety UI:

- `config/articles/table.php`
- `config/articles/modal.php`
- `config/*/table.php`
- `config/settings/form.php`

## Integracje

`SeoIntegration` obsluguje sSeo, resource type `article`, domain key `default`. Bez sLang SEO jest zapisywane w base flow, z sLang per jezyk.

`LangIntegration` czyta jezyki z sLang, ustawia domyslny jezyk jako pierwszy i tworzy zakladki `lang_<code>`.

## Edytory

```php
sArticles::config('general.editor', 'system')
```

`system` uzywa EVO `which_editor`; inne wartosci musza byc nazwami zarejestrowanych edytorow.

## API frontend

```php
$articles = sArticles::all(10);
$article = sArticles::getArticleByAlias('alias');
```

Metody: `all`, `comments`, `getArticle`, `getArticleByAlias`, `resolveArticleByUri`, `trackView`, `showPoll`, `ratingVotes`, `setComment`, `publishArticle`, `config`.

## Zasady

- Uzywac `EVO_*` dla sciezek Evolution.
- Logike sArticles trzymac w sArticles.
- EvoUI zostawic generyczne.
- Po zmianach uruchamiac PHP lint.

# Przewodnik dewelopera

## Architektura

sArticles jest modulem EvoUI + Livewire. `ModulePanel` zarzadza zakladkami, EvoUI renderuje tabele i formularze, a `src/Tables/*TableData.php` dostarcza dane i logike biznesowa.

## Instalacja

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --tag=evo-ui --force
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

sArticles nie trzyma listy pol sSeo ani logiki zapisu sSeo wewnatrz pakietu. Udostepnia eventy,
przez ktore sSeo dodaje swoja zakladke, pola, wartosci domyslne, options, frontend document data i
zapis. Resource type: `article`. W projektach multisite domain key powinien odpowiadac stronie, do
ktorej nalezy artykul. Bez sLang SEO jest zapisywane w base flow, z sLang per jezyk.

Glowne eventy:

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

Listy frontendowe i rozpoznawanie URL uzywaja `sArticle::active()`. Rekord jest aktywny tylko wtedy, gdy `published = 1`, a `published_at` jest puste albo nie pozniejsze niz aktualny czas strony. Flow managera nadal moze ladowac opublikowane rekordy z przyszla data do edycji.

Metody: `all`, `comments`, `getArticle`, `getArticleByAlias`, `resolveArticleByUri`, `trackView`, `showPoll`, `ratingVotes`, `setComment`, `publishArticle`, `config`.

## Zasady

- Uzywac `EVO_*` dla sciezek Evolution.
- Logike sArticles trzymac w sArticles.
- EvoUI zostawic generyczne.
- Po zmianach uruchamiac PHP lint.

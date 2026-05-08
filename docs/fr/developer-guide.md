# Guide developpeur

## Architecture

sArticles est un module evo-ui + Livewire. `ModulePanel` gere les onglets, evo-ui rend les tables et formulaires, et `src/Tables/*TableData.php` fournit les donnees et la logique metier.

## Installation

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

En environnement Extras:

```console
php artisan extras extras "sArticles"
```

## Configuration

Fichier runtime:

```text
core/custom/config/seiger/settings/sArticles.php
```

Presets UI:

- `config/articles/table.php`
- `config/articles/modal.php`
- `config/*/table.php`
- `config/settings/form.php`

## Integrations

`SeoIntegration` utilise sSeo avec resource type `article` et domain key `default`. Sans sLang, le SEO est sauvegarde en mode base. Avec sLang, il est sauvegarde par langue.

`LangIntegration` lit les langues depuis sLang, place la langue par defaut en premier et cree des onglets `lang_<code>`.

## Editeurs

```php
sArticles::config('general.editor', 'system')
```

`system` utilise EVO `which_editor`; les autres valeurs doivent etre des editeurs enregistres.

## API frontend

```php
$articles = sArticles::all(10);
$article = sArticles::getArticleByAlias('alias');
```

Methodes: `all`, `comments`, `getArticle`, `getArticleByAlias`, `resolveArticleByUri`, `trackView`, `showPoll`, `ratingVotes`, `setComment`, `publishArticle`, `config`.

## Regles

- Utiliser `EVO_*` pour les chemins Evolution.
- Garder la logique sArticles dans sArticles.
- Garder evo-ui generique.
- Lancer PHP lint apres les changements.

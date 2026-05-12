# Guide developpeur

## Architecture

sArticles est un module EvoUI + Livewire. `ModulePanel` gere les onglets, EvoUI rend les tables et formulaires, et `src/Tables/*TableData.php` fournit les donnees et la logique metier.

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

## Mise a niveau de 1.x vers 2.x

L'API publique `sArticles::` reste disponible, mais en 2.x l'alias est enregistre directement dans
`Seiger\sArticles\sArticlesServiceProvider`.

Apres la mise a niveau depuis 1.x, supprimez l'ancien fichier d'alias genere s'il existe dans le
projet:

```text
core/custom/config/app/aliases/sArticles.php
```

N'ajoutez pas `extra.laravel.aliases` pour sArticles dans `composer.json`. La decouverte du
provider reste dans `extra.laravel.providers`; ajoutez `extra.laravel.priority` uniquement si une
contrainte reelle d'ordre de chargement apparait.

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
- Garder EvoUI generique.
- Lancer PHP lint apres les changements.

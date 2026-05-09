# Developer Guide

## Architecture

sArticles is now a config-driven EvoUI + Livewire module.

Core pieces:

- `Seiger\sArticles\sArticlesServiceProvider` registers migrations, views, translations, config presets, the Livewire module panel, and published assets.
- `Seiger\sArticles\Livewire\ModulePanel` renders the manager shell and switches internal tabs without iframe reloads.
- `EvoUI` renders tables, filters, choices, forms, modals, builder fields, delete dialogs, and session state.
- `src/Tables/*TableData.php` classes provide rows, options, modal defaults, saves, deletes, filters, and custom action logic.

## Installation

Inside `core`:

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

For Extras-based local environments:

```console
php artisan extras extras "sArticles"
```

## Configuration Files

Runtime settings are stored in:

```text
core/custom/config/seiger/settings/sArticles.php
```

Package defaults live in:

```text
config/sArticlesSettings.php
```

Manager UI presets:

- `config/articles/table.php`
- `config/articles/modal.php`
- `config/authors/table.php`
- `config/tags/table.php`
- `config/categories/table.php`
- `config/features/table.php`
- `config/comments/table.php`
- `config/polls/table.php`
- `config/tvparams/table.php`
- `config/settings/form.php`

## Table Presets

Each table preset defines:

- `key`
- `provider`
- `wire_target`
- `per_page` and `per_page_options`
- `views`
- `search`
- `actions`
- `filters`
- `columns`
- `list`
- `modal`
- `row_actions`

The provider class handles the data side. Keep table layout in config and business logic in provider classes.

## Article Modal

The article modal is configured in `config/articles/modal.php`.

It supports:

- Split layout.
- Main and content tabs.
- Conditional fields based on publication type settings.
- Language-aware fields when sLang is enabled.
- SEO fields when sSeo is enabled.
- Choices fields for topics, tags, features, and relevant publications.
- Content builder fields.

## Content Builder

Builder block source:

```text
builder/<block>/config.php
builder/<block>/render.blade.php
builder/<block>/template.blade.php
```

Published builder blocks can override package defaults:

```text
assets/modules/sarticles/builder/<block>
```

The article provider reads builder configs, normalizes builder data, saves it to article content, and renders frontend HTML with builder render templates.

## Rich Text Editors

sArticles reads the module setting:

```php
sArticles::config('general.editor', 'system')
```

Values:

- `system` - use EVO `which_editor`.
- registered editor name - force that editor for sArticles.

EvoUI supports `options_source.type = rich_text_editors`, which reads registered editors from `OnRichTextEditorRegister`.

## sSeo Integration

`src/Support/SeoIntegration.php` is the sSeo bridge.

Rules:

- Enabled when `check_sSeo` is true and sSeo classes exist.
- Resource type is `article`.
- Domain key is `default`.
- Without sLang, SEO is saved in the base flow.
- With sLang, SEO is saved per language.

Fields:

- robots
- meta title
- meta description
- meta keywords
- canonical URL
- exclude from sitemap
- priority
- change frequency

## sLang Integration

`src/Support/LangIntegration.php` is the sLang bridge.

Rules:

- Enabled when `check_sLang` is true and sLang facade exists.
- Languages are read from sLang config.
- Default language is placed first.
- Modal tab names use `lang_<code>`.

## Frontend API

Facade examples:

```php
$articles = sArticles::all(10);
$article = sArticles::getArticleByAlias('my-alias');
sArticles::trackView($article);
```

Important methods:

- `all($paginate = 30)`
- `comments($paginate = 30, $articleIds = [])`
- `getArticle($id)`
- `getArticleByAlias($alias)`
- `resolveArticleByUri($segments)`
- `trackView($article)`
- `documentListing()`
- `showPoll($id)`
- `ratingVotes($id)`
- `setComment($id)`
- `publishArticle()`
- `config($key, $default = null)`

## Events

Legacy extension events are still available for compatibility:

```php
Event::listen('evolution.sArticlesManagerValueEvent', function ($params) {
    return '';
});

Event::listen('evolution.sArticlesManagerAddAfterEvent', function ($params) {
    return '';
});
```

Prefer new EvoUI configuration and provider methods for new manager functionality.

## Development Rules

- Use `EVO_*` constants for Evolution paths.
- Keep module-specific behavior in sArticles, not EvoUI.
- Keep EvoUI generic.
- Keep table config declarative.
- Keep provider methods typed and focused.
- Run PHP lint after changes.

```console
find config src lang -name "*.php" -print0 | xargs -0 -n1 php -l
```

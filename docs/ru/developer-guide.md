# Руководство разработчика

## Архитектура

sArticles работает как EvoUI + Livewire модуль. `ModulePanel` управляет вкладками, EvoUI рисует таблицы и формы, а `src/Tables/*TableData.php` классы предоставляют данные и бизнес-логику.

## Установка

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

В Extras-окружении:

```console
php artisan extras extras "sArticles"
```

## Обновление с 1.x на 2.x

Публичный API `sArticles::` остается доступным, но в 2.x alias регистрируется внутри
`Seiger\sArticles\sArticlesServiceProvider`.

После обновления с 1.x удалите старый сгенерированный alias-файл, если он есть в проекте:

```text
core/custom/config/app/aliases/sArticles.php
```

Не добавляйте `extra.laravel.aliases` для sArticles в `composer.json`. Discovery провайдера
остается в `extra.laravel.providers`; `extra.laravel.priority` добавляйте только если появится
реальное требование к порядку загрузки провайдеров.

## Конфигурация

Рабочий файл:

```text
core/custom/config/seiger/settings/sArticles.php
```

UI-пресеты:

- `config/articles/table.php`
- `config/articles/modal.php`
- `config/*/table.php`
- `config/settings/form.php`

## Интеграции

`SeoIntegration` работает с sSeo, resource type `article`, domain key `default`. Без sLang SEO хранится в base-потоке, с sLang - по языкам.

`LangIntegration` читает языки из sLang, ставит язык по умолчанию первым и создает вкладки `lang_<code>`.

## Редакторы

```php
sArticles::config('general.editor', 'system')
```

`system` использует EVO `which_editor`; другое значение должно быть именем зарегистрированного editor.

## Frontend API

```php
$articles = sArticles::all(10);
$article = sArticles::getArticleByAlias('alias');
```

Методы: `all`, `comments`, `getArticle`, `getArticleByAlias`, `resolveArticleByUri`, `trackView`, `showPoll`, `ratingVotes`, `setComment`, `publishArticle`, `config`.

## Правила

- Использовать `EVO_*`.
- Логику sArticles держать в sArticles.
- EvoUI оставлять универсальным.
- После изменений запускать PHP lint.

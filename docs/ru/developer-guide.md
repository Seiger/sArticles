# Руководство разработчика

## Архитектура

sArticles работает как EvoUI + Livewire модуль. `ModulePanel` управляет вкладками, EvoUI рисует таблицы и формы, а `src/Tables/*TableData.php` классы предоставляют данные и бизнес-логику.

## Установка

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --tag=evo-ui --force
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

sArticles не хранит список SEO-полей и логику сохранения sSeo внутри пакета. Он предоставляет
события, через которые sSeo добавляет вкладку, поля, значения по умолчанию, options, frontend
document data и сохранение. Resource type: `article`. В multisite-проектах domain key должен
соответствовать сайту, которому принадлежит статья. Без sLang SEO хранится в base-потоке, с sLang -
по языкам.

Основные события:

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

Фронтенд-списки и разрешение URL используют `sArticle::active()`. Запись считается активной только если `published = 1`, а `published_at` пустой или не позже текущего времени сайта. Менеджер по-прежнему может загружать опубликованные записи с будущей датой для редактирования.

Методы: `all`, `comments`, `getArticle`, `getArticleByAlias`, `resolveArticleByUri`, `trackView`, `showPoll`, `ratingVotes`, `setComment`, `publishArticle`, `config`.

## Правила

- Использовать `EVO_*`.
- Логику sArticles держать в sArticles.
- EvoUI оставлять универсальным.
- После изменений запускать PHP lint.

# Гайд розробника

## Архітектура

sArticles тепер працює як конфігураційний EvoUI + Livewire модуль.

Основні частини:

- `Seiger\sArticles\sArticlesServiceProvider` реєструє міграції, views, переклади, конфіги, Livewire-компонент і публікацію assets.
- `Seiger\sArticles\Livewire\ModulePanel` рендерить оболонку модуля і перемикає вкладки без iframe reload.
- EvoUI відповідає за таблиці, фільтри, choices, форми, модалки, builder, delete dialogs і session state.
- `src/Tables/*TableData.php` відповідають за дані, опції, збереження, видалення, фільтрацію і дії.
- sArticles дає події для опціональних інтеграцій. Пакет, який інтегрується, сам відповідає за
  свої поля, дефолти, рендеринг і збереження.

## Встановлення

У директорії `core`:

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --tag=evo-ui --force
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

У Extras-оточенні:

```console
php artisan extras extras "sArticles"
```

## Оновлення з 1.x на 2.x

Публічний API `sArticles::` залишається доступним, але у 2.x alias реєструється всередині
`Seiger\sArticles\sArticlesServiceProvider`.

Після оновлення з 1.x видаліть старий згенерований alias-файл, якщо він є в проєкті:

```text
core/custom/config/app/aliases/sArticles.php
```

Не додавайте `extra.laravel.aliases` для sArticles у `composer.json`. Discovery провайдера
залишається в `extra.laravel.providers`; `extra.laravel.priority` додавайте тільки якщо з'явиться
реальна вимога до порядку завантаження провайдерів.

## Конфіги

Робочі налаштування:

```text
core/custom/config/seiger/settings/sArticles.php
```

Дефолти пакета:

```text
config/sArticlesSettings.php
```

UI-пресети:

- `config/articles/table.php`
- `config/articles/modal.php`
- `config/*/table.php`
- `config/settings/form.php`

## Таблиці і модалки

Табличні пресети описують `actions`, `filters`, `columns`, `list`, `modal` і `row_actions`. Провайдери тримають бізнес-логіку. Нові вкладки краще додавати через конфіг + окремий provider, а не через ручний HTML.

Модалка публікації підтримує умовні поля, мультимовні вкладки, sSeo, choices, builder і rich editor.

## Builder

Блоки лежать у:

```text
builder/<block>/config.php
builder/<block>/template.blade.php
views/render/<block>.blade.php
```

`builder/<block>/template.blade.php` використовується редактором у менеджері. Frontend HTML
рендериться через lowercase Laravel package views:

```text
sarticles::render.<block>
```

Для кастомізації розмітки потрібно скопіювати тільки потрібний render-файл у site-level vendor
override:

```text
views/vendor/sarticles/render/<block>.blade.php
```

Не публікуйте і не змінюйте всі render views за замовчуванням. Пакетні дефолти лишаються у vendor,
а проєкт перевизначає тільки ті блоки, яким потрібна власна HTML-структура.

`builder` JSON є source of truth, а поле `content` містить materialized HTML для сумісності,
фронту і пошуку. Після зміни render views існуючі статті потрібно оновити явно:

```console
php artisan sarticles:rerender --dry-run
php artisan sarticles:rerender --articles=123-10000 --chunk=200
php artisan sarticles:rerender --articles=123,124,200 --lang=uk
```

## Редактори

sArticles читає:

```php
sArticles::config('general.editor', 'system')
```

`system` означає EVO `which_editor`. Інше значення має бути ім'ям редактора, зареєстрованого через `OnRichTextEditorRegister`.

## Інтеграції

sSeo не зашитий у sArticles напряму. sArticles надає hook-и, через які sSeo може додати вкладку,
поля, дефолти, options, frontend document data і збереження.

- resource type: `article`;
- domain key визначається за сайтом, якому належить стаття в multisite-проєктах;
- без sLang зберігає base SEO;
- з sLang зберігає SEO по мовах.

Основні події інтеграцій:

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

`LangIntegration`:

- працює якщо `check_sLang` true і доступний facade sLang;
- читає список мов з sLang;
- дефолтну мову ставить першою;
- генерує вкладки `lang_<code>`.

## Frontend API

```php
$articles = sArticles::all(10);
$article = sArticles::getArticleByAlias('alias');
sArticles::trackView($article);
```

Корисні методи:

- `all()`
- `comments()`
- `getArticle()`
- `getArticleByAlias()`
- `resolveArticleByUri()`
- `trackView()`
- `showPoll()`
- `ratingVotes()`
- `setComment()`
- `publishArticle()`
- `config()`

## Правила розробки

- Для шляхів використовувати `EVO_*`.
- Модульні хуки тримати в sArticles.
- EvoUI лишати універсальним.
- Конфіги мають бути декларативні.
- Після змін запускати PHP lint.

```console
find config src lang -name "*.php" -print0 | xargs -0 -n1 php -l
```

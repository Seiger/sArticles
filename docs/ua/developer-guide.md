# Гайд розробника

## Архітектура

sArticles тепер працює як конфігураційний EvoUI + Livewire модуль.

Основні частини:

- `Seiger\sArticles\sArticlesServiceProvider` реєструє міграції, views, переклади, конфіги, Livewire-компонент і публікацію assets.
- `Seiger\sArticles\Livewire\ModulePanel` рендерить оболонку модуля і перемикає вкладки без iframe reload.
- EvoUI відповідає за таблиці, фільтри, choices, форми, модалки, builder, delete dialogs і session state.
- `src/Tables/*TableData.php` відповідають за дані, опції, збереження, видалення, фільтрацію і дії.

## Встановлення

У директорії `core`:

```console
php artisan package:installrequire seiger/sarticles "*"
php artisan vendor:publish --provider="Seiger\\sArticles\\sArticlesServiceProvider" --tag=sarticles
php artisan migrate
```

У Extras-оточенні:

```console
php artisan extras extras "sArticles"
```

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

`SeoIntegration`:

- працює якщо `check_sSeo` true і класи sSeo доступні;
- resource type: `article`;
- domain key: `default`;
- без sLang зберігає base SEO;
- з sLang зберігає SEO по мовах.

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

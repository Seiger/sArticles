# Гайд розробника

## Архітектура

sArticles використовує EvoUI + Livewire як менеджерський runtime. Модульні вкладки, таблиці, списки, модалки, choices, builder, inline actions і session state описуються конфігами та data-provider класами.

Основні файли:

- `views/livewire/module-panel.blade.php` - оболонка модуля.
- `config/articles/table.php` - таблиця, фільтри, колонки, список і row actions.
- `config/articles/modal.php` - форма створення та редагування публікації.
- `config/settings/form.php` - форма конфігурації.
- `src/Tables/ArticlesTableData.php` - article provider, choices, save/delete/duplicate, content builder і hook-и інтеграцій.
- `src/Tables/*TableData.php` - provider-и інших вкладок.
- `src/Support/LangIntegration.php` - інтеграція з sLang.
- `builder/*` - типи блоків контенту.

## Мінімальна залежність

```json
{
  "require": {
    "evolution-cms/evo-ui": "^1.0.2",
    "seiger/sarticles": "^2.0"
  }
}
```

## Реєстрація панелі

Панель Livewire відкривається через стандартний module entrypoint Evolution CMS. Старі iframe-flow гілки не мають керувати основним UI, але можуть лишатися тільки як чітко ізольований compatibility layer.

## Оновлення з 1.x на 2.x

Після оновлення залежностей опублікуйте runtime-асети EvoUI окремо від
sArticles:

```console
php artisan vendor:publish --tag=evo-ui --force
```

Починаючи з EvoUI `1.0.2`, CSS і JavaScript публікуються через symlink-aware
механізм Evolution CMS. sArticles має підключати `evo::partials.assets` і не
повинен дублювати EvoUI partials або відкривати URL напряму з `core/vendor`.

Публічний API `sArticles::` залишається доступним, але у 2.x alias реєструється всередині
`Seiger\sArticles\sArticlesServiceProvider`.

Після оновлення з 1.x видаліть старий згенерований alias-файл, якщо він є в проєкті:

```text
core/custom/config/app/aliases/sArticles.php
```

Не додавайте `extra.laravel.aliases` для sArticles у `composer.json`. Discovery провайдера
залишається в `extra.laravel.providers`; `extra.laravel.priority` додавайте тільки якщо з'явиться
реальна вимога до порядку завантаження провайдерів.

## Налаштування пакета

Нова інсталяція працює з дефолтами з пакета і не потребує попереднього копіювання повного
конфіга. `vendor:publish --tag=sarticles` лише готує директорію
`core/custom/config/seiger/settings` з `.gitkeep`, а файл
`core/custom/config/seiger/settings/sArticles.php` створюється тільки після зміни налаштувань у
менеджері.

Під час завантаження sArticles накладає локальні налаштування поверх дефолтів з
`config/sArticlesSettings.php`, тому нові ключі з оновлень пакета не губляться через старий
опублікований файл.

## Конфіги таблиць

Кожна вкладка має власний конфіг:

```php
return [
    'title' => 'Articles',
    'data' => \Seiger\sArticles\Tables\ArticlesTableData::class,
    'views' => ['table', 'list'],
    'default_view' => 'table',
    'filters' => [],
    'columns' => [],
    'list' => [],
    'modal' => require __DIR__ . '/modal.php',
];
```

Стан таблиці в межах менеджерської сесії зберігає EvoUI: view mode, search, filters, sort, direction, page і per-page.

## Модалки

Форма публікації винесена в окремий `modal.php`. Це тримає `table.php` фокусованим на списку, а форму - на user workflow. Для вкладок із sLang мовні поля потрібно редагувати в модалці, а не inline, щоб не втрачати переклади.

## Choices

Зв'язки публікації використовують компактні choices-поля. Вони підтримують:

- одиночний або множинний вибір;
- пошук, якщо він увімкнений конфігом;
- відкриття вниз або вгору залежно від простору;
- повторний клік по вибраному пункту як toggle.

## Builder

Контент публікації зберігається як впорядкований набір блоків. Кожен блок має тип, позицію і payload. Вкладені блоки, наприклад Slider або Accordion, мають власні елементи та не дозволяють видалити останній внутрішній елемент.

При додаванні нового блоку UI прокручує форму до створеного блоку, щоб менеджер одразу бачив результат дії.

Структура builder у 2.x:

```text
builder/<block>/config.php
builder/<block>/template.blade.php
views/render/<block>.blade.php
```

`template.blade.php` відповідає за manager UI, а frontend HTML рендериться через Laravel package
view:

```text
sarticles::render.<block>
```

Для кастомізації не потрібно запускати `vendor:publish` для всіх render-шаблонів. Скопіюйте тільки
потрібний файл у кореневий override:

```text
views/vendor/sarticles/render/<block>.blade.php
```

`builder` JSON є джерелом правди, а поле `content` зберігає materialized HTML. Якщо render view
змінено, оновіть вже збережений HTML явно:

```console
php artisan sarticles:rerender --dry-run
php artisan sarticles:rerender --articles=123-10000 --chunk=200
php artisan sarticles:rerender --articles=123,124,200 --lang=uk
```

## sSeo

Якщо встановлено sSeo, він підключається до sArticles через події. sArticles не тримає список
SEO-полів і не зберігає SEO напряму: пакет sSeo сам додає вкладку, поля, дефолти, options,
frontend document data і save logic.

Основні події:

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

Без sLang SEO зберігається в base-потоці. З sLang SEO зберігається по мовах. У multisite-проєктах
sSeo має зберігати дані під ключем сайту, якому належить стаття.

## sLang

Якщо встановлено sLang, модуль додає мовні вкладки для публікацій і мовні поля в модалки тегів, тематик, особливостей та опитувань. Inline edit для мовних сутностей у sArticles вимкнений, щоб поведінка була однакова для всіх мовних і немовних режимів.

## Frontend API

Фронтенд-списки та URL-резолвінг використовують `sArticle::active()`. Запис вважається активним тільки якщо `published = 1`, а `published_at` порожній або не пізніший за поточний час сайту. У менеджері записи з майбутньою датою публікації залишаються доступними для редагування.

## Rich editors

Параметр редактора в налаштуваннях sArticles може примусово вибрати системний EVO редактор, eTinyMCE, dTuiEditor або інший зареєстрований редактор. Вибір біля окремого поля не показується, щоб форма не дублювала глобальне налаштування.

## SQL сумісність

Мовні fallback-запити мають лишатися сумісними з MySQL і SQLite. Для порядку fallback-мов використовуйте `CASE lang WHEN ... THEN ... END`, а для порівняння колонок - `whereColumn`.

# Гайд розробника

## Архітектура

sArticles використовує EvoUI + Livewire як менеджерський runtime. Модульні вкладки, таблиці, списки, модалки, choices, builder, inline actions і session state описуються конфігами та data-provider класами.

Основні файли:

- `views/livewire/module-panel.blade.php` - оболонка модуля.
- `config/articles/table.php` - таблиця, фільтри, колонки, список і row actions.
- `config/articles/modal.php` - форма створення та редагування публікації.
- `config/settings/form.php` - форма конфігурації.
- `src/Tables/ArticlesTableData.php` - article provider, choices, save/delete/duplicate, content builder, sSeo/sLang glue.
- `src/Tables/*TableData.php` - provider-и інших вкладок.
- `src/Support/SeoIntegration.php` - інтеграція з sSeo.
- `src/Support/LangIntegration.php` - інтеграція з sLang.
- `builder/*` - типи блоків контенту.

## Мінімальна залежність

```json
{
  "require": {
    "evolution-cms/evo-ui": "^1.0",
    "seiger/sarticles": "^1.2"
  }
}
```

## Реєстрація панелі

Панель Livewire відкривається через стандартний module entrypoint Evolution CMS. Старі iframe-flow гілки не мають керувати основним UI, але можуть лишатися тільки як чітко ізольований compatibility layer.

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

Якщо встановлено sSeo, sArticles додає SEO-поля до форми публікації. Без sLang SEO живе в базовому потоці, а з sLang - у мовних вкладках і зберігається для конкретної мови.

## sLang

Якщо встановлено sLang, модуль додає мовні вкладки для публікацій і мовні поля в модалки тегів, тематик, особливостей та опитувань. Inline edit для мовних сутностей у sArticles вимкнений, щоб поведінка була однакова для всіх мовних і немовних режимів.

## Rich editors

Параметр редактора в налаштуваннях sArticles може примусово вибрати системний EVO редактор, eTinyMCE, dTuiEditor або інший зареєстрований редактор. Вибір біля окремого поля не показується, щоб форма не дублювала глобальне налаштування.

## SQL сумісність

Мовні fallback-запити мають лишатися сумісними з MySQL і SQLite. Для порядку fallback-мов використовуйте `CASE lang WHEN ... THEN ... END`, а для порівняння колонок - `whereColumn`.

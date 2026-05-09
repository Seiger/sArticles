# Документация sArticles

sArticles - модуль Evolution CMS для публикаций, новостей, блога, комментариев, опросов, авторов, тегов, тематик, особенностей и TV-параметров публикаций.

Новый интерфейс менеджера построен на **EvoUI** и **Livewire**. Вкладки, фильтры, пагинация, сортировка, таблица/список, модальные формы и редакторы обновляются без полной перезагрузки iframe.

## Разделы

- [Руководство пользователя](user-guide.md)
- [Руководство разработчика](developer-guide.md)

## Возможности

- Публикации с несколькими настраиваемыми типами.
- Табличный и списочный вид.
- Поиск, фильтры, сортировка, пагинация и сохранение состояния в сессии.
- Большая модалка редактирования публикации.
- Builder-контент: RichText, SingleImg, Image and Text, YouTube, Quote, Note, ArticlePreview, Poll, Slider, Accordion, File.
- Управление авторами, тегами, текстами тегов, тематиками, особенностями, комментариями, опросами и TV.
- Интеграции с sSeo, sLang, eTinyMCE и dTui.editor.

## Основные файлы

- `config/sArticlesSettings.php`
- `config/settings/form.php`
- `config/articles/table.php`
- `config/articles/modal.php`
- `src/Tables/ArticlesTableData.php`
- `src/Support/SeoIntegration.php`
- `src/Support/LangIntegration.php`
- `builder/*`

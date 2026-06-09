# sArticles Documentation

sArticles is an Evolution CMS manager module for publications, news, blog posts, comments, polls, authors, tags, topics, features, and publication-specific TV parameters.

The current admin interface is built with **EvoUI** and **Livewire**. The module behaves like a compact SPA inside the Evolution manager: tabs, filters, pagination, sorting, table/list view, modals, and editor fields update without reloading the whole manager iframe.

## Guides

- [User Guide](user-guide.md)
- [Developer Guide](developer-guide.md)

## Screenshots

### Publications manager

![sArticles publications manager](../assets/articles-manager.png)

### Article editor

![sArticles article editor](../assets/article-editor.png)

### Module settings

![sArticles module settings](../assets/settings.png)

## Main Capabilities

- Publication list with table and list modes.
- Search, sorting, filters, date range filters, availability filters, and per-page pagination.
- Configurable publication types.
- Future publication dates keep published records hidden on the frontend until their time.
- Article editor modal with main fields, relations, multilingual content, SEO fields, and content builder.
- Builder blocks: RichText, SingleImg, Image and Text, YouTube, Quote, Note, ArticlePreview, Poll, Slider, Accordion, File.
- Author, tag, tag text, topic, feature, comment, poll, and TV parameter management.
- Optional native integrations with sSeo and sLang.
- Rich editor selection for sArticles: system, eTinyMCE, dTuiEditor, or any registered EVO rich text editor.

## Runtime

sArticles uses:

- Evolution CMS service provider.
- EvoUI table and form presets.
- Livewire module panel.
- Laravel-style config, migrations, translations, and views.
- Published builder templates for frontend rendering.

## Important Files

- `config/sArticlesSettings.php` - default module settings.
- `config/settings/form.php` - settings UI.
- `config/articles/table.php` - publication table preset.
- `config/articles/modal.php` - publication modal preset.
- `src/Tables/ArticlesTableData.php` - publication data provider and save logic.
- sArticles manager/frontend events - integration points for packages such as sSeo.
- `src/Support/LangIntegration.php` - sLang bridge.
- `builder/*` - content builder blocks.

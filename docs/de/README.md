# sArticles Dokumentation

sArticles ist ein Evolution CMS Modul fur Publikationen, News, Blogartikel, Kommentare, Umfragen, Autoren, Tags, Themen, Merkmale und publikationsbezogene TV-Parameter.

Die neue Manager-Oberflache basiert auf **EvoUI** und **Livewire**. Tabs, Filter, Sortierung, Pagination, Tabellen-/Listenansicht, Modale und Editorfelder aktualisieren sich ohne kompletten iframe Reload.

## Leitfaden

- [Benutzerhandbuch](user-guide.md)
- [Entwicklerhandbuch](developer-guide.md)

## Screenshots

### Publikationsmanager

![sArticles Publikationsmanager](../assets/articles-manager.png)

### Publikationseditor

![sArticles Publikationseditor](../assets/article-editor.png)

### Moduleinstellungen

![sArticles Einstellungen](../assets/settings.png)

## Funktionen

- Publikationen mit konfigurierbaren Typen.
- Tabellen- und Listenansicht.
- Suche, Filter, Sortierung, Pagination und Session-State.
- Zukunftige Veroffentlichungsdaten halten veroffentlichte Datensatze im Frontend verborgen.
- GroBes Bearbeitungsmodal fur Artikel.
- Content Builder: RichText, SingleImg, Image and Text, YouTube, Quote, Note, ArticlePreview, Poll, Slider, Accordion, File.
- Verwaltung von Autoren, Tags, Tag-Texten, Themen, Merkmalen, Kommentaren, Umfragen und TV-Parametern.
- Native Integrationen mit sSeo, sLang, eTinyMCE und dTui.editor.

## Wichtige Dateien

- `config/sArticlesSettings.php`
- `config/settings/form.php`
- `config/articles/table.php`
- `config/articles/modal.php`
- `src/Tables/ArticlesTableData.php`
- sArticles Manager- und Frontend-Events fur Integrationen wie sSeo
- `src/Support/LangIntegration.php`
- `builder/*`

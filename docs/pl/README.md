# Dokumentacja sArticles

sArticles to modul Evolution CMS do publikacji, aktualnosci, bloga, komentarzy, ankiet, autorow, tagow, tematow, cech oraz parametrow TV publikacji.

Nowy interfejs managera jest zbudowany na **EvoUI** i **Livewire**. Zakladki, filtry, sortowanie, paginacja, widok tabeli/listy, modale i pola edytora odswiezaja sie bez pelnego przeladowania iframe.

## Przewodniki

- [Przewodnik uzytkownika](user-guide.md)
- [Przewodnik dewelopera](developer-guide.md)

## Zrzuty ekranu

### Manager publikacji

![Manager publikacji sArticles](../assets/articles-manager.png)

### Edytor publikacji

![Edytor publikacji sArticles](../assets/article-editor.png)

### Ustawienia modulu

![Ustawienia sArticles](../assets/settings.png)

## Funkcje

- Publikacje z konfigurowalnymi typami.
- Widok tabeli i listy.
- Wyszukiwanie, filtry, sortowanie, paginacja i stan sesji.
- Duzy modal edycji publikacji.
- Content Builder: RichText, SingleImg, Image and Text, YouTube, Quote, Note, ArticlePreview, Poll, Slider, Accordion, File.
- Zarzadzanie autorami, tagami, tekstami tagow, tematami, cechami, komentarzami, ankietami i TV.
- Natywne integracje z sSeo, sLang, eTinyMCE i dTui.editor.

## Wazne pliki

- `config/sArticlesSettings.php`
- `config/settings/form.php`
- `config/articles/table.php`
- `config/articles/modal.php`
- `src/Tables/ArticlesTableData.php`
- eventy managera i frontendu sArticles dla integracji takich jak sSeo
- `src/Support/LangIntegration.php`
- `builder/*`

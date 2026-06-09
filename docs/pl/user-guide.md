# Przewodnik uzytkownika

## Zakladki managera

Modul zawiera zakladki Articles, Autorzy, Tagi, Komentarze, Ankiety, Tematy, Cechy, Parametry (TV) i Konfiguracja.

Zakladki dzialaja przez Livewire. Zmiana zakladki odswieza tylko zawartosc modulu.

## Listy

Wiekszosc list obsluguje widok tabeli i listy, wyszukiwanie, sortowanie, paginacje, liczbe wierszy i akcje wiersza. Lista publikacji ma dodatkowe filtry po sekcji, tagach, tematach, cechach, dacie, statusie i typie.

## Edycja publikacji

Publikacja otwiera sie w duzym modalu. Glowna zakladka zawiera tytul, dlugi tytul, streszczenie, alias, opis, obraz glowny, tytul obrazu, status, date, sekcje, autora, pozycje i relacje.

Zakladka tresci zawiera builder. Gdy sLang jest aktywny, zakladki sa tworzone per jezyk.

## Data publikacji

Opublikowane publikacje z przyszla data publikacji pozostaja ukryte na frontendzie do czasu osiagniecia tej daty.

Manager nadal moze wyswietlac i edytowac takie rekordy, ale listy frontendowe, rozpoznawanie URL i cache aliasow uzywaja tej samej reguly aktywnej publikacji:

- status publikacji jest wlaczony;
- data publikacji jest pusta albo nie pozniejsza niz aktualny czas strony.

Jesli publikacja zostanie zapisana jako opublikowana bez daty, sArticles zapisuje aktualny czas jako date publikacji. Szkice moga pozostawic date pusta.

## Builder

Dostepne bloki: RichText, SingleImg, Image and Text, YouTube, Quote, Note, ArticlePreview, Poll, Slider, Accordion i File.

## Konfiguracja

Konfiguracja pozwala wlaczac funkcje bazowe, ustawic menu managera, wybrac edytor i zarzadzac typami publikacji.

Pierwszy typ `article` jest chroniony. Inne typy mozna usunac tylko wtedy, gdy nie sa uzywane.

## Edytor

Parametr **Edytor**:

- **Systemowy** - uzywa EVO `which_editor`.
- **eTinyMCE** - wymusza eTinyMCE w sArticles.
- **dTuiEditor** - wymusza dTui.editor w sArticles.
- Dowolny zarejestrowany edytor EVO.

## SEO i jezyki

sSeo dodaje pola SEO. sLang dodaje zakladki jezykowe i zapisuje tlumaczenia oddzielnie.

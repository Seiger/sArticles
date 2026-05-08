# Documentation sArticles

sArticles est un module Evolution CMS pour les publications, actualites, articles de blog, commentaires, sondages, auteurs, tags, thematiques, caracteristiques et parametres TV de publication.

La nouvelle interface manager est construite avec **evo-ui** et **Livewire**. Les onglets, filtres, tris, pagination, vues table/liste, modales et champs editeur se mettent a jour sans recharger tout l'iframe du manager.

## Guides

- [Guide utilisateur](user-guide.md)
- [Guide developpeur](developer-guide.md)

## Fonctionnalites

- Publications avec types configurables.
- Vue table et vue liste.
- Recherche, filtres, tri, pagination et etat en session.
- Grande modale d'edition d'article.
- Content Builder: RichText, SingleImg, Image and Text, YouTube, Quote, Note, ArticlePreview, Poll, Slider, Accordion, File.
- Gestion des auteurs, tags, textes de tag, thematiques, caracteristiques, commentaires, sondages et TV.
- Integrations natives avec sSeo, sLang, eTinyMCE et dTui.editor.

## Fichiers importants

- `config/sArticlesSettings.php`
- `config/settings/form.php`
- `config/articles/table.php`
- `config/articles/modal.php`
- `src/Tables/ArticlesTableData.php`
- `src/Support/SeoIntegration.php`
- `src/Support/LangIntegration.php`
- `builder/*`

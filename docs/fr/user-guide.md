# Guide utilisateur

## Onglets du manager

Le module contient les onglets Articles, Auteurs, Tags, Commentaires, Sondages, Thematiques, Caracteristiques, Parametres (TV) et Configuration.

Les onglets utilisent Livewire. Le changement d'onglet met a jour seulement le contenu du module.

## Listes

La plupart des listes proposent vue table, vue liste, recherche, tri, pagination, nombre de lignes et actions de ligne. La liste des articles ajoute des filtres par section, tags, thematiques, caracteristiques, date, statut et type.

## Edition d'une publication

La publication s'edite dans une grande modale. L'onglet principal contient titre, titre long, resume, alias, description, image principale, titre d'image, statut, date, section, auteur, position et relations.

L'onglet contenu contient le builder. Si sLang est actif, les onglets sont crees par langue.

## Builder

Blocs disponibles: RichText, SingleImg, Image and Text, YouTube, Quote, Note, ArticlePreview, Poll, Slider, Accordion et File.

## Configuration

La configuration permet d'activer les fonctions de base, le menu manager, l'editeur et les types de publication.

Le premier type `article` est protege. Les autres types ne peuvent etre supprimes que s'ils ne sont pas utilises.

## Editeur

Le parametre **Editeur**:

- **Systeme** - utilise EVO `which_editor`.
- **eTinyMCE** - force eTinyMCE dans sArticles.
- **dTuiEditor** - force dTui.editor dans sArticles.
- Tout autre editeur EVO enregistre.

## SEO et langues

sSeo ajoute les champs SEO. sLang ajoute les onglets de langues et sauvegarde les traductions separement.

# User Guide

## Manager Tabs

sArticles adds a Publications module to the Evolution CMS manager. The module contains these tabs:

- **Articles** - publication list and article editor.
- **Authors** - author profiles and avatars.
- **Tags** - tags and optional rich tag texts.
- **Comments** - publication comments and moderation.
- **Polls** - polls and answer ordering.
- **Topics** - publication topics.
- **Features** - badges and article features.
- **Parameters (TV)** - publication-specific TV parameters.
- **Configuration** - module settings and publication types.

Tabs are Livewire-driven. Switching between module tabs keeps the manager shell in place and refreshes only the needed HTML.

## Working With Lists

Most tabs support:

- Table and list view.
- Search.
- Sorting.
- Pagination.
- Per-page selection.
- Row actions.
- Selection actions for edit, duplicate, and delete where available.

The article tab also includes filters for sections, tags, topics, features, publication date, availability, and publication type when more than one type exists.

## Creating Or Editing A Publication

Use the add button to create a publication or the edit action to open an existing one. The editor opens in a large modal.

The main tab contains:

- Title.
- Long title.
- Summary.
- Alias.
- Description.
- Main image.
- Image title.
- Publication status and publication date.
- Section and author.
- Position.
- Topics, main tag, publication tags, features, and relevant publications.

The content tab contains the content builder. If sLang is enabled, the modal shows language-specific main/content tabs.

## Publication Date

Published publications with a future publication date stay hidden on the frontend until that date is reached.

The manager may still show and edit these records, but frontend lists, article URL resolution, and the article alias cache use the same active-publication rule:

- publication status is enabled;
- publication date is empty or is not later than the current site time.

If a publication is saved as published without a date, sArticles stores the current time as the publication date. Drafts may keep the publication date empty.

## Content Builder

The builder stores structured content blocks and renders them through published builder templates.

Available blocks:

- RichText - editor-based content.
- SingleImg - one image with title, alt, and link.
- Image and Text - image with rich text.
- YouTube - video iframe URL.
- Quote - image, author, and quote text.
- Note - short text note.
- ArticlePreview - link to another publication.
- Poll - embedded poll.
- Slider - ordered slides.
- Accordion - ordered panels with rich text.
- File - file link with icon and title.

Blocks can be reordered, duplicated by adding new blocks, and removed where allowed. Nested blocks such as Slider slides and Accordion panels always keep at least one item.

## Settings

Configuration is split into:

- **Base settings** - enable or disable ratings, authors, tags, tag texts, comments, topics, features, polls, and type filters.
- **Manager panel** - main menu visibility, menu order, rich text editor, and legacy TinyMCE5 theme name.
- **Types** - publication types and per-type fields.

The first `article` type is protected. Additional types can be added and removed only when they are not used by existing publications.

## Rich Text Editor

The setting **Editor** controls which editor sArticles uses:

- **System** - uses the Evolution CMS `which_editor` setting.
- **eTinyMCE** - forces eTinyMCE in sArticles forms.
- **dTuiEditor** - forces dTui.editor in sArticles forms.
- Any other editor registered with `OnRichTextEditorRegister`.

The small per-field editor selector is hidden in sArticles forms by design.

## SEO And Languages

If `sSeo` is installed, the article editor includes SEO fields. If `sLang` is also installed, SEO data is stored per language. Without sLang, SEO data is stored in the standalone base SEO flow.

If `sLang` is installed, article fields and builder content are shown per configured language.

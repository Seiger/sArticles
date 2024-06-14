<?php
/**
 * Articles management module
 */

use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\SiteTmplvar;
use EvolutionCMS\Models\SiteTmplvarTemplate;
use EvolutionCMS\Models\UserAttribute;
use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Facades\sArticles;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticleComment;
use Seiger\sArticles\Models\sArticlesAuthor;
use Seiger\sArticles\Models\sArticlesCategory;
use Seiger\sArticles\Models\sArticlesFeature;
use Seiger\sArticles\Models\sArticlesPoll;
use Seiger\sArticles\Models\sArticlesTag;
use Seiger\sArticles\Models\sArticleTranslate;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE != 'true') die("No access");

$sArticlesController = new sArticlesController();
$linkType = request()->has('type') ? '&type=' . request()->type : '';
$data['editor'] = '';
$data['tabs'] = [];
$data['get'] = request()->get ?? "articles";
$data['sArticlesController'] = $sArticlesController;
$data['lang_default'] = $defaultLng = $sArticlesController->langDefault();
$data['url'] = $sArticlesController->url;
$data['linkType'] = $linkType;
$data['checkType'] = request()->type ?? "article";

switch ($data['get']) {
    default:
        $checkType = request()->type ?? "article";
        $data['tabs'] = ['articles'];
        if (sArticles::config('general.authors_on', 1) == 1) {
            $data['tabs'][] = 'authors';
        }
        if (sArticles::config('general.tags_on', 1) == 1) {
            $data['tabs'][] = 'tags';
        }
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'comments';
        }
        if (evo()->getConfig('sart_polls_on', 1) == 1) {
            $data['tabs'][] = 'polls';
        }
        if (evo()->getConfig('sart_categories_on', 1) == 1) {
            $data['tabs'][] = 'categories';
        }
        if (evo()->hasPermission('settings')) {
            if (sArticles::config('general.features_on', 1) == 1) {
                $data['tabs'][] = 'features';
            }
            $data['tabs'][] = 'settings';
        }
        $data['checkType'] = $checkType;
        break;
    case 'comments':
        $data['tabs'] = ['articles'];
        if (sArticles::config('general.authors_on', 1) == 1) {
            $data['tabs'][] = 'authors';
        }
        if (sArticles::config('general.tags_on', 1) == 1) {
            $data['tabs'][] = 'tags';
        }
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'comments';
        }
        if (evo()->getConfig('sart_polls_on', 0) == 1) {
            $data['tabs'][] = 'polls';
        }
        if (evo()->getConfig('sart_categories_on', 1) == 1) {
            $data['tabs'][] = 'categories';
        }
        if (evo()->hasPermission('settings')) {
            if (sArticles::config('general.features_on', 1) == 1) {
                $data['tabs'][] = 'features';
            }
            $data['tabs'][] = 'settings';
        }
        $comments = sArticles::comments(50);
        $data['comments'] = $comments;
        $data['articles'] = sArticle::whereIn('id', $comments->pluck('article_id')->toArray())->get()->mapWithKeys(function ($item) {
            return [$item->id => $item];
        })->all();
        $data['usersComments'] = UserAttribute::whereIn('internalKey', $comments->pluck('user_id')->toArray())->get()->mapWithKeys(function ($item) {
            return [$item->internalKey => $item];
        })->all();
        break;
    case "article_comments":
        $checkType = request()->type ?? "article";
        $data['tabs'] = ['article', 'content'];
        $data['article_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['content_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['tvs_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['article'] = sArticles::getArticle(request()->i);
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'article_comments';
            $data['article_comments_url'] = '&i=' . request()->i;
        }
        $template = SiteContent::find(evo()->getConfig('sart_blank', 0))->template ?? null;
        if (request()->i && $template && SiteTmplvarTemplate::whereTemplateid($template)->first()) {
            $data['tabs'][] = 'tvs';
            $data['tvs_url'] = '&i='.request()->i;
        }
        $comments = sArticles::comments(10, [request()->i]);
        $data['usersComments'] = UserAttribute::whereIn('internalKey', $comments->pluck('user_id')->toArray())->get()->mapWithKeys(function ($item) {
            return [$item->internalKey => $item];
        })->all();
        $data['comments'] = $comments;
        $data['generalTabName'] = sArticles::config('types.'.$checkType.'.name', __('sArticles::global.article'));
        $data['checkType'] = $checkType;
        break;
    case 'commentDelete':
        sArticleComment::where('comid', (int)request()->i)->delete();
        $get = '&get='.( request()->get('article') ? 'article_comments&i='.request()->get('article') : 'comments' );
        $page = request()->get('page') ?'&page='.request()->get('page') : '';
        return header('Location: ' . $sArticlesController->url . $get . $page . $linkType);
    case "article":
        $checkType = request()->type ?? "article";
        $data['tabs'] = ['article', 'content'];
        $data['article'] = sArticles::getArticle(request()->i);
        $data['article_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['content_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['tvs_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['features'] = sArticlesFeature::orderBy('base')->get();
        $data['categories'] = sArticlesCategory::orderBy('base')->get();
        $data['tags'] = sArticlesTag::orderBy('base')->get();
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'article_comments';
            $data['article_comments_url'] = '&i=' . request()->i;
        }
        $template = SiteContent::find(evo()->getConfig('sart_blank', 0))->template ?? null;
        if (request()->i && $template && SiteTmplvarTemplate::whereTemplateid($template)->first()) {
            $data['tabs'][] = 'tvs';
        }
        $data['checkType'] = $checkType;
        break;
    case "articleSave":
        $requestId = (int)request()->article;
        $publishedAt = request()->published_at;
        if (empty($publishedAt) || $publishedAt == '0000-00-00 00:00:00') {
            $publishedAt = evo()->now()->toDateTimeString();
        }
        $article = sArticle::where('s_articles.id', $requestId)->firstOrNew();
        $alias = request()->alias;
        if (empty($alias)) {
            $translate = sArticleTranslate::whereArticle($requestId)->whereIn('lang', ['en', $defaultLng, 'base'])->orderByRaw('FIELD(lang, "en", "'.$defaultLng.'", "base")')->first();
            if ($translate) {
                $alias = $translate->pagetitle;
            } else {
                $alias = $requestId;
            }
        }
        $votes = data_is_json($article->votes ?? '', true);
        if (!$votes) {
            $votes = [];
            $votes['total'] = 1;
            $votes['1'] = 0;
            $votes['2'] = 0;
            $votes['3'] = 0;
            $votes['3'] = 0;
            $votes['4'] = 0;
            $votes['5'] = 1;
        }
        $article->published = (int)request()->published;
        $article->parent = (int)request()->parent;
        $article->author_id = (int)request()->author_id;
        $article->alias = $sArticlesController->validateAlias($alias, request()->article);
        $article->position = (int)request()->position;
        $article->cover = request()->cover;
        $article->type = request()->type ?? "article";
        $article->relevants = json_encode(request()->relevants);
        $article->votes = json_encode($votes);
        $article->published_at = $publishedAt;
        $article->save();
        $article->categories()->sync(request()->categories ?? []);
        $article->features()->sync(request()->features ?? []);
        $article->tags()->sync([]);
        $article->tags()->sync(array_unique(request()->input('tags', [])));
        $sArticlesController->setArticlesListing();
        $back = str_replace(['&i=0', '&type=article'], ['&i=' . $article->id, '&type=' . $article->type], (request()->back ?? '&get=articles'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "articleDelete":
        DB::table('s_articles')->whereId((int)request()->i)->delete();
        DB::table('s_article_translates')->whereArticle((int)request()->i)->delete();
        $sArticlesController->setArticlesListing();
        $back = '&get=articles';
        return header('Location: ' . $sArticlesController->url . $back . $linkType);
    case "content":
        $checkType = request()->type ?? "article";
        $data['tabs'] = ['article', 'content'];
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'article_comments';
            $data['article_comments_url'] = '&i=' . request()->i;
        }
        $template = SiteContent::find(evo()->getConfig('sart_blank', 0))->template ?? null;
        if (request()->i && $template && SiteTmplvarTemplate::whereTemplateid($template)->first()) {
            $data['tabs'][] = 'tvs';
        }
        $content = sArticleTranslate::whereArticle((int)request()->i)->whereLang(request()->lang)->first();
        if (!$content && request()->lang == $sArticlesController->langDefault()) {
            $content = sArticleTranslate::whereArticle((int)request()->i)->whereLang('base')->first();
        }
        $data['article_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['content_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['tvs_url'] = '&type='.$checkType.'&i='.request()->i;
        $data['constructor'] = [];
        $editor = [];
        $buttons = [];
        $elements = [];
        $templates = [];
        $fields = glob(MODX_BASE_PATH . 'assets/modules/sarticles/builder/*/config.php');
        View::getFinder()->setPaths([MODX_BASE_PATH . 'assets/modules/sarticles/builder']);

        if (count($fields)) {
            foreach ($fields as $idx => $field) {
                if (is_file(dirname($field).'/template.blade.php')) {
                    $template = basename(dirname($field));
                    $field = require $field;

                    if ((int)$field['active']) {
                        $id = $field['id'];
                        $templates[$id] = $template;
                        $order = ($field['order'] ?? ($idx + 25));
                        while (isset($buttons[$order])) {
                            $order++;
                        }
                        $buttons[$order] = $sArticlesController->view('partials.addBlockButton', compact(['id', 'field']))->render();
                        $elements[] = view($template . '.template', compact(['id']))->render();
                        if (strtolower($field['type']) == 'richtext') {
                            $richtexts[$id] = [];
                        }
                    }
                }
            }
        }
        ksort($buttons);

        $chunks = [];
        $builder = data_is_json($content->builder ?? '', true);
        if (is_array($builder) && count($builder)) {
            foreach ($builder as $i => $item) {
                $key = array_key_first($item);
                if (isset($templates[$key])) {
                    $id = $key . $i;
                    $value = $item[$key];
                    $chunks[] = view($templates[$key] . '.template', compact(['i', 'id', 'value']))->render();
                    if (isset($richtexts[$key])) {
                        $richtexts[$key][] = $i;
                    }
                    if (isset($value['richtext']) && is_array($value['richtext']) && count($value['richtext'])) {
                        foreach (range(1, count($value['richtext'])) as $rnum) {
                            $richtexts[$key][] = $i . $rnum;
                        }
                    }
                }
            }
        }

        foreach ($richtexts as $key => $items) {
            if (!count($items)) {
                $items[] = 1;
            }

            foreach ($items as $item) {
                $editor[] = $key . $item;
            }
        }

        $constructor = data_is_json($content->constructor ?? '', true);
        $data['constructor'] = $constructor;
        $settings = require MODX_BASE_PATH . 'core/custom/config/seiger/settings/sArticles.php';
        if (is_array($settings)) {
            foreach ($settings as $setting) {
                $data['constructor'][$setting['key']] = array_merge($setting, ['value' => ($constructor[$setting['key']] ?? '')]);
                if (strtolower($setting['type']) == 'richtext') {
                    $editor[] = $setting['key'];
                }
            }
        }
        if (sArticles::config('types.'.$checkType.'.visual_editor_introtext', 0) == 1) {
            $editor[] = 'introtext';
        }
        if (sArticles::config('types.'.$checkType.'.visual_editor_description', 0) == 1) {
            $editor[] = 'description';
        }
        $data['content'] = $content;
        $data['editor'] = $sArticlesController->textEditor(implode(',', $editor));
        $data['buttons'] = $buttons;
        $data['elements'] = $elements;
        $data['chunks'] = $chunks;
        $data['generalTabName'] = sArticles::config('types.'.$checkType.'.name', __('sArticles::global.article'));
        $data['checkType'] = $checkType;
        break;
    case "contentSave":
        $contentField = '';
        $renders = [];
        $fields = glob(MODX_BASE_PATH . 'assets/modules/sarticles/builder/*/config.php');
        View::getFinder()->setPaths([MODX_BASE_PATH . 'assets/modules/sarticles/builder']);

        if (count($fields)) {
            foreach ($fields as $field) {
                $render = str_replace('config.php', 'render.blade.php', $field);
                if (is_file($render)) {
                    $render = basename(dirname($render));
                    $field = require $field;
                    $id = $field['id'];
                    $renders[$id] = $render;
                }
            }
        }

        $contentBuilder = request()->input('builder', '');
        if (is_array($contentBuilder) && count($contentBuilder)) {
            foreach ($contentBuilder as $position => $item) {
                $id = array_key_first($item);
                if (isset($renders[$id])) {
                    $value = $item[$id];
                    $contentField .= view($renders[$id] . '.render', compact(['id', 'value']))->render();
                }
            }
        }
        $contentField = str_replace([chr(9), chr(10), chr(13), '  '], '', $contentField);

        $content = sArticleTranslate::whereArticle((int)request()->article)->whereLang(request()->lang)->firstOrNew();
        if (!$content->tid) {
            $content->article = (int)request()->article;
            $content->lang = request()->lang;
        }
        $content->pagetitle = request()->pagetitle;
        $content->longtitle = request()->input('longtitle', '');
        $content->introtext = request()->input('introtext', '');
        $content->description = request()->input('description', '');
        $content->content = $contentField;
        $content->seotitle = request()->seotitle;
        $content->seodescription = request()->seodescription;
        $content->seorobots = request()->seorobots;
        $content->builder = json_encode(array_values(request()->builder ?? []));
        $content->constructor = json_encode(request()->constructor);
        if ($content->article == 0) {
            $article = new sArticle();
            $article->alias = $sArticlesController->validateAlias(request()->pagetitle);
            $article->type = request()->type ?? "article";
            $article->save();
            $content->article = $article->id;
        }
        $content->save();
        $sArticlesController->setArticlesListing();
        $back = str_replace('&i=0', '&i=' . $content->article, (request()->back ?? '&get=articles'));
        return header('Location: ' . $sArticlesController->url . $back . $linkType);
    case "authors":
        $sArticlesController->setModifyTables('authors');
        $data['tabs'] = ['articles'];
        if (sArticles::config('general.authors_on', 1) == 1) {
            $data['tabs'][] = 'authors';
        }
        if (sArticles::config('general.tags_on', 1) == 1) {
            $data['tabs'][] = 'tags';
        }
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'comments';
        }
        if (evo()->getConfig('sart_polls_on', 0) == 1) {
            $data['tabs'][] = 'polls';
        }
        if (evo()->getConfig('sart_categories_on', 1) == 1) {
            $data['tabs'][] = 'categories';
        }
        if (evo()->hasPermission('settings')) {
            if (sArticles::config('general.features_on', 1) == 1) {
                $data['tabs'][] = 'features';
            }
            $data['tabs'][] = 'settings';
        }
        $data['authors'] = sArticlesAuthor::orderBy('base_name')->get();
        $data['editor'] = '';
        break;
    case "addAuthor":
        $responce = ['status' => 0];
        $name = request()->get('name') ?? '';
        $lastname = request()->get('lastname') ?? '';
        $office = request()->get('office') ?? '';
        if (!empty($name) && $name = trim($name)) {
            $author = sArticlesAuthor::where($defaultLng.'_name', $name)->first();
            if (!$author) {
                $author = new sArticlesAuthor();
                $alias = $sArticlesController->validateAlias(Str::slug($name), $author->autid, 'author');
                $author->alias = $alias;
                $author->base_name = $name;
                $author->base_lastname = $lastname;
                $author->base_office = $office;
                $author->{$defaultLng.'_name'} = $name;
                $author->{$defaultLng.'_lastname'} = $lastname;
                $author->{$defaultLng.'_office'} = $office;
                $author->save();
                $responce['status'] = 1;
            }
        }
        die(json_encode($responce));
    case "authorImageChange":
        $author = sArticlesAuthor::find(request()->input('author', 0));
        if ($author) {
            $author->image = request()->input('image', '');
            $author->update();
        }
        break;
    case "authorGetTexts":
        $texts = sArticlesAuthor::find($_POST['dataId'])->toArray();
        die(json_encode($texts ?? []));
    case "authorSetAlias":
        $responce = ['status' => 0];
        $author = sArticlesAuthor::find($_POST['dataId']);
        if ($author) {
            $alias = $sArticlesController->validateAlias($_POST['alias'], $author->autid, 'author');
            $author->alias = $alias;
            $author->update();
            $responce['status'] = 1;
        }
        die(json_encode($responce));
    case "authorSetGender":
        $responce = ['status' => 0];
        $author = sArticlesAuthor::find($_POST['dataId']);
        if ($author) {
            $author->gender = $_POST['gender'];
            $author->update();
            $responce['status'] = 1;
        }
        die(json_encode($responce));
    case "authorTextUpdate":
        $result = false;
        $author = sArticlesAuthor::find($_POST['dataId']);
        if ($author) {
            if ($_POST['target'] == $sArticlesController->langDefault().'_name') {
                $author->base_name = $_POST['value'];
            }
            if ($_POST['target'] == $sArticlesController->langDefault().'_lastname') {
                $author->base_lastname = $_POST['value'];
            }
            if ($_POST['target'] == $sArticlesController->langDefault().'_office') {
                $author->base_office = $_POST['value'];
            }
            $author->{$_POST['target']} = $_POST['value'];
            $author->update();
            $result = true;
        }
        die($result);
    case "authorDelete":
        sArticlesAuthor::find(request()->input('i', 0))->delete();
        $back = '&get=authors' . $linkType;
        return header('Location: ' . $sArticlesController->url . $back);
    case "poll":
        $data['tabs'] = ['poll'];
        $data['poll_url'] = '&i='.request()->i;
        $data['poll'] = $poll = sArticlesPoll::find(request()->i);
        if ($poll) {
            $data['question'] = $poll->question;
            $data['answers'] = data_is_json($poll->answers ?? '', true);
            $data['votes'] = data_is_json($poll->votes ?? '', true);
        }
        break;
    case "pollSave":
        $answers = [];
        if (is_array(request()->answers)) {
            $firstArrayKey = array_key_first(request()->answers);
            if (is_array(request()->answers[$firstArrayKey]) && count(request()->answers[$firstArrayKey])) {
                foreach (request()->answers[$firstArrayKey] as $key => $answer) {
                    foreach ($sArticlesController->langList() as $item) {
                        $answers[$key][$item] = request()->answers[$item][$key];
                    }
                }
            }
        }
        $poll = sArticlesPoll::find(request()->poll);
        if (!$poll) {
            $poll = new sArticlesPoll();
        }
        $poll->question = request()->question;
        $poll->answers = json_encode($answers);
        $votes = data_is_json($poll->votes ?? '', true);
        if (!$votes) {
            $votes = [];
            $votes['total'] = 0;
        }
        if (count($answers)) {
            foreach ($answers as $key => $answer) {
                if (!isset($votes[$key])) {
                    $votes[strval($key)] = 0;
                }
            }
        }
        $poll->votes = json_encode($votes);
        $poll->save();
        Cache::forget('sArticles-polls-list');
        $back = str_replace('&i=0', '&i=' . $poll->pollid, (request()->back ?? '&get=polls'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "pollDelete":
        $poll = sArticlesPoll::find(request()->i);
        if ($poll) {
            $poll->delete();
        }
        Cache::forget('sArticles-polls-list');
        $back = '&get=polls';
        return header('Location: ' . $sArticlesController->url . $back . $linkType);
    case "tvs":
        $data['tabs'] = ['article', 'content'];
        if (evo()->getConfig('sart_comments_on', 1) == 1)
        {
            $data['tabs'][] = 'article_comments';
            $data['article_comments_url'] = '&i=' . request()->i;
        }
        $data['tabs'][] = 'tvs';
        $data['article'] = sArticles::getArticle(request()->i);
        $data['article_url'] = '&i='.request()->i;
        $data['content_url'] = '&i='.request()->i;

        $template = SiteContent::find(evo()->getConfig('sart_blank', 0))->template ?? 0;
        $data['tvs'] = SiteTmplvar::query()
            ->select('site_tmplvars.*', 'site_tmplvar_templates.rank as tvrank', 'site_tmplvar_templates.rank', 'site_tmplvars.id', 'site_tmplvars.rank')
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->orderBy('site_tmplvar_templates.rank', 'ASC')
            ->orderBy('site_tmplvars.rank', 'ASC')
            ->orderBy('site_tmplvars.id', 'ASC')
            ->whereNotIn('name', ['menu_footer', 'menu_main'])
            ->where('site_tmplvar_templates.templateid', $template)
            ->get();
        $data['tvValues'] = data_is_json($data['article']->tmplvars, true) ?? [];
        break;
    case "tvsSave":
        $article = sArticles::getArticle((int)request()->article);
        $template = SiteContent::find(evo()->getConfig('sart_blank', 0))->template ?? 0;
        $tvs = SiteTmplvar::query()
            ->select('site_tmplvars.*', 'site_tmplvar_templates.rank as tvrank', 'site_tmplvar_templates.rank', 'site_tmplvars.id', 'site_tmplvars.rank')
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->orderBy('site_tmplvar_templates.rank', 'ASC')
            ->orderBy('site_tmplvars.rank', 'ASC')
            ->orderBy('site_tmplvars.id', 'ASC')
            ->where('site_tmplvar_templates.templateid', $template)
            ->get();
        $tvValues = [];
        if ($tvs) {
            foreach ($tvs as $tv) {
                if (request()->has('tv'.$tv->id)) {
                    $value = request()->input('tv'.$tv->id);
                    if (is_array($value)) {
                        $value = implode('||', $value);
                    }
                    $tvValues[$tv->name] = $value;
                }
            }
        }
        $article->tmplvars = json_encode($tvValues);
        $article->save();
        $back = str_replace('&i=0', '&i=' . $article->id, (request()->back ?? '&get=tvs'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "categories":
        $sArticlesController->setModifyTables('categories');
        $data['tabs'] = ['articles'];
        if (sArticles::config('general.authors_on', 1) == 1) {
            $data['tabs'][] = 'authors';
        }
        if (sArticles::config('general.tags_on', 1) == 1) {
            $data['tabs'][] = 'tags';
        }
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'comments';
        }
        if (evo()->getConfig('sart_polls_on', 0) == 1) {
            $data['tabs'][] = 'polls';
        }
        if (evo()->getConfig('sart_categories_on', 1) == 1) {
            $data['tabs'][] = 'categories';
        }
        if (evo()->hasPermission('settings')) {
            if (sArticles::config('general.features_on', 1) == 1) {
                $data['tabs'][] = 'features';
            }
            $data['tabs'][] = 'settings';
        }
        $data['categories'] = sArticlesCategory::orderBy('position')->get();
        break;
    case "addCategory":
        $responce = ['status' => 0];
        $value = request()->get('value') ?? '';
        if (!empty($value) && $value = trim($value)) {
            $category = sArticlesCategory::where($defaultLng, $value)->first();
            if (!$category) {
                $category = new sArticlesCategory();
                $category->alias = Str::slug($value);
                $category->base = $value;
                $category->{$defaultLng} = $value;
                $category->save();
                $responce['status'] = 1;
            }
        }
        die(json_encode($responce));
    case "сategorySetAlias":
        $responce = ['status' => 0];
        $category = sArticlesCategory::find($_POST['catid']);
        if ($category) {
            $alias = $sArticlesController->validateAlias($_POST['alias'], $category->catid, 'category');
            $category->alias = $alias;
            $category->update();
            $responce['status'] = 1;
        }
        die(json_encode($responce));
    case "сategoryTranslate":
        $result = '';
        $langDefault = $sArticlesController->langDefault();
        $category = sArticlesCategory::find($_POST['source']);
        if ($category) {
            $text = $category[$langDefault];
            $result = $sArticlesController->googleTranslate($text, $langDefault, $_POST['target']);
        }

        if (trim($result)) {
            $category->{$_POST['target']} = $result;
            $category->save();
        }
        die($result);
    case "сategoryTranslateUpdate":
        $result = false;
        $category = sArticlesCategory::find($_POST['source']);
        if ($category) {
            if ($_POST['target'] == $sArticlesController->langDefault()) {
                $category->base = $_POST['value'];
            }
            $category->{$_POST['target']} = $_POST['value'];
            $category->update();
            $result = true;
        }
        die($result);
    case "сategoryGetTexts":
        $texts = sArticlesCategory::whereCatid($_POST['catId'])->first()->toArray();
        die(json_encode($texts ?? []));
    case "categoryImageChange":
        $result = false;
        $category = sArticlesCategory::whereCatid(request()->input('catId', 0))->first();
        if ($category) {
            $category->cover = request()->input('image', '');
            $category->update();
            $result = true;
        }
        die($result);
    case "сategoryDelete":
        DB::table('s_articles_categories')->where('catid', (int)request()->i)->delete();
        $back = '&get=categories';
        return header('Location: ' . $sArticlesController->url . $back);
    case "features":
        $sArticlesController->setModifyTables('features');
        $data['tabs'] = ['articles'];
        if (sArticles::config('general.authors_on', 1) == 1) {
            $data['tabs'][] = 'authors';
        }
        if (sArticles::config('general.tags_on', 1) == 1) {
            $data['tabs'][] = 'tags';
        }
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'comments';
        }
        if (evo()->getConfig('sart_polls_on', 0) == 1) {
            $data['tabs'][] = 'polls';
        }
        if (evo()->getConfig('sart_categories_on', 1) == 1) {
            $data['tabs'][] = 'categories';
        }
        if (evo()->hasPermission('settings')) {
            if (sArticles::config('general.features_on', 1) == 1) {
                $data['tabs'][] = 'features';
            }
            $data['tabs'][] = 'settings';
        } else {
            $back = request()->back ?? '&get=articles';
            return header('Location: ' . $sArticlesController->url . $back . $linkType);
        }
        $data['features'] = sArticlesFeature::orderBy('position')->get();
        break;
    case "featuresSave":
        if (request()->filled('features')) {
            $features = request()->features;
            $sArticlesFeatures = sArticlesFeature::all();
            if (count($features)) {
                $values = [];
                $fields = array_keys($features);
                foreach ($features['fid'] as $idx => $fid) {
                    $array = [];
                    foreach ($fields as $field) {
                        $array[$field] = $features[$field][$idx];
                    }
                    if (count(array_diff($array, [""]))) {
                        $array['position'] = $idx;
                        if ($sArticlesController->langDefault() != 'base') {
                            $array['base'] = $array[$sArticlesController->langDefault()];
                        }
                        $array['alias'] = $sArticlesController->validateAlias(trim($array['alias'] ?? '') ?: $array['base'], $array['fid'] ?? 0, 'feature');
                        unset($array['fid']);
                        $values[$array['alias']] = $array;
                    }
                }
                foreach ($sArticlesFeatures as $sArticlesFeature) {
                    if (isset($values[$sArticlesFeature->alias])) {
                        foreach ($values[$sArticlesFeature->alias] as $field => $item) {
                            $sArticlesFeature->{$field} = $item;
                        }
                        $sArticlesFeature->update();
                        unset($values[$sArticlesFeature->alias]);
                    } else {
                        $sArticlesFeature->delete();
                    }
                }
                if (count($values)) {
                    foreach ($values as $value) {
                        $sArticlesFeature = new sArticlesFeature();
                        foreach ($value as $field => $item) {
                            $sArticlesFeature->{$field} = $item;
                        }
                        $sArticlesFeature->save();
                    }
                }
            } else {
                foreach ($sArticlesFeatures as $sArticlesFeature) {
                    $sArticlesFeature->delete();
                }
            }
        }
        $back = request()->back ?? '&get=features';
        return header('Location: ' . $sArticlesController->url . $back . $linkType);
    case "settings":
        $data['tabs'] = ['articles'];
        if (sArticles::config('general.authors_on', 1) == 1) {
            $data['tabs'][] = 'authors';
        }
        if (sArticles::config('general.tags_on', 1) == 1) {
            $data['tabs'][] = 'tags';
        }
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'comments';
        }
        if (evo()->getConfig('sart_polls_on', 0) == 1) {
            $data['tabs'][] = 'polls';
        }
        if (evo()->getConfig('sart_categories_on', 1) == 1) {
            $data['tabs'][] = 'categories';
        }
        if (evo()->hasPermission('settings')) {
            if (sArticles::config('general.features_on', 1) == 1) {
                $data['tabs'][] = 'features';
            }
            $data['tabs'][] = 'settings';
        } else {
            $back = request()->back ?? '&get=articles';
            return header('Location: ' . $sArticlesController->url . $back . $linkType);
        }
        break;
    case "settingsSave":
        $tbl = evo()->getDatabase()->getFullTableName('system_settings');
        if (request()->has('parent') && request()->parent != evo()->getConfig('sart_blank')) {
            $resource = request()->parent;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_blank', '{$resource}')");
            evo()->setConfig('sart_blank', $resource);
        }
        if (request()->has('comments_on') && request()->comments_on != evo()->getConfig('sart_comments_on')) {
            $comments_on = request()->comments_on;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_comments_on', '{$comments_on}')");
            evo()->setConfig('sart_comments_on', $comments_on);
        }
        if (request()->has('rating_on') && request()->rating_on != evo()->getConfig('sart_rating_on')) {
            $rating_on = request()->rating_on;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_rating_on', '{$rating_on}')");
            evo()->setConfig('sart_rating_on', $rating_on);
        }
        if (request()->has('polls_on') && request()->polls_on != evo()->getConfig('sart_polls_on')) {
            $polls_on = request()->polls_on;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_polls_on', '{$polls_on}')");
            evo()->setConfig('sart_polls_on', $polls_on);
        }
        if (request()->has('categories_on') && request()->categories_on != evo()->getConfig('sart_categories_on')) {
            $categories_on = request()->categories_on;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_categories_on', '{$categories_on}')");
            evo()->setConfig('sart_categories_on', $categories_on);
        }
        if (request()->has('in_main_menu') && request()->in_main_menu != evo()->getConfig('sart_in_main_menu')) {
            $in_main_menu = request()->in_main_menu;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_in_main_menu', '{$in_main_menu}')");
            evo()->setConfig('sart_in_main_menu', $in_main_menu);
        }
        if (request()->has('main_menu_order') && request()->main_menu_order != evo()->getConfig('sart_main_menu_order')) {
            $main_menu_order = request()->main_menu_order;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_main_menu_order', '{$main_menu_order}')");
            evo()->setConfig('sart_main_menu_order', $main_menu_order);
        }
        if (request()->has('tag_texts_on') && request()->tag_texts_on != evo()->getConfig('sart_tag_texts_on')) {
            $tag_texts_on = request()->tag_texts_on;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_tag_texts_on', '{$tag_texts_on}')");
            evo()->setConfig('sart_tag_texts_on', $tag_texts_on);
        }
        if (request()->has('tinymce5_theme') && request()->tinymce5_theme != evo()->getConfig('sart_tinymce5_theme')) {
            $tinymce5_theme = request()->tinymce5_theme;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_tinymce5_theme', '{$tinymce5_theme}')");
            evo()->setConfig('sart_tinymce5_theme', $tinymce5_theme);
        }
        if (request()->has('seotitle') && request()->seotitle != evo()->getConfig('sart_name_seotitle')) {
            $seotitle = request()->seotitle;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_name_seotitle', '{$seotitle}')");
            evo()->setConfig('sart_name_seotitle', $seotitle);
        }
        if (request()->has('seodescription') && request()->seodescription != evo()->getConfig('sart_name_seodescription')) {
            $seodescription = request()->seodescription;
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('sart_name_seodescription', '{$seodescription}')");
            evo()->setConfig('sart_name_seodescription', $seodescription);
        }
        $keys = request()->input('settings.key', []);
        $settings = [];
        if (count($keys)) {
            foreach ($keys as $idx => $key) {
                $key = Str::slug($key);
                $key = Str::lower($key);
                $key = Str::replace('-', '_', $key);
                $settings[$key] = [
                    'key' => $key,
                    'name' => addslashes(request()->input('settings')['name'][$idx]),
                    'type' => request()->input('settings')['type'][$idx],
                ];
            }
        }

        $filters = ['general', 'types'];
        $all = request()->all();
        ksort($all);

        // Data array formation
        foreach ($filters as $filter) {
            foreach ($all as $key => $value) {
                if (str_starts_with($key, $filter . '__')) {
                    $key = str_replace($filter . '__', '', $key);
                    $key = explode('__', $key);
                    if (ctype_digit(strval($value))) {
                        $value = intval($value);
                    }
                    if (isset($key[1])) {
                        $settings[$filter][$key[0]][$key[1]] = $value;
                    } else {
                        $settings[$filter][$key[0]] = $value;
                    }
                }
            }
        }
        $sArticlesController->updateFileConfigs($settings);
        evo()->clearCache('full');
        sleep(5);
        $back = request()->back ?? '&get=settings';
        return header('Location: ' . $sArticlesController->url . $back . $linkType);
    case "tags":
        $sArticlesController->setModifyTables('tags');
        $data['tabs'] = ['articles'];
        if (sArticles::config('general.authors_on', 1) == 1) {
            $data['tabs'][] = 'authors';
        }
        if (sArticles::config('general.tags_on', 1) == 1) {
            $data['tabs'][] = 'tags';
        }
        if (evo()->getConfig('sart_comments_on', 1) == 1) {
            $data['tabs'][] = 'comments';
        }
        if (evo()->getConfig('sart_polls_on', 0) == 1) {
            $data['tabs'][] = 'polls';
        }
        if (evo()->getConfig('sart_categories_on', 1) == 1) {
            $data['tabs'][] = 'categories';
        }
        if (evo()->hasPermission('settings')) {
            if (sArticles::config('general.features_on', 1) == 1) {
                $data['tabs'][] = 'features';
            }
            $data['tabs'][] = 'settings';
        }
        $data['tags'] = sArticlesTag::orderBy($defaultLng)->get();
        $editor = "tagContent";
        $data['editor'] = $sArticlesController->textEditor($editor);
        break;
    case "addTag":
        $responce = ['status' => 0];
        $value = request()->get('value') ?? '';
        if (!empty($value) && $value = trim($value)) {
            $tag = sArticlesTag::where($defaultLng, $value)->first();
            if (!$tag) {
                $tag = new sArticlesTag();
                $tag->alias = Str::slug($value);
                $tag->base = $value;
                $tag->{$defaultLng} = $value;
                $tag->save();
                $responce['status'] = 1;
            }
        }
        die(json_encode($responce));
    case "tagGetTexts":
        $texts = sArticlesTag::whereTagid($_POST['tagId'])->first()->toArray();
        die(json_encode($texts ?? []));
    case "tagSetAlias":
        $responce = ['status' => 0];
        $tag = sArticlesTag::find($_POST['tagId']);
        if ($tag) {
            $alias = $sArticlesController->validateAlias($_POST['alias'], $tag->tagid, 'tag');
            $tag->alias = $alias;
            $tag->update();
            $responce['status'] = 1;
        }
        die(json_encode($responce));
    case "tagSetTexts":
        $tag = sArticlesTag::find($_POST['tagId']);
        foreach ($_POST['texts'] as $field => $text) {
            $tag->{$_POST['lang'] . '_' . $field} = $text;
            if ($_POST['lang'] == $defaultLng) {
                $tag->{'base_' . $field} = $text;
            }
        }
        $result =  $tag->update();
        die($result);
    case "tagTranslate":
        $result = $sArticlesController->getAutomaticTranslateTag($_POST['source'], $_POST['target']);
        die($result);
    case "tagTranslateUpdate":
        $result = $sArticlesController->updateTranslateTag($_POST['source'], $_POST['target'], $_POST['value']);
        die($result);
    case "tagDelete":
        DB::table('s_articles_tags')->where('tagid', (int)request()->i)->delete();
        $back = '&get=tags';
        return header('Location: ' . $sArticlesController->url . $back . $linkType);
}

echo $sArticlesController->view('index', $data);

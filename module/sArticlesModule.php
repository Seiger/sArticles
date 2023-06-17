<?php
/**
 * Articles management module
 */

use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\SiteTmplvar;
use EvolutionCMS\Models\SiteTmplvarTemplate;
use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticlesFeature;
use Seiger\sArticles\Models\sArticlesTag;
use Seiger\sArticles\Models\sArticleTranslate;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE != 'true') die("No access");

$sArticlesController = new sArticlesController();
$data['editor'] = '';
$data['tabs'] = [];
$data['get'] = request()->get ?? "articles";
$data['sArticlesController'] = $sArticlesController;
$data['lang_default'] = $defaultLng = $sArticlesController->langDefault();
$data['url'] = $sArticlesController->url;

switch ($data['get']) {
    default:
        $data['tabs'] = ['articles', 'tags'];
        if (evo()->hasPermission('settings')) {
            $data['tabs'][] = 'features';
            $data['tabs'][] = 'settings';
        }
        break;
    case "article":
        $data['tabs'] = ['article', 'content'];
        $data['article'] = sArticles::getArticle(request()->i);
        $data['article_url'] = '&i='.request()->i;
        $data['content_url'] = '&i='.request()->i;
        $data['tvs_url'] = '&i='.request()->i;
        $data['features'] = sArticlesFeature::orderBy('base')->get();
        $template = SiteContent::find(evo()->getConfig('s_articles_resource', 0))->template ?? null;
        if ($template && SiteTmplvarTemplate::whereTemplateid($template)->first()) {
            $data['tabs'][] = 'tvs';
        }
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
        $article->published = (int)request()->published;
        $article->parent = (int)request()->parent;
        $article->author = (int)request()->author;
        $article->alias = $sArticlesController->validateAlias($alias, request()->article);
        $article->position = (int)request()->position;
        $article->cover = request()->cover;
        $article->published_at = $publishedAt;
        $article->save();
        $article->features()->sync(request()->features ?? []);
        $sArticlesController->setArticlesListing();
        $back = str_replace('&i=0', '&i=' . $article->id, (request()->back ?? '&get=articles'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "articleDelete":
        DB::table('s_articles')->whereId((int)request()->i)->delete();
        DB::table('s_article_translates')->whereArticle((int)request()->i)->delete();
        $sArticlesController->setArticlesListing();
        $back = '&get=articles';
        return header('Location: ' . $sArticlesController->url . $back);
    case "content":
        $data['tabs'] = ['article', 'content'];
        $template = SiteContent::find(evo()->getConfig('s_articles_resource', 0))->template ?? null;
        if ($template && SiteTmplvarTemplate::whereTemplateid($template)->first()) {
            $data['tabs'][] = 'tvs';
        }
        $content = sArticleTranslate::whereArticle((int)request()->i)->whereLang(request()->lang)->first();
        if (!$content && request()->lang == $sArticlesController->langDefault()) {
            $content = sArticleTranslate::whereArticle((int)request()->i)->whereLang('base')->first();
        }
        $data['article_url'] = '&i=' . request()->i;
        $data['content_url'] = '&i=' . request()->i;
        $data['tvs_url'] = '&i='.request()->i;
        $data['constructor'] = [];
        $constructor = data_is_json($content->constructor ?? '', true);
        $settings = require MODX_BASE_PATH . 'core/custom/config/seiger/settings/sArticles.php';
        $editor = "introtext,content";
        if (is_array($settings)) {
            foreach ($settings as $setting) {
                $data['constructor'][] = array_merge($setting, ['value' => ($constructor[$setting['key']] ?? '')]);
                if ($setting['type'] == 'RichText') {
                    $editor .= ",".$setting['key'];
                }
            }
        }
        $data['editor'] = $sArticlesController->textEditor($editor);
        $data['content'] = $content;
        break;
    case "contentSave":
        $content = sArticleTranslate::whereArticle((int)request()->article)->whereLang(request()->lang)->firstOrNew();
        if (!$content->tid) {
            $content->article = (int)request()->article;
            $content->lang = request()->lang;
        }
        $content->pagetitle = request()->pagetitle;
        $content->longtitle = request()->longtitle;
        $content->introtext = request()->introtext;
        $content->content = request()->input('content');
        $content->seotitle = request()->seotitle;
        $content->seodescription = request()->seodescription;
        $content->seorobots = request()->seorobots;
        $content->constructor = json_encode(request()->constructor);
        if ($content->article == 0) {
            $article = new sArticle();
            $article->alias = $sArticlesController->validateAlias(request()->pagetitle);
            $article->save();
            $content->article = $article->id;
        }
        $content->save();
        $back = str_replace('&i=0', '&i=' . $content->article, (request()->back ?? '&get=articles'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "tvs":
        $data['tabs'] = ['article', 'content', 'tvs'];
        $data['article'] = sArticles::getArticle(request()->i);
        $data['article_url'] = '&i='.request()->i;
        $data['content_url'] = '&i='.request()->i;
        $template = SiteContent::find(evo()->getConfig('s_articles_resource', 0))->template ?? 0;
        $data['tvs'] = SiteTmplvar::query()
            ->select('site_tmplvars.*', 'site_tmplvar_templates.rank as tvrank', 'site_tmplvar_templates.rank', 'site_tmplvars.id', 'site_tmplvars.rank')
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->orderBy('site_tmplvar_templates.rank', 'ASC')
            ->orderBy('site_tmplvars.rank', 'ASC')
            ->orderBy('site_tmplvars.id', 'ASC')
            ->where('site_tmplvar_templates.templateid', $template)
            ->get();
        $data['tvValues'] = data_is_json($data['article']->tmplvars, true) ?? [];
        break;
    case "tvsSave":
        $article = sArticles::getArticle((int)request()->article);
        $template = SiteContent::find(evo()->getConfig('s_articles_resource', 0))->template ?? 0;
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
    case "features":
        $sArticlesController->setModifyTables('features');
        $data['tabs'] = ['articles', 'tags'];
        if (evo()->hasPermission('settings')) {
            $data['tabs'][] = 'features';
            $data['tabs'][] = 'settings';
        } else {
            $back = request()->back ?? '&get=articles';
            return header('Location: ' . $sArticlesController->url . $back);
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
        return header('Location: ' . $sArticlesController->url . $back);
    case "settings":
        $data['tabs'] = ['articles', 'tags'];
        if (evo()->hasPermission('settings')) {
            $data['tabs'][] = 'features';
            $data['tabs'][] = 'settings';
        } else {
            $back = request()->back ?? '&get=articles';
            return header('Location: ' . $sArticlesController->url . $back);
        }
        break;
    case "settingsSave":
        if (request()->has('parent') && request()->parent != evo()->getConfig('s_articles_resource')) {
            $resource = request()->parent;
            $tbl = evo()->getDatabase()->getFullTableName('system_settings');
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('s_articles_resource', '{$resource}')");
            evo()->setConfig('s_articles_resource', $resource);
            evo()->clearCache('full');
        }

        $keys = request()->input('settings.key', []);
        $settings = [];
        if (count($keys)) {
            foreach ($keys as $idx => $key) {
                $key = Str::slug($key);
                $key = Str::lower($key);
                $settings[$key] = [
                    'key' => $key,
                    'name' => request()->input('settings')['name'][$idx],
                    'type' => request()->input('settings')['type'][$idx],
                ];
            }
        }

        $f = fopen(MODX_BASE_PATH . 'core/custom/config/seiger/settings/sArticles.php', "w");
        fwrite($f, '<?php return [' . "\r\n");
        if (count($settings)) {
            foreach ($settings as $key => $setting) {
                if (trim($key)) {
                    fwrite($f, "\t'" . $key . "' => [" . "\r\n");
                    foreach ($setting as $k => $v) {
                        fwrite($f, "\t\t'" . $k . "' => '" . $v . "',\r\n");
                    }
                    fwrite($f, "\t]" . ",\r\n");
                }
            }
        }
        fwrite($f, "];");
        fclose($f);
        sleep(10);

        $back = request()->back ?? '&get=settings';
        return header('Location: ' . $sArticlesController->url . $back);
    case "tags":
        $sArticlesController->setModifyTables('tags');
        $data['tabs'] = ['articles', 'tags'];
        if (evo()->hasPermission('settings')) {
            $data['tabs'][] = 'features';
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
        return header('Location: ' . $sArticlesController->url . $back);
}

echo $sArticlesController->view('index', $data);

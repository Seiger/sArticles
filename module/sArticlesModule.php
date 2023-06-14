<?php
/**
 * Articles management module
 */

use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\SiteTmplvar;
use EvolutionCMS\Models\SiteTmplvarTemplate;
use Illuminate\Support\Str;
use Seiger\sArticles\Controllers\sArticlesController;
use Seiger\sArticles\Models\sAFeature;
use Seiger\sArticles\Models\sArticle;
use Seiger\sArticles\Models\sArticlesTranslate;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE != 'true') die("No access");

$sArticlesController = new sArticlesController();
$data['editor'] = '';
$data['tabs'] = [];
$data['get'] = request()->get ?? "articles";
$data['sArticlesController'] = $sArticlesController;
$data['lang_default'] = $sArticlesController->langDefault();
$data['url'] = $sArticlesController->url;

switch ($data['get']) {
    default:
        $data['tabs'] = ['articles'];
        if (evo()->hasPermission('settings')) {
            $data['tabs'][] = 'features';
            $data['tabs'][] = 'settings';
        }
        break;
    case "article":
        $data['tabs'] = ['articles', 'offer', 'content'];
        $data['offer'] = sOffers::getOffer(request()->i);
        $data['offer_url'] = '&i='.request()->i;
        $data['content_url'] = '&i='.request()->i;
        $data['tvs_url'] = '&i='.request()->i;
        $data['features'] = sOFeature::orderBy('base')->get();

        $template = SiteContent::find(evo()->getConfig('s_articles_resource', 0))->template ?? null;
        if ($template && SiteTmplvarTemplate::whereTemplateid($template)->first()) {
            $data['tabs'][] = 'tvs';
        }
        break;
    case "articleSave":
        $offer = sArticle::where('s_articles.id', (int)request()->offer)->firstOrNew();
        $offer->published = (int)request()->published;
        $offer->parent = (int)request()->parent;
        $offer->price = $sArticlesController->validatePrice(request()->price);
        $offer->position = (int)request()->position;
        $offer->rating = (int)request()->rating;
        $offer->alias = $sArticlesController->validateAlias(request()->alias, request()->offer);
        $offer->prg_link = request()->prg_link;
        $offer->website = request()->website;
        $offer->cover = request()->cover;
        $offer->published_at = request()->published_at;
        $offer->save();
        $offer->features()->sync(request()->features ?? []);

        $sArticlesController->setOfferListing();

        $back = str_replace('&i=0', '&i=' . $offer->id, (request()->back ?? '&get=articles'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "articleDelete":
        $offer = DB::table('s_articles')->whereId((int)request()->i)->delete();
        DB::table('s_article_translates')->whereOffer((int)request()->i)->delete();

        $sArticlesController->setOfferListing();

        $back = '&get=articles';
        return header('Location: ' . $sArticlesController->url . $back);
    case "content":
        $data['tabs'] = ['articles', 'offer', 'content'];

        $template = SiteContent::find(evo()->getConfig('s_articles_resource', 0))->template ?? null;
        if ($template && SiteTmplvarTemplate::whereTemplateid($template)->first()) {
            $data['tabs'][] = 'tvs';
        }

        $content = sArticlesTranslate::whereOffer((int)request()->i)->whereLang(request()->lang)->first();
        if (!$content && request()->lang == $sArticlesController->langDefault()) {
            $content = sArticlesTranslate::whereOffer((int)request()->i)->whereLang('base')->first();
        }
        $data['article_url'] = '&i=' . request()->i;
        $data['content_url'] = '&i=' . request()->i;
        $data['tvs_url'] = '&i='.request()->i;
        $data['constructor'] = [];
        $constructor = data_is_json($content->constructor ?? '', true);
        $settings = require MODX_BASE_PATH . 'core/custom/config/cms/settings/sArticle.php';
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
        $content = sArticlesTranslate::whereOffer((int)request()->offer)->whereLang(request()->lang)->firstOrNew();
        if (!$content->tid) {
            $content->offer = (int)request()->offer;
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
        $content->save();
        $back = str_replace('&i=0', '&i=' . $content->offer, (request()->back ?? '&get=articles'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "tvs":
        $data['tabs'] = ['articles', 'offer', 'content', 'tvs'];
        $data['offer'] = sOffers::getOffer(request()->i);
        $data['offer_url'] = '&i='.request()->i;
        $data['content_url'] = '&i='.request()->i;
        $template = SiteContent::find(evo()->getConfig('s_offers_resource', 0))->template ?? 0;
        $data['tvs'] = SiteTmplvar::query()
            ->select('site_tmplvars.*', 'site_tmplvar_templates.rank as tvrank', 'site_tmplvar_templates.rank', 'site_tmplvars.id', 'site_tmplvars.rank')
            ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
            ->orderBy('site_tmplvar_templates.rank', 'ASC')
            ->orderBy('site_tmplvars.rank', 'ASC')
            ->orderBy('site_tmplvars.id', 'ASC')
            ->where('site_tmplvar_templates.templateid', $template)
            ->get();
        $data['tvValues'] = data_is_json($data['offer']->tmplvars, true) ?? [];
        break;
    case "tvsSave":
        $offer = sOffers::getOffer((int)request()->offer);
        $template = SiteContent::find(evo()->getConfig('s_offers_resource', 0))->template ?? 0;
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

        $offer->tmplvars = json_encode($tvValues);
        $offer->save();
        $back = str_replace('&i=0', '&i=' . $offer->id, (request()->back ?? '&get=tvs'));
        return header('Location: ' . $sArticlesController->url . $back);
    case "features":
        $sArticlesController->setModifyTables();
        $data['tabs'] = ['articles', 'features', 'settings'];
        $data['features'] = sAFeature::orderBy('position')->get();
        break;
    case "featuresSave":
        if (request()->filled('features')) {
            $features = request()->features;
            $sAFeatures = sAFeature::all();
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

                foreach ($sAFeatures as $sAFeature) {
                    if (isset($values[$sAFeature->alias])) {
                        foreach ($values[$sAFeature->alias] as $field => $item) {
                            $sAFeature->{$field} = $item;
                        }
                        $sAFeature->update();

                        unset($values[$sAFeature->alias]);
                    } else {
                        $sAFeature->delete();
                    }
                }

                if (count($values)) {
                    foreach ($values as $value) {
                        $sAFeature = new sAFeature();
                        foreach ($value as $field => $item) {
                            $sAFeature->{$field} = $item;
                        }
                        $sAFeature->save();
                    }
                }
            } else {
                foreach ($sAFeatures as $sAFeature) {
                    $sAFeature->delete();
                }
            }
        }
        $back = request()->back ?? '&get=features';
        return header('Location: ' . $sArticlesController->url . $back);
    case "settings":
        $data['tabs'] = ['articles', 'features'];
        if (evo()->hasPermission('settings')) {
            $data['tabs'][] = 'settings';
        } else {
            $back = request()->back ?? '&get=articles';
            return header('Location: ' . $sArticlesController->url . $back);
        }
        break;
    case "settingsSave":
        if (request()->has('parent') && request()->parent != evo()->getConfig('s_offers_resource')) {
            $resource = request()->parent;
            $tbl = evo()->getDatabase()->getFullTableName('system_settings');
            evo()->getDatabase()->query("REPLACE INTO {$tbl} (`setting_name`, `setting_value`) VALUES ('s_offers_resource', '{$resource}')");
            evo()->setConfig('s_offers_resource', $resource);
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

        $f = fopen(MODX_BASE_PATH . config_path('cms/settings/sArticles.php', true), "w");
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
}

echo $sArticlesController->view('index', $data);

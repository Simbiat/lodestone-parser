<?php
declare(strict_types = 1);

namespace Simbiat\FFXIV\LodestoneModules;

use function in_array, is_array, sprintf;

/**
 * Main parsing logic
 */
trait Parsers
{
    /**
     * Parse Lodestone HTML
     *
     * @return \Simbiat\FFXIV\LodestoneModules\Parsers|\Simbiat\FFXIV\Lodestone
     * @throws \Throwable
     */
    protected function parse(): self
    {
        $started = \hrtime(true);
        #Set array key for results
        $resultkey = match ($this->type) {
            'search_character', 'character', 'character_jobs', 'character_friends', 'character_following', 'achievements', 'achievement_details' => 'characters',
            'free_company_members', 'search_free_company', 'free_company' => 'freecompanies',
            'linkshell_members', 'search_linkshell' => 'linkshells',
            'pvp_team_members', 'search_pvp_team' => 'pvpteams',
            'database', 'achievement_from_db' => 'database',
            default => $this->type,
        };
        $resultsubkey = match ($this->type) {
            'character_jobs' => 'jobs',
            'character_friends' => 'friends',
            'character_following' => 'following',
            'achievements', 'achievement_details' => 'achievements',
            'free_company_members', 'linkshell_members', 'pvp_team_members' => 'members',
            'frontline', 'grand_company_ranking', 'free_company_ranking' => $this->type_settings['week_month'],
            'database' => $this->type_settings['type'],
            default => '',
        };
        try {
            $this->last_error = NULL;
            $this->html = new HttpRequest($this->user_agent)->get($this->url);
        } catch (\Throwable $exception) {
            $this->errorRegister($exception->getMessage(), 'http', $started);
            if ($exception->getCode() === 404) {
                $this->addToResults($resultkey, $resultsubkey, 404);
            } elseif ($exception->getCode() === 403) {
                if ($this->type === 'character') {
                    $this->addToResults($resultkey, $resultsubkey, ['private' => true]);
                }
            } else {
                #Any network errors or throttling can be bad when chaining multiple requests, which can result in incomplete dataset, so we re-throw here
                throw $exception;
            }
            return $this;
        }
        if ($this->benchmark) {
            $finished = \hrtime(true);
            $duration = $finished - $started;
            $this->result['benchmark']['http_time'][] = \date('H:i:s.'.sprintf('%06d', ($duration / 1000)), (int)($duration / 1000000000));
        }
        $started = \hrtime(true);
        try {
            $this->last_error = NULL;
            #Parsing of pages
            if (in_array($this->type, [
                'search_character',
                'character_friends',
                'character_following',
                'free_company_members',
                'linkshell_members',
                'pvp_team_members',
                'search_free_company',
                'search_linkshell',
                'search_pvp_team',
                'topics',
                'notices',
                'maintenance',
                'updates',
                'status',
            ])) {
                if (!$this->regexfail(\preg_match_all(Regex::PAGECOUNT, $this->html, $pages, \PREG_SET_ORDER), \preg_last_error(), 'PAGECOUNT')) {
                    return $this;
                }
                $this->pages($pages, $resultkey);
            }
            if (in_array($this->type, ['grand_company_ranking', 'free_company_ranking'])) {
                if (!$this->regexfail(\preg_match_all(Regex::PAGECOUNT2, $this->html, $pages, \PREG_SET_ORDER), \preg_last_error(), 'PAGECOUNT2')) {
                    return $this;
                }
                $this->pages($pages, $resultkey);
            }
            if ($this->type === 'database') {
                if (!$this->regexfail(\preg_match_all(Regex::DBPAGECOUNT, $this->html, $pages, \PREG_SET_ORDER), \preg_last_error(), 'DBPAGECOUNT')) {
                    return $this;
                }
                $this->pages($pages, $resultkey);
            }
            
            #Banners special precut
            if ($this->type === 'banners') {
                if (!$this->regexfail(\preg_match(Regex::BANNERS, $this->html, $banners), \preg_last_error(), 'BANNERS')) {
                    return $this;
                }
                $this->html = $banners[0];
            }
            
            #Notices special precut for pinned items
            if (in_array($this->type, [
                'notices',
                'maintenance',
                'updates',
                'status',
            ])) {
                if (!$this->regexfail(\preg_match_all(Regex::NOTICES, $this->html, $notices, \PREG_SET_ORDER), \preg_last_error(), 'NOTICES')) {
                    return $this;
                }
                $this->html = $notices[0][0];
            }
            
            #Main (general) parser
            #Setting initial regex
            $regex = match ($this->type) {
                'search_pvp_team' => Regex::PVPTEAMLIST,
                'search_linkshell' => Regex::LINKSHELLLIST,
                'search_free_company' => Regex::FREECOMPANYLIST,
                'banners' => Regex::BANNERS2,
                'worlds' => Regex::DATACENTERS,
                'feast' => Regex::FEAST,
                'frontline' => Regex::FRONTLINE,
                'grand_company_ranking' => Regex::GCRANKING,
                'free_company_ranking' => Regex::FCRANKING,
                'deep_dungeon' => Regex::DEEPDUNGEON,
                'free_company' => Regex::FREECOMPANY,
                'achievements' => Regex::ACHIEVEMENTS_LIST,
                'achievement_details' => Regex::ACHIEVEMENT_DETAILS,
                'character' => Regex::CHARACTER_GENERAL,
                'character_jobs' => Regex::CHARACTER_JOBS,
                'topics', 'news' => Regex::NEWS,
                'notices', 'maintenance', 'updates', 'status' => Regex::NOTICES2,
                'database' => Regex::DBLIST,
                'achievement_from_db' => Regex::ACHIEVEMENT_DB,
                default => Regex::CHARACTERLIST,
            };
            
            #Uncomment for debugging purposes
            #file_put_contents(__DIR__.'/regex.txt', $regex);
            #file_put_contents(__DIR__.'/html.txt', $this->html);
            
            if (!$this->regexfail(\preg_match_all($regex, $this->html, $temp_results, \PREG_SET_ORDER), \preg_last_error(), 'main regex')) {
                if (in_array($this->type, [
                    'search_character',
                    'character_friends',
                    'character_following',
                    'free_company_members',
                    'linkshell_members',
                    'pvp_team_members',
                    'search_free_company',
                    'search_linkshell',
                    'search_pvp_team',
                    'topics',
                    'notices',
                    'maintenance',
                    'updates',
                    'status',
                ])) {
                    if (!empty($this->type_settings['id']) && !empty($this->result[$resultkey][$this->type_settings['id']][$resultsubkey]['total'])) {
                        return $this;
                    }
                    $this->errorUnregister();
                } else {
                    return $this;
                }
            }
            
            #Character results update
            if ($this->type === 'character') {
                #Remove non-named groups before rearranging results to avoid overwrites
                foreach ($temp_results as $key => $temp_result) {
                    foreach ($temp_result as $key2 => $details) {
                        if (\is_numeric($key2) || empty($details)) {
                            #No idea why EA thinks $key2 is float, when it's either string or int. Probably gets confused by is_numeric check
                            /** @noinspection OffsetOperationsInspection */
                            unset($temp_results[$key][$key2]);
                        }
                    }
                }
                $temp_results = [\array_merge($temp_results[0], $temp_results[1], $temp_results[2] ?? [])];
            }
            
            foreach ($temp_results as $key => $temp_result) {
                #Remove unnamed groups and empty values
                foreach ($temp_result as $key2 => $value) {
                    if (\is_numeric($key2) || empty($value)) {
                        unset($temp_results[$key][$key2], $temp_result[$key2]);
                    }
                }
                #Decode HTML entities
                foreach ($temp_result as $key2 => $value) {
                    #Decode in the data inside loop
                    $temp_result[$key2] = \html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5);
                    #Decode in original data (for consistency)
                    $temp_results[$key][$key2] = \html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5);
                }
                
                #Specific processing
                switch ($this->type) {
                    case 'search_pvp_team':
                    case 'search_free_company':
                        $temp_results[$key]['crest'] = $this->crest($temp_result, 'crest');
                        break;
                    case 'search_character':
                    case 'character_friends':
                    case 'character_following':
                    case 'free_company_members':
                    case 'linkshell_members':
                    case 'pvp_team_members':
                        if (!empty($temp_result['linkshell_community_id'])) {
                            $temp_results[$key]['community_id'] = $temp_result['linkshell_community_id'];
                        }
                        if (!empty($temp_result['pvp_team_community_id'])) {
                            $temp_results[$key]['community_id'] = $temp_result['pvp_team_community_id'];
                        }
                        if ($this->type === 'free_company_members') {
                            $temp_results[$key]['rank_id'] = $this->converters->fcRankId($temp_result['rank_icon']);
                        }
                        if (!empty($temp_result['gc_name'])) {
                            $temp_results[$key]['grand_company'] = $this->grandcompany($temp_result);
                        }
                        if (!empty($temp_result['fc_id'])) {
                            $temp_results[$key]['free_company'] = $this->freecompany($temp_result);
                        }
                        if (!empty($temp_result['ls_rank']) && !empty($temp_result['ls_rank_icon'])) {
                            $temp_results[$key]['rank_icon'] = $temp_result['ls_rank_icon'];
                        }
                        #Specific for linkshell members
                        if (empty($this->result['server']) && !empty($this->type_settings['id'])) {
                            $this->result[$resultkey][$this->type_settings['id']]['server'] = $temp_result['server'];
                        }
                        if (!empty($pages[0]['linkshell_server']) && !empty($this->type_settings['id'])) {
                            $this->result[$resultkey][$this->type_settings['id']]['server'] = $pages[0]['linkshell_server'];
                        }
                        break;
                    case 'frontline':
                    case 'grand_company_ranking':
                        if (!empty($temp_result['gc_name'])) {
                            $temp_results[$key]['grand_company'] = $this->grandcompany($temp_result);
                        }
                        if (!empty($temp_result['fc_id'])) {
                            $temp_results[$key]['free_company'] = $this->freecompany($temp_result);
                        }
                        $temp_results[$key]['rank'] = ($temp_result['rank2'] ?: $temp_result['rank1']);
                        break;
                    case 'free_company_ranking':
                        $temp_results[$key]['crest'] = $this->crest($temp_result, 'crest');
                        $temp_results[$key]['rank'] = ($temp_result['rank2'] ?: $temp_result['rank1']);
                        break;
                    case 'topics':
                    case 'news':
                    case 'notices':
                    case 'maintenance':
                    case 'updates':
                    case 'status':
                        $temp_results[$key]['url'] = sprintf(Routes::LODESTONE_URL_BASE, $this->language).$temp_result['url'];
                        break;
                    case 'deep_dungeon':
                        $temp_results[$key]['job'] = [
                            'name' => $temp_result['job'],
                            'icon' => $temp_result['jobicon'],
                        ];
                        if (!empty($temp_result['jobform'])) {
                            $temp_results[$key]['job']['form'] = $temp_result['jobform'];
                        }
                        break;
                    case 'free_company':
                        $temp_results[$key]['crest'] = $this->crest($temp_result, 'crest');
                        #Ranking checks for --
                        if ($temp_result['weekly_rank'] === '--') {
                            $temp_results[$key]['weekly_rank'] = NULL;
                        }
                        if ($temp_result['monthly_rank'] === '--') {
                            $temp_results[$key]['monthly_rank'] = NULL;
                        }
                        #Estates
                        if (!empty($temp_result['estate_name'])) {
                            $temp_results[$key]['estate']['name'] = $temp_result['estate_name'];
                        }
                        if (!empty($temp_result['estate_address'])) {
                            $temp_results[$key]['estate']['address'] = $temp_result['estate_address'];
                        }
                        if (!empty($temp_result['estate_greeting']) && !in_array($temp_result['estate_greeting'], ['No greeting available.', 'グリーティングメッセージが設定されていません。', 'Il n\'y a aucun message d\'accueil.', 'Keine Begrüßung vorhanden.'])) {
                            $temp_results[$key]['estate']['greeting'] = $temp_result['estate_greeting'];
                        }
                        #Grand companies reputation
                        for ($iteration = 1; $iteration <= 3; $iteration++) {
                            if (!empty($temp_result['gc_name_'.$iteration])) {
                                $temp_results[$key]['reputation'][$temp_result['gc_name_'.$iteration]] = $temp_result['gcrepu'.$iteration];
                                unset($temp_results[$key]['gc_name_'.$iteration], $temp_results[$key]['gcrepu'.$iteration]);
                            }
                        }
                        #Focus
                        for ($iteration = 1; $iteration <= 9; $iteration++) {
                            if (!empty($temp_result['focusname'.$iteration])) {
                                $temp_results[$key]['focus'][] = [
                                    'name' => $temp_result['focusname'.$iteration],
                                    'enabled' => (empty($temp_result['focusoff'.$iteration]) ? 1 : 0),
                                    'icon' => $temp_result['focusicon'.$iteration],
                                ];
                                unset($temp_results[$key]['focusname'.$iteration], $temp_results[$key]['focusoff'.$iteration], $temp_results[$key]['focusicon'.$iteration]);
                            }
                        }
                        #Seeking
                        for ($iteration = 1; $iteration <= 5; $iteration++) {
                            if (!empty($temp_result['seekingname'.$iteration])) {
                                $temp_results[$key]['seeking'][] = [
                                    'name' => $temp_result['seekingname'.$iteration],
                                    'enabled' => (empty($temp_result['seekingoff'.$iteration]) ? 1 : 0),
                                    'icon' => $temp_result['seekingicon'.$iteration],
                                ];
                                unset($temp_results[$key]['seekingname'.$iteration], $temp_results[$key]['seekingoff'.$iteration], $temp_results[$key]['seekingicon'.$iteration]);
                            }
                        }
                        #Trim stuff
                        $temp_results[$key]['slogan'] = mb_trim($temp_result['slogan'] ?? '', null, 'UTF-8');
                        $temp_results[$key]['active'] = mb_trim($temp_result['active'], null, 'UTF-8');
                        $temp_results[$key]['recruitment'] = mb_trim($temp_result['recruitment'], null, 'UTF-8');
                        $temp_results[$key]['grand_company'] = mb_trim($temp_result['grand_company'], null, 'UTF-8');
                        if (empty($temp_result['members_count'])) {
                            $temp_results[$key]['members_count'] = 0;
                        } else {
                            $temp_results[$key]['members_count'] = (int)$temp_result['members_count'];
                        }
                        break;
                    case 'achievements':
                        $temp_results[$key]['title'] = !empty($temp_result['title']);
                        $temp_results[$key]['item'] = !empty($temp_result['item']);
                        if (empty($temp_result['time'])) {
                            $temp_results[$key]['time'] = NULL;
                        }
                        if (empty($temp_result['points'])) {
                            $temp_results[$key]['points'] = 0;
                        }
                        break;
                    case 'achievement_details':
                    case 'achievement_from_db':
                        if (empty($temp_result['title'])) {
                            $temp_results[$key]['title'] = NULL;
                        } else {
                            $temp_result['title'] = mb_trim($temp_result['title'], null, 'UTF-8');
                        }
                        if (empty($temp_result['item'])) {
                            $temp_results[$key]['item'] = NULL;
                        }
                        if (!empty($temp_result['item_name'])) {
                            $temp_results[$key]['item'] = [
                                'id' => $temp_result['item_id'],
                                'name' => $temp_result['item_name'],
                                'icon' => $temp_result['item_icon'],
                            ];
                            unset($temp_results[$key]['item_id'], $temp_results[$key]['item_name'], $temp_results[$key]['item_icon']);
                        }
                        if (empty($temp_result['time'])) {
                            $temp_results[$key]['time'] = NULL;
                        }
                        if (empty($temp_result['points'])) {
                            $temp_results[$key]['points'] = 0;
                        }
                        break;
                    case 'database':
                        $temp_results[$key]['name'] = \str_replace(['<i>', '</i>'], '', mb_trim($temp_results[$key]['name'], null, 'UTF-8'));
                        switch ($this->type_settings['type']) {
                            case 'achievement':
                                $temp_results[$key]['reward'] = (mb_trim($temp_results[$key]['column1'], null, 'UTF-8') === '-' ? null : mb_trim($temp_results[$key]['column1'], null, 'UTF-8'));
                                $temp_results[$key]['points'] = (int)($temp_results[$key]['column2'] ?? 0);
                                break;
                            case 'quest':
                                $temp_results[$key]['area'] = (mb_trim($temp_results[$key]['column1'], null, 'UTF-8') === '-' ? null : mb_trim($temp_results[$key]['column1'], null, 'UTF-8'));
                                $temp_results[$key]['character_level'] = (int)($temp_results[$key]['column2'] ?? 0);
                                break;
                            case 'duty':
                                $temp_results[$key]['character_level'] = (int)($temp_results[$key]['column1'] ?? 0);
                                $temp_results[$key]['item_level'] = (mb_trim($temp_results[$key]['column2'], null, 'UTF-8') === '-' ? 0 : (int)$temp_results[$key]['column2']);
                                break;
                            case 'item':
                                $temp_results[$key]['item_level'] = (mb_trim($temp_results[$key]['column1'], null, 'UTF-8') === '-' ? 0 : (int)$temp_results[$key]['column1']);
                                $temp_results[$key]['character_level'] = (mb_trim($temp_results[$key]['column2'], null, 'UTF-8') === '-' ? 0 : (int)$temp_results[$key]['column2']);
                                break;
                            case 'recipe':
                                if (isset($temp_results[$key]['extraicon'])) {
                                    $temp_results[$key]['collectable'] = true;
                                } else {
                                    $temp_results[$key]['collectable'] = false;
                                }
                                if (!isset($temp_results[$key]['master'])) {
                                    $temp_results[$key]['master'] = NULL;
                                }
                                $temp_results[$key]['recipe_level'] = (mb_trim($temp_results[$key]['column1'], null, 'UTF-8') === '-' ? 0 : (int)$temp_results[$key]['column1']);
                                $temp_results[$key]['stars'] = $this->stars($temp_results[$key]);
                                if (isset($temp_results[$key]['expert'])) {
                                    $temp_results[$key]['expert'] = true;
                                } else {
                                    $temp_results[$key]['expert'] = false;
                                }
                                $temp_results[$key]['item_level'] = (mb_trim($temp_results[$key]['column2'], null, 'UTF-8') === '-' ? 0 : (int)$temp_results[$key]['column2']);
                                break;
                            case 'gathering':
                                if (isset($temp_results[$key]['extraicon'])) {
                                    $temp_results[$key]['collectable'] = true;
                                } else {
                                    $temp_results[$key]['collectable'] = false;
                                }
                                if (isset($temp_results[$key]['hidden'])) {
                                    $temp_results[$key]['hidden'] = true;
                                } else {
                                    $temp_results[$key]['hidden'] = false;
                                }
                                $temp_results[$key]['level'] = (mb_trim($temp_results[$key]['column1'], null, 'UTF-8') === '-' ? 0 : (int)$temp_results[$key]['column1']);
                                $temp_results[$key]['stars'] = $this->stars($temp_results[$key]);
                                break;
                            case 'shop':
                                $temp_results[$key]['area'] = \preg_replace('/\s+((Other Locations)|(ほか)|(Etc.)|(Anderer Ort))/miu', '', \str_replace(['<i>', '</i>'], '', mb_trim($temp_results[$key]['column1'], null, 'UTF-8')));
                                break;
                            case 'text_command':
                                if (in_array($temp_results[$key]['column1'], ['Yes', '○', 'oui', '○'])) {
                                    $temp_results[$key]['Windows'] = true;
                                } else {
                                    $temp_results[$key]['Windows'] = false;
                                }
                                if (in_array($temp_results[$key]['column2'], ['Yes', '○', 'oui', '○'])) {
                                    $temp_results[$key]['PS4'] = true;
                                } else {
                                    $temp_results[$key]['PS4'] = false;
                                }
                                if (in_array($temp_results[$key]['column3'], ['Yes', '○', 'oui', '○'])) {
                                    $temp_results[$key]['Mac'] = true;
                                } else {
                                    $temp_results[$key]['Mac'] = false;
                                }
                                break;
                        }
                        break;
                    case 'character':
                        #There are cases of characters not returning a proper race or clan (usually both).
                        #I've reported this issue to Square Enix several times, and they simply update affected characters.
                        #This breaks normal update routines, though, so both race and clan are defaulted to what the game suggests for new characters: Midlander Hyur. Appropriate comments are added, though for information purposes.
                        $temp_results[$key]['private'] = !empty($temp_results[$key]['private']);
                        #Portrait
                        if (!\array_key_exists('avatar', $temp_results[$key])) {
                            throw new \UnexpectedValueException('No avatar key for character '.$this->type_settings['id']);
                        }
                        $temp_results[$key]['portrait'] = \str_replace('c0.jpg', 'l0.jpg', $temp_result['avatar']);
                        #Since release of Dawntrail, if profile is private you won't get any of the fields below
                        if ($temp_results[$key]['private'] === false) {
                            $temp_results[$key]['race'] = mb_trim($temp_results[$key]['race'], null, 'UTF-8');
                            $temp_results[$key]['clan'] = mb_trim($temp_results[$key]['clan'], null, 'UTF-8');
                            if ($temp_results[$key]['race'] === '----') {
                                $temp_results[$key]['race'] = null;
                                $temp_results[$key]['comment'] = 'No race';
                            }
                            if ($temp_results[$key]['clan'] === '----') {
                                $temp_results[$key]['clan'] = null;
                                if ($temp_results[$key]['comment'] === 'No race') {
                                    $temp_results[$key]['comment'] .= ' and clan';
                                } else {
                                    $temp_results[$key]['comment'] = 'No clan';
                                }
                            }
                            $temp_results[$key]['nameday'] = \str_replace('32st', '32nd', $temp_results[$key]['nameday']);
                            if (!empty($temp_result['uppertitle'])) {
                                $temp_results[$key]['title'] = $temp_result['uppertitle'];
                            } elseif (!empty($temp_result['undertitle'])) {
                                $temp_results[$key]['title'] = $temp_result['undertitle'];
                            } else {
                                $temp_results[$key]['title'] = '';
                            }
                            #Gender to text
                            $temp_results[$key]['gender'] = ($temp_result['gender'] === '♂' ? 'male' : 'female');
                            #Guardian
                            if (empty($temp_results[$key]['guardian'])) {
                                $temp_results[$key]['guardian']['name'] = match (mb_strtolower($this->language, 'UTF-8')) {
                                    'jp', 'ja' => 'ハルオーネ',
                                    'fr' => 'Halone, la Conquérante',
                                    'de' => 'Halone - Die Furie',
                                    default => 'Halone, the Fury',
                                };
                                $temp_results[$key]['guardian']['icon'] = 'https://img.finalfantasyxiv.com/lds/h/5/qmgVmQ1o6skxdK4hDEbIV5NETA.png';
                                if (empty($temp_results[$key]['comment'])) {
                                    $temp_results[$key]['comment'] = 'Defaulted guardian';
                                } else {
                                    $temp_results[$key]['comment'] .= ' and guardian';
                                }
                            } else {
                                $temp_results[$key]['guardian'] = [
                                    'name' => $temp_result['guardian'],
                                    'icon' => $temp_result['guardianicon'],
                                ];
                            }
                            #City
                            $temp_results[$key]['city'] = [
                                'name' => $temp_result['city'],
                                'icon' => $temp_result['city_icon'],
                            ];
                            #Grand Company
                            if (!empty($temp_result['gc_name'])) {
                                $temp_results[$key]['grand_company'] = $this->grandcompany($temp_result);
                            }
                            #Free Company
                            if (!empty($temp_result['fc_id'])) {
                                $temp_results[$key]['free_company'] = $this->freecompany($temp_result);
                            }
                            #PvP Team
                            if (!empty($temp_result['pvpid'])) {
                                $temp_results[$key]['pvp'] = [
                                    'id' => $temp_result['pvpid'],
                                    'name' => $temp_result['pvpname'],
                                ];
                                $temp_results[$key]['pvp']['crest'] = $this->crest($temp_result, 'pvpcrest');
                            }
                            #Bio
                            $temp_result['bio'] = mb_trim($temp_result['bio'], null, 'UTF-8');
                            if ($temp_result['bio'] === '-') {
                                $temp_result['bio'] = '';
                            }
                            if (!empty($temp_result['bio'])) {
                                $temp_results[$key]['bio'] = $temp_result['bio'];
                            } else {
                                $temp_results[$key]['bio'] = '';
                            }
                            $temp_results[$key]['attributes'] = $this->attributes();
                            #Minions and mounts now show only icon on Lodestone, thus it's not really practically to grab them
                            #$temp_results[$key]['mounts'] = $this->collectibles('mounts');
                            #$temp_results[$key]['minions'] = $this->collectibles('minions');
                            $temp_results[$key]['gear'] = $this->items();
                        }
                        break;
                    case 'character_jobs':
                        $temp_result['id'] = $this->converters->classToJob($temp_result['name']);
                        $temp_result['expcur'] = \preg_replace('/\D/', '', $temp_result['expcur'] ?? '');
                        $temp_result['expmax'] = \preg_replace('/\D/', '', $temp_result['expmax'] ?? '');
                        $temp_results[$key] = $this->jobDetails($temp_result);
                        break;
                }
                
                #Unset stuff for cleaner look. Since it does not trigger warnings if variable is missing, no need to "switch" it
                unset($temp_results[$key]['crest1'], $temp_results[$key]['crest2'], $temp_results[$key]['crest3'], $temp_results[$key]['fccrestimg1'], $temp_results[$key]['fccrestimg2'], $temp_results[$key]['fccrestimg3'], $temp_results[$key]['gc_name'], $temp_results[$key]['gcrank'], $temp_results[$key]['gc_rank_icon'], $temp_results[$key]['fc_id'], $temp_results[$key]['fcname'], $temp_results[$key]['ls_rank_icon'], $temp_results[$key]['jobicon'], $temp_results[$key]['jobform'], $temp_results[$key]['estate_greeting'], $temp_results[$key]['estate_address'], $temp_results[$key]['estate_name'], $temp_results[$key]['city_icon'], $temp_results[$key]['guardianicon'], $temp_results[$key]['gcicon'], $temp_results[$key]['uppertitle'], $temp_results[$key]['undertitle'], $temp_results[$key]['pvpid'], $temp_results[$key]['pvpname'], $temp_results[$key]['pvpcrest1'], $temp_results[$key]['pvpcrest2'], $temp_results[$key]['pvpcrest3'], $temp_results[$key]['rank1'], $temp_results[$key]['rank2'], $temp_results[$key]['id'], $temp_results[$key]['column1'], $temp_results[$key]['column2'], $temp_results[$key]['column3'], $temp_results[$key]['star1'], $temp_results[$key]['star2'], $temp_results[$key]['star3'], $temp_results[$key]['extraicon']);
                
                #Adding to results
                $this->addToResults($resultkey, $resultsubkey, $temp_results[$key], (empty($temp_result['id']) ? null : $temp_result['id']));
            }
            
            #Sort worlds
            if ($this->type === 'worlds') {
                \ksort($this->result[$resultkey]);
            }
        } catch (\Throwable $exception) {
            $this->errorRegister($exception->getMessage(), 'parse', $started);
            return $this;
        }
        #Benchmarking
        if ($this->benchmark) {
            $finished = \hrtime(true);
            $duration = $finished - $started;
            $this->benchUpdate($duration);
        }
        
        #Processing achievements' details last to get proper order of timings for benchmarking
        if ($this->type === 'achievements' && $this->type_settings['details']) {
            foreach ($this->result[$resultkey][$this->type_settings['id']][$resultsubkey] as $key => $ach) {
                $this->getCharacterAchievements($this->type_settings['id'], $key, 1, false, true);
            }
        }
        if ($this->type === 'achievements' && $this->type_settings['allachievements']) {
            $this->type_settings['allachievements'] = false;
            for ($iteration = 1; $iteration <= 13; $iteration++) {
                $this->getCharacterAchievements($this->type_settings['id'], false, (string)$iteration, false, $this->type_settings['details'], $this->type_settings['only_owned']);
            }
        }
        $this->allpagesproc($resultkey);
        
        return $this;
    }
    
    /**
     * Add  entity to results
     * @param string          $resultkey    Result key (essentially type of the entity)
     * @param string          $resultsubkey Sub-key of result
     * @param array|int       $result       Actual result
     * @param string|int|null $id           ID of an entity we are adding
     *
     * @return void
     */
    protected function addToResults(string $resultkey, string $resultsubkey, array|int $result, string|int|null $id = null): void
    {
        switch ($this->type) {
            case 'search_pvp_team':
            case 'search_linkshell':
            case 'search_free_company':
            case 'search_character':
                if ($result !== 404) {
                    $this->result[$resultkey][$id] = $result;
                }
                break;
            case 'free_company':
            case 'character':
                $this->result[$resultkey][$this->type_settings['id']] = $result;
                break;
            case 'character_jobs':
            case 'character_friends':
            case 'character_following':
            case 'free_company_members':
            case 'linkshell_members':
                if ($result === 404) {
                    if (!isset($this->result[$resultkey]) || (!\is_scalar($this->result[$resultkey][$this->type_settings['id']]) && !is_array($this->result[$resultkey][$this->type_settings['id']][$resultsubkey]))) {
                        $this->result[$resultkey][$this->type_settings['id']][$resultsubkey] = $result;
                    }
                } else {
                    $this->result[$resultkey][$this->type_settings['id']][$resultsubkey][$id] = $result;
                }
                break;
            case 'pvp_team_members':
                if ($result === 404) {
                    $this->result[$resultkey][$this->type_settings['id']][$resultsubkey] = $result;
                } else {
                    $this->result[$resultkey][$this->type_settings['id']][$resultsubkey][$id] = $result;
                }
                break;
            case 'achievements':
                if ($result !== 404 && ($this->type_settings['only_owned'] === false || ($this->type_settings['only_owned'] === true && is_array($result) && $result['time'] !== NULL))) {
                    $this->result[$resultkey][$this->type_settings['id']][$resultsubkey][$id] = $result;
                }
                break;
            case 'achievement_details':
                if ($result !== 404 || empty($this->result[$resultkey][$this->type_settings['id']][$resultsubkey][$this->type_settings['achievement_id']])) {
                    $this->result[$resultkey][$this->type_settings['id']][$resultsubkey][$this->type_settings['achievement_id']] = $result;
                }
                break;
            case 'database':
                $this->result[$resultkey][$resultsubkey][$id] = $result;
                break;
            case 'achievement_from_db':
                $this->result[$resultkey]['achievement'][$this->type_settings['id']] = $result;
                break;
            case 'banners':
            case 'topics':
            case 'news':
            case 'notices':
            case 'maintenance':
            case 'updates':
            case 'status':
                if ($result !== 404) {
                    $this->result[$resultkey][] = $result;
                }
                break;
            case 'worlds':
                if ($result !== 404 && is_array($result)) {
                    $this->result[$resultkey][$result['data_center']] = [];
                    \preg_match_all(Regex::WORLDS, $result['servers'], $servers, \PREG_SET_ORDER);
                    if ($this->type_settings['world_details']) {
                        foreach ($servers as $server) {
                            $this->result[$resultkey][$result['data_center']][$server['server']] = [
                                'Online' => $server['maintenance'] === '1',
                                'Partial maintenance' => $server['maintenance'] === '2',
                                'Full maintenance' => $server['maintenance'] === '3',
                                'Preferred' => in_array($server['population'], ['Preferred', '優遇', 'Désignés', 'Bevorzugt']),
                                'Congested' => in_array($server['population'], ['Congested', '混雑', 'Surpeuplés', 'Belastet']),
                                'New characters' => in_array($server['newchars'], ['Creation of New Characters Available', '新規キャラクター作成可', 'Création de personnage possible', 'Erstellung möglich']),
                            ];
                        }
                    } else {
                        $this->result[$resultkey][$result['data_center']] = \array_column($servers, 'status', 'server');
                    }
                    \ksort($this->result[$resultkey][$result['data_center']]);
                }
                break;
            case 'feast':
                if ($result === 404) {
                    $this->result[$resultkey][$this->type_settings['season']] = $result;
                } else {
                    $this->result[$resultkey][$this->type_settings['season']][$id] = $result;
                }
                break;
            case 'frontline':
            case 'grand_company_ranking':
            case 'free_company_ranking':
                if ($result === 404) {
                    $this->result[$resultkey][$resultsubkey][$this->type_settings['week']] = $result;
                } else {
                    $this->result[$resultkey][$resultsubkey][$this->type_settings['week']][$id] = $result;
                }
                break;
            case 'deep_dungeon':
                if ($this->type_settings['solo_party'] === 'solo') {
                    if ($result === 404) {
                        $this->result[$resultkey][$this->type_settings['dungeon']][$this->type_settings['solo_party']][$this->type_settings['class']] = $result;
                    } else {
                        $this->result[$resultkey][$this->type_settings['dungeon']][$this->type_settings['solo_party']][$this->type_settings['class']][$id] = $result;
                    }
                } elseif ($result === 404) {
                    $this->result[$resultkey][$this->type_settings['dungeon']][$this->type_settings['solo_party']] = $result;
                } else {
                    $this->result[$resultkey][$this->type_settings['dungeon']][$this->type_settings['solo_party']][$id] = $result;
                }
                break;
        }
    }
    
    /**
     * Function to check if we need to grab all pages and there are still pages left
     *
     * @param string $result_key
     *
     * @return bool
     */
    protected function allpagesproc(string $result_key): bool
    {
        if ($this->all_pages && in_array($this->type, [
                'search_character',
                'character_friends',
                'character_following',
                'free_company_members',
                'linkshell_members',
                'search_free_company',
                'search_linkshell',
                'search_pvp_team',
                'topics',
                'notices',
                'maintenance',
                'updates',
                'status',
                'grand_company_ranking',
                'free_company_ranking',
                'database',
            ])) {
            switch ($this->type) {
                case 'character_friends':
                case 'character_following':
                case 'free_company_members':
                case 'linkshell_members':
                    $current_page = $this->result[$result_key][$this->type_settings['id']]['page_current'];
                    $total_page = $this->result[$result_key][$this->type_settings['id']]['page_total'];
                    break;
                case 'grand_company_ranking':
                case 'free_company_ranking':
                    $current_page = $this->result[$result_key][$this->type_settings['week']]['page_current'];
                    $total_page = $this->result[$result_key][$this->type_settings['week']]['page_total'];
                    break;
                case 'database':
                    $current_page = $this->result[$result_key][$this->type_settings['type']]['page_current'];
                    $total_page = $this->result[$result_key][$this->type_settings['type']]['page_total'];
                    break;
                default:
                    $current_page = $this->result[$result_key]['page_current'];
                    $total_page = $this->result[$result_key]['page_total'];
                    break;
            }
            if ($current_page === $total_page) {
                return false;
            }
            $current_page++;
            \ini_set('max_execution_time', '6000');
            $this->all_pages = false;
            switch ($this->type) {
                case 'character_friends':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->getCharacterFriends($this->type_settings['id'], $iteration);
                    }
                    break;
                case 'character_following':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->getCharacterFollowing($this->type_settings['id'], $iteration);
                    }
                    break;
                case 'free_company_members':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->getFreeCompanyMembers($this->type_settings['id'], $iteration);
                    }
                    break;
                case 'linkshell_members':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->getLinkshellMembers($this->type_settings['id'], $iteration);
                    }
                    break;
                case 'grand_company_ranking':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->getGrandCompanyRanking($this->type_settings['week_month'], $this->type_settings['week'], $this->type_settings['worldname'], $this->type_settings['gcid'], $iteration);
                    }
                    break;
                case 'free_company_ranking':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->getFreeCompanyRanking($this->type_settings['week_month'], $this->type_settings['week'], $this->type_settings['worldname'], $this->type_settings['gcid'], $iteration);
                    }
                    break;
                case 'search_character':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->searchCharacter($this->type_settings['name'], $this->type_settings['server'], $this->type_settings['classjob'], $this->type_settings['race_tribe'], $this->type_settings['gcid'], $this->type_settings['blog_lang'], $this->type_settings['order'], $iteration);
                    }
                    break;
                case 'search_free_company':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->searchFreeCompany($this->type_settings['name'], $this->type_settings['server'], $this->type_settings['character_count'], $this->type_settings['activities'], $this->type_settings['roles'], $this->type_settings['activetime'], $this->type_settings['join'], $this->type_settings['house'], $this->type_settings['gcid'], $this->type_settings['order'], $iteration);
                    }
                    break;
                case 'search_linkshell':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->searchLinkshell($this->type_settings['name'], $this->type_settings['server'], $this->type_settings['character_count'], $this->type_settings['order'], $iteration);
                    }
                    break;
                case 'search_pvp_team':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->searchPvPTeam($this->type_settings['name'], $this->type_settings['server'], $this->type_settings['order'], $iteration);
                    }
                    break;
                case 'database':
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->searchDatabase($this->type_settings['type'], $this->type_settings['category'], $this->type_settings['subcatecory'], $this->type_settings['search'], $iteration);
                    }
                    break;
                case 'topics':
                case 'notices':
                case 'maintenance':
                case 'updates':
                case 'status':
                    $function_to_call = 'getLodestone'.mb_ucfirst($this->type, 'UTF-8');
                    for ($iteration = $current_page; $iteration <= $total_page; $iteration++) {
                        $this->$function_to_call($iteration);
                    }
                    break;
            }
            return true;
        }
        return false;
    }
    
    /**
     * Function to parse pages
     * @param array  $pages     List of pages
     * @param string $resultkey Result key (essentially entity type)
     *
     * @return \Simbiat\FFXIV\LodestoneModules\Parsers|\Simbiat\FFXIV\Lodestone
     */
    protected function pages(array $pages, string $resultkey): self
    {
        foreach ($pages as $page => $data) {
            foreach ($data as $key => $value) {
                if (\is_string($value)) {
                    $pages[$page][$key] = \html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5);
                }
            }
        }
        switch ($this->type) {
            case 'character_friends':
            case 'character_following':
            case 'free_company_members':
            case 'linkshell_members':
            case 'pvp_team_members':
                if (!empty($pages[0]['linkshell_community_id'])) {
                    $this->result[$resultkey][$this->type_settings['id']]['community_id'] = $pages[0]['linkshell_community_id'];
                }
                if (!empty($pages[0]['pvp_team_community_id'])) {
                    $this->result[$resultkey][$this->type_settings['id']]['community_id'] = $pages[0]['pvp_team_community_id'];
                }
                if (isset($pages[0]['page_current']) && \is_numeric($pages[0]['page_current'])) {
                    $this->result[$resultkey][$this->type_settings['id']]['page_current'] = $pages[0]['page_current'];
                } else {
                    $this->result[$resultkey][$this->type_settings['id']]['page_current'] = 1;
                }
                if (isset($pages[0]['page_total']) && \is_numeric($pages[0]['page_total'])) {
                    $this->result[$resultkey][$this->type_settings['id']]['page_total'] = $pages[0]['page_total'];
                } else {
                    $this->result[$resultkey][$this->type_settings['id']]['page_total'] = $this->result[$resultkey][$this->type_settings['id']]['page_current'];
                }
                if (isset($pages[0]['total']) && \is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey][$this->type_settings['id']]['members_count'] = $pages[0]['total'];
                }
                break;
            case 'grand_company_ranking':
            case 'free_company_ranking':
                if (isset($pages[0]['page_current']) && \is_numeric($pages[0]['page_current'])) {
                    $this->result[$resultkey][$this->type_settings['week']]['page_current'] = $pages[0]['page_current'];
                } else {
                    $this->result[$resultkey][$this->type_settings['week']]['page_current'] = 1;
                }
                if (isset($pages[0]['page_total']) && \is_numeric($pages[0]['page_total'])) {
                    $this->result[$resultkey][$this->type_settings['week']]['page_total'] = $pages[0]['page_total'];
                } else {
                    $this->result[$resultkey][$this->type_settings['week']]['page_total'] = $this->result[$resultkey][$this->type_settings['week']]['page_current'];
                }
                if (isset($pages[0]['total']) && \is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey][$this->type_settings['week']]['total'] = $pages[0]['total'];
                }
                break;
            case 'database':
                if (isset($pages[0]['page_current']) && \is_numeric($pages[0]['page_current'])) {
                    $this->result[$resultkey][$this->type_settings['type']]['page_current'] = $pages[0]['page_current'];
                } else {
                    $this->result[$resultkey][$this->type_settings['type']]['page_current'] = 1;
                }
                if (isset($pages[0]['page_total']) && \is_numeric($pages[0]['page_total'])) {
                    $this->result[$resultkey][$this->type_settings['type']]['page_total'] = $pages[0]['page_total'];
                } else {
                    $this->result[$resultkey][$this->type_settings['type']]['page_total'] = $this->result[$resultkey][$this->type_settings['type']]['page_current'];
                }
                if (isset($pages[0]['total']) && \is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey][$this->type_settings['type']]['total'] = $pages[0]['total'];
                }
                break;
            default:
                if (isset($pages[0]['page_current']) && \is_numeric($pages[0]['page_current'])) {
                    $this->result[$resultkey]['page_current'] = $pages[0]['page_current'];
                } else {
                    $this->result[$resultkey]['page_current'] = 1;
                }
                if (isset($pages[0]['page_total']) && \is_numeric($pages[0]['page_total'])) {
                    $this->result[$resultkey]['page_total'] = $pages[0]['page_total'];
                } else {
                    $this->result[$resultkey]['page_total'] = $this->result[$resultkey]['page_current'];
                }
                if (isset($pages[0]['total']) && \is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey]['total'] = $pages[0]['total'];
                }
                break;
        }
        #Linkshell members specific
        if (!empty($pages[0]['linkshell_name'])) {
            $this->result[$resultkey][$this->type_settings['id']]['name'] = mb_trim($pages[0]['linkshell_name'], null, 'UTF-8');
            if (!empty($pages[0]['linkshell_server'])) {
                if (\preg_match('/[a-zA-Z0-9]{40}/mi', $this->type_settings['id'])) {
                    $this->result[$resultkey][$this->type_settings['id']]['data_center'] = $pages[0]['linkshell_server'];
                } else {
                    $this->result[$resultkey][$this->type_settings['id']]['server'] = $pages[0]['linkshell_server'];
                }
            }
            if (!empty($pages[0]['linkshellformed'])) {
                $this->result[$resultkey][$this->type_settings['id']]['formed'] = $pages[0]['linkshellformed'];
            }
            if (!empty($pages[0]['emptygroup'])) {
                $this->result[$resultkey][$this->type_settings['id']]['page_current'] = 0;
                $this->result[$resultkey][$this->type_settings['id']]['page_total'] = 0;
            }
        }
        #PvpTeam members specific
        if (!empty($pages[0]['pvpname'])) {
            $this->result[$resultkey][$this->type_settings['id']]['name'] = $pages[0]['pvpname'];
            if (!empty($pages[0]['server'])) {
                $this->result[$resultkey][$this->type_settings['id']]['data_center'] = $pages[0]['server'];
            }
            if (!empty($pages[0]['formed'])) {
                $this->result[$resultkey][$this->type_settings['id']]['formed'] = $pages[0]['formed'];
            }
            $this->result[$resultkey][$this->type_settings['id']]['crest'] = $this->crest($pages[0], 'pvpcrest');
        }
        return $this;
    }
    
    /**
     * Getting crest from array based on "keybase" identifying numbered keys in the array
     * @param array  $tempresult Array to process
     * @param string $keybase    Prefix for key
     *
     * @return array
     */
    protected function crest(array $tempresult, string $keybase): array
    {
        $crest[] = \str_replace(['40x40', '64x64'], '128x128', $tempresult[$keybase.'1']);
        if (!empty($tempresult[$keybase.'2'])) {
            $crest[] = \str_replace(['40x40', '64x64'], '128x128', $tempresult[$keybase.'2']);
        }
        if (!empty($tempresult[$keybase.'3'])) {
            $crest[] = \str_replace(['40x40', '64x64'], '128x128', $tempresult[$keybase.'3']);
        }
        foreach ($crest as $key => $value) {
            #Lodestone now serves one of the default emblems using non-standard URL sometimes, so we change it to a standard one
            if ($value === 'https://lds-img.finalfantasyxiv.com/h/s/79TDIxvk2GApbpkZ8_0xbfvGAM.png') {
                $crest[$key] = 'https://img2.finalfantasyxiv.com/c/S00_9a8096c55d9b806ba05b5626ccfa14e8_00_128x128.png';
            }
            if ($value === 'https://lds-img.finalfantasyxiv.com/h/O/qCQ49Y0JW_QjV6fkYMeHiW23qk.png') {
                $crest[$key] = 'https://img2.finalfantasyxiv.com/c/S02_e4ecc91f18ffeea79c65a3b0a101f184_00_128x128.png';
            }
            if ($value === 'https://lds-img.finalfantasyxiv.com/h/H/yJIZkIxUaa4hjXq_3lBQtumV20.png') {
                $crest[$key] = 'https://img2.finalfantasyxiv.com/c/S01_49d2902352dde56ae3c6423a937ac151_00_128x128.png';
            }
        }
        return $crest;
    }
    
    /**
     * Generate Grand Company details
     * @param array $tempresult
     *
     * @return array
     */
    protected function grandcompany(array $tempresult): array
    {
        $gc = [];
        if (!empty($tempresult['gcrank'])) {
            $gc['rank'] = $tempresult['gcrank'];
        }
        if (!empty($tempresult['gc_rank_icon'])) {
            $gc['icon'] = $tempresult['gc_rank_icon'];
        }
        return $gc;
    }
    
    /**
     * Generate free company details
     * @param array $tempresult
     *
     * @return array
     */
    protected function freecompany(array $tempresult): array
    {
        return [
            'id' => $tempresult['fc_id'],
            'name' => $tempresult['fcname'],
            'crest' => $this->crest($tempresult, 'fccrestimg'),
        ];
    }
    
    /**
     * Process character jobs
     * @return array
     */
    protected function jobs(): array
    {
        $temp_jobs = [];
        if (!$this->regexfail(\preg_match_all(Regex::CHARACTER_JOBS, $this->html, $jobs, \PREG_SET_ORDER), \preg_last_error(), 'CHARACTER_JOBS')) {
            return [];
        }
        foreach ($jobs as $job) {
            $job['expcur'] = \preg_replace('/\D/', '', $job['expcur']);
            $job['expmax'] = \preg_replace('/\D/', '', $job['expmax']);
            $temp_jobs[$this->converters->classToJob($job['name'])] = $this->jobDetails($job);
        }
        return $temp_jobs;
    }
    
    /**
     * Process job details
     * @param array $job
     *
     * @return array
     */
    protected function jobDetails(array $job): array
    {
        return [
            'level' => (\is_numeric($job['level']) ? (int)$job['level'] : 0),
            'specialist' => !empty($job['specialist']),
            'expcur' => (\is_numeric($job['expcur']) ? (int)$job['expcur'] : 0),
            'expmax' => (\is_numeric($job['expmax']) ? (int)$job['expmax'] : 0),
            'icon' => $job['icon'],
        ];
    }
    
    /**
     * Process character attributes
     * @return array
     */
    protected function attributes(): array
    {
        $temp_attrs = [];
        if (!$this->regexfail(\preg_match_all(Regex::CHARACTER_ATTRIBUTES, $this->html, $attributes, \PREG_SET_ORDER), \preg_last_error(), 'CHARACTER_ATTRIBUTES')) {
            return [];
        }
        foreach ($attributes as $attribute) {
            if (empty($attribute['name'])) {
                $temp_attrs[$attribute['name2']] = $attribute['value2'];
            } else {
                $temp_attrs[$attribute['name']] = $attribute['value'];
            }
        }
        return $temp_attrs;
    }
    
    /**
     * Process collectibles
     * @param string $type
     *
     * @return array
     */
    protected function collectibles(string $type): array
    {
        $collectables = [];
        if ($type === 'mounts') {
            \preg_match_all(Regex::CHARACTER_MOUNTS, $this->html, $results, \PREG_SET_ORDER);
        } elseif ($type === 'minions') {
            \preg_match_all(Regex::CHARACTER_MINIONS, $this->html, $results, \PREG_SET_ORDER);
        }
        if (!empty($results[0][0])) {
            \preg_match_all(Regex::COLLECTIBLE, $results[0][0], $results, \PREG_SET_ORDER);
            $collectables = \array_column($results, 2, 1);
        }
        return $collectables;
    }
    
    /**
     * Process items
     * @return array
     */
    protected function items(): array
    {
        if (!$this->regexfail(\preg_match_all(Regex::CHARACTER_GEAR, $this->html, $temp_results, \PREG_SET_ORDER), \preg_last_error(), 'CHARACTER_GEAR')) {
            return [];
        }
        #Remove non-named groups
        foreach ($temp_results as $key => $temp_result) {
            foreach ($temp_result as $key2 => $details) {
                if (\is_numeric($key2) || empty($details)) {
                    unset($temp_results[$key][(int)$key2]);
                }
            }
            $temp_results[$key]['armoireable'] = $this->converters->imageToBool($temp_result['armoireable']);
            $temp_results[$key]['hq'] = !empty($temp_result['hq']);
            $temp_results[$key]['unique'] = !empty($temp_result['unique']);
            #Requirements
            $temp_results[$key]['requirements'] = [
                'level' => $temp_result['level'],
                'classes' => (in_array($temp_result['classes'], ['Disciple of the Land', 'Disciple of the Hand', 'Disciple of Magic', 'Disciple of War', 'Disciples of War or Magic', 'All Classes', 'ギャザラー', 'Sammler', 'Récolteurs', 'Handwerker', 'Artisans', 'クラフター', 'Magier', 'Mages', 'ソーサラー', 'Krieger', 'Combattants', 'ファイター', 'Krieger, Magier', 'Combattants et mages', 'ファイター ソーサラー', 'Alle Klassen', 'Toutes les classes', '全クラス']) ? $temp_result['classes'] : \explode(' ', $temp_result['classes'])),
            ];
            #Attributes
            for ($iteration = 1; $iteration <= 15; $iteration++) {
                if (!empty($temp_result['attrname'.$iteration])) {
                    $temp_results[$key]['attributes'][$temp_result['attrname'.$iteration]] = $temp_result['attrvalue'.$iteration];
                    unset($temp_results[$key]['attrname'.$iteration], $temp_results[$key]['attrvalue'.$iteration]);
                }
            }
            #Materia
            if (!empty($temp_result['materianame1']) || !empty($temp_result['materianame2']) || !empty($temp_result['materianame3']) || !empty($temp_result['materianame4']) || !empty($temp_result['materianame5'])) {
                $temp_results[$key]['materia'] = [];
                for ($iteration = 1; $iteration <= 5; $iteration++) {
                    if (!empty($temp_result['materianame'.$iteration])) {
                        $temp_results[$key]['materia'] = [
                            'name' => $temp_result['materianame'.$iteration],
                            'attribute' => $temp_result['materiaattr'.$iteration],
                            'bonus' => $temp_result['materiaval'.$iteration],
                        ];
                        unset($temp_results[$key]['materianame'.$iteration], $temp_results[$key]['materiaattr'.$iteration], $temp_results[$key]['materiaval'.$iteration]);
                    }
                }
            }
            #Crafting
            if (!empty($temp_result['repair'])) {
                $temp_results[$key]['crafting']['class'] = $temp_result['repair'];
                $temp_results[$key]['crafting']['materials'] = $temp_result['materials'];
                if (empty($temp_result['desynthesizable'])) {
                    $temp_results[$key]['crafting']['desynth'] = false;
                } else {
                    $temp_results[$key]['crafting']['desynth'] = $temp_result['desynthesizable'];
                }
                if (empty($temp_result['melding'])) {
                    $temp_results[$key]['crafting']['melding'] = false;
                } else {
                    $temp_results[$key]['crafting']['melding'] = $temp_result['melding'];
                    $temp_results[$key]['crafting']['advancedmelding'] = empty($temp_result['advancedmelding']);
                }
                $temp_results[$key]['crafting']['convertible'] = $this->converters->imageToBool($temp_result['convertible']);
            }
            #Trading
            if (empty($temp_result['price'])) {
                $temp_results[$key]['trading']['price'] = NULL;
            } else {
                $temp_results[$key]['trading']['price'] = $temp_result['price'];
            }
            $temp_results[$key]['trading']['sellable'] = empty($temp_result['unsellable']);
            $temp_results[$key]['trading']['marketable'] = empty($temp_result['marketprohibited']);
            $temp_results[$key]['trading']['tradeable'] = empty($temp_result['untradeable']);
            #Link to shop, if present
            if (empty($temp_result['shop'])) {
                $temp_results[$key]['trading']['shop'] = NULL;
            } else {
                $temp_results[$key]['trading']['shop'] = sprintf(Routes::LODESTONE_URL_BASE, $this->language).$temp_result['shop'];
            }
            #Customization
            $temp_results[$key]['customization'] = [
                'crestable' => $this->converters->imageToBool($temp_result['crestable']),
                'glamourable' => $this->converters->imageToBool($temp_result['glamourable']),
                'projectable' => $this->converters->imageToBool($temp_result['projectable']),
                'dyeable' => $this->converters->imageToBool($temp_result['dyeable']),
            ];
            #Glamour
            if (!empty($temp_result['glamourname'])) {
                $temp_results[$key]['customization']['glamour'] = [
                    'id' => $temp_result['glamourid'],
                    'name' => $temp_result['glamourname'],
                    'icon' => $temp_result['glamouricon'],
                ];
            }
            unset($temp_results[$key]['level'], $temp_results[$key]['classes'], $temp_results[$key]['price'], $temp_results[$key]['unsellable'], $temp_results[$key]['marketprohibited'], $temp_results[$key]['repair'], $temp_results[$key]['materials'], $temp_results[$key]['desynthesizable'], $temp_results[$key]['melding'], $temp_results[$key]['advancedmelding'], $temp_results[$key]['convertible'], $temp_results[$key]['glamourname'], $temp_results[$key]['glamourid'], $temp_results[$key]['glamouricon'], $temp_results[$key]['crestable'], $temp_results[$key]['glamourable'], $temp_results[$key]['projectable'], $temp_results[$key]['dyeable'], $temp_results[$key]['untradeable'], $temp_results[$key]['shop']);
        }
        return $temp_results;
    }
    
    /**
     * Convert stars to integer
     * @param array $stars
     *
     * @return int
     */
    protected function stars(array $stars): int
    {
        if (isset($stars['star4'])) {
            return 4;
        }
        if (isset($stars['star3'])) {
            return 3;
        }
        if (isset($stars['star2'])) {
            return 2;
        }
        if (isset($stars['star1'])) {
            return 1;
        }
        return 0;
    }
    
    /**
     * Function to return error in case regex resulted in 0 or error
     *
     * @param int|bool   $matchescount Number of matches or `false`
     * @param int|string $errorcode    Error code, if available
     * @param int|string $regexid      Regex, that failed
     *
     * @return bool
     */
    protected function regexfail(int|bool $matchescount, int|string $errorcode, int|string $regexid): bool
    {
        if ($matchescount === 0) {
            $this->errorRegister('No matches found for regex ('.$regexid.')');
            return false;
        }
        if ($matchescount === false) {
            $this->errorRegister('Regex ('.$regexid.') failed with error code '.$errorcode);
            return false;
        }
        return true;
    }
    
    /**
     * Function to save error
     * @param string $errormessage
     * @param string $type
     * @param int    $started
     *
     * @return void
     */
    protected function errorRegister(string $errormessage, string $type = 'parse', int $started = 0): void
    {
        $this->last_error = ['type' => $this->type, 'id' => ($this->type_settings['id'] ?? NULL), 'error' => $errormessage, 'url' => $this->url];
        $this->errors[] = $this->last_error;
        if ($this->benchmark) {
            if ($started === 0) {
                $duration = 0;
            } else {
                $finished = \hrtime(true);
                $duration = $finished - $started;
            }
            if ($type === 'http') {
                $this->result['benchmark']['http_time'][] = \date('H:i:s.'.sprintf('%06d', ($duration / 1000)), (int)($duration / 1000000000));
                $duration = 0;
            }
            $this->benchUpdate($duration);
        }
    }
    
    /**
     * Update benchmark details
     * @param int $duration
     *
     * @return void
     */
    protected function benchUpdate(int $duration): void
    {
        $this->result['benchmark']['parse_time'][] = \date('H:i:s.'.sprintf('%06d', ($duration / 1000)), (int)($duration / 1000000000));
        $this->result['benchmark']['memory'] = $this->converters->memory(\memory_get_usage(true));
        $this->result['benchmark']['memory_peak'] = $this->converters->memory(\memory_get_peak_usage(true));
    }
    
    /**
     * Function to reset last error (in case false positive)
     * @return void
     */
    protected function errorUnregister(): void
    {
        \array_pop($this->errors);
        if (empty($this->errors)) {
            $this->last_error = NULL;
        } else {
            $this->last_error = \end($this->errors);
        }
    }
}
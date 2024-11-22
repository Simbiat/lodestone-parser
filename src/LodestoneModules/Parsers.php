<?php
declare(strict_types = 1);

namespace Simbiat\LodestoneModules;

use function in_array, is_array;

/**
 * Main parsing logic
 */
trait Parsers
{
    /**
     * Parse Lodestone HTML
     * @return \Simbiat\LodestoneModules\Parsers|\Simbiat\Lodestone
     */
    protected function parse(): self
    {
        $started = hrtime(true);
        #Set array key for results
        $resultkey = match ($this->type) {
            'searchCharacter', 'Character', 'CharacterJobs', 'CharacterFriends', 'CharacterFollowing', 'Achievements', 'AchievementDetails' => 'characters',
            'FreeCompanyMembers', 'searchFreeCompany', 'FreeCompany' => 'freecompanies',
            'LinkshellMembers', 'searchLinkshell' => 'linkshells',
            'PvPTeamMembers', 'searchPvPTeam' => 'pvpteams',
            'Database' => 'database',
            default => $this->type,
        };
        $resultsubkey = match ($this->type) {
            'CharacterJobs' => 'jobs',
            'CharacterFriends' => 'friends',
            'CharacterFollowing' => 'following',
            'Achievements', 'AchievementDetails' => 'achievements',
            'FreeCompanyMembers', 'LinkshellMembers', 'PvPTeamMembers' => 'members',
            'frontline', 'GrandCompanyRanking', 'FreeCompanyRanking' => $this->typeSettings['week_month'],
            'Database' => $this->typeSettings['type'],
            default => '',
        };
        try {
            $this->lasterror = NULL;
            $this->html = (new HttpRequest($this->useragent))->get($this->url);
        } catch (\Exception $e) {
            $this->errorRegister($e->getMessage(), 'http', $started);
            if ($e->getCode() === 404) {
                $this->addToResults($resultkey, $resultsubkey, 404);
            } elseif ($this->type === 'Character' && $e->getCode() === 403) {
                $this->addToResults($resultkey, $resultsubkey, ['private' => true]);
            }
            return $this;
        }
        if ($this->benchmark) {
            $finished = hrtime(true);
            $duration = $finished - $started;
            $this->result['benchmark']['httptime'][] = date('H:i:s.'.sprintf('%06d', ($duration / 1000)), (int)($duration / 1000000000));
        }
        $started = hrtime(true);
        try {
            $this->lasterror = NULL;
            #Parsing of pages
            if (in_array($this->type, [
                'searchCharacter',
                'CharacterFriends',
                'CharacterFollowing',
                'FreeCompanyMembers',
                'LinkshellMembers',
                'PvPTeamMembers',
                'searchFreeCompany',
                'searchLinkshell',
                'searchPvPTeam',
                'topics',
                'notices',
                'maintenance',
                'updates',
                'status',
            ])) {
                if (!$this->regexfail(preg_match_all(Regex::PAGECOUNT, $this->html, $pages, PREG_SET_ORDER), preg_last_error(), 'PAGECOUNT')) {
                    return $this;
                }
                $this->pages($pages, $resultkey);
            }
            if (in_array($this->type, ['GrandCompanyRanking', 'FreeCompanyRanking'])) {
                if (!$this->regexfail(preg_match_all(Regex::PAGECOUNT2, $this->html, $pages, PREG_SET_ORDER), preg_last_error(), 'PAGECOUNT2')) {
                    return $this;
                }
                $this->pages($pages, $resultkey);
            }
            if ($this->type === 'Database') {
                if (!$this->regexfail(preg_match_all(Regex::DBPAGECOUNT, $this->html, $pages, PREG_SET_ORDER), preg_last_error(), 'DBPAGECOUNT')) {
                    return $this;
                }
                $this->pages($pages, $resultkey);
            }
            
            #Banners special precut
            if ($this->type === 'banners') {
                if (!$this->regexfail(preg_match(Regex::BANNERS, $this->html, $banners), preg_last_error(), 'BANNERS')) {
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
                if (!$this->regexfail(preg_match_all(Regex::NOTICES, $this->html, $notices, PREG_SET_ORDER), preg_last_error(), 'NOTICES')) {
                    return $this;
                }
                $this->html = $notices[0][0];
            }
            
            #Main (general) parser
            #Setting initial regex
            $regex = match ($this->type) {
                'searchPvPTeam' => Regex::PVPTEAMLIST,
                'searchLinkshell' => Regex::LINKSHELLLIST,
                'searchFreeCompany' => Regex::FREECOMPANYLIST,
                'banners' => Regex::BANNERS2,
                'worlds' => Regex::DATACENTERS,
                'feast' => Regex::FEAST,
                'frontline' => Regex::FRONTLINE,
                'GrandCompanyRanking' => Regex::GCRANKING,
                'FreeCompanyRanking' => Regex::FCRANKING,
                'deepdungeon' => Regex::DEEPDUNGEON,
                'FreeCompany' => Regex::FREECOMPANY,
                'Achievements' => Regex::ACHIEVEMENTS_LIST,
                'AchievementDetails' => Regex::ACHIEVEMENT_DETAILS,
                'Character' => Regex::CHARACTER_GENERAL,
                'CharacterJobs' => Regex::CHARACTER_JOBS,
                'topics', 'news' => Regex::NEWS,
                'notices', 'maintenance', 'updates', 'status' => Regex::NOTICES2,
                'Database' => Regex::DBLIST,
                default => Regex::CHARACTERLIST,
            };
            
            #Uncomment for debugging purposes
            #file_put_contents(__DIR__.'/regex.txt', $regex);
            #file_put_contents(__DIR__.'/html.txt', $this->html);
            
            if (!$this->regexfail(preg_match_all($regex, $this->html, $tempResults, PREG_SET_ORDER), preg_last_error(), 'main regex')) {
                if (in_array($this->type, [
                    'searchCharacter',
                    'CharacterFriends',
                    'CharacterFollowing',
                    'FreeCompanyMembers',
                    'LinkshellMembers',
                    'PvPTeamMembers',
                    'searchFreeCompany',
                    'searchLinkshell',
                    'searchPvPTeam',
                    'topics',
                    'notices',
                    'maintenance',
                    'updates',
                    'status',
                ])) {
                    if (!empty($this->typeSettings['id']) && !empty($this->result[$resultkey][$this->typeSettings['id']][$resultsubkey]['total'])) {
                        return $this;
                    }
                    $this->errorUnregister();
                } else {
                    return $this;
                }
            }
            
            #Character results update
            if ($this->type === 'Character') {
                #Remove non-named groups before rearranging results to avoid overwrites
                foreach ($tempResults as $key => $tempresult) {
                    foreach ($tempresult as $key2 => $details) {
                        if (is_numeric($key2) || empty($details)) {
                            unset($tempResults[$key][$key2]);
                        }
                    }
                }
                $tempResults = [array_merge($tempResults[0], $tempResults[1], $tempResults[2] ?? [])];
            }
            
            foreach ($tempResults as $key => $tempresult) {
                #Remove unnamed groups and empty values
                foreach ($tempresult as $key2 => $value) {
                    if (is_numeric($key2) || empty($value)) {
                        unset($tempResults[$key][$key2], $tempresult[$key2]);
                    }
                }
                #Decode HTML entities
                foreach ($tempresult as $key2 => $value) {
                    #Decode in the data inside loop
                    $tempresult[$key2] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
                    #Decode in original data (for consistency)
                    $tempResults[$key][$key2] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
                }
                
                #Specific processing
                switch ($this->type) {
                    case 'searchPvPTeam':
                    case 'searchFreeCompany':
                        $tempResults[$key]['crest'] = $this->crest($tempresult, 'crest');
                        break;
                    case 'searchCharacter':
                    case 'CharacterFriends':
                    case 'CharacterFollowing':
                    case 'FreeCompanyMembers':
                    case 'LinkshellMembers':
                    case 'PvPTeamMembers':
                        if (!empty($tempresult['linkshellcommunityid'])) {
                            $tempResults[$key]['communityid'] = $tempresult['linkshellcommunityid'];
                        }
                        if (!empty($tempresult['pvpteamcommunityid'])) {
                            $tempResults[$key]['communityid'] = $tempresult['pvpteamcommunityid'];
                        }
                        if ($this->type === 'FreeCompanyMembers') {
                            $tempResults[$key]['rankid'] = $this->converters->FCRankID($tempresult['rankicon']);
                        }
                        if (!empty($tempresult['gcname'])) {
                            $tempResults[$key]['grandCompany'] = $this->grandcompany($tempresult);
                        }
                        if (!empty($tempresult['fcid'])) {
                            $tempResults[$key]['freeCompany'] = $this->freecompany($tempresult);
                        }
                        if (!empty($tempresult['lsrank']) && !empty($tempresult['lsrankicon'])) {
                            $tempResults[$key]['rankicon'] = $tempresult['lsrankicon'];
                        }
                        #Specific for linkshell members
                        if (empty($this->result['server'])) {
                            $this->result[$resultkey][$this->typeSettings['id']]['server'] = $tempresult['server'];
                        }
                        if (!empty($pages[0]['linkshellserver'])) {
                            $this->result[$resultkey][$this->typeSettings['id']]['server'] = $pages[0]['linkshellserver'];
                        }
                        break;
                    case 'frontline':
                    case 'GrandCompanyRanking':
                        if (!empty($tempresult['gcname'])) {
                            $tempResults[$key]['grandCompany'] = $this->grandcompany($tempresult);
                        }
                        if (!empty($tempresult['fcid'])) {
                            $tempResults[$key]['freeCompany'] = $this->freecompany($tempresult);
                        }
                        $tempResults[$key]['rank'] = ($tempresult['rank2'] ?: $tempresult['rank1']);
                        break;
                    case 'FreeCompanyRanking':
                        $tempResults[$key]['crest'] = $this->crest($tempresult, 'crest');
                        $tempResults[$key]['rank'] = ($tempresult['rank2'] ?: $tempresult['rank1']);
                        break;
                    case 'topics':
                    case 'news':
                    case 'notices':
                    case 'maintenance':
                    case 'updates':
                    case 'status':
                        $tempResults[$key]['url'] = sprintf(Routes::LODESTONE_URL_BASE, $this->language).$tempresult['url'];
                        break;
                    case 'deepdungeon':
                        $tempResults[$key]['job'] = [
                            'name' => $tempresult['job'],
                            'icon' => $tempresult['jobicon'],
                        ];
                        if (!empty($tempresult['jobform'])) {
                            $tempResults[$key]['job']['form'] = $tempresult['jobform'];
                        }
                        break;
                    case 'FreeCompany':
                        $tempResults[$key]['crest'] = $this->crest($tempresult, 'crest');
                        #Ranking checks for --
                        if ($tempresult['weekly_rank'] === '--') {
                            $tempResults[$key]['weekly_rank'] = NULL;
                        }
                        if ($tempresult['monthly_rank'] === '--') {
                            $tempResults[$key]['monthly_rank'] = NULL;
                        }
                        #Estates
                        if (!empty($tempresult['estate_name'])) {
                            $tempResults[$key]['estate']['name'] = $tempresult['estate_name'];
                        }
                        if (!empty($tempresult['estate_address'])) {
                            $tempResults[$key]['estate']['address'] = $tempresult['estate_address'];
                        }
                        if (!empty($tempresult['estate_greeting']) && !in_array($tempresult['estate_greeting'], ['No greeting available.', 'グリーティングメッセージが設定されていません。', 'Il n\'y a aucun message d\'accueil.', 'Keine Begrüßung vorhanden.'])) {
                            $tempResults[$key]['estate']['greeting'] = $tempresult['estate_greeting'];
                        }
                        #Grand companies reputation
                        for ($i = 1; $i <= 3; $i++) {
                            if (!empty($tempresult['gcname'.$i])) {
                                $tempResults[$key]['reputation'][$tempresult['gcname'.$i]] = $tempresult['gcrepu'.$i];
                                unset($tempResults[$key]['gcname'.$i], $tempResults[$key]['gcrepu'.$i]);
                            }
                        }
                        #Focus
                        for ($i = 1; $i <= 9; $i++) {
                            if (!empty($tempresult['focusname'.$i])) {
                                $tempResults[$key]['focus'][] = [
                                    'name' => $tempresult['focusname'.$i],
                                    'enabled' => (empty($tempresult['focusoff'.$i]) ? 1 : 0),
                                    'icon' => $tempresult['focusicon'.$i],
                                ];
                                unset($tempResults[$key]['focusname'.$i], $tempResults[$key]['focusoff'.$i], $tempResults[$key]['focusicon'.$i]);
                            }
                        }
                        #Seeking
                        for ($i = 1; $i <= 5; $i++) {
                            if (!empty($tempresult['seekingname'.$i])) {
                                $tempResults[$key]['seeking'][] = [
                                    'name' => $tempresult['seekingname'.$i],
                                    'enabled' => (empty($tempresult['seekingoff'.$i]) ? 1 : 0),
                                    'icon' => $tempresult['seekingicon'.$i],
                                ];
                                unset($tempResults[$key]['seekingname'.$i], $tempResults[$key]['seekingoff'.$i], $tempResults[$key]['seekingicon'.$i]);
                            }
                        }
                        #Trim stuff
                        $tempResults[$key]['slogan'] = trim($tempresult['slogan'] ?? '');
                        $tempResults[$key]['active'] = trim($tempresult['active']);
                        $tempResults[$key]['recruitment'] = trim($tempresult['recruitment']);
                        $tempResults[$key]['grandCompany'] = trim($tempresult['grandCompany']);
                        if (empty($tempresult['members_count'])) {
                            $tempResults[$key]['members_count'] = 0;
                        } else {
                            $tempResults[$key]['members_count'] = (int)$tempresult['members_count'];
                        }
                        break;
                    case 'Achievements':
                        $tempResults[$key]['title'] = !empty($tempresult['title']);
                        $tempResults[$key]['item'] = !empty($tempresult['item']);
                        if (empty($tempresult['time'])) {
                            $tempResults[$key]['time'] = NULL;
                        }
                        if (empty($tempresult['points'])) {
                            $tempResults[$key]['points'] = 0;
                        }
                        break;
                    case 'AchievementDetails':
                        if (empty($tempresult['title'])) {
                            $tempResults[$key]['title'] = NULL;
                        }
                        if (empty($tempresult['item'])) {
                            $tempResults[$key]['item'] = NULL;
                        }
                        if (!empty($tempresult['itemname'])) {
                            $tempResults[$key]['item'] = [
                                'id' => $tempresult['itemid'],
                                'name' => $tempresult['itemname'],
                                'icon' => $tempresult['itemicon'],
                            ];
                            unset($tempResults[$key]['itemid'], $tempResults[$key]['itemname'], $tempResults[$key]['itemicon']);
                        }
                        if (empty($tempresult['time'])) {
                            $tempResults[$key]['time'] = NULL;
                        }
                        if (empty($tempresult['points'])) {
                            $tempResults[$key]['points'] = 0;
                        }
                        break;
                    case 'Database':
                        $tempResults[$key]['name'] = str_replace(['<i>', '</i>'], '', trim($tempResults[$key]['name']));
                        switch ($this->typeSettings['type']) {
                            case 'achievement':
                                $tempResults[$key]['reward'] = (trim($tempResults[$key]['column1']) === '-' ? NULL : trim($tempResults[$key]['column1']));
                                $tempResults[$key]['points'] = (int)($tempResults[$key]['column2'] ?? 0);
                                break;
                            case 'quest':
                                $tempResults[$key]['area'] = (trim($tempResults[$key]['column1']) === '-' ? NULL : trim($tempResults[$key]['column1']));
                                $tempResults[$key]['character_level'] = (int)($tempResults[$key]['column2'] ?? 0);
                                break;
                            case 'duty':
                                $tempResults[$key]['character_level'] = (int)($tempResults[$key]['column1'] ?? 0);
                                $tempResults[$key]['item_level'] = (trim($tempResults[$key]['column2']) === '-' ? 0 : (int)$tempResults[$key]['column2']);
                                break;
                            case 'item':
                                $tempResults[$key]['item_level'] = (trim($tempResults[$key]['column1']) === '-' ? 0 : (int)$tempResults[$key]['column1']);
                                $tempResults[$key]['character_level'] = (trim($tempResults[$key]['column2']) === '-' ? 0 : (int)$tempResults[$key]['column2']);
                                break;
                            case 'recipe':
                                if (isset($tempResults[$key]['extraicon'])) {
                                    $tempResults[$key]['collectable'] = true;
                                } else {
                                    $tempResults[$key]['collectable'] = false;
                                }
                                if (!isset($tempResults[$key]['master'])) {
                                    $tempResults[$key]['master'] = NULL;
                                }
                                $tempResults[$key]['recipe_level'] = (trim($tempResults[$key]['column1']) === '-' ? 0 : (int)$tempResults[$key]['column1']);
                                $tempResults[$key]['stars'] = $this->stars($tempResults[$key]);
                                if (isset($tempResults[$key]['expert'])) {
                                    $tempResults[$key]['expert'] = true;
                                } else {
                                    $tempResults[$key]['expert'] = false;
                                }
                                $tempResults[$key]['item_level'] = (trim($tempResults[$key]['column2']) === '-' ? 0 : (int)$tempResults[$key]['column2']);
                                break;
                            case 'gathering':
                                if (isset($tempResults[$key]['extraicon'])) {
                                    $tempResults[$key]['collectable'] = true;
                                } else {
                                    $tempResults[$key]['collectable'] = false;
                                }
                                if (isset($tempResults[$key]['hidden'])) {
                                    $tempResults[$key]['hidden'] = true;
                                } else {
                                    $tempResults[$key]['hidden'] = false;
                                }
                                $tempResults[$key]['level'] = (trim($tempResults[$key]['column1']) === '-' ? 0 : (int)$tempResults[$key]['column1']);
                                $tempResults[$key]['stars'] = $this->stars($tempResults[$key]);
                                break;
                            case 'shop':
                                $tempResults[$key]['area'] = preg_replace('/\s+((Other Locations)|(ほか)|(Etc.)|(Anderer Ort))/miu', '', str_replace(['<i>', '</i>'], '', trim($tempResults[$key]['column1'])));
                                break;
                            case 'text_command':
                                if (in_array($tempResults[$key]['column1'], ['Yes', '○', 'oui', '○']) === true) {
                                    $tempResults[$key]['Windows'] = true;
                                } else {
                                    $tempResults[$key]['Windows'] = false;
                                }
                                if (in_array($tempResults[$key]['column2'], ['Yes', '○', 'oui', '○']) === true) {
                                    $tempResults[$key]['PS4'] = true;
                                } else {
                                    $tempResults[$key]['PS4'] = false;
                                }
                                if (in_array($tempResults[$key]['column3'], ['Yes', '○', 'oui', '○']) === true) {
                                    $tempResults[$key]['Mac'] = true;
                                } else {
                                    $tempResults[$key]['Mac'] = false;
                                }
                                break;
                        }
                        break;
                    case 'Character':
                        #There are cases of characters not returning a proper race or clan (usually both).
                        #I've reported this issue to Square Enix several times, and they simply update affected characters.
                        #This breaks normal update routines, though, so both race and clan are defaulted to what the game suggests for new characters: Midlander Hyur. Appropriate comments are added, though for information purposes.
                        $tempResults[$key]['private'] = !empty($tempResults[$key]['private']);
                        #Portrait
                        $tempResults[$key]['portrait'] = str_replace('c0.jpg', 'l0.jpg', $tempresult['avatar']);
                        #Since release of Dawntrail, if profile is private you won't get any of the fields below
                        if ($tempResults[$key]['private'] === false) {
                            $tempResults[$key]['race'] = trim($tempResults[$key]['race']);
                            $tempResults[$key]['clan'] = trim($tempResults[$key]['clan']);
                            if ($tempResults[$key]['race'] === '----') {
                                $tempResults[$key]['race'] = null;
                                $tempResults[$key]['comment'] = 'No race';
                            }
                            if ($tempResults[$key]['clan'] === '----') {
                                $tempResults[$key]['clan'] = null;
                                if ($tempResults[$key]['comment'] === 'No race') {
                                    $tempResults[$key]['comment'] .= ' and clan';
                                } else {
                                    $tempResults[$key]['comment'] = 'No clan';
                                }
                            }
                            $tempResults[$key]['nameday'] = str_replace('32st', '32nd', $tempResults[$key]['nameday']);
                            if (!empty($tempresult['uppertitle'])) {
                                $tempResults[$key]['title'] = $tempresult['uppertitle'];
                            } elseif (!empty($tempresult['undertitle'])) {
                                $tempResults[$key]['title'] = $tempresult['undertitle'];
                            } else {
                                $tempResults[$key]['title'] = '';
                            }
                            #Gender to text
                            $tempResults[$key]['gender'] = ($tempresult['gender'] === '♂' ? 'male' : 'female');
                            #Guardian
                            if (empty($tempResults[$key]['guardian'])) {
                                $tempResults[$key]['guardian']['name'] = match (mb_strtolower($this->language, 'UTF-8')) {
                                    'jp', 'ja' => 'ハルオーネ',
                                    'fr' => 'Halone, la Conquérante',
                                    'de' => 'Halone - Die Furie',
                                    default => 'Halone, the Fury',
                                };
                                $tempResults[$key]['guardian']['icon'] = 'https://img.finalfantasyxiv.com/lds/h/5/qmgVmQ1o6skxdK4hDEbIV5NETA.png';
                                if (empty($tempResults[$key]['comment'])) {
                                    $tempResults[$key]['comment'] = 'Defaulted guardian';
                                } else {
                                    $tempResults[$key]['comment'] .= ' and guardian';
                                }
                            } else {
                                $tempResults[$key]['guardian'] = [
                                    'name' => $tempresult['guardian'],
                                    'icon' => $tempresult['guardianicon'],
                                ];
                            }
                            #City
                            $tempResults[$key]['city'] = [
                                'name' => $tempresult['city'],
                                'icon' => $tempresult['cityicon'],
                            ];
                            #Grand Company
                            if (!empty($tempresult['gcname'])) {
                                $tempResults[$key]['grandCompany'] = $this->grandcompany($tempresult);
                            }
                            #Free Company
                            if (!empty($tempresult['fcid'])) {
                                $tempResults[$key]['freeCompany'] = $this->freecompany($tempresult);
                            }
                            #PvP Team
                            if (!empty($tempresult['pvpid'])) {
                                $tempResults[$key]['pvp'] = [
                                    'id' => $tempresult['pvpid'],
                                    'name' => $tempresult['pvpname'],
                                ];
                                $tempResults[$key]['pvp']['crest'] = $this->crest($tempresult, 'pvpcrest');
                            }
                            #Bio
                            $tempresult['bio'] = trim($tempresult['bio']);
                            if ($tempresult['bio'] === '-') {
                                $tempresult['bio'] = '';
                            }
                            if (!empty($tempresult['bio'])) {
                                $tempResults[$key]['bio'] = $tempresult['bio'];
                            } else {
                                $tempResults[$key]['bio'] = '';
                            }
                            $tempResults[$key]['attributes'] = $this->attributes();
                            #Minions and mounts now show only icon on Lodestone, thus it's not really practically to grab them
                            #$tempResults[$key]['mounts'] = $this->collectibles('mounts');
                            #$tempResults[$key]['minions'] = $this->collectibles('minions');
                            $tempResults[$key]['gear'] = $this->items();
                        }
                        break;
                    case 'CharacterJobs':
                        $tempresult['id'] = $this->converters->classToJob($tempresult['name']);
                        $tempresult['expcur'] = preg_replace('/\D/', '', $tempresult['expcur'] ?? '');
                        $tempresult['expmax'] = preg_replace('/\D/', '', $tempresult['expmax'] ?? '');
                        $tempResults[$key] = $this->jobDetails($tempresult);
                        break;
                }
                
                #Unset stuff for cleaner look. Since it does not trigger warnings if variable is missing, no need to "switch" it
                unset($tempResults[$key]['crest1'], $tempResults[$key]['crest2'], $tempResults[$key]['crest3'], $tempResults[$key]['fccrestimg1'], $tempResults[$key]['fccrestimg2'], $tempResults[$key]['fccrestimg3'], $tempResults[$key]['gcname'], $tempResults[$key]['gcrank'], $tempResults[$key]['gcrankicon'], $tempResults[$key]['fcid'], $tempResults[$key]['fcname'], $tempResults[$key]['lsrankicon'], $tempResults[$key]['jobicon'], $tempResults[$key]['jobform'], $tempResults[$key]['estate_greeting'], $tempResults[$key]['estate_address'], $tempResults[$key]['estate_name'], $tempResults[$key]['cityicon'], $tempResults[$key]['guardianicon'], $tempResults[$key]['gcicon'], $tempResults[$key]['uppertitle'], $tempResults[$key]['undertitle'], $tempResults[$key]['pvpid'], $tempResults[$key]['pvpname'], $tempResults[$key]['pvpcrest1'], $tempResults[$key]['pvpcrest2'], $tempResults[$key]['pvpcrest3'], $tempResults[$key]['rank1'], $tempResults[$key]['rank2'], $tempResults[$key]['id'], $tempResults[$key]['column1'], $tempResults[$key]['column2'], $tempResults[$key]['column3'], $tempResults[$key]['star1'], $tempResults[$key]['star2'], $tempResults[$key]['star3'], $tempResults[$key]['extraicon']);
                
                #Adding to results
                $this->addToResults($resultkey, $resultsubkey, $tempResults[$key], (empty($tempresult['id']) ? null : $tempresult['id']));
            }
            
            #Worlds sort
            if ($this->type === 'worlds') {
                ksort($this->result[$resultkey]);
            }
        } catch (\Exception $e) {
            $this->errorRegister($e->getMessage(), 'parse', $started);
            return $this;
        }
        #Benchmarking
        if ($this->benchmark) {
            $finished = hrtime(true);
            $duration = $finished - $started;
            $this->benchUpdate($duration);
        }
        
        #Doing achievements details last to get proper order of timings for benchmarking
        if ($this->type === 'Achievements' && $this->typeSettings['details']) {
            foreach ($this->result[$resultkey][$this->typeSettings['id']][$resultsubkey] as $key => $ach) {
                $this->getCharacterAchievements($this->typeSettings['id'], $key, 1, false, true);
            }
        }
        if ($this->type === 'Achievements' && $this->typeSettings['allachievements']) {
            $this->typeSettings['allachievements'] = false;
            foreach (self::achKinds as $kindid) {
                $this->getCharacterAchievements($this->typeSettings['id'], false, (string)$kindid, false, $this->typeSettings['details'], $this->typeSettings['only_owned']);
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
            case 'searchPvPTeam':
            case 'searchLinkshell':
            case 'searchFreeCompany':
            case 'searchCharacter':
                if ($result !== 404) {
                    $this->result[$resultkey][$id] = $result;
                }
                break;
            case 'FreeCompany':
            case 'Character':
                $this->result[$resultkey][$this->typeSettings['id']] = $result;
                break;
            case 'CharacterJobs':
            case 'CharacterFriends':
            case 'CharacterFollowing':
            case 'FreeCompanyMembers':
            case 'LinkshellMembers':
                if ($result === 404) {
                    if (!isset($this->result[$resultkey]) || (!is_scalar($this->result[$resultkey][$this->typeSettings['id']]) && !is_array($this->result[$resultkey][$this->typeSettings['id']][$resultsubkey]))) {
                        $this->result[$resultkey][$this->typeSettings['id']][$resultsubkey] = $result;
                    }
                } else {
                    $this->result[$resultkey][$this->typeSettings['id']][$resultsubkey][$id] = $result;
                }
                break;
            case 'PvPTeamMembers':
                if ($result === 404) {
                    $this->result[$resultkey][$this->typeSettings['id']][$resultsubkey] = $result;
                } else {
                    $this->result[$resultkey][$this->typeSettings['id']][$resultsubkey][$id] = $result;
                }
                break;
            case 'Achievements':
                if ($result !== 404 && ($this->typeSettings['only_owned'] === false || ($this->typeSettings['only_owned'] === true && is_array($result) && $result['time'] !== NULL))) {
                    $this->result[$resultkey][$this->typeSettings['id']][$resultsubkey][$id] = $result;
                }
                break;
            case 'AchievementDetails':
                if ($result !== 404 || empty($this->result[$resultkey][$this->typeSettings['id']][$resultsubkey][$this->typeSettings['achievementId']])) {
                    $this->result[$resultkey][$this->typeSettings['id']][$resultsubkey][$this->typeSettings['achievementId']] = $result;
                }
                break;
            case 'Database':
                $this->result[$resultkey][$resultsubkey][$id] = $result;
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
                    $this->result[$resultkey][$result['dataCenter']] = [];
                    preg_match_all(Regex::WORLDS, $result['servers'], $servers, PREG_SET_ORDER);
                    if ($this->typeSettings['worldDetails']) {
                        foreach ($servers as $server) {
                            $this->result[$resultkey][$result['dataCenter']][$server['server']] = [
                                'Online' => $server['maintenance'] === '1',
                                'Partial maintenance' => $server['maintenance'] === '2',
                                'Full maintenance' => $server['maintenance'] === '3',
                                'Preferred' => in_array($server['population'], ['Preferred', '優遇', 'Désignés', 'Bevorzugt']),
                                'Congested' => in_array($server['population'], ['Congested', '混雑', 'Surpeuplés', 'Belastet']),
                                'New characters' => in_array($server['newchars'], ['Creation of New Characters Available', '新規キャラクター作成可', 'Création de personnage possible', 'Erstellung möglich']),
                            ];
                        }
                    } else {
                        #$this->result[$resultkey][$result['dataCenter']][$server['server']]] = $server['status'];
                        foreach ($servers as $server) {
                            $this->result[$resultkey][$result['dataCenter']][$server['server']] = $server['status'];
                        }
                    }
                    ksort($this->result[$resultkey][$result['dataCenter']]);
                }
                break;
            case 'feast':
                if ($result === 404) {
                    $this->result[$resultkey][$this->typeSettings['season']] = $result;
                } else {
                    $this->result[$resultkey][$this->typeSettings['season']][$id] = $result;
                }
                break;
            case 'frontline':
            case 'GrandCompanyRanking':
            case 'FreeCompanyRanking':
                if ($result === 404) {
                    $this->result[$resultkey][$resultsubkey][$this->typeSettings['week']] = $result;
                } else {
                    $this->result[$resultkey][$resultsubkey][$this->typeSettings['week']][$id] = $result;
                }
                break;
            case 'deepdungeon':
                if ($this->typeSettings['solo_party'] === 'solo') {
                    if ($result === 404) {
                        $this->result[$resultkey][$this->typeSettings['dungeon']][$this->typeSettings['solo_party']][$this->typeSettings['class']] = $result;
                    } else {
                        $this->result[$resultkey][$this->typeSettings['dungeon']][$this->typeSettings['solo_party']][$this->typeSettings['class']][$id] = $result;
                    }
                } elseif ($result === 404) {
                    $this->result[$resultkey][$this->typeSettings['dungeon']][$this->typeSettings['solo_party']] = $result;
                } else {
                    $this->result[$resultkey][$this->typeSettings['dungeon']][$this->typeSettings['solo_party']][$id] = $result;
                }
                break;
        }
    }
    
    /**
     * Function to check if we need to grab all pages and there are still pages left
     * @param string $resultkey
     *
     * @return bool
     */
    protected function allpagesproc(string $resultkey): bool
    {
        if ($this->allPages === true && in_array($this->type, [
                'searchCharacter',
                'CharacterFriends',
                'CharacterFollowing',
                'FreeCompanyMembers',
                'LinkshellMembers',
                'searchFreeCompany',
                'searchLinkshell',
                'searchPvPTeam',
                'topics',
                'notices',
                'maintenance',
                'updates',
                'status',
                'GrandCompanyRanking',
                'FreeCompanyRanking',
                'Database',
            ])) {
            switch ($this->type) {
                case 'CharacterFriends':
                case 'CharacterFollowing':
                case 'FreeCompanyMembers':
                case 'LinkshellMembers':
                    $current_page = $this->result[$resultkey][$this->typeSettings['id']]['pageCurrent'];
                    $total_page = $this->result[$resultkey][$this->typeSettings['id']]['pageTotal'];
                    break;
                case 'GrandCompanyRanking':
                case 'FreeCompanyRanking':
                    $current_page = $this->result[$resultkey][$this->typeSettings['week']]['pageCurrent'];
                    $total_page = $this->result[$resultkey][$this->typeSettings['week']]['pageTotal'];
                    break;
                case 'Database':
                    $current_page = $this->result[$resultkey][$this->typeSettings['type']]['pageCurrent'];
                    $total_page = $this->result[$resultkey][$this->typeSettings['type']]['pageTotal'];
                    break;
                default:
                    $current_page = $this->result[$resultkey]['pageCurrent'];
                    $total_page = $this->result[$resultkey]['pageTotal'];
                    break;
            }
            if ($current_page === $total_page) {
                return false;
            }
            $current_page++;
            ini_set('max_execution_time', '6000');
            $this->allPages = false;
            switch ($this->type) {
                case 'CharacterFriends':
                case 'CharacterFollowing':
                case 'FreeCompanyMembers':
                case 'LinkshellMembers':
                    $function_to_call = 'get'.$this->type;
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->$function_to_call($this->typeSettings['id'], $i);
                    }
                    break;
                case 'GrandCompanyRanking':
                case 'FreeCompanyRanking':
                    $function_to_call = 'get'.$this->type;
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->$function_to_call($this->typeSettings['week_month'], $this->typeSettings['week'], $this->typeSettings['worldname'], $this->typeSettings['gcid'], $i);
                    }
                    break;
                case 'searchCharacter':
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->searchCharacter($this->typeSettings['name'], $this->typeSettings['server'], $this->typeSettings['classjob'], $this->typeSettings['race_tribe'], $this->typeSettings['gcid'], $this->typeSettings['blog_lang'], $this->typeSettings['order'], $i);
                    }
                    break;
                case 'searchFreeCompany':
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->searchFreeCompany($this->typeSettings['name'], $this->typeSettings['server'], $this->typeSettings['character_count'], $this->typeSettings['activities'], $this->typeSettings['roles'], $this->typeSettings['activetime'], $this->typeSettings['join'], $this->typeSettings['house'], $this->typeSettings['gcid'], $this->typeSettings['order'], $i);
                    }
                    break;
                case 'searchLinkshell':
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->searchLinkshell($this->typeSettings['name'], $this->typeSettings['server'], $this->typeSettings['character_count'], $this->typeSettings['order'], $i);
                    }
                    break;
                case 'searchPvPTeam':
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->searchPvPTeam($this->typeSettings['name'], $this->typeSettings['server'], $this->typeSettings['order'], $i);
                    }
                    break;
                case 'Database':
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->searchDatabase($this->typeSettings['type'], $this->typeSettings['category'], $this->typeSettings['subcatecory'], $this->typeSettings['search'], $i);
                    }
                    break;
                case 'topics':
                case 'notices':
                case 'maintenance':
                case 'updates':
                case 'status':
                    $function_to_call = 'getLodestone'.ucfirst($this->type);
                    for ($i = $current_page; $i <= $total_page; $i++) {
                        $this->$function_to_call($i);
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
     * @return \Simbiat\LodestoneModules\Parsers|\Simbiat\Lodestone
     */
    protected function pages(array $pages, string $resultkey): self
    {
        foreach ($pages as $page => $data) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $pages[$page][$key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
                }
            }
        }
        switch ($this->type) {
            case 'CharacterFriends':
            case 'CharacterFollowing':
            case 'FreeCompanyMembers':
            case 'LinkshellMembers':
            case 'PvPTeamMembers':
                if (!empty($pages[0]['linkshellcommunityid'])) {
                    $this->result[$resultkey][$this->typeSettings['id']]['communityid'] = $pages[0]['linkshellcommunityid'];
                }
                if (!empty($pages[0]['pvpteamcommunityid'])) {
                    $this->result[$resultkey][$this->typeSettings['id']]['communityid'] = $pages[0]['pvpteamcommunityid'];
                }
                if (isset($pages[0]['pageCurrent']) && is_numeric($pages[0]['pageCurrent'])) {
                    $this->result[$resultkey][$this->typeSettings['id']]['pageCurrent'] = $pages[0]['pageCurrent'];
                } else {
                    $this->result[$resultkey][$this->typeSettings['id']]['pageCurrent'] = 1;
                }
                if (isset($pages[0]['pageTotal']) && is_numeric($pages[0]['pageTotal'])) {
                    $this->result[$resultkey][$this->typeSettings['id']]['pageTotal'] = $pages[0]['pageTotal'];
                } else {
                    $this->result[$resultkey][$this->typeSettings['id']]['pageTotal'] = $this->result[$resultkey][$this->typeSettings['id']]['pageCurrent'];
                }
                if (isset($pages[0]['total']) && is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey][$this->typeSettings['id']]['memberscount'] = $pages[0]['total'];
                }
                break;
            case 'GrandCompanyRanking':
            case 'FreeCompanyRanking':
                if (isset($pages[0]['pageCurrent']) && is_numeric($pages[0]['pageCurrent'])) {
                    $this->result[$resultkey][$this->typeSettings['week']]['pageCurrent'] = $pages[0]['pageCurrent'];
                } else {
                    $this->result[$resultkey][$this->typeSettings['week']]['pageCurrent'] = 1;
                }
                if (isset($pages[0]['pageTotal']) && is_numeric($pages[0]['pageTotal'])) {
                    $this->result[$resultkey][$this->typeSettings['week']]['pageTotal'] = $pages[0]['pageTotal'];
                } else {
                    $this->result[$resultkey][$this->typeSettings['week']]['pageTotal'] = $this->result[$resultkey][$this->typeSettings['week']]['pageCurrent'];
                }
                if (isset($pages[0]['total']) && is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey][$this->typeSettings['week']]['total'] = $pages[0]['total'];
                }
                break;
            case 'Database':
                if (isset($pages[0]['pageCurrent']) && is_numeric($pages[0]['pageCurrent'])) {
                    $this->result[$resultkey][$this->typeSettings['type']]['pageCurrent'] = $pages[0]['pageCurrent'];
                } else {
                    $this->result[$resultkey][$this->typeSettings['type']]['pageCurrent'] = 1;
                }
                if (isset($pages[0]['pageTotal']) && is_numeric($pages[0]['pageTotal'])) {
                    $this->result[$resultkey][$this->typeSettings['type']]['pageTotal'] = $pages[0]['pageTotal'];
                } else {
                    $this->result[$resultkey][$this->typeSettings['type']]['pageTotal'] = $this->result[$resultkey][$this->typeSettings['type']]['pageCurrent'];
                }
                if (isset($pages[0]['total']) && is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey][$this->typeSettings['type']]['total'] = $pages[0]['total'];
                }
                break;
            default:
                if (isset($pages[0]['pageCurrent']) && is_numeric($pages[0]['pageCurrent'])) {
                    $this->result[$resultkey]['pageCurrent'] = $pages[0]['pageCurrent'];
                } else {
                    $this->result[$resultkey]['pageCurrent'] = 1;
                }
                if (isset($pages[0]['pageTotal']) && is_numeric($pages[0]['pageTotal'])) {
                    $this->result[$resultkey]['pageTotal'] = $pages[0]['pageTotal'];
                } else {
                    $this->result[$resultkey]['pageTotal'] = $this->result[$resultkey]['pageCurrent'];
                }
                if (isset($pages[0]['total']) && is_numeric($pages[0]['total'])) {
                    $this->result[$resultkey]['total'] = $pages[0]['total'];
                }
                break;
        }
        #Linkshell members specific
        if (!empty($pages[0]['linkshellname'])) {
            $this->result[$resultkey][$this->typeSettings['id']]['name'] = trim($pages[0]['linkshellname']);
            if (!empty($pages[0]['linkshellserver'])) {
                if (preg_match('/[a-zA-Z0-9]{40}/mi', $this->typeSettings['id'])) {
                    $this->result[$resultkey][$this->typeSettings['id']]['dataCenter'] = $pages[0]['linkshellserver'];
                } else {
                    $this->result[$resultkey][$this->typeSettings['id']]['server'] = $pages[0]['linkshellserver'];
                }
            }
            if (!empty($pages[0]['linkshellformed'])) {
                $this->result[$resultkey][$this->typeSettings['id']]['formed'] = $pages[0]['linkshellformed'];
            }
            if (!empty($pages[0]['emptygroup'])) {
                $this->result[$resultkey][$this->typeSettings['id']]['pageCurrent'] = 0;
                $this->result[$resultkey][$this->typeSettings['id']]['pageTotal'] = 0;
            }
        }
        #PvpTeam members specific
        if (!empty($pages[0]['pvpname'])) {
            $this->result[$resultkey][$this->typeSettings['id']]['name'] = $pages[0]['pvpname'];
            if (!empty($pages[0]['server'])) {
                $this->result[$resultkey][$this->typeSettings['id']]['dataCenter'] = $pages[0]['server'];
            }
            if (!empty($pages[0]['formed'])) {
                $this->result[$resultkey][$this->typeSettings['id']]['formed'] = $pages[0]['formed'];
            }
            $this->result[$resultkey][$this->typeSettings['id']]['crest'] = $this->crest($pages[0], 'pvpcrest');
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
        $crest[] = str_replace(['40x40', '64x64'], '128x128', $tempresult[$keybase.'1']);
        if (!empty($tempresult[$keybase.'2'])) {
            $crest[] = str_replace(['40x40', '64x64'], '128x128', $tempresult[$keybase.'2']);
        }
        if (!empty($tempresult[$keybase.'3'])) {
            $crest[] = str_replace(['40x40', '64x64'], '128x128', $tempresult[$keybase.'3']);
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
        if (!empty($tempresult['gcrankicon'])) {
            $gc['icon'] = $tempresult['gcrankicon'];
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
            'id' => $tempresult['fcid'],
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
        $tempJobs = [];
        if (!$this->regexfail(preg_match_all(Regex::CHARACTER_JOBS, $this->html, $jobs, PREG_SET_ORDER), preg_last_error(), 'CHARACTER_JOBS')) {
            return [];
        }
        foreach ($jobs as $job) {
            $job['expcur'] = preg_replace('/\D/', '', $job['expcur']);
            $job['expmax'] = preg_replace('/\D/', '', $job['expmax']);
            $tempJobs[$this->converters->classToJob($job['name'])] = $this->jobDetails($job);
        }
        return $tempJobs;
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
            'level' => (is_numeric($job['level']) ? (int)$job['level'] : 0),
            'specialist' => !empty($job['specialist']),
            'expcur' => (is_numeric($job['expcur']) ? (int)$job['expcur'] : 0),
            'expmax' => (is_numeric($job['expmax']) ? (int)$job['expmax'] : 0),
            'icon' => $job['icon'],
        ];
    }
    
    /**
     * Process character attributes
     * @return array
     */
    protected function attributes(): array
    {
        $tempAttrs = [];
        if (!$this->regexfail(preg_match_all(Regex::CHARACTER_ATTRIBUTES, $this->html, $attributes, PREG_SET_ORDER), preg_last_error(), 'CHARACTER_ATTRIBUTES')) {
            return [];
        }
        foreach ($attributes as $attribute) {
            if (empty($attribute['name'])) {
                $tempAttrs[$attribute['name2']] = $attribute['value2'];
            } else {
                $tempAttrs[$attribute['name']] = $attribute['value'];
            }
        }
        return $tempAttrs;
    }
    
    /**
     * Process collectibles
     * @param string $type
     *
     * @return array
     */
    protected function collectibles(string $type): array
    {
        $colls = [];
        if ($type === 'mounts') {
            preg_match_all(Regex::CHARACTER_MOUNTS, $this->html, $results, PREG_SET_ORDER);
        } elseif ($type === 'minions') {
            preg_match_all(Regex::CHARACTER_MINIONS, $this->html, $results, PREG_SET_ORDER);
        }
        if (!empty($results[0][0])) {
            preg_match_all(Regex::COLLECTIBLE, $results[0][0], $results, PREG_SET_ORDER);
            foreach ($results as $result) {
                $colls[$result[1]] = $result[2];
            }
        }
        return $colls;
    }
    
    /**
     * Process items
     * @return array
     */
    protected function items(): array
    {
        if (!$this->regexfail(preg_match_all(Regex::CHARACTER_GEAR, $this->html, $tempresults, PREG_SET_ORDER), preg_last_error(), 'CHARACTER_GEAR')) {
            return [];
        }
        #Remove non-named groups
        foreach ($tempresults as $key => $tempresult) {
            foreach ($tempresult as $key2 => $details) {
                if (is_numeric($key2) || empty($details)) {
                    unset($tempresults[$key][(int)$key2]);
                }
            }
            $tempresults[$key]['armoireable'] = $this->converters->imageToBool($tempresult['armoireable']);
            $tempresults[$key]['hq'] = !empty($tempresult['hq']);
            $tempresults[$key]['unique'] = !empty($tempresult['unique']);
            #Requirements
            $tempresults[$key]['requirements'] = [
                'level' => $tempresult['level'],
                'classes' => (in_array($tempresult['classes'], ['Disciple of the Land', 'Disciple of the Hand', 'Disciple of Magic', 'Disciple of War', 'Disciples of War or Magic', 'All Classes', 'ギャザラー', 'Sammler', 'Récolteurs', 'Handwerker', 'Artisans', 'クラフター', 'Magier', 'Mages', 'ソーサラー', 'Krieger', 'Combattants', 'ファイター', 'Krieger, Magier', 'Combattants et mages', 'ファイター ソーサラー', 'Alle Klassen', 'Toutes les classes', '全クラス']) ? $tempresult['classes'] : explode(' ', $tempresult['classes'])),
            ];
            #Attributes
            for ($i = 1; $i <= 15; $i++) {
                if (!empty($tempresult['attrname'.$i])) {
                    $tempresults[$key]['attributes'][$tempresult['attrname'.$i]] = $tempresult['attrvalue'.$i];
                    unset($tempresults[$key]['attrname'.$i], $tempresults[$key]['attrvalue'.$i]);
                }
            }
            #Materia
            if (!empty($tempresult['materianame1']) || !empty($tempresult['materianame2']) || !empty($tempresult['materianame3']) || !empty($tempresult['materianame4']) || !empty($tempresult['materianame5'])) {
                $tempresults[$key]['materia'] = [];
                for ($i = 1; $i <= 5; $i++) {
                    if (!empty($tempresult['materianame'.$i])) {
                        $tempresults[$key]['materia'] = [
                            'name' => $tempresult['materianame'.$i],
                            'attribute' => $tempresult['materiaattr'.$i],
                            'bonus' => $tempresult['materiaval'.$i],
                        ];
                        unset($tempresults[$key]['materianame'.$i], $tempresults[$key]['materiaattr'.$i], $tempresults[$key]['materiaval'.$i]);
                    }
                }
            }
            #Crafting
            if (!empty($tempresult['repair'])) {
                $tempresults[$key]['crafting']['class'] = $tempresult['repair'];
                $tempresults[$key]['crafting']['materials'] = $tempresult['materials'];
                if (empty($tempresult['desynthesizable'])) {
                    $tempresults[$key]['crafting']['desynth'] = false;
                } else {
                    $tempresults[$key]['crafting']['desynth'] = $tempresult['desynthesizable'];
                }
                if (empty($tempresult['melding'])) {
                    $tempresults[$key]['crafting']['melding'] = false;
                } else {
                    $tempresults[$key]['crafting']['melding'] = $tempresult['melding'];
                    $tempresults[$key]['crafting']['advancedmelding'] = empty($tempresult['advancedmelding']);
                }
                $tempresults[$key]['crafting']['convertible'] = $this->converters->imageToBool($tempresult['convertible']);
            }
            #Trading
            if (empty($tempresult['price'])) {
                $tempresults[$key]['trading']['price'] = NULL;
            } else {
                $tempresults[$key]['trading']['price'] = $tempresult['price'];
            }
            $tempresults[$key]['trading']['sellable'] = empty($tempresult['unsellable']);
            $tempresults[$key]['trading']['marketable'] = empty($tempresult['marketprohibited']);
            $tempresults[$key]['trading']['tradeable'] = empty($tempresult['untradeable']);
            #Link to shop, if present
            if (empty($tempresult['shop'])) {
                $tempresults[$key]['trading']['shop'] = NULL;
            } else {
                $tempresults[$key]['trading']['shop'] = sprintf(Routes::LODESTONE_URL_BASE, $this->language).$tempresult['shop'];
            }
            #Customization
            $tempresults[$key]['customization'] = [
                'crestable' => $this->converters->imageToBool($tempresult['crestable']),
                'glamourable' => $this->converters->imageToBool($tempresult['glamourable']),
                'projectable' => $this->converters->imageToBool($tempresult['projectable']),
                'dyeable' => $this->converters->imageToBool($tempresult['dyeable']),
            ];
            #Glamour
            if (!empty($tempresult['glamourname'])) {
                $tempresults[$key]['customization']['glamour'] = [
                    'id' => $tempresult['glamourid'],
                    'name' => $tempresult['glamourname'],
                    'icon' => $tempresult['glamouricon'],
                ];
            }
            unset($tempresults[$key]['level'], $tempresults[$key]['classes'], $tempresults[$key]['price'], $tempresults[$key]['unsellable'], $tempresults[$key]['marketprohibited'], $tempresults[$key]['repair'], $tempresults[$key]['materials'], $tempresults[$key]['desynthesizable'], $tempresults[$key]['melding'], $tempresults[$key]['advancedmelding'], $tempresults[$key]['convertible'], $tempresults[$key]['glamourname'], $tempresults[$key]['glamourid'], $tempresults[$key]['glamouricon'], $tempresults[$key]['crestable'], $tempresults[$key]['glamourable'], $tempresults[$key]['projectable'], $tempresults[$key]['dyeable'], $tempresults[$key]['untradeable'], $tempresults[$key]['shop']);
        }
        return $tempresults;
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
        $this->lasterror = ['type' => $this->type, 'id' => ($this->typeSettings['id'] ?? NULL), 'error' => $errormessage, 'url' => $this->url];
        $this->errors[] = $this->lasterror;
        if ($this->benchmark) {
            if ($started === 0) {
                $duration = 0;
            } else {
                $finished = hrtime(true);
                $duration = $finished - $started;
            }
            if ($type === 'http') {
                $this->result['benchmark']['httptime'][] = date('H:i:s.'.sprintf('%06d', ($duration / 1000)), (int)($duration / 1000000000));
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
        $this->result['benchmark']['parsetime'][] = date('H:i:s.'.sprintf('%06d', ($duration / 1000)), (int)($duration / 1000000000));
        $this->result['benchmark']['memory'] = $this->converters->memory(memory_get_usage(true));
        $this->result['benchmark']['memorypeak'] = $this->converters->memory(memory_get_peak_usage(true));
    }
    
    /**
     * Function to reset last error (in case false positive)
     * @return void
     */
    protected function errorUnregister(): void
    {
        array_pop($this->errors);
        if (empty($this->errors)) {
            $this->lasterror = NULL;
        } else {
            $this->lasterror = end($this->errors);
        }
    }
}
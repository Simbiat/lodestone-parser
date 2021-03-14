<?php
declare(strict_types=1);
namespace Simbiat;

// use all the things
use LodestoneModules\{
    Routes, Regex, HttpRequest, Converters
};

/**
 * Provides quick functions to various parsing routes
 *
 * Class Api
 * @package Lodestone
 */
class Lodestone
{
    #Use trait
    use LodestoneModules\Parsers;
    
    const langallowed = ['na', 'jp', 'ja', 'eu', 'fr', 'de'];
    #List of achievements categories' ids excluding 1
    const achkinds = [2, 3, 4, 5, 6, 8, 11, 12, 13];
    
    protected string $useragent = '';
    protected string $language = 'na';
    protected bool $benchmark = false;
    protected string $url = '';
    protected string $type = '';
    protected array $typesettings = [];
    protected string $html = '';
    protected bool $allpages = false;
    protected ?object $converters = null;
    protected array $result = [];
    protected array $errors = [];
    protected ?array $lasterror = NULL;
    
    public function __construct()
    {
        $this->converters = new \Simbiat\LodestoneModules\Converters;
    }
    
    public function __destruct()
    {
        #Force close cURL handler
        curl_close(HttpRequest::$curlHandle);
    }
    
    #############
    #Accessor functions
    #############
    public function getResult(bool $close = true): array
    {
        #Close cURL handler
        if ($close) {
            curl_close(HttpRequest::$curlHandle);
        }
        return $this->result;
    }
    
    public function getErrors(bool $close = false): array
    {
        #Close cURL handler
        if ($close) {
            curl_close(HttpRequest::$curlHandle);
        }
        return $this->errors;
    }
    
    public function getLastError(bool $close = false): ?array
    {
        #Close cURL handler
        if ($close) {
            curl_close(HttpRequest::$curlHandle);
        }
        return $this->lasterror;
    }
    
    public function setResult(array $result): self
    {
        $this->result = $result;
        return $this;
    }
    
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }
    
    public function setLastError(?array $lasterror): self
    {
        $this->lasterror = $lasterror;
        return $this;
    }
    
    
    #############
    #Character functions
    #############
    public function getCharacter(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_URL, $id);
        $this->type = 'Character';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }
    
    public function getCharacterJobs(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_JOBS_URL, $id);
        $this->type = 'CharacterJobs';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }

    public function getCharacterFriends(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_FRIENDS_URL, $id, $page);
        $this->type = 'CharacterFriends';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }

    public function getCharacterFollowing(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_FOLLOWING_URL, $id, $page);
        $this->type = 'CharacterFollowing';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }

    public function getCharacterAchievements(string|int $id, int|bool $achievementId = false, string|int $kind = 1, bool $category = false, bool $details = false, bool $only_owned = false): self
    {
        if ($kind == 0) {
            $category = false;
            $kind = 1;
            $this->typesettings['allachievements'] = true;
        } else {
            $this->typesettings['allachievements'] = false;
        }
        if ($only_owned) {
            $this->typesettings['only_owned'] = true;
        } else {
            $this->typesettings['only_owned'] = false;
        }
        if ($achievementId !== false) {
            $this->type = 'AchievementDetails';
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_DET_URL, $id, $achievementId);
        } else {
            $this->type = 'Achievements';
            if ($category === false) {
                $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_URL, $id, $this->converters->getAchKindId(strval($kind)));
            } else {
                $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_CAT_URL, $id, $this->converters->getAchCatId(strval($kind)));
            }
        }
        $this->typesettings['id'] = $id;
        $this->typesettings['details'] = $details;
        $this->typesettings['achievementId'] = $achievementId;
        return $this->parse();
    }

    #############
    #Groups functions
    #############    
    public function getFreeCompany(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_URL, $id);
        $this->type = 'FreeCompany';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }

    public function getFreeCompanyMembers(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_MEMBERS_URL, $id, $page);
        $this->type = 'FreeCompanyMembers';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }

    public function getLinkshellMembers(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        if (preg_match('/[a-zA-Z0-9]{40}/mi', $id)) {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CROSSWORLD_LINKSHELL_MEMBERS_URL, $id, $page);
        } else {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_LINKSHELL_MEMBERS_URL, $id, $page);
        }
        $this->type = 'LinkshellMembers';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }

    public function getPvPTeam(string $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_PVPTEAM_MEMBERS_URL, $id);
        $this->type = 'PvPTeamMembers';
        $this->typesettings['id'] = $id;
        return $this->parse();
    }
    
    #############
    #Search functions
    #############
    public function searchDatabase(string $type, int $category = 0, int $subcatecory = 0, string $search = '', int $page = 1): self
    {
        #Ensure we have lowercase for consistency
        $type = strtolower($type);
        if (!in_array($type, ['item', 'duty', 'quest', 'recipe', 'gathering', 'achievement', 'shop', 'text_command'])) {
            throw new \UnexpectedValueException('Unsupported type of database \''.$type.'\' element was requested');
        }
        $page = $this->pageCheck($page);
        $query = $this->queryBuilder([
            'db_search_category' => $type,
            'category2' => $category,
            #Duty has been updated at some point and category3 was replaced with ex_version
            ($type === 'duty' ? 'ex_version' : 'category3') => $subcatecory,
            'q' => str_ireplace(' ', '+', $search),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_DATABASE_URL, $type, $query);
        $this->type = 'Database';
        $this->typesettings['type'] = $type;
        $this->typesettings['category'] = $category;
        $this->typesettings['subcatecory'] = $subcatecory;
        $this->typesettings['search'] = $search;
        return $this->parse();
    }
    
    public function searchCharacter(string $name = '', string $server = '', string $classjob = '', string $race_tribe = '', $gcid = '', $blog_lang = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        if (is_array($gcid)) {
            foreach ($gcid as $key=>$item) {
                $gcid[$key] = $this->converters->getSearchGCId($item);
            }
        } elseif (is_string($gcid)) {
            $gcid = $this->converters->getSearchGCId($gcid);
        } else {
            $gcid = '';
        }
        if (is_array($blog_lang)) {
            foreach ($blog_lang as $key=>$item) {
                $blog_lang[$key] = $this->converters->languageConvert($item);
            }
        } elseif (is_string($gcid)) {
            $blog_lang = $this->converters->languageConvert($blog_lang);
        } else {
            $blog_lang = '';
        }
        $query = str_replace(['&blog_lang=&', '&gcid=&'], '&', $this->queryBuilder([
            'q' => str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'classjob' => $this->converters->getSearchClassId($classjob),
            'race_tribe' => $this->converters->getSearchClanId($race_tribe),
            'gcid' => (is_array($gcid) ? implode('&gcid=', $gcid) : $gcid),
            'blog_lang' => (is_array($blog_lang) ? implode('&blog_lang=', $blog_lang) : $blog_lang),
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]));
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_SEARCH_URL, $query);
        $this->type = 'searchCharacter';
        $this->typesettings['name'] = $name;
        $this->typesettings['server'] = $server;
        $this->typesettings['classjob'] = $classjob;
        $this->typesettings['race_tribe'] = $race_tribe;
        $this->typesettings['gcid'] = $gcid;
        $this->typesettings['blog_lang'] = $blog_lang;
        $this->typesettings['order'] = $order;
        return $this->parse();
    }

    public function searchFreeCompany(string $name = '', string $server = '', int $character_count = 0, $activities = '', $roles = '', string $activetime = '', string $join = '', string $house = '', $gcid = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        if (is_array($gcid)) {
            foreach ($gcid as $key=>$item) {
                $gcid[$key] = $this->converters->getSearchGCId($item);
            }
        } elseif (is_string($gcid)) {
            $gcid = $this->converters->getSearchGCId($gcid);
        } else {
            $gcid = '';
        }
        if (is_array($activities)) {
            foreach ($activities as $key=>$item) {
                $activities[$key] = $this->converters->getSearchActivitiesId($item);
            }
        } elseif (is_string($gcid)) {
            $activities = $this->converters->getSearchActivitiesId($activities);
        } else {
            $activities = '';
        }
        if (is_array($roles)) {
            foreach ($roles as $key=>$item) {
                $roles[$key] = $this->converters->getSearchRolesId($item);
            }
        } elseif (is_string($gcid)) {
            $roles = $this->converters->getSearchRolesId($roles);
        } else {
            $roles = '';
        }
        $query = str_replace(['&activities=&', '&roles=&', '&gcid=&'], '&', $this->queryBuilder([
            'q' => str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'character_count' => $this->converters->membersCount($character_count),
            'activities' => (is_array($activities) ? implode('&activities=', $activities) : $activities),
            'roles' => (is_array($roles) ? implode('&roles=', $roles) : $roles),
            'activetime' => $this->converters->getSearchActiveTimeId($activetime),
            'join' => $this->converters->getSearchJoinId($join),
            'house' => $this->converters->getSearchHouseId($house),
            'gcid' => (is_array($gcid) ? implode('&gcid=', $gcid) : $gcid),
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]));
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_SEARCH_URL, $query);
        $this->type = 'searchFreeCompany';
        $this->typesettings['name'] = $name;
        $this->typesettings['server'] = $server;
        $this->typesettings['character_count'] = $character_count;
        $this->typesettings['activities'] = $activities;
        $this->typesettings['roles'] = $roles;
        $this->typesettings['activetime'] = $activetime;
        $this->typesettings['join'] = $join;
        $this->typesettings['house'] = $house;
        $this->typesettings['gcid'] = $gcid;
        $this->typesettings['order'] = $order;
        return $this->parse();
    }

    public function searchLinkshell(string $name = '', string $server = '', int $character_count = 0, string $order = '', int $page = 1, bool $crossworld = false): self
    {
        $page = $this->pageCheck($page);
        $query = $this->queryBuilder([
            'q' => str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'character_count' => $this->converters->membersCount($character_count),
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]);
        if ($crossworld) {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CROSSWORLD_LINKSHELL_SEARCH_URL, $query);
        } else {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_LINKSHELL_SEARCH_URL, $query);
        }
        $this->type = 'searchLinkshell';
        $this->typesettings['name'] = $name;
        $this->typesettings['server'] = $server;
        $this->typesettings['character_count'] = $character_count;
        $this->typesettings['order'] = $order;
        return $this->parse();
    }
    
    public function searchPvPTeam(string $name = '', string $server = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $query = $this->queryBuilder([
            'q' => str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_PVPTEAM_SEARCH_URL, $query);
        $this->type = 'searchPvPTeam';
        $this->typesettings['name'] = $name;
        $this->typesettings['server'] = $server;
        $this->typesettings['order'] = $order;
        return $this->parse();
    }
    
    #############
    #Rankings functions
    #############
    public function getFeast(int $season = 1, string $dcgroup = '', string $rank_type = 'all'): self
    {
        if ($season <= 0) {
            $season = 1;
        }
        $query = $this->queryBuilder([
            'dcgroup' => $dcgroup,
            'rank_type' => $this->converters->getFeastRankId($rank_type),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FEAST, strval($season), $query);
        $this->type = 'feast';
        $this->typesettings['season'] = $season;
        return $this->parse();
    }
    
    public function getDeepDungeon(int $id = 1, string $dcgroup = '', string $solo_party = 'party', string $subtype = 'PLD'): self
    {
        if ($id == 1) {
            $id = '';
        }
        if ($subtype) {
            $solo_party = 'solo';
        }
        if (!in_array($solo_party, ['party', 'solo'])) {
            $solo_party = 'party';
        }
        $query = $this->queryBuilder([
            'dcgroup' => $dcgroup,
            'solo_party' => $solo_party,
            'subtype' => $this->converters->getDeepDungeonClassId($subtype),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_DEEP_DUNGEON, strval($id), $query);
        if (empty($id)) {
            $id = 1;
        }
        if (empty($subtype)) {
            $subtype = $this->converters->getDeepDungeonClassId('PLD');
        }
        $this->type = 'deepdungeon';
        $this->typesettings['dungeon'] = $id;
        $this->typesettings['solo_party'] = $solo_party;
        $this->typesettings['class'] = $subtype;
        return $this->parse();
    }
    
    public function getFrontline(string $week_month = 'weekly', int $week = 0, string $dcgroup = '', string $worldname = '', int $pvp_rank = 0, int $match = 0, string $gcid = '', string $sort = 'win'): self
    {
        if (!in_array($week_month, ['weekly','monthly'])) {
            $week_month = 'weekly';
        }
        if (!in_array($sort, ['win', 'rate', 'match'])) {
            $sort = 'win';
        }
        if ($week_month == 'weekly') {
            if (!preg_match('/^[0-9]{4}(0[1-9]|[1-4][0-9]|5[0-3])$/', strval($week))) {
                $week = 0;
            }
        } else {
            if (!preg_match('/^[0-9]{4}(0[1-9]|1[0-2])$/', strval($week))) {
                $week = 0;
            }
        }
        $query = $this->queryBuilder([
            'filter' => 1,
            'sort' => $sort,
            'dcgroup' => $dcgroup,
            'worldname' => $worldname,
            'pvp_rank' => $this->converters->pvpRank($pvp_rank),
            'match' => $this->converters->matchesCount($match),
            'gcid' => $this->converters->getSearchGCId($gcid),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FRONTLINE, $week_month, $week, $query);
        $this->type = 'frontline';
        $this->typesettings['week'] = $week;
        $this->typesettings['week_month'] = $week_month;
        return $this->parse();
    }
    
    public function getGrandCompanyRanking(string $week_month = 'weekly', int $week = 0, string $worldname = '', string $gcid = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        if (!in_array($week_month, ['weekly','monthly'])) {
            $week_month = 'weekly';
        }
        if ($week_month == 'weekly') {
            if (!preg_match('/^[0-9]{4}(0[1-9]|[1-4][0-9]|5[0-3])$/', strval($week))) {
                $week = 0;
            }
        } else {
            if (!preg_match('/^[0-9]{4}(0[1-9]|1[0-2])$/', strval($week))) {
                $week = 0;
            }
        }
        $query = $this->queryBuilder([
            'filter' => 1,
            'worldname' => $worldname,
            'gcid' => $this->converters->getSearchGCId($gcid),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_GCRANKING, $week_month, $week, $query);
        $this->type = 'GrandCompanyRanking';
        $this->typesettings['week'] = $week;
        $this->typesettings['week_month'] = $week_month;
        $this->typesettings['worldname'] = $worldname;
        $this->typesettings['gcid'] = $gcid;
        return $this->parse();
    }
    
    public function getFreeCompanyRanking(string $week_month = 'weekly', int $week = 0, string $worldname = '', string $gcid = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        if (!in_array($week_month, ['weekly','monthly'])) {
            $week_month = 'weekly';
        }
        if ($week_month == 'weekly') {
            if (!preg_match('/^[0-9]{4}(0[1-9]|[1-4][0-9]|5[0-3])$/', strval($week))) {
                $week = 0;
            }
        } else {
            if (!preg_match('/^[0-9]{4}(0[1-9]|1[0-2])$/', strval($week))) {
                $week = 0;
            }
        }
        $query = $this->queryBuilder([
            'filter' => 1,
            'worldname' => $worldname,
            'gcid' => $this->converters->getSearchGCId($gcid),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FCRANKING, $week_month, $week, $query);
        $this->type = 'FreeCompanyRanking';
        $this->typesettings['week'] = $week;
        $this->typesettings['week_month'] = $week_month;
        $this->typesettings['worldname'] = $worldname;
        $this->typesettings['gcid'] = $gcid;
        return $this->parse();
    }
    
    #############
    #Special pages functions
    #############
    public function getLodestoneBanners(): self
    {
        $this->url = sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_BANNERS;
        $this->type = 'banners';
        return $this->parse();
    }

    public function getLodestoneNews(): self
    {
        $this->url = sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_NEWS;
        $this->type = 'news';
        return $this->parse();
    }

    public function getLodestoneTopics(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_TOPICS, $page);
        $this->type = 'topics';
        return $this->parse();
    }

    public function getLodestoneNotices(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_NOTICES, $page);
        $this->type = 'notices';
        return $this->parse();
    }

    public function getLodestoneMaintenance(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_MAINTENANCE, $page);
        $this->type = 'maintenance';
        return $this->parse();
    }

    public function getLodestoneUpdates(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_UPDATES, $page);
        $this->type = 'updates';
        return $this->parse();
    }

    public function getLodestoneStatus(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_STATUS, $page);
        $this->type = 'status';
        return $this->parse();
    }

    public function getWorldStatus(bool $worlddetails = false): self
    {
        $this->url = sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_WORLD_STATUS;
        $this->type = 'worlds';
        $this->typesettings['worlddetails'] = $worlddetails;
        return $this->parse();
    }
    
    #############
    #Logic to accumulate filters and add them as parameters to URL
    #############
    protected function queryBuilder(array $params): string
    {
        $query = [];
        foreach($params as $param => $value) {
            if (empty($value) && $value !== '0') {
                continue;
            }
            if ($param == 'q' || !empty($value) || $value === '0') {
                $query[] = $param .'='. $value;
            }
        }
        return '?'. implode('&', $query);
    }
    
    protected function pageCheck(int $page): int
    {
        if ($page === 0) {
            $page = 1;
            $this->allpages = true;
        }
        return $page;
    }
    
    #############
    #Settings functions
    #############
    public function setUseragent(string $useragent = ''): self
    {
        $this->useragent = $useragent;
        return $this;
    }
    
    public function setLanguage(string $language = ''): self
    {
        if (!in_array($language, self::langallowed)) {
            $language = 'na';
        }
        if (in_array($language, ['jp', 'ja'])) {$language = 'jp';}
        $this->language = $language;
        return $this;
    }
    
    public function setBenchmark($bench = false): self
    {
        $this->benchmark = (bool)$bench;
        return $this;
    }
}
?>

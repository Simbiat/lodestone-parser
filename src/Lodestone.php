<?php
declare(strict_types=1);
namespace Simbiat;

// use all the things
use Simbiat\LodestoneModules\{Converters, Routes, HttpRequest};

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

    const langAllowed = ['na', 'jp', 'ja', 'eu', 'fr', 'de'];
    #List of achievements categories' ids excluding 1
    const achKinds = [2, 3, 4, 5, 6, 8, 11, 12, 13];

    protected string $useragent = '';
    protected string $language = 'na';
    protected bool $benchmark = false;
    protected string $url = '';
    protected string $type = '';
    protected array $typeSettings = [];
    protected string $html = '';
    protected bool $allPages = false;
    protected ?object $converters = null;
    protected array $result = [];
    protected array $errors = [];
    protected ?array $lasterror = NULL;

    public function __construct()
    {
        $this->converters = new Converters;
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
        $this->typeSettings['id'] = $id;
        return $this->parse();
    }

    public function getCharacterJobs(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_JOBS_URL, $id);
        $this->type = 'CharacterJobs';
        $this->typeSettings['id'] = $id;
        return $this->parse();
    }

    public function getCharacterFriends(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_FRIENDS_URL, $id, $page);
        $this->type = 'CharacterFriends';
        $this->typeSettings['id'] = $id;
        return $this->parse();
    }

    public function getCharacterFollowing(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_FOLLOWING_URL, $id, $page);
        $this->type = 'CharacterFollowing';
        $this->typeSettings['id'] = $id;
        return $this->parse();
    }

    public function getCharacterAchievements(string|int $id, int|bool $achievementId = false, string|int $kind = 1, bool $category = false, bool $details = false, bool $only_owned = false): self
    {
        if ($kind == 0) {
            $category = false;
            $kind = 1;
            $this->typeSettings['allachievements'] = true;
        } else {
            $this->typeSettings['allachievements'] = false;
        }
        if ($only_owned) {
            $this->typeSettings['only_owned'] = true;
        } else {
            $this->typeSettings['only_owned'] = false;
        }
        if ($achievementId !== false) {
            $this->type = 'AchievementDetails';
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_DET_URL, $id, $achievementId);
        } else {
            $this->type = 'Achievements';
            if ($category === false) {
                $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_URL, $id, strval($kind));
            } else {
                $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_CAT_URL, $id, strval($kind));
            }
        }
        $this->typeSettings['id'] = $id;
        $this->typeSettings['details'] = $details;
        $this->typeSettings['achievementId'] = $achievementId;
        return $this->parse();
    }

    #############
    #Groups functions
    #############
    public function getFreeCompany(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_URL, $id);
        $this->type = 'FreeCompany';
        $this->typeSettings['id'] = $id;
        return $this->parse();
    }

    public function getFreeCompanyMembers(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_MEMBERS_URL, $id, $page);
        $this->type = 'FreeCompanyMembers';
        $this->typeSettings['id'] = $id;
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
        $this->typeSettings['id'] = $id;
        return $this->parse();
    }

    public function getPvPTeam(string $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_PVPTEAM_MEMBERS_URL, $id);
        $this->type = 'PvPTeamMembers';
        $this->typeSettings['id'] = $id;
        return $this->parse();
    }

    #############
    #Search functions
    #############
    public function searchDatabase(string $type, int $category = 0, int $subCategory = 0, string $search = '', int $page = 1): self
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
            ($type === 'duty' ? 'ex_version' : 'category3') => $subCategory,
            'q' => str_ireplace(' ', '+', $search),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_DATABASE_URL, $type, $query);
        $this->type = 'Database';
        $this->typeSettings['type'] = $type;
        $this->typeSettings['category'] = $category;
        $this->typeSettings['subCategory'] = $subCategory;
        $this->typeSettings['search'] = $search;
        return $this->parse();
    }

    public function searchCharacter(string $name = '', string $server = '', string $classJob = '', string $race_tribe = '', array|string|int $gcId = '', $blog_lang = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $gcId = $this->gcIdCheck($gcId);
        if (is_array($blog_lang)) {
            foreach ($blog_lang as $key=>$item) {
                $blog_lang[$key] = $this->converters->languageConvert($item);
            }
        } elseif (is_string($gcId)) {
            $blog_lang = $this->converters->languageConvert($blog_lang);
        } else {
            $blog_lang = '';
        }
        $query = str_replace(['&blog_lang=&', '&gcId=&'], '&', $this->queryBuilder([
            'q' => str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'classJob' => $this->converters->getSearchClassId($classJob),
            'race_tribe' => $this->converters->getSearchClanId($race_tribe),
            'gcId' => (is_array($gcId) ? implode('&gcId=', $gcId) : $gcId),
            'blog_lang' => (is_array($blog_lang) ? implode('&blog_lang=', $blog_lang) : $blog_lang),
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]));
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_SEARCH_URL, $query);
        $this->type = 'searchCharacter';
        $this->typeSettings['name'] = $name;
        $this->typeSettings['server'] = $server;
        $this->typeSettings['classJob'] = $classJob;
        $this->typeSettings['race_tribe'] = $race_tribe;
        $this->typeSettings['gcId'] = $gcId;
        $this->typeSettings['blog_lang'] = $blog_lang;
        $this->typeSettings['order'] = $order;
        return $this->parse();
    }

    public function searchFreeCompany(string $name = '', string $server = '', int $character_count = 0, $activities = '', $roles = '', string $activeTime = '', string $join = '', string $house = '', array|string|int $gcId = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $gcId = $this->gcIdCheck($gcId);
        if (is_array($activities)) {
            foreach ($activities as $key=>$item) {
                $activities[$key] = $this->converters->getSearchActivitiesId($item);
            }
        } elseif (is_string($gcId)) {
            $activities = $this->converters->getSearchActivitiesId($activities);
        } else {
            $activities = '';
        }
        if (is_array($roles)) {
            foreach ($roles as $key=>$item) {
                $roles[$key] = $this->converters->getSearchRolesId($item);
            }
        } elseif (is_string($gcId)) {
            $roles = $this->converters->getSearchRolesId($roles);
        } else {
            $roles = '';
        }
        $query = str_replace(['&activities=&', '&roles=&', '&gcId=&'], '&', $this->queryBuilder([
            'q' => str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'character_count' => $this->converters->membersCount($character_count),
            'activities' => (is_array($activities) ? implode('&activities=', $activities) : $activities),
            'roles' => (is_array($roles) ? implode('&roles=', $roles) : $roles),
            'activeTime' => $this->converters->getSearchActiveTimeId($activeTime),
            'join' => $this->converters->getSearchJoinId($join),
            'house' => $this->converters->getSearchHouseId($house),
            'gcId' => (is_array($gcId) ? implode('&gcId=', $gcId) : $gcId),
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]));
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_SEARCH_URL, $query);
        $this->type = 'searchFreeCompany';
        $this->typeSettings['name'] = $name;
        $this->typeSettings['server'] = $server;
        $this->typeSettings['character_count'] = $character_count;
        $this->typeSettings['activities'] = $activities;
        $this->typeSettings['roles'] = $roles;
        $this->typeSettings['activeTime'] = $activeTime;
        $this->typeSettings['join'] = $join;
        $this->typeSettings['house'] = $house;
        $this->typeSettings['gcId'] = $gcId;
        $this->typeSettings['order'] = $order;
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
        $this->typeSettings['name'] = $name;
        $this->typeSettings['server'] = $server;
        $this->typeSettings['character_count'] = $character_count;
        $this->typeSettings['order'] = $order;
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
        $this->typeSettings['name'] = $name;
        $this->typeSettings['server'] = $server;
        $this->typeSettings['order'] = $order;
        return $this->parse();
    }

    #############
    #Rankings functions
    #############
    public function getFeast(int $season = 1, string $dcGroup = '', string $rank_type = 'all'): self
    {
        if ($season <= 0) {
            $season = 1;
        }
        $query = $this->queryBuilder([
            'dcGroup' => $dcGroup,
            'rank_type' => $this->converters->getFeastRankId($rank_type),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FEAST, strval($season), $query);
        $this->type = 'feast';
        $this->typeSettings['season'] = $season;
        return $this->parse();
    }

    public function getDeepDungeon(int $id = 1, string $dcGroup = '', string $solo_party = 'party', string $subtype = 'PLD'): self
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
            'dcGroup' => $dcGroup,
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
        $this->typeSettings['dungeon'] = $id;
        $this->typeSettings['solo_party'] = $solo_party;
        $this->typeSettings['class'] = $subtype;
        return $this->parse();
    }

    public function getFrontline(string $week_month = 'weekly', int $week = 0, string $dcGroup = '', string $worldname = '', int $pvp_rank = 0, int $match = 0, string $gcId = '', string $sort = 'win'): self
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
            'dcGroup' => $dcGroup,
            'worldname' => $worldname,
            'pvp_rank' => $this->converters->pvpRank($pvp_rank),
            'match' => $this->converters->matchesCount($match),
            'gcId' => $this->converters->getSearchGCId($gcId),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FRONTLINE, $week_month, $week, $query);
        $this->type = 'frontline';
        $this->typeSettings['week'] = $week;
        $this->typeSettings['week_month'] = $week_month;
        return $this->parse();
    }

    public function getGrandCompanyRanking(string $week_month = 'weekly', int $week = 0, string $worldname = '', string $gcId = '', int $page = 1): self
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
            'gcId' => $this->converters->getSearchGCId($gcId),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_GCRANKING, $week_month, $week, $query);
        $this->type = 'GrandCompanyRanking';
        $this->typeSettings['week'] = $week;
        $this->typeSettings['week_month'] = $week_month;
        $this->typeSettings['worldname'] = $worldname;
        $this->typeSettings['gcId'] = $gcId;
        return $this->parse();
    }

    public function getFreeCompanyRanking(string $week_month = 'weekly', int $week = 0, string $worldname = '', string $gcId = '', int $page = 1): self
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
            'gcId' => $this->converters->getSearchGCId($gcId),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FCRANKING, $week_month, $week, $query);
        $this->type = 'FreeCompanyRanking';
        $this->typeSettings['week'] = $week;
        $this->typeSettings['week_month'] = $week_month;
        $this->typeSettings['worldname'] = $worldname;
        $this->typeSettings['gcId'] = $gcId;
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

    public function getWorldStatus(bool $worldDetails = false): self
    {
        $this->url = sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_WORLD_STATUS;
        $this->type = 'worlds';
        $this->typeSettings['worldDetails'] = $worldDetails;
        return $this->parse();
    }

    #############
    #Logic to accumulate filters and add them as parameters to URL
    #############
    private function queryBuilder(array $params): string
    {
        $query = [];
        foreach($params as $param => $value) {
            if (empty($value) && $value !== '0') {
                continue;
            }
            if ($param == 'q') {
                $query[] = $param .'='. $value;
            }
        }
        return '?'. implode('&', $query);
    }

    private function pageCheck(int $page): int
    {
        if ($page === 0) {
            $page = 1;
            $this->allPages = true;
        }
        return $page;
    }

    private function gcIdCheck(array|string|int $gcId): string
    {
        if (is_array($gcId)) {
            foreach ($gcId as $key=> $item) {
                $gcId[$key] = $this->converters->getSearchGCId($item);
            }
        } elseif (is_string($gcId)) {
            $gcId = $this->converters->getSearchGCId($gcId);
        } else {
            $gcId = '';
        }
        return $gcId;
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
        if (!in_array($language, self::langAllowed)) {
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

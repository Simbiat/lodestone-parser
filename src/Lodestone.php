<?php
declare(strict_types = 1);

namespace Simbiat\FFXIV;

// use all the things
use Simbiat\FFXIV\LodestoneModules\{Converters, Routes, HttpRequest};
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;

use function is_array, is_string, in_array, sprintf;

/**
 * Provides quick functions to various parsing routes
 */
class Lodestone
{
    #Use trait
    use LodestoneModules\Parsers;
    
    public const array LANGUAGES_ALLOWED = ['na', 'jp', 'ja', 'eu', 'fr', 'de'];
    
    protected string $user_agent = '';
    protected string $language = 'na';
    protected bool $benchmark = false;
    protected string $url = '';
    protected string $type = '';
    protected array $type_settings = [];
    protected string $html = '';
    private bool $all_pages = false;
    protected ?object $converters = null;
    protected array $result = [];
    protected array $errors = [];
    protected ?array $last_error = NULL;
    
    #[Pure] public function __construct()
    {
        $this->converters = new Converters();
    }
    
    /**
     * Class destructor
     */
    public function __destruct()
    {
        #Force close cURL handler
        if (!empty(HttpRequest::$curl_handle)) {
            \curl_close(HttpRequest::$curl_handle);
        }
    }
    
    #############
    #Accessor functions
    #############
    /**
     * Get results of accumulated from other function
     * @param bool $close Whether to close the cURL after getting results
     *
     * @return array
     */
    public function getResult(bool $close = true): array
    {
        #Close cURL handler
        if ($close) {
            \curl_close(HttpRequest::$curl_handle);
        }
        return $this->result;
    }
    
    /**
     * Get list of all errors
     * @param bool $close Whether to close the cURL after getting errors
     *
     * @return array
     */
    public function getErrors(bool $close = false): array
    {
        #Close cURL handler
        if ($close) {
            \curl_close(HttpRequest::$curl_handle);
        }
        return $this->errors;
    }
    
    /**
     * Get last error
     * @param bool $close Whether to close the cURL after getting errors
     *
     * @return array|null
     */
    public function getLastError(bool $close = false): ?array
    {
        #Close cURL handler
        if ($close) {
            \curl_close(HttpRequest::$curl_handle);
        }
        return $this->last_error;
    }
    
    /**
     * Reset results
     * @return $this
     */
    public function resetResult(): self
    {
        $this->result = [];
        return $this;
    }
    
    /**
     * Reset errors
     * @return $this
     */
    public function resetErrors(): self
    {
        $this->errors = [];
        return $this;
    }
    
    #############
    #Character functions
    #############
    /**
     * Get data for a character based on ID
     * @param string|int $id Character ID
     *
     * @return self
     */
    public function getCharacter(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_URL, $id);
        $this->type = 'character';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    /**
     * Get jobs for a character based on ID
     * @param string|int $id Character ID
     *
     * @return self
     */
    public function getCharacterJobs(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_JOBS_URL, $id);
        $this->type = 'character_jobs';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    /**
     * Get friends of a character based on ID
     * @param string|int $id   Character ID
     * @param int        $page Page number to read
     *
     * @return self
     */
    public function getCharacterFriends(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_FRIENDS_URL, $id, $page);
        $this->type = 'character_friends';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    /**
     * Get characters followed by a character based on ID
     * @param string|int $id   Character ID
     * @param int        $page Page number to read
     *
     * @return self
     */
    public function getCharacterFollowing(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_FOLLOWING_URL, $id, $page);
        $this->type = 'character_following';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    /**
     * Get character achievements based on ID
     * @param string|int $id             Character ID.
     * @param int|bool   $achievement_id Optional achievement ID. Use if you need specific achievement data.
     * @param string|int $kind           Get only specific category of achievements. If `$category` is `true`, will work as subcategory.
     * @param bool       $category       Switch to turn `$kind` into subcategory.
     * @param bool       $details        Switch to grab details for all achievements in category. Be careful, since this will increase runtimes proportionally to amount of achievements.
     * @param bool       $only_owned     Whether to get only achieved achievements.
     *
     * @return self
     */
    public function getCharacterAchievements(string|int $id, int|bool $achievement_id = false, string|int $kind = 1, bool $category = false, bool $details = false, bool $only_owned = false): self
    {
        if ((int)$kind < 1) {
            $category = false;
            $kind = 1;
            $this->type_settings['allachievements'] = true;
        } else {
            $this->type_settings['allachievements'] = false;
        }
        if ($only_owned) {
            $this->type_settings['only_owned'] = true;
        } else {
            $this->type_settings['only_owned'] = false;
        }
        if ($achievement_id !== false) {
            $this->type = 'achievement_details';
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_DET_URL, $id, $achievement_id);
        } else {
            $this->type = 'achievements';
            if ($category) {
                $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_CAT_URL, $id, (string)$kind);
            } else {
                $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_URL, $id, (string)$kind);
            }
        }
        $this->type_settings['id'] = $id;
        $this->type_settings['details'] = $details;
        $this->type_settings['achievement_id'] = $achievement_id;
        return $this->parse();
    }
    
    /**
     * Get achievement details from Lodestone Database page
     * @param string $db_id
     *
     * @return self
     */
    public function getAchievementFromDB(string $db_id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_ACHIEVEMENTS_DB_URL, $db_id);
        $this->type = 'achievement_from_db';
        $this->type_settings['type'] = 'achievement';
        $this->type_settings['id'] = $db_id;
        return $this->parse();
    }
    
    #############
    #Groups functions
    #############
    /**
     * Get free company data based on ID
     * @param string|int $id Free company ID
     *
     * @return self
     */
    public function getFreeCompany(string|int $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_URL, $id);
        $this->type = 'free_company';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    /**
     * Get free company members based on ID
     * @param string|int $id   Free company ID
     * @param int        $page Page number to read
     *
     * @return self
     */
    public function getFreeCompanyMembers(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_MEMBERS_URL, $id, $page);
        $this->type = 'free_company_members';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    /**
     * Get linkshell members based on ID
     * @param string|int $id   Linkshell ID
     * @param int        $page Page number to read
     *
     * @return self
     */
    public function getLinkshellMembers(string|int $id, int $page = 1): self
    {
        $page = $this->pageCheck($page);
        if (\preg_match('/[a-zA-Z0-9]{40}/mi', $id)) {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CROSSWORLD_LINKSHELL_MEMBERS_URL, $id, $page);
        } else {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_LINKSHELL_MEMBERS_URL, $id, $page);
        }
        $this->type = 'linkshell_members';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    /**
     * Get PvP team data based on ID
     * @param string $id PvP team ID
     *
     * @return self
     */
    public function getPvPTeam(string $id): self
    {
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_PVPTEAM_MEMBERS_URL, $id);
        $this->type = 'pvp_team_members';
        $this->type_settings['id'] = $id;
        return $this->parse();
    }
    
    #############
    #Search functions
    #############
    /**
     * Search Lodestone database
     *
     * @param string $type         Type of entity to search for, taken from `https://eu.finalfantasyxiv.com/lodestone/playguide/db/$type`
     * @param int    $category     Category of entities to search for, taken from `https://eu.finalfantasyxiv.com/lodestone/playguide/db/achievement/?category2=$category`
     * @param int    $sub_category Subcategory of entities to search for, taken from `https://eu.finalfantasyxiv.com/lodestone/playguide/db/achievement/?category2=2&category3=$subcategory`
     * @param string $search       Optional text to search for
     * @param int    $page         Page number to scan
     *
     * @return self
     */
    public function searchDatabase(#[ExpectedValues(['item', 'duty', 'quest', 'recipe', 'gathering', 'achievement', 'shop', 'text_command'])] string $type, int $category = 0, int $sub_category = 0, string $search = '', int $page = 1): self
    {
        #Ensure we have lowercase for consistency
        $type = mb_strtolower($type, 'UTF-8');
        if (!in_array($type, ['item', 'duty', 'quest', 'recipe', 'gathering', 'achievement', 'shop', 'text_command'])) {
            throw new \UnexpectedValueException('Unsupported type of database \''.$type.'\' element was requested');
        }
        $page = $this->pageCheck($page);
        $query = $this->queryBuilder([
            'db_search_category' => $type,
            'category2' => $category,
            #Duty has been updated at some point and category3 was replaced with ex_version
            ($type === 'duty' ? 'ex_version' : 'category3') => $sub_category,
            'q' => \str_ireplace(' ', '+', $search),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_DATABASE_URL, $type, $query);
        $this->type = 'database';
        $this->type_settings['type'] = $type;
        $this->type_settings['category'] = $category;
        $this->type_settings['sub_category'] = $sub_category;
        $this->type_settings['search'] = $search;
        return $this->parse();
    }
    
    /**
     * Search for a characters
     *
     * @param string           $name       Optional character name
     * @param string           $server     Optional server name
     * @param string           $class_job  Optional class/job
     * @param string           $race_tribe Optional race/clan
     * @param array|string|int $gc_id      Optional Grand Company (or list of them)
     * @param string|array     $blog_lang  Optional character language(s)
     * @param string           $order      Optional order to sort results by
     * @param int              $page       Page number to scan
     *
     * @return self
     */
    public function searchCharacter(string $name = '', string $server = '', string $class_job = '', string $race_tribe = '', array|string|int $gc_id = '', string|array $blog_lang = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $gc_id = $this->gcIdCheck($gc_id);
        if (is_array($blog_lang)) {
            foreach ($blog_lang as $key => $item) {
                $blog_lang[$key] = $this->converters->languageConvert($item);
            }
        } elseif (is_string($blog_lang)) {
            $blog_lang = $this->converters->languageConvert($blog_lang);
        } else {
            $blog_lang = '';
        }
        $query = \str_replace(['&blog_lang=&', '&gcId=&'], '&', $this->queryBuilder([
            'q' => \str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'class_job' => $this->converters->getSearchClassId($class_job),
            'race_tribe' => $this->converters->getSearchClanId($race_tribe),
            'gcId' => (is_array($gc_id) ? \implode('&gcId=', $gc_id) : $gc_id),
            'blog_lang' => (is_array($blog_lang) ? \implode('&blog_lang=', $blog_lang) : $blog_lang),
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]));
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_CHARACTERS_SEARCH_URL, $query);
        $this->type = 'search_character';
        $this->type_settings['name'] = $name;
        $this->type_settings['server'] = $server;
        $this->type_settings['class_job'] = $class_job;
        $this->type_settings['race_tribe'] = $race_tribe;
        $this->type_settings['gc_id'] = $gc_id;
        $this->type_settings['blog_lang'] = $blog_lang;
        $this->type_settings['order'] = $order;
        return $this->parse();
    }
    
    /**
     * Search for a free company
     *
     * @param string           $name            Optional free company name
     * @param string           $server          Optional server name
     * @param int              $character_count Optional members count
     * @param string|array     $activities      Optional activity (or list of), that company participates in
     * @param string|array     $roles           Optional role(s), that company is looking for
     * @param string           $active_time     Optional time, when company is active
     * @param string           $join            Optional recruitment status
     * @param string           $house           Optional filter by estate availability
     * @param array|string|int $gc_id           Optional grand company ID (or list of)
     * @param string           $order           Optional order to sort results by
     * @param int              $page            Page number to scan
     *
     * @return self
     */
    public function searchFreeCompany(string $name = '', string $server = '', int $character_count = 0, string|array $activities = '', string|array $roles = '', string $active_time = '', string $join = '', string $house = '', array|string|int $gc_id = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $gc_id = $this->gcIdCheck($gc_id);
        if (is_array($activities)) {
            foreach ($activities as $key => $item) {
                $activities[$key] = $this->converters->getSearchActivitiesId($item);
            }
        } elseif (is_string($activities)) {
            $activities = $this->converters->getSearchActivitiesId($activities);
        } else {
            $activities = '';
        }
        if (is_array($roles)) {
            foreach ($roles as $key => $item) {
                $roles[$key] = $this->converters->getSearchRolesId($item);
            }
        } elseif (is_string($roles)) {
            $roles = $this->converters->getSearchRolesId($roles);
        } else {
            $roles = '';
        }
        $query = \str_replace(['&activities=&', '&roles=&', '&gcId=&'], '&', $this->queryBuilder([
            'q' => \str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'character_count' => $this->converters->membersCount($character_count),
            'activities' => (is_array($activities) ? \implode('&activities=', $activities) : $activities),
            'roles' => (is_array($roles) ? \implode('&roles=', $roles) : $roles),
            'active_time' => $this->converters->getSearchActiveTimeId($active_time),
            'join' => $this->converters->getSearchJoinId($join),
            'house' => $this->converters->getSearchHouseId($house),
            'gcId' => (is_array($gc_id) ? \implode('&gcId=', $gc_id) : $gc_id),
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]));
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FREECOMPANY_SEARCH_URL, $query);
        $this->type = 'search_free_company';
        $this->type_settings['name'] = $name;
        $this->type_settings['server'] = $server;
        $this->type_settings['character_count'] = $character_count;
        $this->type_settings['activities'] = $activities;
        $this->type_settings['roles'] = $roles;
        $this->type_settings['active_time'] = $active_time;
        $this->type_settings['join'] = $join;
        $this->type_settings['house'] = $house;
        $this->type_settings['gc_id'] = $gc_id;
        $this->type_settings['order'] = $order;
        return $this->parse();
    }
    
    /**
     * Search for a linkshell
     * @param string $name            Optional linkshell name
     * @param string $server          Optional server (or data center) name
     * @param int    $character_count Optional member count
     * @param string $order           Optional order to sort results by
     * @param int    $page            Page number to scan
     * @param bool   $crossworld      Whether we are searching for a crossworld linkshell
     *
     * @return self
     */
    public function searchLinkshell(string $name = '', string $server = '', int $character_count = 0, string $order = '', int $page = 1, bool $crossworld = false): self
    {
        $page = $this->pageCheck($page);
        $query = $this->queryBuilder([
            'q' => \str_ireplace(' ', '+', $name),
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
        $this->type = 'search_linkshell';
        $this->type_settings['name'] = $name;
        $this->type_settings['server'] = $server;
        $this->type_settings['character_count'] = $character_count;
        $this->type_settings['order'] = $order;
        return $this->parse();
    }
    
    /**
     * Search for a PvP team
     * @param string $name   Optional PvP team
     * @param string $server Optional server name
     * @param string $order  Optional order to sort results by
     * @param int    $page   Page number to scan
     *
     * @return self
     */
    public function searchPvPTeam(string $name = '', string $server = '', string $order = '', int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $query = $this->queryBuilder([
            'q' => \str_ireplace(' ', '+', $name),
            'worldname' => $server,
            'order' => $this->converters->getSearchOrderId($order),
            'page' => $page,
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_PVPTEAM_SEARCH_URL, $query);
        $this->type = 'search_pvp_team';
        $this->type_settings['name'] = $name;
        $this->type_settings['server'] = $server;
        $this->type_settings['order'] = $order;
        return $this->parse();
    }
    
    #############
    #Rankings functions
    #############
    /**
     * Get Feast ranking
     *
     * @param int    $season    Season number
     * @param string $dc_group  Server/data center name
     * @param string $rank_type Type of rank to filter
     *
     * @return self
     */
    public function getFeast(int $season = 1, string $dc_group = '', string $rank_type = 'all'): self
    {
        if ($season <= 0) {
            $season = 1;
        }
        $query = $this->queryBuilder([
            'dcGroup' => $dc_group,
            'rank_type' => $this->converters->getFeastRankId($rank_type),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FEAST, (string)$season, $query);
        $this->type = 'feast';
        $this->type_settings['season'] = $season;
        return $this->parse();
    }
    
    /**
     * Get Deep Dungeon rankings
     * @param int|string $id         Deep Dungeon ID
     * @param string     $dc_group   Server/data center name
     * @param string     $solo_party Solo or party ranking
     * @param string     $subtype    Job filter in case of solo ranking
     *
     * @return self
     */
    public function getDeepDungeon(int|string $id = 1, string $dc_group = '', string $solo_party = 'party', string $subtype = 'PLD'): self
    {
        if ((int)$id <= 1) {
            $id = '';
        }
        if ($subtype) {
            $solo_party = 'solo';
        }
        if (!in_array($solo_party, ['party', 'solo'])) {
            $solo_party = 'party';
        }
        $query = $this->queryBuilder([
            'dcGroup' => $dc_group,
            'solo_party' => $solo_party,
            'subtype' => $this->converters->getDeepDungeonClassId($subtype),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_DEEP_DUNGEON, (string)$id, $query);
        if (empty($id)) {
            $id = 1;
        }
        if (empty($subtype)) {
            $subtype = $this->converters->getDeepDungeonClassId('PLD');
        }
        $this->type = 'deep_dungeon';
        $this->type_settings['dungeon'] = $id;
        $this->type_settings['solo_party'] = $solo_party;
        $this->type_settings['class'] = $subtype;
        return $this->parse();
    }
    
    /**
     * Get Frontline rankings
     * @param string $week_month Weekly or monthly rankings.
     * @param int    $week       Week number in `YYYYNN` format or month number in `YYYYMM` format.
     * @param string $dc_group   Optional data center name.
     * @param string $world_name Optional server name.
     * @param int    $pvp_rank   Optional minimum PvP rank.
     * @param int    $match      Optional minimum number of matches.
     * @param string $gc_id      Optional Grand Company to filter.
     * @param string $sort       Sorting order. Accepts `win` (sort by number of won matches), `match` (sort by total number of matches) and `rate` (sort by winning rate). Defaults to `win`.
     *
     * @return self
     */
    public function getFrontline(#[ExpectedValues(['weekly', 'monthly'])] string $week_month = 'weekly', int $week = 0, string $dc_group = '', string $world_name = '', int $pvp_rank = 0, int $match = 0, string $gc_id = '', #[ExpectedValues(['win', 'rate', 'match'])] string $sort = 'win'): self
    {
        if (!in_array($week_month, ['weekly', 'monthly'])) {
            $week_month = 'weekly';
        }
        if (!in_array($sort, ['win', 'rate', 'match'])) {
            $sort = 'win';
        }
        if ($week_month === 'weekly') {
            if (!\preg_match('/^\d{4}(0[1-9]|[1-4]\d|5[0-3])$/', (string)$week)) {
                $week = 0;
            }
        } elseif (!\preg_match('/^\d{4}(0[1-9]|1[0-2])$/', (string)$week)) {
            $week = 0;
        }
        $query = $this->queryBuilder([
            'filter' => 1,
            'sort' => $sort,
            'dcGroup' => $dc_group,
            'worldname' => $world_name,
            'pvp_rank' => $this->converters->pvpRank($pvp_rank),
            'match' => $this->converters->matchesCount($match),
            'gcId' => $this->converters->getSearchGCId($gc_id),
        ]);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FRONTLINE, $week_month, $week, $query);
        $this->type = 'frontline';
        $this->type_settings['week'] = $week;
        $this->type_settings['week_month'] = $week_month;
        return $this->parse();
    }
    
    /**
     * Get Grand Company rankings
     * @param string $week_month Weekly or monthly rankings
     * @param int    $week       Week number
     * @param string $world_name Optional server name
     * @param string $gc_id      Optional Grand Company to filter
     * @param int    $page       Page number to scan
     *
     * @return self
     */
    public function getGrandCompanyRanking(#[ExpectedValues(['weekly', 'monthly'])] string $week_month = 'weekly', int $week = 0, string $world_name = '', string $gc_id = '', int $page = 1): self
    {
        return $this->companyRankingHelper($week_month, $week, $world_name, $gc_id, $page, true);
    }
    
    /**
     * Get Free Company rankings
     * @param string $week_month Weekly or monthly rankings
     * @param int    $week       Week number
     * @param string $world_name  Optional server name
     * @param string $gc_id      Optional Grand Company to filter
     * @param int    $page       Page number to scan
     *
     * @return self
     */
    public function getFreeCompanyRanking(#[ExpectedValues(['weekly', 'monthly'])] string $week_month = 'weekly', int $week = 0, string $world_name = '', string $gc_id = '', int $page = 1): self
    {
        return $this->companyRankingHelper($week_month, $week, $world_name, $gc_id, $page);
    }
    
    /**
     * Helper for company ranking
     *
     * @param string $week_month Weekly or monthly rankings
     * @param int    $week       Week number
     * @param string $world_name  Optional server name
     * @param string $gc_id      Optional Grand Company to filter
     * @param int    $page       Page number to scan
     * @param bool   $gc         Whether this is a Grand Company ranking
     *
     * @return self
     */
    private function companyRankingHelper(#[ExpectedValues(['weekly', 'monthly'])] string $week_month = 'weekly', int $week = 0, string $world_name = '', string $gc_id = '', int $page = 1, bool $gc = false): self
    {
        $page = $this->pageCheck($page);
        if (!in_array($week_month, ['weekly', 'monthly'])) {
            $week_month = 'weekly';
        }
        if ($week_month === 'weekly') {
            if (!\preg_match('/^\d{4}(0[1-9]|[1-4]\d|5[0-3])$/', (string)$week)) {
                $week = 0;
            }
        } elseif (!\preg_match('/^\d{4}(0[1-9]|1[0-2])$/', (string)$week)) {
            $week = 0;
        }
        $query = $this->queryBuilder([
            'filter' => 1,
            'worldname' => $world_name,
            'gcId' => $this->converters->getSearchGCId($gc_id),
            'page' => $page,
        ]);
        if ($gc) {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_GCRANKING, $week_month, $week, $query);
            $this->type = 'grand_company_ranking';
        } else {
            $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_FCRANKING, $week_month, $week, $query);
            $this->type = 'free_company_ranking';
        }
        $this->type_settings['week'] = $week;
        $this->type_settings['week_month'] = $week_month;
        $this->type_settings['worldname'] = $world_name;
        $this->type_settings['gc_id'] = $gc_id;
        return $this->parse();
    }
    
    #############
    #Special pages functions
    #############
    /**
     * Get Lodestone banners (ads at the top)
     * @return self
     */
    public function getLodestoneBanners(): self
    {
        $this->url = sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_BANNERS;
        $this->type = 'banners';
        return $this->parse();
    }
    
    /**
     * Get Lodestone news
     * @return self
     */
    public function getLodestoneNews(): self
    {
        $this->url = sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_NEWS;
        $this->type = 'news';
        return $this->parse();
    }
    
    /**
     * Get Lodestone topics
     * @param int $page Page number to scan
     *
     * @return self
     */
    public function getLodestoneTopics(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_TOPICS, $page);
        $this->type = 'topics';
        return $this->parse();
    }
    
    /**
     * Get Lodestone notices
     * @param int $page Page number to scan
     *
     * @return self
     */
    public function getLodestoneNotices(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_NOTICES, $page);
        $this->type = 'notices';
        return $this->parse();
    }
    
    /**
     * Get Lodestone maintenance information
     * @param int $page Page number to scan
     *
     * @return self
     */
    public function getLodestoneMaintenance(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_MAINTENANCE, $page);
        $this->type = 'maintenance';
        return $this->parse();
    }
    
    /**
     * Get Lodestone updates information
     * @param int $page Page number to scan
     *
     * @return self
     */
    public function getLodestoneUpdates(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_UPDATES, $page);
        $this->type = 'updates';
        return $this->parse();
    }
    
    /**
     * Get Lodestone status updates
     * @param int $page Page number to scan
     *
     * @return self
     */
    public function getLodestoneStatus(int $page = 1): self
    {
        $page = $this->pageCheck($page);
        $this->url = sprintf(sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_STATUS, $page);
        $this->type = 'status';
        return $this->parse();
    }
    
    /**
     * Get status of servers
     * @param bool $world_details Whether to show detailed status of worlds or not
     *
     * @return self
     */
    public function getWorldStatus(bool $world_details = false): self
    {
        $this->url = sprintf(Routes::LODESTONE_URL_BASE, $this->language).Routes::LODESTONE_WORLD_STATUS;
        $this->type = 'worlds';
        $this->type_settings['world_details'] = $world_details;
        return $this->parse();
    }
    
    #############
    #Logic to accumulate filters and add them as parameters to URL
    #############
    /**
     * Helper function to generate a GET query for other functions
     * @param array $params
     *
     * @return string
     */
    private function queryBuilder(array $params): string
    {
        $query = [];
        foreach ($params as $param => $value) {
            if (empty($value) && $value !== '0') {
                continue;
            }
            $query[] = $param.'='.$value;
        }
        return '?'.\implode('&', $query);
    }
    
    /**
     * Helper function to enable `all pages` logic, where supported
     * @param int $page
     *
     * @return int
     */
    private function pageCheck(int $page): int
    {
        if ($page === 0) {
            $page = 1;
            $this->all_pages = true;
        }
        return $page;
    }
    
    /**
     * Helper function to convert strings into Grand Company IDs
     * @param array|string|int $gc_id
     *
     * @return string|array
     */
    #[Pure] private function gcIdCheck(array|string|int $gc_id): string|array
    {
        if (is_array($gc_id)) {
            foreach ($gc_id as $key => $item) {
                $gc_id[$key] = $this->converters->getSearchGCId($item);
            }
        } elseif (is_string($gc_id)) {
            $gc_id = $this->converters->getSearchGCId($gc_id);
        } else {
            $gc_id = '';
        }
        return $gc_id;
    }
    
    #############
    #Settings functions
    #############
    /**
     * Set a custom user-agent
     * @param string $user_agent
     *
     * @return $this
     */
    public function setUserAgent(string $user_agent = ''): self
    {
        $this->user_agent = $user_agent;
        return $this;
    }
    
    /**
     * Set language
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage(string $language = ''): self
    {
        if (!in_array($language, self::LANGUAGES_ALLOWED, true)) {
            $language = 'na';
        }
        if (in_array($language, ['jp', 'ja'])) {
            $language = 'jp';
        }
        $this->language = $language;
        return $this;
    }
    
    /**
     * Enable or disable benchmark
     * @param bool $bench
     *
     * @return $this
     */
    public function setBenchmark(bool $bench = false): self
    {
        $this->benchmark = $bench;
        return $this;
    }
}

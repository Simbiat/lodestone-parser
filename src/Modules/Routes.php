<?php
declare(strict_types=1);
namespace Lodestone\Modules;

/**
 * URL's for Lodestone content
 *
 * The API is currently built based on the NA lodestone,
 * changing the lodestone to de/fr may work but this has
 * not been tested and can't be guaranteed.
 *
 * Class Routes
 * @package Lodestone\Modules
 */
class Routes
{
    #base URL
    const LODESTONE_URL_BASE = 'https://%s.finalfantasyxiv.com/lodestone';
    
    #characters
    const LODESTONE_CHARACTERS_URL = '/character/%s/';
    const LODESTONE_CHARACTERS_FRIENDS_URL = '/character/%s/friend/?page=%u';
    const LODESTONE_CHARACTERS_FOLLOWING_URL = '/character/%s/following/?page=%u';
    const LODESTONE_CHARACTERS_JOBS_URL = '/character/%s/class_job/';
    const LODESTONE_CHARACTERS_MINIONS_URL = '/character/%s/minion/';
    const LODESTONE_CHARACTERS_MOUNTS_URL = '/character/%s/mount/';
    const LODESTONE_CHARACTERS_SEARCH_URL = '/character/%s';
    const LODESTONE_ACHIEVEMENTS_URL = '/character/%s/achievement/kind/%u/';
    const LODESTONE_ACHIEVEMENTS_CAT_URL = '/character/%s/achievement/category/%u/';
    const LODESTONE_ACHIEVEMENTS_DET_URL = '/character/%s/achievement/detail/%u/';
    #free company
    const LODESTONE_FREECOMPANY_URL = '/freecompany/%s/';
    const LODESTONE_FREECOMPANY_SEARCH_URL = '/freecompany/%s';
    const LODESTONE_FREECOMPANY_MEMBERS_URL = '/freecompany/%s/member/?page=%u';
    #linkshell
    const LODESTONE_LINKSHELL_SEARCH_URL = '/linkshell/%s';
    const LODESTONE_LINKSHELL_MEMBERS_URL = '/linkshell/%s/?page=%u';
    const LODESTONE_CROSSWORLD_LINKSHELL_SEARCH_URL = '/crossworld_linkshell/%s';
    const LODESTONE_CROSSWORLD_LINKSHELL_MEMBERS_URL = '/crossworld_linkshell/%s/?page=%u';
    #pvp team
    const LODESTONE_PVPTEAM_SEARCH_URL = '/pvpteam/%s';
    const LODESTONE_PVPTEAM_MEMBERS_URL = '/pvpteam/%s/';
    #news
    const LODESTONE_BANNERS = '/';
    const LODESTONE_NEWS = '/news/';
    const LODESTONE_TOPICS = '/topics/?page=%u';
    const LODESTONE_NOTICES = '/news/category/1/?page=%u';
    const LODESTONE_MAINTENANCE = '/news/category/2/?page=%u';
    const LODESTONE_UPDATES = '/news/category/3/?page=%u';
    const LODESTONE_STATUS = '/news/category/4/?page=%u';
    #world status
    const LODESTONE_WORLD_STATUS = '/worldstatus/';
    #feast
    const LODESTONE_FEAST = '/ranking/thefeast/result/%s/%s';
    #deep dungeon
    const LODESTONE_DEEP_DUNGEON = '/ranking/deepdungeon%s/%s';
    #frontline
    const LODESTONE_FRONTLINE = '/ranking/frontline/%s/%u/%s';
    #company rankings
    const LODESTONE_GCRANKING = '/ranking/gc/%s/%u/%s';
    const LODESTONE_FCRANKING = '/ranking/fc/%s/%u/%s';
    #database
    const LODESTONE_DATABASE_URL = '/playguide/db/%s/%s';
}

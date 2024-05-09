<?php
declare(strict_types = 1);

namespace Simbiat\LodestoneModules;

/**
 * URL's for Lodestone content
 */
class Routes
{
    #base URL
    public const string LODESTONE_URL_BASE = 'https://%s.finalfantasyxiv.com/lodestone';
    
    #characters
    public const string LODESTONE_CHARACTERS_URL = '/character/%s/';
    public const string LODESTONE_CHARACTERS_FRIENDS_URL = '/character/%s/friend/?page=%u';
    public const string LODESTONE_CHARACTERS_FOLLOWING_URL = '/character/%s/following/?page=%u';
    public const string LODESTONE_CHARACTERS_JOBS_URL = '/character/%s/class_job/';
    public const string LODESTONE_CHARACTERS_MINIONS_URL = '/character/%s/minion/';
    public const string LODESTONE_CHARACTERS_MOUNTS_URL = '/character/%s/mount/';
    public const string LODESTONE_CHARACTERS_SEARCH_URL = '/character/%s';
    public const string LODESTONE_ACHIEVEMENTS_URL = '/character/%s/achievement/kind/%u/';
    public const string LODESTONE_ACHIEVEMENTS_CAT_URL = '/character/%s/achievement/category/%u/';
    public const string LODESTONE_ACHIEVEMENTS_DET_URL = '/character/%s/achievement/detail/%u/';
    #free company
    public const string LODESTONE_FREECOMPANY_URL = '/freecompany/%s/';
    public const string LODESTONE_FREECOMPANY_SEARCH_URL = '/freecompany/%s';
    public const string LODESTONE_FREECOMPANY_MEMBERS_URL = '/freecompany/%s/member/?page=%u';
    #linkshell
    public const string LODESTONE_LINKSHELL_SEARCH_URL = '/linkshell/%s';
    public const string LODESTONE_LINKSHELL_MEMBERS_URL = '/linkshell/%s/?page=%u';
    public const string LODESTONE_CROSSWORLD_LINKSHELL_SEARCH_URL = '/crossworld_linkshell/%s';
    public const string LODESTONE_CROSSWORLD_LINKSHELL_MEMBERS_URL = '/crossworld_linkshell/%s/?page=%u';
    #pvp team
    public const string LODESTONE_PVPTEAM_SEARCH_URL = '/pvpteam/%s';
    public const string LODESTONE_PVPTEAM_MEMBERS_URL = '/pvpteam/%s/';
    #news
    public const string LODESTONE_BANNERS = '/';
    public const string LODESTONE_NEWS = '/news/';
    public const string LODESTONE_TOPICS = '/topics/?page=%u';
    public const string LODESTONE_NOTICES = '/news/category/1/?page=%u';
    public const string LODESTONE_MAINTENANCE = '/news/category/2/?page=%u';
    public const string LODESTONE_UPDATES = '/news/category/3/?page=%u';
    public const string LODESTONE_STATUS = '/news/category/4/?page=%u';
    #world status
    public const string LODESTONE_WORLD_STATUS = '/worldstatus/';
    #feast
    public const string LODESTONE_FEAST = '/ranking/thefeast/result/%s/%s';
    #deep dungeon
    public const string LODESTONE_DEEP_DUNGEON = '/ranking/deepdungeon%s/%s';
    #frontline
    public const string LODESTONE_FRONTLINE = '/ranking/frontline/%s/%u/%s';
    #company rankings
    public const string LODESTONE_GCRANKING = '/ranking/gc/%s/%u/%s';
    public const string LODESTONE_FCRANKING = '/ranking/fc/%s/%u/%s';
    #database
    public const string LODESTONE_DATABASE_URL = '/playguide/db/%s/%s';
}

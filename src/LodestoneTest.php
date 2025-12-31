<?php
declare(strict_types = 1);

namespace Simbiat\FFXIV;

use function in_array;

/**
 * Class to generate a test report for Lodestone Parser
 */
class LodestoneTest
{
    private object $lodestone;
    
    /**
     * Creation of the test object
     * @param string $language
     */
    public function __construct(string $language = 'na')
    {
        \ini_set('max_execution_time', '6000');
        \ob_clean();
        echo '<style>
                table, th, td, tr {border: 1px solid black; border-collapse: collapse;}
                pre {height: 5pc; max-width: 600px; overflow-y: scroll;}
            </style>
            <table><th>Type of test</th><th>Result</th><th>Page time, hh:mm:ss.ms</th><th>Parse time, hh:mm:ss.ms</th><th>Errors</th><th>Output</th>';
        $this->lodestone = new Lodestone()->setLanguage($language)->setUserAgent('Lodestone PHP Parser')->setBenchmark(true);
        
        #Checking characters
        $this->lodestone = $this->lodestone->getCharacter('6691027');
        $this->tableLine('Character (regular)', $this->lodestone->getResult()['characters']['6691027']);
        $this->lodestone = $this->lodestone->getCharacter('21915843');
        $this->tableLine('Character (empty)', $this->lodestone->getResult()['characters']['21915843']);
        $this->lodestone = $this->lodestone->getCharacter('21471245');
        $this->tableLine('Character (with PvP)', $this->lodestone->getResult()['characters']['21471245']);
        $this->lodestone = $this->lodestone->getCharacterJobs('6691027');
        $this->tableLine('Character jobs', $this->lodestone->getResult()['characters']['6691027']['jobs']);
        $this->lodestone = $this->lodestone->getCharacterFriends('6691027');
        $this->tableLine('Character friends', $this->lodestone->getResult()['characters']['6691027']['friends']);
        $this->lodestone = $this->lodestone->getCharacterFollowing('6691027');
        $this->tableLine('Character following', $this->lodestone->getResult()['characters']['6691027']['following']);
        $this->lodestone = $this->lodestone->getCharacterAchievements('6691027', false, 39, true, true);
        $this->tableLine('Achievements', $this->lodestone->getResult()['characters']['6691027']['achievements']);
        
        #Checking groups
        $this->lodestone = $this->lodestone->getFreeCompany('9234631035923213559');
        $this->tableLine('Free company (regular)', $this->lodestone->getResult()['freecompanies']['9234631035923213559']);
        $this->lodestone = $this->lodestone->getFreeCompanyMembers('9234631035923202551', 0);
        $this->tableLine('Free company members', $this->lodestone->getResult()['freecompanies']['9234631035923202551']['members']);
        $this->lodestone = $this->lodestone->getFreeCompany('9234631035923243608');
        $this->tableLine('Free company (no estate, ranking and greeting)', $this->lodestone->getResult()['freecompanies']['9234631035923243608']);
        $this->lodestone = $this->lodestone->getFreeCompany('9234631035923203676');
        $this->tableLine('Free company (no plot, focus and recruitment)', $this->lodestone->getResult()['freecompanies']['9234631035923203676']);
        $this->lodestone = $this->lodestone->getLinkshellMembers('19984723346535274');
        $this->tableLine('Linkshell', $this->lodestone->getResult()['linkshells']['19984723346535274']);
        $this->lodestone = $this->lodestone->getPvPTeam('d1ce24446f4fbf6e0eabd31334feef2bc16966d1');
        $this->tableLine('PvP team', $this->lodestone->getResult()['pvpteams']['d1ce24446f4fbf6e0eabd31334feef2bc16966d1']);
        
        #Checking searches
        $this->lodestone = $this->lodestone->searchCharacter();
        $this->tableLine('Character search', $this->lodestone->getResult(false)['characters']);
        $this->lodestone = $this->lodestone->searchFreeCompany();
        $this->tableLine('Free company search', $this->lodestone->getResult(false)['freecompanies']);
        $this->lodestone = $this->lodestone->searchLinkshell();
        $this->tableLine('Linkshell search', $this->lodestone->getResult(false)['linkshells']);
        $this->lodestone = $this->lodestone->searchPvPTeam();
        $this->tableLine('PvP teams search', $this->lodestone->getResult(false)['pvpteams']);
        
        #Checking specials
        $this->lodestone = $this->lodestone->getLodestoneBanners();
        $this->tableLine('Banners', $this->lodestone->getResult(false)['banners']);
        $this->lodestone = $this->lodestone->getLodestoneNews();
        $this->tableLine('News', $this->lodestone->getResult(false)['news']);
        $this->lodestone = $this->lodestone->getLodestoneTopics();
        $this->tableLine('Topics', $this->lodestone->getResult(false)['topics']);
        $this->lodestone = $this->lodestone->getLodestoneNotices();
        $this->tableLine('Notices', $this->lodestone->getResult(false)['notices']);
        $this->lodestone = $this->lodestone->getLodestoneMaintenance();
        $this->tableLine('Maintenance', $this->lodestone->getResult(false)['maintenance']);
        $this->lodestone = $this->lodestone->getLodestoneUpdates();
        $this->tableLine('Updates', $this->lodestone->getResult(false)['updates']);
        $this->lodestone = $this->lodestone->getLodestoneStatus();
        $this->tableLine('Status', $this->lodestone->getResult(false)['status']);
        $this->lodestone = $this->lodestone->getWorldStatus();
        $this->tableLine('Worlds', $this->lodestone->getResult(false)['worlds']);
        
        #Checking rankings
        $this->lodestone = $this->lodestone->getFeast();
        $this->tableLine('Feast (older format)', $this->lodestone->getResult(false)['feast'][1]);
        $this->lodestone = $this->lodestone->getFeast(8);
        $this->tableLine('Feast (current format)', $this->lodestone->getResult(false)['feast'][8]);
        $this->lodestone = $this->lodestone->getDeepDungeon(1, '', 'solo', 'BRD');
        $this->tableLine('Palace of the Dead, BRD', $this->lodestone->getResult(false)['deep_dungeon'][1]['solo']['BRD']);
        $this->lodestone = $this->lodestone->getDeepDungeon(2, '', '', '');
        $this->tableLine('Heaven-on-High, party', $this->lodestone->getResult(false)['deep_dungeon'][2]['party']);
        $this->lodestone = $this->lodestone->getFrontline();
        $this->tableLine('Frontline', $this->lodestone->getResult(false)['frontline']['weekly'][0]);
        $this->lodestone = $this->lodestone->getGrandCompanyRanking('weekly', 0, 'Cerberus');
        $this->tableLine('Grand Company Ranking', $this->lodestone->getResult(false)['grand_company_ranking']['weekly'][0]);
        $this->lodestone = $this->lodestone->getFreeCompanyRanking('weekly', 0, 'Cerberus');
        $this->tableLine('Free Company Ranking', $this->lodestone->getResult(false)['free_company_ranking']['weekly'][0]);
        
        #Checking database
        $this->lodestone = $this->lodestone->searchDatabase('achievement', 1);
        $this->tableLine('Play guide: achievements', $this->lodestone->getResult(false)['database']['achievement']);
        $this->lodestone = $this->lodestone->searchDatabase('quest', 1);
        $this->tableLine('Play guide: quests', $this->lodestone->getResult(false)['database']['quest']);
        $this->lodestone = $this->lodestone->searchDatabase('duty', 2);
        $this->tableLine('Play guide: duties', $this->lodestone->getResult(false)['database']['duty']);
        $this->lodestone = $this->lodestone->searchDatabase('item', 1);
        $this->tableLine('Play guide: items', $this->lodestone->getResult(false)['database']['item']);
        $this->lodestone = $this->lodestone->searchDatabase('recipe', 1);
        $this->tableLine('Play guide: recipes', $this->lodestone->getResult(false)['database']['recipe']);
        $this->lodestone = $this->lodestone->searchDatabase('gathering', 1);
        $this->tableLine('Play guide: gathering', $this->lodestone->getResult(false)['database']['gathering']);
        $this->lodestone = $this->lodestone->searchDatabase('shop', 1);
        $this->tableLine('Play guide: shops', $this->lodestone->getResult(false)['database']['shop']);
        $this->lodestone = $this->lodestone->searchDatabase('text_command', 1);
        $this->tableLine('Play guide: text commands', $this->lodestone->getResult(false)['database']['text_command']);
        
        #Checking Errors
        $this->lodestone = $this->lodestone->getFreeCompany('1');
        $this->tableLine('Non-existent free company', $this->lodestone->getResult(false)['freecompanies']['1'], true);
        $this->lodestone = $this->lodestone->getLinkshellMembers('1');
        $this->tableLine('Non-existent linkshell', $this->lodestone->getResult(false)['linkshells']['1'], true);
        $this->lodestone = $this->lodestone->getCharacter('9234631035923213559');
        $this->tableLine('Non-existent character', $this->lodestone->getResult(false)['characters']['9234631035923213559'], true);
        $this->lodestone = $this->lodestone->getCharacterAchievements('4339591', false, 39, false, true);
        $this->tableLine('Character with private achievements', $this->lodestone->getResult()['characters']['4339591']['achievements'], true);
        unset($this->lodestone);
        echo '</table>';
    }
    
    /**
     * Generate table row for the report
     * @param string $type    Type of the test
     * @param mixed  $what    Contents of the test
     * @param bool   $reverse Whether to reverse `good/bad` logic
     *
     * @return void
     */
    private function tableLine(string $type, mixed $what, bool $reverse = false): void
    {
        echo '<tr>
                    <td><b>'.$type.'</b></td>
                    <td>'.(!empty($this->lodestone->getErrors()) && !$reverse ? '<span style="color: red; font-weight: bold;">error</span>' : '<span style="color: lightgreen; font-weight: bold;">success</span>').'</td>
                    <td>'.(in_array($type, ['Achievements', 'Free company members']) ? \implode('<br>', $this->lodestone->getResult(false)['benchmark']['http_time']) : $this->lodestone->getResult(false)['benchmark']['http_time'][0]).'</td>
                    <td>'.(in_array($type, ['Achievements', 'Free company members']) ? \implode('<br>', $this->lodestone->getResult(false)['benchmark']['parse_time']) : $this->lodestone->getResult(false)['benchmark']['parse_time'][0]).'</td>
                    <td>'.(empty($this->lodestone->getErrors()) ? '' : '<pre>'.\var_export($this->lodestone->getErrors(), true).'</pre>').'</td>
                    <td>'.(empty($what) ? '' : '<pre>'.\var_export($what, true).'</pre>').'</td>
                </tr>';
        $this->lodestone->resetResult();
        $this->lodestone->resetErrors();
        \ob_flush();
        \flush();
    }
}

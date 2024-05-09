<?php
declare(strict_types = 1);

namespace Simbiat;

use function in_array;

/**
 * Class to generate test report for Lodestone Parser
 */
class LodestoneTest
{
    private object $Lodestone;
    
    /**
     * Creation of the test object
     * @param string $language
     */
    public function __construct(string $language = 'na')
    {
        ini_set('max_execution_time', '6000');
        ob_clean();
        echo '<style>
                table, th, td, tr {border: 1px solid black; border-collapse: collapse;}
                pre {height: 5pc; max-width: 600px; overflow-y: scroll;}
            </style>
            <table><th>Type of test</th><th>Result</th><th>Page time, hh:mm:ss.ms</th><th>Parse time, hh:mm:ss.ms</th><th>Errors</th><th>Output</th>';
        $this->Lodestone = (new Lodestone())->setLanguage($language)->setUseragent('Simbiat Software UAT')->setBenchmark(true);
        
        #Checking characters
        $this->Lodestone->getCharacter('6691027');
        $this->tableLine('Character (regular)', $this->Lodestone->getResult(false)['characters']['6691027']);
        $this->Lodestone->getCharacter('21915843');
        $this->tableLine('Character (empty)', $this->Lodestone->getResult(false)['characters']['21915843']);
        $this->Lodestone->getCharacter('21471245');
        $this->tableLine('Character (with PvP)', $this->Lodestone->getResult(false)['characters']['21471245']);
        $this->Lodestone->getCharacterJobs('6691027');
        $this->tableLine('Character jobs', $this->Lodestone->getResult(false)['characters']['6691027']['jobs']);
        $this->Lodestone->getCharacterFriends('6691027');
        $this->tableLine('Character friends', $this->Lodestone->getResult(false)['characters']['6691027']['friends']);
        $this->Lodestone->getCharacterFollowing('6691027')->getResult(false);
        $this->tableLine('Character following', $this->Lodestone->getResult(false)['characters']['6691027']['following']);
        $this->Lodestone->getCharacterAchievements('6691027', false, 39, true, true);
        $this->tableLine('Achievements', $this->Lodestone->getResult(false)['characters']['6691027']['achievements']);
        
        #Checking groups
        $this->Lodestone->getFreeCompany('9234631035923213559');
        $this->tableLine('Free company (regular)', $this->Lodestone->getResult(false)['freecompanies']['9234631035923213559']);
        $this->Lodestone->getFreeCompanyMembers('9234631035923202551', 0);
        $this->tableLine('Free company members', $this->Lodestone->getResult(false)['freecompanies']['9234631035923202551']['members']);
        $this->Lodestone->getFreeCompany('9234631035923243608');
        $this->tableLine('Free company (no estate, ranking and greeting)', $this->Lodestone->getResult(false)['freecompanies']['9234631035923243608']);
        $this->Lodestone->getFreeCompany('9234631035923203676');
        $this->tableLine('Free company (no plot, focus and recruitment)', $this->Lodestone->getResult(false)['freecompanies']['9234631035923203676']);
        $this->Lodestone->getLinkshellMembers('19984723346535274');
        $this->tableLine('Linkshell', $this->Lodestone->getResult(false)['linkshells']['19984723346535274']);
        $this->Lodestone->getPvPTeam('d1ce24446f4fbf6e0eabd31334feef2bc16966d1');
        $this->tableLine('PvP team', $this->Lodestone->getResult(false)['pvpteams']['d1ce24446f4fbf6e0eabd31334feef2bc16966d1']);
        
        #Checking searches
        $this->Lodestone->searchCharacter();
        $this->tableLine('Character search', $this->Lodestone->getResult(false)['characters']);
        $this->Lodestone->searchFreeCompany();
        $this->tableLine('Free company search', $this->Lodestone->getResult(false)['freecompanies']);
        $this->Lodestone->searchLinkshell();
        $this->tableLine('Linkshell search', $this->Lodestone->getResult(false)['linkshells']);
        $this->Lodestone->searchPvPTeam();
        $this->tableLine('PvP teams search', $this->Lodestone->getResult(false)['pvpteams']);
        
        #Checking specials
        $this->Lodestone->getLodestoneBanners();
        $this->tableLine('Banners', $this->Lodestone->getResult(false)['banners']);
        $this->Lodestone->getLodestoneNews();
        $this->tableLine('News', $this->Lodestone->getResult(false)['news']);
        $this->Lodestone->getLodestoneTopics();
        $this->tableLine('Topics', $this->Lodestone->getResult(false)['topics']);
        $this->Lodestone->getLodestoneNotices();
        $this->tableLine('Notices', $this->Lodestone->getResult(false)['notices']);
        $this->Lodestone->getLodestoneMaintenance();
        $this->tableLine('Maintenance', $this->Lodestone->getResult(false)['maintenance']);
        $this->Lodestone->getLodestoneUpdates();
        $this->tableLine('Updates', $this->Lodestone->getResult(false)['updates']);
        $this->Lodestone->getLodestoneStatus();
        $this->tableLine('Status', $this->Lodestone->getResult(false)['status']);
        $this->Lodestone->getWorldStatus();
        $this->tableLine('Worlds', $this->Lodestone->getResult(false)['worlds']);
        
        #Checking rankings
        $this->Lodestone->getFeast();
        $this->tableLine('Feast (older format)', $this->Lodestone->getResult(false)['feast'][1]);
        $this->Lodestone->getFeast(8);
        $this->tableLine('Feast (current format)', $this->Lodestone->getResult(false)['feast'][8]);
        $this->Lodestone->getDeepDungeon(1, '', 'solo', 'BRD');
        $this->tableLine('Palace of the Dead, BRD', $this->Lodestone->getResult(false)['deepdungeon'][1]['solo']['BRD']);
        $this->Lodestone->getDeepDungeon(2, '', '', '');
        $this->tableLine('Heaven-on-High, party', $this->Lodestone->getResult(false)['deepdungeon'][2]['party']);
        $this->Lodestone->getFrontline();
        $this->tableLine('Frontline', $this->Lodestone->getResult(false)['frontline']['weekly'][0]);
        $this->Lodestone->getGrandCompanyRanking('weekly', 0, 'Cerberus');
        $this->tableLine('Grand Company Ranking', $this->Lodestone->getResult(false)['GrandCompanyRanking']['weekly'][0]);
        $this->Lodestone->getFreeCompanyRanking('weekly', 0, 'Cerberus');
        $this->tableLine('Free Company Ranking', $this->Lodestone->getResult(false)['FreeCompanyRanking']['weekly'][0]);
        
        #Checking database
        $this->Lodestone->searchDatabase('achievement', 1);
        $this->tableLine('Play guide: achievements', $this->Lodestone->getResult(false)['database']['achievement']);
        $this->Lodestone->searchDatabase('quest', 1);
        $this->tableLine('Play guide: quests', $this->Lodestone->getResult(false)['database']['quest']);
        $this->Lodestone->searchDatabase('duty', 2);
        $this->tableLine('Play guide: duties', $this->Lodestone->getResult(false)['database']['duty']);
        $this->Lodestone->searchDatabase('item', 1);
        $this->tableLine('Play guide: items', $this->Lodestone->getResult(false)['database']['item']);
        $this->Lodestone->searchDatabase('recipe', 1);
        $this->tableLine('Play guide: recipes', $this->Lodestone->getResult(false)['database']['recipe']);
        $this->Lodestone->searchDatabase('gathering', 1);
        $this->tableLine('Play guide: gathering', $this->Lodestone->getResult(false)['database']['gathering']);
        $this->Lodestone->searchDatabase('shop', 1);
        $this->tableLine('Play guide: shops', $this->Lodestone->getResult(false)['database']['shop']);
        $this->Lodestone->searchDatabase('text_command', 1);
        $this->tableLine('Play guide: text commands', $this->Lodestone->getResult(false)['database']['text_command']);
        
        #Checking Errors
        $this->Lodestone->getFreeCompany('1');
        $this->tableLine('Non-existent free company', $this->Lodestone->getResult(false)['freecompanies']['1'], true);
        $this->Lodestone->getLinkshellMembers('1');
        $this->tableLine('Non-existent linkshell', $this->Lodestone->getResult(false)['linkshells']['1'], true);
        $this->Lodestone->getCharacter('9234631035923213559');
        $this->tableLine('Non-existent character', $this->Lodestone->getResult(false)['characters']['9234631035923213559'], true);
        $this->Lodestone->getCharacterAchievements('4339591', false, 39, false, true);
        $this->tableLine('Character with private achievements', $this->Lodestone->getResult()['characters']['4339591']['achievements'], true);
        unset($this->Lodestone);
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
                    <td>'.(!empty($this->Lodestone->getErrors()) && $reverse === false ? '<span style="color: red; font-weight: bold;">error</span>' : '<span style="color: lightgreen; font-weight: bold;">success</span>').'</td>
                    <td>'.(in_array($type, ['Achievements', 'Free company members']) ? implode('<br>', $this->Lodestone->getResult(false)['benchmark']['httptime']) : $this->Lodestone->getResult(false)['benchmark']['httptime'][0]).'</td>
                    <td>'.(in_array($type, ['Achievements', 'Free company members']) ? implode('<br>', $this->Lodestone->getResult(false)['benchmark']['parsetime']) : $this->Lodestone->getResult(false)['benchmark']['parsetime'][0]).'</td>
                    <td>'.(empty($this->Lodestone->getErrors()) ? '' : '<pre>'.var_export($this->Lodestone->getErrors(), true).'</pre>').'</td>
                    <td>'.(empty($what) ? '' : '<pre>'.var_export($what, true).'</pre>').'</td>
                </tr>';
        $this->Lodestone->resetResult();
        $this->Lodestone->resetErrors();
        ob_flush();
        flush();
    }
}

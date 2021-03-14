<?php
#Functions used to convert textual filters to appropriate IDs used by Lodestone
declare(strict_types=1);
namespace Simbiat\LodestoneModules;

class Converters {
    
    public function FCRankID(string $image): string
    {
    	if (strpos($image, 'W5a6yeRyN2eYiaV-AGU7mJKEhs') !== false) {
    		$rank = '0';
    	} elseif (strpos($image, 'SO2DiXPE4vb5ZquxK9qZzaS2FI') !== false) {
    		$rank = '12';
    	} elseif (strpos($image, 'hOa5rExOnxaN1WNnQqZYe3Vb7c') !== false) {
    		$rank = '8';
    	} elseif (strpos($image, 'eWQ8n_shMm6W0LoRN9KodNZ8tw') !== false) {
    		$rank = '14';
    	} elseif (strpos($image, 'p94F1j-5xhM2ySM16VNrA08qjU') !== false) {
    		$rank = '1';
    	} elseif (strpos($image, 'nw6rom1Gt5lCuBPbSsRUeFEAYo') !== false) {
    		$rank = '9';
    	} elseif (strpos($image, 'qm0y-fW7o2TvgYFH-vvcL-IH8s') !== false) {
    		$rank = '10';
    	} elseif (strpos($image, 'hU1Eoa9YXYljYZSLr_PDKlS9rA') !== false) {
    		$rank = '11';
    	} elseif (strpos($image, 'cliLaxMGlva579Q7-BGQofaHoU') !== false) {
    		$rank = '3';
    	} elseif (strpos($image, 'zXxmuKQfvR0_XbK-Q9tGafCvZQ') !== false) {
    		$rank = '4';
    	} elseif (strpos($image, 'ZgBF9xaOv1cXJ5hpqJk775gPnU') !== false) {
    		$rank = '7';
    	} elseif (strpos($image, 'MORWKTwHdU9RwJjwTjA8Goqczg') !== false) {
    		$rank = '13';
    	} elseif (strpos($image, 'uIrHic2MOYHNS316SWOpAFgMKM') !== false) {
    		$rank = '6';
    	} elseif (strpos($image, 'wy6luU_yTtJcSMjKaq-g7_uxX0') !== false) {
    		$rank = '2';
    	} elseif (strpos($image, 'IjnNzh88h17r2k16noer9mUzZo') !== false) {
    		$rank = '5';
    	} else {
    	    $rank = '';
    	}
    	return $rank;
    }
    
    public function imageToBool(string $img): bool
    {
        return in_array(strtolower($img), [
            'yes',
            '○',
            'oui',
            '○',
            'https://img.finalfantasyxiv.com/lds/h/h/iMiPYBWuh22FtFJTn2coPIp0I0.png',
            'https://img.finalfantasyxiv.com/lds/h/A/te5gEgEroS6xWX-bPrAVizJcSg.png',
            'https://img.finalfantasyxiv.com/lds/h/D/_VRXR3uARNQzxAv1v16NYvS5xk.png',
        ]);
    }
    
    public function getAchKindId(string $kind): string
    {
        return match(strtolower($kind)) {
            '1', 'battle', 'バトル', 'combats', 'kampferfolge' => '1',
            '2', 'pvp', 'jcj' => '2',
            '3', 'character', 'キャラクター', 'personnage', 'charakter' => '3',
            '4', 'items', 'アイテム', 'objets', 'gegenstände' => '4',
            '5', 'crafting', '製作', 'synthèse', 'synthese' => '5',
            '6', 'gathering', '採集', 'récolte', 'sammeln' => '6',
            '8', 'quests', 'クエスト', 'quêtes', 'aufträge' => '8',
            '11', 'exploration', '探検', 'erkundungen' => '11',
            '12', 'grand company', 'グランドカンパニー', 'grandes compagnies', 'staatliche gesellschaften' => '12',
            '13', 'legacy', 'レガシー' => '13',
            default => '1',
        };
    }
    
    public function getAchCatId(string $cat): string
    {
        return match(strtolower($cat)) {
            '1', 'battle', '全般', 'général', 'kampferfolge' => '1',
            '2', 'dungeons', 'ダンジョン', 'donjons' => '2',
            '3', 'trials', '討伐・討滅戦', 'défis', 'prüfungen' => '3',
            '4', 'raids', 'レイド' => '4',
            '5', 'the hunt', 'モブハント', 'contrats de chasse', 'hohe jagd' => '5',
            '6', 'treasure hunt', 'トレジャーハント', 'chasse aux trésors', 'schatzsuche' => '6',
            #French uses duplicate name here, thus not supported
            '7', 'general', '全般', 'allgemein' => '7',
            '8', 'ranking', 'ランキング', 'classement', 'pvp-ranglisten' => '8',
            '9', 'the wolve\'s den', 'コロセウム', 'l\'antre des loups', 'wolfshöhle' => '9',
            '10', 'frontline', 'フロントライン', 'front' => '10',
            '11', 'rival wings', 'ライバルウィングズ', 'ailes rivales', 'stahlschwingen' => '11',
            #Japanese and French use duplicate name here, thus not supported
            '12', 'class', 'charakter' => '12',
            '13', 'disciples of war', 'ファイター', 'disciples de la guerre', 'krieger' => '13',
            '14', 'disciples of magic', 'ソーサラー', 'disciples de la magie', 'magier' => '15',
            '15', 'disciples of the hand', 'クラフター', 'disciples de la main', 'handwerker' => '15',
            '16', 'disciples of the land', 'ギャザラー', 'disciples de la terre', 'sammler' => '16',
            '17', 'commendation', 'mip', 'honneurs', 'ehrungen' => '17',
            '18', 'gold saucer', 'ゴールドソーサー' => '18',
            #Japanese and French use duplicate name here, thus not supported
            '19', 'items', 'gegenstände' => '19',
            '20', 'currency', '通貨', 'devises', 'vermögen' => '20',
            '21', 'desynthesis', '分解', 'recyclage', 'verwertung' => '21',
            '22', 'collectables', '蒐集品', 'objets collectionnables', 'sammlerstücke' => '22',
            '23', 'materia', 'マテリア', 'matérias' => '23',
            '24', 'carpenter', '木工師', 'menuisier', 'zimmerer' => '24',
            '25', 'blacksmith', '鍛冶師', 'forgeron', 'grobschmied' => '25',
            '26', 'armorer', '甲冑師', 'armurier', 'plattner' => '26',
            '27', 'goldsmith', '彫金師', 'orfèvre', 'goldschmied' => '27',
            '28', 'leatherworker', '革細工師', 'tanneur', 'gerber' => '28',
            '29', 'weaver', '裁縫師', 'couturier', 'weber' => '29',
            '30', 'alchemist', '錬金術師', 'alchimiste' => '30',
            '31', 'culinarian', '調理師', 'cuisinier', 'gourmet' => '31',
            '32', 'miner', '採掘師', 'mineur', 'minenarbeiter' => '32',
            '33', 'botanist', '園芸師', 'botaniste', 'gärtner' => '33',
            '34', 'fisher', '漁師', 'pêcheur', 'fischer' => '34',
            #Japanese and French use duplicate name here, thus not supported
            '35', 'quests', 'aufträge' => '35',
            '36', 'levequests', 'リーヴ', 'mandats', 'freibriefe' => '36',
            '37', 'beast tribe quests', '蛮族クエスト', 'quêtes tribales', 'wilde stämme' => '37',
            '38', 'seasonal events', 'シーズナルイベント', 'événements saisonniers', 'saisonale ereignisse' => '38',
            '39', 'sightseeing log', '探検手帳', 'carnet d\'exploration', 'eorzea incognita' => '39',
            '40', 'la noscea', 'ラノシア', 'noscea' => '40',
            '41', 'the black shroud', '黒衣森', 'forêt de sombrelinceul', 'finsterwald' => '41',
            '42', 'thanalan', 'ザナラーン' => '42',
            '43', 'coerthas', 'クルザス' => '43',
            '44', 'mor dhona', 'モードゥナ' => '44',
            '45', 'abalathia\'s spine', 'アバラシア', 'abalathia' => '45',
            '46', 'dravania', 'ドラヴァニア' => '46',
            '47', 'gyr abania', 'ギラバニア' => '47',
            '48', 'othard', 'オサード' => '48',
            '49', 'duty', 'コンテンツ', 'instances', 'inhalte' => '49',
            #Japanese and French use duplicate name here, thus not supported
            '50', 'grand company', 'staatliche gesellschaften' => '50',
            '51', 'maelstrom', '黒渦団', 'mahlstrom' => '51',
            '52', 'order of the twin adder', '双蛇党', 'ordre des deux vipères', 'bruderschaft' => '52',
            '53', 'immortal flames', '不滅隊', 'immortels', 'legion' => '53',
            #Legacy categories. Due duplicate names only IDs are supported for them
            '54' => '54',
            '55' => '55',
            '56' => '56',
            '57' => '57',
            '58' => '58',
            '59' => '59',
            '60' => '60',
            '61' => '61',
            default => '1',
        };
    }
    
    public function getFeastRankId(string $rank): string
    {
        return match(strtolower($rank)) {
            '1', 'bronze', 'verteidiger' => '0',
            '2', 'silver', 'argent', 'silber' => '1',
            '3', 'gold', 'or' => '2',
            '4', 'platinum', 'platine', 'platin' => '3',
            '5', 'diamond', 'diamant' => '4',
            default => 'all',
        };
    }
    
    public function getSearchRolesId(string $role): string
    {
        return match(strtolower($role)) {
            '0', 'tank', 'tanks', 'verteidiger' => '0',
            '1', 'healer', 'soigneurs', 'heiler' => '1',
            '2', 'dps', 'angreifer' => '2',
            '3', 'crafter', 'artisans', 'handwerker' => '3',
            '4', 'gatherer', 'récolteurs', 'sammler' => '4',
            '-1', 'not specified', '設定なし', 'indéterminé', 'keine angabe' => '-1',
            default => '',
        };
    }
    
    public function getSearchActivitiesId(string $act): string
    {
        return match(strtolower($act)) {
            '0', 'role-playing', 'ロールプレイ', 'jeu de rôle', 'rollenspiel' => '0',
            '1', 'leveling', 'レベリング', 'gain d\'expérience', 'stufenaufstieg' => '1',
            '2', 'casual', 'カジュアル', 'jeu décontracté', 'gelegenheitsspieler' => '2',
            '3', 'hardcore', 'ハードコア', 'jeu intense' => '3',
            '4', 'Dungeons', 'ダンジョン', 'donjons' => '4',
            '5', 'guildhests', 'ギルドオーダー', 'opérations de guilde', 'gildengeheiße' => '5',
            '6', 'trials', '討伐・討滅戦', 'défis', 'prüfungen' => '6',
            '7', 'raids', 'レイド' => '7',
            '8', 'pvp', 'jcj' => '8',
            '-1', 'not specified', '設定なし', 'indéterminé', 'keine angabe' => '-1',
            default => '',
        };
    }
    
    public function getSearchHouseId(string $house): string
    {
        return match(strtolower($house)) {
            '2', 'estate built', '所有あり', 'logement construit', 'besitzt unterkunft' => '2',
            '1', 'plot only', '土地のみ', 'terrain seul', 'nur grundstück' => '1',
            '0', 'no estate or plot', '所有なし', 'sans logement ni terrain', 'besitzt keine unterkunft' => '0',
            default => '',
        };
    }
    
    public function getSearchJoinId(string $join): string
    {
        return match(strtolower($join)) {
            '1', 'open', '申請可', 'candidatures acceptées', 'nimmt gesuche an' => '1',
            '0', 'closed', '申請不可', 'candidatures refusées', 'nimmt keine gesuche an' => '0',
            default => '',
        };
    }
    
    public function getSearchActiveTimeId(string $active): string
    {
        return match(strtolower($active)) {
            '1', 'weekdays', 'weekdays only', '平日のみ', 'en semaine seulement', 'nur wochentags' => '1',
            '2', 'weekends', 'weekends only', '週末のみ', 'le week-end seulement', 'nur Wochenende' => '2',
            '3', 'always', '平日/週末', 'toute la semaine', 'jeden Tag' => '3',
            default => '',
        };
    }
    
    public function matchesCount(int $count): string
    {
        if ($count >= 1 && $count <= 29) {
            $count = '1';
        } elseif ($count >= 30 && $count <= 49) {
            $count = '2';
        } elseif ($count >= 50) {
            $count = '3';
        } else {
            $count = '';
        }
        return $count;
    }
    
    public function pvpRank(int $count): string
    {
        if ($count >= 1 && $count <= 10) {
            $count = '1';
        } elseif ($count >= 11 && $count <= 20) {
            $count = '2';
        } elseif ($count >= 21 && $count <= 30) {
            $count = '3';
        } elseif ($count >= 31) {
            $count = '4';
        } else {
            $count = '';
        }
        return $count;
    }
    
    public function membersCount(int $count): string
    {
        if ($count >= 1 && $count <= 10) {
            $count = '1-10';
        } elseif ($count >= 11 && $count <= 30) {
            $count = '11-30';
        } elseif ($count >= 31 && $count <= 50) {
            $count = '31-50';
        } elseif ($count >= 51) {
            $count = '51-';
        } else {
            $count = '';
        }
        return $count;
    }
    
    public function languageConvert(string $lang): string
    {
        if (!empty($lang)) {
            if (!in_array($lang, self::langallowed)) {
                $lang = 'na';
            }
            if (in_array($lang, ['jp', 'ja'])) {$lang = 'ja';}
            if (in_array($lang, ['na', 'eu'])) {$lang = 'en';}
        } else {
            $lang = '';
        }
        return $lang;
    }
    
    public function getSearchOrderId(string $order): string
    {
	    return match(strtolower($order)) {
	        '1', 'charaz', 'fcaz', 'lsaz', 'pvpaz' => '1',
            '2', 'charza', 'fcza', 'lsza', 'pvpza' => '2',
            '3', 'worldaz', 'fcmembersza', 'lsmembersza' => '3',
            '4', 'worldza', 'fcmembersaz', 'lsmembersaz' => '4',
            '5', 'levelza', 'fcfoundza' => '5',
            '6', 'levelaz', 'fcfoundaz' => '6',
            default => '',
	    };
    }
    
    public function getSearchGCId(string $gc): string
    {
	    return match(strtolower($gc)) {
	        '1', 'maelstrom', '黒渦団', 'le maelstrom', 'mahlstrom' => '1',
            '2', 'order of the twin adder', '双蛇党 ', 'l\'ordre des deux vipères', 'bruderschaft der morgenviper' => '2',
            '3', 'immortal flames', '不滅隊', 'les immortels', 'legion der unsterblichen' => '3',
            '0', 'no affiliation', '所属なし', 'sans allégeance', 'keine gesellschaft' => '0',
            default => '',
	    };
    }
    
    public function getSearchClanId(string $clan): string
    {
	    return match(strtolower($clan)) {
	        'hyur', 'ヒューラン', 'hyuran' => 'race_1',
            'midlander', 'ミッドランダー', 'hyurois', 'wiesländer' => 'tribe_1',
            'highlander', 'ハイランダー', 'hyurgoth', 'hochländer' => 'tribe_2',
            'elezen', 'エレゼン', 'élézen' => 'race_2',
            'wildwood', 'フォレスター', 'sylvestre', 'erlschatten' => 'tribe_3',
            'duskwight', 'シェーダー', 'crépusculaire', 'dunkelalb' => 'tribe_4',
            'lalafell', 'ララフェル' => 'race_3',
            'plainsfolk', 'プレーンフォーク', 'peuple des plaines', 'halmling' => 'tribe_5',
            'dunesfolk', 'デューンフォーク', 'peuple des dunes', 'sandling' => 'tribe_6',
            'miqo\'te', 'ミコッテ' => 'race_4',
            'seeker of the sun', 'サンシーカー', 'tribu du soleil', 'goldtatze' => 'tribe_7',
            'keeper of the moon', 'ムーンキーパー', 'tribu de la lune', 'mondstreuner' => 'tribe_8',
            'roegadyn', 'ルガディン' => 'race_5',
            'sea wolf', 'ゼーヴォルフ', 'clan de la mer', 'seewolf' => 'tribe_9',
            'hellsguard', 'ローエンガルデ', 'clan du feu', 'lohengarde' => 'tribe_10',
            'au ra', 'アウラ', 'ao ra' => 'race_6',
            'raen', 'アウラ・レン' => 'tribe_11',
            'xaela', 'アウラ・ゼラ' => 'tribe_12',
            'hrothgar', 'ロスガル' => 'race_7',
            'helions', 'ヘリオン', 'hélion', 'helion' => 'tribe_13',
            'the lost', 'ロスト', 'éloigné', 'losgesagter' => 'tribe_14',
            'viera', 'ヴィエラ', 'viéra' => 'race_8',
            'rava', 'ラヴァ・ヴィエラ' => 'tribe_15',
            'veena', 'ヴィナ・ヴィエラ' => 'tribe_16',
            default => '',
	    };
    }
    
    public function classToJob(string $class): string
    {
        return match(strtolower($class)) {
	        'gladiator', 'gladiateur' => 'Paladin',
            'marauder' => 'Warrior',
            'conjurer' => 'White Mage',
            'pugilist' => 'Monk',
            'lancer', 'Pikenier' => 'Dragoon',
            'archer' => 'Bard',
            'rogue', 'surineur', 'schurke' => 'Ninja',
            'thaumaturge' => 'Black Mage',
            'arcanist' => 'Summoner',
            '剣術士' => 'ナイト',
            '斧術士' => '戦士',
            '幻術士' => '白魔道士',
            '格闘士' => 'モンク',
            '槍術士' => '竜騎士',
            '弓術士' => '吟遊詩人',
            '双剣士' => '忍者',
            '呪術士' => '黒魔道士',
            '巴術士' => '召喚士',
            'maraudeur' => 'Guerrier',
            'élémentaliste' => 'Mage blanc',
            'pugiliste' => 'Moine',
            'maître d\'hast' => 'Chevalier dragon',
            'archer', 'waldläufer' => 'Barde',
            'occultiste' => 'Mage noir',
            'arcaniste' => 'Invocateur',
            'marodeur' => 'Krieger',
            'druide' => 'Weißmagier',
            'faustkämpfer' => 'Mönch',
            'thaumaturg' => 'Schwarzmagier',
            'hermetiker' => 'Beschwörer',
            default => $class,
	    };
    }
    
    public function getSearchClassId(string $classname): string
    {
	    return match(strtolower($classname)) {
	        'tnk' => '_job_TANK&classjob=_class_TANK',
            'hlr' => '_job_HEALER&classjob=_class_HEALER',
            'dps' => '_job_DPS&classjob=_class_DPS',
            'doh' => '_class_CRAFTER',
            'dol' => '_class_GATHERER',
            'gla' => '1',
            'pld' => '19',
            'mar' => '3',
            'war' => '21',
            'drk' => '32',
            'cnj' => '6',
            'whm' => '24',
            'sch' => '28',
            'ast' => '33',
            'mnk' => '20',
            'drg' => '22',
            'nin' => '30',
            'brd' => '23',
            'mch' => '31',
            'blm' => '25',
            'smn' => '27',
            'sam' => '34',
            'rdm' => '35',
            'pug' => '2',
            'lnc' => '4',
            'rog' => '29',
            'arc' => '5',
            'thm' => '7',
            'acn' => '26',
            'crp' => '8',
            'bsm' => '9',
            'arm' => '10',
            'gsm' => '11',
            'ltw' => '12',
            'wvr' => '13',
            'alc' => '14',
            'cul' => '15',
            'min' => '16',
            'btn' => '17',
            'fsh' => '18',
            'gnb' => '37',
            'dnc' => '38',
            default => '',
	    };
    }

    public function getDeepDungeonClassId(string $classname): string
    {
        return match(strtolower($classname)) {
	        'gla', 'pld' => '125bf9c1198a3a148377efea9c167726d58fa1a5',
            'mar', 'war' => '741ae8622fa496b4f98b040ff03f623bf46d790f',
            'drk' => 'c31f30f41ab1562461262daa74b4d374e633a790',
            'cnj', 'whm' => '56d60f8dbf527ab9a4f96f2906f044b33e7bd349',
            'sch' => '56f91364620add6b8e53c80f0d5d315a246c3b94',
            'ast' => 'eb7fb1a2664ede39d2d921e0171a20fa7e57eb2b',
            'mnk', 'pug' => '46fcce8b2166c8afb1d76f9e1fa3400427c73203',
            'drg', 'lnc' => 'b16807bd2ef49bd57893c56727a8f61cbaeae008',
            'nin', 'rog' => 'e8f417ab2afdd9a1e608cb08f4c7a1ae3fe4a441',
            'brd', 'arc' => 'f50dbaf7512c54b426b991445ff06a6697f45d2a',
            'mch' => '773aae6e524e9a497fe3b09c7084af165bef434d',
            'blm', 'thm' => 'f28896f2b4a22b014e3bb85a7f20041452319ff2',
            'acn', 'smn' => '9ef51b0f36842b9566f40c5e3de2c55a672e4607',
            'sam' => '7c3485028121b84720df20de7772371d279d097d',
            'rdm' => '55a98ea6cf180332222184e9fb788a7941a03ec3',
            'gnb' => '1c29ab32bcd60f4ac37827066709fa17c872edca',
            'dnc' => 'baa255d6ec667f5a88920d8968e86a41261d8576',
            default => '',
	    };
    }
    
    public function getGuardianId(string $guardian): string
    {
        return match(strtolower($guardian)) {
	        'althyk, the keeper', 'アルジク', 'althyk, le contemplateur', 'althyk - der hüter', 'althyk' => '1',
            'azeyma, the warden', 'アーゼマ', 'azeyma, la gardienne', 'azeyma - die aufseherin', 'azeyma' => '2',
            'byregot, the builder', 'ビエルゴ', 'byregot, l\'artisan', 'byregot - der erbauer', 'byregot' => '3',
            'halone, the fury', 'ハルオーネ', 'halone, la conquérante', 'halone - die furie', 'halone' => '4',
            'llymlaen, the navigator', 'リムレーン', 'llymlaen, la navigatrice', 'llymlaen - die lotsin', 'llymlaen' => '5',
            'menphina, the lover', 'メネフィナ', 'menphina, la bien-aimante', 'menphina - die liebende', 'menphina' => '6',
            'nald\'thal, the traders', 'ナルザル', 'nald\'thal, les marchands', 'nald\'thal - die kaufleute', 'nald\'thal' => '7',
            'nophica, the matron', 'ノフィカ', 'nophica, la mère', 'nophica - die mutter', 'nophica' => '8',
            'nymeia, the spinner', 'ニメーヤ', 'nymeia, la fileuse', 'nymeia - die norne', 'nymeia' => '9',
            'oschon, the wanderer', 'オシュオン', 'oschon, le vagabond', 'oschon - der wanderer', 'oschon' => '10',
            'rhalgr, the destroyer', 'ラールガー', 'rhalgr, le destructeur', 'rhalgr - der zerstörer', 'rhalgr' => '11',
            'thaliak, the scholar', 'サリャク', 'thaliak, l\'érudit', 'thaliak - der forscher', 'thaliak' => '12',
            default => '',
	    };
    }
    
    public function getCityId(string $city): string
    {
        #IDs are based on what I have in my own database, there is no other meaning behind them
        return match(strtolower($city)) {
	        'gridania', 'the lavender beds', 'グリダニア', 'ラベンダーベッド', 'lavandière', 'lavendelbeete' => '2',
            'limsa lominsa', 'mist', 'リムサ・ロミンサ', 'ミスト・ヴィレッジ', 'brumée', 'dorf des Nebels' => '4',
            'ul\'dah', 'the goblet', 'ウルダハ', 'la Coupe', 'ゴブレットビュート', 'kelchkuppe' => '5',
            'kugane', 'クガネ', 'shirogane', 'シロガネ' => '7',
            default => '',
	    };
    }
    
    public function getCityName(int $id = 1, string $lang = 'en'): string
    {
        if (!in_array(strtolower($lang), ['na', 'jp', 'ja', 'eu', 'fr', 'de', 'en'])) {
            throw new \UnexpectedValueException('Unsupported language \''.$lang.'\' requested for City name');
        }
        switch($id) {
            case 2:
                switch(strtolower($lang)) {
                    case 'na':
                    case 'eu':
                    case 'en':
                    case 'fr':
                    case 'de':
                        $name = 'Gridania'; break;
                    case 'jp':
                    case 'ja':
                        $name = 'グリダニア'; break;
                }
                break;
            case 4:
                switch(strtolower($lang)) {
                    case 'na':
                    case 'eu':
                    case 'en':
                    case 'fr':
                    case 'de':
                        $name = 'Limsa Lominsa'; break;
                    case 'jp':
                    case 'ja':
                        $name = 'リムサ・ロミンサ'; break;
                }
                break;
            case 5:
                switch(strtolower($lang)) {
                    case 'na':
                    case 'eu':
                    case 'en':
                    case 'fr':
                    case 'de':
                        $name = 'Ul\'dah'; break;
                    case 'jp':
                    case 'ja':
                        $name = 'ウルダハ'; break;
                }
                break;
            case 7:
                switch(strtolower($lang)) {
                    case 'na':
                    case 'eu':
                    case 'en':
                    case 'fr':
                    case 'de':
                        $name = 'Kugane'; break;
                    case 'jp':
                    case 'ja':
                        $name = 'クガネ'; break;
                }
                break;
            default:
                $name = '';
        }
        return $name;
    }
    
    public function getGrandCompanyId(string $gc): string
    {
        return match(strtolower($gc)) {
	        'maelstrom', '黒渦団', 'mahlstrom' => '1',
            'order of the twin adder', '双蛇党', 'ordre des deux vipères', 'bruderschaft' => '2',
            'immortal flames', '不滅隊', 'immortels', 'legion' => '3',
            default => '',
	    };
    }
    
    public function getGrandCompanyName(int $id = 1, string $lang = 'en'): string
    {
        if (!in_array(strtolower($lang), ['na', 'jp', 'ja', 'eu', 'fr', 'de', 'en'])) {
            throw new \UnexpectedValueException('Unsupported language \''.$lang.'\' requested for Grand Company name');
        }
        switch($id) {
            case 1:
                switch(strtolower($lang)) {
                    case 'na':
                    case 'eu':
                    case 'en':
                    case 'fr':
                        $name = 'Maelstrom'; break;
                    case 'jp':
                    case 'ja':
                        $name = '黒渦団'; break;
                    case 'de':
                        $name = 'Mahlstrom'; break;
                }
                break;
            case 2:
                switch(strtolower($lang)) {
                    case 'na':
                    case 'eu':
                    case 'en':
                        $name = 'Order of the Twin Adder'; break;
                    case 'jp':
                    case 'ja':
                        $name = '双蛇党'; break;
                    case 'fr':
                        $name = 'Ordre des Deux Vipères'; break;
                    case 'de':
                        $name = 'Bruderschaft'; break;
                }
                break;
            case 3:
                switch(strtolower($lang)) {
                    case 'na':
                    case 'eu':
                    case 'en':
                        $name = 'Immortal Flames'; break;
                    case 'jp':
                    case 'ja':
                        $name = '不滅隊'; break;
                    case 'fr':
                        $name = 'Immortels'; break;
                    case 'de':
                        $name = 'Legion'; break;
                }
                break;
            default:
                $name = '';
        }
        return $name;
    }
    
    #Returns guardian's color
    public function colorGuardians(string $guardian): string
    {
        return match($this->getGuardianId($guardian)) {
	        '1' => '#776c3e',
            '2' => '#6b3e3d',
            '3' => '#4e3a61',
            '4' => '#536f7b',
            '5' => '#709959',
            '6' => '#7196a6',
            '7' => '#8c4f4f',
            '8' => '#a69453',
            '9' => '#465970',
            '10' => '#566d45',
            '11' => '#6b4e88',
            '12' => '#506b8c',
            default => '',
	    };
    }
    
    #Returns city's color
    public function colorCities(string $city): string
    {
        return match($this->getCityId($city)) {
	        '2' => '#ffb200',
            '4' => '#bd0421',
            '5' => '#080300',
            '7' => '#8d5810',
            default => '',
	    };
    }
    
    #Returns grand company's color
    public function colorGC(string $company): string
    {
        return match($this->getGrandCompanyId($company)) {
	        '1' => '#c22e46',
            '2' => '#e8a01f',
            '3' => '#414849',
            default => '',
	    };
    }
    
    public function memory($bytes): string
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$unit[$i];
    }
}
?>
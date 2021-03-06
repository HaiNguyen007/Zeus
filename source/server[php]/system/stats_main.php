<?php if(!defined('__CP__'))die();

define('COUNTRYLIST_WIDTH', 200);  //REReSЂReRЅR ° RєRѕR "RѕRЅRєRe SЃS, SЂR ° RЅ.
define('STAT_WIDTH',        '1%'); //REReSЂReRЅR ° RєRѕR "RѕRЅRєRe SЃS, P ° C ReSЃS, ReRєRe.

//REReSЂReRЅR ° RєRѕR "RѕRЅRєRe SЃS, P ° C ReSЃS, ReRєRe.
if(isset($_GET['reset_newbots']) && !empty($userData['r_stats_main_reset']))
{
  $query = 'UPDATE `botnet_list` SET `flag_new`=0';
  if(!empty($_GET['botnet']))$query .= " WHERE `botnet`='".addslashes($_GET['botnet'])."'";
  mysqlQueryEx('botnet_list', $query);

  if(empty($_GET['botnet']))header('Location: '.QUERY_STRING);
  else header('Location: '.QUERY_STRING.'&botnet='.urlencode($_GET['botnet']));

  die();
}

//RўRμRєSѓS Pepsi № ‰ P ± RѕS, RЅRμS.
define('CURRENT_BOTNET', (!empty($_GET['botnet']) ? $_GET['botnet'] : ''));

////////////////////////////////////////////////// / / ///////////////////////////////////////////////
// R'S <RІRѕRґ RѕR ± C ‰ RμR number ReRЅS "RѕSЂRјR ° C † ReRe.
////////////////////////////////////////////////// / / ///////////////////////////////////////////////

$i = 0;
$output = str_replace('{WIDTH}', (COUNTRYLIST_WIDTH * 2).'px', THEME_LIST_BEGIN).
          str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, LNG_STATS_TOTAL_INFO), THEME_LIST_TITLE);

//RџRѕRґSЃS ‡ RμS, RєRѕR "Republic ‡ RμSЃS, RІR ° RѕS, C ‡ RμS, RѕRІ RІ P ± P ° P · Rμ RґR ° RЅRЅS <C ...
if(!empty($userData['r_reports_db']))
{
  $reportsList  = listReportTables($config['mysql_db']);
  $reportsCount = 0;
  foreach($reportsList as $table)if(($mt = @mysql_fetch_row(mysqlQueryEx($table, "SELECT COUNT(*) FROM `{$table}`"))))$reportsCount += $mt[0];
  $output .= 
  THEME_LIST_ROW_BEGIN.
    str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_REPORTS),              $i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1).
    str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, numberFormatAsInt($reportsCount)), $i % 2 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1).
  THEME_LIST_ROW_END;
  
  $i++;
}

$output .= getBotnetStats('', $i).THEME_LIST_END.THEME_STRING_NEWLINE;

////////////////////////////////////////////////// / / ///////////////////////////////////////////////
// R'S <RІRѕRґ ReRЅS "RѕSЂRјR ° C † ReRe RѕR ± C RμRєSѓS RμRј ‰ P ± RѕS, RЅRμS, Rμ.
////////////////////////////////////////////////// / / ///////////////////////////////////////////////

$actionList = '';
if(!empty($userData['r_stats_main_reset']))
{
  $actionList = str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, LNG_STATS_BOTNET_ACTIONS.THEME_STRING_SPACE.
                            str_replace(array('{TEXT}',     '{JS_EVENTS}'),
                                        array(LNG_STATS_RESET_NEWBOTS, ' onclick="if(confirm(\''.addJsSlashes(LNG_STATS_RESET_NEWBOTS_Q).'\'))window.location=\''.QUERY_STRING_HTML.'&amp;reset_newbots&amp;botnet='.addJsSlashes(urlencode(CURRENT_BOTNET)).'\';"'),
                                        THEME_DIALOG_ITEM_ACTION
                                       )),
                            THEME_DIALOG_TITLE);
}

$output .= 
str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN).
str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, LNG_STATS_BOTNET.THEME_STRING_SPACE.botnetsToListBox(CURRENT_BOTNET, '')), THEME_DIALOG_TITLE).
$actionList;

//RЎR ± RѕSЂ SЃS, P ° C ReSЃS, ReRєRe RґR "SЏ RєRѕRЅRєSЂRμS, RЅRѕRіRѕ P ± RѕS, RЅRμS, P °.
if(CURRENT_BOTNET != '')
{
  $output .=
    THEME_DIALOG_ROW_BEGIN.
      str_replace('{COLUMNS_COUNT}', 2, THEME_DIALOG_ITEM_CHILD_BEGIN).
        str_replace('{WIDTH}', '100%', THEME_LIST_BEGIN).
          getBotnetStats(CURRENT_BOTNET, 0).
        THEME_LIST_END.
      THEME_DIALOG_ITEM_CHILD_END.
    THEME_DIALOG_ROW_END;
}

//R'S <RІRѕRґ SЃRїReSЃRєR ° SЃS, SЂR ° RЅ.
$commonQuery = ((CURRENT_BOTNET != '') ? ' AND botnet=\''.addslashes(CURRENT_BOTNET).'\'' : '');
$output .= 
THEME_DIALOG_ROW_BEGIN.  
  str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN).
    listCountries(LNG_STATS_COLUMN_NEWBOTS, '`flag_new`=1'.$commonQuery).
  THEME_DIALOG_ITEM_CHILD_END.
  str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN).
    listCountries(LNG_STATS_COLUMN_ONLINEBOTS, '`rtime_last`>=\''.(CURRENT_TIME - $config['botnet_timeout']).'\''.$commonQuery).
  THEME_DIALOG_ITEM_CHILD_END.
THEME_DIALOG_ROW_END.
THEME_DIALOG_END;

ThemeBegin(LNG_STATS, 0, 0, 0);
echo $output;
ThemeEnd();

////////////////////////////////////////////////// / / ///////////////////////////////////////////////
// P ¤ † SѓRЅRєS ReRe.
////////////////////////////////////////////////// / / ///////////////////////////////////////////////

/*
  Создание информации по ботнету.
  
  IN $botnet - string, название ботнета.
  IN  $i     - int, счетчик номера строки.
  
  Return    - string, часть таблицы.
*/
function getBotnetStats($botnet, $i)
{
  $query1 = '';
  $query2 = '';
  
  if($botnet != '')
  {
    $botnet = addslashes($botnet);
    $query1 = " WHERE `botnet`='{$botnet}'";
    $query2 = " AND `botnet`='{$botnet}'";
  }
  
  //RљRѕR "Republic ‡ RμS, SЃRІRѕ P ± RѕS, RѕRІ, Fe RІSЂRμRјSЏ RїRμSЂRІRѕRіRѕ RѕS, C ‡ RμS, P °.
  $tmp = htmlEntitiesEx(($mt = @mysql_fetch_row(mysqlQueryEx('botnet_list', "SELECT MIN(`rtime_first`), COUNT(`bot_id`), MIN(`bot_version`), MAX(`bot_version`) FROM `botnet_list`{$query1}"))) && $mt[0] > 0 ? gmdate(LNG_FORMAT_DT, $mt[0]) : '-');
  $data =
  THEME_LIST_ROW_BEGIN.
    str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_FIRST_BOT), $i == 0 ? THEME_LIST_ITEM_LTEXT_U1 : THEME_LIST_ITEM_LTEXT_U2).
    str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, $tmp),            $i == 0 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2). //RџSѓSЃS, SЊ P ± SѓRґRμS, num.
  THEME_LIST_ROW_END.
  THEME_LIST_ROW_BEGIN.
    str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_BOTS),          $i == 0 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1).
    str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, numberFormatAsInt($mt[1])), $i == 0 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1).
  THEME_LIST_ROW_END;

  $totalBots  = $mt[1];
  $minVersion = $mt[2];
  $maxVersion = $mt[3];

  //RљRѕR "Republic ‡ RμS, SЃRІRѕ P ± RѕS, RѕRІ P ° RєS, ReRІRЅS <C ... P · P ° RїRѕSЃR" RμRґRЅReRe 24 C ‡ P ° SЃR °.
  $tmp = ($mt = @mysql_fetch_row(mysqlQueryEx('botnet_list', 'SELECT COUNT(`bot_id`) FROM `botnet_list` WHERE `rtime_last`>='.(CURRENT_TIME - 86400).$query2))) ? $mt[0] : 0;
  $data .= 
  THEME_LIST_ROW_BEGIN.
    str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_BOTS24),                                                                                       $i == 0 ? THEME_LIST_ITEM_LTEXT_U1 : THEME_LIST_ITEM_LTEXT_U2).
    str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, ($totalBots > 0 ? numberFormatAsFloat(($tmp * 100) / $totalBots, 2) : 0).'% -  '.numberFormatAsInt($tmp)), $i == 0 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2).
  THEME_LIST_ROW_END;
 
  //RњR RєSЃReRјR ° ° p "° SЊRЅR SЏ Re RјReRЅReRјR ° p" ° SЊRЅR SЏ RІRμSЂSЃReSЏ P ± RѕS, P °.
  $data .= 
  THEME_LIST_ROW_BEGIN.
    str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_MIN_VERSION),   $i == 0 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1).
    str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, intToVersion($minVersion)), $i == 0 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1).
  THEME_LIST_ROW_END.
  THEME_LIST_ROW_BEGIN.
    str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_MAX_VERSION),   $i == 0 ? THEME_LIST_ITEM_LTEXT_U1 : THEME_LIST_ITEM_LTEXT_U2).
    str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, intToVersion($maxVersion)), $i == 0 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2).
  THEME_LIST_ROW_END;
  
  return $data;
}

/*  RЎRѕR · RґR RЅReRμ ° C, P ° P ± P "Republic † C <SЃRѕ SЃRїReSЃRѕRј SЃS, SЂR ° RЅ.
  
  IN $ name - string, RЅR ° F · RІR RЅReRμ ° C, P ° P ± P "Republic † C <.
  IN $ query - string, RґRѕRїRѕR "RЅReS, RμR" SЊRЅS <Rμ SѓSЃR "RѕRІReSЏ RґR" SЏ SQL-P · RїSЂRѕSЃR P ° °.
  
  Return - string, C, P ° P ± P "Republic † P °.*/
function listCountries($name, $query)
{
  $data = str_replace('{WIDTH}', COUNTRYLIST_WIDTH.'px', THEME_LIST_BEGIN);

  $r = mysqlQueryEx('botnet_list', 'SELECT `country`, COUNT(`country`) FROM `botnet_list` WHERE '.$query.' GROUP BY BINARY `country` ORDER BY COUNT(`country`) DESC, `country` ASC');
  if($r && @mysql_affected_rows() > 0)
  {
    //RЎRѕSЃS, P ° RІR "SЏRμRј SЃRїReSЃRѕRє.
    $count = 0;
    $i     = 0;
    $list  = '';

    while(($m = mysql_fetch_row($r)))
    {
      $list .=
      THEME_LIST_ROW_BEGIN.
        str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($m[0])),   $i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1).
        str_replace(array('{WIDTH}', '{TEXT}'), array('8em', numberFormatAsInt($m[1])), $i % 2 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1).
      THEME_LIST_ROW_END;

      $count += $m[1];
      $i++;
    }

    //P-P ° RіRѕR "RѕRІRѕRє
    $data .= str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, sprintf($name, numberFormatAsInt($count))), THEME_LIST_TITLE).$list;
  }
  //RћS € Pepsi ± RєR °.
  else
  {
    $data .= 
    str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, sprintf($name, 0)), THEME_LIST_TITLE).
    THEME_LIST_ROW_BEGIN.
      str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, $r ? LNG_STATS_COUNTRYLIST_EMPTY : mysqlErrorEx()), THEME_LIST_ITEM_EMPTY_1).
    THEME_LIST_ROW_END;
  }
  
  return $data.THEME_LIST_END;
}
?>
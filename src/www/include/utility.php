<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/utility.php
 * 
 * collection of settings and utility functions needed by several scripts
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * display a title and optionally some help in a modal window
 * 
 * @param string $title the title to display
 * @param string $helpText
 * 
 * @return NULL nothing
 */
function titleAndHelp ( $title,
                        $helpText = "" )
{
  global $__;

  echo "<div class=\"row\"><h2>$title ";

  if ( $helpText != "" )
  {
    echo "<a href=\"#", $__[ "include/utility" ] [ "ids" ] [ "helpModal" ], "\" data-toggle=\"modal\"><small><span class=\"text-success\"><i class=\"fa fa-question-circle\"></i></span></small></a>";
  }

  echo "</h2></div>";

  if ( $helpText != "" )
  {
    echo "<div class=\"modal fade\" id=\"", $__[ "include/utility" ] [ "ids" ] [ "helpModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><div class=\"alert alert-success\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa ", $__[ "include/utility" ] [ "values" ][ "helpSign" ], " fa-2x\"></i></div><div class=\"msgText\"><strong>$title</strong></div></div></div><div class=\"modal-body\">$helpText</div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-success\" data-dismiss=\"modal\">", _ ( "Schließen" ), "</button></div></div></div></div>";
  }
}


/**
 * escape string for HTML view
 * 
 * @param string $text text to html escape
 * @return string escaped input
 */
function htmlSave ( $text )
{
  return preg_replace ( "/\r?\n\r?/",
                        "<br>",
                        htmlspecialchars ( $text ) );
}


/**
 * escape string for JS integration
 * 
 * @param string $text text to JS escape
 * @param boolean $doHtmlEscape do an html escape as well
 * 
 * @return string escaped input
 */
function jsSave ( $text,
                  $doHtmlEscape = true )
{
  return preg_replace ( "/\r?\n\r?/",
                        "\\n",
                        str_replace ( "'",
                                      "\\x27",
                                      str_replace ( "\"",
                                                    "\\x22",
                                                    $doHtmlEscape ? htmlspecialchars ( $text ) : $text ) ) );
}


/**
 * show message
 * 
 * @param string $message text to show
 * @param string $class bootstrap contextual class name
 * @param string $icon icon to show
 * @param boolean $doEcho true if message should be shown rightaway, false if html should be returned
 * @return NULL/string if not $doEcho return html code
 */
function showMessage ( $message,
                       $class,
                       $icon,
                       $doEcho )
{
  $htmlResult = "<div class=\"alert alert-$class alert-sm\" role=\"alert\"><div class=\"msgIcon\"><i class=\"$icon\"></i></div><div class=\"msgText\"><strong>$message</strong></div></div>";

  if ( $doEcho )
  {
    echo $htmlResult;
  }
  else
  {
    return $htmlResult;
  }
}


/**
 * show informational message
 */
function showInfoMessage ( $message,
                           $doEcho = true )
{
  return showMessage ( $message,
                       "info",
                       "fa fa-info fa-2x",
                       $doEcho );
}


/**
 * show progress/execution/waiting related message (hourglass icon)
 */
function showWaitMessage ( $message,
                           $doEcho = true )
{
  return showMessage ( $message,
                       "info",
                       "fa fa-hourglass-start fa-2x flash",
                       $doEcho );
}


/**
 * show success message
 */
function showSuccessMessage ( $message,
                              $doEcho = true )
{
  return showMessage ( $message,
                       "success",
                       "fa fa-check fa-2x",
                       $doEcho );
}


/**
 * show alert message
 */
function showAlertMessage ( $message,
                            $doEcho = true )
{
  return showMessage ( $message,
                       "warning",
                       "fa fa-exclamation-triangle fa-2x",
                       $doEcho );
}


/**
 * show error message
 */
function showErrorMessage ( $message,
                            $doEcho = true )
{
  return showMessage ( $message,
                       "danger",
                       "fa fa-bomb fa-2x",
                       $doEcho );
}


/**
 * show extra small iconized link button
 * 
 * @param string $icon icon to show on button
 * @param string $url url to open on button click
 * @param string $title hover title to display
 * @param string $class bootstrap button class
 * @param string $target frame to open url in
 * @return string html of button
 */
function showIconButton ( $icon,
                          $url,
                          $title = "",
                          $class = "",
                          $target = "" )
{
  global $my_name;

  return "<a class=\"btn btn-" . ($class = "" ? "default" : $class) . " btn-xs\" href=\"$url\"" . ($title == "" ? "" : " title=\"$title\"") . " target=\"$my_name$target\"><i class=\"$icon\"></i></a>";
}


/**
 * show button with eye icon
 */
function showViewButton ( $url,
                          $title = NULL,
                          $target = "" )
{
  /*
   * set i18'd default value
   */
  if ( is_null ( $title ) )
  {
    $title = _ ( "Ansehen" );
  }
  return showIconButton ( "fa fa-eye",
                          $url,
                          $title,
                          "info",
                          $target );
}


/**
 * return epoch microseconds
 * 
 * @return int microseconds since epoch start
 */
function microNow ()
{
  $mtime = microtime ();
  $mtime = explode ( " ",
                     $mtime );
  return $mtime[ 1 ] + $mtime[ 0 ];
}


/**
 * add html to a domainname that provides whois information on mouse hover
 * 
 * @param string $domainName name to whoisify
 * @param boolean $checkDB add mouse hover highlight signalling if whois information for domain (part) is in db
 * @return string html code
 */
function whoisify ( $domainName,
                    $checkDB = true )
{
  global $db, $my_name, $__;
  /*
   * keep a static copy of all known domains for performance reasons
   */
  static $knownDomains = NULL;

  /*
   * query database
   */
  if ( $checkDB && is_null ( $knownDomains ) )
  {
    $selectDomainStatement = $db->prepare ( "select distinct domain from whois" );
    $selectDomainStatement->execute ();
    $knownDomains = $selectDomainStatement->fetchAll ( PDO::FETCH_COLUMN,
                                                       0 );
  }

  /*
   * ip address cannot be split in parts
   */
  if ( ip2long ( $domainName ) != false )
  {
    $domainParts = array (
      $domainName );
  }
  /*
   * real name: split at "."
   */
  else
  {
    $domainParts = explode ( ".",
                             $domainName );
  }

  $returnValue = "";

  /*
   * loop through parts
   */
  while ( count ( $domainParts ) )
  {
    if ( $domainParts[ 0 ] != "" )
    {
      $returnValue .= "<div class=\"whohover\" ";
      $returnValue .= "onmouseover=\"" . $__[ "include/openHTML" ] [ "js" ][ "mouseOverFunc" ] . "(this, '" . ($checkDB ? (in_array ( implode ( ".",
                                                                                                                                                $domainParts ),
                                                                                                                                                $knownDomains ) ? $__[ "include/openHTML" ][ "values" ] [ "known" ] : $__[ "include/openHTML" ][ "values" ] [ "unknown" ]) : $__[ "include/openHTML" ][ "values" ] [ "neutral" ]) . "')\" ";
      $returnValue .= "onmouseout=\"" . $__[ "include/openHTML" ] [ "js" ][ "mouseOutFunc" ] . "(this, '" . ($checkDB ? (in_array ( implode ( ".",
                                                                                                                                              $domainParts ),
                                                                                                                                              $knownDomains ) ? $__[ "include/openHTML" ][ "values" ] [ "known" ] : $__[ "include/openHTML" ][ "values" ] [ "unknown" ]) : $__[ "include/openHTML" ][ "values" ] [ "neutral" ]) . "')\">";
      $returnValue .= "<a class=\"whohover\" href=\"whois.php?" . $__[ "whois" ] [ "params" ][ "whois" ] . "=" . implode ( ".",
                                                                                                                           $domainParts ) . "\" target=\"" . $my_name . $__[ "whois" ] [ "names" ] [ "frame" ] . "\">" . $domainParts[ 0 ];
    }
    /*
     * chop off leftmost part
     */
    array_shift ( $domainParts );
    /*
     * add "." if more parts to come
     */
    if ( count ( $domainParts ) )
    {
      $returnValue .= ".";
    }

    $returnValue .= "</a></div>";
  }

  return "<div class=\"whohover\">$returnValue</div>";
}


/**
 * return information on a host whose ip address is given as integer
 * 
 * @param long $ipAddress ip address as integer
 * @param string $table not null = return html code to use in given table; null = return as array
 */
function ipHostinfo ( $ipAddress,
                      $table = null )
{
  global $db, $__;

  if ( is_null ( $ipAddress ) || $ipAddress == "" )
  {
    if ( $table )
    {
      return "<td></td>";
    }
    else
    {
      return array ();
    }
  }

  $derivedValues = array ();
  $originalValues = array ();

  $selectHostStatement = $db->prepare ( "select * from host where ip=?" );
  $selectHostStatement->execute ( array (
    $ipAddress ) );
  while ( $host = $selectHostStatement->fetch () )
  {
    if ( $host[ "nameermittelt" ] )
    {
      array_push ( $derivedValues,
                   $host[ "name" ] );
    }
    else
    {
      array_push ( $originalValues,
                   $host[ "name" ] );
    }
  }

  usort ( $originalValues,
          "hostnameSort" );
  usort ( $derivedValues,
          "hostnameSort" );

  $authoritativeName = count ( $originalValues ) ? array_pop ( $originalValues ) : array_pop ( $derivedValues );
  if ( !$authoritativeName )
  {
    if ( $table )
    {
      return "<td class=\"break\"" . onTableToggleEvent ( $table ) . ">" . whoisify ( long2ip ( $ipAddress ) ) . "</td>";
    }
    else
    {
      return array (
        long2ip ( $ipAddress ) );
    }
  }
  else
  {
    if ( $table )
    {
      $nameExplosion = explode ( ".",
                                 $authoritativeName );
      $topLevelDomain = array_pop ( $nameExplosion );
      $domain = array_pop ( $nameExplosion );
      array_push ( $nameExplosion,
                   $domain . "." . $topLevelDomain );
      return "<td class=\"break\"" . onTableToggleEvent ( $table ) . ">" . whoisify ( $authoritativeName ) . "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $table . "\"><br>(" . implode ( " * ",
                                                                                                                                                                                                                             array_merge ( array_map ( "whoisify",
                                                                                                                                                                                                                                                       $originalValues ),
                                                                                                                                                                                                                                                       array_map ( "whoisify",
                                                                                                                                                                                                                                                                   $derivedValues ),
                                                                                                                                                                                                                                                                   array (
          whoisify ( long2ip ( $ipAddress ) ) ) ) ) . ")</span>" . "<!--" . implode ( " ",
                                                                                      array_reverse ( $nameExplosion ) ) . " --></td>";
    }
    else
    {
      return array_merge ( array (
        $authoritativeName ),
                           $originalValues,
                           $derivedValues,
                           array (
        long2ip ( $ipAddress ) ) );
    }
  }
}


/**
 * return information on a host whose name is given
 */
function nameHostinfo ( $hostName,
                        $table = null )
{
  global $db;

  $selectHostIPStatement = $db->prepare ( "select ip from host where name=?" );
  $selectHostIPStatement->execute ( array (
    $hostName ) );
  return ipHostinfo ( $selectHostIPStatement->fetchColumn (),
                      $table );
}


/**
 * return information on a host whose db id is given
 */
function idHostinfo ( $hostID,
                      $table = null )
{
  global $db;

  $selectHostIPStatement = $db->prepare ( "select ip from host where id=?" );
  $selectHostIPStatement->execute ( array (
    $hostID ) );
  return ipHostinfo ( $selectHostIPStatement->fetchColumn (),
                      $table );
}


/**
 * sorter function for host names
 * 
 * @param string $hostA name of first host to compare
 * @param string $hostB name of second host to compare
 * @return int -1 if hostA is less than hostB; +1 if hostA is greater than hostB; 0 if they are equal
 */
function hostnameSort ( $hostA,
                        $hostB )
{
  $explosionA = explode ( ".",
                          $hostA );
  $explosionB = explode ( ".",
                          $hostB );
  $topLevelDomainA = array_pop ( $explosionA );
  $domainA = array_pop ( $explosionA );
  $topLevelDomainB = array_pop ( $explosionB );
  $domainB = array_pop ( $explosionB );

  /*
   * primary comparation is done on domain name
   */
  if ( $domainA != $domainB )
  {
    return strcmp ( $domainA,
                    $domainB );
  }
  /*
   * secondary is tld
   */
  else if ( $topLevelDomainA != $topLevelDomainB )
  {
    return strcmp ( $topLevelDomainA,
                    $topLevelDomainB );
  }
  /*
   * last is server name
   */
  else
  {
    return strcmp ( $explosionA[ 0 ],
                    $explosionB[ 0 ] );
  }
}


/**
 * show recordings scope
 * 
 * @param int $recordingID id>0 of specific recording or 0 for all recordings
 */
function recordingsScope ( $recordingID )
{
  global $db;

  $selectRecordingsStatement = $db->prepare ( "select count(*) from aufzeichnung" );
  $selectRecordingsStatement->execute ( array () );
  $recordings = $selectRecordingsStatement->fetchColumn ();

  /*
   * if only one recording exists, nothing must be displayed
   */
  if ( $recordings > 1 )
  {
    /*
     * show all recordings
     */
    if ( $recordingID == 0 )
    {
      echo "<div class=\"row\">";
      showInfoMessage ( _ ( "Unter Berücksichtigung aller Aufzeichnungen" ) );
      echo "</div>";
    }
    /*
     * show specific recording only
     */
    else
    {
      $selectRecordingStatement = $db->prepare ( "select name,date_format(start,'%e.%c.%Y %H:%i') as _start from aufzeichnung where id=?" );
      $selectRecordingStatement->execute ( array (
        $recordingID ) );
      $recording = $selectRecordingStatement->fetch ();

      echo "<div class=\"row\">";
      showInfoMessage ( _ ( "Begrenzt auf die Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong>") . _ ( " vom " ) . $recording[ "_start" ] );
      echo "</div>";
    }
  }
}


/**
 * collect information on memory usage (sd card or memory stick)
 * 
 * @return array (boolean: true if usb memory stick is mounted; string: memory used; string: memory free; int: memory used in percent)
 */
function storageInformation ()
{
  global $data_dir, $mysqld_datadir;
  require_once ("probeHardware.php");

  $freeDir = hasMemoryStick () == 2 ? $data_dir : "/";
  /*
   * get sizes in blocks
   */
  $memUsed = `/usr/bin/du -s $mysqld_datadir | /usr/bin/cut -f1`;
  $memFree = `/bin/df | /bin/grep \\\\s$freeDir\$ | /usr/bin/tr -s ' ' | /usr/bin/cut -d ' ' -f4`;
  $memPercent = 100 - floor ( 100 * $memFree / ($memFree + $memUsed) );
  /*
   * sizes in human units (k, M, G)
   */
  $memUsed = `/usr/bin/du -sh $mysqld_datadir | /usr/bin/cut -f1`;
  $memFree = `/bin/df -h | /bin/grep \\\\s$freeDir\$ | /usr/bin/tr -s ' ' | /usr/bin/cut -d ' ' -f4`;

  return array (
    $freeDir == $data_dir,
    $memUsed,
    $memFree,
    $memPercent + 0 );
}


/**
 * control green system LED
 * 
 * @param int $ledOnMS time span im ms led should be on (permanently off if value is 0)
 * @param type $ledOffMS time span in ms led should be off (permanently on if value is 0)
 */
function flashLED ( $ledOnMS,
                    $ledOffMS )
{
  global $__;

  /*
   * Raspberry Zeroes do have an inverse LED logic (apparently for cost reasons), see https://raspberrypi.stackexchange.com/questions/40559/disable-leds-pi-zero and https://www.raspberrypi.org/forums/viewtopic.php?f=63&t=127400
   */
  exec ( "/bin/cat /proc/cpuinfo |grep \"^Revision.*9000\"",
         $cpuinfo,
         $returnValue );
  /*
   * zeroes have a revision starting with "9000", we swap on and off values then
   */
  if ( !$returnValue )
  {
    $x = $ledOnMS;
    $ledOnMS = $ledOffMS;
    $ledOffMS = $x;
  }

  $ledTriggerFH = fopen ( "/sys/class/leds/led0/trigger",
                          "w" );

  /*
   * if no on-time, switch off; if no off-time, switch on
   */
  if ( $ledOnMS == 0 || $ledOffMS == 0 )
  {
    fprintf ( $ledTriggerFH,
              "none" );
    $ledBrightnessFH = fopen ( "/sys/class/leds/led0/brightness",
                               "w" );
    fprintf ( $ledBrightnessFH,
              $ledOnMS == 0 ? 0 : 1  );
    fclose ( $ledBrightnessFH );
  }
  /*
   * blink
   */
  else
  {
    fprintf ( $ledTriggerFH,
              "timer" );

    $ledOnFH = fopen ( "/sys/class/leds/led0/delay_on",
                       "w" );
    fprintf ( $ledOnFH,
              $ledOnMS );
    fclose ( $ledOnFH );

    $ledOffFH = fopen ( "/sys/class/leds/led0/delay_off",
                        "w" );
    fprintf ( $ledOffFH,
              $ledOffMS );
    fclose ( $ledOffFH );
  }
  fclose ( $ledTriggerFH );
}


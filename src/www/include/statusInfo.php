<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/statusInfo.php
 * 
 * used to provide several system and state information for the top menu's info button (see include/topMenu.php)
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/timeUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * show one row of content
 * rows are made of three columns: left, possibly a warning sign, right
 * 
 * @param string $left content to show in left column
 * @param string $right content to show in right column
 * @param boolean $warning if true show some warning signs
 */
function row ( $left = "",
               $right = "",
               $warning = false )
{
  $class = $warning ? " bg-warning" : "";
  $sign = $warning ? "<span class=\"text-danger\"><i class=\"fa fa-warning fa-lg\"></i></span>" : "";
  echo "<div class=\"row$class\"><div class=\"col-xs-5\"><p class=\"text-right\">$left</p></div><div class=\"col-xs-1\"><p class=\"text-center\">$sign</p></div><div class=\"col-xs-6\"><p><strong>$right</strong></p></div></div>";
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * time related infos
 */
row ( _ ( "Datum" ),
          strftime ( "%d.%m.%Y" ),
                     strftime ( "%Y" ) < 2016 || !file_exists ( $temp_dir . "/" . $__[ "include/goOnline" ] [ "values" ][ "onlineFlag" ] ) );
row ( _ ( "Uhrzeit" ),
          strftime ( "%H:%M:%S" ),
                     strftime ( "%Y" ) < 2016 || !file_exists ( $temp_dir . "/" . $__[ "include/goOnline" ] [ "values" ][ "onlineFlag" ] ) );
row ( _ ( "Betriebsdauer" ),
          humanReadableDuration ( time () - strtotime ( `/usr/bin/uptime -s` ) ) );
row ();


/*
 * memory usage
 */
list ($isUSB, $storageUsed, $storageFree, $storagePercent) = storageInformation ();
row ( _ ( ( $isUSB ? _ ( "USB-Stick" ) : _ ( "SD-Karte" )) . "<br>$storageFree frei" ),
                                             "<div class=\"progress\" style=\"margin-bottom:0px\">
  <div class=\"progress-bar progress-bar-striped progress-bar-" . ($storagePercent < 75 ? "success" : ($storagePercent < 90 ? "warning" : "danger")) . "\" role=\"progressbar\" style=\"min-width: 2em; width:" . ($storagePercent + 0) . "%;\">$storagePercent%</div>
</div>",
                                             $storagePercent >= 90 );
row ();


/*
 * are we online and if so, how?
 */
$internet = _ ( "Offline" );
$warn = true;
if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
{
  $cfile = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                   "r" );
  $line = fgets ( $cfile );
  fclose ( $cfile );
  $internet = trim ( substr ( $line,
                              1 ) );
  $warn = false;
}
row ( _ ( "Internet" ),
          $internet,
          $warn );
if ( !$warn )
{
  row ( _ ( "Online" ),
            $__internet_aufzeichnung ? _ ( "Nur während einer Aufzeichnung" ) : _ ( "Dauerhaft" )  );
}


/*
 * recording active?
 */
if ( !file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  row ( _ ( "Aufzeichnung" ),
            _ ( "nein" ) );
}
else
{
  $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                   "r" );
  $recordingID = trim ( fgets ( $sfile ) );
  $ipAddress = trim ( fgets ( $sfile ) );
  $running = trim ( fgets ( $sfile ) );
  fclose ( $sfile );

  if ( $running )
  {
    row ( _ ( "Aufzeichnung aktiv für IP" ),
              $ipAddress );

    /*
     * stat of session file gives creation time which is start of recording
     */
    $since = stat ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] )[ "mtime" ];
    row ( _ ( "Aufzeichnungsdauer" ),
              gmdate ( "H:i:s",
                       time () - $since ) );
  }
  else
  {
    row ( _ ( "Aufzeichnung" ),
              _ ( "wird beendet" ) );
  }

  /*
   * determine recorded and processed connections so far
   */
  $connectionDir = $recordingID . "/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ];
  $connections = 0 + `/bin/ls -1 "$data_dir/$connectionDir" | wc -l`;
  $connectionsDone = 0 + `/bin/ls -1 "$temp_dir/$connectionDir" | wc -l`;
  row ( _ ( "Verbindungen (davon in DB)" ),
            "$connections ($connectionsDone)" );
}

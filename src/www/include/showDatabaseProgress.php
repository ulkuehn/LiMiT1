<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/showDatabaseProgress.php
 * 
 * used to show the progress of processing raw recorded data to structured data in the database
 * works by counting the connections that are processed vs those that have been recorded
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
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  $recordingID = $_GET[ $__[ "include/showDatabaseProgress" ][ "params" ][ "id" ] ];

  /*
   * on debug and if a start time is given, calculate processing time so far
   */
  if ( $__debug && isset ( $_GET[ $__[ "include/showDatabaseProgress" ][ "params" ][ "start" ] ] ) )
  {
    $totaltime = " &middot;//&middot; " . strftime ( "%d.%m.%Y, %H:%M:%S" ) . " &middot; " . floor ( (microNow () - $_GET[ $__[ "include/showDatabaseProgress" ][ "params" ][ "start" ] ]) * 1000 ) . " ms";
  }
  else
  {
    $totaltime = "";
  }

  $connectionDir = $recordingID . "/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ];
  $connections = 0 + `/bin/ls -1 "$data_dir/$connectionDir" | wc -l`;
  $connectionsDone = 0 + `/bin/ls -1 "$temp_dir/$connectionDir" | wc -l`;
  $progressBarWidth = floor ( $connectionsDone * 100 / ($connections == 0 ? 1 : $connections) );

  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Fortschritt" ), "</h4></div><div class=\"panel-body\"><div class=\"progress\"><div class=\"progress-bar progress-bar-success progress-bar-striped\" role=\"progressbar\" style=\"min-width: 5em; width: $progressBarWidth%\">";
  echo "<p style=\"color:black;font-weight:bold\">$connectionsDone / $connections</p></div></div>";

  showWaitMessage ( _ ( "Restliche Verbindungen in Datenbank übernehmen (noch " ) . ($connections - $connectionsDone) . _ ( " übrig)" ) . $totaltime );
  echo "</div></div>";
}
else
{
  echo "";
}

<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/onlineOfflineUtility.php
 * 
 * common definitions needed for all scripts going on- or offline
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
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * execute shell script that breaks internet connection and delete it
 */
function goOffline ()
{
  global $__, $temp_dir;

  if ( file_exists ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
  {
    exec ( "/bin/bash " . $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] );
    rename ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
             $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "donePostfix" ] );
  }

  unlink ( $temp_dir . "/" . $__[ "include/goOnline" ] [ "values" ][ "onlineFlag" ] );
}


/**
 * ajax trigger php script that puts system online and monitor progress
 * 
 * @param string $connectionMethod description of the technical way to go online
 */
function goOnline ( $connectionMethod )
{
  global $__;

  if ( $connectionMethod == "" )
  {
    $connectionMethod = _ ( "Verbindung" );
  }
  else
  {
    $connectionMethod .= _ ( "-Verbindung" );
  }

  /*
   * message to display while trying to go online
   */
  echo "<div class=\"row\" id=\"", $__[ "include/onlineOfflineUtility" ] [ "ids" ][ "progress" ], "\">";
  showWaitMessage ( _ ( "Die $connectionMethod wird hergestellt ..." ) );
  echo "</div>";

  /*
   * message to display when successfully having gone online
   */
  echo "<div class=\"row\" id=\"", $__[ "include/onlineOfflineUtility" ] [ "ids" ][ "success" ], "\" style=\"display:none\">";
  showSuccessMessage ( _ ( "Die $connectionMethod wurde erfolgreich hergestellt." ) );
  echo "</div>";

  /*
   * message to display when failed to go online
   */
  echo "<div class=\"row\" id=\"", $__[ "include/onlineOfflineUtility" ] [ "ids" ][ "failure" ], "\" style=\"display:none\">";
  showErrorMessage ( _ ( "Die $connectionMethod konnte nicht hergestellt werden." ) );
  echo "</div>";

  /*
   * script monitoring online progress, success or failure
   */
  echo "<script> var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { document.getElementById(\"", $__[ "include/onlineOfflineUtility" ] [ "ids" ][ "progress" ], "\").style.display=\"none\"; if (xmlhttp.responseText == \"", $__[ "include/goOnline" ] [ "responses" ] [ "success" ], "\") { document.getElementById(\"", $__[ "include/onlineOfflineUtility" ] [ "ids" ][ "success" ], "\").style.display=\"block\"; document.getElementById(\"topmenuOffline\").classList.remove(\"disabled\"); } else { document.getElementById(\"", $__[ "include/onlineOfflineUtility" ] [ "ids" ][ "failure" ], "\").style.display=\"block\"; } } }; xmlhttp.open(\"GET\",\"include/goOnline.php\",true); xmlhttp.send();</script>";
}


/**
 * write shell script that puts system online
 * 
 * @param string $shellCommands shell commands to execute
 * @param string $interfaceName name of the interface to use
 */
function writeOnlineScript ( $shellCommands,
                             $interfaceName = "" )
{
  global $__, $__internet_aufzeichnung, $temp_dir;

  unlink ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "onlineScript" ] . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "donePostfix" ] );

  $script = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "onlineScript" ],
                    "w" );
  fwrite ( $script,
           $shellCommands );

  /*
   * if configured for passing online connection to all connected devices immediately (not only on reording), add appropriate iptables rule
   */
  if ( $interfaceName != "" && !$__internet_aufzeichnung )
  {
    fwrite ( $script,
             $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --append POSTROUTING --out-interface $interfaceName -j MASQUERADE\n" );
  }

  fclose ( $script );
}


/**
 * write shell script that puts system offline
 * 
 * @param string $shellCommandsshell commands to execute
 * @param string $interfaceName name of the interface to use
 */
function writeOfflineScript ( $shellCommands,
                              $interfaceName = "" )
{
  global $__, $temp_dir;

  unlink ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "donePostfix" ] );

  $script = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                    "w" );
  fwrite ( $script,
           $shellCommands );

  /*
   * we try to delete iptables rule in any case (independant of $__internet_aufzeichnung), as configuration might change anytime
   * if no such rule exists, this is on no harm
   */
  if ( $interfaceName != "" )
  {
    fwrite ( $script,
             $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --delete POSTROUTING --out-interface $interfaceName -j MASQUERADE\n" );
  }
  fclose ( $script );
}


<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file connectOffline.php
 * 
 * disconnect the LiMiT1 system from the internet
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
require_once ("include/onlineOfflineUtility.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

titleAndHelp ( _ ( "Offline" ),
                   _ ( "Die bestehende Internetverbindung trennen. Eine Aufzeichnung ist nur bei bestehender Internetverbindung möglich." ) );

echo "<div class=\"row\">";

if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  showErrorMessage ( _ ( "Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor $my_name vom Internet getrennt werden kann." ) );
}
else
{
  /*
   * read first line from script to bring system offline
   * first line is a comment naming the method we went online
   */
  if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
  {
    $offlineScriptFH = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                               "r" );
    $line = fgets ( $offlineScriptFH );
    fclose ( $offlineScriptFH );
    $internet = trim ( substr ( $line,
                                1 ) );
  }

  /*
   * really go offline
   */
  if ( array_key_exists ( $__[ "connectOffline" ][ "params" ][ "offline" ],
                          $_REQUEST ) )
  {
    goOffline ();

    echo "<script>document.getElementById(\"", $_[ "include/topMenu" ] [ "ids" ] [ "offline" ], "\").classList.add(\"disabled\");</script>";
    showSuccessMessage ( _ ( "Die $internet-Verbindung wurde getrennt. $my_name ist jetzt offline." ) );
  }

  /*
   * show offline form
   */
  else
  {
    echo "<form method=\"post\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Die per <strong>$internet</strong> bestehende Internetverbindung trennen" ), "</h4></div><div class=\"panel-body\">";
    showAlertMessage ( _ ( "Nach Trennen der Internetverbindung ist keine Aufzeichnung mehr möglich." ) );
    if ( !$__internet_aufzeichnung )
    {
      showInfoMessage ( _ ( "Die angeschlossenen Geräte werden dadurch ebenfalls vom Internet getrennt." ) );
    }
    echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "connectOffline" ][ "values" ][ "offline" ], "\" name=\"", $__[ "connectOffline" ][ "params" ][ "offline" ], "\"></div></div></form>";
  }
}

echo "</div>";

include ("include/closeHTML.php");

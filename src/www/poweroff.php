<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file powerOff.php
 * 
 * used to power the LiMiT1 system off or reboot it
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
require_once ("include/rebootUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * say good bye to the user
 * 
 * @param string $title title of the good bye panel 
 * @param string $text text within the panel
 */
function bye ( $title,
               $text )
{
  global $__;

  echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">$title</h4></div><div class=\"panel-body\" id=\"", $__[ "powerOff" ] [ "ids" ][ "bye" ], "\"><h1>$text</h1></div></div></div>";
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");


/**
 * id of div to update while rebooting etc
 */
$bye = "bye";



/*
 * no shutdown possible while recording is ongoing
 */
if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  titleAndHelp ( isset ( $_REQUEST[ $__[ "powerOff" ][ "params" ][ "restart" ] ] ) ? _ ( "Neu starten" ) : _ ( "Herunterfahren" )  );
  showErrorMessage ( _ ( "Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor $my_name " ) . (isset ( $_REQUEST[ $__[ "powerOff" ][ "params" ][ "restart" ] ] ) ? _ ( "neu gestartet" ) : _ ( "heruntergefahren" )) . _ ( " werden kann." ) );
}
/*
 * no recording going on
 */
else
{
  /*
   * shutdown
   */
  if ( array_key_exists ( $__[ "powerOff" ][ "params" ][ "shutdown" ],
                          $_POST ) )
  {
    bye ( _ ( "$my_name wird beendet" ),
              _ ( "Tschüss und bis zum nächsten Mal !" ) );
    /*
     * script to monitor shutdown process
     */
    $powerOff = _ ( "Die Stromversorgung von $my_name kann jetzt ausgeschaltet werden." );
    echo "<script> function ping() { $.ajax ({ url: window.location.protocol + \"//\" + window.location.hostname + \"/index.php\", timeout: 5000, success: function (result) { ping(); }, error: function (result) { clearInterval(iv); document.getElementById(\"", $__[ "powerOff" ] [ "ids" ][ "bye" ], "\").innerHTML += \"<br><br><div class='alert alert-warning' role='alert'><strong>$powerOff</strong></div>\"; } }); } ping(); var iv = setInterval (function () { document.getElementById(\"", $__[ "powerOff" ] [ "ids" ][ "bye" ], "\").innerHTML += \"<i class='fa fa-clock-o fa-2x'></i> \"; }, 2000);</script>";
  }
  /*
   * restart
   */
  else if ( array_key_exists ( $__[ "powerOff" ][ "params" ][ "restart" ],
                               $_POST ) )
  {
    bye ( _ ( "$my_name startet neu" ),
              _ ( "$my_name ist in Kürze zurück ..." ) );
    waitForReboot ( $__[ "powerOff" ] [ "ids" ][ "bye" ],
                    "fa-clock-o fa-2x" );
  }
  /*
   * confirmation screens
   */
  else
  {
    /*
     * restart
     */
    if ( $_GET[ $__[ "powerOff" ][ "params" ][ "restart" ] ] == 1 )
    {
      titleAndHelp ( _ ( "Neu starten" ),
                         _ ( "Mit dieser Funktion wird $my_name neu gestartet" ) );
      echo "<form method=\"post\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "$my_name neu starten" ), "</h4></div><div class=\"panel-body\">";
      showInfoMessage ( _ ( "Der Neustart benötigt einige Zeit. Das Browserfenster kann währenddessen geöffnet bleiben." ) );
      echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
      echo _ ( "Neu starten" );
      echo "\" name=\"", $__[ "powerOff" ][ "params" ][ "restart" ], "\">";
      echo "</div></div></div></form>";
    }
    /*
     * shutdown
     */
    else
    {
      titleAndHelp ( _ ( "Herunterfahren" ),
                         _ ( "Mit dieser Funktion wird $my_name heruntergefahren" ) );
      echo "<form method=\"post\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "$my_name herunterfahren" ), "</h4></div><div class=\"panel-body\">";
      showInfoMessage ( _ ( "Nach dem Herunterfahren kann die Stromversorgung von $my_name abgeschaltet werden." ) );
      echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
      echo _ ( "Herunterfahren" );
      echo "\" name=\"", $__[ "powerOff" ][ "params" ][ "shutdown" ], "\">";
      echo "</div></div></div></form>";
    }
  }
}

include ("include/closeHTML.php");

/*
 * system commands
 */
if ( array_key_exists ( $__[ "powerOff" ][ "params" ][ "shutdown" ],
                        $_POST ) )
{
  exec ( "/bin/umount $mount_dir" );
  exec ( "(/bin/sleep 2 && /sbin/halt) > /dev/null 2>&1 &" );
}
else if ( array_key_exists ( $__[ "powerOff" ][ "params" ][ "restart" ],
                             $_POST ) )
{
  exec ( "/bin/umount $mount_dir" );
  exec ( "(/bin/sleep 2 && /sbin/reboot) > /dev/null 2>&1 &" );
}

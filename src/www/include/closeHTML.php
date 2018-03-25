<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/closeHTML.php
 * 
 * used to properly close html tags left open by openHTML.php
 * if debugging is configured, a bunch of extra information is presented to the user
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
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * execute a system command and print its oútput
 * @param string $cmd system command string
 */
function executeCommand ( $cmd )
{
  echo "<pre>$cmd\n\n", htmlspecialchars ( system ( $cmd ) ), "</pre>";
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * on debug give extra timing info in version line
 */
if ( $__debug && isset ( $$__[ "include/httpHeaders" ][ "vars" ][ "starttime" ] ) )
{
  $totaltime = " &middot;//&middot; " . strftime ( "%d.%m.%Y, %H:%M:%S" ) . " &middot; " . floor ( (microNow () - $$__[ "include/httpHeaders" ][ "vars" ][ "starttime" ]) * 1000 ) . " ms";
}
else
{
  $totaltime = "";
}

/*
 * version line
 */
echo "<p class=\"text-right small\"><em>$my_name &middot; ", _ ( "Version" ), " $my_version$totaltime</em></p>";

/*
 * on debug give a report on all superglobals
 */
if ( $__debug )
{
  $globals = array (
    '$_REQUEST' => $_REQUEST,
    '$_GET'     => $_GET,
    '$_POST'    => $_POST,
    '$_COOKIE'  => $_COOKIE,
    '$_SERVER'  => $_SERVER,
    '$_ENV'     => $_ENV,
    '$_FILES'   => $_FILES
  );

  echo "<div class=\"row\">";
  foreach ( $globals as $g => $v )
  {
    echo "<pre>$g:\n", htmlspecialchars ( print_r ( $v,
                                                    true ) ), "</pre>";
  }
  echo "</div>";
}

/*
 * on debug show system info
 */
if ( $__debug )
{
  echo "<div class=\"row\">";
  /*
   * memory usage
   */
  executeCommand ( "/usr/bin/free -m" );
  /*
   * mounted file systems
   */
  executeCommand ( "/bin/df -h" );
  /*
   * network interfaces
   */
  executeCommand ( "/sbin/ifconfig" );
  /*
   * wireless 
   */
  executeCommand ( "/sbin/iwconfig" );
  /*
   * processes
   */
  executeCommand ( "/bin/ps au -N --pid 2 --ppid 2" );
  echo "</div>";
}


/*
 * close container which was opened by topMenu.php and finish html
 */
echo "</div></body></html>";

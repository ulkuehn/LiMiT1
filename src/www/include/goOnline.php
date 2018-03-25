<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file goOnline.php
 * 
 * bring the system online
 * this script executes a readily configured shell script that contains the commands for bringing the system online
 * the method to go online (lan, wifi, ...) is sepecified beforehand in the respective scripts
 * this script verifies that online access is indeed accomplished by checking the system time provided by an online ntp server
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
 * MAIN CODE
 * 
 * ======================================================================== */

$ok = $__[ "include/goOnline" ] [ "responses" ] [ "failure" ];

if ( file_exists ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "onlineScript" ] ) )
{
  exec ( "/bin/bash " . $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "onlineScript" ] );
  sleep ( 2 );

  for ( $pool = 0; $pool < 3 && $ok == 0; $pool++ )
  {
    $fp = fsockopen ( "udp://$pool.pool.ntp.org",
                      123,
                      $errno,
                      $errstr,
                      10 );
    if ( $fp )
    {
      fclose ( $fp );

      exec ( "/usr/sbin/service ntp stop" );
      exec ( "/bin/date -s \"01/01/2000 00:00:00\"" );
      exec ( "/usr/bin/sntp -s $pool.pool.ntp.org" );
      exec ( "/usr/sbin/service ntp start" );

      if ( strftime ( "%Y" ) != "2000" )
      {
        $ok = $__[ "include/goOnline" ] [ "responses" ] [ "success" ];
      }
    }
  }
  rename ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "onlineScript" ],
           $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "onlineScript" ] . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "donePostfix" ] );
}

if ( !$ok && file_exists ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
{
  exec ( "/bin/bash " . $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] );
  rename ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
           $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "donePostfix" ] );
}

if ( $ok )
{
  touch ( $temp_dir . "/" . $__[ "include/goOnline" ] [ "values" ][ "onlineFlag" ] );
}

echo $ok;

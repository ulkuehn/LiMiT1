<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/buttonState.php
 * 
 * used to asynchronously change the state and appearance of several buttons depending on plugged-in hardware and system state
 * returns a sequence of ssemicolon separated values:
 *  0. LAN present and cable plugged in (0=not present/1=no cable/2=cable)
 *  1. wifi adapter present (0/1)
 *  2. UMTS adapter present (0/1)
 *  3. memory stick present and mounted (0=not present/1=not mounted/2=mounted)
 *  4. system is online (0/1)
 *  5. value for record button (start/stop/end)
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
require_once ("include/probeHardware.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * is an ethernet adapter present and cable plugged in?
 */
echo ethernetCable (), ";";

/*
 * are wifi and umts adapters plugged in?
 */
list ($wifi, $umts) = hasWifiUMTS ();
echo $wifi ? "1;" : "0;";
echo $umts ? "1;" : "0;";

/*
 * memory stick present and mounted?
 */
echo hasMemoryStick (), ";";

/*
 * are we online or offline?
 */
echo file_exists ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) ? "1;" : "0;";

/*
 * is a recording session running?
 */
if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                   "r" );
  $recordingID = trim ( fgets ( $sfile ) );
  $ipAddress = trim ( fgets ( $sfile ) );
  $running = trim ( fgets ( $sfile ) );
  fclose ( $sfile );
  echo $running ? $__[ "include/topMenu" ][ "values" ][ "recordStop" ] : $__[ "include/topMenu" ][ "values" ][ "recordEnd" ];
}
else
{
  echo $__[ "include/topMenu" ][ "values" ][ "recordStart" ];
}
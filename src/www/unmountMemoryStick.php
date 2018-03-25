<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file unmountMemoryStick.php
 * 
 * unmount a memory stick
 *
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/connectDB.php");
require_once ("include/probeHardware.php");
require_once ("include/utility.php");
require_once ("include/memoryStickUtility.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

titleAndHelp ( $__[ "include/topMenu" ] [ "values" ] [ "toolsUnmountMemoryStickMenuName" ],
               _ ( "Mit dieser Funktion wird ein eingebundener USB-Stick gelöst. Die im Systemspeicher vorhandenen Aufzeichnungen werden dadurch wieder verfügbar." ) );

$memoryStick = hasMemoryStick ();

/*
 * no stick (shouldn't be here in the first place)
 */
if ( $memoryStick == 0 )
{
  showAlertMessage ( _ ( "Es ist kein USB-Stick eingesteckt" ) );
}
/*
 * stick not mounted - huh?
 */
else if ( $memoryStick == 1 )
{
  showAlertMessage ( _ ( "Es ist kein USB-Stick eingebunden" ) );
}
/*
 * recording going on, do not touch database server!
 */
else if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  showAlertMessage ( _ ( "Während einer laufenden Aufzeichnung kann ein USB-Stick nicht gelöst werden." ) );
}
/*
 * conditions okay
 */
else
{
  /*
   * stop database server
   */
  if ( !stopDatabaseServer () )
  {
    showErrorMessage ( _ ( "Der Datenbankserver konnte nicht angehalten werden" ) );
  }
  else
  {
    /*
     * unmount stick
     */
    exec ( "/bin/umount /dev/sda1",
           $umountOutput,
           $returnValue );
    if ( $returnValue )
    {
      showErrorMessage ( _ ( "Der Stick konnte nicht ausgehängt werden. Bitte den USB-Stick entfernen und $my_name neu starten." ) );
    }
    else if ( !startDatabaseServer ( true ) )
    {
      showErrorMessage ( _ ( "Der Datenbankserver konnte nicht wieder gestartet werden. Bitte den USB-Stick entfernen und $my_name neu starten." ) );
    }
    else
    {
      showSuccessMessage ( _ ( "Der Stick wurde erfolgreich gelöst" ) );
    }
  }
}

include ("include/closeHTML.php");

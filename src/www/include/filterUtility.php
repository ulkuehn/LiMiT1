<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/filterUtility.php
 * 
 * common definitions needed for all scripts filtering recordings
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
 * show a selection menu to filter recordings
 * the menu allows for three modes:
 *  - show all recordings in one table
 *  - show each recording in a seperate table
 *  - show only one specific recording
 * 
 * @param string $textAll optional text to show for selection "all recordings"
 * @param string $textEach optional text to show for selection "each recording"
 * @param string $textSingle optional text to show for selections of single recordings
 * 
 * @return int number of recordings in database
 */
function recordingsSelector ( $textAll = "",
                              $textEach = "",
                              $textSingle = "" )
{
  global $db, $__;

  $selectRecordingsStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc" );
  $selectRecordingsStatement->execute ();
  $recordings = $selectRecordingsStatement->fetchAll ();

  /*
   * no recording
   */
  if ( count ( $recordings ) == 0 )
  {
    $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $__[ "include/filterUtility" ] [ "values" ] [ "noRecordings" ];
  }
  /*
   * only one recording in database
   */
  else if ( count ( $recordings ) == 1 )
  {
    $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $recordings[ 0 ][ "id" ];
  }
  /*
   * more than one recording in database
   */
  else
  {
    if ( isset ( $_COOKIE[ $__[ "include/filterUtility" ][ "names" ][ "cookie" ] ] ) )
    {
      $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $_COOKIE[ $__[ "include/filterUtility" ][ "names" ][ "cookie" ] ];
    }
    else if ( !isset ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) )
    {
      $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ];
    }

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Darstellungsfilter" ), "</h4></div><div class=\"panel-body\">";
    echo "<form method=\"post\" id=\"", $__[ "include/filterUtility" ] [ "ids" ][ "form" ], "\"><select class=\"form-control\" id=\"", $__[ "include/filterUtility" ] [ "ids" ][ "select" ], "\" name=\"show\" onChange=\"document.cookie = '", $__[ "include/filterUtility" ][ "names" ][ "cookie" ], "='+document.getElementById('", $__[ "include/filterUtility" ] [ "ids" ][ "select" ], "').value+'; expires=0'; document.getElementById('", $__[ "include/filterUtility" ] [ "ids" ][ "form" ], "').submit();\">";

    echo "<option value=\"", $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ], "\"", ($_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] ? " selected" : ""), ">", ($textAll == "" ? _ ( "Alle Aufzeichnungen in einer Tabelle" ) : $textAll), "</option>";
    echo "<option value=\"", $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ], "\"", ($_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ] ? " selected" : ""), ">", ($textEach == "" ? _ ( "Jede Aufzeichnung in einer eigenen Tabelle" ) : $textEach), "</option>";

    foreach ( $recordings as $recording )
    {
      echo "<option value=\"", $recording[ "id" ], "\"", ($_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $recording[ "id" ] ? " selected" : ""), ">", ($textSingle == "" ? _ ( "Nur Aufzeichnung" ) . ($recording[ "name" ] == "" ? "" : " &nbsp; &nbsp;" . htmlSave ( strlen ( $recording[ "name" ] ) > $__[ "include/filterUtility" ] [ "values" ] [ "maxRecordingNameLength" ] ? substr ( $recording[ "name" ],
                                                                                                                                                                                                                                                                                                                                                                                                                              0,
                                                                                                                                                                                                                                                                                                                                                                                                                              $__[ "include/filterUtility" ] [ "values" ] [ "maxRecordingNameLength" ] - strlen ( $__[ "include/filterUtility" ] [ "values" ] [ "reordingNameEllipses" ] ) ) . $__[ "include/filterUtility" ] [ "values" ] [ "reordingNameEllipses" ] : $recording[ "name" ]  ) . "&nbsp; &nbsp;") . _ ( " vom " ) . $recording[ "_start" ] : $textSingle), "</option>";
    }
    echo "</select></form></div></div></div>";
  }

  return count ( $recordings );
}


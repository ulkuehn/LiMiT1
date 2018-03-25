<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file recordingBrowser.php
 * 
 * used to show the currently selected recording and provide access to all available recordings
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
require_once ("include/tableUtility.php");
require_once ("include/timeUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * determine recording id directly from http parameter or by using id of an associated connection or request
 */
if ( isset ( $_GET[ $__[ "showRecording" ][ "params" ][ "recording" ] ] ) )
{
  $recordingID = $_GET[ $__[ "showRecording" ][ "params" ][ "recording" ] ];
}
else if ( isset ( $_GET[ $__[ "showRecording" ][ "params" ][ "connection" ] ] ) )
{
  $selectRecordingStatement = $db->prepare ( "select aufzeichnung from verbindung where id=?" );
  $selectRecordingStatement->execute ( array (
    $_GET[ $__[ "showRecording" ][ "params" ][ "connection" ] ] ) );
  $recordingID = $selectRecordingStatement->fetchColumn ();
}
else if ( isset ( $_GET[ $__[ "showRecording" ][ "params" ][ "request" ] ] ) )
{
  $selectRecordingStatement = $db->prepare ( "select aufzeichnung from request where id=?" );
  $selectRecordingStatement->execute ( array (
    $_GET[ $__[ "showRecording" ][ "params" ][ "request" ] ] ) );
  $recordingID = $selectRecordingStatement->fetchColumn ();
}

/*
 * if succesful, show browsing buttons and table with basic infos
 */
if ( isset ( $recordingID ) )
{
  $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt, timediff(ende,start) as _dauer, unix_timestamp(ende)-unix_timestamp(start) as _diff from aufzeichnung where id=?" );
  $selectRecordingStatement->execute ( array (
    $recordingID ) );
  if ( $recording = $selectRecordingStatement->fetch () )
  {
    $$__[ "include/recordingBrowser" ][ "vars" ] [ "recording" ] = $recording;

    $selectPreviousRecordingStatement = $db->prepare ( "select id from aufzeichnung where id<? order by id desc limit 1" );
    $selectPreviousRecordingStatement->execute ( array (
      $recording[ "id" ] ) );
    $previousRecording = $__[ "include/recordingBrowser" ][ "values" ][ "backButtonInactive" ];
    if ( $id = $selectPreviousRecordingStatement->fetchColumn () )
    {
      $previousRecording = "<a href=\"showRecording.php?" . $__[ "showRecording" ] [ "params" ] [ "recording" ] . "=$id\">" . $__[ "include/recordingBrowser" ][ "values" ][ "backButton" ] . "</a>";
    }
    $selectNextRecordingStatement = $db->prepare ( "select id from aufzeichnung where id>? order by id asc limit 1" );
    $selectNextRecordingStatement->execute ( array (
      $recording[ "id" ] ) );
    $nextRecording = $__[ "include/recordingBrowser" ][ "values" ][ "forwardButtonInactive" ];
    if ( $id = $selectNextRecordingStatement->fetchColumn () )
    {
      $nextRecording = "<a href=\"showRecording.php?" . $__[ "showRecording" ] [ "params" ] [ "recording" ] . "=$id\">" . $__[ "include/recordingBrowser" ][ "values" ][ "forwardButton" ] . "</a>";
    }

    $foldMe = tableFolder ( $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ] );

    echo "<div class=\"row nestedPanel\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">$previousRecording $nextRecording ", _ ( "Aufzeichnung" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ], "\" class=\"table\"><thead><tr>";
    echo "<th>$foldMe</th>";
    echo "<th>", _ ( "Bezeichnung" ), "</th>";
    echo "<th>", _ ( "Beginn" ), "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ], "\"> ", _ ( "der Aufzeichnung" ), "</span></th>";
    echo "<th>", _ ( "Dauer" ), "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ], "\"> ", _ ( "der Aufzeichnung" ), "</span></th>";
    echo "<th>", _ ( "Gerät" ), "</th>";
    echo "<th>", _ ( "Infos" ), "</th>";
    echo "<th><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ], "\">", _ ( "Verb." ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ], "\">", _ ( "Verbindungen" ), "</span></th></tr></thead><tbody><tr>";

    echo "<td>", showViewButton ( "showRecording.php?" . $__[ "showRecording" ] [ "params" ] [ "recording" ] . "=" . $recording[ "id" ],
                                  $__[ "evaluateRecordings" ][ "titles" ][ "viewRecording" ] ), "</td>";

    echo foldableTableCell ( $recording[ "name" ],
                             $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ] );

    echo "<td", onTableToggleEvent ( $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ] ), ">", $recording[ "_startd" ], " <i class=\"fa fa-clock-o\"></i> ", $recording[ "_startt" ], "</td>";

    echo "<td", onTableToggleEvent ( $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ] ), ">", humanReadableDuration ( $recording[ "_diff" ] ), "</td>";

    $selectDeviceStatement = $db->prepare ( "select * from geraet where id=?" );
    $selectDeviceStatement->execute ( array (
      $recording[ "geraet" ] ) );
    echo foldableTableCell ( $selectDeviceStatement->fetch ()[ "name" ],
                             $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ] );

    echo foldableTableCell ( $recording[ "info" ],
                             $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ] );

    $selectNumberOfConnectionsStatement = $db->prepare ( "select count(*) from verbindung where aufzeichnung=?" );
    $selectNumberOfConnectionsStatement->execute ( array (
      $recording[ "id" ] ) );
    $numberOfConnections = $selectNumberOfConnectionsStatement->fetchColumn ();
    echo "<td class=\"numeric\"", onTableToggleEvent ( $__[ "include/recordingBrowser" ][ "ids" ][ "tables" ][ "recording" ] ), ">$numberOfConnections</td>";

    echo "</tr></tbody></table></div></div></div></div>";
  }
}

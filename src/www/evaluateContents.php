<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateContents.php
 * 
 * display specifics of the contents of a connection
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
require_once ("include/filterUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

function tabulateContents ( $recordingID,
                            $tableID )
{
  global $db, $__;

  if ( $recordingID == 0 )
  {
    $selectContentsStatement = $db->prepare ( "select 0 as sent,response.verbindung as verbindung,response.inhalt as inhalt,mime,length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id having length>0
                              union all
                              select 1 as sent,request.verbindung as verbindung,request.inhalt as inhalt,mime,length(inhalt.inhalt) as length from request,inhalt where request.inhalt=inhalt.id having length>0" );
    $selectContentsStatement->execute ();
  }
  else
  {
    $selectContentsStatement = $db->prepare ( "select 0 as sent,response.verbindung as verbindung,response.inhalt as inhalt,mime,length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id and response.aufzeichnung=? having length>0
                              union all
                              select 1 as sent,request.verbindung as verbindung,request.inhalt as inhalt,mime,length(inhalt.inhalt) as length from request,inhalt where request.inhalt=inhalt.id and request.aufzeichnung=? having length>0" );
    $selectContentsStatement->execute ( array (
      $recordingID,
      $recordingID ) );
  }

  /*
   * no contents found
   */
  if ( ($contents = $selectContentsStatement->fetch ()) == false )
  {
    return array (
      false,
      _ ( "Es wurden keine Inhalte übertragen." ) );
  }
  /*
   * contents found
   */
  else
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID,
                                                     false );
    $table .= tableSorter ( $tableID,
                            "columns: [ {orderable:false, searchable:false}" . ($_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] ? ", {}" : "") . ", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );

    $table .= "<div class=\"table-responsive\"><table id=\"$tableID\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    if ( $recordingID == 0 )
    {
      $table .= "<th>" . _ ( "Aufzeichnung" ) . "</th>";
    }
    $table .= "<th>" . _ ( "Zeit" ) . "</th>";
    $table .= "<th>" . _ ( "Typ" ) . "</th>";
    $table .= "<th></th>";
    $table .= "<th>" . _ ( "Bytes" ) . "</th>";
    $table .= "<th>" . _ ( "Server" ) . "</th></tr></thead><tbody>";

    do
    {
      $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?" );
      $selectConnectionStatement->execute ( array (
        $contents[ "verbindung" ] ) );
      $connection = $selectConnectionStatement->fetch ();

      $table .= "<tr>";

      /*
       * contents drilldown
       */
      $table .= "<td>" . showViewButton ( "showContents.php?" . $__[ "showContents" ] [ "params" ][ "contents" ] . "=" . $contents[ "inhalt" ],
                                          _ ( "Inhalt ansehen" ) ) . "</td>";

      /*
       * recording details
       */
      if ( $recordingID == 0 )
      {
        $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
        $selectRecordingStatement->execute ( array (
          $connection[ "aufzeichnung" ] ) );
        $recording = $selectRecordingStatement->fetch ();
        $table .= $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                                    $tableID );
      }

      /*
       * time (sort by connection number)
       */
      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . $connection[ "_zeitd" ] . " <i class=\"fa fa-clock-o\"></i> </span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . "</span>" . $connection[ "_zeitt" ] . "<!--" . $connection[ "nr" ] . "--></td>";

      /*
       * MIME type
       */
      $table .= foldableTableCell ( strtolower ( $contents[ "mime" ] ),
                                                 $tableID );

      /*
       * direction (outgoing / incoming)
       */
      $table .= "<td>" . ($contents[ "sent" ] ? $__[ "evaluateContents" ] [ "values" ] [ "outgoingIcon" ] : $__[ "evaluateContents" ] [ "values" ] [ "incomingIcon" ]) . "<!--" . $contents[ "sent" ] . "--></td>";

      /*
       * bytes
       */
      $table .= "<td class=\"numeric\">" . $contents[ "length" ] . "</td>";

      /*
       * server
       */
      if ( !$connection[ "host" ] )
      {
        $table .= ipHostinfo ( $connection[ "ip" ],
                               $tableID );
      }
      else
      {
        $table .= idHostinfo ( $connection[ "host" ],
                               $tableID );
      }

      $table .= "</tr>";
    }
    while ( $contents = $selectContentsStatement->fetch () );

    $table .= "</tbody></table></div>";

    return array (
      true,
      $table );
  }
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

if ( !isset ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) )
{
  $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ];
}

titleAndHelp ( _ ( "Inhalte" ),
                   _ ( "Diese Auswertung betrachtet die per HTTP(S) übertragenen Inhalte der verschiedenen MIME-Typen. In der Regel werden Inhalte als Teil der Response übertragen, aber auch im Request können Inhaltsdaten übermittelt werden." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($contentsFound, $contentsTable) = tabulateContents ( 0,
                                                               $__[ "evaluateContents" ][ "ids" ][ "tables" ][ "contents" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$contentsFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Inhalte aller Aufzeichnungen" ), (!$contentsFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$contentsTable</div></div></div>";
  }

  /*
   * show each recording in a seperate table
   */
  else if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ] )
  {
    echo "<div class=\"row\"><div class=\"panel-group\" role=\"tablist\">";

    $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc" );
    $selectRecordingStatement->execute ();
    while ( $recording = $selectRecordingStatement->fetch () )
    {
      list ($contentsFound, $contentsTable) = tabulateContents ( $recording[ "id" ],
                                                                 $__[ "evaluateContents" ][ "ids" ][ "tables" ][ "contents" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateContents" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";
      echo (!$contentsFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Inhalte der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$contentsFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateContents" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">$contentsTable</div></div></div>";
    }
    echo "</div></div>";
  }

  /*
   * show single recording
   */
  else
  {
    $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?" );
    $selectRecordingStatement->execute ( array (
      $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) );
    $recording = $selectRecordingStatement->fetch ();

    list ($contentsFound, $contentsTable) = tabulateContents ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                               $__[ "evaluateContents" ][ "ids" ][ "tables" ][ "contents" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$contentsFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Inhalte der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$contentsFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$contentsTable</div></div></div>";
  }
}

include ("include/closeHTML.php");

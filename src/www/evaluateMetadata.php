<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateMetadata.php
 * 
 * display meta information of specific mime types (e.g. comments in html content, camera information in jpeg)
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

/**
 * build a set of tables listing metadata
 * 
 * @param int $recordingID database id of a specific recording or 0 for all recordings
 * @param string $tableID id prefix of the tables to build
 * @return array if metadata were found: (true, html code of table); if no metadata: (false, some text)
 */
function tabulateMetadata ( $recordingID,
                            $tableID )
{
  global $db, $__;

  $tableCounter = "_1";

  /*
   * HTML metadata
   */
  if ( $recordingID == 0 )
  {
    $selectMetadataStatement = $db->prepare ( "select * from metadaten where mime=?" );
    $selectMetadataStatement->execute ( array (
      "text/html" ) );
  }
  else
  {
    $selectMetadataStatement = $db->prepare ( "select * from metadaten where mime=? and aufzeichnung=?" );
    $selectMetadataStatement->execute ( array (
      "text/html",
      $recordingID ) );
  }

  if ( ($metadata = $selectMetadataStatement->fetch ()) != false )
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID . $tableCounter,
                                                     false );
    $table .= tableSorter ( $tableID . $tableCounter,
                            "columns: [ {orderable:false, searchable:false}" . ($recordingID == 0 ? ", {}" : "") . ", {}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]" );

    $table .= "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">" . _ ( "HTML-Metadaten (Titel, Kommentare etc.)" ) . "</h5></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"$tableID$tableCounter\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    if ( $recordingID == 0 )
    {
      $table .= "<th>" . _ ( "Aufzeichnung" ) . "</th>";
    }
    $table .= "<th>" . _ ( "Typ" ) . "</th>";
    $table .= "<th>" . _ ( "Inhalt" ) . "</th>";
    $table .= "<th>" . _ ( "Zeit" ) . "</th>";
    $table .= "<th>" . _ ( "Server" ) . "</th></tr></thead><tbody>";

    do
    {
      $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?" );
      $selectConnectionStatement->execute ( array (
        $metadata[ "verbindung" ] ) );
      $connection = $selectConnectionStatement->fetch ();

      $table .= "<tr>";

      $table .= "<td>" . showViewButton ( "showRequest.php?" . $__[ "showRequest" ] [ "params" ] [ "request" ] . "=" . $metadata[ "request" ],
                                          $__[ "showRequest" ][ "titles" ][ "viewRequest" ] ) . "</td>";

      if ( $recordingID == 0 )
      {
        $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
        $selectRecordingStatement->execute ( array (
          $connection[ "aufzeichnung" ] ) );
        $recording = $selectRecordingStatement->fetch ();
        $table .= $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                                    $tableID . $tableCounter );
      }

      $table .= foldableTableCell ( $metadata[ "feld" ],
                                    $tableID . $tableCounter );
      $table .= foldableTableCell ( $metadata[ "wert" ],
                                    $tableID . $tableCounter );

      $table .= "<td" . onTableToggleEvent ( $tableID . $tableCounter ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . $tableCounter . "\">" . $connection[ "_zeitd" ] . " <i class=\"fa fa-clock-o\"></i> </span><span class=\"" . $__[ "include/tableUtility" ][ "values" ][ "foldedPrefix" ] . $tableID . $tableCounter . "\">" . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . "</span>" . $connection[ "_zeitt" ] . "<!--" . $connection[ "nr" ] . "--></td>";

      if ( !$connection[ "host" ] )
      {
        $table .= ipHostinfo ( $connection[ "ip" ],
                               $tableID . $tableCounter );
      }
      else
      {
        $table .= idHostinfo ( $connection[ "host" ],
                               $tableID . $tableCounter );
      }
      $table .= "</tr>";
    }
    while ( $metadata = $selectMetadataStatement->fetch () );

    $table .= "</tbody></table></div></div></div>";
  }


  /*
   * image metadata
   */
  $tableCounter = "_2";

  if ( $recordingID == 0 )
  {
    $selectMetadataStatement = $db->prepare ( "select * from metadaten where mime like ?" );
    $selectMetadataStatement->execute ( array (
      "image/%" ) );
  }
  else
  {
    $selectMetadataStatement = $db->prepare ( "select * from metadaten where mime like ? and aufzeichnung=?" );
    $selectMetadataStatement->execute ( array (
      "image/%",
      $recordingID ) );
  }

  if ( ($metadata = $selectMetadataStatement->fetch ()) != false )
  {
    list ($e, $foldUnfoldButton) = tableFolder ( $tableID . $tableCounter,
                                                 false );
    $table .= $e . tableSorter ( $tableID . $tableCounter,
                                 "columns: [ {orderable:false, searchable:false}" . ($recordingID == 0 ? ", {}" : "") . ", {}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]" );

    $table .= "<div class=\"panel panel-info\" style=\"margin-top:20px\"><div class=\"panel-heading\"><h5 class=\"panel-title\">" . _ ( "Bilder-Metadaten (Dimensionen, EXIF-Daten, Kommentare etc.)" ) . "</h5></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"$tableID$tableCounter\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    if ( $recordingID == 0 )
    {
      $table .= "<th>" . _ ( "Aufzeichnung" ) . "</th>";
    }
    $table .= "<th>" . _ ( "Typ" ) . "</th>";
    $table .= "<th>" . _ ( "Inhalt" ) . "</th>";
    $table .= "<th>" . _ ( "Zeit" ) . "</th>";
    $table .= "<th>" . _ ( "Server" ) . "</th></tr></thead><tbody>";

    do
    {
      $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?" );
      $selectConnectionStatement->execute ( array (
        $metadata[ "verbindung" ] ) );
      $connection = $selectConnectionStatement->fetch ();

      $table .= "<tr>";

      $table .= "<td>" . showViewButton ( "showRequest.php?" . $__[ "showRequest" ] [ "params" ] [ "request" ] . "=" . $metadata[ "request" ],
                                          $__[ "showRequest" ][ "titles" ][ "viewRequest" ] ) . "</td>";

      if ( $recordingID == 0 )
      {
        $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
        $selectRecordingStatement->execute ( array (
          $connection[ "aufzeichnung" ] ) );
        $recording = $selectRecordingStatement->fetch ();
        $table .= $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                                    $tableID . $tableCounter );
      }

      $table .= foldableTableCell ( $metadata[ "feld" ],
                                    $tableID . $tableCounter );
      $table .= foldableTableCell ( $metadata[ "wert" ],
                                    $tableID . $tableCounter );

      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . $tableCounter . "\">" . $connection[ "_zeitd" ] . " <i class=\"fa fa-clock-o\"></i> </span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . $tableCounter . "\">" . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . "</span>" . $connection[ "_zeitt" ] . "<!--" . $connection[ "nr" ] . "--></td>";

      if ( !$connection[ "host" ] )
      {
        $table .= ipHostinfo ( $connection[ "ip" ],
                               $tableID . $tableCounter );
      }
      else
      {
        $table .= idHostinfo ( $connection[ "host" ],
                               $tableID . $tableCounter );
      }

      $table .= "</tr>";
    }
    while ( $metadata = $selectMetadataStatement->fetch () );

    $table .= "</tbody></table></div></div></div>";
  }

  if ( $table == "" )
  {
    return array (
      false,
      _ ( "Es sind keine Metadaten vorhanden." ) );
  }
  else
  {
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

titleAndHelp ( _ ( "Metadaten" ),
                   _ ( "Manche übertragenen Inhalte enthalten Meta-Daten, die bei einer Ansicht des Inhalts nicht oder nur schwer erkennbar sind. In HTML-Dateien sind dies z.B. Kommentare, in Bildern Informationen über die verwendete Kamera oder deren Standort.<br>Diese Auswertung macht solche Informationen leicht und übersichtlich zugänglich." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($metadataFound, $metadataTables) = tabulateMetadata ( 0,
                                                                $__[ "evaluateMetadata" ][ "ids" ][ "tables" ][ "metadata" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$metadataFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Metadaten aller Aufzeichnungen" ), (!$metadataFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$metadataTables</div></div></div>";
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
      list ($metadataFound, $metadataTables) = tabulateMetadata ( $recording[ "id" ],
                                                                  $__[ "evaluateMetadata" ][ "ids" ][ "tables" ][ "metadata" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateMetadata" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ 'id' ], "\"><h4 class=\"panel-title\">";
      echo (!$metadataFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Metadaten der Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> ") . _ ( "vom " ) . $recording[ "_start" ], (!$metadataFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateMetadata" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ 'id' ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">", $metadataTables, "</div></div></div>";
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

    list ($metadataFound, $metadataTables) = tabulateMetadata ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                                $__[ "evaluateMetadata" ][ "ids" ][ "tables" ][ "metadata" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo _ ( "Metadaten der Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> ") . _ ( "vom " ) . $recording[ "_start" ];
    echo "</h4></div><div class=\"panel-body\">$metadataTables</div></div></div>";
  }
}

include ("include/closeHTML.php");

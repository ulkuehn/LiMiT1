<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateHeaders.php
 * 
 * display http headers found in recorded data
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
 * build a table of all headers found in recording(s)
 * 
 * @param int $recordingID database id of a specific recording or 0 for all recordings
 * @param string $tableID id of the table to build
 * @return array if headers were found: (true, html code of table); if no headers: (false, some text)
 */
function tabulateHeaders ( $recordingID,
                           $tableID )
{
  global $db, $__;

  if ( $recordingID == 0 )
  {
    $selectHeaderStatement = $db->prepare ( "select feld,response from header group by feld,response" );
    $selectHeaderStatement->execute ();
  }
  else
  {
    $selectHeaderStatement = $db->prepare ( "select feld,response from header where aufzeichnung=? group by feld,response" );
    $selectHeaderStatement->execute ( array (
      $recordingID ) );
  }

  if ( ($header = $selectHeaderStatement->fetch ()) == false )
  {
    return array (
      false,
      _ ( "Es sind keine Header vorhanden." ) );
  }
  else
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID,
                                                     false );
    $table .= tableSorter ( $tableID,
                            "columns: [ {orderable:false, searchable:false}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]" );

    $table .= "<div class=\"table-responsive\"><table id=\"$tableID\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    $table .= "<th>" . _ ( "Header" ) . "</th>";
    $table .= "<th>" . _ ( "Werte" ) . "</th>";
    $table .= "<th></th></tr></thead><tbody>";

    do
    {
      if ( $recordingID == 0 )
      {
        $selectHeaderValuesStatement = $db->prepare ( "select distinct wert from header where feld=? and response=?" );
        $selectHeaderValuesStatement->execute ( array (
          $header[ "feld" ],
          $header[ "response" ] ) );
      }
      else
      {
        $selectHeaderValuesStatement = $db->prepare ( "select distinct wert from header where feld=? and response=? and aufzeichnung=?" );
        $selectHeaderValuesStatement->execute ( array (
          $header[ "feld" ],
          $header[ "response" ],
          $recordingID ) );
      }
      $headerValues = $selectHeaderValuesStatement->fetchAll ( PDO::FETCH_COLUMN,
                                                               0 );
      asort ( $headerValues,
              SORT_NATURAL | SORT_FLAG_CASE );

      $table .= "<tr>";

      $table .= "<td>" . showViewButton ( "showHeader.php?" . $__[ "showHeader" ] [ "params" ][ "value" ] . "=" . urlencode ( $header[ "feld" ] ) . "&" . $__[ "showHeader" ] [ "params" ][ "response" ] . "=" . $header[ "response" ] . "&" . $__[ "showHeader" ] [ "params" ][ "recording" ] . "=$recordingID",
                                                                                                                              $__[ "evaluateHeaders" ] [ "titles" ] [ "viewDetails" ] ) . "</td>";

      $table .= foldableTableCell ( $header[ "feld" ],
                                    $tableID );

      $table .= foldableTableCell ( implode ( "\n",
                                              array_values ( $headerValues ) ),
                                                             $tableID );

      $table .= "<td><div style=\"white-space: nowrap;\"><i class=\"fa " . ($header[ "response" ] ? "fa-globe" : "fa-home") . "\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa " . ($header[ "response" ] ? "fa-home" : "fa-globe") . "\"></i></div><!--" . ($header[ "response" ] ? 1 : 0) . "--></td>";

      $table .= "</tr>";
    }
    while ( $header = $selectHeaderStatement->fetch () );

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

titleAndHelp ( _ ( "HTTP-Header" ),
                   _ ( "In dieser Auswertung werden sämtliche übertragenen HTTP-Header und die dabei übermittleten Werte aufgelistet. Es werden sowohl Request- als auch Response-Header berücksichtigt.<br>Für jeden Header-Typ kann im Drill-Down das Vorkommen der verschiedenen Werte analysiert werden." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($headersFound, $headersTable) = tabulateHeaders ( 0,
                                                            $__[ "evaluateHeaders" ][ "ids" ][ "tables" ][ "headers" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$headersFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Header aller Aufzeichnungen" ), (!$headersFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$headersTable</div></div></div>";
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
      list ($headersFound, $headersTable) = tabulateHeaders ( $recording[ "id" ],
                                                              $__[ "evaluateHeaders" ][ "ids" ][ "tables" ][ "headers" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateHeaders" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";
      echo (!$headersFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Header der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$headersFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateHeaders" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">", $headersTable, "</div></div></div>";
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

    list ($headersFound, $headersTable) = tabulateHeaders ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                            $__[ "evaluateHeaders" ][ "ids" ][ "tables" ][ "headers" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$headersFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Header der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$headersFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$headersTable</div></div></div>";
  }
}

include ("include/closeHTML.php");

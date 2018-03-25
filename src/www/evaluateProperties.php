<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateProperties.php
 * 
 * used to search for properties of defined devices
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/tableUtility.php");
require_once ("include/searchUtility.php");
require_once ("include/filterUtility.php");
require_once ("include/connectDB.php");

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * find device properties in recorded data and return html table code
 * 
 * @global PDO $db database object
 * @global int $propertyIndex running index of search form over all tables
 * @global string $my_name system name
 * @global array $searchAreas properties of existing search areas
 * @param int $recordingID primary database key of the recording 
 * @param string $tableName name of the table
 * @return array if properties were found: true, html code of the table to show; else: false, text message that nothing was found 
 */
function tabulateProperties ( $recordingID,
                              $tableName )
{
  global $db, $propertyIndex, $my_name, $searchAreas, $__;

  /*
   * use all recordings
   */
  if ( $recordingID == 0 || "$recordingID" == "" )
  {
    $propertyStatement = $db->prepare ( "select geraet.name as geraet, eigenschaft.name as name, eigenschaft.wert as wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet" );
    $propertyStatement->execute ();
  }
  /*
   * limit to specific recording with primary key $recordingID
   */
  else
  {
    $propertyStatement = $db->prepare ( "select geraet.name as geraet, eigenschaft.name as name, eigenschaft.wert as wert from geraet,eigenschaft,aufzeichnung where geraet.id=eigenschaft.geraet and geraet.id=aufzeichnung.geraet and aufzeichnung.id=?" );
    $propertyStatement->execute ( array (
      $recordingID ) );
  }

  /*
   * no results
   */
  if ( ($property = $propertyStatement->fetch ()) == false )
  {
    return array (
      false,
      _ ( "Es wurden keine Eigenschaften definiert." ) );
  }
  /*
   * some results
   */
  else
  {
    /*
     * open a table
     */
    list ($result, $foldUnfoldButton) = tableFolder ( $tableName,
                                                      false );
    $result .= tableSorter ( $tableName,
                             "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {}, {orderable:false} ], order: [ [1,'asc'], [2,'asc'] ]" );

    $result .= "<div class=\"table-responsive\"><table id=\"$tableName\" class=\"table table-hover\"><thead><tr>";
    $result .= "<th>$foldUnfoldButton</th>";
    $result .= "<th>" . _ ( "Gerät" ) . "</th>";
    $result .= "<th>" . _ ( "Name" ) . "</th>";
    $result .= "<th>" . _ ( "Wert" ) . "</th>";
    $result .= "<th>" . _ ( "Funde" ) . "</th>";
    $result .= "<th>" . _ ( "Stellen" ) . "</th></tr></thead><tbody>";

    /*
     * do a search of each property's value
     */
    $totalSearchResults = 0;
    /*
     * iterate through all properties found
     */
    do
    {
      $searchResults = 0;
      $searchItems = array ();
      $searchString = $property[ "wert" ];
      /*
       * execute search in all search areas
       */
      foreach ( $searchAreas as $area => $areaInfo )
      {
        foreach ( $areaInfo[ 3 ] as $searchItem => $searches )
        {
          /*
           * compile sql query string and array
           */
          $searchQueryString = $searches[ 3 ];
          $searchQueryArray = array (
            $searchString );
          /*
           * for content related queries we must provide search string twice
           */
          if ( $searches[ 1 ] )
          {
            array_push ( $searchQueryArray,
                         $searchString );
          }
          /*
           * if search is limited to specific recording we must provide additional query and recording id
           */
          if ( $recordingID )
          {
            $searchQueryString .= $areaInfo[ 2 ];
            array_push ( $searchQueryArray,
                         $recordingID );
          }

          /*
           * count results for the current property
           */
          $searchStatement = $db->prepare ( $searchQueryString );
          $searchStatement->execute ( $searchQueryArray );
          $results = $searchStatement->fetchColumn ();
          $searchResults += $results;
          if ( $results )
          {
            array_push ( $searchItems,
                         "$results " . _ ( "mal" ) . " $searchItem (" . $areaInfo[ 0 ] . ")" );
          }
          #echo "<p><pre>\$searchQueryString\n", var_dump ( $searchQueryString ), "</pre></p>";
          #echo "<p><pre>\$searchQueryArray\n", var_dump ( $searchQueryArray ), "</pre></p>";
          #echo "<p><pre>\$searchItems\n", var_dump ( $searchItems ), "</pre></p>";
        }
      }

      /*
       * some results
       */
      if ( $searchResults )
      {
        $result .= "<tr>";

        $propertyIndex++;
        $result .= "<td><form method=\"post\" action=\"search.php\" id=\"" . $__[ "evaluateProperties" ] [ "ids" ] [ "searchPrefix" ] . $propertyIndex . "\" target=\"" . $my_name . $__[ "search" ][ "names" ] [ "frame" ] . "\"><input type=\"hidden\" name=\"" . $__[ "search" ][ "params" ] [ "areas" ] . "\" value=\"on\"><input type=\"hidden\" name=\"show\" value=\"" . ($recordingID ? $recordingID : $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ]) . "\"><input type=\"hidden\" name=\"" . $__[ "search" ] [ "params" ] [ "search" ] . "\" value=\"" . $property[ "wert" ] . "\"><a class=\"btn btn-info btn-xs\" href=\"search.php\" onclick=\"document.getElementById('" . $__[ "evaluateProperties" ] [ "ids" ] [ "searchPrefix" ] . $propertyIndex . "').submit(); return false;\" title=\"" . _ ( "Wert dieser Eigenschaft suchen" ) . "\"><i class=\"fa " . $__[ "search" ][ "values" ][ "icon" ] . " fa-lg\"></i></a></form></td>";

        $result .= foldableTableCell ( $property[ "geraet" ],
                                       $tableName );

        $result .= foldableTableCell ( $property[ "name" ],
                                       $tableName );

        $result .= foldableTableCell ( $property[ "wert" ],
                                       $tableName );

        $result .= "<td class=\"numeric\">$searchResults</td>";

        $result .= "<td>" . implode ( "<br>",
                                      $searchItems ) . "</td>";

        $result .= "</tr>";

        $totalSearchResults += $searchResults;
      }
    }
    while ( $property = $propertyStatement->fetch () );

    $result .= "</tbody></table></div>";

    return array (
      $totalSearchResults > 0,
      $totalSearchResults > 0 ? $result : _ ( "Keine der definierten Geräteeigenschaften wurden gefunden" ) );
  }
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/**
 * global counter for all properties processed and displayed; it is incremented by tabulateProperties
 * each property needs a unique number/id, so it can be addressed individually
 */
$propertyIndex = 0;

/*
 * set showing each recording in a separate table as default behaviour
 */
if ( !isset ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) )
{
  $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ];
}

titleAndHelp ( _ ( "Geräteeigenschaften" ),
                   _ ( "<p>Mit dieser Auswertung können gezielt Werte gesucht werden, die als Eigenschaften der verwalteten Geräte definiert wurden.</p><p>Es werden sämtliche Geräteeigenschaften aufgelistet und jeweils angegeben, ob das Gerät bzw. die Eigenschaft in einer der vorhandenen Aufzeichnungen verwendet wird.</p>" ) );

if ( recordingsSelector () )
{
  /*
   * results covering all recordings in a single table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($propertiesFound, $propertiesTable) = tabulateProperties ( 0,
                                                                     "propertyTableAllRecordings" );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Geräteeigenschaften aller Aufzeichnungen" ), "</h4></div><div class=\"panel-body\">$propertiesTable</div></div></div>";
  }

  /*
   * results of each recording in a separate table
   */
  else if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ] )
  {
    echo "<div class=\"row\"><div class=\"panel-group\" role=\"tablist\">";

    $recordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc" );
    $recordingStatement->execute ();

    /*
     * iterate through all recordings
     */
    while ( $recording = $recordingStatement->fetch () )
    {
      list ($propertiesFound, $propertiesTable) = tabulateProperties ( $recording[ "id" ],
                                                                       $__[ "evaluateProperties" ] [ "ids" ] [ "tables" ] [ "properties" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateProperties" ] [ "ids" ][ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";
      echo (!$propertiesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Geräteeigenschaften der Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> ") . _ ( "vom " ) . $recording[ "_start" ], (!$propertiesFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateProperties" ] [ "ids" ][ "recordingPanelPrefix" ], $recording[ "id" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">$propertiesTable</div></div></div>";
    }

    echo "</div></div>";
  }

  /*
   * only one specific recording
   */
  else
  {
    $recordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?" );
    $recordingStatement->execute ( array (
      $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) );
    $recording = $recordingStatement->fetch ();

    list ($propertiesFound, $propertiesTable) = tabulateProperties ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                                     $__[ "evaluateProperties" ] [ "ids" ] [ "tables" ] [ "properties" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$propertiesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Geräteeigenschaften der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$propertiesFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$propertiesTable</div></div></div>";
  }
}

include ("include/closeHTML.php");

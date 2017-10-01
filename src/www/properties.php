<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file properties.php
 * 
 * used to search for properties of defined devices
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
include ("include/htmlstart.php");
include ("include/topmenu.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * find device properties in recorded data and return html table code
 * 
 * @global PDO $db database object
 * @global int $nr running index of search form over all tables
 * @global string $my_name system name
 * @global array $suchOrte properties of existing search areas
 * @param int $recordingID primary database key of the recording 
 * @param string $tableName name of the table
 * @return array if properties were found: true, html code of the table to show; else: false, text message that nothing was found 
 */
function propertyTables ( $recordingID, $tableName )
{
  global $db, $nr, $my_name, $suchOrte;

  /*
   * use all recordings
   */
  if ( $recordingID == 0 )
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
    $propertyStatement->execute ( array ( $recordingID ) );
  }

  /*
   * no results
   */
  if ( ($property = $propertyStatement->fetch ()) == false )
  {
    return array ( false, _ ( "Es wurden keine Eigenschaften definiert." ) );
  }
  /*
   * some results
   */
  else
  {
    /*
     * open a table
     */
    list ($result, $foldMe) = tableFolder ( $tableName, false );
    $result .= tableSorter ( $tableName, "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]" );

    $result .= <<<LIMIT1
    <div class="table-responsive">
      <table id="$tableName" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
LIMIT1;
    $result .= "<th>" . _ ( "Gerät" ) . "</th>";
    $result .= "<th>" . _ ( "Name" ) . "</th>";
    $result .= "<th>" . _ ( "Wert" ) . "</th>";
    $result .= "<th>" . _ ( "Funde" ) . "</th>";
    $result .= <<<LIMIT1
          </tr>
        </thead>
        <tbody>
LIMIT1;

    $totalSearchResults = 0;
    /*
     * iterate through all properties found
     */
    do
    {
      $searchResults = 0;
      $needle = $property[ "wert" ];
      /*
       * execute search in all search areas
       */
      foreach ( $suchOrte as $searchArea => $searchAreaInfo )
      {
        /*
         * special case content date
         */
        if ( $searchArea == "Inhalte" )
        {
          /*
           * all recordings
           */
          if ( !$recordingID )
          {
            $searchStatement = $db->prepare ( $searchAreaInfo[ 3 ] );
            $searchStatement->execute ( array ( $needle, $needle ) );
          }
          /*
           * specific recording
           */
          else
          {
            $searchStatement = $db->prepare ( $searchAreaInfo[ 3 ] . " and aufzeichnung=?" );
            $searchStatement->execute ( array ( $needle, $needle, $recordingID ) );
          }
        }
        /*
         * other search areas
         */
        else
        {
          /*
           * all recordings
           */
          if ( !$recordingID )
          {
            $searchStatement = $db->prepare ( $searchAreaInfo[ 3 ] );
            $searchStatement->execute ( array ( $needle ) );
          }
          /*
           * specific recording
           */
          else
          {
            $searchStatement = $db->prepare ( $searchAreaInfo[ 3 ] . " and aufzeichnung=?" );
            $searchStatement->execute ( array ( $needle, $recordingID ) );
          }
        }
        /*
         * count results for the current property
         */
        $searchResults += $searchStatement->fetchColumn ();
      }

      /*
       * some results
       */
      if ( $searchResults )
      {
        $result .= "<tr>";

        $nr++;
        $show = $recordingID ? $recordingID : "alle";
        $result .= <<<LIMIT1
              <td>
                <form method="post" action="suche.php" id="suche$nr" target="${my_name}suche">
                  <input type="hidden" name="caseSwitch" value="on">
                  <input type="hidden" name="orte" value="alle">
                  <input type="hidden" name="show" value="$show">
                  <input type="hidden" name="search" value="{$property[ "wert" ]}">
                  <a class="btn btn-info btn-xs" href="suche.php" onclick="document.getElementById('suche$nr').submit(); return false;" title=
LIMIT1;
        $result .= _ ( "Wert dieser Eigenschaft suchen" );
        $result .= <<<LIMIT1
><i class="fa fa-search fa-lg"></i></a>
                </form>
              </td>
LIMIT1;

        $result .= faltZelle ( $property[ "geraet" ], $tableName );

        $result .= faltZelle ( $property[ "name" ], $tableName );

        $result .= faltZelle ( $property[ "wert" ], $tableName );

        $result .= "<td class=\"numeric\">$searchResults</td>";

        $result .= "</tr>";

        $totalSearchResults += $searchResults;
      }
    }
    while ( $property = $propertyStatement->fetch () );

    $result .= <<<LIMIT1
        </tbody>
      </table>
    </div>
LIMIT1;

    return array ( $totalSearchResults > 0, $totalSearchResults > 0 ? $result : _ ( "Keine der definierten Geräteeigenschaften wurden gefunden" ) );
  }
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * set showing each recording in a separate table as default behaviour
 */
if ( !isset ( $_REQUEST[ "show" ] ) )
{
  $_REQUEST[ "show" ] = "jede";
}

titleAndHelp ( _ ( "Geräteeigenschaften" ), _ ( "<p>Mit dieser Auswertung können gezielt Werte gesucht werden, die als Eigenschaften der verwalteten Geräte definiert wurden.</p><p>Es werden sämtliche Geräteeigenschaften aufgelistet und jeweils angegeben, ob das Gerät bzw. die Eigenschaft in einer der vorhandenen Aufzeichnungen verwendet wird.</p>" ) );

aufzeichnungsFilter ();
$nr = 0;

/*
 * results covering all recordings in a single table
 */
if ( $_REQUEST[ "show" ] === "alle" )
{
  list ($havingResults, $results) = propertyTables ( 0, "propertyTableAllRecordings" );

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo _ ( "Geräteeigenschaften aller Aufzeichnungen" );
  echo <<<LIMIT1
</h4>
      </div>
      <div class="panel-body">
        $results
      </div>
    </div>
  </div>        
LIMIT1;
}

/*
 * results of each recording in a separate table
 */
else if ( $_REQUEST[ "show" ] === "jede" )
{
  echo <<<LIMIT1
  <div class="row">
    <div class="panel-group" role="tablist">
LIMIT1;

  $recordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc" );
  $recordingStatement->execute ();

  /*
   * iterate through all recordings
   */
  while ( $recording = $recordingStatement->fetch () )
  {
    list ($havingResults, $results) = propertyTables ( $recording[ "id" ], "propertyTableRecording" . $recording[ "id" ] );

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#recording{$recording[ 'id' ]}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$havingResults ? "<span class=\"emptyPanel\">" : ""), _ ( "Geräteeigenschaften der Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> ") . _ ( "vom " ) . $recording[ "_start" ], (!$havingResults ? "</span>" : "");
    echo <<<LIMIT1
          </h4>
        </div>
        <div id="recording{$recording[ 'id' ]}" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body">
            $results
LIMIT1;
    echo <<<LIMIT1
          </div>
        </div>
      </div>
LIMIT1;
  }

  echo <<<LIMIT1
    </div>
  </div>
LIMIT1;
}

/*
 * only one specific recording
 */
else
{
  $recordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?" );
  $recordingStatement->execute ( array ( $_REQUEST[ "show" ] ) );
  $recording = $recordingStatement->fetch ();

  list ($havingResults, $results) = propertyTables ( $_REQUEST[ "show" ], "propertyTableRecording" . $_REQUEST[ "show" ] );

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo _ ( "Geräteeigenschaften der Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> ") . _ ( "vom " ) . $recording[ "_start" ];
  echo <<<LIMIT1
        </h4>
      </div>
      <div class="panel-body">
        $results
      </div>
    </div>
  </div>        
LIMIT1;
}

include ("include/htmlend.php");

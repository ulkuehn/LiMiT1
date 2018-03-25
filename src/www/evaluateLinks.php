<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateLinks.php
 *
 * display sites that are linked by referrers or other means, possibly indicating some connection between the site operators
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

function tabulateLinks ( $recordingID,
                         $tableID )
{
  global $db, $__;

  $connectSourceTarget = array ();

  /*
   * type "referer": client specifies in request header to connected server (target) URL of server referal origins from (source)
   * type "origin": same as for "referer"
   */
  if ( $recordingID == 0 )
  {
    $selectLinkStatement = $db->prepare ( "select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and (feld=? or feld=?)" );
    $selectLinkStatement->execute ( array (
      "Referer",
      "Origin" ) );
  }
  else
  {
    $selectLinkStatement = $db->prepare ( "select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and (feld=? or feld=?) and header.aufzeichnung=?" );
    $selectLinkStatement->execute ( array (
      "Referer",
      "Origin",
      $recordingID ) );
  }

  while ( $link = $selectLinkStatement->fetch () )
  {
    if ( $link[ "host" ] )
    {
      $targetHost = $link[ "host" ];
    }
    else
    {
      $selectTargetHostStatement = $db->prepare ( "select id from host where ip=?" );
      $selectTargetHostStatement->execute ( array (
        $link[ "ip" ] ) );
      $targetHost = $selectTargetHostStatement->fetchColumn ();
    }
    $targetHostName = array_shift ( idHostinfo ( $targetHost ) );

    if ( ($sourceHostName = parse_url ( $link[ "wert" ] )[ "host" ]) != false || ($sourceHostName = parse_url ( "http://" . $link[ "wert" ] )[ "host" ]) != false )
    {
      $selectSourceHostStatement = $db->prepare ( "select id from host where name=?" );
      $selectSourceHostStatement->execute ( array (
        $sourceHostName ) );
      if ( $sourceHost = $selectSourceHostStatement->fetchColumn () )
      {
        $sourceHostName = array_shift ( idHostinfo ( $sourceHost ) );
      }
      if ( $sourceHostName != $targetHostName )
      {
        $connectSourceTarget[ $sourceHostName ][ $targetHostName ] = array ();
      }
    }
  }


  /*
   * type "location": server (source) specifies in response header connected server (target)
   */
  if ( $recordingID == 0 )
  {
    $selectLinkStatement = $db->prepare ( "select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and feld=?" );
    $selectLinkStatement->execute ( array (
      "Location" ) );
  }
  else
  {
    $selectLinkStatement = $db->prepare ( "select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and feld=? and header.aufzeichnung=?" );
    $selectLinkStatement->execute ( array (
      "Location",
      $recordingID ) );
  }

  while ( $link = $selectLinkStatement->fetch () )
  {
    if ( $link[ "host" ] )
    {
      $sourceHost = $link[ "host" ];
    }
    else
    {
      $selectSourceHostStatement = $db->prepare ( "select id from host where ip=?" );
      $selectSourceHostStatement->execute ( array (
        $link[ "ip" ] ) );
      $sourceHost = $selectSourceHostStatement->fetchColumn ();
    }
    $sourceHostName = array_shift ( idHostinfo ( $sourceHost ) );

    if ( ($targetHostName = parse_url ( $link[ "wert" ] )[ "host" ]) != false || ($targetHostName = parse_url ( "http://" . $link[ "wert" ] )[ "host" ]) != false )
    {
      $selectTargetHostStatement = $db->prepare ( "select id from host where name=?" );
      $selectTargetHostStatement->execute ( array (
        $targetHostName ) );
      if ( $targetHost = $selectTargetHostStatement->fetchColumn () )
      {
        $targetHostName = array_shift ( idHostinfo ( $targetHost ) );
      }
      if ( $sourceHostName != $targetHostName )
      {
        $connectSourceTarget[ $sourceHostName ][ $targetHostName ] = array ();
      }
    }
  }

  /*
   * add indirect connections, i.e. if a connects to b and b connects to c, a connects to c as well (if c is not identical to a)
   */
  do
  {
    $connectionsAdded = 0;
    foreach ( $connectSourceTarget as $sourceHost => $targets )
    {
      foreach ( $targets as $targetHost1 => $targets )
      {
        foreach ( $connectSourceTarget[ $sourceHost ][ $targetHost1 ] as $targetHost2 => $targets )
        {
          if ( $targetHost2 != $sourceHost && !array_key_exists ( $targetHost2,
                                                                  $connectSourceTarget[ $sourceHost ] ) )
          {
            $connectSourceTarget[ $sourceHost ][ $targetHost2 ] = array_merge ( $connectSourceTarget[ $sourceHost ][ $targetHost1 ],
                                                                                array (
              $targetHost1 ),
                                                                                $connectSourceTarget[ $targetHost1 ][ $targetHost2 ] );
            $connectionsAdded++;
          }
        }
      }
    }
  }
  while ( $connectionsAdded );

  if ( !count ( $connectSourceTarget ) )
  {
    return array (
      false,
      _ ( "Es sind keine Verweise vorhanden." ) );
  }
  else
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID,
                                                     false );
    $table .= tableSorter ( $tableID,
                            "columns: [ {orderable:false, searchable:false}, {}, {}, {} ], order: [ [1,'asc'] ]" );

    $table .= "<div class=\"table-responsive\"><table id=\"$tableID\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    $table .= "<th>" . _ ( "von Server" ) . "</th>";
    $table .= "<th>" . _ ( "auf Server" ) . "</th>";
    $table .= "<th>" . _ ( "über" ) . "</th></tr></thead><tbody>";

    foreach ( $connectSourceTarget as $sourceHost => $targets )
    {
      foreach ( $targets as $targetHost => $vias )
      {
        $table .= "<tr>";

        /*
         * drill down
         */
        $table .= "<td>" . showViewButton ( "showLink.php?" . $__[ "showLink" ][ "params" ][ "source" ] . "=$sourceHost&" . $__[ "showLink" ][ "params" ][ "target" ] . "=$targetHost&" . $__[ "showLink" ][ "params" ][ "recording" ] . "=$recordingID",
                                            _ ( "Verweisdetails ansehen" ) ) . "</td>";

        /*
         * source
         */
        if ( $hostInfo = nameHostinfo ( $sourceHost,
                                        $tableID ) )
        {
          $table .= $hostInfo;
        }
        else
        {
          $table .= foldableTableCell ( $sourceHost,
                                        $tableID );
        }

        /*
         * target
         */
        if ( $hostInfo = nameHostinfo ( $targetHost,
                                        $tableID ) )
        {
          $table .= $hostInfo;
        }
        else
        {
          $table .= foldableTableCell ( $targetHost,
                                        $tableID );
        }

        /*
         * intermediate hosts for indirect connections
         */
        $intermediates = array ();
        $table .= "<td" . onTableToggleEvent ( $tableID ) . ">";
        foreach ( $vias as $viaHost )
        {
          if ( $hostInfo = nameHostinfo ( $viaHost ) )
          {
            $auth = array_shift ( $hostInfo );
            array_push ( $intermediates,
                         whoisify ( $auth ) . "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\"><br>(" . implode ( " * ",
                                                                                                                                                                array_map ( "whoisify",
                                                                                                                                                                            $hostInfo ) ) . ")</span>" );
          }
          else
          {
            array_push ( $intermediates,
                         whoisify ( $viaHost ) );
          }
        }
        $table .= implode ( "<br>",
                            $intermediates ) . "<!--" . (count ( $vias ) + 1) . "--></td>";
        $table .= "</tr>";
      }
    }
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

titleAndHelp ( _ ( "Verweise" ),
                   _ ( "Diese Auswertung betrachtet Verbindungen zwischen Servern, die durch explizite Verweise hergestellt werden. Solche Verweise können durch Referer-Angaben, Origin- oder Location-Header hergestellt werden.<br>Referer-Verbindungen entstehen, wenn in eine HTML-Seite Elemente eines anderen Servers eingebunden sind, etwa ein Bild oder ein iFrame.<br>Origin- und Location-Header deuten auf eine Weiterleitung von einem Server zu einem anderen hin.<br>Neben den direkten Verbindungen über diese Elemente sind auch die indirekten aufgelistet (wenn a&rarr;b und b&rarr;c, dann auch a&rarr;c). In der Spalte \"über\" ist erkennbar, über welche anderen Server diese Verbindung hergestellt wird." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($linksFound, $linksTable) = tabulateLinks ( 0,
                                                      $__[ "evaluateLinks" ][ "ids" ][ "tables" ][ "links" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$linksFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Verweise aller Aufzeichnungen" ), (!$linksFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$linksTable</div></div></div>";
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
      list ($linksFound, $linksTable) = tabulateLinks ( $recording[ "id" ],
                                                        $__[ "evaluateLinks" ][ "ids" ][ "tables" ][ "links" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateLinks" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";
      echo (!$linksFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Verweise der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$linksFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateLinks" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ 'id' ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">$linksTable</div></div></div>";
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

    list ($linksFound, $linksTable) = tabulateLinks ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                      $__[ "evaluateLinks" ][ "ids" ][ "tables" ][ "links" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$linksFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Verweise der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$linksFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$linksTable</div></div></div>";
  }
}

include ("include/closeHTML.php");

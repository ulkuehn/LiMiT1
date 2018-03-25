<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateCookies.php
 *
 * display all cookies in a specific or all recordings
 * covers cookies received by the monitored device as well as cookies sent by the device
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

function tabulateCookies ( $recordingID,
                           $tableID )
{
  global $__, $db;

  $tableRows = "";
  $selectCookieStatement = $db->prepare ( "select * from cookie" );
  $selectCookieStatement->execute ();
  while ( $cookie = $selectCookieStatement->fetch () )
  {
    if ( $recordingID == 0 )
    {
      $selectSetCookiesStatement = $db->prepare ( "select count(*) from setcookie where cookie=?" );
      $selectSetCookiesStatement->execute ( array (
        $cookie[ "id" ] ) );
      $setCookies = $selectSetCookiesStatement->fetchColumn ();
      $selectSentCookiesStatement = $db->prepare ( "select count(*) from sendcookie where cookie=?" );
      $selectSentCookiesStatement->execute ( array (
        $cookie[ "id" ] ) );
      $sentCookies = $selectSentCookiesStatement->fetchColumn ();
    }
    else
    {
      $selectSetCookiesStatement = $db->prepare ( "select count(*) from setcookie where cookie=? and aufzeichnung=?" );
      $selectSetCookiesStatement->execute ( array (
        $cookie[ "id" ],
        $recordingID ) );
      $setCookies = $selectSetCookiesStatement->fetchColumn ();
      $selectSentCookiesStatement = $db->prepare ( "select count(*) from sendcookie where cookie=? and aufzeichnung=?" );
      $selectSentCookiesStatement->execute ( array (
        $cookie[ "id" ],
        $recordingID ) );
      $sentCookies = $selectSentCookiesStatement->fetchColumn ();
    }

    if ( $setCookies || $sentCookies )
    {
      $tableRows .= "<tr>";
      $tableRows .= "<td>" . showViewButton ( "showCookie.php?" . $__[ "showCookie" ][ "params" ][ "cookie" ] . "=" . $cookie[ "id" ] . "&" . $__[ "showCookie" ][ "params" ][ "recording" ] . "=$recordingID",
                                              $__[ "evaluateCookies" ][ "titles" ][ "viewCookie" ] ) . "</td>";
      $tableRows .= foldableTableCell ( $cookie[ "name" ],
                                        $tableID );

      $cookieSiteParts = explode ( ".",
                                   $cookie[ "site" ] );
      $topLevelDomain = array_pop ( $cookieSiteParts );
      $domain = array_pop ( $cookieSiteParts );
      array_push ( $cookieSiteParts,
                   $domain . "." . $topLevelDomain );
      $hostInfo = nameHostinfo ( $cookie[ "site" ],
                                 $tableID );
      $tableRows .= $hostInfo == false ? foldableTableCellSorting ( $cookie[ "site" ],
                                                                    $tableID,
                                                                    true,
                                                                    implode ( " ",
                                                                              array_reverse ( $cookieSiteParts ) ) ) : $hostInfo;

      $tableRows .= "<td class=\"numeric\">$setCookies " . _ ( "mal" ) . "<!--$setCookies--></td>";
      $tableRows .= "<td class=\"numeric\">$sentCookies " . _ ( "mal" ) . "<!--$sentCookies--></td>";
      $tableRows .= "</tr>";
    }
  }

  if ( $tableRows == "" )
  {
    return array (
      false,
      _ ( "Es wurden weder Cookies empfangen noch versandt." ) );
  }
  else
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID,
                                                     false );
    $table .= tableSorter ( $tableID,
                            "columns: [ {orderable:false, searchable:false}, {}, {}, {type:'num'}, {type:'num'} ], order: [ [1,'asc'] ]" );

    $table .= "<div class=\"table-responsive\"><table id=\"$tableID\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    $table .= "<th>" . _ ( "Name" ) . "</th>";
    $table .= "<th>" . _ ( "Site" ) . "</th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $__[ "evaluateContents" ] [ "values" ] [ "incomingIcon" ] . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "empfangen" ) . "</span></th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $__[ "evaluateContents" ] [ "values" ] [ "outgoingIcon" ] . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "versandt" ) . "</span></th></tr></thead><tbody>$tableRows</tbody></table></div>";

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

titleAndHelp ( _ ( "Cookies" ),
                   _ ( "Mit dieser Auswertung lassen sich die Cookies erkennen, die in den aufgezeichneten Verbindungen enthalten sind.<br>Dabei werden sowohl empfangene Cookies (d.h. solche, die von Internet-Servern stammen) berücksichtigt als auch versandte Cookies (d.h. solche, die an Internet-Server zurückübermittelt wurden).<br>Sind mehrere Aufzeichnungen vorhanden, kann die Auswertung auf einzelne Aufzeichnungen begrenzt werden oder die Cookies sämtlicher Aufzeichnungen in eine gemeinsamen Tabelle dargestellt werden." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($cookiesFound, $cookiesTable) = tabulateCookies ( 0,
                                                            $__[ "evaluateCookies" ] [ "ids" ] [ "tables" ] [ "cookies" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$cookiesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Cookies aller Aufzeichnungen" ), (!$cookiesFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$cookiesTable</div></div></div>";
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
      list ($cookiesFound, $cookiesTable) = tabulateCookies ( $recording[ "id" ],
                                                              $__[ "evaluateCookies" ] [ "ids" ] [ "tables" ] [ "cookies" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateCookies" ][ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";

      echo (!$cookiesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Cookies der Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> ") . _ ( "vom " ) . $recording[ "_start" ], (!$cookiesFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateCookies" ][ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">$cookiesTable</div></div></div>";
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

    list ($cookiesFound, $cookiesTable) = tabulateCookies ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                            $__[ "evaluateCookies" ] [ "ids" ] [ "tables" ] [ "cookies" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$cookiesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Cookies der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$cookiesFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$cookiesTable</div></div></div>";
  }
}

include ("include/closeHTML.php");

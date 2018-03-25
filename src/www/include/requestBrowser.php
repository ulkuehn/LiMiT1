<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/requestBrowser.php
 * 
 * used to show the currently selected request and to provide access to all available requests of the currently selected connection
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
require_once ("include/httpStatusUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

if ( isset ( $_GET[ $__[ "showRecording" ][ "params" ][ "request" ] ] ) )
{
  $selectRequestStatement = $db->prepare ( "select * from request where id=?" );
  $selectRequestStatement->execute ( array (
    $_GET[ $__[ "showRecording" ][ "params" ][ "request" ] ] ) );
  if ( $request = $selectRequestStatement->fetch () )
  {
    /*
     * one request back
     */
    $selectPreviousRequestStatement = $db->prepare ( "select id from request where id<? and verbindung=? order by id desc limit 1" );
    $selectPreviousRequestStatement->execute ( array (
      $request[ "id" ],
      $request[ "verbindung" ] ) );
    $previousRequest = $__[ "include/recordingBrowser" ][ "values" ][ "backButtonInactive" ];
    if ( $id = $selectPreviousRequestStatement->fetchColumn () )
    {
      $previousRequest = "<a href=\"showRequest.php?" . $__[ "showRecording" ] [ "params" ] [ "request" ] . "=$id\">" . $__[ "include/recordingBrowser" ][ "values" ][ "backButton" ] . "</a>";
    }

    /*
     * one request forward
     */
    $selectNextRequestStatement = $db->prepare ( "select id from request where id>? and verbindung=? order by id asc limit 1" );
    $selectNextRequestStatement->execute ( array (
      $request[ "id" ],
      $request[ "verbindung" ] ) );
    $nextRequest = $__[ "include/recordingBrowser" ][ "values" ][ "forwardButtonInactive" ];
    if ( $id = $selectNextRequestStatement->fetchColumn () )
    {
      $nextRequest = "<a href=\"showRequest.php?" . $__[ "showRecording" ] [ "params" ] [ "request" ] . "=$id\">" . $__[ "include/recordingBrowser" ][ "values" ][ "forwardButton" ] . "</a>";
    }

    /*
     * request info table
     */
    $foldMe = tableFolder ( $__[ "include/requestBrowser" ][ "ids" ][ "tables" ][ "request" ] );

    echo "<div class=\"row nestedPanel\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">$previousRequest $nextRequest ", _ ( "Request" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "include/requestBrowser" ][ "ids" ][ "tables" ][ "request" ], "\" class=\"table\"><thead><tr>";
    echo "<th>$foldMe</th>";
    echo "<th>", _ ( "Methode" ), "</th>";
    echo "<th>", _ ( "URI" ), "</th>";
    echo "<th>", _ ( "Response" ), "</th></tr></thead><tbody>";

    $selectResponseStatement = $db->prepare ( "select * from response where request=?" );
    $selectResponseStatement->execute ( array (
      $request[ "id" ] ) );
    $response = $selectResponseStatement->fetch ();

    echo "<tr>";
    echo "<td>", showViewButton ( "showRequest.php?" . $__[ "showRecording" ] [ "params" ] [ "request" ] . "=" . $request[ "id" ],
                              $__[ "verbindung" ][ "titles" ][ "viewRequest" ] ), "</td>";
    echo "<td>", $request[ "methode" ], "</td>";
    echo foldableTableCell ( $request[ "uri" ],
                             $__[ "include/requestBrowser" ][ "ids" ][ "tables" ][ "request" ] );
    echo "<td>", httpStatusBadge ( $response[ "status" ] ), " ", $response[ "status" ], " ", $response[ "statustext" ], "</td>";

    echo "</tr></tbody></table></div></div></div></div>";

    $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ] = $request;
    $$__[ "include/requestBrowser" ][ "vars" ] [ "response" ] = $response;
  }
}

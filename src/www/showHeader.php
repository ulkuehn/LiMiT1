<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file showHeader.php
 * 
 * display details of a http header (drill down for evaluateHeaders.php)
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
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

titleAndHelp ( _ ( "Headerdetails" ) );

$foldUnfoldButton = tableFolder ( $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "details" ] );

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Header" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "details" ], "\" class=\"table table-hover\"><tbody>";
echo "<tr><td>", _ ( "Name" ), "</td><td>", $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "value" ] ], "</td></tr>";
echo "<tr><td>", _ ( "Versand" ), "</td><td><i class=\"fa ", ($_REQUEST[ $__[ "showHeader" ] [ "params" ][ "response" ] ] ? "fa-globe" : "fa-home"), "\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa ", ($_REQUEST[ $__[ "showHeader" ] [ "params" ][ "response" ] ] ? "fa-home" : "fa-globe"), "\"></i> (", ($_REQUEST[ $__[ "showHeader" ] [ "params" ][ "response" ] ] ? _ ( "Response-Header" ) : _ ( "Request-Header" )), ")</td></tr></tbody></table></div></div></div></div>";

recordingsScope ( $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "recording" ] ] );

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Verwendungen" ), "</h4></div><div class=\"panel-body\">";

if ( $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "recording" ] ] == 0 )
{
  $selectHeaderStatement = $db->prepare ( "select * from header where feld=? and response=?" );
  $selectHeaderStatement->execute ( array (
    $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "value" ] ],
    $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "response" ] ] ) );
}
else
{
  $selectHeaderStatement = $db->prepare ( "select * from header where feld=? and response=? and aufzeichnung=?" );
  $selectHeaderStatement->execute ( array (
    $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "value" ] ],
    $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "response" ] ],
    $_REQUEST[ $__[ "showHeader" ] [ "params" ][ "recording" ] ] ) );
}

if ( ($header = $selectHeaderStatement->fetch ()) == false )
{
  echo _ ( "Der Header wurde nicht verwendet." );
}
else
{
  echo tableSorter ( $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ],
                     "columns: [ {orderable:false, searchable:false}" . (!$_REQUEST[ $__[ "showHeader" ] [ "params" ][ "recording" ] ] ? ", {}" : "") . ", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );
  $foldUnfoldButton = tableFolder ( $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldUnfoldButton</th>";
  if ( !$_REQUEST[ $__[ "showHeader" ] [ "params" ][ "recording" ] ] )
  {
    echo "<th>", _ ( "Aufzeichnung" ), "</th>";
  }
  echo "<th>", _ ( "Wert" ), "</th>";
  echo "<th>", _ ( "Server" ), "</th>";
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th></th>";
  echo "<th>", _ ( "Request" ), "</th></tr></thead><tbody>";

  do
  {
    $selectConnectionIDStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?" );
    $selectConnectionIDStatement->execute ( array (
      $header[ "verbindung" ] ) );
    $connection = $selectConnectionIDStatement->fetch ();

    $selectRequestStatement = $db->prepare ( "select * from request where id=?" );
    $selectRequestStatement->execute ( array (
      $header[ "request" ] ) );
    $request = $selectRequestStatement->fetch ();

    echo "<tr>";

    echo "<td>", showViewButton ( "showRequest.php?" . $__[ "showRequest" ] [ "params" ] [ "request" ] . "=" . $header[ "request" ],
                              $__[ "showRequest" ] [ "titles" ][ "viewRequest" ] ), "</td>";

    if ( !$_REQUEST[ $__[ "showHeader" ] [ "params" ][ "recording" ] ] )
    {
      $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
      $selectRecordingStatement->execute ( array (
        $connection[ "aufzeichnung" ] ) );
      $recording = $selectRecordingStatement->fetch ();
      echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                             $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ] );
    }

    echo foldableTableCell ( $header[ "wert" ],
                             $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ] );

    if ( !$connection[ "host" ] )
    {
      echo ipHostinfo ( $connection[ "ip" ],
                        $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ] );
    }
    else
    {
      echo idHostinfo ( $connection[ "host" ],
                        $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ] );
    }

    echo "<td>", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", $connection[ "_zeitt" ], "</td>";

    echo "<td>", ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl" ? "<i class=\"fa fa-key\"></i>" : ""), "</td>";

    echo foldableTableCell ( $request[ "methode" ] . " " . $request[ "uri" ],
                             $__[ "showHeader" ] [ "ids" ] [ "tables" ] [ "usage" ] );

    echo "</tr>";
  }
  while ( $header = $selectHeaderStatement->fetch () );

  echo "</tbody></table></div>";
}

echo "</div></div></div>";

include ("include/closeHTML.php");

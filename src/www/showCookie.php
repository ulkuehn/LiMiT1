<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file showCookie.php
 *
 * display the specifics of one cookie which has been recorded
 * the cookie is identified by its id in the respective database table
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

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

$_ansehen = "Request ansehen";

$selectCookieStatement = $db->prepare ( "select * from cookie where id=?" );
$selectCookieStatement->execute ( array (
  $_REQUEST[ $__[ "showCookie" ][ "params" ][ "cookie" ] ] ) );
$cookie = $selectCookieStatement->fetch ();

titleAndHelp ( _ ( "Cookiedetails" ),
                   _ ( "In dieser Auswertung sind sämtliche Übertragungen des Cookies" ) . " <strong>" . $cookie[ "name" ] . "</strong> " . _ ( "von der Site" ) . " <strong>" . $cookie[ "site" ] . "</strong> " . _ ( "berücksichtigt. \"Site\" bezeichnet dabei den Domain-Parameter des Set-Cookie-Headers, mit dem das Cookie gesetzt wird. Er kann aus einer Domain- oder einer Server-Angabe bestehen.<br>Ein Cookie wird typischerweise nur einmal empfangen. Allerdings kann dies auch öfter erfolgen, wenn der Wert oder die Gültigkeitsdauer des Cookies geändert werden soll. Ausnahmsweise ist es auch möglich, dass ein Cookie im Rahmen der Aufzeichnung überhaupt nicht empfangen wurde. Dies kann daran liegen, dass das Cookie bereits im Browser vorhanden war oder dass es nicht über einen Set-Cookie-Header, sondern per Javascript erzeugt wird." ) );

$foldUnfoldButton = tableFolder ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "cookie" ] );

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Cookie" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showCookie" ][ "ids" ][ "tables" ][ "cookie" ], "\" class=\"table table-hover\"><tbody>";
echo "<tr><td>", _ ( "Name" ), "</td><td>", $cookie[ "name" ], "</td></tr>";
echo "<tr><td>", _ ( "Site" ), "</td><td>", whoisify ( $cookie[ "site" ] ), "</td></tr></tbody></table></div></div></div></div>";

recordingsScope ( $_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] );


/*
 *
 * received
 *
 */
echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", $__[ "evaluateContents" ] [ "values" ] [ "incomingIcon" ], _ ( " Empfangen" ), "</h4></div><div class=\"panel-body\">";

if ( $_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] == 0 )
{
  $selectSetCookieStatement = $db->prepare ( "select *,date_format(expires,'%e.%c.%Y %H:%i') as _expires from setcookie where cookie=?" );
  $selectSetCookieStatement->execute ( array (
    $cookie[ "id" ] ) );
}
else
{
  $selectSetCookieStatement = $db->prepare ( "select *,date_format(expires,'%e.%c.%Y %H:%i') as _expires from setcookie where cookie=? and aufzeichnung=?" );
  $selectSetCookieStatement->execute ( array (
    $cookie[ "id" ],
    $_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] ) );
}

if ( ($setCookie = $selectSetCookieStatement->fetch ()) == false )
{
  echo _ ( "Das Cookie wurde nicht empfangen." );
}
else
{
  echo tableSorter ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ],
                     "columns: [ {orderable:false, searchable:false}" . (!$_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] ? ", {}" : "") . ", {}, {}, {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );
  $foldUnfoldButton = tableFolder ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldUnfoldButton</th>";
  if ( !$_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] )
  {
    echo "<th>", _ ( "Aufzeichnung" ), "</th>";
  }
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th>";
  echo "<th>", _ ( "von Server" ), "</th>";
  echo "<th></th>";
  echo "<th>", _ ( "Request" ), "</th>";
  echo "<th>", _ ( "Pfad" ), "</th>";
  echo "<th>", _ ( "Verfall" ), "</th>";
  echo "<th><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", _ ( "Dauer" ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", _ ( "Speicherdauer" ), "</span></th>";
  echo "<th><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", _ ( "Eig." ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", _ ( "Eigenschaften" ), "</span></th></tr></thead><tbody>";

  do
  {
    $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung,request where request.verbindung=verbindung.id and request.id=?" );
    $selectConnectionStatement->execute ( array (
      $setCookie[ "request" ] ) );
    $connection = $selectConnectionStatement->fetch ();

    /*
     * request access
     */
    echo "<tr><td>", showViewButton ( "showRequest.php?" . $__[ "showRequest" ][ "params" ][ "request" ] . "=" . $setCookie[ "request" ],
                                  $_ansehen ), "</td>";

    /*
     * recording
     */
    if ( !$_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] )
    {
      $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
      $selectRecordingStatement->execute ( array (
        $connection[ "aufzeichnung" ] ) );
      $recording = $selectRecordingStatement->fetch ();
      echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                             $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] );
    }

    /*
     * time
     */
    echo "<td" . onTableToggleEvent ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] ) . "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span>", $connection[ "_zeitt" ], "<!--", $connection[ "nr" ], "--></td>";

    /*
     * value
     */
    echo foldableTableCell ( $setCookie[ "wert" ],
                             $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] );

    /*
     * host
     */
    if ( !$connection[ "host" ] )
    {
      echo ipHostinfo ( $connection[ "ip" ],
                        $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] );
    }
    else
    {
      echo idHostinfo ( $connection[ "host" ],
                        $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] );
    }

    /*
     * crypted?
     */
    echo "<td>", ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl" ? "<i class=\"fa fa-key\"></i>" : ""), "</td>";

    /*
     * request URL
     */
    echo foldableTableCell ( $connection[ "methode" ] . " " . $connection[ "uri" ],
                             $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] );

    /*
     * path
     */
    echo "<td>", $setCookie[ "domain" ], $setCookie[ "path" ], "</td>";

    /*
     * expiration
     */
    if ( $setCookie[ "expires" ] < 0 )
    {
      echo "<td>?</td>";
    }
    else if ( $setCookie[ "expires" ] == 0 )
    {
      echo "<td></td>";
    }
    else
    {
      echo "<td", onTableToggleEvent ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] ), "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", explode ( " ",
                                                                                                                                                                                                                                                         $setCookie[ "_expires" ] )[ 0 ], $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", $setCookie[ "_expires" ], "</span><!--", $setCookie[ "expires" ], "--></td>";
    }

    /*
     * validity
     */
    if ( $setCookie[ "valid" ] < 0 )
    {
      echo "<td>?<!--0--></td>";
    }
    else if ( $setCookie[ "valid" ] == 0 )
    {
      echo "<td>", _ ( "Session" ), "<!--0--></td>";
    }
    else
    {
      $timeSpan = humanReadableTimeSpan ( $setCookie[ "valid" ] );
      echo "<td", onTableToggleEvent ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ] ), "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", explode ( ",",
                                                                                                                                                                                                                                                         $timeSpan )[ 0 ], $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">$timeSpan</span><!--", $setCookie[ "valid" ], "--></td>";
    }

    /*
     * flags
     */
    echo "<td>";
    if ( $setCookie[ "httponly" ] )
    {
      echo "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\"><i class=\"fa fa-code\" title=\"httponly\"></i></span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", _ ( "für Skripte nicht zugänglich (\"httponly\")" ), "</span><br>";
    }
    if ( $setCookie[ "secure" ] )
    {
      echo "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\"><i class=\"fa fa-key\" title=\"secure\"></i></span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", _ ( "nur verschlüsselter Versand (\"secure\")" ), "</span><br>";
    }
    if ( $setCookie[ "comment" ] )
    {
      echo "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\"><i class=\"fa fa-commenting-o\" title=\"comment\"></i></span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "setcookie" ], "\">", _ ( "Kommentar" ), ": \"", $setCookie[ "comment" ], "\"</span>";
    }
    echo "</td>";

    echo "</tr>";
  }
  while ( $setCookie = $selectSetCookieStatement->fetch () );

  echo "</tbody></table></div>";
}
echo "</div></div></div>";


/*
 *
 * sent
 *
 */
echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", $__[ "evaluateContents" ] [ "values" ] [ "outgoingIcon" ], _ ( " Versandt" ), "</h4></div><div class=\"panel-body\">";

if ( $_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] == 0 )
{
  $selectSendCookieStatement = $db->prepare ( "select * from sendcookie where cookie=?" );
  $selectSendCookieStatement->execute ( array (
    $cookie[ "id" ] ) );
}
else
{
  $selectSendCookieStatement = $db->prepare ( "select * from sendcookie where cookie=? and aufzeichnung=?" );
  $selectSendCookieStatement->execute ( array (
    $cookie[ "id" ],
    $_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] ) );
}

if ( ($sendCookie = $selectSendCookieStatement->fetch ()) == false )
{
  echo _ ( "Das Cookie wurde nicht versandt." );
}
else
{
  echo tableSorter ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ],
                     "columns: [ {orderable:false, searchable:false}" . (!$_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] ? ", {}" : "") . ", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );
  $foldUnfoldButton = tableFolder ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldUnfoldButton</th>";
  if ( !$_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] )
  {
    echo "<th>", _ ( "Aufzeichnung" ), "</th>";
  }
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th>";
  echo "<th>", _ ( "an Server" ), "</th>";
  echo "<th></th>";
  echo "<th>", _ ( "Request" ), "</th></tr></thead><tbody>";

  do
  {
    $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung,request where request.verbindung=verbindung.id and request.id=?" );
    $selectConnectionStatement->execute ( array (
      $sendCookie[ "request" ] ) );
    $connection = $selectConnectionStatement->fetch ();

    echo "<tr>";

    /*
     * request access
     */
    echo "<td>", showViewButton ( "showRequest.php?" . $__[ "showRequest" ] [ "params" ] [ "request" ] . "=" . $sendCookie[ "request" ],
                              $_ansehen ), "</td>";

    /*
     * recording
     */
    if ( !$_REQUEST[ $__[ "showCookie" ][ "params" ][ "recording" ] ] )
    {
      $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
      $selectRecordingStatement->execute ( array (
        $connection[ "aufzeichnung" ] ) );
      $recording = $selectRecordingStatement->fetch ();
      echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                             $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ] );
    }

    /*
     * time
     */
    echo "<td" . onTableToggleEvent ( $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ] ) . "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ], "\">", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ], "\">", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span>", $connection[ "_zeitt" ], "<!--", $connection[ "nr" ], "--></td>";

    /*
     * value
     */
    echo foldableTableCell ( $sendCookie[ "wert" ],
                             $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ] );

    /*
     * host
     */
    if ( !$connection[ "host" ] )
    {
      echo ipHostinfo ( $connection[ "ip" ],
                        $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ] );
    }
    else
    {
      echo idHostinfo ( $connection[ "host" ],
                        $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ] );
    }

    /*
     * crypted?
     */
    echo "<td>", ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl" ? "<i class=\"fa fa-key\"></i>" : ""), "</td>";

    /*
     * request URL
     */
    echo foldableTableCell ( $connection[ "methode" ] . " " . $connection[ "uri" ],
                             $__[ "showCookie" ][ "ids" ][ "tables" ][ "sendcookie" ] );

    echo "</tr>";
  }
  while ( $sendCookie = $selectSendCookieStatement->fetch () );

  echo "</tbody></table></div>";
}
echo "</div></div></div>";


include ("include/closeHTML.php");

<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file showConnection.php
 * 
 * display details of a connection
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
require_once ("include/contentUtility.php");
require_once ("include/httpStatusUtility.php");
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

titleAndHelp ( _ ( "Inhalte einer Verbindung" ),
                   _ ( "In dieser Ansicht ist es möglich, schnell durch die vorhandenen Aufzeichnungen und durch deren Verbindungen zu blättern und dabei die Eigenschaften und die Inhalte der Verbindung zu betrachten." ) );

echo "<div class=\"row\"><div class=\"panel-group\" role=\"tablist\"><div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showConnection" ][ "ids" ] [ "navigation" ], "\"><h4 class=\"panel-title\">", _ ( "Navigation" ), "</h4></div><div id=\"", $__[ "showConnection" ][ "ids" ] [ "navigation" ], "\" class=\"panel-collapse collapse in\" role=\"tabpanel\"><div class=\"panel-body\">";

include ("include/recordingBrowser.php");
$recording = $$__[ "include/recordingBrowser" ][ "vars" ] [ "recording" ];
include ("include/connectionBrowser.php");
$connection = $$__[ "include/connectionBrowser" ][ "vars" ] [ "connection" ];

echo "</div></div></div>";


/*
 * encryption
 */
include ("include/showEncryption.php");


/*
 * certificate
 */
include ("include/showCertificate.php");


/*
 * 
 * https(s) connections: cookies, requests
 * 
 */
if ( $connection[ "typ" ] == "http" || $connection[ "typ" ] == "https" )
{
  /*
   * received cookies
   */
  $$__[ "include/showReceivedCookies" ] [ "vars" ][ "statement" ] = $db->prepare ( "select *,date_format(setcookie.expires,'%e.%c.%Y %H:%i') as _expires from setcookie,cookie where setcookie.cookie=cookie.id and setcookie.verbindung=?" );
  $$__[ "include/showReceivedCookies" ] [ "vars" ][ "statement" ]->execute ( array (
    $connection[ "id" ] ) );
  include ("include/showReceivedCookies.php");

  /*
   * sent cookies
   */
  $$__[ "include/showSentCookies" ] [ "vars" ][ "statement" ] = $db->prepare ( "select *,count(sendcookie.request) as _requests from sendcookie,cookie where sendcookie.cookie=cookie.id and sendcookie.verbindung=? group by sendcookie.wert,sendcookie.cookie" );
  $$__[ "include/showSentCookies" ] [ "vars" ][ "statement" ]->execute ( array (
    $connection[ "id" ] ) );
  include ("include/showSentCookies.php");


  /*
   * requests
   */
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showConnection" ] [ "ids" ][ "requests" ], "\"><h4 class=\"panel-title\">";

  $selectRequestStatement = $db->prepare ( "select * from request where verbindung=? order by id" );
  $selectRequestStatement->execute ( array (
    $connection[ "id" ] ) );
  if ( ($request = $selectRequestStatement->fetch ()) == false )
  {
    echo "<span class=\"emptyPanel\">", _ ( "Requests" ), "</span></h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ][ "requests" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "In dieser Verbindung sind keine Requests enthalten." ), "</p>";
  }
  else
  {
    echo tableSorter ( $__[ "showConnection" ] [ "ids" ] [ "tables" ][ "requests" ],
                       "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {} ], order: []" );
    $foldUnfoldButton = tableFolder ( $__[ "showConnection" ] [ "ids" ] [ "tables" ][ "requests" ] );

    echo _ ( "Requests" ), "</h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ][ "requests" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><div class=\"table-esponsive\"><table id=\"", $__[ "showConnection" ] [ "ids" ] [ "tables" ][ "requests" ], "\" class=\"table table-hover\"><thead><tr>";
    echo "<th>$foldUnfoldButton</th>";
    echo "<th>", _ ( "Methode" ), "</th>";
    echo "<th>", _ ( "URI" ), "</th>";
    echo "<th>", _ ( "Response" ), "</th>";
    echo "<th>", _ ( "Typ" ), "</th></tr></thead><tbody>";

    do
    {
      $selectResponseStatement = $db->prepare ( "select * from response where request=?" );
      $selectResponseStatement->execute ( array (
        $request[ "id" ] ) );
      $response = $selectResponseStatement->fetch ();

      echo "<tr>";

      echo "<td>", showViewButton ( "showRequest.php?" . $__[ "showRecording" ] [ "params" ] [ "request" ] . "=" . $request[ "id" ],
                                $__[ "showRequest" ][ "titles" ][ "viewRequest" ]
      ), "</td>";

      echo "<td>", $request[ "methode" ], "</td>";

      echo foldableTableCell ( $request[ "uri" ],
                               $__[ "showConnection" ] [ "ids" ] [ "tables" ][ "requests" ] );

      echo "<td>", httpStatusBadge ( $response[ "status" ] ), " ", $response[ "status" ], " ", $response[ "statustext" ], "</td>";

      echo foldableTableCell ( $response[ "mime" ],
                               $__[ "showConnection" ] [ "ids" ] [ "tables" ][ "requests" ] );

      echo "</tr>";
    }
    while ( $request = $selectRequestStatement->fetch () );

    echo "</tbody></table></div>";
  }
  echo "</div></div></div>";
}


/*
 * 
 * content
 * 
 */
if ( $connection[ "laenge" ] )
{
  $selectPropertiesStatement = $db->prepare ( "select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?" );
  $selectPropertiesStatement->execute ( array (
    $recording[ "geraet" ] ) );
  $properties = $selectPropertiesStatement->fetchAll ( PDO::FETCH_COLUMN,
                                                       0 );
}

/*
 * UDP connection
 */
if ( $connection[ "typ" ] == "udp" )
{
  /*
   * sent UDP data
   */
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showConnection" ] [ "ids" ] [ "udpSent" ], "\"><h4 class=\"panel-title\">";

  $selectContentStatement = $db->prepare ( "select * from inhalt where typ='udpsend' and referenz=?" );
  $selectContentStatement->execute ( array (
    $connection[ "id" ] ) );
  $content = $selectContentStatement->fetch ();

  if ( $content[ "inhalt" ] == "" )
  {
    echo "<span class=\"emptyPanel\">Versandte Daten</span></h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ] [ "udpSent" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Es wurden keine Daten versandt." ), "</p>";
  }
  else
  {
    echo _ ( "Versandte Daten" ), "</h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ] [ "udpSent" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\" id=\"", $__[ "showConnection" ] [ "ids" ] [ "udpSentBody" ], "\">";

    if ( strlen ( $content[ "inhalt" ] ) > $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] * $__[ "include/contentUtility" ][ "values" ][ "longContentGraceFactor" ] )
    {
      showContent ( 0,
                    substr ( $content[ "inhalt" ],
                             0,
                             $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] ),
                             $properties );
      loadFullContent ( $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ],
                        strlen ( $content[ "inhalt" ] ),
                                 0,
                                 $__[ "showConnection" ] [ "ids" ] [ "udpSentBody" ],
                                 $content[ "id" ] );
    }
    else
    {
      showContent ( 0,
                    $content[ "inhalt" ],
                    $properties );
    }
  }
  echo "</div></div></div>";

  /*
   * received UDP data
   */
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showConnection" ] [ "ids" ] [ "udpReceived" ], "\"><h4 class=\"panel-title\">";

  $selectContentStatement = $db->prepare ( "select * from inhalt where typ='udprcv' and referenz=?" );
  $selectContentStatement->execute ( array (
    $connection[ "id" ] ) );
  $content = $selectContentStatement->fetch ();

  if ( $content[ "inhalt" ] == "" )
  {
    echo "<span class=\"emptyPanel\">", _ ( "Empfangene Daten" ), "</span></h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ] [ "udpReceived" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Es wurden keine Daten empfangen." ), "</p>";
  }
  else
  {
    echo _ ( "Empfangene Daten" ), "</h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ] [ "udpReceived" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\" id=\"", $__[ "showConnection" ] [ "ids" ] [ "udpReceivedBody" ], "\">";

    if ( strlen ( $content[ "inhalt" ] ) > $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] * $__[ "include/contentUtility" ][ "values" ][ "longContentGraceFactor" ] )
    {
      showContent ( 1,
                    substr ( $content[ "inhalt" ],
                             0,
                             $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] ),
                             $properties );
      loadFullContent ( $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ],
                        strlen ( $content[ "inhalt" ] ),
                                 1,
                                 $__[ "showConnection" ] [ "ids" ] [ "udpReceivedBody" ],
                                 $content[ "id" ] );
    }
    else
    {
      showContent ( 1,
                    $content[ "inhalt" ],
                    $properties );
    }
  }
  echo "</div></div></div>";
}

/*
 * non UDP connection
 */
else
{
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showConnection" ] [ "ids" ] [ "content" ], "\"><h4 class=\"panel-title\">";

  if ( !$connection[ "laenge" ] )
  {
    echo "<span class=\"emptyPanel\">", _ ( "Inhalt" ), "</span></h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ] [ "content" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Der Inhalt dieser Verbindung ist leer." ), "</p>";
  }
  else
  {
    echo _ ( "Inhalt" ), "</h4></div><div id=\"", $__[ "showConnection" ] [ "ids" ] [ "content" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\" id=\"", $__[ "showConnection" ] [ "ids" ] [ "contentBody" ], "\">";

    /*
     * TCP/SSL (non HTTP)
     */
    if ( $connection[ "typ" ] == "tcp" || $connection[ "typ" ] == "ssl" )
    {
      $selectContentStatement = $db->prepare ( "select * from inhalt where typ='tcp' and referenz=?" );
      $selectContentStatement->execute ( array (
        $connection[ "id" ] ) );
      $content = $selectContentStatement->fetch ();

      if ( strlen ( $content[ "inhalt" ] ) > $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] * $__[ "include/contentUtility" ][ "values" ][ "longContentGraceFactor" ] )
      {
        showContent ( 0,
                      substr ( $content[ "inhalt" ],
                               0,
                               $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] ),
                               $properties );
        loadFullContent ( $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ],
                          strlen ( $content[ "inhalt" ] ),
                                   0,
                                   $__[ "showConnection" ] [ "ids" ] [ "contentBody" ],
                                   $content[ "id" ] );
      }
      else
      {
        showContent ( 0,
                      $content[ "inhalt" ],
                      $properties );
      }
    }

    /*
     * HTTP(S)
     */
    else
    {
      $content = "";
      $selectRequestStatement = $db->prepare ( "select * from request where verbindung=? order by id" );
      $selectRequestStatement->execute ( array (
        $connection[ "id" ] ) );
      while ( $request = $selectRequestStatement->fetch () )
      {
        $content .= $request[ "methode" ] . " " . $request[ "uri" ] . " " . $request[ "version" ] . "\n";

        $selectHeaderStatement = $db->prepare ( "select feld,wert from header where request=? and not response order by id" );
        $selectHeaderStatement->execute ( array (
          $request[ "id" ] ) );
        while ( $header = $selectHeaderStatement->fetch () )
        {
          $content .= $header[ "feld" ] . ": " . $header[ "wert" ] . "\n";
        }
        $content .= "\n";

        if ( $request[ "inhaltroh" ] || $request[ "inhalt" ] )
        {
          $selectContentStatement = $db->prepare ( "select inhalt from inhalt where id=?" );
          $selectContentStatement->execute ( array (
            $request[ "inhaltroh" ] ? $request[ "inhaltroh" ] : $request[ "inhalt" ] ) );
          $content .= $selectContentStatement->fetchColumn ();
        }

        $selectResponseStatement = $db->prepare ( "select * from response where request=?" );
        $selectResponseStatement->execute ( array (
          $request[ "id" ] ) );
        $response = $selectResponseStatement->fetch ();
        $content .= $response[ "version" ] . " " . $response[ "status" ] . " " . $response[ "statustext" ] . "\n";

        $selectHeaderStatement = $db->prepare ( "select feld,wert from header where request=? and response order by id" );
        $selectHeaderStatement->execute ( array (
          $request[ "id" ] ) );
        while ( $header = $selectHeaderStatement->fetch () )
        {
          $content .= $header[ "feld" ] . ": " . $header[ "wert" ] . "\n";
        }
        $content .= "\n";

        if ( $response[ "inhaltroh" ] || $response[ "inhalt" ] )
        {
          $selectContentStatement = $db->prepare ( "select inhalt from inhalt where id=?" );
          $selectContentStatement->execute ( array (
            $response[ "inhaltroh" ] ? $response[ "inhaltroh" ] : $response[ "inhalt" ] ) );
          $content .= $selectContentStatement->fetchColumn ();
        }
      }

      if ( strlen ( $content ) > $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] * $__[ "include/contentUtility" ][ "values" ][ "longContentGraceFactor" ] )
      {
        showContent ( 0,
                      substr ( $content,
                               0,
                               $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] ),
                               $properties );
        loadFullContent ( $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ],
                          strlen ( $content ),
                                   0,
                                   $__[ "showConnection" ] [ "ids" ] [ "contentBody" ],
                                   -$connection[ "id" ] );
      }
      else
      {
        showContent ( 0,
                      $content,
                      $properties );
      }
    }
  }
}

echo "</div></div></div></div></div>";

include ("include/closeHTML.php");

<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file showRequest.php
 * 
 * display details of a http request and navigate through all requests of a recorded connection
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

titleAndHelp ( _ ( "Details eines Requests" ),
                   _ ( "In dieser Ansicht ist es möglich, schnell durch die vorhandenen Aufzeichnungen, deren Verbindungen und deren Requests zu blättern und dabei die Eigenschaften und die Inhalte des Requests zu betrachten." ) );

/*
 * navigation
 */
echo "<div class=\"row\"><div class=\"panel-group\" role=\"tablist\"><div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "navigation" ], "\"><h4 class=\"panel-title\">", _ ( "Navigation" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "navigation" ], "\" class=\"panel-collapse collapse in\" role=\"tabpanel\"><div class=\"panel-body\">";

include ("include/recordingBrowser.php");
$recording = $$__[ "include/recordingBrowser" ][ "vars" ] [ "recording" ];
include ("include/connectionBrowser.php");
$connection = $$__[ "include/connectionBrowser" ][ "vars" ] [ "connection" ];
include ("include/requestBrowser.php");
$request = $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ];
$response = $$__[ "include/requestBrowser" ][ "vars" ] [ "response" ];

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
 * received cookies
 */
$$__[ "include/showReceivedCookies" ] [ "vars" ][ "statement" ] = $db->prepare ( "select *,date_format(setcookie.expires,'%e.%c.%Y %H:%i') as _expires from setcookie,cookie where setcookie.cookie=cookie.id and setcookie.request=?" );
$$__[ "include/showReceivedCookies" ] [ "vars" ][ "statement" ]->execute ( array (
  $_GET[ $__[ "showRequest" ][ "params" ][ "request" ] ] ) );
include ("include/showReceivedCookies.php");


/*
 * sent cookies
 */
$$__[ "include/showSentCookies" ] [ "vars" ][ "statement" ] = $db->prepare ( "select *,count(sendcookie.request) as _requests from sendcookie,cookie where sendcookie.cookie=cookie.id and sendcookie.request=? group by sendcookie.wert,sendcookie.cookie" );
$$__[ "include/showSentCookies" ] [ "vars" ][ "statement" ]->execute ( array (
  $_GET[ $__[ "showRequest" ][ "params" ][ "request" ] ] ) );
include ("include/showSentCookies.php");


/*
 * request header
 */
echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "requestHeader" ], "\"><h4 class=\"panel-title\">";

$selectHeaderStatement = $db->prepare ( "select * from header where response=0 and request=?" );
$selectHeaderStatement->execute ( array (
  $request[ "id" ] ) );
if ( ($header = $selectHeaderStatement->fetch ()) == false )
{
  echo "<span class=\"emptyPanel\">", _ ( "Request-Header" ), "</span></h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "requestHeader" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Der Request enthält keine Header." ), "</p>";
}
else
{
  echo tableSorter ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestHeader" ],
                     "order: []" );
  $foldUnfoldButton = tableFolder ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestHeader" ] );

  echo _ ( "Request-Header" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "requestHeader" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestHeader" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>", _ ( "Name" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th></tr></thead><tbody>";

  do
  {
    echo "<tr>";
    echo "<td>", $header[ "feld" ], "</td>";
    echo foldableTableCell ( $header[ "wert" ],
                             $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestHeader" ] );
    echo "</tr>";
  }
  while ( $header = $selectHeaderStatement->fetch () );

  echo "</tbody></table></div>";
}
echo "</div></div></div>";


/*
 * access content and device properties
 */
$selectRequestContentStatement = $db->prepare ( "select * from inhalt where id=?" );
$selectRequestContentStatement->execute ( array (
  $request[ "inhalt" ] ) );
$requestContent = $selectRequestContentStatement->fetch ();

$selectResponseContentStatement = $db->prepare ( "select * from inhalt where id=?" );
$selectResponseContentStatement->execute ( array (
  $response[ "inhalt" ] ) );
$responseContent = $selectResponseContentStatement->fetch ();

$selectDevicePropertiesStatement = $db->prepare ( "select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?" );
$selectDevicePropertiesStatement->execute ( array (
  $recording[ "geraet" ] ) );
$deviceProperties = $selectDevicePropertiesStatement->fetchAll ( PDO::FETCH_COLUMN,
                                                                 0 );

/*
 * request parameter
 */
echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "requestParameter" ], "\"><h4 class=\"panel-title\">";

$postParameter = $requestContent[ "inhalt" ];
$getParameter = parse_url ( $request[ "uri" ],
                            PHP_URL_QUERY );

if ( $postParameter == "" && $getParameter == "" )
{
  echo "<span class=\"emptyPanel\">", _ ( "Request-Parameter" ), "</span></h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "requestParameter" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Es wurden keine Parameter übertragen." ), "</p>";
}
else
{
  echo tableSorter ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestParameter" ],
                     "order: []" );
  $foldMe = tableFolder ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestParameter" ] );

  echo _ ( "Request-Parameter" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "requestParameter" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestParameter" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>", _ ( "Name" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th>";
  echo "<th>", _ ( "Quelle" ), "</th></tr></thead><tbody>";

  if ( $postParameter != "" )
  {
    foreach ( preg_split ( "/[&;]/",
                           $postParameter ) as $parameter )
    {
      list ($parameterName, $parameterValue) = explode ( "=",
                                                         $parameter,
                                                         2 );
      echo "<tr>";
      echo foldableTableCell ( urldecode ( $parameterName ),
                                           $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestParameter" ] );
      echo foldableTableCell ( urldecode ( $parameterValue ),
                                           $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestParameter" ] );
      echo "<td>", ($request[ "methode" ] == "POST" ? $__[ "include/utility" ][ "values" ] [ "goodSign" ] : $__[ "include/utility" ][ "values" ] [ "mehSign" ]), " ", _ ( "Inhalt (Body)" ), "</td></tr>";
    }
  }

  if ( $getParameter != "" )
  {
    foreach ( preg_split ( "/[&;]/",
                           $getParameter ) as $par )
    {
      list ($key, $val) = explode ( "=",
                                    $par,
                                    2 );
      echo "<tr>";
      echo foldableTableCell ( urldecode ( $key ),
                                           $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestParameter" ] );
      echo foldableTableCell ( urldecode ( $val ),
                                           $__[ "showRequest" ] [ "ids" ][ "tables" ][ "requestParameter" ] );
      echo "<td>", ($request[ "methode" ] == "GET" ? $__[ "include/utility" ][ "values" ] [ "goodSign" ] : $__[ "include/utility" ][ "values" ] [ "mehSign" ]), " URL (Query-String)</td>";
      echo "</tr>";
    }
  }
  echo "</tbody></table></div>";
}
echo "</div></div></div>";


/*
 * request content
 */
echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "requestContent" ], "\"><h4 class=\"panel-title\">";

if ( $requestContent[ "inhalt" ] == "" )
{
  echo "<span class=\"emptyPanel\">Request-Inhalt</span></h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "requestContent" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Der Inhalt dieses Requests ist leer." ), "</p>";
}
else
{
  echo _ ( "Request-Inhalt" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "requestContent" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\" id=\"", $__[ "showRequest" ] [ "ids" ][ "requestContentBody" ], "\">";

  if ( strlen ( $requestContent[ "inhalt" ] ) > $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] * $__[ "include/contentUtility" ][ "values" ][ "longContentGraceFactor" ] )
  {
    showContent ( 0,
                  substr ( $requestContent[ "inhalt" ],
                           0,
                           $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] ),
                           $deviceProperties,
                           $request[ "mime" ] );
    loadFullContent ( $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ],
                      strlen ( $requestContent[ "inhalt" ] ),
                               0,
                               $__[ "showRequest" ] [ "ids" ][ "requestContentBody" ],
                               $request[ "inhalt" ] );
  }
  else
  {
    showContent ( 0,
                  $requestContent[ "inhalt" ],
                  $deviceProperties,
                  $request[ "mime" ] );
  }
}
echo "</div></div></div>";


/*
 * response header
 */
echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "responseHeader" ], "\"><h4 class=\"panel-title\">";

$selectHeaderStatement = $db->prepare ( "select * from header where response=1 and request=?" );
$selectHeaderStatement->execute ( array (
  $request[ "id" ] ) );

if ( ($header = $selectHeaderStatement->fetch ()) == false )
{
  echo "<span class=\"emptyPanel\">", _ ( "Response-Header" ), "</span></h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "responseHeader" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Die Response enthält keine Header." ), "</p>";
}
else
{
  echo tableSorter ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "responseHeader" ],
                     "order: []" );
  $foldMe = tableFolder ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "responseHeader" ] );

  echo _ ( "Response-Header" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "responseHeader" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showRequest" ] [ "ids" ][ "tables" ][ "responseHeader" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>", _ ( "Name" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th></tr></thead><tbody>";

  do
  {
    echo "<tr>";
    echo "<td>", $header[ "feld" ], "</td>";
    echo foldableTableCell ( $header[ "wert" ],
                             $__[ "showRequest" ] [ "ids" ][ "tables" ][ "responseHeader" ] );
    echo "</tr>";
  }
  while ( $header = $selectHeaderStatement->fetch () );

  echo "</tbody></table></div>";
}
echo "</div></div></div>";


/*
 * response content
 */
echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "responseContent" ], "\"><h4 class=\"panel-title\">";

if ( $responseContent[ "inhalt" ] == "" )
{
  echo "<span class=\"emptyPanel\">", _ ( "Response-Inhalt" ), "</span></h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "responseContent" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Der Inhalt dieser Response ist leer." ), "</p>";
}
else
{
  echo _ ( "Response-Inhalt" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "responseContent" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\" id=\"", $__[ "showRequest" ] [ "ids" ][ "responseContentBody" ], "\">";

  if ( strlen ( $responseContent[ "inhalt" ] ) > $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] * $__[ "include/contentUtility" ][ "values" ][ "longContentGraceFactor" ] )
  {
    showContent ( 1,
                  substr ( $responseContent[ "inhalt" ],
                           0,
                           $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] ),
                           $deviceProperties,
                           $response[ "mime" ] );
    loadFullContent ( $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ],
                      strlen ( $responseContent[ "inhalt" ] ),
                               1,
                               $__[ "showRequest" ] [ "ids" ][ "responseContentBody" ],
                               $response[ "inhalt" ] );
  }
  else
  {
    showContent ( 1,
                  $responseContent[ "inhalt" ],
                  $deviceProperties,
                  $response[ "mime" ] );
  }
}
echo "</div></div></div>";


/*
 * native response
 */
if ( $responseContent[ "inhalt" ] != "" )
{
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "responseView" ], "\"><h4 class=\"panel-title\">";

  $responseView = "";
  $mimeParts = explode ( "/",
                         $response[ "mime" ] );
  switch ( $mimeParts[ 0 ] )
  {
    case "text":
      switch ( $mimeParts[ 1 ] )
      {
        case "html":
          $responseView = "<div class=\"iframer\"><iframe src=\"include/showMIMEContent.php?" . $__[ "include/showMIMEContent" ][ "params" ][ "type" ] . "=response&" . $__[ "include/showMIMEContent" ][ "params" ][ "id" ] . "=" . $response[ "id" ] . "\"></iframe></div>";
          break;
      }
      break;
    case "image":
      $responseView = "<img class=\"img-responsive center-block canvas\" src=\"include/showMIMEContent.php?" . $__[ "include/showMIMEContent" ][ "params" ][ "type" ] . "=response&" . $__[ "include/showMIMEContent" ][ "params" ][ "id" ] . "=" . $response[ "id" ] . "\" onclick=\"this.style.backgroundColor=(this.style.backgroundColor=='#000000'||this.style.backgroundColor=='rgb(0, 0, 0)')?'#ffffff':'#000000';\">";
      break;
  }

  if ( $responseView == "" )
  {
    echo "<span class=\"emptyPanel\">", _ ( "Response-Darstellung" ), "</span></h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "responseView" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Der MIME-Typ" ), " \"", $response[ "mime" ], "\" ", _ ( "kann nicht dargestellt werden." ), "</p>";
  }
  else
  {
    echo _ ( "Response-Darstellung" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "responseView" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">", $responseView;
  }
  echo "</div></div></div>";
}


/*
 * meta data
 */
echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "showRequest" ] [ "ids" ][ "metaData" ], "\"><h4 class=\"panel-title\">";

$selectMetadataStatement = $db->prepare ( "select * from metadaten where request=? and response=1" );
$selectMetadataStatement->execute ( array (
  $_GET[ $__[ "showRequest" ][ "params" ][ "request" ] ] ) );
if ( ($metadata = $selectMetadataStatement->fetch ()) == false )
{
  echo "<span class=\"emptyPanel\">", _ ( "Meta-Daten" ), "</span></h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "metaData" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Es wurden keine Meta-Daten gefunden." ), "</p>";
}
else
{
  echo _ ( "Meta-Daten" ), "</h4></div><div id=\"", $__[ "showRequest" ] [ "ids" ][ "metaData" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";
  echo tableSorter ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "metaData" ],
                     "order: [ [0,'asc'] ]" );
  $foldUnfoldButton = tableFolder ( $__[ "showRequest" ] [ "ids" ][ "tables" ][ "metaData" ] );
  echo "<div class=\"table-responsive\"><table id=\"", $__[ "showRequest" ] [ "ids" ][ "tables" ][ "metaData" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>", _ ( "Feld" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th></tr></thead><tbody>";

  do
  {
    echo "<tr>";
    echo foldableTableCell ( $metadata[ "feld" ],
                             $__[ "showRequest" ] [ "ids" ][ "tables" ][ "metaData" ] );
    echo foldableTableCell ( $metadata[ "wert" ],
                             $__[ "showRequest" ] [ "ids" ][ "tables" ][ "metaData" ] );
    echo "</tr>";
  }
  while ( $metadata = $selectMetadataStatement->fetch () );

  echo "</tbody></table></div>";
}

echo "</div></div></div></div></div>";

include ("include/closeHTML.php");

<?php


/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file showContents.php
 * 
 * display the sepcifics of the contents of a connection (drilldown for evaluateContents.php)
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

$_ransehen = "Request ansehen";

$selectContentsStatement = $db->prepare ( "select * from inhalt where id=?" );
$selectContentsStatement->execute ( array (
  $_REQUEST[ $__[ "showContents" ] [ "params" ][ "contents" ] ] ) );
$contents = $selectContentsStatement->fetch ();

if ( $contents[ "typ" ] == "request" )
{
  $selectRequestStatement = $db->prepare ( "select * from request where id=?" );
  $selectRequestStatement->execute ( array (
    $contents[ "referenz" ] ) );
  $referenz = $selectRequestStatement->fetch ();
}
else if ( $contents[ "typ" ] == "response" )
{
  $selectResponseStatement = $db->prepare ( "select * from response where request=?" );
  $selectResponseStatement->execute ( array (
    $contents[ "referenz" ] ) );
  $referenz = $selectResponseStatement->fetch ();
}

$selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?" );
$selectConnectionStatement->execute ( array (
  $contents[ "verbindung" ] ) );
$connection = $selectConnectionStatement->fetch ();

$selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
$selectRecordingStatement->execute ( array (
  $contents[ "aufzeichnung" ] ) );
$recording = $selectRecordingStatement->fetch ();

$selectPropertiesStatement = $db->prepare ( "select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?" );
$selectPropertiesStatement->execute ( array (
  $recording[ "geraet" ] ) );
$properties = $selectPropertiesStatement->fetchAll ( PDO::FETCH_COLUMN,
                                       0 );

titleAndHelp ( _("Inhaltdetails"));


/*
 * 
 * properties
 * 
 */
$foldMe = tableFolder ( $__[ "showContents"]["ids"]["tables"]["properties"]);

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">Eigenschaften</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"",$__[ "showContents"]["ids"]["tables"]["properties"],"\" class=\"table table-hover\"><tbody>";

/*
 * view recording
 */
echo "<tr><td>", viewButton ( "showRecording.php?".$__[ "showRecording]["params"]["recording"]."=" . $recording[ "id" ],
  $__["evaluateRecordings" ] ["titles" ] [      "viewRecording"]
                              ), " ",_("Aufzeichnung"),"</td>", ($recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "</td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                                                          $__[ "showContents"]["ids"]["tables"]["properties"] )), "</tr>";

/*
 * view request
 */
echo "<tr><td>", viewButton ( "showRequest.php?".$__[ "showRequest"]["params"]["request"]."=" . $contents[ "referenz" ],
  $__[ "showRequest"]["titles"]["viewRequest"]
                              ), " "._("Request")."</td><td>", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", $connection[ "_zeitt" ], "</td></tr>";

/*
 * MIME type
 */
echo "<tr><td>",_("Typ"),"</td><td>", strtolower ( $referenz[ "mime" ] ), "</td></tr>";

/*
 * direction (outgoing / incoming)
 */
echo "<tr><td>",_("Kommunikation"),"</td><td>";
switch ( $contents[ "typ" ] )
{
  case "request":
  case "requestroh":
  case "udpsend":
    echo $__[ "evaluateContents" ] [ "values" ] [ "outgoingIcon" ];
    break;
  case "response":
  case "responseroh":
  case "udprcv":
    echo $__[ "evaluateContents" ] [ "values" ] [ "incomingIcon" ];
    break;
}
echo "</td></tr>";

/*
 * bytes
 */
echo "<tr><td>",_("Bytes"),"</td><td>", strlen ( $contents[ "inhalt" ] ), "</td></tr>";

/*
 * server
 */
echo "<tr><td>",_("Server"),"</td>";
if ( !$connection[ "host" ] )
{
  echo ipHostinfo ( $connection[ "ip" ],
                    $__[ "showContents"]["ids"]["tables"]["properties"] );
}
else
{
  echo idHostinfo ( $connection[ "host" ],
                    $__[ "showContents"]["ids"]["tables"]["properties"] );
}
echo "</tr>";

echo "</tbody></table></div></div></div></div>";


/*
 * 
 * contents as text
 * 
 */
echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#",$__[ "showContents"]["ids"]["content"],"\"><h4 class=\"panel-title\">Text</h4></div><div id=\"",$__[ "showContents"]["ids"]["content"],"\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\" id=\"",$__[ "showContents"]["ids"]["contentBody"],"\">";

if ( strlen ( $contents[ "inhalt" ] ) > $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] * $__[ "include/contentUtility" ][ "values" ][ "longContentGraceFactor" ] )
{
  showContent ( 0,
                substr ( $contents[ "inhalt" ],
                         0,
                         $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ] ),
                         $properties,
                         $referenz[ "mime" ] );
  loadFullContent ( $__[ "include/contentUtility" ][ "values" ][ "longContentLength" ],
                    strlen ( $contents[ "inhalt" ] ),
                             0,
                             $__[ "showContents"]["ids"]["contentBody"],
                             $contents[ "id" ] );
}
else
{
  showContent ( 0,
                $contents[ "inhalt" ],
                $properties,
                $referenz[ "mime" ] );
}

echo "</div></div></div></div>";


/*
 * 
 * native contents
 * 
 */
echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#",$__[ "showContents"]["ids"]["native"],"\"><h4 class=\"panel-title\">";

$nativeElement = "";
$mimeParts = explode ( "/",
                 $referenz[ "mime" ] );

switch ( $mimeParts[ 0 ] )
{
      /*
       * html
       */
  case "text":
    switch ( $mimeParts[ 1 ] )
    {
      case "html":
        $nativeElement = "<div class=\"iframer\"><iframe src=\"include/showMIMEContent.php?" . $__[ "include/showMIMEContent" ][ "params" ][ "type" ] . "=" . $contents[ "typ" ] . "&" . $__[ "include/showMIMEContent" ][ "params" ][ "id" ] . "=" . ($contents[ "typ" ] == "response" ? $referenz[ "id" ] : $contents[ "referenz" ]) . "\"></iframe></div>";
        break;
    }
    break;
  
  /*
   * images
   */
  case "image":
    $nativeElement = "<img class=\"img-responsive center-block canvas\" src=\"include/showMIMEContent.php?" . $__[ "include/showMIMEContent" ][ "params" ][ "type" ] . "=" . $contents[ "typ" ] . "&" . $__[ "include/showMIMEContent" ][ "params" ][ "id" ] . "=" . ($contents[ "typ" ] == "response" ? $referenz[ "id" ] : $contents[ "referenz" ]) . "\" onclick=\"this.style.backgroundColor=(this.style.backgroundColor=='#000000'||this.style.backgroundColor=='rgb(0, 0, 0)')?'#ffffff':'#000000';\">";
    break;
}

/*
 * no native view possible
 */
if ( $nativeElement == "" )
{
  echo "<span class=\"emptyPanel\">Darstellung</span></h4></div><div id=\"",$__[ "showContents"]["ids"]["native"],"\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>",_("Der MIME-Typ "),$referenz[ "mime" ],_(" kann nur als Text dargestellt werden."),"</p>";
}

/*
 * show native view in browser
 */
else
{
  echo _("Darstellung"),"</h4></div><div id=\"",$__[ "showContents"]["ids"]["native"],"\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">",$nativeElement;
}

echo "</div></div></div></div>";


/*
 * 
 * contents metadata
 * 
 */
echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#",$__[ "showContents"]["ids"]["metadata"],"\"><h4 class=\"panel-title\">";

$selectMetadataStatement = $db->prepare ( "select * from metadaten where request=? and response=1" );
$selectMetadataStatement->execute ( array (
  $contents[ "referenz" ] ) );

/*
 * no metadata
 */
if ( ($metadata = $selectMetadataStatement->fetch ()) == false )
{
  echo "<span class=\"emptyPanel\">Meta-Daten</span></h4></div><div id=\"",$__[ "showContents"]["ids"]["metadata"],"\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>Es wurden keine Meta-Daten gefunden.</p>";
}

/*
 * have metadata
 */
else
{
  echo _("Meta-Daten"),"</h4></div><div id=\"",$__[ "showContents"]["ids"]["metadata"],"\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";
  
  echo tableSorter ( $__[ "showContents"]["ids"]["tables"]["metadata"],
                     "order: [ [0,'asc'] ]" );
  $foldMe = tableFolder ( $__[ "showContents"]["ids"]["tables"]["metadata"] );
  echo "<div class=\"table-responsive\"><table id=\"",$__[ "showContents"]["ids"]["tables"]["metadata"],"\" class=\"table table-hover\"><thead><tr><th>",_("Feld"),"</th><th>",_("Wert"),"</th></tr></thead><tbody>";

  do
  {
    echo "<tr>";
    echo foldableTableCell ( $metadata[ "feld" ],
                             $__[ "showContents"]["ids"]["tables"]["metadata"] );
    echo foldableTableCell ( $metadata[ "wert" ],
                             $__[ "showContents"]["ids"]["tables"]["metadata"] );
    echo "</tr>";
  }
  while ( $metadata = $selectMetadataStatement->fetch () );

  echo "</tbody></table></div>";
}

echo "</div></div></div></div>";

include ("include/closeHTML.php");
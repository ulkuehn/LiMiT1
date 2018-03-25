<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateImages.php
 * 
 * used to display the images contained in a specific or all recordings
 * images are shown by their thumbnails and meta data such as pixel dimension and image type
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

/**
 * extract image name from URI (last part of path component)
 * 
 * @param string $uri (partial) URI
 * @return string image name
 */
function imageName ( $uri )
{
  $path = parse_url ( $uri,
                      PHP_URL_PATH );
  if ( $path != "" )
  {
    return basename ( $path );
  }
  else
  {
    return $uri;
  }
}


/**
 * build a table of all images found in recording(s)
 * 
 * @param int $recordingID database id of a specific recording or 0 for all recordings
 * @param string $tableID id of the table to build
 * @return array if images were found: (true, html code of table); if no images: (false, some text)
 */
function tabulateImages ( $recordingID,
                          $tableID )
{
  global $imageIndex, $db, $__;

  if ( $recordingID == 0 )
  {
    $selectImageStatement = $db->prepare ( "select response.id as id, response.verbindung as verbindung, request, mime, inhalt.inhalt as inhalt, length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id and locate(?,mime)" );
    $selectImageStatement->execute ( array (
      "image/" ) );
  }
  else
  {
    $selectImageStatement = $db->prepare ( "select response.id as id, response.verbindung as verbindung, request, mime, inhalt.inhalt as inhalt, length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id and locate(?,mime) and response.aufzeichnung=?" );
    $selectImageStatement->execute ( array (
      "image/",
      $recordingID ) );
  }

  /*
   * no image found
   */
  if ( ($image = $selectImageStatement->fetch ()) == false )
  {
    return array (
      false,
      _ ( "Es wurden keine Bilder übertragen." ) );
  }
  /*
   * images were found
   */
  else
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID,
                                                     false );
    $table .= tableSorter ( $tableID,
                            "columns: [ {orderable:false, searchable:false}, {orderable:false, searchable:false}" . ($recordingID == 0 ? ", {}" : "") . ", {}, {}, {}, {}, {} ], order: [ [" . ($recordingID == 0 ? "5" : "4") . ",'asc'] ]" );

    $table .= "<div class=\"table-responsive\"><table id=\"$tableID\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    $table .= "<th>" . _ ( "Ansicht" ) . "</th>";
    if ( $recordingID == 0 )
    {
      $table .= "<th>" . _ ( "Aufzeichnung" ) . "</th>";
    }
    $table .= "<th>" . _ ( "Zeit" ) . "</th>";
    $table .= "<th>" . _ ( "Name" ) . "</th>";
    $table .= "<th>" . _ ( "Bytes" ) . "</th>";
    $table .= "<th>" . _ ( "Typ" ) . "</th>";
    $table .= "<th>" . _ ( "Server" ) . "</th></tr></thead><tbody>";

    do
    {
      $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?" );
      $selectConnectionStatement->execute ( array (
        $image[ "verbindung" ] ) );
      $connection = $selectConnectionStatement->fetch ();
      $selectRequestStatement = $db->prepare ( "select * from request where id=?" );
      $selectRequestStatement->execute ( array (
        $image[ "request" ] ) );
      $request = $selectRequestStatement->fetch ();

      $table .= "<tr>";
      /*
       * request
       */
      $table .= "<td>" . showViewButton ( "showRequest.php?" . $__[ "showRecording" ][ "params" ][ "request" ] . "=" . $image[ "request" ],
                                          $__[ "showRequest" ][ "titles" ] [ "viewRequest" ] ) . "</td>";
      /*
       * thumbnail 
       */
      $table .= "<td>";
      $table .= "<a title=\"" . _ ( "Bilddetails ansehen" ) . "\" href=\"#" . $__[ "evaluateImages" ][ "ids" ] [ "modalPrefix" ] . $imageIndex . "\" class=\"btn btn-default btn-lg\" data-toggle=\"modal\" onclick=\"document.getElementById('" . $__[ "evaluateImages" ][ "ids" ] [ "widthPrefix" ] . $imageIndex . "').innerHTML=document.getElementById('" . $__[ "evaluateImages" ][ "ids" ] [ "thumbnailPrefix" ] . $imageIndex . "').naturalWidth; document.getElementById('" . $__[ "evaluateImages" ][ "ids" ] [ "heightPrefix" ] . $imageIndex . "').innerHTML=document.getElementById('" . $__[ "evaluateImages" ][ "ids" ] [ "thumbnailPrefix" ] . $imageIndex . "').naturalHeight;\"><img id=\"" . $__[ "evaluateImages" ][ "ids" ] [ "thumbnailPrefix" ] . $imageIndex . "\" style=\"max-height:40px; max-width:150px;\" src=\"include/showMIMEContent.php?" . $__[ "include/showMIMEContent" ][ "params" ][ "type" ] . "=" . $__[ "include/showMIMEContent" ][ "values" ][ "response" ] . "&" . $__[ "include/showMIMEContent" ][ "params" ][ "id" ] . "=" . $image[ "id" ] . "\"> <i class=\"fa fa-search-plus\"></i></a>";
      /*
       * modal window for full size display
       */
      $table .= "<div class=\"modal fade\" id=\"" . $__[ "evaluateImages" ][ "ids" ] [ "modalPrefix" ] . $imageIndex . "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><div class=\"alert alert-info\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-search-plus fa-2x\"></i></div><div class=\"msgText\"><strong>" . _ ( "Bilddetails" ) . "</strong></div></div></div><div class=\"modal-body\"><table class=\"table table-condensed\"><tbody>";
      $table .= "<tr><td>" . _ ( "Name" ) . "</td><td class=\"break\">" . imageName ( $request[ "uri" ] ) . "</td></tr>";
      $table .= "<tr><td>" . _ ( "Server" ) . "</td><td>" . whoisify ( $connection[ "host" ] ? array_shift ( idHostinfo ( $connection[ "host" ] ) ) : array_shift ( ipHostinfo ( $connection[ "ip" ] ) ) ) . "</td></tr>";
      $table .= "<tr><td>" . _ ( "Größe" ) . "</td><td><span id=\"" . $__[ "evaluateImages" ][ "ids" ] [ "widthPrefix" ] . $imageIndex . "\"></span> &times; <span id=\"" . $__[ "evaluateImages" ][ "ids" ] [ "heightPrefix" ] . $imageIndex . "\"></span>" . _ ( " Pixel" ) . "</td></tr>";
      $table .= "<tr><td>" . _ ( "Typ" ) . "</td><td>" . array_pop ( explode ( "/",
                                                                               $image[ "mime" ] ) ) . "</td></tr>";
      $table .= "</tbody></table><img class=\"img-responsive center-block canvas\" src=\"include/showMIMEContent.php?" . $__[ "include/showMIMEContent" ][ "params" ][ "type" ] . "=" . $__[ "include/showMIMEContent" ][ "values" ][ "response" ] . "&" . $__[ "include/showMIMEContent" ][ "params" ][ "id" ] . "=" . $image[ "id" ] . "\" onclick=\"this.style.backgroundColor=(this.style.backgroundColor=='#000000'||this.style.backgroundColor=='rgb(0, 0, 0)')?'#ffffff':'#000000';\"></div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">" . _ ( "Schließen" ) . "</button></div></div></div></div></td>";
      /*
       * recording details
       */
      if ( $recordingID == 0 )
      {
        $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
        $selectRecordingStatement->execute ( array (
          $connection[ "aufzeichnung" ] ) );
        $recording = $selectRecordingStatement->fetch ();
        $table .= $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                                    $tableID );
      }
      /*
       * time (sort by connection number)
       */
      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . $connection[ "_zeitd" ] . " <i class=\"fa fa-clock-o\"></i> </span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . "</span>" . $connection[ "_zeitt" ] . "<!--" . $connection[ "nr" ] . "--></td>";
      /*
       * name
       */
      $table .= foldableTableCell ( imageName ( $request[ "uri" ] ),
                                                $tableID );
      /*
       * bytes
       */
      $table .= "<td class=\"numeric\">" . $image[ "length" ] . "</td>";
      /*
       * mime
       */
      $table .= foldableTableCell ( array_pop ( explode ( "/",
                                                          strtolower ( $image[ "mime" ] ) ) ),
                                                                       $tableID );
      /*
       * server
       */
      if ( !$connection[ "host" ] )
      {
        $table .= ipHostinfo ( $connection[ "ip" ],
                               $tableID );
      }
      else
      {
        $table .= idHostinfo ( $connection[ "host" ],
                               $tableID );
      }

      $table .= "</tr>";
      $imageIndex++;
    }
    while ( $image = $selectImageStatement->fetch () );

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

/**
 * global counter for all images processed and displayed; it is incremented by tabulateImages
 * each image needs a unique number/id, so it can be addressed individually
 */
$imageIndex = 0;

if ( !isset ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) )
{
  $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ];
}

titleAndHelp ( _ ( "Bilder" ),
                   _ ( "Mit dieser Auswertung können die aufgezeichneten Bilder betrachtet werden. Dabei werden alle Inhalte berücksichtigt, die den MIME-Typ \"image/...\" haben, also etwa \"image/jpeg\" oder \"image/gif\".<br>Die Bilder werden als Thumbnail angezeigt und können durch Klick in Originalgröße betrachtet werden." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($imagesFound, $imageTable) = tabulateImages ( 0,
                                                        $__[ "evaluateImages" ][ "ids" ][ "tables" ][ "images" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$imagesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Bilder aller Aufzeichnungen" ), (!$imagesFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$imageTable</div></div></div>";
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
      list ($imagesFound, $imageTable) = tabulateImages ( $recording[ "id" ],
                                                          $__[ "evaluateImages" ][ "ids" ][ "tables" ][ "images" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateImages" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";
      echo (!$imagesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Bilder der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$imagesFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateImages" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">$imageTable</div></div></div>";
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

    list ($imagesFound, $imageTable) = tabulateImages ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                        $__[ "evaluateImages" ][ "ids" ][ "tables" ][ "images" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$imagesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Bilder der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$imagesFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$imageTable</div></div></div>";
  }
}

include ("include/closeHTML.php");

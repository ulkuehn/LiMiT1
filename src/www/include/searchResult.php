<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/searchResult.php
 * 
 * provide a specific search result within a source
 * the result's index is given by parameter with name $__[ "include/searchResult" ][ "params" ] [ "searchIndex" ]
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
require_once ("include/searchUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * compile sql query string and array
 */
$item = -1;

foreach ( $searchAreas as $area => $areaInfo )
{
  foreach ( $areaInfo[ 3 ] as $searchItem => $searches )
  {
    $item++;
    if ( $item == $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchArea" ] ] )
    {
      $searchQueryString = $searches[ 2 ];
      $searchQueryArray = array (
        $_POST[ $__[ "include/searchResult" ][ "params" ] [ "whatToSearch" ] ] );
      /*
       * for content related queries we must provide search string twice
       */
      if ( $searches[ 1 ] )
      {
        array_push ( $searchQueryArray,
                     $_POST[ $__[ "include/searchResult" ][ "params" ] [ "whatToSearch" ] ] );
      }
      /*
       * if search is limited to specific recording we must provide additional query and recording id
       */
      if ( $_POST[ $__[ "include/searchResult" ][ "params" ] [ "recordingFilter" ] ] != $__[ "include/filterUtility" ][ "values" ] [ "allRecordings" ] )
      {
        $searchQueryString .= $areaInfo[ 2 ];
        array_push ( $searchQueryArray,
                     $_POST[ $__[ "include/searchResult" ][ "params" ] [ "recordingFilter" ] ] );
      }
      /*
       * do query
       */
      $searchQueryString .= " limit " . $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchIndex" ] ] . ",1";
      $selectResultStatement = $db->prepare ( $searchQueryString );
      $selectResultStatement->execute ( $searchQueryArray );
      $regexpResult = $selectResultStatement->fetch ();
      break;
    }
  }
  if ( $item == $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchArea" ] ] )
  {
    break;
  }
}

$selectConnectionStatement = $db->prepare ( "select *, date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?" );
$selectConnectionStatement->execute ( array (
  $regexpResult[ "verbindung" ] ) );
$connection = $selectConnectionStatement->fetch ();

/*
 * find the next search result starting from a textual position
 */
list ($searchResult, $moreResults, $searchPosition) = highlightSearchResult ( $regexpResult[ $__[ "include/searchUtility" ][ "values" ][ "matchField" ] ],
                                                                              $_POST[ $__[ "include/searchResult" ][ "params" ] [ "whatToSearch" ] ],
                                                                              $_POST[ $__[ "include/searchResult" ][ "params" ] [ "isRegexp" ] ],
                                                                              $_POST[ $__[ "include/searchResult" ][ "params" ] [ "isCaseSensitive" ] ],
                                                                              $_POST[ $__[ "include/searchResult" ][ "params" ] [ "actualPosition" ] ] );

/*
 * nothing (more) found
 */
if ( $searchResult == "" )
{
  #echo "<tr><td colspan=11><pre>item ", htmlspecialchars ( var_dump ( $item ) );
  #echo "\n\nsearchQueryArray ", htmlspecialchars ( var_dump ( $searchQueryArray ) );
  #echo "\n\nsearchQueryString ", htmlspecialchars ( var_dump ( $searchQueryString ) );
  #echo "\n\nregexpResult ", htmlspecialchars ( var_dump ( $regexpResult ) );
  #echo "</pre></td></tr>";
}
/*
 * found something
 */
else
{
  /*
   * button
   */
  if ( array_key_exists ( "request",
                          $regexpResult ) )
  {
    echo "<td>", showViewButton ( "showRequest.php?" . $__[ "showRecording" ][ "params" ][ "request" ] . "=" . $regexpResult[ "request" ],
                                  $__[ "search" ] [ "titles" ][ "viewDetails" ] ), "</td>";
  }
  elseif ( array_key_exists ( "verbindung",
                              $regexpResult ) )
  {
    echo "<td>", showViewButton ( "showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=" . $regexpResult[ "verbindung" ],
                                  $__[ "search" ] [ "titles" ][ "viewDetails" ] ), "</td>";
  }
  else
  {
    echo "<td></td>";
  }

  /*
   * recording
   */
  if ( $_POST[ $__[ "include/searchResult" ][ "params" ] [ "recordingFilter" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
    $selectRecordingStatement->execute ( array (
      $connection[ "aufzeichnung" ] ) );
    $recording = $selectRecordingStatement->fetch ();
    echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "</td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                       $__[ "search" ][ "ids" ][ "tables" ][ "searchResultPrefix" ] . $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchArea" ] ] );
  }

  /*
   * search item
   */
  echo "<td>$searchItem (", $areaInfo[ 0 ], ")"; #</td>";
  #echo "<br>item ", var_dump ( $item );
  #echo "<br>searchQueryArray", var_dump ( $searchQueryArray );
  #echo "<br>searchQueryString", var_dump ( $searchQueryString );
  echo "</td>";

  /*
   * time stamp of connection the search result belongs to
   */
  echo "<td>" . $connection[ "_zeitd" ] . " <i class=\"fa fa-clock-o\"></i> " . $connection[ "_zeitt" ] . "<!--" . $connection[ "zeit" ] . "--></td>";

  /*
   * server
   */
  if ( !$connection[ "host" ] )
  {
    echo ipHostinfo ( $connection[ "ip" ],
                      $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ] );
  }
  else
  {
    echo idHostinfo ( $connection[ "host" ],
                      $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ] );
  }

  /*
   * destination port of connection the search result belongs to
   */
  $portInfo = getservbyport ( $connection[ "anport" ],
                              $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
  echo "<td class=\"numeric\">", $connection[ "anport" ], $portInfo != "" ? " ($portInfo)" : "", "<!--", $connection[ "anport" ], "--></td>";

  /*
   * encryption of connection the search result belongs to
   */
  echo "<td>" . ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl" ? "<i class=\"fa fa-key\"></i>" : "") . "</td>";

  /*
   * navigation element to step back (left) to previous search result
   */
  if ( $_POST[ $__[ "include/searchResult" ][ "params" ] [ "previousPositions" ] ] == "" )
  {
    /*
     * dummy button taking as much space as the real button
     */
    echo "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-left\"></i></button></td>";
  }
  else
  {
    /*
     * take previous position from list and make new list without that position
     */
    $previousPositions = explode ( ",",
                                   $_POST[ $__[ "include/searchResult" ][ "params" ] [ "previousPositions" ] ] );
    $previousPosition = array_pop ( $previousPositions );
    $previousPreviousPositions = implode ( ",",
                                           $previousPositions );
    /*
     * show a button that on click returns previous search result
     */
    echo "<td><button class=\"btn btn-info btn-xs\" title=\"", $__[ "include/searchResult" ] [ "titles" ] [ "previousSearchResult" ], "\" onclick=\"", $__[ "search" ][ "js" ] [ "searchFunctionName" ], "('", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "htmlID" ] ], "', '", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "recordingFilter" ] ], "', ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchArea" ] ], ", ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchIndex" ] ], ", '", jsSave ( $_POST[ $__[ "include/searchResult" ][ "params" ] [ "whatToSearch" ] ] ), "', ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "isRegexp" ] ], ", ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "isCaseSensitive" ] ], ", '$previousPreviousPositions',$previousPosition);\"><i class=\"fa fa-chevron-left\"></i></button></td>";
  }

  /*
   * search result
   */
  echo "<td class=\"break\"", onTableToggleEvent ( $__[ "search" ][ "ids" ][ "tables" ][ "searchResultPrefix" ] . $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchArea" ] ] ), ">$searchResult</td>";

  /*
   * navigation element to step forward (right) to next search result
   */
  if ( !$moreResults )
  {
    /*
     * dummy button taking as much space as the real button
     */
    echo "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-right\"></i></button></td>";
  }
  else
  {
    /*
     * real button
     */
    echo "<td><button class=\"btn btn-info btn-xs\" title=\"", $__[ "include/searchResult" ] [ "titles" ] [ "nextSearchResult" ], "\" onclick=\"", $__[ "search" ][ "js" ] [ "searchFunctionName" ], "('", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "htmlID" ] ], "', '", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "recordingFilter" ] ], "', ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchArea" ] ], ", ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "searchIndex" ] ], ", '", jsSave ( $_POST[ $__[ "include/searchResult" ][ "params" ] [ "whatToSearch" ] ] ), "', ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "isRegexp" ] ], ", ", $_POST[ $__[ "include/searchResult" ][ "params" ] [ "isCaseSensitive" ] ], ", '", ($_POST[ $__[ "include/searchResult" ][ "params" ] [ "previousPositions" ] ] != "" ? $_POST[ $__[ "include/searchResult" ][ "params" ] [ "previousPositions" ] ] . "," : ""), $_POST[ $__[ "include/searchResult" ][ "params" ] [ "actualPosition" ] ], "', $searchPosition);\"><i class=\"fa fa-chevron-right\"></i></button></td>";
  }
}
<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file showLink.php
 *
 * display details of site-to-site links (drilldown for evaluateLinks.php)
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

$_ansehen = "Request ansehen";
$connectSourceTarget = array ();

titleAndHelp ( _ ( "Verweisdetails" ) );

$foldMe = tableFolder ( $__[ "showLink" ][ "ids" ][ "tables" ][ "link" ] );

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Verweis" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showLink" ][ "ids" ][ "tables" ][ "link" ], "\" class=\"table table-hover\"><tbody>";

echo "<tr><td>", _ ( "von Server" ), "</td><td>";
if ( $host = nameHostinfo ( $_REQUEST[ $__[ "showLink" ][ "params" ][ "source" ] ],
                            $__[ "showLink" ][ "ids" ][ "tables" ][ "link" ] ) )
{
  echo $host;
}
else
{
  echo foldableTableCell ( $_REQUEST[ $__[ "showLink" ][ "params" ][ "source" ] ],
                           $__[ "showLink" ][ "ids" ][ "tables" ][ "link" ] );
}
echo "</td></tr>";

echo "<tr><td>", _ ( "zu Server" ), "</td><td>";
if ( $host = nameHostinfo ( $_REQUEST[ $__[ "showLink" ][ "params" ][ "target" ] ],
                            $__[ "showLink" ][ "ids" ][ "tables" ][ "link" ] ) )
{
  echo $host;
}
else
{
  echo foldableTableCell ( $_REQUEST[ $__[ "showLink" ][ "params" ][ "target" ] ],
                           $__[ "showLink" ][ "ids" ][ "tables" ][ "link" ] );
}
echo "</td></tr>";

echo "</tbody></table></div></div></div></div>";

recordingsScope ( $_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] );

/*
 * referer links
 */
if ( $_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] == 0 )
{
  $selectLinkStatement = $db->prepare ( "select wert, host, ip, request, feld from header, verbindung where header.verbindung = verbindung.id and (feld = ? or feld = ?)" );
  $selectLinkStatement->execute ( array (
    "Referer",
    "Origin" ) );
}
else
{
  $selectLinkStatement = $db->prepare ( "select wert, host, ip, request, feld from header, verbindung where header.verbindung = verbindung.id and (feld = ? or feld = ?) and header.aufzeichnung = ?" );
  $selectLinkStatement->execute ( array (
    "Referer",
    "Origin",
    $_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] ) );
}

while ( $link = $selectLinkStatement->fetch () )
{
  if ( $link[ "host" ] )
  {
    $linkTarget = $link[ "host" ];
  }
  else
  {
    $selectHostStatement = $db->prepare ( "select id from host where ip = ?" );
    $selectHostStatement->execute ( array (
      $link[ "ip" ] ) );
    $linkTarget = $selectHostStatement->fetchColumn ();
  }
  $linkTargetName = array_shift ( idHostinfo ( $linkTarget ) );

  if ( ($linkSourceName = parse_url ( $link[ "wert" ] )[ "host" ]) != false || ($linkSourceName = parse_url ( "http://" . $link[ "wert" ] )[ "host" ]) != false )
  {
    $selectHostStatement = $db->prepare ( "select id from host where name=?" );
    $selectHostStatement->execute ( array (
      $linkSourceName ) );
    if ( $linkSource = $selectHostStatement->fetchColumn () )
    {
      $linkSourceName = array_shift ( idHostinfo ( $linkSource ) );
    }
    if ( $linkSourceName != $linkTargetName )
    {
      if ( !isset ( $connectSourceTarget[ $linkSourceName ][ $linkTargetName ] ) )
      {
        $connectSourceTarget[ $linkSourceName ][ $linkTargetName ] = array (
          array (
            $linkSourceName,
            $linkTargetName ) );
        $linkingRequests[ $linkSourceName ][ $linkTargetName ] = array ();
      }
      array_push ( $linkingRequests[ $linkSourceName ][ $linkTargetName ],
                   array (
        $link[ "request" ],
        $link[ "feld" ],
        $link[ "wert" ] ) );
    }
  }
}

/*
 * location links
 */
if ( $_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] == 0 )
{
  $selectLinkStatement = $db->prepare ( "select wert,host,ip,request,feld from header,verbindung where header.verbindung=verbindung.id and feld=?" );
  $selectLinkStatement->execute ( array (
    "Location" ) );
}
else
{
  $selectLinkStatement = $db->prepare ( "select wert,host,ip,request,feld from header,verbindung where header.verbindung=verbindung.id and feld=? and header.aufzeichnung=?" );
  $selectLinkStatement->execute ( array (
    "Location",
    $_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] ) );
}

while ( $link = $selectLinkStatement->fetch () )
{
  if ( $link[ "host" ] )
  {
    $linkSource = $link[ "host" ];
  }
  else
  {
    $selectHostStatement = $db->prepare ( "select id from host where ip=?" );
    $selectHostStatement->execute ( array (
      $link[ "ip" ] ) );
    $linkSource = $selectHostStatement->fetchColumn ();
  }
  $linkSourceName = array_shift ( idHostinfo ( $linkSource ) );

  if ( ($linkTargetName = parse_url ( $link[ "wert" ] )[ "host" ]) != false || ($linkTargetName = parse_url ( "http://" . $link[ "wert" ] )[ "host" ]) != false )
  {
    $selectHostStatement = $db->prepare ( "select id from host where name=?" );
    $selectHostStatement->execute ( array (
      $linkTargetName ) );
    if ( $linkTarget = $selectHostStatement->fetchColumn () )
    {
      $linkTargetName = array_shift ( idHostinfo ( $linkTarget ) );
    }
    if ( $linkSourceName != $linkTargetName )
    {
      if ( !isset ( $connectSourceTarget[ $linkSourceName ][ $linkTargetName ] ) )
      {
        $connectSourceTarget[ $linkSourceName ][ $linkTargetName ] = array (
          array (
            $linkSourceName,
            $linkTargetName ) );
        $linkingRequests[ $linkSourceName ][ $linkTargetName ] = array ();
      }
      array_push ( $linkingRequests[ $linkSourceName ][ $linkTargetName ],
                   array (
        $link[ "request" ],
        $link[ "feld" ],
        $link[ "wert" ] ) );
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


/*
 * tabulate requests
 */
$tableCounter = 0;
foreach ( $connectSourceTarget[ $_REQUEST[ $__[ "showLink" ][ "params" ][ "source" ] ] ][ $_REQUEST[ $__[ "showLink" ][ "params" ][ "target" ] ] ] as $linkage )
{
  echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", whoisify ( $linkage[ 0 ] ), " &rarr; ", whoisify ( $linkage[ 1 ] ), "</h4></div><div class=\"panel-body\">";

  $tableName = $__[ "showLink" ][ "ids" ] [ "tables" ][ "requestPrefix" ] . $tableCounter;
  $tableCounter++;

  echo tableSorter ( $tableName,
                     "columns: [ {orderable:false, searchable:false}" . (!$_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] ? ", {}" : "") . ", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );
  $foldUnfoldButton = tableFolder ( $tableName );

  echo "<div class=\"table-responsive\"><table id=\"$tableName\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldUnfoldButton</th>";

  if ( !$_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] )
  {
    echo "<th>", _ ( "Aufzeichnung" ), "</th>";
  }
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th>", _ ( "Header" ), "</th>";
  echo "<th>", _ ( "Header-Wert" ), "</th>";
  echo "<th></th>";
  echo "<th>", _ ( "Request" ), "</th></tr></thead><tbody>";

  foreach ( $linkingRequests[ $linkage[ 0 ] ][ $linkage[ 1 ] ] as $requestInfos )
  {
    $selectConnectionStatement = $db->prepare ( "select *,date_format(verbindung.zeit,'%e.%c.%Y') as _zeitd, date_format(verbindung.zeit,'%H:%i') as _zeitt from verbindung,request where request.verbindung=verbindung.id and request.id=?" );
    $selectConnectionStatement->execute ( array (
      $requestInfos[ 0 ] ) );
    $connection = $selectConnectionStatement->fetch ();

    echo "<tr>";

    echo "<td>", showViewButton ( "showRequest.php?" . $__[ "showRequest" ] [ "params" ][ "request" ] . "=" . $requestInfos[ 0 ],
                              $__[ "showRequest" ] [ "titles" ][ "viewRequest" ] ), "</td>";

    if ( !$_REQUEST[ $__[ "showLink" ][ "params" ][ "recording" ] ] )
    {
      $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
      $selectRecordingStatement->execute ( array (
        $connection[ "aufzeichnung" ] ) );
      $recording = $selectRecordingStatement->fetch ();
      echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                             $tableName );
    }

    /*
     * sort by timestamp
     */
    echo "<td>", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", $connection[ "_zeitt" ], "<!--", $connection[ "zeit" ], "--></td>";

    echo foldableTableCell ( $requestInfos[ 1 ],
                             $tableName );

    echo foldableTableCell ( $requestInfos[ 2 ],
                             $tableName );

    echo "<td>", ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl") ? "<i class=\"fa fa-key\"></i>" : "", "</td>";

    echo foldableTableCell ( $connection[ "methode" ] . " " . $connection[ "uri" ],
                             $tableName );

    echo "</tr>";
  }

  echo "</tbody></table></div></div></div></div>";
}

include ("include/closeHTML.php");

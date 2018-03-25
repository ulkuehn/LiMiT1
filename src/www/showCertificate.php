<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file showCertificate.php
 *
 * display details of a ssl certificate
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
require_once ("include/certificateUtility.php");
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

titleAndHelp ( _ ( "Zertifikatdetails" ) );

$_ansehen = "Verbindung ansehen";

$selectCertificateStatement = $db->prepare ( "select *, date_format(notbefore, '%T') as _notbeforetime, date_format(notbefore, '%e.%c.%Y') as _notbeforedate, date_format(notafter, '%T') as _notaftertime, date_format(notafter, '%e.%c.%Y') as _notafterdate, datediff(notafter,notbefore) as _tage from zertifikat where id=?" );
$selectCertificateStatement->execute ( array (
  $_REQUEST[ $__[ "showCertificate" ][ "params" ][ "certificateID" ] ] ) );
$certificate = $selectCertificateStatement->fetch ();

/*
 * issuer
 */
if ( preg_match ( "/O=([^=,]*)/",
                  $certificate[ "issuer" ],
                  $match ) )
{
  $issuer = $match[ 1 ];
}
else
{
  $issuer = $certificate[ "issuer" ];
}

/*
 * subject
 */
if ( preg_match ( "/O=([^=,]*)/",
                  $certificate[ "subject" ],
                  $match ) )
{
  $subject = $match[ 1 ];
}
else
{
  $subject = $certificate[ "subject" ];
}

$foldMe = tableFolder ( $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] );

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Zertifikat" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\" class=\"table table-hover\"><thead><tr>";
echo "<th>", _ ( "Eigenschaft" ), "</th>";
echo "<th>", _ ( "Wert" ), "</th>";
echo "<th>", _ ( "Erläuterung" ), "</th></tr></thead><tbody>";

/*
 * serial number
 */
echo "<tr><td>", _ ( "Seriennummer" ), "</td>";
echo "<td>", $certificate[ "serial" ], "</td>";
echo "<td><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "foldedPrefix" ], $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", $__[ "include/tableUtility" ] [ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", _ ( "Die Seriennummer dient zur Identifikation des Zertifikats; sie ist eindeutig für jede Zertifizierungsstelle" ), "</span></td></tr>";

/*
 * validity
 */
echo "<tr" . onTableToggleEvent ( $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] ) . "><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "foldedPrefix" ], $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", _ ( "Gültigkeit" ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", _ ( "Gültigkeit (GMT)" ), "</span></td>";
echo "<td><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "foldedPrefix" ], $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", $certificate[ "_notbeforedate" ], " &nbsp;", _ ( "bis" ), "&nbsp; ", $certificate[ "_notafterdate" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", $certificate[ "_notbeforedate" ], " <i class=\"fa fa-clock-o\"></i> ", $certificate[ "_notbeforetime" ], " &nbsp;", _ ( "bis" ), "&nbsp; ", $certificate[ "_notafterdate" ], " <i class=\"fa fa-clock-o\"></i> ", $certificate[ "_notaftertime" ], "<br>(= ", $certificate[ "_tage" ], " Tage)</span></td>";
echo "<td></td></tr>";

rdnInfo ( _ ( "ausgestellt für" ),
              "",
              $certificate[ "subject" ],
              $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] );
rdnInfo ( _ ( "ausgestellt von" ),
              $certificate[ "subject" ] == $certificate[ "issuer" ] ? _ ( "Es handelt sich um ein selbst signiertes Zertifikat (Aussteller und Inhaber sind identisch)" ) : "",
                                                                          $certificate[ "issuer" ],
                                                                          $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] );

$names = explode ( ",",
                   $certificate[ "names" ] );
echo "<tr><td>", _ ( "Domains" ), "</td>", foldableTableCell ( implode ( " ",
                                                                         $names ),
                                                                         $__[ "showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] ), "<td></td></tr>";

echo "</tbody></table></div></div></div></div>";


/*
 * connections
 */
echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Verbindungen" ), "</h4></div><div class=\"panel-body\">";

if ( $_REQUEST[ $__[ "showCertificate" ][ "params" ][ "recordingID" ] ] == 0 )
{
  $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from
                          (
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,ssltls where verbindung.id=ssltls.verbindung and zertifikat=?
                            union all
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,https where verbindung.id=https.verbindung and zertifikat=?
                          ) t
                          group by id" );
  $selectConnectionStatement->execute ( array (
    $certificate[ "id" ],
    $certificate[ "id" ] ) );
}
else
{
  recordingsScope ( $_REQUEST[ $__[ "showCertificate" ][ "params" ][ "recordingID" ] ] );

  $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from
                          (
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,ssltls where verbindung.id=ssltls.verbindung and zertifikat=? and ssltls.aufzeichnung=?
                            union all
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,https where verbindung.id=https.verbindung and zertifikat=? and https.aufzeichnung=?
                          ) t
                          group by id" );
  $selectConnectionStatement->execute ( array (
    $certificate[ "id" ],
    $_REQUEST[ $__[ "showCertificate" ][ "params" ][ "recordingID" ] ],
    $certificate[ "id" ],
    $_REQUEST[ $__[ "showCertificate" ][ "params" ][ "recordingID" ] ] ) );
}

if ( ($connection = $selectConnectionStatement->fetch ()) == false )
{
  echo _ ( "Das Zertifikat wurde nicht verwendet." );
}
else
{
  echo tableSorter ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ],
                     "columns: [ {orderable:false, searchable:false}" . (!$_REQUEST[ $__[ "showCertificate" ][ "params" ][ "recordingID" ] ] ? ", {}" : "") . ", {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );
  $foldUnfoldButton = tableFolder ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\" class=\"table table-hover\"><thead><tr>";

  echo "<th>$foldUnfoldButton</th>";
  if ( !$_REQUEST[ $__[ "showCertificate" ][ "params" ][ "recordingID" ] ] )
  {
    echo "<th>", _ ( "Aufzeichnung" ), "</th>";
  }
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th>", _ ( "Typ" ), "</th>";
  echo "<th>", _ ( "User-Agent" ), "</th>";
  echo "<th>", _ ( "von Port" ), "</th>";
  echo "<th>", _ ( "zu Server" ), "</th>";
  echo "<th>", _ ( "zu Port" ), "</th>";
  echo "<th>", _ ( "Länge" ), "<span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\"> (", _ ( "Bytes" ), ")</span></th></tr></thead><tbody>";

  $hostinfo = array ();

  do
  {
    echo "<tr>";
    echo "<td>", showViewButton ( "showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=" . $connection[ "id" ],
                                  $_ansehen ), "</td>";

    if ( !$_REQUEST[ $__[ "showCertificate" ][ "params" ][ "recordingID" ] ] )
    {
      $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
      $selectRecordingStatement->execute ( array (
        $connection[ "aufzeichnung" ] ) );
      $recording = $selectRecordingStatement->fetch ();
      echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                             $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] );
    }

    $selectEarlyLateStatement = $db->prepare ( "select ?<? as early, ?>? as late" );
    $selectEarlyLateStatement->execute ( array (
      $connection[ "zeit" ],
      $certificate[ "notbefore" ],
      $connection[ "zeit" ],
      $certificate[ "notafter" ] ) );
    $earlyLate = $selectEarlyLateStatement->fetch ();
    $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
    $explanation = _ ( "Das Zertifikat war zum Zeitpunkt der Verbindung gültig." );
    if ( $earlyLate[ "early" ] )
    {
      $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
      $explanation = _ ( "Das Zertifikat war zum Zeitpunkt der Verbindung noch nicht gültig." );
    }
    else if ( $earlyLate[ "late" ] )
    {
      $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
      $explanation = _ ( "Das Zertifikat war zum Zeitpunkt der Verbindung nicht mehr gültig." );
    }

    /*
     * time
     */
    echo "<td" . onTableToggleEvent ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] ) . ">";
    echo "<span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\">", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> </span><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "foldedPrefix" ], $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\">", $__[ "include/tableUtility" ] [ "values" ][ "foldedEllipses" ], "</span>", $connection[ "_zeitt" ], "</span>";
    echo "<span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "foldedPrefix" ], $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\"> $sign</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\"><br>$sign $explanation</span>";
    echo "<!--", $connection[ "nr" ], "--></td>";

    /*
     * connection type
     */
    echo "<td" . onTableToggleEvent ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] ) . ">", $connection[ "typ" ], "</td>";

    /*
     * user agent
     */
    $userAgent = "";
    if ( substr ( $connection[ "typ" ],
                  0,
                  4 ) == "http" )
    {
      $selectUserAgentStatement = $db->prepare ( "select useragent from " . $connection[ "typ" ] . " where verbindung=?" );
      $selectUserAgentStatement->execute ( array (
        $connection[ "id" ] ) );
      $userAgent = $selectUserAgentStatement->fetchColumn ();
    }
    echo foldableTableCell ( $userAgent,
                             $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] );

    /*
     * source port
     */
    $srvc = getservbyport ( $connection[ "vonport" ],
                            $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\"" . onTableToggleEvent ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] ) . ">", $connection[ "vonport" ], $srvc != "" ? " ($srvc)" : "", "<!--", $connection[ "vonport" ], "--></td>";

    /*
     * subjects
     */
    $names = explode ( ",",
                       $certificate[ "names" ] );
    if ( !$connection[ "host" ] )
    {
      $hostNames = ipHostinfo ( $connection[ "ip" ] );
    }
    else
    {
      $hostNames = idHostinfo ( $connection[ "host" ] );
    }
    $sign = $__[ "include/utility" ][ "values" ] [ "mehSign" ];
    if ( count ( $names ) == 1 )
    {
      $explanation = _ ( "Die Domain, für die das Zertifikat ausgestellt ist, passt nicht zum Servernamen" );
    }
    else
    {
      $explanation = _ ( "Keine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen" );
    }

    foreach ( $names as $name )
    {
      foreach ( $hostNames as $hostName )
      {
        /*
         * the "||"-preg allows for matching of "*.a.b" for "a.b" (not "c.a.b" etc. only )
         * while this seems common, it is maybe not in line with RFC 2595 ?
         */
        if ( $name == $hostName || (substr ( $name,
                                             0,
                                             1 ) == "*" && (preg_match ( "/." . str_replace ( ".",
                                                                                              "\\.",
                                                                                              $name ) . "/",
                                                                                              $hostName ) || preg_match ( "/." . str_replace ( ".",
                                                                                                                                               "\\.",
                                                                                                                                               $name ) . "/",
                                                                                                                                               ".$hostName" ))) )
        {
          $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
          if ( count ( $names ) == 1 )
          {
            $explanation = _ ( "Die Domain, für die das Zertifikat ausgestellt ist, passt zum Servernamen" );
          }
          else
          {
            $explanation = _ ( "Mindestens eine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen" );
          }
          break;
        }
      }
    }

    /*
     * host
     */
    $authoritativeName = array_shift ( $hostNames );
    $authoritativeName = explode ( ".",
                                   $authoritativeName );
    $topLevelDomain = array_pop ( $authoritativeName );
    $domain = array_pop ( $authoritativeName );
    array_push ( $authoritativeName,
                 $domain . "." . $topLevelDomain );
    echo "<td class=\"break\"" . onTableToggleEvent ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] ) . ">", whoisify ( $authoritativeName ),
    "<span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "foldedPrefix" ], $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\"> $sign ", $__[ "include/tableUtility" ] [ "values" ][ "foldedEllipses" ], "</span>",
    "<span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ], "\">",
    "<br>(" . implode ( " * ",
                        array_map ( "whoisify",
                                    $hostNames ) ) . ")<br>$sign $explanation</span>",
    "<!--" . implode ( " ",
                       array_reverse ( $authoritativeName ) ) . " --></td>";

    /*
     * destination port
     */
    $destinationPort = getservbyport ( $connection[ "anport" ],
                                       $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\"" . onTableToggleEvent ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] ) . ">", $connection[ "anport" ], $destinationPort != "" ? " ($destinationPort)" : "", "<!--", $connection[ "anport" ], "--></td>";

    /*
     * length
     */
    echo "<td class=\"numeric\"" . onTableToggleEvent ( $__[ "showCertificate" ][ "ids" ] [ "tables" ][ "connection" ] ) . ">", $connection[ "laenge" ], "</td></tr>";
  }
  while ( $connection = $selectConnectionStatement->fetch () );

  echo "</tbody></table></div>";
}
echo "</div></div></div>";


include ("include/closeHTML.php");

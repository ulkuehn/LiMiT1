<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file showSslsuite.pho
 * 
 * display ssl related details of an encrypted connection (drilldown for sslsuites.php)
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

$_ansehen = "Verbindung ansehen";

$selectCipherSuiteStatement = $db->prepare ( "select * from cipherSuite where id=?" );
$selectCipherSuiteStatement->execute ( array (
  $_REQUEST[ $__[ "showSslsuite" ] [ "params" ][ "cipherSuite" ] ] ) );
$cipherSuite = $selectCipherSuiteStatement->fetch ();
$selectCipherStatement = $db->prepare ( "select * from cipher where id=?" );
$selectCipherStatement->execute ( array (
  $cipherSuite[ "cipher" ] ) );
$cipher = $selectCipherStatement->fetch ();
$selectKeyExchangeStatement = $db->prepare ( "select * from keyExchange where id=?" );
$selectKeyExchangeStatement->execute ( array (
  $cipherSuite[ "keyExchange" ] ) );
$keyExchange = $selectKeyExchangeStatement->fetch ();
$selectMacStatement = $db->prepare ( "select * from mac where id=?" );
$selectMacStatement->execute ( array (
  $cipherSuite[ "mac" ] ) );
$mac = $selectMacStatement->fetch ();


titleAndHelp ( _ ( "Verschlüsselungsdetails" ) );


$foldMe = tableFolder ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ] );

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Eigenschaften" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\" class=\"table table-hover\"><thead><tr>";
echo "<th>", _ ( "Eigenschaft" ), "</th>";
echo "<th>", _ ( "Wert" ), "</th>";
echo "<th>", _ ( "Erläuterung" ), "</th></tr></thead><tbody>";

/*
 * secure?
 */
if ( $cipher[ "secure" ] )
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
  $showText = _ ( "Das Verschlüsselungsverfahren " ) . $cipher[ "shortName" ] . _ ( " gilt als sicher." );
}
else
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
  $showText = _ ( "Das Verschlüsselungsverfahren " ) . $cipher[ "shortName" ] . _ ( " gilt als unsicher." );
}
echo "<tr", onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ] ), "><td>", _ ( "Verschlüsselungsverfahren" ), "</td><td>", $cipher[ "longName" ], "</td><td><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign", $__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign $showText</span></td></tr>";

/*
 * cipher bits
 */
if ( $cipher[ "bits" ] < 128 )
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
  $showText = _ ( "Verschlüsselungen mit einer Schlüssellänge von unter 128 Bit sind unsicher." );
}
else if ( $cipher[ "bits" ] < 256 )
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "mehSign" ];
  $showText = _ ( "Schlüssellängen mit 128 Bit und mehr gelten als sicher. Optimal wären Schlüssel ab 256 Bit." );
}
else
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
  $showText = _ ( "Verfahren mit Schlüsseln ab 256 Bit Länge sind optimal." );
}
echo "<tr", onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ] ), "><td>", _ ( "Schlüssellänge" ), "</td><td>", $cipher[ "bits" ], _ ( " Bits" ), "</td><td><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign", $__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign $showText</span></td></tr>";

/*
 * key exchange
 */
if ( $keyExchange[ "secure" ] )
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
  $showText = _ ( "Das Schlüsselaustauschverfahren " ) . $keyExchange[ "shortName" ] . _ ( " gilt als sicher." );
}
else
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
  $showText = _ ( "Das Schlüsselaustauschverfahren " ) . $keyExchange[ "shortName" ] . _ ( " gilt als unsicher." );
}
echo "<tr", onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ] ), "><td>", _ ( "Schlüsselaustauschverfahren" ), "</td><td>", $keyExchange[ "longName" ], "</td><td><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign", $__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign $showText</span></td></tr>";

/*
 * forward secrecy
 */
if ( $keyExchange[ "forwardSecrecy" ] )
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
  $showText = _ ( "Forward Secrecy stellt sicher, dass eine aufgezeichnete verschlüsselte Kommunikation nicht nachträglich entschlüsselt werden kann." );
}
else
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "mehSign" ];
  $showText = _ ( "Ohne Forward Secrecy besteht das Risiko, dass aufgezeichnete Kommunikationsströme entschlüsselt werden können, wenn das Verschlüsselungsverfahren gebrochen wird." );
}
echo "<tr", onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ] ), "><td>", _ ( "Forward Secrecy" ), "</td><td>", $keyExchange[ "forwardSecrecy" ] ? _ ( "ja" ) : _ ( "nein" ), "</td><td><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign", $__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign $showText</span></td></tr>";

/*
 * mac
 */
if ( $mac[ "secure" ] )
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
  $showText = _ ( "Das Hashverfahren " ) . $mac[ "shortName" ] . _ ( " gilt als sicher." );
}
else
{
  $showSign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
  $showText = _ ( "Das Hashverfahren " ) . $mac[ "shortName" ] . _ ( " gilt als unsicher." );
}
echo "<tr", onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ] ), "><td>", _ ( "Hashverfahren" ), "</td><td>", $mac[ "longName" ], " (", $mac[ "bits" ], _ ( " Bits" ), ")</td><td><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign", $__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "suite" ], "\">$showSign $showText</span></td></tr>";

echo "</tbody></table></div></div></div></div>";


echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Verbindungen" ), "</h4></div><div class=\"panel-body\">";

if ( $_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ] == 0 )
{
  $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from
                          (
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,ssltls where verbindung.id=ssltls.verbindung and ciphersuite=?
                            union all
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,https where verbindung.id=https.verbindung and ciphersuite=?
                          ) t
                          group by id" );
  $selectConnectionStatement->execute ( array (
    $cipherSuite[ "id" ],
    $cipherSuite[ "id" ] ) );
}
else
{
  recordingsScope ( $_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ] );

  $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from
                          (
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ,sslversion from verbindung,ssltls where verbindung.id=ssltls.verbindung and ciphersuite=? and ssltls.aufzeichnung=?
                            union all
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ,sslversion from verbindung,https where verbindung.id=https.verbindung and ciphersuite=? and https.aufzeichnung=?
                          ) t
                          group by id" );
  $selectConnectionStatement->execute ( array (
    $cipherSuite[ "id" ],
    $_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ],
    $cipherSuite[ "id" ],
    $_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ] ) );
}

if ( ($connection = $selectConnectionStatement->fetch ()) == false )
{
  echo _ ( "Die Verschlüsselung wurde nicht verwendet." );
}
else
{
  echo tableSorter ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ],
                     "columns: [ {orderable:false, searchable:false}" . (!$_REQUEST[ "aufzeichnung" ] ? ", {}" : "") . ", {}, {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );
  $foldMe = tableFolder ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldMe</th>";
  if ( !$_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ] )
  {
    echo "<th>", _ ( "Aufzeichnung" ), "</th>";
  }
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th>", _ ( "Version" ), "</th>";
  echo "<th>", _ ( "Typ" ), "</th>";
  echo "<th>", _ ( "User-Agent" ), "</th>";
  echo "<th>", _ ( "von Port" ), "</th>";
  echo "<th>", _ ( "zu Server" ), "</th>";
  echo "<th>", _ ( "zu Port" ), "</th>";
  echo "<th>", _ ( "Länge" ), "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "connections" ], "\"> (", _ ( "Bytes" ), ")</span></th></tr></thead><tbody>";

  $hostinfo = array ();

  do
  {
    echo "<tr>";

    /*
     * connection
     */
    echo "<td>", showViewButton ( "showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=" . $connection[ "id" ],
                                  $__[ "showRecording" ] [ "titles" ] [ "viewConnection" ] ), "</td>";

    /*
     * recording
     */
    if ( !$_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ] )
    {
      $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from recording where id=?" );
      $selectRecordingStatement->execute ( array (
        $connection[ "recording" ] ) );
      $recording = $selectRecordingStatement->fetch ();
      echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "<!--" . $recording[ "start" ] . "--></td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                                             $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] );
    }

    /*
     * time (sort by connection number)
     */
    echo "<td" . onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] ) . ">";
    echo "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "connections" ], "\">", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> </span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "connections" ], "\">", $__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ], "</span>", $connection[ "_zeitt" ], "</span>";
    echo "<!--", $connection[ "nr" ], "--></td>";

    /*
     * version
     */
    echo "<td" . onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] ) . ">", $connection[ "sslversion" ], "</td>";

    /*
     * type
     */
    echo "<td" . onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] ) . ">", $connection[ "typ" ], "</td>";

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
                             $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] );

    /*
     * source port
     */
    $sourcePort = getservbyport ( $connection[ "vonport" ],
                                  $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\"" . onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] ) . ">", $connection[ "vonport" ], $sourcePort != "" ? " ($sourcePort)" : "", "<!--", $connection[ "vonport" ], "--></td>";

    /*
     * host
     */
    if ( !$connection[ "host" ] )
    {
      $hostNames = ipHostinfo ( $connection[ "ip" ] );
    }
    else
    {
      $hostNames = idHostinfo ( $connection[ "host" ] );
    }
    $authoritativeName = array_shift ( $hostNames );
    $authoritativeExplode = explode ( ".",
                                      $authoritativeName );
    $topLevelDomain = array_pop ( $authoritativeExplode );
    $domain = array_pop ( $authoritativeExplode );
    array_push ( $authoritativeExplode,
                 $domain . "." . $topLevelDomain );
    echo "<td class=\"break\"" . onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] ) . ">", whoisify ( $authoritativeName ),
    "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "connections" ], "\">", $__[ "include/tableUtility" ] [ "values" ] [ "foldedEllipses" ], "</span>",
    "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "showSslsuite" ] [ "ids" ] [ "tables" ][ "connections" ], "\">",
    "<br>(" . implode ( " * ",
                        array_map ( "whoisify",
                                    $hostNames ) ) . ")</span>",
    "<!--" . implode ( " ",
                       array_reverse ( $authoritativeExplode ) ) . " --></td>";

    /*
     * destination port
     */
    $destinationPort = getservbyport ( $connection[ "anport" ],
                                       $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\"" . onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] ) . ">", $connection[ "anport" ], $destinationPort != "" ? " ($destinationPort)" : "", "<!--", $connection[ "anport" ], "--></td>";

    /*
     * bytes
     */
    echo "<td class=\"numeric\"" . onTableToggleEvent ( $__[ "showSslsuite" ] [ "ids" ] [ "tables" ] [ "connections" ] ) . ">", $connection[ "laenge" ], "</td>";

    echo "</tr>";
  }
  while ( $connection = $selectConnectionStatement->fetch () );

  echo "</tbody></table></div>";
}

echo "</div></div></div>";

include ("include/closeHTML.php");

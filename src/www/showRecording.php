<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file showRecording.php
 * 
 * display the details of one recording
 * the recording displayed is referred to by its database id which is given in parameter named $__[ "showRecording" ][ "params" ][ "recording" ]
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

/*
 * store recording id in cookie
 */
if ( isset ( $_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ] ) )
{
  setcookie ( $__[ "include/filterUtility" ][ "names" ][ "cookie" ],
              $_REQUEST[ $__[ "showRecording" ][ "params" ][ "recording" ] ] );
}

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

titleAndHelp ( _ ( "Verbindungen einer Aufzeichnung" ),
                   _ ( "<p>In dieser Ansicht ist es möglich, schnell durch die vorhandenen Aufzeichnungen zu blättern und die Verbindungen zu sehen, die während der ausgewählten Aufzeichnung hergestellt wurden.</p>" ) . _ ( "<p>Dabei ist z.B. erkennbar, zu welchem Server und zu welchem Dienst oder Port die Verbindung bestand. Bei http(s)-Verbindungen wird auch der User-Agent angegeben, der beim Verbindungsaufbau übertragen wurde. Über diese Angabe lassen sich häufig zusammengehörige Verbindungen zuordnen, da sie vom selben Programm (Browser, Client, App) stammen.</p><p>Es werden folgende Verbindungstypen unterschieden:</p>" ) . "<dl class=\"dl-horizontal\"><dt>" . _ ( "http" ) . "</dt><dd>" . _ ( "unverschlüsselte HTTP-Verbindung" ) . "</dd><dt>" . _ ( "https" ) . "</dt><dd>" . _ ( "SSL/TLS-verschlüsselte HTTP-Verbindung" ) . "</dd><dt>" . _ ( "tcp" ) . "</dt><dd>" . _ ( "unverschlüsselte TCP-Verbindung" ) . "</dd><dt>" . _ ( "ssl" ) . "</dt><dd>" . _ ( "SSL/TLS-verschlüsselte TCP-Verbindung" ) . "</dd><dt>" . _ ( "udp" ) . "</dt><dd>" . _ ( "UDP-Datenaustausch" ) . "</dd></dl>" );

/*
 * allow for stepping through recordings
 */
include ("include/recordingBrowser.php");

/*
 * tabulate the recording's connections
 */
echo "<form class=\"form-horizontal\" method=\"post\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Verbindungen" ), "</h4></div><div class=\"panel-body\">";

$selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where aufzeichnung=? order by nr" );
$selectConnectionStatement->execute ( array (
  $_GET[ $__[ "showRecording" ][ "params" ][ "recording" ] ] ) );

if ( ($connection = $selectConnectionStatement->fetch ()) == false )
{
  showInfoMessage ( _ ( "Es sind keine Verbindungen vorhanden." ) );
}
else
{
  echo tableSorter ( $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ],
                     "columns: [ {orderable:false, searchable:false}, {type:'num'}, {}, {}, {}, {}, {type:'num'}, {} ], order: [ [1,'asc'] ]" );
  $foldMe = tableFolder ( $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ] );

  echo "<table id=\"", $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldMe</th>";
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th>", _ ( "Typ" ), "</th>";
  echo "<th>", _ ( "User-Agent" ), "</th>";
  echo "<th>", _ ( "von Port" ), "</th>";
  echo "<th>", _ ( "zu Server" ), "</th>";
  echo "<th>", _ ( "zu Port" ), "</th>";
  echo "<th>", _ ( "Länge" ), "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ][ "unfoldedPrefix" ], $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ], "\"> (", _ ( "Bytes" ), ")</span></th></tr></thead><tbody>";


  do
  {
    echo "<tr>";
    /*
     * link to connection details
     */
    echo "<td>", showViewButton ( "showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=" . $connection[ "id" ],
                                  $__[ "showRecording" ] [ "titles" ] [ "viewConnection" ] ), "</td>";
    /*
     * time (sort by connection index)
     */
    echo "<td", onTableToggleEvent ( $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ] ), "><span class=\"", $__[ "include/tableUtility" ] [ "ids" ][ "unfoldedPrefix" ], $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ], "\">", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", "</span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ][ "foldedPrefix" ], $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ], "\">", $__[ "include/tableUtility" ] [ "values" ][ "foldedEllipses" ], "</span>", $connection[ "_zeitt" ], "<!--", $connection[ "nr" ], "--></td>";
    /*
     * type
     */
    echo "<td>", $connection[ "typ" ], "</td>";
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
                             $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ] );
    /*
     * source port
     */
    $srvc = getservbyport ( $connection[ "vonport" ],
                            $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\">", $connection[ "vonport" ], $srvc != "" ? " ($srvc)" : "", "<!--", $connection[ "vonport" ], "--></td>";
    /*
     * destination server
     */
    if ( !$connection[ "host" ] )
    {
      echo ipHostinfo ( $connection[ "ip" ],
                        $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ] );
    }
    else
    {
      echo idHostinfo ( $connection[ "host" ],
                        $__[ "showRecording" ][ "ids" ][ "tables" ][ "connections" ] );
    }
    /*
     * destination port
     */
    $srvc = getservbyport ( $connection[ "anport" ],
                            $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\">", $connection[ "anport" ], $srvc != "" ? " ($srvc)" : "", "<!--", $connection[ "anport" ], "--></td>";
    /*
     * bytes
     */
    echo "<td class=\"numeric\">", $connection[ "laenge" ], "</td></tr>";
  }
  while ( $connection = $selectConnectionStatement->fetch () );

  echo "</tbody></table>";
}

echo "</div></div></div></form>";

include ("include/closeHTML.php");

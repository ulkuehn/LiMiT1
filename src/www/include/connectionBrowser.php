<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/connectionBrowser.php
 * 
 * show the currently selected connection and to provide access to all available connections of the currently selected recording
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

if ( isset ( $_GET[ $__[ "showRecording" ][ "params" ][ "connection" ] ] ) )
{
  $connectionID = $_GET[ $__[ "showRecording" ][ "params" ][ "connection" ] ];
}
else if ( isset ( $_GET[ $__[ "showRecording" ][ "params" ][ "request" ] ] ) )
{
  $selectConnectionIDStatement = $db->prepare ( "select verbindung from request where id=?" );
  $selectConnectionIDStatement->execute ( array (
    $_GET[ $__[ "showRecording" ][ "params" ][ "request" ] ] ) );
  $connectionID = $selectConnectionIDStatement->fetchColumn ();
}

if ( isset ( $connectionID ) )
{
  $selectConnectionStatement = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?" );
  $selectConnectionStatement->execute ( array (
    $connectionID ) );
  if ( $connection = $selectConnectionStatement->fetch () )
  {
    $$__[ "include/connectionBrowser" ][ "vars" ] [ "connection" ] = $connection;
    $selectPreviousConnectionStatement = $db->prepare ( "select id from verbindung where nr<? and aufzeichnung=? order by nr desc limit 1" );
    $selectPreviousConnectionStatement->execute ( array (
      $connection[ "nr" ],
      $connection[ "aufzeichnung" ] ) );
    $previousConnection = $__[ "include/recordingBrowser" ][ "values" ][ "backButtonInactive" ];
    if ( $id = $selectPreviousConnectionStatement->fetchColumn () )
    {
      $previousConnection = "<a href=\"showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=$id\">" . $__[ "include/recordingBrowser" ][ "values" ][ "backButton" ] . "</a>";
    }

    $selectNextConnectionStatement = $db->prepare ( "select id from verbindung where nr>? and aufzeichnung=? order by nr asc limit 1" );
    $selectNextConnectionStatement->execute ( array (
      $connection[ "nr" ],
      $connection[ "aufzeichnung" ] ) );
    $nextConnection = $__[ "include/recordingBrowser" ][ "values" ][ "forwardButtonInactive" ];
    if ( $id = $selectNextConnectionStatement->fetchColumn () )
    {
      $nextConnection = "<a href=\"showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=$id\">" . $__[ "include/recordingBrowser" ][ "values" ][ "forwardButton" ] . "</a>";
    }

    $foldMe = tableFolder ( $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] );

    echo "<div class=\"row nestedPanel\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">$previousConnection $nextConnection ", _ ( "Verbindung" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table id=\"", $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ], "\" class=\"table\"><thead><tr>";
    echo "<th>$foldMe</th>";
    echo "<th>", _ ( "Zeit" ), "</th>";
    echo "<th>", _ ( "Typ" ), "</th>";
    echo "<th>", _ ( "User-Agent" ), "</th>";
    echo "<th>", _ ( "von Port" ), "</th>";
    echo "<th>", _ ( "zu Server" ), "</th>";
    echo "<th>", _ ( "zu Port" ), "</th>";
    echo "<th>", _ ( "Länge" ), "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ], "\"> ", _ ( "(Bytes)" ), "</span></th></tr></thead><tbody><tr>";

    /*
     * view button
     */
    echo "<td>", showViewButton ( "showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=" . $connection[ "id" ],
                                  $__[ "showRecording" ][ "titles" ] [ "viewConnection" ] ), "</td>";

    /*
     * connection time
     */
    echo "<td", onTableToggleEvent ( $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] ), "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ], "\">", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ], "\">", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span>", $connection[ "_zeitt" ], "<!--", $connection[ "nr" ], "--></td>"; // sort by nr

    /*
     * type
     */
    echo "<td", onTableToggleEvent ( $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] ), ">", $connection[ "typ" ], "</td>";

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
                             $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] );

    /*
     * source port
     */
    $portInfo = getservbyport ( $connection[ "vonport" ],
                                $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\"", onTableToggleEvent ( $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] ), ">", $connection[ "vonport" ], $portInfo != "" ? " ($portInfo)" : "", "<!--", $connection[ "vonport" ], "--></td>";

    /*
     * server
     */
    if ( !$connection[ "host" ] )
    {
      echo ipHostinfo ( $connection[ "ip" ],
                        $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] );
    }
    else
    {
      echo idHostinfo ( $connection[ "host" ],
                        $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] );
    }

    /*
     * destination port
     */
    $portInfo = getservbyport ( $connection[ "anport" ],
                                $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\"", onTableToggleEvent ( $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] ), ">", $connection[ "anport" ], $portInfo != "" ? " ($portInfo)" : "", "<!--", $connection[ "anport" ], "--></td>";

    /*
     * bytes
     */
    echo "<td class=\"numeric\"", onTableToggleEvent ( $__[ "include/connectionBrowser" ][ "ids" ][ "tables" ][ "connection" ] ), ">", $connection[ "laenge" ], "</td>";

    echo "</tr></tbody></table></div></div></div></div>";
  }
}
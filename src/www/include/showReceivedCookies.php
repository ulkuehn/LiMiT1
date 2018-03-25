<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/showReceivedCookies.php
 *
 * display a table of all cookies that were received in a specific request or connection
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
require_once ("include/timeUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 *
 * MAIN CODE
 *
 * ======================================================================== */

echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "include/showReceivedCookies" ] [ "ids" ][ "cookies" ], "\"><h4 class=\"panel-title\">";

if ( ($cookie = $$__[ "include/showReceivedCookies" ] [ "vars" ][ "statement" ]->fetch ()) == false )
{
  echo "<span class=\"emptyPanel\">", _ ( "Empfangene Cookies" ), "</span></h4></div><div id=\"", $__[ "include/showReceivedCookies" ] [ "ids" ][ "cookies" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Es wurden keine Cookies empfangen." ), "</p>";
}
else
{
  echo _ ( "Empfangene Cookies" ), "</h4></div><div id=\"", $__[ "include/showReceivedCookies" ] [ "ids" ][ "cookies" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";
  echo tableSorter ( $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ],
                     "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {}, {type:'date'}, {type:'num'}, {}" . (!isset ( $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ] ) ? ", {type:'num'}" : "") . " ], order: [ [1,'asc'] ]" );

  $foldUnfoldButton = tableFolder ( $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] );
  echo "<div class=\"table-responsive\"><table id=\"", $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldUnfoldButton</th>";
  echo "<th>", _ ( "Name" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th>";
  echo "<th>", _ ( "Site" ), "</th>";
  echo "<th>", _ ( "Pfad" ), "</th>";
  echo "<th>", _ ( "Verfall" ), "</th>";
  echo "<th><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">", _ ( "Dauer" ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">", _ ( "Speicherdauer" ), "</span></th>";
  echo "<th><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">", _ ( "Eig." ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">", _ ( "Eigenschaften" ), "</span></th>";
  if ( !isset ( $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ] ) )
  {
    echo "<th>", _ ( "versandt" ), "</th>";
  }
  echo "</tr></thead><tbody>";

  /*
   * list all relevant cookies
   */
  do
  {
    echo "<tr>";
    echo "<td>", showViewButton ( "showCookie.php?" . $__[ "showCookie" ][ "params" ] [ "cookie" ] . "=" . $cookie[ "id" ] . "&aufzeichnung=$aufzeichnungId",
                                  $__[ "evaluateCookies" ][ "titles" ][ "viewCookie" ] ), "</td>";
    echo foldableTableCell ( $cookie[ "name" ],
                             $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] );
    echo foldableTableCell ( $cookie[ "wert" ],
                             $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] );
    echo foldableTableCell ( $cookie[ "site" ],
                             $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ],
                             true );
    echo "<td>", $cookie[ "path" ], "</td>";

    if ( $cookie[ "expires" ] == "" || $cookie[ "expires" ] == 0 )
    {
      echo "<td><!--0--></td>";
    }
    else
    {
      echo "<td", onTableToggleEvent ( $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] ), "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">";
      echo explode ( " ",
                     $cookie[ "_expires" ] )[ 0 ], $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">", $cookie[ "_expires" ], "</span><!--", $cookie[ "expires" ], "--></td>";
    }

    if ( $cookie[ "valid" ] == 0 )
    {
      echo "<td>", _ ( "Session" ), "<!--0--></td>";
    }
    else
    {
      $span = humanReadableTimeSpan ( $cookie[ "valid" ] );
      echo "<td", onTableToggleEvent ( $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] ), "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">";
      echo explode ( ",",
                     $span )[ 0 ], $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\">$span</span><!--", $cookie[ "valid" ], "--></td>";
    }

    echo "<td", onTableToggleEvent ( $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] ), ">";
    echo $cookie[ "httponly" ] ? "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] . "\"><i class=\"fa fa-code\" title=\"httponly\"></i></span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] . "\">" . _ ( "für Skripte nicht zugänglich (\"httponly\")" ) . "</span><br>" : "";
    echo $cookie[ "secure" ] ? "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] . "\"><i class=\"fa fa-key\" title=\"secure\"></i></span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] . "\">" . _ ( "nur verschlüsselter Versand (\"secure\")" ) . "</span><br>" : "";
    echo $cookie[ "comment" ] ? "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] . "\"><i class=\"fa fa-commenting-o\" title=\"comment\"></i></span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $__[ "include/showReceivedCookies" ][ "ids" ][ "tables" ][ "cookies" ] . "\">" . _ ( "Kommentar" ) . ": \"" . $cookie[ "comment" ] . "\"</span>" : "";
    echo "</td>";

    if ( !isset ( $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ] ) )
    {
      $selectSentCookieStatement = $db->prepare ( "select count(*) from sendcookie where cookie=? and wert=? and verbindung=?" );
      $selectSentCookieStatement->execute ( array (
        $cookie[ "cookie" ],
        $cookie[ "wert" ],
        ${$__[ "include/connectionBrowser" ][ "vars" ] [ "connection" ]}[ "id" ] ) );
      echo "<td class=\"numeric\">", $selectSentCookieStatement->fetchColumn (), _ ( " mal" ), "</td>";
    }
  }
  while ( $cookie = $$__[ "include/showReceivedCookies" ] [ "vars" ][ "statement" ]->fetch () );

  echo "</tbody></table></div>";
}
echo "</div></div></div>";

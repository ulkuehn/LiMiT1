<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/showSentCookies.php
 *
 * display a table of all cookies that were sent in a specific request or connection
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

echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "include/showSentCookies" ] [ "ids" ][ "cookies" ], "\"><h4 class=\"panel-title\">";

if ( ($cookie = $$__[ "include/showSentCookies" ] [ "vars" ][ "statement" ]->fetch ()) == false )
{
  echo "<span class=\"emptyPanel\">", _ ( "Versandte Cookies" ), "</span></h4></div><div id=\"", $__[ "include/showSentCookies" ] [ "ids" ][ "cookies" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Es wurden keine Cookies versandt." ), "</p>";
}
else
{
  echo _ ( "Versandte Cookies" ), "</h4></div><div id=\"", $__[ "include/showSentCookies" ] [ "ids" ][ "cookies" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";

  echo tableSorter ( $__[ "include/showSentCookies" ][ "ids" ][ "tables" ][ "cookies" ],
                     "columns: [ {orderable:false, searchable:false}, {}, {}, {}" . (!isset ( $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ] ) ? ", {type:'num'}, {}" : "") . " ], order: [ [1,'asc'] ]" );

  $foldMe = tableFolder ( $__[ "include/showSentCookies" ][ "ids" ][ "tables" ][ "cookies" ] );
  echo "<div class=\"table-responsive\"><table id=\"", $__[ "include/showSentCookies" ][ "ids" ][ "tables" ][ "cookies" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldMe</th>";
  echo "<th>", _ ( "Name" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th>";
  echo "<th>", _ ( "Site" ), "</th>";
  if ( !isset ( $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ] ) )
  {
    echo "<th>", _ ( "versandt" ), "</th>";
    echo "<th>", _ ( "empfangen" ), "</th>";
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
                             $__[ "include/showSentCookies" ][ "ids" ][ "tables" ][ "cookies" ] );
    echo foldableTableCell ( $cookie[ "wert" ],
                             $__[ "include/showSentCookies" ][ "ids" ][ "tables" ][ "cookies" ] );
    echo foldableTableCell ( $cookie[ "site" ],
                             $__[ "include/showSentCookies" ][ "ids" ][ "tables" ][ "cookies" ],
                             true );

    if ( !isset ( $$__[ "include/requestBrowser" ][ "vars" ] [ "request" ] ) )
    {
      echo "<td class=\"numeric\">", $cookie[ "_requests" ], _ ( " mal" ), "</td>";
      $selectSetCookieStatement = $db->prepare ( "select count(*) from setcookie where cookie=? and wert=? and verbindung=?" );
      $selectSetCookieStatement->execute ( array (
        $cookie[ "cookie" ],
        $cookie[ "wert" ],
        ${$__[ "include/connectionBrowser" ][ "vars" ] [ "connection" ]}[ "id" ] ) );
      echo "<td>", $selectSetCookieStatement->fetchColumn () ? _ ( "ja" ) : _ ( "nein" ), "</td>";
    }

    echo "</tr>";
  }
  while ( $cookie = $$__[ "include/showSentCookies" ] [ "vars" ][ "statement" ]->fetch () );

  echo "</tbody></table></div>";
}
echo "</div></div></div>";

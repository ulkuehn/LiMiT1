<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file whois.php
 * 
 * used to display and obtain whois data for a domain
 * all whois data is kept in the LiMiT1 database for future use,
 * and offline queries are preferred over online queries
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/tableUtility.php");
require_once ("include/timeUtility.php");
require_once ("include/utility.php");
require_once ("include/connectDB.php");

include ("include/httpHeaders.php");

$$__[ "include/openHTML" ][ "vars" ][ "title" ] = " - " . _ ( "Whois" );
$$__[ "include/openHTML" ][ "vars" ][ "frame" ] = $__usetabs ? $__[ "whois" ][ "names" ][ "frame" ] : "";
include ("include/openHTML.php");

include ("include/topMenu.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * do a whois query for a domain either online or offline
 * 
 * @param string $domainName domain name to be whois'd
 * @return array(string,boolean): html containing the query result; true if lookup was online, false if offline
 */
function whoisLookup ( $domainName )
{
  global $db, $my_name, $__, $temp_dir;

  /*
   * string having html results in
   */
  $resultString = "";
  /*
   * flag to indicate if result has been yielded online
   */
  $doOnline = false;
  $insertID = 0;

  $selectWhoisStatement = $db->prepare ( "select *, unix_timestamp()-unix_timestamp(stand) as _tspan, date_format(stand,'%e.%c.%Y') as _standd, date_format(stand,'%H:%i:%s') as _standt from whois where domain=? order by stand desc" );
  $selectWhoisStatement->execute ( array (
    $domainName ) );

  /*
   * we do an online query if no offline result avail or explicitely asked for an update query
   */
  if ( ($whoisFetch = $selectWhoisStatement->fetch ()) == false || isset ( $_POST[ $__[ "whois" ] [ "params" ] [ "refresh" ] ] ) )
  {
    $doOnline = true;
    if ( file_exists ( $temp_dir . "/" . $__[ "include/goOnline" ] [ "values" ][ "onlineFlag" ] ) )
    {
      /*
       * whois Version 5.2.7 return value gives no indication if query was succesful (?)
       */
      exec ( "/usr/bin/whois -H $domainName",
             $whoisResult,
             $whoisReturn );
      $whoisResult = implode ( "\n",
                               $whoisResult );
      $insertStatement = $db->prepare ( "insert into whois set domain=?, whois=?, stand=now(), okay=?" );
      /*
       * see above, so $whoisReturn bears no information; always use TRUE
       */
      $insertStatement->execute ( array (
        $domainName,
        $whoisResult,
        TRUE ) );
      $insertID = $db->lastInsertId ();

      /*
       * repeat database query to get consistens results in $whoisFetch
       */
      $selectWhoisStatement = $db->prepare ( "select *, unix_timestamp()-unix_timestamp(stand) as _tspan, date_format(stand,'%e.%c.%Y') as _standd, date_format(stand,'%H:%i:%s') as _standt from whois where domain=? order by stand desc" );
      $selectWhoisStatement->execute ( array (
        $domainName ) );
      $whoisFetch = $selectWhoisStatement->fetch ();
    }
  }

  /*
   * collect current and historical whois info in result to return
   */
  do
  {
    $fetchID = $whoisFetch[ "id" ];
    if ( $fetchID == $insertID )
    {
      $headerInfo = _ ( "(aktuelle Abfrage)" );
    }
    else
    {
      $headerInfo = _ ( "(gespeicherter Stand vom " ) . $whoisFetch[ "_standd" ] . " <i class=\"fa fa-clock-o\"></i> " . $whoisFetch[ "_standt" ] . " = " . humanReadableDuration ( $whoisFetch[ "_tspan" ] ) . _ ( " her)" );
    }

    $resultString .= "<div class=\"panel panel-" . ((!$whoisFetch[ "okay" ] || $whoisFetch[ "whois" ] == "" ) ? "danger" : "success") . "\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#$fetchID\"><h4 class=\"panel-title\">$domainName $headerInfo</h4></div>";
    $resultString .= "<div id=\"$fetchID\" class=\"panel-collapse collapse" . ($fetchID == $insertID ? " in" : "") . " role=\"tabpanel\"><div class=\"panel-body\">";

    if ( !$whoisFetch[ "okay" ] || $whoisFetch[ "whois" ] == "" )
    {
      $resultString .= showAlertMessage ( _ ( "Die Whois-Abfrage von \"$domainName\" war nicht erfolgreich" ),
                                              false );
    }
    else
    {
      $resultString .= tableSorter ( "tab" . $fetchID,
                                     "columns: [ {}, {} ], order: [ [0,'asc'] ]" );

      $resultString .= "<p class=\"text-right\"><button class=\"btn btn-info btn-sm\" title=\"" . _ ( "Textansicht" ) . "\" onclick=\"document.getElementById('" . $__[ "whois" ] [ "ids" ] [ "tableViewPrefix" ] . $fetchID . "').style.display='none'; document.getElementById('" . $__[ "whois" ] [ "ids" ] [ "textViewPrefix" ] . $fetchID . "').style.display='block'; return false;\"><i class=\"fa fa-align-left\"></i></button> ";

      $resultString .= "<button class=\"btn btn-info btn-sm\" title=\"" . _ ( "Tabellenansicht" ) . "\" onclick=\"document.getElementById('" . $__[ "whois" ] [ "ids" ] [ "textViewPrefix" ] . $fetchID . "').style.display='none'; document.getElementById('" . $__[ "whois" ] [ "ids" ] [ "tableViewPrefix" ] . $fetchID . "').style.display='block'; return false;\"><i class=\"fa fa-table\"></i></button></p>";

      $resultString .= "<div class=\"row\"><div id=\"" . $__[ "whois" ] [ "ids" ] [ "textViewPrefix" ] . $fetchID . "\"><pre>" . $whoisFetch[ "whois" ] . "</pre></div><div class=\"table-responsive\" id=\"" . $__[ "whois" ] [ "ids" ] [ "tableViewPrefix" ] . $fetchID . "\" style=\"display:none\"><table class=\"table table-hover\"><thead><tr><th>" . _ ( "Feld" ) . "</th><th>" . _ ( "Wert" ) . "</th></tr></thead><tbody>";

      $lines = array ();
      foreach ( explode ( PHP_EOL,
                          $whoisFetch[ "whois" ] ) as $line )
      {
        if ( preg_match ( "/^([^:]+):\s+(.*)/",
                          $line,
                          $match ) )
        {
          if ( !array_key_exists ( $match[ 1 ] . $match[ 2 ],
                                   $lines ) )
          {
            $resultString .= "<tr><td>" . $match[ 1 ] . "</td><td>" . $match[ 2 ] . "</td></tr>";
            $lines[ $match[ 1 ] . $match[ 2 ] ] = TRUE;
          }
        }
      }
      $resultString .= "</tbody></table></div></div>";
    }
    $resultString .= "</div></div></div>";
  }
  while ( $whoisFetch = $selectWhoisStatement->fetch () );

  return array (
    $resultString,
    $doOnline );
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

$domain = array_key_exists ( $__[ "whois" ] [ "params" ][ "whois" ],
                             $_REQUEST ) ? strtolower ( trim ( $_REQUEST[ $__[ "whois" ] [ "params" ][ "whois" ] ] ) ) : "";
$domainValue = htmlspecialchars ( $domain );

titleAndHelp ( _ ( "Whois-Abfrage" ),
                   _ ( "Die Whois-Informationen zu einer Domain enthalten viele hilfreiche Hinweise. Bei Bedarf kann mit dieser Funktion eine entsprechende Abfrage durchgeführt werden. Die über das Internet erhaltenen Informationen werden in der Datenbank gespeichert, so dass sie nicht jedes Mal erneut abgerufen werden müssen.<br><br>Die Abfrage der gespeicherten Whois-Daten ist auch \"mit Musterzeichen\" möglich. Der Domainstring wird dann als regulärer Ausdruck interpretiert. Dann haben u.a. folgende Zeichen eine besondere Bedeutung:</p>" ) . "<table class=\"table table-condensed\"><tbody>" . "<tr><td><strong>.</strong></td><td>" . _ ( "beliebiges Zeichen" ) . "</td></tr>" . "<tr><td><strong>?</strong></td><td>" . _ ( "vorheriges Zeichen kommt nicht oder einmal vor" ) . "</td></tr>" . "<tr><td><strong>*</strong></td><td>" . _ ( "vorheriges Zeichen kommt nicht, ein- oder mehrmals vor" ) . "</td></tr>" . "<tr><td><strong>+</strong></td><td>" . _ ( "vorheriges Zeichen kommt ein- oder mehrmals vor" ) . "</td></tr>" . "<tr><td><strong>^</strong></td><td>" . _ ( "Textanfang" ) . "</td></tr>" . "<tr><td><strong>$</strong></td><td>" . _ ( "Textende" ) . "</td></tr>" . "<tr><td><strong>|</strong></td><td>" . _ ( "Oder-Verknüpfung" ) . "</td></tr></tbody></table>" );

/*
 * form
 */
echo "<form method=\"post\" class=\"form-horizontal\"><div class=\"row\" style=\"margin-bottom:20px\"><div class=\"input-group\">";
echo "<span class=\"input-group-btn\"><input type=\"submit\" class=\"btn btn-primary\" value=\"", _ ( "Domain" ), "\"></span>";
echo "<input class=\"form-control\" type=\"text\" id=\"", $__[ "whois" ] [ "params" ][ "whois" ], "\" name=\"", $__[ "whois" ] [ "params" ][ "whois" ], "\" value=\"$domainValue\">";
echo "<span class=\"input-group-btn\"><button type=\"button\" class=\"btn btn-default\" onclick=\"document.getElementById('", $__[ "whois" ] [ "params" ][ "whois" ], "').value='';\">&times;</button></span></div>";
echo "<div class=\"checkbox\"><p class=\"text-right\"><label><input type=\"checkbox\" name=\"", $__[ "whois" ][ "params" ] [ "regexp" ], "\"", ( array_key_exists ( $__[ "whois" ][ "params" ] [ "regexp" ],
                                                                                                                                                                    $_REQUEST ) && $_REQUEST[ $__[ "whois" ][ "params" ] [ "regexp" ] ] == "on" ? " checked" : "" ), ">", _ ( "mit Musterzeichen (nur gespeicherte Informationen abrufen)" ), "</label></p></div></div></form>";

/*
 * ignore empty input
 */
if ( $domain != "" )
{
  /*
   * with regexp
   */
  if ( $_REQUEST[ $__[ "whois" ][ "params" ] [ "regexp" ] ] )
  {
    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Domains" ), "</h4></div><div class=\"panel-body\">";
    echo tableSorter ( $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ],
                       "columns: [ {orderable:false, searchable:false}, {type:'num'}, {type:'date'}, {orderable:false,width:'67%'} ], order: [ [1,'num'] ]" );
    $foldUnfoldButton = tableFolder ( $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ] );

    echo "<div class=\"table-responsive\"><table id=\"", $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ], "\" class=\"table table-hover\"><thead><tr>";
    echo "<th>$foldUnfoldButton</th>";
    echo "<th>Name</th>";
    echo "<th><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ], "\">Alter</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ], "\">Stand</span></th>";
    echo "<th>Whois</th></tr></thead><tbody>";

    /*
     * get all domain names in db and order them appropriately
     */
    $selectWhoisDomainStatement = $db->prepare ( "select distinct domain from whois where domain REGEXP ? order by domain desc" );
    $selectWhoisDomainStatement->execute ( array (
      $domain ) );
    $allDomains = $selectWhoisDomainStatement->fetchAll ( PDO::FETCH_COLUMN,
                                                          0 );
    usort ( $allDomains,
            "hostnameSort" );
    $domainOrder = count ( $allDomains );

    foreach ( $allDomains as $whoisDomain )
    {
      $selectWhoisStatement = $db->prepare ( "select *, unix_timestamp()-unix_timestamp(stand) as _tspan, date_format(stand,'%e.%c.%Y') as _standd, date_format(stand,'%H:%i:%s') as _standt from whois where domain=? order by okay desc, stand desc limit 1" );
      $selectWhoisStatement->execute ( array (
        $whoisDomain ) );
      $whois = $selectWhoisStatement->fetch ();

      echo "<tr><td>", showIconButton ( "fa fa-eye",
                                        "whois.php?" . $__[ "whois" ][ "params" ] [ "whois" ] . "=$whoisDomain",
                                        $__[ "whois" ][ "titles" ][ "viewWhois" ],
                                        $whois[ "okay" ] ? "success" : "danger",
                                        $__usetabs ? $__[ "whois" ] [ "names" ] [ "frame" ] : ""
      ), "</td>";

      echo "<td>$whoisDomain<!--$domainOrder--></td>";

      echo "<td", onTableToggleEvent ( $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ] ), "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ], "\">", humanReadableDuration ( $whois[ "_tspan" ] ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ], "\">", $whois[ "_standd" ], " <i class=\"fa fa-clock-o\"></i> ", $whois[ "_standt" ], "<!--", $whois[ "stand" ], "--></td>";

      echo "<td", onTableToggleEvent ( $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ] ), "><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ], "\">", explode ( PHP_EOL,
                                                                                                                                                                                                                                       $whois[ "whois" ] )[ 0 ], $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "whois" ][ "ids" ][ "tables" ][ "whois" ], "\"><pre class=\"pre-scrollable\">", $whois[ "whois" ], "</pre></span></td></tr>";

      $domainOrder--;
    }
    echo "</tbody></table></div></div></div>";
  }

  /*
   * no regexp: check validity of input
   */
  else if ( ( preg_match ( "/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i",
                           $domain ) &&
    preg_match ( "/^.{1,253}$/",
                 $domain ) &&
    preg_match ( "/^[^\.]{1,63}(\.[^\.]{1,63})*$/",
                 $domain ) ) || ip2long ( $domain ) != false )
  {
    if ( file_exists ( $temp_dir . "/" . $__[ "include/goOnline" ] [ "values" ][ "onlineFlag" ] ) )
    {
      echo "<form method=\"post\" class=\"form-horizontal\"><div class=\"row\" style=\"margin-bottom:20px\"><p class=\"text-right\"><input type=\"hidden\" name=\"", $__[ "whois" ] [ "params" ][ "whois" ], "\" value=\"$domain\"><button type=\"submit\" class=\"btn btn-primary btn-sm\" title=\"", _ ( "Aktualisieren" ), "\" name=\"", $__[ "whois" ] [ "params" ] [ "refresh" ], "\">", _ ( "$domainValue online abfragen" ), " <i class=\"fa fa-refresh\"></i></button></p></div></form>";
    }

    $result = "<div class=\"row\"><div class=\"panel-group\" role=\"tablist\">";
    $doneOnline = false;

    list ($r, $o) = whoisLookup ( $domain );
    $result .= $r;
    $doneOnline |= $o;

    /*
     * if domain is a real domain name (not an ip address), do additional whois queries for father levels of domain hierarchy
     */
    if ( ip2long ( $domain ) == false )
    {
      $parts = explode ( ".",
                         $domain );
      /*
       * we need at least two name parts (no query on tld alone)
       */
      if ( count ( $parts ) > 2 )
      {
        $result .= "<p style=\"margin-top:20px\" class=\"text-right\">" . _ ( "Superdomains (von denen $domainValue eine Subdomain ist)" ) . "</p>";
      }
      while ( count ( $parts ) > 2 )
      {
        array_shift ( $parts );
        list ($r, $o) = whoisLookup ( implode ( ".",
                                                $parts ) );
        $result .= $r;
        $doneOnline |= $o;
      }
    }

    if ( !file_exists ( $temp_dir . "/" . $__[ "include/goOnline" ] [ "values" ][ "onlineFlag" ] ) && $doneOnline )
    {
      echo "<div class=\"row\">";
      showErrorMessage ( _ ( "$my_name ist offline. Eine Whois-Abfrage ist nur bei bestehender Internetverbindung möglich." ) );
      echo "</div>";
    }
    echo $result;
  }
  /*
   * input not valid
   */
  else
  {
    echo "<div class=\"row\">";
    showErrorMessage ( _ ( "\"$domainValue\" ist kein gültiger Domainname" ) );
  }

  echo "</div>";
}

include ("include/closeHTML.php");

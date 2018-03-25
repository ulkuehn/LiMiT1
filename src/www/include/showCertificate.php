<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/showCertificate.php
 * 
 * display the certificate specifics of an encrypted connection
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
require_once ("include/certificateUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "include/showCertificate" ] [ "ids" ][ "certificate" ], "\"><h4 class=\"panel-title\">";

if ( isset ( $connection ) && ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl" ) )
{
  echo _ ( "Zertifikat" ), "</h4></div><div id=\"", $__[ "include/showCertificate" ] [ "ids" ][ "certificate" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";

  $selectCertificateIDStatement = $db->prepare ( "select zertifikat from " . ($connection[ "typ" ] == "https" ? "https" : "ssltls") . " where verbindung=?" );
  $selectCertificateIDStatement->execute ( array (
    $connection[ "id" ] ) );
  $certificateID = $selectCertificateIDStatement->fetchColumn ();

  if ( !$certificateID )
  {
    echo _ ( "<p>Für diese Verbindung ist kein Zertifikat vorhanden.</p>" );
  }
  else
  {
    $foldMe = tableFolder ( $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] );

    echo "<div class=\"table-responsive\"><table id=\"", $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\" class=\"table table-hover\"><thead><tr>";
    echo "<th>", _ ( "Eigenschaft" ), "</th>";
    echo "<th>", _ ( "Wert" ), "</th>";
    echo "<th>", _ ( "Erläuterung" ), "</th></tr></thead><tbody>";

    $selectCertificateStatement = $db->prepare ( "select *, date_format(notbefore, '%T') as _notbeforetime, date_format(notbefore, '%e.%c.%Y') as _notbeforedate, date_format(notafter, '%T') as _notaftertime, date_format(notafter, '%e.%c.%Y') as _notafterdate, datediff(notafter,notbefore) as _tage from zertifikat where id=?" );
    $selectCertificateStatement->execute ( array (
      $certificateID ) );
    $certificate = $selectCertificateStatement->fetch ();
    $selectCertificateEarlyLateStatement = $db->prepare ( "select ?<? as early, ?>? as late" );
    $selectCertificateEarlyLateStatement->execute ( array (
      $connection[ "zeit" ],
      $certificate[ "notbefore" ],
      $connection[ "zeit" ],
      $certificate[ "notafter" ] ) );
    $certificateEarlyLate = $selectCertificateEarlyLateStatement->fetch ();

    /*
     * serial number
     */
    echo "<tr", onTableToggleEvent ( $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] ), "><td>", _ ( "Seriennummer" ), "</td><td>", $certificate[ "serial" ], "</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", _ ( "Die Seriennummer dient zur Identifikation des Zertifikats; sie ist eindeutig für jede Zertifizierungsstelle" ), "</span></td></tr>";

    /*
     * validity
     */
    if ( $certificateEarlyLate[ "early" ] )
    {
      $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
      $explanation = _ ( "Das Zertifikat war zum Zeitpunkt der Verbindung noch nicht gültig." );
    }
    else if ( $certificateEarlyLate[ "late" ] )
    {
      $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
      $explanation = _ ( "Das Zertifikat war zum Zeitpunkt der Verbindung nicht mehr gültig." );
    }
    else
    {
      $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
      $explanation = _ ( "Das Zertifikat war zum Zeitpunkt der Verbindung gültig." );
    }
    echo "<tr", onTableToggleEvent ( $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] ), "><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", _ ( "Gültigkeit" ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", _ ( "Gültigkeit (GMT)" ), "</span></td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", $certificate[ "_notbeforedate" ], " &nbsp;bis&nbsp; ", $certificate[ "_notafterdate" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", $certificate[ "_notbeforedate" ], " <i class=\"fa fa-clock-o\"></i> ", $certificate[ "_notbeforetime" ], " &nbsp;bis&nbsp; ", $certificate[ "_notafterdate" ], " <i class=\"fa fa-clock-o\"></i> ", $certificate[ "_notaftertime" ], "<br>(= ", $certificate[ "_tage" ], _ ( " Tage)" ), "</span></td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">$sign", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">$sign $explanation</span></td></tr>";

    /*
     * issued for
     */
    rdnInfo ( _ ( "ausgestellt für" ),
                  "",
                  $certificate[ "subject" ],
                  $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] );

    /*
     * issued by
     */
    rdnInfo ( _ ( "ausgestellt von" ),
                  $certificate[ "subject" ] == $certificate[ "issuer" ] ? _ ( "Es handelt sich um ein selbst signiertes Zertifikat (Aussteller und Inhaber sind identisch)" ) : "",
                                                                              $certificate[ "issuer" ],
                                                                              $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] );

    /*
     * domains covered
     */
    $sign = $__[ "include/utility" ][ "values" ] [ "mehSign" ];
    $names = array ();
    $domain = "";
    if ( !$connection[ "host" ] )
    {
      $hostNames = ipHostinfo ( $connection[ "ip" ] );
    }
    else
    {
      $hostNames = idHostinfo ( $connection[ "host" ] );
    }

    foreach ( explode ( ",",
                        $certificate[ "names" ] ) as $name )
    {
      $show = $name;
      foreach ( $hostNames as $hostName )
      {
        /*
         * the second preg_match also matches "*.a.b" for "a.b" (not only "c.a.b" etc.)
         * this is a common behaviour, even though possibly not RFC 2595 compliant ?
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
          $show = "<strong>$name</strong>";
          $domain .= "$name ";
        }
      }
      array_push ( $names,
                   $show );
    }

    if ( $sign == $__[ "include/utility" ][ "values" ] [ "goodSign" ] )
    {
      if ( count ( $names ) == 1 )
      {
        $wert = $domain;
        $explanation = _ ( "Die Domain, für die das Zertifikat ausgestellt ist, passt zum Servernamen" );
      }
      else
      {
        $wert = implode ( "<br>",
                          $names ); //"<span class=\"".$__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ]."Zertifikat\">$dom</span><span class=\"".$__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ]."Zertifikat\">".implode(" &nbsp; ",$names)."</span>";
        $explanation = _ ( "Mindestens eine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen. Sie ist/sind hervorgehoben" );
      }
    }
    else
    {
      $wert = implode ( "<br>",
                        $names );
      if ( count ( $names ) == 1 )
      {
        $explanation = _ ( "Die Domain, für die das Zertifikat ausgestellt ist, passt nicht zum Servernamen" );
      }
      else
      {
        $explanation = _ ( "Keine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen" );
      }
    }

    echo "<tr", onTableToggleEvent ( $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ] ), "><td>", _ ( "Domain(s)" ), "</td><td>$wert</td><td>$sign <span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showCertificate" ][ "ids" ][ "tables" ][ "certificate" ], "\">$explanation</span></td></tr></tbody></table></div>";
  }
}
else
{
  echo "<span class=\"emptyPanel\">", _ ( "Zertifikat" ), "</span></h4></div><div id=\"", $__[ "include/showCertificate" ] [ "ids" ][ "certificate" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Die Verbindung ist nicht verschlüsselt, daher ist kein Zertifikat vorhanden." ), "</p>";
}
echo "</div></div></div>";

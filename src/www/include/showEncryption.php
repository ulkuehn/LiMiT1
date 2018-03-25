<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/showEncryption.php
 * 
 * display the ssl specifics of an encrypted connection
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

echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "include/showEncryption" ] [ "ids" ][ "encryption" ], "\"><h4 class=\"panel-title\">";

if ( isset ( $connection ) && ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl" ) )
{
  echo _ ( "Verschlüsselung" ), "</h4></div><div id=\"", $__[ "include/showEncryption" ] [ "ids" ][ "encryption" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";

  $selectCryptStatement = $db->prepare ( "select * from " . ($connection[ "typ" ] == "https" ? "https" : "ssltls") . " where verbindung=?" );
  $selectCryptStatement->execute ( array (
    $connection[ "id" ] ) );
  $crypt = $selectCryptStatement->fetch ();

  $foldUnfoldButton = tableFolder ( $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>", _ ( "Eigenschaft" ), "</th>";
  echo "<th>", _ ( "Wert" ), "</th>";
  echo "<th>", _ ( "Erläuterung" ), "</th></tr></thead><tbody>";

  $selectCipherSuiteStatement = $db->prepare ( "select * from cipherSuite where id=?" );
  $selectCipherSuiteStatement->execute ( array (
    $crypt[ "ciphersuite" ] ) );
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

  /*
   * version
   */
  switch ( strtolower ( $crypt[ "sslversion" ] ) )
  {
    case "sslv2":
    case "sslv3":
      $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
      $explanation = _ ( "SSLv2 und SSLv3 sind unsicher und sollten nicht mehr verwendet werden." );
      break;
    case "tlsv1.0":
    case "tlsv1.1":
      $sign = $__[ "include/utility" ][ "values" ] [ "mehSign" ];
      $explanation = _ ( "TLS ist eine Verbesserung der veralteten SSL-Protokolls. Es sollte jedoch die aktuelle Version TLSv1.2 verwendet werden." );
      break;
    case "tlsv1.2":
      $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
      $explanation = _ ( "TLSv1.2 ist die aktuelle Version." );
      break;
    default:
      $sign = $__[ "include/utility" ][ "values" ] [ "questSign" ];
      $explanation = _ ( "Unbekannte Version." );
  }
  echo "<tr", onTableToggleEvent ( $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ] ), "><td>", _ ( "TLS-Version" ), "</td><td>", $crypt[ "sslversion" ], "</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign $explanation</span></td></tr>";

  /*
   * key length
   */
  if ( $crypt[ "effBits" ] < 128 )
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
    $explanation = _ ( "Verschlüsselungen mit einer Schlüssellänge von unter 128 Bit sind unsicher." );
  }
  else if ( $crypt[ "effBits" ] < 256 )
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "mehSign" ];
    $explanation = _ ( "Schlüssellängen mit 128 Bit und mehr gelten als sicher. Optimal wären Schlüssel ab 256 Bit." );
  }
  else
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
    $explanation = _ ( "Verfahren mit Schlüsseln ab 256 Bit Länge sind optimal." );
  }
  echo "<tr", onTableToggleEvent ( $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ] ), "><td>", _ ( "Schlüssellänge" ), "</td><td>", $crypt[ "effBits" ], _ ( " Bits" ), "</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign $explanation</span></td></tr>";

  /*
   * forward secrecy
   */
  if ( $keyExchange[ "forwardSecrecy" ] )
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
    $explanation = _ ( "Forward Secrecy stellt sicher, dass eine aufgezeichnete verschlüsselte Kommunikation nicht nachträglich entschlüsselt werden kann." );
  }
  else
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "mehSign" ];
    $explanation = _ ( "Ohne Forward Secrecy besteht das Risiko, dass aufgezeichnete Kommunikationsströme entschlüsselt werden können, wenn das Verschlüsselungsverfahren gebrochen wird." );
  }
  echo "<tr", onTableToggleEvent ( $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ] ), "><td>", _ ( "Forward Secrecy" ), "</td><td>", $keyExchange[ "forwardSecrecy" ] ? _ ( "ja" ) : _ ( "nein" ), "</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign $explanation</span></td></tr>";

  /*
   * key exchange
   */
  if ( $keyExchange[ "secure" ] )
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
    $explanation = _ ( "Das Schlüsselaustauschverfahren " ) . $keyExchange[ "shortName" ] . _ ( " gilt als sicher." );
  }
  else
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
    $explanation = _ ( "Das Schlüsselaustauschverfahren " ) . $keyExchange[ "shortName" ] . _ ( " gilt als unsicher." );
  }
  echo "<tr", onTableToggleEvent ( $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ] ), "><td>", _ ( "Schlüsselaustauschverfahren" ), "</td><td>", $keyExchange[ "longName" ], "</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign $explanation</span></td></tr>";

  /*
   * quality
   */
  if ( $cipher[ "secure" ] )
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
    $explanation = _ ( "Das Verschlüsselungsverfahren " ) . $cipher[ "shortName" ] . _ ( " gilt als sicher." );
  }
  else
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
    $explanation = _ ( "Das Verschlüsselungsverfahren " ) . $cipher[ "shortName" ] . _ ( " gilt als unsicher." );
  }
  echo "<tr", onTableToggleEvent ( $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ] ), "><td>", _ ( "Verschlüsselungsverfahren" ), "</td><td>", $cipher[ "longName" ], " (", $cipher[ "bits" ], _ ( " Bits)" ), "</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign $explanation</span></td></tr>";

  /*
   * message digest (hashing)
   */
  if ( $mac[ "secure" ] )
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "goodSign" ];
    $explanation = _ ( "Das Hashverfahren " ) . $mac[ "shortName" ] . _ ( " gilt als sicher." );
  }
  else
  {
    $sign = $__[ "include/utility" ][ "values" ] [ "badSign" ];
    $explanation = _ ( "Das Hashverfahren " ) . $mac[ "shortName" ] . _ ( " gilt als unsicher." );
  }
  echo "<tr", onTableToggleEvent ( $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ] ), "><td>", _ ( "Hashverfahren" ), "</td><td>", $mac[ "longName" ], " (", $mac[ "bits" ], _ ( " Bits)" ), "</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign", $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "include/showEncryption" ][ "ids" ][ "tables" ][ "encryption" ], "\">$sign $explanation</span></td></tr></tbody></table></div>";
}
else
{
  echo "<span class=\"emptyPanel\">", _ ( "Verschlüsselung" ), "</span></h4></div><div id=\"", $__[ "include/showEncryption" ] [ "ids" ][ "encryption" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\"><p>", _ ( "Die Verbindung ist nicht verschlüsselt." ), "</p>";
}
echo "</div></div></div>";

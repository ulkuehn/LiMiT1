<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/certificateUtility.php
 * 
 * common definitions needed for all scripts displaying certificate details
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
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * display RDN info (relative distinguished name) of a certificate as a table row
 * 
 * @param string $rdnFieldText text to show in first column
 * @param string $warningMessage optional message to show in third column
 * @param string $rdnString rdn info string
 * @param string $tableID html id of the table the row is a part of
 */
function rdnInfo ( $rdnFieldText,
                   $warningMessage,
                   $rdnString,
                   $tableID )
{
  global $__;

  $valueColumn = "";
  $explanation = array ();
  $organisation = "";
  $commonName = "";
  $rdnKeys = array ();

  /*
   * take apart rdn string and process all elements
   */
  foreach ( explode ( ", ",
                      $rdnString ) as $rdnPart )
  {
    if ( preg_match ( "/^(.*)=(.*)$/U",
                      $rdnPart,
                      $match ) )
    {
      $rdnKeys[ $match[ 1 ] ] = 1;
      switch ( $match[ 1 ] )
      {
        case "O":
          $organisation .= $match[ 2 ] . " ";
          break;
        case "CN":
          $commonName .= $match[ 2 ] . " ";
          break;
      }
    }
  }

  if ( $organisation != "" || $commonName != "" )
  {
    $valueColumn = "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . ($organisation != "" ? $organisation : $commonName) . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . $rdnString . "</span>";
  }
  else
  {
    $valueColumn = $rdnString;
  }

  /*
   * process all rdn elements that were found
   */
  ksort ( $rdnKeys );
  foreach ( array_keys ( $rdnKeys ) as $rdnKey )
  {
    switch ( strtoupper ( $rdnKey ) )
    {
      case "C":
        array_push ( $explanation,
                     _ ( "C: Land (Country)" ) );
        break;
      case "ST":
        array_push ( $explanation,
                     _ ( "ST: Staat/Provinz (State)" ) );
        break;
      case "L":
        array_push ( $explanation,
                     _ ( "L: Ort (Locality)" ) );
        break;
      case "O":
        array_push ( $explanation,
                     _ ( "O: Organisation/Firma etc." ) );
        break;
      case "OU":
        array_push ( $explanation,
                     _ ( "OU: Abteilung (Organizational Unit)" ) );
        break;
      case "CN":
        array_push ( $explanation,
                     _ ( "CN: Name (Common Name)" ) );
        break;
      default:
        array_push ( $explanation,
                     strtoupper ( $rdnKey ) . ": ???" );
        break;
    }
  }

  echo "<tr", onTableToggleEvent ( $tableID ), "><td>$rdnFieldText</td><td>$valueColumn</td><td><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $tableID, "\">", $warningMessage == "" ? "" : $__[ "include/utility" ][ "values" ] [ "badSign" ], $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ], "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $tableID, "\">", $warningMessage == "" ? "" : $__[ "include/utility" ][ "values" ] [ "badSign" ] . " " . $warningMessage . "<br>", implode ( ", ",
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              $explanation ), "</span></td></tr>";
}


<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateSslsuites.php
 * 
 * display ssl connections with some of their specifics
 * more details can be obtained with drilldown script showSslsuite.pho
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
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * 
 * @global type $db
 * @param type $recordingID
 * @param type $tableID
 * @return type
 */
function tabulateEncryptions ( $recordingID,
                               $tableID )
{
  global $db, $__;

  if ( $recordingID == 0 )
  {
    $selectCipherSuitesStatement = $db->prepare ( "select ciphersuite,sum(v) as verbindungen
                              from
                              (
                                select ciphersuite,count(*) as v from https group by ciphersuite
                                union all
                                select ciphersuite,count(*) as v from ssltls group by ciphersuite
                              ) t
                              group by ciphersuite" );
    $selectCipherSuitesStatement->execute ();
  }
  else
  {
    $selectCipherSuitesStatement = $db->prepare ( "select ciphersuite,sum(v) as verbindungen
                              from
                              (
                                select ciphersuite,count(*) as v from https where aufzeichnung=? group by ciphersuite
                                union all
                                select ciphersuite,count(*) as v from ssltls where aufzeichnung=? group by ciphersuite
                              ) t
                              group by ciphersuite" );
    $selectCipherSuitesStatement->execute ( array (
      $recordingID,
      $recordingID ) );
  }

  if ( ($cipherSuites = $selectCipherSuitesStatement->fetch ()) == false )
  {
    return array (
      false,
      _ ( "Es wurde keine Verschlüsselung verwendet." ) );
  }
  else
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID,
                                                     false );
    $table .= tableSorter ( $tableID,
                            "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );

    $table .= "<div class=\"table-responsive\"><table id=\"$tableID\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . _ ( "Verschl." ) . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "Ver-<br>schlüsselung" ) . "</span></th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . _ ( "Bits" ) . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "Schlüssel-<br>länge" ) . "</span></th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . _ ( "Austausch" ) . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "Schlüssel-<br>austausch" ) . "</span></th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . _ ( "PFS" ) . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "Forward<br>Secrecy" ) . "</span></th>";
    $table .= "<th>" . _ ( "Hash" ) . "<span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . ("-<br>Verfahren") . "</span></th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "verwendet von " ) . "</span>" . _ ( "Hosts" ) . "</th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . _ ( "Verb." ) . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "Verbindungen" ) . "</span></th></tr></thead><tbody>";

    do
    {
      $selectCipherSuiteStatement = $db->prepare ( "select * from cipherSuite where id=?" );
      $selectCipherSuiteStatement->execute ( array (
        $cipherSuites[ "ciphersuite" ] ) );
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

      $hostsFolded = array ();
      $hostsUnfolded = array ();
      if ( $recordingID == 0 )
      {
        $selectConnectionStatement = $db->prepare ( "select host,ip from
                              (
                                select verbindung.host,ip from verbindung,ssltls where verbindung.id=ssltls.verbindung and ciphersuite=?
                                union all
                                select verbindung.host,ip from verbindung,https where verbindung.id=https.verbindung and ciphersuite=?
                              ) t
                              group by ip" );

        $selectConnectionStatement->execute ( array (
          $cipherSuites[ "ciphersuite" ],
          $cipherSuites[ "ciphersuite" ] ) );
      }
      else
      {
        $selectConnectionStatement = $db->prepare ( "select host,ip from
                              (
                                select verbindung.host,ip from verbindung,ssltls where verbindung.aufzeichnung=? and verbindung.id=ssltls.verbindung and ciphersuite=?
                                union all
                                select verbindung.host,ip from verbindung,https where verbindung.aufzeichnung=? and verbindung.id=https.verbindung and ciphersuite=?
                              ) t
                              group by ip" );

        $selectConnectionStatement->execute ( array (
          $recordingID,
          $cipherSuites[ "ciphersuite" ],
          $recordingID,
          $cipherSuites[ "ciphersuite" ] ) );
      }
      while ( $connection = $selectConnectionStatement->fetch () )
      {
        if ( !$connection[ "host" ] )
        {
          list ($ip) = ipHostinfo ( $connection[ "ip" ] );
          array_push ( $hostsFolded,
                       whoisify ( $ip ) );
          array_push ( $hostsUnfolded,
                       whoisify ( $ip ) );
        }
        else
        {
          $names = idHostinfo ( $connection[ "host" ] );
          $auth = array_shift ( $names );
          array_push ( $hostsFolded,
                       whoisify ( $auth ) );
          array_push ( $hostsUnfolded,
                       whoisify ( $auth ) . " (" . implode ( " * ",
                                                             array_map ( "whoisify",
                                                                         $names ) ) . ")" );
        }
      }

      $table .= "<tr>";

      /*
       * drill down
       */
      $table .= "<td>" . showViewButton ( "showSslsuite.pho?" .
          $__[ "showSslsuite" ] [ "params" ][ "cipherSuite" ] . "=" . $cipherSuites[ "ciphersuite" ] . "&" . $__[ "showRecording" ][ "params" ][ "recording" ] . "=$recordingID",
                                          _ ( "SSL-Verschlüsselung ansehen" ) ) . "</td>";
      /*
       * cipher
       */
      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . $cipher[ "shortName" ] . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . $cipher[ "longName" ] . "</td>";
      /*
       * cipher bits
       */
      $table .= "<td class=\"numeric\">" . $cipher[ "bits" ] . "</td>";
      /*
       * key exchange
       */
      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . $keyExchange[ "shortName" ] . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . $keyExchange[ "longName" ] . "</td>";

      $table .= "<td>" . ($keyExchange[ "forwardSecrecy" ] ? "<i class=\"fa fa-check\"></i>" : "") . "</td>";
      /*
       * mac
       */
      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . $mac[ "shortName" ] . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . $mac[ "longName" ] . "</td>";
      /*
       * hosts
       */
      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ] . $tableID . "\">" . implode ( ",<br>",
                                                                                                                                                                        $hostsFolded ) . "</span><span class=\"" . $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ] . $tableID . "\">" . implode ( ",<br>",
                                                                                                                                                                                                                                                                                                               $hostsUnfolded ) . "</span></td>";
      /*
       * connections
       */
      $table .= "<td class=\"numeric\">" . $cipherSuites[ "verbindungen" ] . "</td>";
      $table .= "</tr>";
    }
    while ( $cipherSuites = $selectCipherSuitesStatement->fetch () );

    $table .= "</tbody></table></div>";

    return array (
      true,
      $table );
  }
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

if ( !isset ( $_REQUEST[ $__[ "include/filterUtility" ][ "params" ] [ "showRecording" ] ] ) )
{
  $_REQUEST[ $__[ "include/filterUtility" ][ "params" ] [ "showRecording" ] ] = $__[ "include/filterUtility" ][ "values" ] [ "eachRecording" ];
}

titleAndHelp ( _ ( "SSL-Verschlüsselung" ),
                   _ ( "Soweit SSL- oder HTTPS-Verbindungen aufgezeichnet wurden, können hier die verwendeten Verschlüsselungstypen (SSL-Suite) analysiert werden. Im Drill-Down können die einzelnen Verbindungen identifiziert werden, die die entsprechende Suite verwenden." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($encryptionsFound, $encryptionTable) = tabulateEncryptions ( 0,
                                                                       $__[ "evaluateSslsuites" ][ "ids" ][ "tables" ][ "encryptions" ] );


    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$encryptionsFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Verschlüsselungen aller Aufzeichnungen" ), (!$encryptionsFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$encryptionTable</div></div></div>";
  }

  /*
   * show each recording in a seperate table
   */
  else if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ] )
  {
    echo "<div class=\"row\"><div class=\"panel-group\" role=\"tablist\">";

    $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc" );
    $selectRecordingStatement->execute ();
    while ( $recording = $selectRecordingStatement->fetch () )
    {
      list ($encryptionsFound, $encryptionTable) = tabulateEncryptions ( $recording[ "id" ],
                                                                         $__[ "evaluateSslsuites" ][ "ids" ][ "tables" ][ "encryptions" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateSslsuites" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";
      echo (!$encryptionsFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Verschlüsselungen der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$encryptionsFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateSslsuites" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">$encryptionTable</div></div></div>";
    }
    echo "</div></div>";
  }

  /*
   * show single recording
   */
  else
  {
    $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?" );
    $selectRecordingStatement->execute ( array (
      $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) );
    $recording = $selectRecordingStatement->fetch ();

    list ($encryptionsFound, $encryptionTable) = tabulateEncryptions ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                                       $__[ "evaluateSslsuites" ][ "ids" ][ "tables" ][ "encryptions" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$encryptionsFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Verschlüsselungen der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$encryptionsFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$encryptionTable</div></div></div> ";
  }
}

include ("include/closeHTML.php");

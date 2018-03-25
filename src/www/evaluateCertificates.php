<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateCertificates.php
 * 
 * display ssl certificates that have been recorded
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

function tabulateCertificates ( $recordingID,
                                $tableID )
{
  global $db, $__;

  $_ansehen = "Zertifikat ansehen";

  if ( $recordingID == 0 )
  {
    $selectCertificatesStatement = $db->prepare ( "select zertifikat,sum(v) as verbindungen
                              from
                              (
                                select zertifikat,count(*) as v from https group by zertifikat
                                union all
                                select zertifikat,count(*) as v from ssltls group by zertifikat
                              ) t
                              group by zertifikat" );
    $selectCertificatesStatement->execute ();
  }
  else
  {
    $selectCertificatesStatement = $db->prepare ( "select zertifikat,sum(v) as verbindungen
                              from
                              (
                                select zertifikat,count(*) as v from https where aufzeichnung=? group by zertifikat
                                union all
                                select zertifikat,count(*) as v from ssltls where aufzeichnung=? group by zertifikat
                              ) t
                              group by zertifikat" );
    $selectCertificatesStatement->execute ( array (
      $recordingID,
      $recordingID ) );
  }

  if ( ($certificates = $selectCertificatesStatement->fetch ()) == false )
  {
    return array (
      false,
      _ ( "Es wurden keine Zertifikate verwendet." ) );
  }
  else
  {
    list ($table, $foldUnfoldButton) = tableFolder ( $tableID,
                                                     false );
    $table .= tableSorter ( $tableID,
                            "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]" );

    $table .= "<div class=\"table-responsive\"><table id=\"$tableID\" class=\"table table-hover\"><thead><tr>";
    $table .= "<th>$foldUnfoldButton</th>";
    $table .= "<th>" . _ ( "Eigentümer" ) . "</th>";
    $table .= "<th>" . _ ( "Aussteller" ) . "</th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "gültig" ) . " </span>" . _ ( "ab" ) . "</th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "gültig" ) . " </span>" . _ ( "bis" ) . "</th>";
    $table .= "<th>" . _ ( "Tage" ) . "</th>";
    $table .= "<th><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "verwendet von" ) . " </span>" . _ ( "Hosts" ) . "</th>";
    $table .= "<th>" . _ ( "Verb" ) . "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">.</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . _ ( "indungen" ) . "</span></th></tr></thead><tbody>";

    do
    {
      $selectCertificateStatement = $db->prepare ( "select *, date_format(notbefore, '%T') as _notbeforetime, date_format(notbefore, '%e.%c.%Y') as _notbeforedate, date_format(notafter, '%T') as _notaftertime, date_format(notafter, '%e.%c.%Y') as _notafterdate, datediff(notafter,notbefore) as _tage from zertifikat where id=?" );
      $selectCertificateStatement->execute ( array (
        $certificates[ "zertifikat" ] ) );
      $certificate = $selectCertificateStatement->fetch ();

      /*
       * drill down
       */
      $table .= "<tr><td>" . showViewButton ( "showCertificate.php?" . $__[ "showCertificate" ][ "params" ][ "certificateID" ] . "=" . $certificate[ "id" ] . "&" . $__[ "showCertificate" ][ "params" ][ "recordingID" ] . "=$recordingID",
                                              _ ( "Zertifikat ansehen" ) ) . "</td>";

      /*
       * subject
       */
      if ( preg_match ( "/O=([^=,]*)/",
                        $certificate[ "subject" ],
                        $match ) )
      {
        $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $match[ 1 ] . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . $certificate[ "subject" ] . "</span><!--" . $match[ 1 ] . "--></td>";
      }
      else
      {
        $table .= foldableTableCell ( $certificate[ "subject" ],
                                      $tableID );
      }

      /*
       * issuer
       */
      if ( preg_match ( "/O=([^=,]*)/",
                        $certificate[ "issuer" ],
                        $match ) )
      {
        $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $match[ 1 ] . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . $certificate[ "issuer" ] . "</span><!--" . $match[ 1 ] . "--></td>";
      }
      else
      {
        $table .= foldableTableCell ( $certificate[ "issuer" ],
                                      $tableID );
      }

      /*
       * valid from
       */
      $table .= "<td class=\"numeric\"" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $certificate[ "_notbeforedate" ] . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . $certificate[ "_notbeforedate" ] . " <i class=\"fa fa-clock-o\"></i> " . $certificate[ "_notbeforetime" ] . "</span><!--" . $certificate[ "notbefore" ] . "--></td>";

      /*
       * valid to
       */
      $table .= "<td class=\"numeric\"" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $certificate[ "_notafterdate" ] . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . $certificate[ "_notafterdate" ] . " <i class=\"fa fa-clock-o\"></i> " . $certificate[ "_notaftertime" ] . "</span><!--" . $certificate[ "notafter" ] . "--></td>";

      /*
       * days valid
       */
      $table .= "<td class=\"numeric\">" . $certificate[ "_tage" ] . "</td>";

      /*
       * valid for
       */
      $foldedHosts = array ();
      $unfoldedHosts = array ();
      $selectConnectionStatement = $db->prepare ( "select host,ip from
                              (
                                select verbindung.host,ip from verbindung,ssltls where verbindung.id=ssltls.verbindung and zertifikat=?
                                union all
                                select verbindung.host,ip from verbindung,https where verbindung.id=https.verbindung and zertifikat=?
                              ) t
                              group by ip" );
      $selectConnectionStatement->execute ( array (
        $certificate[ "id" ],
        $certificate[ "id" ] ) );
      while ( $connection = $selectConnectionStatement->fetch () )
      {
        if ( !$connection[ "host" ] )
        {
          list ($ip) = ipHostinfo ( $connection[ "ip" ] );
          array_push ( $foldedHosts,
                       whoisify ( $ip ) );
          array_push ( $unfoldedHosts,
                       whoisify ( $ip ) );
        }
        else
        {
          $names = idHostinfo ( $connection[ "host" ] );
          $auth = array_shift ( $names );
          array_push ( $foldedHosts,
                       whoisify ( $auth ) );
          array_push ( $unfoldedHosts,
                       whoisify ( $auth ) . " (" . implode ( " * ",
                                                             array_map ( "whoisify",
                                                                         $names ) ) . ")" );
        }
      }

      $table .= "<td" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . implode ( ",<br>",
                                                                                                                                                                      $foldedHosts ) . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . implode ( ",<br>",
                                                                                                                                                                                                                                                                                                           $unfoldedHosts ) . "</span></td>";

      /*
       * connections
       */
      $table .= "<td class=\"numeric\">" . $certificates[ "verbindungen" ] . "</td></tr>";
    }
    while ( $certificates = $selectCertificatesStatement->fetch () );

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

if ( !isset ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] ) )
{
  $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] = $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ];
}

titleAndHelp ( _ ( "Zertifikate" ),
                   _ ( "Diese Auswertung betrachtet die SSL-Zertifikate, die aufgezeichnet wurden." ) );

if ( recordingsSelector () )
{
  /*
   * show all recordings in one table
   */
  if ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] )
  {
    list ($certificatesFound, $certificatesTable) = tabulateCertificates ( 0,
                                                                           $__[ "evaluateCertificates" ] [ "ids" ] [ "tables" ][ "certificates" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$certificatesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Zertifikate aller Aufzeichnungen" ), (!$certificatesFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$certificatesTable</div></div></div>";
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
      list ($certificatesFound, $certificatesTable) = tabulateCertificates ( $recording[ "id" ],
                                                                             $__[ "evaluateCertificates" ] [ "ids" ] [ "tables" ][ "certificates" ] . $recording[ "id" ] );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#", $__[ "evaluateCertificates" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\"><h4 class=\"panel-title\">";
      echo (!$certificatesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Zertifikate der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$certificatesFound ? "</span>" : "");
      echo "</h4></div><div id=\"", $__[ "evaluateCertificates" ] [ "ids" ] [ "recordingPanelPrefix" ], $recording[ "id" ], "\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">$certificatesTable</div></div></div>";
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

    list ($certificatesFound, $certificatesTable) = tabulateCertificates ( $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ],
                                                                           $__[ "evaluateCertificates" ] [ "ids" ] [ "tables" ][ "certificates" ] );

    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo (!$certificatesFound ? "<span class=\"emptyPanel\">" : ""), _ ( "Zertifikate der Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], (!$certificatesFound ? "</span>" : "");
    echo "</h4></div><div class=\"panel-body\">$certificatesTable</div></div></div>";
  }
}

include ("include/closeHTML.php");

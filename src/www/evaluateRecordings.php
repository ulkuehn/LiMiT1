<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file evaluateRecordings.php
 *
 * display all available recordings
 * additionally, tools to manage those recordings are provided such as editing, exporting and deleting a recording
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
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * show deletion progress message
 * 
 * @param int $step current step
 * @param int $totalSteps number of steps
 * @param string $message text to show
 */
function progressInfo ( $step,
                        $totalSteps,
                        $message )
{
  global $__;

  $percent = floor ( $step / $totalSteps * 100 );
  echo "<script>document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgressBar" ], "').style.width = \"$percent%\"; document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgressText" ], "').innerHTML = \"$step / $totalSteps\"; document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgressMessage" ], "').innerHTML = \"$message\";</script>";
  ob_flush ();
  flush ();
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * reset recording filter cookie
 */
setcookie ( $__[ "include/filterUtility" ][ "names" ][ "cookie" ],
            $__[ "include/filterUtility" ][ "values" ][ "eachRecording" ] );

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");


/*
 * delete a recording
 */
if ( isset ( $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "delete" ] ] ) )
{
  $recordingID = $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "recording" ] ];
  echo "<div class=\"row\">";

  $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?" );
  $selectRecordingStatement->execute ( array (
    $recordingID ) );
  if ( ($recording = $selectRecordingStatement->fetch ()) == false )
  {
    showErrorMessage ( _ ( "Die Aufzeichnung ist nicht in der Datenbank vorhanden." . $__[ "evaluateRecordings" ][ "params" ][ "recording" ] ) );
  }
  else
  {
    /*
     * number of distinct deletion steps (needed for progress bar calculations)
     */
    $deletionSteps = 10;

    echo "<div class=\"panel panel-primary\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgress" ], "\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
    echo _ ( "Aufzeichnung " ), ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> "), _ ( "vom " ), $recording[ "_start" ], _ ( " löschen" );
    echo "</h4></div><div class=\"panel-body\"><div class=\"progress\"><div class=\"progress-bar progress-bar-success progress-bar-striped\" role=\"progressbar\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgressBar" ], "\" style=\"min-width: 5em; width:0%\"><p style=\"color:black;font-weight:bold\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgressText" ], "\">0 / $deletionSteps</p></div></div>";
    showWaitMessage ( "<span id=\"" . $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgressMessage" ] . "\"></span>" );

    /*
     * put all deleting into one transaction to speed up things
     */
    $db->beginTransaction ();

    /*
     * connections
     */
    $deleteConnectionStatement = $db->prepare ( "delete from verbindung where aufzeichnung=?" );
    $deleteConnectionStatement->execute ( array (
      $recordingID ) );
    /*
     * http
     */
    $deleteHTTPStatement = $db->prepare ( "delete from http where aufzeichnung=?" );
    $deleteHTTPStatement->execute ( array (
      $recordingID ) );
    /*
     * https
     */
    $deleteHTTPSStatement = $db->prepare ( "delete from https where aufzeichnung=?" );
    $deleteHTTPSStatement->execute ( array (
      $recordingID ) );
    /*
     * ssl
     */
    $deleteSSLStatement = $db->prepare ( "delete from ssltls where aufzeichnung=?" );
    $deleteSSLStatement->execute ( array (
      $recordingID ) );

    progressInfo ( 1,
                   $deletionSteps,
                   _ ( "Verbindungen wurden gelöscht ..." ) );

    /*
     * requests
     */
    $deleteRequestStatement = $db->prepare ( "delete from request where aufzeichnung=?" );
    $deleteRequestStatement->execute ( array (
      $recordingID ) );
    progressInfo ( 2,
                   $deletionSteps,
                   _ ( "Requests wurden gelöscht ..." ) );


    /*
     * responses
     */
    $deleteResponseStatement = $db->prepare ( "delete from response where aufzeichnung=?" );
    $deleteResponseStatement->execute ( array (
      $recordingID ) );
    progressInfo ( 3,
                   $deletionSteps,
                   _ ( "Responses wurden gelöscht ..." ) );

    /*
     * set cookies
     */
    $deleteSetcookieStatement = $db->prepare ( "delete from setcookie where aufzeichnung=?" );
    $deleteSetcookieStatement->execute ( array (
      $recordingID ) );
    /*
     * sent cookies
     */
    $deleteSendCookieStatement = $db->prepare ( "delete from sendcookie where aufzeichnung=?" );
    $deleteSendCookieStatement->execute ( array (
      $recordingID ) );

    progressInfo ( 4,
                   $deletionSteps,
                   _ ( "Cookies wurden gelöscht ..." ) );

    /*
     * header
     */
    $deleteHeaderStatement = $db->prepare ( "delete from header where aufzeichnung=?" );
    $deleteHeaderStatement->execute ( array (
      $recordingID ) );
    progressInfo ( 5,
                   $deletionSteps,
                   _ ( "Header wurden gelöscht ..." ) );

    /*
     * contents
     */
    $deleteContentStatement = $db->prepare ( "delete from inhalt where aufzeichnung=?" );
    $deleteContentStatement->execute ( array (
      $recordingID ) );
    progressInfo ( 6,
                   $deletionSteps,
                   _ ( "Inhalte wurden gelöscht ..." ) );

    /*
     * meta data
     */
    $deleteMetadataStatement = $db->prepare ( "delete from metadaten where aufzeichnung=?" );
    $deleteMetadataStatement->execute ( array (
      $recordingID ) );
    progressInfo ( 7,
                   $deletionSteps,
                   _ ( "Metadaten wurden gelöscht ..." ) );

    /*
     * hosts
     */
    $hostsToDelete = array ();
    $selectHostStatement = $db->prepare ( "select id from host" );
    $selectHostStatement->execute ();

    while ( $hostID = $selectHostStatement->fetchColumn () )
    {
      $selectConnectionsStatement = $db->prepare ( "select count(*) from verbindung where host=?" );
      $selectConnectionsStatement->execute ( array (
        $hostID ) );
      if ( !$selectConnectionsStatement->fetchColumn () )
      {
        $selectHTTPsStatement = $db->prepare ( "select count(*) from http where host=?" );
        $selectHTTPsStatement->execute ( array (
          $hostID ) );
        if ( !$selectHTTPsStatement->fetchColumn () )
        {
          $selectHTTPSsStatement = $db->prepare ( "select count(*) from https where host=?" );
          $selectHTTPSsStatement->execute ( array (
            $hostID ) );
          if ( !$selectHTTPSsStatement->fetchColumn () )
          {
            /*
             * this is only reached if no connection, no http and no https refers to that host anymore
             */
            array_push ( $hostsToDelete,
                         $hostID );
          }
        }
      }
    }

    foreach ( $hostsToDelete as $hostID )
    {
      $deleteHostStatement = $db->prepare ( "delete from host where id=?" );
      $deleteHostStatement->execute ( array (
        $hostID ) );
    }

    progressInfo ( 8,
                   $deletionSteps,
                   _ ( "Hosts wurden bereinigt ..." ) );

    /*
     * certificates
     */
    $certificatesToDelete = array ();
    $selectCertificateStatement = $db->prepare ( "select id from zertifikat" );
    $selectCertificateStatement->execute ();

    while ( $certifcateID = $selectCertificateStatement->fetchColumn () )
    {
      $selectHTTPSsStatement = $db->prepare ( "select count(*) from https where zertifikat=?" );
      $selectHTTPSsStatement->execute ( array (
        $certifcateID ) );
      if ( !$selectHTTPSsStatement->fetchColumn () )
      {
        $selectSSLsStatement = $db->prepare ( "select count(*) from ssltls where zertifikat=?" );
        $selectSSLsStatement->execute ( array (
          $certifcateID ) );
        if ( !$selectSSLsStatement->fetchColumn () )
        {
          array_push ( $certificatesToDelete,
                       $certifcateID );
        }
      }
    }

    foreach ( $certificatesToDelete as $certifcateID )
    {
      $deleteCertificateStatement = $db->prepare ( "delete from zertifikat where id=?" );
      $deleteCertificateStatement->execute ( array (
        $certifcateID ) );
    }

    progressInfo ( 9,
                   $deletionSteps,
                   _ ( "Zertifikate wurden bereinigt ..." ) );

    /*
     * cookies
     */
    $cookiesToDelete = array ();
    $selectCookieStatement = $db->prepare ( "select id from cookie" );
    $selectCookieStatement->execute ();

    while ( $cookieID = $selectCookieStatement->fetchColumn () )
    {
      $selectSetCookiesStatement = $db->prepare ( "select count(*) from setcookie where cookie=?" );
      $selectSetCookiesStatement->execute ( array (
        $cookieID ) );
      $selectSendCookiesStatement = $db->prepare ( "select count(*) from sendcookie where cookie=?" );
      $selectSendCookiesStatement->execute ( array (
        $cookieID ) );
      if ( !$selectSetCookiesStatement->fetchColumn () && !$selectSendCookiesStatement->fetchColumn () )
      {
        array_push ( $cookiesToDelete,
                     $cookieID );
      }
    }

    foreach ( $cookiesToDelete as $cookieID )
    {
      $deleteCookieStatement = $db->prepare ( "delete from cookie where id=?" );
      $deleteCookieStatement->execute ( array (
        $cookieID ) );
    }
    progressInfo ( 10,
                   $deletionSteps,
                   _ ( "Cookies wurden bereinigt ..." ) );

    /*
     * recording itself
     */
    $deleteRecordingStatement = $db->prepare ( "delete from aufzeichnung where id=?" );
    $deleteRecordingStatement->execute ( array (
      $recordingID ) );

    /*
     * commit all deletions
     */
    $db->commit ();

    echo "</div></div><script>document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteProgress" ], "').style.display = \"none\";</script>";
    ob_flush ();
    flush ();
    showSuccessMessage ( _ ( "Die Aufzeichnung " ) . ($recording[ "name" ] == "" ? "" : "<strong>" . htmlSave ( $recording[ "name" ] ) . "</strong> ") . _ ( "vom " ) . $recording[ "_start" ] . _ ( " wurde gelöscht." ) );
  }
  echo "</div>";
}


/*
 * edit a recording's parameter
 */
if ( isset ( $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "edit" ] ] ) )
{
  $recordingNameExists = 0;
  if ( $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "name" ] ] != "" )
  {
    $selectRecordingStatement = $db->prepare ( "select count(*) from aufzeichnung where name=? and id!=?" );
    $selectRecordingStatement->execute ( array (
      $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "name" ] ],
      $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "recording" ] ] ) );
    $recordingNameExists = $select_s->fetchColumn ();
  }
  if ( $recordingNameExists )
  {
    echo "<div class=\"row\">";
    showErrorMessage ( _ ( "Eine Aufzeichnung mit dem Namen" ) . " \"" . $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "name" ] ] . "\" " . _ ( "existiert bereits." ) );
    echo "</div>";
  }
  else
  {
    $updateRecordingStatement = $db->prepare ( "update aufzeichnung set geraet=?, name=?, info=? where id=?" );
    $updateRecordingStatement->execute ( array (
      $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "device" ] ],
      $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "name" ] ],
      $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "info" ] ],
      $_POST[ $__[ "evaluateRecordings" ][ "params" ][ "recording" ] ] ) );
  }
}


titleAndHelp ( _ ( "Aufzeichnungen" ),
                   _ ( "<p>Diese Funktion bietet einen Überblick über die vorhandenen Aufzeichnungen. Dabei sind folgende Operationen möglich:</p>" ) . "<div class=\"row\"><div class=\"col-md-1\"><button class=\"btn btn-info btn-xs center-block\"><i class=\"fa fa-eye\"></i></button></div><div class=\"col-md-11\">" . _ ( "<p>Jede Aufzeichnung besteht aus mehreren Verbindungen, die wiederum aus mehreren Requests bestehen können. Über diesen Button sind sämtliche Details der Aufzeichnung zugänglich.</p>" ) . "</div><div class=\"col-md-1\"><button class=\"btn btn-success btn-xs center-block\"><i class=\"fa fa-pencil\"></i></button></div><div class=\"col-md-11\">" .
  _ ( "<p>Eigenschaften vorhandener Aufzeichnungen können nachträglich geändert bzw. ergänzt werden, wenn dies beim Start der Aufzeichnung übersprungen wurde.</p>" ) . "</div><div class=\"col-md-1\"><button class=\"btn btn-primary btn-xs center-block\"><i class=\"fa fa-upload\"></i></button></div><div class=\"col-md-11\">" .
  _ ( "<p>Hiermit kann eine Aufzeichnung aus dem $my_name-System exportiert und auf einem anderen Computer gespeichert werden. Mit der entsprechenden Funktion im Menü \"Werkzeuge\" kann sie später wieder (auch auf einem anderen $my_name-Gerät) importiert werden.</p>" ) .
  "</div><div class=\"col-md-1\"><button class=\"btn btn-danger btn-xs center-block\"><i class=\"fa fa-trash\"></i></button></div><div class=\"col-md-11\">" .
  _ ( "<p>Mit dieser Funktion können nicht länger benötigte Aufzeichnungen dauerhaft vom System gelöscht werden.</p>" )
  . "</div></div>" );


echo "<form class=\"form-horizontal\" method=\"post\"><input type=\"hidden\" id=\"", $__[ "evaluateRecordings" ][ "ids" ][ "hiddenInput" ], "\" name=\"", $__[ "evaluateRecordings" ][ "params" ][ "recording" ], "\" value=\"\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Vorhandene Aufzeichnungen" ), "</h4></div><div class=\"panel-body\">";

$selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt, timediff(ende,start) as _dauer, unix_timestamp(ende)-unix_timestamp(start) as _diff from aufzeichnung order by start desc" );
$selectRecordingStatement->execute ();

if ( ($recording = $selectRecordingStatement->fetch ()) == false )
{
  showInfoMessage ( _ ( "Es sind keine Aufzeichnungen vorhanden." ) );
}
else
{
  echo tableSorter ( $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ],
                     "columns: [ {orderable:false, searchable:false}, {}, {}, {type:'num'}, {}, {}, {} ], order: [ [2,'desc'] ]" );
  $foldUnfoldButton = tableFolder ( $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldUnfoldButton</th>";
  echo "<th>", _ ( "Bezeichnung" ), "</th>";
  echo "<th>", _ ( "Beginn" ), "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ], "\">", _ ( " der Aufzeichnung" ), "</span></th>";
  echo "<th>", _ ( "Dauer" ), "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ], "\">", _ ( " der Aufzeichnung" ), "</span></th>";
  echo "<th>", _ ( "Gerät" ), "</th>";
  echo "<th>", _ ( "Infos" ), "</th>";
  echo "<th><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "foldedPrefix" ], $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ], "\">", _ ( "Verb." ), "</span><span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ], "\">", _ ( "Verbindungen" ), "</span></th>";
  echo "<th></th></tr></thead><tbody>";

  do
  {
    echo "<tr>";

    /*
     * controls
     */
    echo "<td><span style=\"white-space:nowrap\">";
    echo showViewButton ( "showRecording.php?" . $__[ "showRecording" ] [ "params" ][ "recording" ] . "=" . $recording[ "id" ],
                          _ ( "Aufzeichnung ansehen" ) );

    echo "<span class=\"", $__[ "include/tableUtility" ] [ "ids" ] [ "unfoldedPrefix" ], $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ], "\">";

    /*
     * edit
     */
    echo " <a href=\"#", $__[ "evaluateRecordings" ] [ "ids" ] [ "editModal" ], "\" title=\"", $__[ "evaluateRecordings" ][ "values" ][ "edit" ], "\" class=\"btn btn-success btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "editRecordingInfo" ], "').innerHTML='", ($recording[ "name" ] != "" ? "<strong>" . jsSave ( htmlSave ( $recording[ "name" ] ) ) . "</strong> " : ""), "vom ", $recording[ "_startd" ], ", ", $recording[ "_startt" ], "'; document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "editDevice" ], $recording[ "geraet" ] + 0, "').selected='true'; document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "editName" ], "').value='", jsSave ( $recording[ "name" ] ), "'; document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "editInfo" ], "').value='", jsSave ( $recording[ "info" ] ), "'; document.getElementById('", $__[ "evaluateRecordings" ][ "ids" ][ "hiddenInput" ], "').value='", $recording[ "id" ], "';\"><i class=\"fa fa-pencil\"></i></a>";

    /*
     * export
     */
    echo " <a href=\"#", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModal" ], "\" title=\"", $__[ "evaluateRecordings" ][ "values" ][ "export" ], "\" class=\"btn btn-primary btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportRecordingInfo" ], "').innerHTML='", ($recording[ "name" ] != "" ? "<strong>" . jsSave ( htmlSave ( $recording[ "name" ] ) ) . "</strong> " : ""), "vom ", $recording[ "_startd" ], ", ", $recording[ "_startt" ], "'; exportRecording(", $recording[ "id" ], ");\"><i class=\"fa fa-upload\"></i></a>";

    /*
     * delete
     */
    echo " <a href=\"#", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteModal" ], "\" title=\"", $__[ "evaluateRecordings" ][ "values" ][ "delete" ], "\" class=\"btn btn-danger btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteRecordingInfo" ], "').innerHTML='", ($recording[ "name" ] != "" ? "<strong>" . jsSave ( htmlSave ( $recording[ "name" ] ) ) . "</strong> " : ""), "vom ", $recording[ "_startd" ], ", ", $recording[ "_startt" ], "'; document.getElementById('", $__[ "evaluateRecordings" ][ "ids" ][ "hiddenInput" ], "').value='", $recording[ "id" ], "';\"><i class=\"fa fa-trash\"></i></a>";

    echo "</span></span></td>";

    /*
     * name
     */
    echo foldableTableCell ( $recording[ "name" ],
                             $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ] );

    /*
     * start
     */
    echo "<td>", $recording[ "_startd" ], " <i class=\"fa fa-clock-o\"></i> ", $recording[ "_startt" ], "<!--", $recording[ "start" ], "--></td>";

    /*
     * duration
     */
    echo "<td>", humanReadableDuration ( $recording[ "_diff" ] ), "<!--", $recording[ "_diff" ], "--></td>";

    /*
     * device
     */
    $selectDeviceStatement = $db->prepare ( "select * from geraet where id=?" );
    $selectDeviceStatement->execute ( array (
      $recording[ "geraet" ] ) );
    echo foldableTableCell ( $selectDeviceStatement->fetch ()[ "name" ],
                             $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ] );

    /*
     * infos
     */
    echo foldableTableCell ( $recording[ "info" ],
                             $__[ "evaluateRecordings" ][ "ids" ][ "tables" ][ "recordings" ] );

    /*
     * connections count
     */
    $selectConnectionsStatement = $db->prepare ( "select count(*) from verbindung where aufzeichnung=?" );
    $selectConnectionsStatement->execute ( array (
      $recording[ "id" ] ) );
    echo "<td class=\"numeric\">", $selectConnectionsStatement->fetchColumn (), "</td>";

    echo "</tr>";
  }
  while ( $recording = $selectRecordingStatement->fetch () );

  echo "</tbody></table></div></div>";


  /*
   * delete modal
   */
  echo "<div class=\"modal fade\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\">";
  echo "<div class=\"modal-header\"><div class=\"alert alert-danger\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-trash fa-2x\"></i></div><div class=\"msgText\"><strong>", $__[ "evaluateRecordings" ][ "values" ][ "delete" ], "</strong></div></div></div>";
  echo "<div class=\"modal-body\"><p>", _ ( "Soll die Aufzeichnung" ), " <span id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "deleteRecordingInfo" ], "\"></span> ", _ ( "gelöscht werden?" ), "</p></div>";
  echo "<div class=\"modal-footer\"><input class=\"btn btn-danger\" type=\"submit\" value=\"", $__[ "evaluateRecordings" ][ "values" ][ "delete" ], "\" name=\"", $__[ "evaluateRecordings" ][ "params" ][ "delete" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div>";
  echo "</div></div></div>";


  /*
   * export modal
   */
  echo "<div class=\"modal fade\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\">";
  echo "<div class=\"modal-header\"><div class=\"alert alert-info\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-upload fa-2x\"></i></div><div class=\"msgText\"><strong>", _ ( "Aufzeichnung" ), " <span id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportRecordingInfo" ], "\"></span> ", _ ( "exportieren" ), "</strong></div></div></div>";
  echo "<div class=\"modal-body\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModalBody" ], "\">";
  showWaitMessage ( _ ( "Das Datei-Archiv wird erstellt" ) );
  echo "</div>";
  echo "<div class=\"modal-footer\"><a href=\"#\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModalExport" ], "\" class=\"btn btn-primary disabled\" onclick=\"setTimeout(function(){ $('#", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModal" ], "').modal('hide');},500)\">", $__[ "evaluateRecordings" ][ "values" ][ "export" ], "</a><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div>";

  echo "<script>function exportRecording (id) { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { document.getElementById(\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModalBody" ], "\").innerHTML = xmlhttp.responseText; document.getElementById(\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModalExport" ], "\").href = \"include/downloadArchive.php?", $__[ "include/downloadArchive" ][ "params" ][ "recording" ], "=\"+id; document.getElementById(\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "exportModalExport" ], "\").classList.remove(\"disabled\"); } }; xmlhttp.open(\"GET\",\"include/archiveRecording.php?", $__[ "include/archiveRecording" ][ "params" ][ "recording" ], "=\"+id,true); xmlhttp.send(); } </script>";


  /*
   * edit modal
   */
  echo "<div class=\"modal fade\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\">";
  echo "<div class=\"modal-header\"><div class=\"alert alert-success\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-pencil fa-2x\"></i></div><div class=\"msgText\">", _ ( "Aufzeichnung" ), " <span id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editRecordingInfo" ], "\"></span></div></div></div>";
  echo "<div class=\"modal-body\"><div class=\"form-group\"><label for=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editName" ], "\" class=\"col-md-3 control-label\">", _ ( "Bezeichnung" ), "</label><div class=\"col-md-9\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "evaluateRecordings" ][ "params" ][ "name" ], "\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editName" ], "\"></div></div><div class=\"form-group\"><label for=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editDevice" ], "\" class=\"col-md-3 control-label\">", _ ( "Verwendetes Gerät" ), "</label><div class=\"col-md-9\">";

  $selectDeviceStatement = $db->prepare ( "select * from geraet order by name" );
  $selectDeviceStatement->execute ();
  if ( $device = $selectDeviceStatement->fetch () )
  {
    echo "<select class=\"form-control\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editDevice" ], "\" name=\"", $__[ "evaluateRecordings" ][ "params" ][ "device" ], "\">";
    echo "<option id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editDevice" ], "0\" value=\"0\"></option>";
    do
    {
      echo "<option id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editDevice" ], $device[ "id" ], "\" value=\"", $device[ "id" ], "\">", htmlSave ( $device[ "name" ] ), "</option>";
    }
    while ( $device = $selectDeviceStatement->fetch () );
    echo "</select>";
  }

  echo "</div></div><div class=\"form-group\"><label for=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editInfo" ], "\" class=\"col-md-3 control-label\">", _ ( "Erläuterungen" ), "</label><div class=\"col-md-9\"><textarea class=\"form-control\" id=\"", $__[ "evaluateRecordings" ] [ "ids" ] [ "editInfo" ], "\" name=\"", $__[ "evaluateRecordings" ][ "params" ][ "info" ], "\" rows=\"3\"></textarea></div></div></div>";

  echo "<div class=\"modal-footer\"><input class=\"btn btn-success\" type=\"submit\" value=\"", $__[ "evaluateRecordings" ][ "values" ][ "edit" ], "\" name=\"", $__[ "evaluateRecordings" ][ "params" ][ "edit" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div>";
}

echo "</div></div></div></form>";

include ("include/closeHTML.php");

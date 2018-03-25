<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file importRecording.php
 * 
 * import a recording that was exported beforehand
 * the export might origin from the same or from some other system, thus enabling the transfer of recordings from one system to another
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
require_once ("include/processUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");


titleAndHelp ( _ ( "Aufzeichnung importieren" ),
                   _ ( "Eine zuvor auf diesem oder einem anderen $my_name-Gerät exportierte Aufzeichnung kann hier in die Datenbank importiert werden." ) );

/*
 * process uploaded file
 */
if ( isset ( $_POST[ $__[ "importRecording" ][ "params" ][ "upload" ] ] ) )
{
  echo "<div class=\"row\">";

  /*
   * recording going on
   */
  if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
  {
    showErrorMessage ( _ ( "Während einer Aufzeichnung ist der Import einer anderen Aufzeichnung nicht möglich." ) );
  }

  /*
   * file not found
   */
  else if ( move_uploaded_file ( $_FILES[ $__[ "importRecording" ] [ "files" ] [ "uploadName" ] ][ "tmp_name" ],
                                 $data_dir . "/" . $__[ "include/archiveRecording" ] [ "values" ][ "archiveName" ] ) == false )
  {
    showErrorMessage ( _ ( "Die Datei \"" ) . $_FILES[ $__[ "importRecording" ] [ "files" ] [ "uploadName" ] ][ "name" ] . _ ( "\" konnte nicht hochgeladen werden" ) );
  }
  else
  {
    $zip = new ZipArchive;
    /*
     * not a zip file
     */
    if ( $zip->open ( $data_dir . "/" . $__[ "include/archiveRecording" ] [ "values" ][ "archiveName" ] ) !== true )
    {
      showErrorMessage ( _ ( "Die Datei \"" ) . $_FILES[ $__[ "importRecording" ] [ "files" ] [ "uploadName" ] ][ "name" ] . _ ( "\" ist kein Archiv oder konnte nicht geöffnet werden" ) );
    }
    else
    {
      /*
       * not an archive file
       */
      if ( $zip->locateName ( $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/" ) === false ||
        $zip->locateName ( $__[ "startStopRecording" ] [ "values" ] [ "certificateDir" ] . "/" ) === false ||
        $zip->locateName ( $__[ "importRecording" ] [ "files" ][ "metaFile" ] ) === false )
      {
        showErrorMessage ( _ ( "Es handelt sich nicht um ein $my_name-Archiv" ) );
      }
      else
      {
        /*
         * no meta data found
         */
        if ( ($xml = simplexml_load_string ( $zip->getFromName ( $__[ "importRecording" ] [ "files" ][ "metaFile" ] ) )) === false )
        {
          showErrorMessage ( "Die Metadaten konnten nicht eingelesen werden" );
        }
        /*
         * successful upload, correct file type
         */
        else
        {
          /*
           * find out if device the archived recording is connected with is already in database
           */
          $isNewDevice = true;
          $device = $xml->geraet[ 0 ];
          $selectDeviceIDStatement = $db->prepare ( "select id from geraet where name=?" );
          $selectDeviceIDStatement->execute ( array (
            $device->name->__toString () ) );
          if ( ($deviceID = $selectDeviceIDStatement->fetchColumn ()) != false )
          {
            $selectPropertyStatement = $db->prepare ( "select name,wert from property where geraet=?" );
            $selectPropertyStatement->execute ( array (
              $deviceID ) );
            while ( $property = $selectPropertyStatement->fetch () )
            {
              $propertySet1[ $property[ "name" ] ] = $property[ "wert" ];
            }
            foreach ( $xml->eigenschaft as $property )
            {
              $propertySet2[ $property->name->__toString () ] = $property->wert->__toString ();
            }
            $isNewDevice = ($propertySet1 != $propertySet2);
          }
          /*
           * device not in database: insert device
           */
          if ( $isNewDevice )
          {
            $insertDeviceStatement = $db->prepare ( "insert into geraet set name=?, stand=?" );
            $insertDeviceStatement->execute ( array (
              $device->name->__toString (),
              $device->stand->__toString () ) );
            $deviceID = $db->lastInsertId ();
            foreach ( $xml->eigenschaft as $property )
            {
              $insertPropertyStatement = $db->prepare ( "insert into eigenschaft set geraet=?, name=?, wert=?" );
              $insertPropertyStatement->execute ( array (
                $deviceID,
                $property->name->__toString (),
                $property->wert->__toString () ) );
            }
          }

          /*
           * insert recording into db
           */
          $recording = $xml->aufzeichnung[ 0 ];
          $insertRecordingStatement = $db->prepare ( "insert into aufzeichnung set start=?, ende=?, name=?, info=?, geraet=?, ip=?" );
          $insertRecordingStatement->execute ( array (
            $recording->start->__toString (),
            $recording->ende->__toString (),
            $recording->name->__toString (),
            $recording->info->__toString (),
            $deviceID,
            $recording->ip->__toString () ) );
          $recordingID = $db->lastInsertId ();

          if ( !$recordingID )
          {
            showErrorMessage ( _ ( "Die Aufzeichnung konnte nicht angelegt werden" ) );
          }
          else
          {
            $workDir = "$data_dir/$recordingID";
            $tempDir = "$temp_dir/$recordingID";

            if ( is_dir ( $workDir ) )
            {
              exec ( "/bin/rm -rf $workDir" );
            }
            mkdir ( $workDir );
            $zip->extractTo ( $workDir );

            if ( is_dir ( $tempDir ) )
            {
              exec ( "/bin/rm -rf $tempDir" );
            }
            mkdir ( $tempDir );

            /*
             * start regular insertion process of all recorded data
             */
            $cmd = $__[ "startStopRecording" ] [ "values" ] [ "php5Binary" ] . " " . $_SERVER[ "DOCUMENT_ROOT" ] . "/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterScript" ] . " $recordingID " . long2ip ( $recording->ip->__toString () );
            exec ( sprintf ( "%s >%s 2>&1 & echo $! > %s",
                             $cmd,
                             "$tempDir/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterOutput" ],
                             "$tempDir/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ] ) );
            if ( !processFileRunning ( "$tempDir/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ] ) )
            {
              showErrorMessage ( $__[ "startStopRecording" ] [ "values" ] [ "dbinserterScript" ] . _ ( " konnte nicht gestartet werden" ) );
            }
            else
            {
              /*
               * create session file: 1st line = session name, 2nd line = ip address of monitored device, 3rd line = 0 (recording flag)
               */
              $sessionFileFH = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                                       "w" );
              fwrite ( $sessionFileFH,
                       $recordingID . "\n" );
              fwrite ( $sessionFileFH,
                       long2ip ( $recording->ip->__toString () ) . "\n" );
              fwrite ( $sessionFileFH,
                       "0\n" );
              fclose ( $sessionFileFH );

              showSuccessMessage ( _ ( "Das Archiv wurde extrahiert" ) );

              /*
               * script to monitor insertion progress
               */
              echo "</div><script> function importRecordingProgress() { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { if (xmlhttp.responseText != \"\") { document.getElementById(\"", $__[ "importRecording" ] [ "ids" ][ "progressDiv" ], "\").innerHTML = xmlhttp.responseText + \"", jsSave ( "<a href=\"#" . $__[ "importRecording" ] [ "ids" ] [ "abortModal" ] . "\" class=\"btn btn-warning\" data-toggle=\"modal\">" . $__[ "importRecording" ] [ "values" ][ "abort" ] . "</a>",
                                                                                                                                                                                                                                                                                                                                                                                      false ), "\"; } else { document.getElementById(\"", $__[ "importRecording" ] [ "ids" ][ "progressDiv" ], "\").innerHTML = \"", jsSave ( showSuccessMessage ( _ ( "Die" ) . " <a href=\"showRecording.php?" . $__[ "showRecording" ] [ "params" ] [ "recording" ] . "=$recordingID\">" . _ ( "Aufzeichnung" ) . "</a> " . _ ( "wurde in die Datenbank übernommen." ),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   false ),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   false ), "\"; } } };";
              echo "xmlhttp.open(\"GET\",\"include/showDatabaseProgress.php?", $__[ "include/showDatabaseProgress" ][ "params" ][ "id" ], "=$recordingID&", $__[ "include/showDatabaseProgress" ][ "params" ][ "start" ], "=", microNow (), "\",true); xmlhttp.send(); } importRecordingProgress(); var myVar = setInterval (function () { importRecordingProgress() }, 1000); </script>";

              /*
               * abort modal
               */
              echo "<form method=\"post\"><div class=\"modal fade\" id=\"", $__[ "importRecording" ] [ "ids" ] [ "abortModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\">";
              echo "<div class=\"modal-header\"><div class=\"alert alert-warning\" role=\"alert\"><div class=\"msgIcon\"><span class=\"fa-stack fa-lg\"><i class=\"fa fa-download fa-stack-1x\"></i><i class=\"fa fa-ban fa-stack-2x\"></i></span></div><div class=\"msgText\"><strong>", $__[ "importRecording" ] [ "values" ][ "abort" ], "</strong></div></div></div>";
              echo "<div class=\"modal-body\"><p>", _ ( "Soll der Import der Aufzeichnung tatächlich beendet werden?" ), "</p></div><div class=\"modal-footer\"><input class=\"btn btn-warning\" type=\"submit\" value=\"", $__[ "importRecording" ] [ "values" ][ "abort" ], "\" name=\"", $__[ "importRecording" ] [ "params" ][ "abort" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div></form>";

              /*
               * progress div
               */
              echo "<div class=\"row\" id=\"", $__[ "importRecording" ] [ "ids" ][ "progressDiv" ], "\"></div>";
            }
          }
        }
      }
      $zip->close ();
    }
  }
  echo "</div>";
}

/*
 * abort import
 */
else if ( isset ( $_POST[ $__[ "importRecording" ] [ "params" ][ "abort" ] ] ) )
{
  $sessionFileFH = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                           "r" );
  $recordingID = trim ( fgets ( $sessionFileFH ) );
  fclose ( $sessionFileFH );

  if ( is_readable ( "$temp_dir/$recordingID/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ] ) )
  {
    $pidFileFH = fopen ( "$temp_dir/$recordingID/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ],
                         "r" );
    $pid = trim ( fgets ( $pidFileFH ) );
    fclose ( $pidFileFH );

    system ( "/bin/ps $pid >/dev/null",
             $rv );
    if ( $rv == 0 )
    {
      posix_kill ( $pid,
                   SIGINT );
      while ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
      {
        sleep ( 1 );
      }
      system ( "/bin/ps $pid >/dev/null",
               $rv );
      if ( $rv == 0 )
      {
        posix_kill ( $pid,
                     SIGKILL );
      }
    }
  }

  echo "<div class=\"row\">";
  showSuccessMessage ( _ ( "Der Import der" ) . " <a href=\"showRecording.php?" . $__[ "showRecording" ] [ "params" ] [ "recording" ] . "=$recordingID\">" . _ ( "Aufzeichnung" ) . "</a> " . _ ( "wurde vorzeitig beendet." ) );
  echo "</div>";
}

/*
 * show form
 */
else
{
  if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
  {
    echo "<div class=\"row\">";
    showErrorMessage ( _ ( "Während einer Aufzeichnung ist der Import einer anderen Aufzeichnung nicht möglich." ) );
    echo "</div>";
  }
  else
  {
    if ( !is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
    {
      echo "<div class=\"row\">";
      showAlertMessage ( _ ( "$my_name ist offline. Für einen fehlerfreien Import sollte $my_name mit dem Internet verbunden sein." ) );
      echo "</div>";
    }

    echo "<form class=\"form-horizontal\" method=\"post\" enctype=\"multipart/form-data\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Archivdatei" ), "</h4></div><div class=\"panel-body\"><div class=\"form-group\">";
    echo "<div class=\"col-md-2\"><div class=\"fileUpload btn btn-primary\"><span>", _ ( "Datei auswählen" ), "</span><input type=\"file\" name=\"", $__[ "importRecording" ][ "files" ][ "uploadName" ], "\" class=\"upload\" onchange=\"document.getElementById('", $__[ "importRecording" ][ "files" ][ "uploadName" ], "').value = this.value.replace('C:\\\\fakepath\\\\', ''); \"></div></div>";
    echo "<div class=\"col-md-9 col-md-offset-1\"><input id=\"", $__[ "importRecording" ][ "files" ][ "uploadName" ], "\" class=\"form-control\" placeholder=\"", _ ( "keine Datei ausgewählt" ), "\" disabled=\"disabled\"></div></div></div></div></div>";
    echo "<div class=\"row\"><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "importRecording" ][ "values" ][ "upload" ], "\" name=\"", $__[ "importRecording" ][ "params" ][ "upload" ], "\"></div></form>";
  }
}

include ("include/closeHTML.php");

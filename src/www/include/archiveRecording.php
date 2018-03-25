<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/archiveRecording.php
 * 
 * used to asynchronously archive data gathered in a recording
 * the script generates a zip file containing
 *  - a meta.xml file describing the redording
 *  - all data contained in the database table 'datei' (being a collection of the paths and contents of all raw files collected in a recording)
 * thus, the archive consists of the raw data produced by sslsplit, tcpdump etc, meaning that a reimport must reprocess the data while this reprocessing is time consuming, it guarantees that the processed data is compatible with the database structure at import time
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
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

$selectRecordingStatement = $db->prepare ( "select * from aufzeichnung where id=?" );
$selectRecordingStatement->execute ( array (
  $_GET[ $__[ "include/archiveRecording" ][ "params" ][ "recording" ] ] ) );
if ( ($recording = $selectRecordingStatement->fetch ( PDO::FETCH_ASSOC )) == false )
{
  showErrorMessage ( _ ( "Diese Aufzeichnung ist nicht vorhanden" ) );
}
else
{
  $zip = new ZipArchive;
  if ( $zip->open ( $data_dir . "/" . $__[ "include/archiveRecording" ] [ "values" ][ "archiveName" ],
                    file_exists ( $data_dir . "/" . $__[ "include/archiveRecording" ] [ "values" ][ "archiveName" ] ) ? ZipArchive::OVERWRITE : ZipArchive::CREATE  ) == false )
  {
    showErrorMessage ( _ ( "Die Archivdatei konnte nicht geöffnet werden" ) );
  }
  else
  {
    $xml = new SimpleXMLElement ( "<meta/>" );

    /*
     * properties of recording, including device infos
     */
    $xmlA = $xml->addChild ( "aufzeichnung" );
    foreach ( $recording as $k => $v )
    {
      $xmlA->addChild ( htmlspecialchars ( $k ),
                                           htmlspecialchars ( $v ) );
    }

    $selectDeviceStatement = $db->prepare ( "select * from geraet where id=?" );
    $selectDeviceStatement->execute ( array (
      $recording[ "geraet" ] ) );
    if ( ($device = $selectDeviceStatement->fetch ( PDO::FETCH_ASSOC )) != false )
    {
      $xmlG = $xml->addChild ( "geraet" );
      foreach ( $device as $k => $v )
      {
        $xmlG->addChild ( htmlspecialchars ( $k ),
                                             htmlspecialchars ( $v ) );
      }

      $selectDevicePropertyStatement = $db->prepare ( "select * from eigenschaft where geraet=?" );
      $selectDevicePropertyStatement->execute ( array (
        $recording[ "geraet" ] ) );
      while ( $deviceProperty = $selectDevicePropertyStatement->fetch ( PDO::FETCH_ASSOC ) )
      {
        $xmlE = $xml->addChild ( "eigenschaft" );
        foreach ( $deviceProperty as $k => $v )
        {
          $xmlE->addChild ( htmlspecialchars ( $k ),
                                               htmlspecialchars ( $v ) );
        }
      }
    }
    $zip->addFromString ( $__[ "importRecording" ] [ "files" ][ "metaFile" ],
                          $xml->asXML () );

    /*
     * raw file contents
     */
    $zip->addEmptyDir ( $__[ "startStopRecording" ] [ "values" ] [ "certificateDir" ] );
    $zip->addEmptyDir ( $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] );
    $selectFileStatement = $db->prepare ( "select * from datei where aufzeichnung=?" );
    $selectFileStatement->execute ( array (
      $_GET[ $__[ "include/archiveRecording" ][ "params" ][ "recording" ] ] ) );
    while ( $file = $selectFileStatement->fetch () )
    {
      if ( substr ( $file[ "name" ],
                    0,
                    1 ) == "/" )
      {
        $file[ "name" ] = substr ( $file[ "name" ],
                                   1 );
      }
      $zip->addFromString ( $file[ "name" ],
                            $file[ "inhalt" ] );
    }
    if ( $zip->close () == false )
    {
      showErrorMessage ( _ ( "Das Archiv konnte nicht erstellt werden" ) );
    }
    else
    {
      echo "<p>", _ ( "Das Archiv wurde erstellt und kann jetzt exportiert werden." ), "</p>";
    }
  }
}

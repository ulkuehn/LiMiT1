<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/downloadArchive.php
 * 
 * used to download archived recording
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

$selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%Y%m%d%H%i') as _start from aufzeichnung where id=?" );
$selectRecordingStatement->execute ( array (
  $_GET[ $__[ "include/downloadArchive" ][ "params" ][ "recording" ] ] ) );
if ( ($recording = $selectRecordingStatement->fetch ()) != false && $fd = fopen ( $data_dir . "/" . $__[ "include/archiveRecording" ] [ "values" ][ "archiveName" ],
                                                                                  "r" ) )
{
  $fileSize = filesize ( $data_dir . "/" . $__[ "include/archiveRecording" ] [ "values" ][ "archiveName" ] );
  header ( "Content-Type: application/zip" );
  header ( "Content-Disposition: attachment; filename=\"" . $my_name . "_" . $recording[ "_start" ] . ".zip\"" );
  header ( "Content-length: $fileSize" );
  while ( !feof ( $fd ) )
  {
    $buffer = fread ( $fd,
                      2048 );
    echo $buffer;
  }
  fclose ( $fd );
}
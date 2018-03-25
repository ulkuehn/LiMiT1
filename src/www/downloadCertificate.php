<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file downloadCertificate.php
 * 
 * used for downloading the certificate file to a target platform
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

if ( file_exists ( $base_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] ) )
{
  header ( "Content-Disposition: attachment; filename=\"" . pathinfo ( $base_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] )[ "basename" ] . "\"" );
  header ( "Content-Type: application/octet-stream" );

  $file = fopen ( $base_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ],
                  "rb" );

  while ( !feof ( $file ) )
  {
    print (fread ( $file,
                   1024 ) );
    ob_flush ();
    flush ();
  }

  fclose ( $file );
}

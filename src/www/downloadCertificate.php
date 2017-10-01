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
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require_once ("include/constants.php");
require_once ("include/configuration.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

if ( file_exists ( $cert_file ) )
{
  header ( "Content-Disposition: attachment; filename=\"" . pathinfo ( $cert_file )[ "basename" ] . "\"" );
  header ( "Content-Type: application/octet-stream" );

  $file = fopen ( $cert_file,
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

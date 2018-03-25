<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/configuration.php
 * 
 * used to make available all configuration settings to php scripts (configurations being values that are user editable)
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

$baseDir = pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ];

/*
 * read configuration file line per line and do an eval which sets respective php variables
 */
if ( is_readable ( $baseDir . "/" . $__[ "include/configuration" ][ "files" ][ "configuration" ] ) )
{
  $configurationFile = fopen ( $baseDir . "/" . $__[ "include/configuration" ][ "files" ][ "configuration" ],
                               "r" );
  while ( ($line = fgets ( $configurationFile )) !== false )
  {
    $line = trim ( $line );
    if ( $line != "" && substr ( $line,
                                 0,
                                 1 ) != "#" )
    {
      eval ( "\$$line;" );
    }
  }
  fclose ( $configurationFile );
}
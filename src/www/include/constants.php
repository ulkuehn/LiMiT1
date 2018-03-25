<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/constants.php
 * 
 * used to make available all constants settings to php scripts (constants being values that are _not_ user editable)
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
 * read constants file line per line and do an eval which sets respective php variables
 */
if ( is_readable ( $baseDir . "/" . $__[ "include/constants" ][ "files" ][ "constants" ] ) )
{
  $constantsFile = fopen ( $baseDir . "/" . $__[ "include/constants" ][ "files" ][ "constants" ],
                           "r" );
  while ( ($line = fgets ( $constantsFile )) !== false )
  {
    $line = trim ( $line );
    if ( $line != "" && substr ( $line,
                                 0,
                                 1 ) != "#" )
    {
      eval ( "\$$line;" );
    }
  }
  fclose ( $constantsFile );
}

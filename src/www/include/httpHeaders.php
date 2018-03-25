<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/httpHeaders.php
 * 
 * used to provide http headers for all php scripts
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

/*
 * do not limit execution time of scripts to avoid cancellation of longer running ones
 */
set_time_limit ( 0 );

/*
 * on debug prepare for execution time measurement
 */
if ( $__debug )
{
  $$__[ "include/httpHeaders" ][ "vars" ][ "starttime" ] = microNow ();
}

/*
 * HTTP basics
 */
header ( "Content-Type: text/html; charset=utf-8" );
header ( "Connection: keep-alive" );
header ( "Expires: 0" );

<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/processUtility.php
 * 
 * common definitions needed for all scripts controlling system processes
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
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * check if process whose PID is stored in file is still running
 * 
 * @param string $pidFile path to file where PID to check is stored
 * @return boolean true if process is running
 */
function processFileRunning ( $pidFile )
{
  if ( !is_readable ( $pidFile ) )
  {
    return false;
  }
  $pf = fopen ( $pidFile,
                "r" );
  $pid = trim ( fgets ( $pf ) );
  fclose ( $pf );

  return processRunning ( $pid );
}


/**
 * check if process is still running
 * 
 * @param int $pid pid of process to check
 * @return boolean true if process is running
 */
function processRunning ( $pid )
{
  system ( "/bin/ps $pid >/dev/null",
           $rv );
  return $rv == 1 ? false : true;
}


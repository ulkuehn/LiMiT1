<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/timeUtility.php
 * 
 * common definitions needed for all scripts dealing with time
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
 * return a human readable approximated time span
 * 
 * @param int $seconds length of the time span in seconds
 * @return string text giving (approximated) time span
 */
function humanReadableDuration ( $seconds )
{
  $timeSpan = "";

  if ( $seconds < 60 )
  {
    $timeSpan = $seconds . _ ( " Sek" );
  }
  else
  {
    $minutes = floor ( $seconds / 60 );
    if ( $minutes < 60 )
    {
      $timeSpan = $minutes . _ ( " Min" );
    }
    else
    {
      $hours = floor ( $minutes / 60 );
      if ( $hours < 48 )
      {
        $timeSpan = $hours . _ ( " Std" );
      }
      else
      {
        $days = floor ( $hours / 24 );
        $timeSpan = $days . _ ( " Tage" );
      }
    }
  }

  return $timeSpan;
}


/**
 * return a human readable exact time span
 * 
 * @param int $seconds time span in seconds
 * @return string human readable exact duration
 */
function humanReadableTimeSpan ( $seconds )
{
  $days = 0;
  if ( $seconds < 0 )
  {
    $seconds = 0;
  }
  $days = floor ( $seconds / 24 / 3600 );
  $seconds = $seconds - 24 * 3600 * floor ( $seconds / 24 / 3600 );
  return $days . ($days == 1 ? _ ( " Tag" ) : _ ( " Tage" )) . ", " . gmdate ( "H:i:s",
                                                                               $seconds );
}


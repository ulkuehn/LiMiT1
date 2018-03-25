<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/connections.php
 * 
 * collect infos on latest connections for live preview while recording is under way
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
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/**
 * current recording seesion
 */
$sessionID = $_GET[ $__[ "include/connections" ][ "params" ][ "recording" ] ];
/**
 * only consider connections later than this unixtime value
 */
$timeStamp = $_GET[ $__[ "include/connections" ][ "params" ][ "timeStamp" ] ];
/**
 * return at most this many connections
 */
$maxItems = $_GET[ $__[ "include/connections" ][ "params" ][ "maxItems" ] ];

$items = 0;

/*
 * process all directory entries
 */
$files = scandir ( "$data_dir/$sessionID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ],
                   SCANDIR_SORT_DESCENDING );
foreach ( $files as $connection )
{
  /*
   * limit to files having correct name
   */
  if ( preg_match ( "/^([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2})([0-9]{2})([0-9]{2})Z.*-(.+),(.+)\.log$/",
                    $connection,
                    $connectionMatch ) )
  {
    /*
     * get unixtime of connection
     */
    $connectionTime = gmmktime ( $connectionMatch[ 4 ],
                                 $connectionMatch[ 5 ],
                                 $connectionMatch[ 6 ],
                                 $connectionMatch[ 2 ],
                                 $connectionMatch[ 3 ],
                                 $connectionMatch[ 1 ] );

    /*
     * process only if later that time threshold
     */
    if ( $connectionTime > $timeStamp )
    {
      /*
       * do a name lookup if necessary
       */
      if ( !isset ( $hosts[ $connectionMatch[ 7 ] ] ) )
      {
        $host = gethostbyaddr ( $connectionMatch[ 7 ] );
        /*
         * gethostbyaddr returned a name, not the ip address
         */
        if ( $host != $connectionMatch[ 7 ] )
        {
          /*
           * do some visual polishing (emphasise domain name)
           */
          $parts = split ( "\.",
                           $host );
          $tld = array_pop ( $parts );
          $domain = array_pop ( $parts );
          $host = implode ( ".",
                            $parts ) . (count ( $parts ) > 0 ? "." : "") . "<strong>$domain.$tld</strong>";
        }
        $hosts[ $connectionMatch[ 7 ] ] = $host;
      }

      /*
       * for each connection return a line consisting of '_' seperated fields, terminated by '#'
       * fields are: running connection number, server (name or ip), port, readable time, bytes, unixtime
       */
      echo count ( $files ) - 2 - $items, "_", $hosts[ $connectionMatch[ 7 ] ], "_", $connectionMatch[ 8 ], "_", strftime ( "%H:%M:%S", $connectionTime ), "_", stat ( "$data_dir/$sessionID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/$connection" )[ 7 ], "_", $connectionTime, "#";

      /*
       * count connections and end loop if limit is reached
       */
      $items++;
      if ( $items >= $maxItems )
      {
        break;
      }
    }
  }
}

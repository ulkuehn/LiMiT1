<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/memoryStickUtility.php
 * 
 * common functions needed for all scripts dealing with memory sticks
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
require_once ("include/processUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * stop database server
 * 
 * @return boolean true if server stopped, false if not stopped
 */
function stopDatabaseServer ()
{
  global $db, $mysqld_pidfile;

  /*
   * make sure server is running
   */
  exec ( "/bin/ps -C mysqld > /dev/null",
         $ps,
         $psRV );
  /*
   * nope ...
   */
  if ( $psRV )
  {
    return false;
  }

  /*
   * get pid
   */
  if ( !is_readable ( $mysqld_pidfile ) )
  {
    return false;
  }
  $mysqlPidFileFH = fopen ( $mysqld_pidfile,
                            "r" );
  $mysqlPid = trim ( fgets ( $mysqlPidFileFH ) );
  fclose ( $mysqlPidFileFH );

  /*
   * shutdown by (mariadb!) command
   */
  $db->exec ( "shutdown" );

  /*
   * check it is stopped now
   */
  for ( $i = 1; $i <= 10; $i++ )
  {
    if ( !processRunning ( $mysqlPid ) )
    {
      return true;
    }
    sleep ( 1 );
  }
  return false;
}


/**
 * (re)start database server
 * 
 * @param boolean $refresh if true reconnect to database (refresh $db)
 * 
 * @return boolean true if start was successful (and $db could be refreshed), false otherwise
 */
function startDatabaseServer ( $refresh = false )
{
  global $mysqld_configfile, $mysqld_pidfile, $db, $database_name, $l;

  /*
   * make sure server is not running
   */
  exec ( "/bin/ps -C mysqld > /dev/null",
         $ps,
         $psRV );
  /*
   * but then it does ...
   */
  if ( !$psRV )
  {
    return false;
  }

  /*
   * start it
   */
  exec ( "/usr/sbin/mysqld --defaults-file=$mysqld_configfile >/dev/null 2>&1 &" );

  /*
   * check that it is running now
   */
  for ( $i = 1; $i <= 10; $i++ )
  {
    if ( processFileRunning ( $mysqld_pidfile ) )
    {
      if ( $refresh )
      {
        try
        {
          $db = new PDO ( "mysql:host=localhost;dbname=$database_name;charset=utf8",
                          "root",
                          "" );
        } catch (PDOException $e)
        {
          return false;
        }
      }
      return true;
    }
    sleep ( 1 );
  }
  return false;
}


/**
 * unmount memory stick
 */
function unmountStick ()
{
  exec ( "/bin/umount /dev/sda1",
         $umountOutput,
         $returnValue );
  if ( $returnValue )
  {
    showErrorMessage ( _ ( "Der Stick konnte nicht wieder ausgehängt werden. Bitte den USB-Stick entfernen und $my_name neu starten." ) );
  }
  else if ( !startDatabaseServer ( true ) )
  {
    showErrorMessage ( _ ( "Der Datenbankserver konnte nicht wieder gestartet werden. Bitte den USB-Stick entfernen und $my_name neu starten." ) );
  }
}


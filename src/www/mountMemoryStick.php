<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file mountMemoryStick.php
 * 
 * try to mount a memory stick that has files containing a suitable database on it
 * if no file system or no database is found the mounting is cancelled an nothing changes
 *
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/connectDB.php");
require_once ("include/probeHardware.php");
require_once ("include/utility.php");
require_once ("include/memoryStickUtility.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

titleAndHelp ( $__[ "include/topMenu" ] [ "values" ] [ "toolsMountMemoryStickMenuName" ],
               _ ( "Mit dieser Funktion kann ein USB-Stick eingebunden werden. Befindet sich auf dem Stick bereits eine $my_name-Datenbank, werden die dort gespeicherten Aufzeichnungen zur Verfügung gestellt. Ist noch keine Datenbank vorhanden, wird sie auf dem Stick erzeugt.<br>Die im Systemspeicher vorhandenen Aufzeichnungen sind nicht verfügbar, solange ein USB-Stick eingebunden ist." ) );

echo "<div class=\"row\">";

$memoryStick = hasMemoryStick ();

/*
 * no stick (shouldn't be here in the first place)
 */
if ( $memoryStick == 0 )
{
  showAlertMessage ( _ ( "Es ist kein USB-Stick eingesteckt" ) );
}
/*
 * stick already mounted - huh?
 */
else if ( $memoryStick == 2 )
{
  showAlertMessage ( _ ( "Der USB-Stick ist bereits eingebunden" ) );
}
/*
 * recording going on, do not touch database server!
 */
else if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  showAlertMessage ( _ ( "Während einer laufenden Aufzeichnung kann ein USB-Stick nicht eingebunden werden." ) );
}
/*
 * conditions okay
 */
else
{
  /*
   * show form
   */
  if ( !isset ( $_POST[ $__[ "mountMemoryStick" ][ "params" ][ "mount" ] ] ) )
  {
    /*
     * analyze stick
     */
    $partitions = false;
    $partitionSize = array ();
    $partitionType = array ();
    exec ( "/sbin/parted -ml",
           $parted,
           $returnValue );
    foreach ( $parted as $line )
    {
      if ( $line == "" )
      {
        $partitions = false;
      }
      $fields = explode ( ":",
                          $line );
      if ( $partitions )
      {
        $partitionSize[ $fields[ 0 ] ] = $fields[ 3 ];
        $partitionType[ $fields[ 0 ] ] = $fields[ 4 ];
      }
      if ( $fields[ 0 ] == "/dev/sda" )
      {
        $totalSize = $fields[ 1 ];
        $productName = $fields[ 6 ];
        $partitions = true;
      }
    }

    echo "<form method=\"post\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "USB-Stick" ), "</h4></div><div class=\"panel-body\">";
    echo "<p>", _ ( "Der eingesteckte USB-Stick" ), " <strong>$productName</strong> ($totalSize) ", _ ( "hat folgende Partitionen" ), ":</p><table class=\"table table-responsive\"><thead><tr><th>", _ ( "Nr." ), "</th><th>", _ ( "Größe" ), "</th><th>", _ ( "Formatierung" ), "</th></tr>";
    foreach ( $partitionSize as $p => $ps )
    {
      echo "<tr><td>$p</td><td>", $partitionSize[ $p ], "</td><td>", $partitionType[ $p ], "</td></tr>";
    }
    echo "</table>";

    if ( strtolower ( $partitionType[ 1 ] ) != "fat32" )
    {
      showAlertMessage ( _ ( "Es werden aktuell nur FAT32-formatierte Sticks unterstützt. Die erste Partition ist jedoch " ) . $partitionType[ 1 ] . _ ( " formatiert." ) );
    }
    else
    {
      /*
       * test mount stick
       */
      exec ( "/bin/mount -o iocharset=utf8 -t vfat /dev/sda1 /mnt",
             $mountOutput,
             $returnValue );
      /*
       * not successful
       */
      if ( $returnValue != 0 )
      {
        showAlertMessage ( _ ( "Der Versuch, den Stick einzubinden, schlug fehl" ) );
      }
      /*
       * successfully mounted, show sizes and contents
       */
      else
      {
        $memFree = `/bin/df -h | /bin/grep \\\\s/mnt\$ | /usr/bin/tr -s ' ' | /usr/bin/cut -d ' ' -f4`;
        exec ( "ls -al /mnt",
               $ls,
               $returnValue );
        echo "<p>", _ ( "Auf der ersten Partition befinden sich folgende Dateien ($memFree frei):" ), "</p><pre class=\"pre-scrollable\">", implode ( "\n",
                                                                                                                                                      array_map ( function($s)
          {
            return str_replace ( "/mnt",
                                 "",
                                 $s );
          },
                                 $ls ) ), "</pre>";
        exec ( "/bin/umount /mnt" );

        echo "<input class=\"btn btn-primary\" type=\"submit\" value=\"", _ ( "Diesen Stick einbinden" ), "\" name=\"", $__[ "mountMemoryStick" ][ "params" ][ "mount" ], "\">";
      }
    }
    echo "</div></div></form>";
  }

  /*
   * do stick integration
   */
  else
  {
    /*
     * stop database server
     */
    if ( !stopDatabaseServer () )
    {
      showErrorMessage ( _ ( "Der Datenbankserver konnte nicht angehalten werden" ) );
    }
    else
    {
      /*
       * mount stick
       */
      exec ( "/bin/mount -o iocharset=utf8 -t vfat /dev/sda1 $data_dir",
             $mountOutput,
             $returnValue );
      /*
       * not successful, restart database server
       */
      if ( $returnValue != 0 )
      {
        showAlertMessage ( _ ( "Der USB-Stick kann nicht eingebunden werden" ) );
        if ( !startDatabaseServer ( true ) )
        {
          showErrorMessage ( _ ( "Der Datenbankserver konnte nicht wieder gestartet werden. Bitte den USB-Stick entfernen und $my_name neu starten." ) );
        }
      }
      /*
       * mount okay
       */
      else
      {
        /*
         * database files on stick?
         */
        if ( !is_dir ( $mysqld_datadir ) )
        {
          exec ( "/usr/bin/mysql_install_db --defaults-file=$mysqld_configfile" );
          showInfoMessage ( _ ( "Es wurde ein Datenbanksystem auf dem Stick eingerichtet" ) );
        }
        /*
         * database files cannot be created
         */
        if ( !is_dir ( $mysqld_datadir ) )
        {
          showAlertMessage ( _ ( "Es kann kein Datenbanksystem auf dem USB-Stick eingerichtet werden. Der Stick wird nicht eingebunden." ) );
          unmountStick ();
        }
        /*
         * database files are on stick
         */
        else
        {
          if ( !startDatabaseServer () )
          {
            showAlertMessage ( _ ( "Der Datenbankserver lässt sich mit den Dateien auf dem Stick nicht starten. Der Stick wird nicht eingebunden." ) );
            unmountStick ();
          }
          else
          {
            /*
             * no database on stick? create it!
             */
            try
            {
              $db = new PDO ( "mysql:host=localhost;dbname=$database_name;charset=utf8",
                              "root",
                              "" );
            } catch (PDOException $e)
            {
              exec ( "echo \"create database $database_name character set utf8\" | /usr/bin/mysql" );
              exec ( "cat $database_initfile | /usr/bin/mysql $database_name" );
              exec ( "cat $ciphers_initfile | /usr/bin/mysql $database_name" );
              exec ( $ciphersuites_cmd );
              showInfoMessage ( _ ( "Es wurde eine neue $my_name-Datenbank auf dem Stick eingerichtet" ) );
            }

            /*
             * still no database on stick? well, that's an error
             */
            try
            {
              $db = new PDO ( "mysql:host=localhost;dbname=$database_name;charset=utf8",
                              "root",
                              "" );
              showSuccessMessage ( _ ( "Der Stick wurde erfolgreich eingebunden" ) );
            } catch (PDOException $e)
            {
              showAlertMessage ( _ ( "Es kann keine Datenbank auf dem USB-Stick eingerichtet werden. Der Stick wird nicht eingebunden." ) );
              unmountStick ();
            }
          }
        }
      }
    }
  }
}

include ("include/closeHTML.php");

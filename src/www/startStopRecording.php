<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file startStopRecording.php
 *
 * start or stop a data recording
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
 * insert code to show a live update of latest connections
 * 
 * @param int $sessionID id of the current recording
 */
function livePreview ( $sessionID )
{
  global $__;

  /*
   * panel with table
   */
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Aktuelle Verbindungen" ), "</h4></div><div class=\"panel-body\">";
  echo "<div class=\"table-responsive\"><table class=\"table table-condensed\"><thead><tr><th class=\"numeric\">", _ ( "Nr." ), "</th><th>", _ ( "Server" ), "</th><th class=\"numeric\">", _ ( "Port" ), "</th><th class=\"numeric\">", _ ( "Bytes" ), "</th><th>", _ ( "Uhrzeit" ), "</th></thead><tbody id=\"", $__[ "startStopRecording" ] [ "ids" ][ "connection" ], "\"></tbody></table></div></div></div>";

  /*
   * the following js code repeatedly calls a function which triggers a php script returning up to $__[ "startStopRecording" ] [ "values" ][ "connectionLogMaxItems" ] new connections since timeStamp
   * new connections are highlighted (extra tr class) for one function call cycle
   * call repetition is not done by setInterval to make sure that js function is not called twice if php script execution time is overly long (setTimeout is used instead)
   * the php script is expected to return a set of fields seperated by '_' for each connection, which are seperated by '#'
   * fields are: running connection number, server (name or ip), port, readable time, bytes, unixtime
   */
  echo "<script> function livePreview() { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { var div = document.getElementById(\"", $__[ "startStopRecording" ] [ "ids" ][ "connection" ], "\"); div.innerHTML = \"\"; var oldTimeStamp = timeStamp; if (xmlhttp.responseText != \"\") { var connections = xmlhttp.responseText.split(\"#\"); for (var i=connections.length-1; i >= 0; i--) { if (connections[i] != \"\") { var con = connections[i].split(\"_\"); if (timeStamp < con[5]) { timeStamp = con[5]; } latestConnections.unshift(connections[i]); } } } for (var i=0, len = latestConnections.length; i < len && i < ", $__[ "startStopRecording" ] [ "values" ][ "connectionLogMaxItems" ], "; i++) { var html = \"\"; var con = latestConnections[i].split(\"_\"); if (parseInt(con[5]) > oldTimeStamp) { html = \"<tr class='success'>\"; } else { html = \"<tr>\"; } html += \"<td class='numeric'>\" + con[0] + \"</td><td>\" + con[1] + \"</td><td class='numeric'>\" + con[2] + \"</td><td class='numeric'>\" + con[4] + \"</td><td>\" + con[3] + \"</td></tr>\"; div.innerHTML += html; } setTimeout(livePreview, ", $__[ "startStopRecording" ] [ "values" ][ "connectionLogUpdateMS" ], "); } }; ";
  echo "xmlhttp.open(\"GET\",\"include/connections.php?", $__[ "include/connections" ][ "params" ][ "recording" ], "=$sessionID&", $__[ "include/connections" ][ "params" ][ "maxItems" ], "=", $__[ "startStopRecording" ] [ "values" ][ "connectionLogMaxItems" ], "&", $__[ "include/connections" ][ "params" ][ "timeStamp" ], "=\"+timeStamp,true); xmlhttp.send(); } var timeStamp = 0; var latestConnections = new Array(); livePreview(); </script>";
}


/**
 * stop processes by their PIDs as found in several pid files in a directory
 * 
 * @param string $directoryWithPidFiles path to pid files
 * @param boolean $killInserter true=kill inserter process as well, false=do not kill inserter process
 */
function killPids ( $directoryWithPidFiles,
                    $killInserter = false )
{
  global $__;

  foreach ( scandir ( $directoryWithPidFiles ) as $file )
  {
    if ( substr ( $file,
                  -strlen ( $__[ "startStopRecording" ] [ "values" ] [ "pidFileExtension" ] ) ) == $__[ "startStopRecording" ] [ "values" ] [ "pidFileExtension" ] && ($killInserter || $file != $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ]) )
    {
      $pidFileFH = fopen ( "$directoryWithPidFiles/$file",
                           "r" );
      $pid = trim ( fgets ( $pidFileFH ) );
      fclose ( $pidFileFH );

      posix_kill ( $pid,
                   SIGINT );
      system ( "/bin/ps $pid >/dev/null",
               $rv );
      if ( $rv == 0 )
      {
        posix_kill ( $pid,
                     SIGKILL );
      }
    }
  }

  /*
   * this is a bit of a hack we need because sslsplit spawns lower privileged processes we don't know the pid of
   */
  system ( "/usr/bin/killall " . $__[ "startStopRecording" ] [ "values" ] [ "sslsplitBinary" ] );
}


/**
 * insert new property or update value of existing property of a device in the db
 * 
 * @param string $propertyValue value of the property
 * @param string $deviceID device id
 * @param string $propertyName name of the property
 */
function addDeviceProperty ( $propertyValue,
                             $deviceID,
                             $propertyName )
{
  global $db;

  if ( $propertyValue != "" )
  {
    $selectPropertyStatement = $db->prepare ( "select * from eigenschaft where geraet=? and name=?" );
    $selectPropertyStatement->execute ( array (
      $deviceID,
      $propertyName ) );
    if ( ($property = $selectPropertyStatement->fetch ()) == false )
    {
      $insertPropertyStatement = $db->prepare ( "insert into eigenschaft set geraet=?, name=?, wert=?" );
      $insertPropertyStatement->execute ( array (
        $deviceID,
        $propertyName,
        $propertyValue ) );
    }
    else
    {
      $updatePropertyStatement = $db->prepare ( "update eigenschaft set wert=? where geraet=? and name=?" );
      $updatePropertyStatement->execute ( array (
        $propertyValue,
        $deviceID,
        $propertyName ) );
    }
  }
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");


/*
 * 
 * no active recording
 * 
 */
if ( !file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  titleAndHelp ( _ ( "Datenverkehr aufzeichnen" ),
                     _ ( "<p>Mit dieser Funktion kann die Aufzeichnung des Datenverkehrs für das bzw. eines der am WLAN \"$__wlan_ssid\" angeschlossenen Geräte gestartet werden. Während die Datenaufzeichnung läuft, wird der Datenverkehr zwischen dem entsprechenden Gerät und dem Internet mitgeschnitten und wird anschließend für die spätere Auswertung in einer Datenbank abgespeichert.</p><p>Sind mehrere Geräte mit dem WLAN \"$__wlan_ssid\" verbunden, kann das Gerät, dessen Daten aufgezciehnet werden sollen aus der Liste \"Aufzuzeichnendes Gerät\" ausgewählt werden. Die Auswahlliste \"Verwaltetes Gerät\" bezieht sich auf die in der Geräteverwaltung eingerichteten Geräte.</p>" ) );

  echo "<div class=\"row\">";

  /*
   * start recording
   */
  if ( isset ( $_POST[ $__[ "startStopRecording" ][ "params" ][ "start" ] ] ) )
  {
    $recordingExists = 0;

    /*
     * check for uniqueness of recording name
     */
    if ( $_POST[ $__[ "startStopRecording" ][ "params" ][ "name" ] ] != "" )
    {
      $selectRecordingStatement = $db->prepare ( "select count(*) from aufzeichnung where name=?" );
      $selectRecordingStatement->execute ( array (
        $_POST[ $__[ "startStopRecording" ][ "params" ][ "name" ] ] ) );
      $recordingExists = $selectRecordingStatement->fetchColumn ();
    }

    /*
     * recording name not unique
     */
    if ( $recordingExists )
    {
      showErrorMessage ( "Eine Aufzeichnung mit dem Namen \"" . $_POST[ $__[ "startStopRecording" ][ "params" ][ "name" ] ] . "\" existiert bereits." );
    }

    /*
     * recording name is unique
     */
    else
    {
      $anErrorOccured = false;

      foreach ( preg_split ( "/\\r\\n|\\r|\\n/",
                             $_POST[ $__[ "startStopRecording" ][ "params" ][ "sources" ] ] ) as $ipMacName )
      {
        list ($ip, $mac, $name) = explode ( " ",
                                            $ipMacName,
                                            3 );
        if ( $ip == $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] )
        {
          if ( !array_key_exists ( $__[ "startStopRecording" ][ "params" ][ "device" ],
                                   $_POST ) || $_POST[ $__[ "startStopRecording" ][ "params" ][ "device" ] ] == "" || $_POST[ $__[ "startStopRecording" ][ "params" ][ "device" ] ] == 0 )
          {
            /*
             * insert new device specific to this recording
             */
            $insertDeviceStatement = $db->prepare ( "insert into geraet set stand=now(), name=?" );
            $insertDeviceStatement->execute ( array (
              _ ( "Für Aufzeichnung " ) . ($_POST[ $__[ "startStopRecording" ][ "params" ][ "name" ] ] == "" ? "" : "\"" . $_POST[ $__[ "startStopRecording" ][ "params" ][ "name" ] ] . "\" ") . _ ( "vom " ) . strftime ( "%d.%m.%Y, %H:%M" ) . _ ( " automatisch hinzugefügt" ) ) );

            $_POST[ $__[ "startStopRecording" ][ "params" ][ "device" ] ] = $db->lastInsertId ();
          }
          /*
           * add or update some basic properties
           */
          addDeviceProperty ( $mac,
                              $_POST[ $__[ "startStopRecording" ][ "params" ][ "device" ] ],
                              _ ( "MAC-Adresse" ) );
          addDeviceProperty ( $name,
                              $_POST[ $__[ "startStopRecording" ][ "params" ][ "device" ] ],
                              _ ( "Name" ) );
        }
      }

      /*
       * create new recording in database
       */
      $insertRecordingStatement = $db->prepare ( "insert into aufzeichnung set start=now(), name=?, info=?, geraet=?, ip=inet_aton(?)" );
      $insertRecordingStatement->execute ( array (
        $_POST[ $__[ "startStopRecording" ][ "params" ][ "name" ] ],
        $_POST[ $__[ "startStopRecording" ][ "params" ][ "infos" ] ],
        $_POST[ $__[ "startStopRecording" ][ "params" ][ "device" ] ],
        $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] ) );
      $recordingID = $db->lastInsertId ();

      /*
       * directories
       */
      $workingDirectory = "$data_dir/$recordingID";
      $tempDirectory = "$temp_dir/$recordingID";

      /*
       * delete existing if necessary
       */
      if ( is_dir ( $workingDirectory ) )
      {
        exec ( "/bin/rm -R $workingDirectory" );
      }
      mkdir ( $workingDirectory );
      mkdir ( "$workingDirectory/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] );
      mkdir ( "$workingDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "certificateDir" ] );

      /*
       * delete existing if necessary
       */
      if ( is_dir ( $tempDirectory ) )
      {
        exec ( "/bin/rm -R $tempDirectory" );
      }
      mkdir ( $tempDirectory );

      /*
       * start process for parallel insertion of data into database
       */
      if ( !$anErrorOccured )
      {
        $commandString = $__[ "startStopRecording" ] [ "values" ] [ "php5Binary" ] . " " . $_SERVER[ "DOCUMENT_ROOT" ] . "/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterScript" ] . " $recordingID " . $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ];
        exec ( sprintf ( "%s >%s 2>&1 & echo $! > %s",
                         $commandString,
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterOutput" ],
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ] ) );
        if ( !processFileRunning ( "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ] ) )
        {
          showErrorMessage ( $__[ "startStopRecording" ] [ "values" ] [ "dbinserterScript" ] . _ ( " konnte nicht gestartet werden" ) );
          $anErrorOccured = true;
        }
      }

      /*
       * start sslsplit process
       */
      if ( !$anErrorOccured )
      {
        $commandString = $base_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sslsplitBinary" ] . " -P -k $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "keyFile" ] . " -c $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] . " -W \"$workingDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "certificateDir" ] . "\" -F \"$workingDirectory/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/%T-%s-%d.log\" -l $workingDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "connectionLog" ] . " ssl $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip " . $__[ "startStopRecording" ] [ "values" ] [ "sslProxyPort" ] . " tcp $__ip_ip1.$__ip_ip2.$__ip_ip3.$__ip_ip " . $__[ "startStopRecording" ] [ "values" ] [ "tcpProxyPort" ];
        exec ( sprintf ( "%s >%s 2>&1 & echo $! > %s",
                         $commandString,
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "sslsplitOutput" ],
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "sslsplitPid" ] ) );
        if ( !processFileRunning ( "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "sslsplitPid" ] ) )
        {
          showErrorMessage ( $__[ "startStopRecording" ] [ "values" ] [ "sslsplitBinary" ] . _ ( " konnte nicht gestartet werden" ) );
          $anErrorOccured = true;
        }
      }

      /*
       * start tcpdump process to record udp traffic
       */
      if ( !$anErrorOccured )
      {
        $commandString = $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpBinary" ] . " -i $wireless_interface -U -w $workingDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPcap" ] . " udp and host " . $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] . " and not host " . $_SERVER[ 'SERVER_ADDR' ];
        exec ( sprintf ( "%s >%s 2>&1 & echo $! > %s",
                         $commandString,
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpOutput" ],
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPid" ] ) );
        if ( !processFileRunning ( "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPid" ] ) )
        {
          showErrorMessage ( $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpBinary" ] . _ ( " konnte nicht gestartet werden" ) );
          $anErrorOccured = true;
        }
      }

      /*
       * if in develop mode start more tcpdumps
       */
      if ( !$anErrorOccured && $develop_mode )
      {
        /*
         * start local tcpdump    
         */
        $commandString = $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpBinary" ] . " -i $wireless_interface -w $workingDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpInternalPrefix" ] . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPcap" ] . " -C 100 host " . $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] . " and not host " . $_SERVER[ 'SERVER_ADDR' ];
        exec ( sprintf ( "%s >%s 2>&1 & echo $! > %s",
                         $commandString,
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpInternalPrefix" ] . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpOutput" ],
                         "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpInternalPrefix" ] . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPid" ] ) );

        if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
        {
          $cfile = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                           "r" );
          fgets ( $cfile );
          $interface = trim ( substr ( fgets ( $cfile ),
                                               1 ) );
          fclose ( $cfile );

          /*
           * start remote tcpdump
           */
          exec ( "/sbin/ifconfig $interface | grep 'inet ' | cut -d: -f2 | cut -d' ' -f1",
                 $remoteIp );
          $commandString = $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpBinary" ] . " -i $interface -w $workingDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpExternalPrefix" ] . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPcap" ] . " -C 100 host " . $remoteIp[ 0 ];
          exec ( sprintf ( "%s >%s 2>&1 & echo $! > %s",
                           $commandString,
                           "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpInternalPrefix" ] . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpOutput" ],
                           "$tempDirectory/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpInternalPrefix" ] . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPid" ] ) );
        }
      }

      /*
       * something went wrong
       */
      if ( $anErrorOccured )
      {
        /*
         * kill all started processes
         */
        killPids ( $tempDirectory,
                   true );

        /*
         * delete directory
         */
        if ( is_dir ( $workingDirectory ) )
        {
          exec ( "/bin/rm -rf $workingDirectory" );
        }

        /*
         * remove recording from database
         */
        $deleteRecordingStatement = $db->prepare ( "delete from aufzeichnung where id=?" );
        $deleteRecordingStatement->execute ( array (
          $recordingID ) );
      }

      /*
       * everything is okay
       */
      else
      {
        /*
         * if connected devices get internet access only on recording, activate masquerading for the recorded source to connect it with the internet facing interface
         */
        if ( $__internet_aufzeichnung )
        {
          system ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --append POSTROUTING --source " . $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] . " --out-interface $interface -j MASQUERADE" );
        }

        /*
         * redirect all necessary ports to sslsplit ssl proxy port
         */
        foreach ( explode ( " ",
                            $__ssl_ports ) as $port )
        {
          system ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat  --source " . $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] . " --append PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports " . $__[ "startStopRecording" ] [ "values" ] [ "sslProxyPort" ] );
        }

        /*
         * redirect all necessary ports to sslsplit non-ssl proxy port
         */
        if ( $__tcp_ports == "" )
        {
          $__tcp_ports = "1:65535";
        }
        foreach ( explode ( " ",
                            $__tcp_ports ) as $port )
        {
          system ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat  --source " . $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] . " --append PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports " . $__[ "startStopRecording" ] [ "values" ] [ "tcpProxyPort" ] );
        }

        /*
         * create session file: 1st line session id, 2nd line ip address of source, 3rd line running=1
         */
        $sFile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                         "w" );
        fwrite ( $sFile,
                 $recordingID . "\n" );
        fwrite ( $sFile,
                 $_POST[ $__[ "startStopRecording" ][ "params" ][ "source" ] ] . "\n" );
        fwrite ( $sFile,
                 "1\n" );
        fclose ( $sFile );

        /*
         * set LED to recording mode
         */
        flashLED ( explode ( " ",
                             $__led2 )[ 0 ],
                             explode ( " ",
                                       $__led2 )[ 1 ] );

        showSuccessMessage ( _ ( "Die Aufzeichnung wurde gestartet." ) );

        /*
         * start live preview of incoming connections
         */
        livePreview ( $recordingID );

        /*
         * change appearance of record button to "stop" mode
         */
        echo "<script>document.getElementById(\"recordButton\").innerHTML=\"", $__[ "include/topMenu" ][ "values" ][ "recordStop" ], "\";</script>";
      }
    }
  }

  /*
   * show recording form
   */
  else
  {
    echo "<form class=\"form-horizontal\" method=\"post\">";

    /*
     * we need a certificate
     */
    if ( !file_exists ( $base_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] ) || !file_exists ( $base_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "keyFile" ] ) )
    {
      showErrorMessage ( _ ( "Es ist kein $my_name-Zertifikat vorhanden." ) );
    }
    /*
     * we must be online
     */
    else if ( !file_exists ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
    {
      showErrorMessage ( _ ( "$my_name ist offline." ) );
    }
    /*
     * ok to do recording
     */
    else
    {
      if ( $__internet_aufzeichnung )
      {
        showInfoMessage ( _ ( "Das Gerät, dessen Daten aufgezeichnet werden, erhält dadurch Zugang zum Internet." ) );
      }

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Eigenschaften der Aufzeichnung" ), "</h4></div><div class=\"panel-body\">";

      /*
       * recording's name
       */
      echo "<div class=\"form-group\"><label for=\"", $__[ "startStopRecording" ][ "ids" ][ "name" ], "\" class=\"col-md-3 control-label\">", _ ( "Name der Aufzeichnung" ), "</label><div class=\"col-md-9\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "startStopRecording" ][ "params" ][ "name" ], "\" id=\"", $__[ "startStopRecording" ][ "ids" ][ "name" ], "\"><p class=\"help-block\">", _ ( "Zur leichteren Identifizierung der Aufzeichnung bei der Auswertung. Optional. Kann später geändert bzw. ergänzt werden." ), "</p></div></div>";

      /*
       * device to record from
       */

      /**
       * list of devices to display
       */
      $connectedDevices = array ();
      /**
       * list of devices to send as parameter
       */
      $connectedDevicesAsParameter = "";

      /*
       * get list of connected devices
       */
      exec ( "/usr/sbin/arp -i $wireless_interface",
             $arp );

      foreach ( $arp as $arpLine )
      {
        /*
         * arp lines are like this:
         * 172.16.0.2               ether   54:2a:a2:xx:yy:zz   C                     limit1wlan
         * = match[1]                       = match[2]
         */
        if ( preg_match ( "/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}).*([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/",
                          $arpLine,
                          $arpMatch ) )
        {
          /*
           * ping device to see if still alive (arp list might contain disconnected or dead devices)
           */
          exec ( "/bin/ping -w 1 -c 1 -q " . $arpMatch[ 1 ],
                 $ping,
                 $pingReturn );

          /*
           * ping ok if return is zero
           */
          if ( !$pingReturn )
          {
            $grep = array ();
            /**
             * name for humans
             */
            $deviceName = "???";
            /**
             * name for script processing
             */
            $deviceNameForParameter = "";

            /*
             * try to find station name in log
             */
            exec ( "/bin/grep \"DHCP[A-Z]* on " . $arpMatch[ 1 ] . "\" /var/log/user.log",
                   $grep );
            foreach ( $grep as $grepLine )
            {
              /*
               * log entries are like this:
               * Nov 11 00:59:28 limit1 dhcpd: DHCPOFFER on 172.16.0.2 to 54:2a:a2:xx:yy:zz (ulrich-PC) via limit1wlan
               *                                                                            = match[1]
               */
              if ( preg_match ( "/\((.+)\)/",
                                $grepLine,
                                $grepMatch ) )
              {
                $deviceName = "\"" . trim ( $grepMatch[ 1 ] ) . "\"";
                $deviceNameForParameter = " " . trim ( $grepMatch[ 1 ] );
                break;
              }
            }
            $connectedDevices[ $arpMatch[ 1 ] ] = "$deviceName &mdash; " . ($_SERVER[ "REMOTE_ADDR" ] == $arpMatch[ 1 ] ? "" : _ ( "nicht " )) . _ ( "dieses Gerät (IP-Adresse " ) . $arpMatch[ 1 ] . _ ( ", MAC-Adresse " ) . $arpMatch[ 2 ] . _ ( ", pingbar)" );
            $connectedDevicesAsParameter .= ($connectedDevicesAsParameter == "" ? "" : "\n") . $arpMatch[ 1 ] . " " . $arpMatch[ 2 ] . $deviceNameForParameter;
          }

          /*
           * no successful ping
           */
          else
          {
            $connectedDevices[ $arpMatch[ 1 ] ] = ($_SERVER[ "REMOTE_ADDR" ] == $arpMatch[ 1 ] ? "" : _ ( "nicht " )) . _ ( "dieses Gerät (IP-Adresse " ) . $arpMatch[ 1 ] . _ ( ", MAC-Adresse " ) . $arpMatch[ 2 ] . _ ( ", nicht pingbar)" );
            $connectedDevicesAsParameter .= ($connectedDevicesAsParameter == "" ? "" : "\n") . $arpMatch[ 1 ] . " " . $arpMatch[ 2 ];
          }
        }
      }

      /*
       * show 'em devices
       */
      if ( count ( $connectedDevices ) > 1 )
      {
        ksort ( $connectedDevices );

        echo "<div class=\"form-group\"><label for=\"", $__[ "startStopRecording" ][ "ids" ][ "source" ], "\" class=\"col-md-3 control-label\">Aufzuzeichnendes Gerät</label><div class=\"col-md-9\"><select class=\"form-control\" name=\"", $__[ "startStopRecording" ][ "params" ][ "source" ], "\" id=\"", $__[ "startStopRecording" ][ "ids" ][ "source" ], "\">";
        foreach ( $connectedDevices as $ip => $info )
        {
          echo "<option value=\"$ip\"", $_SERVER[ "REMOTE_ADDR" ] == $ip ? " selected" : "", ">$info</option>";
        }
        echo "</select><p class=\"help-block\">", _ ( "Es sind mehrere Geräte mit $my_name verbunden. Bitte das Gerät auswählen, dessen Daten aufgezeichnet werden sollen." ), "</p></div></div>";
      }
      /*
       * just one device connected, use it silently
       */
      else
      {
        echo "<input type=\"hidden\" name=\"", $__[ "startStopRecording" ][ "params" ][ "source" ], "\" value=\"", $_SERVER[ "REMOTE_ADDR" ], "\">";
      }
      echo "<input type=\"hidden\" name=\"", $__[ "startStopRecording" ][ "params" ][ "sources" ], "\" value=\"$connectedDevicesAsParameter\">";

      /*
       * managed device
       */
      $selectDeviceStatement = $db->prepare ( "select * from geraet order by name" );
      $selectDeviceStatement->execute ();
      if ( $device = $selectDeviceStatement->fetch () )
      {
        echo "<div class=\"form-group\"><label for=\"", $__[ "startStopRecording" ][ "ids" ][ "device" ], "\" class=\"col-md-3 control-label\">", _ ( "Verwaltetes Gerät" ), "</label><div class=\"col-md-9\"><select class=\"form-control\" name=\"", $__[ "startStopRecording" ][ "params" ][ "device" ], "\" id=\"", $__[ "startStopRecording" ][ "ids" ][ "device" ], "\"><option value=\"0\"></option>";

        do
        {
          echo "<option value=\"", $device[ "id" ], "\">", htmlspecialchars ( $device[ "name" ] ), "</option>";
        }
        while ( $device = $selectDeviceStatement->fetch () );

        echo "</select><p class=\"help-block\">", _ ( "Bitte das passende Gerät zuordnen, das in der Geräteverwaltung erfasst ist. Optional. Kann später geändert bzw. ergänzt werden." ), "</p></div></div>";
      }

      /*
       * remarks
       */
      echo "<div class=\"form-group\"><label for=\"", $__[ "startStopRecording" ][ "ids" ][ "infos" ], "\" class=\"col-md-3 control-label\">", _ ( "Erläuterungen" ), "</label><div class=\"col-md-9\"><textarea class=\"form-control\" style=\"resize:vertical\" id=\"", $__[ "startStopRecording" ][ "ids" ][ "infos" ], "\" name=\"", $__[ "startStopRecording" ][ "params" ][ "infos" ], "\" rows=\"3\"></textarea><p class=\"help-block\">", _ ( "Hilfreiche Hinweise. Diese können später geändert bzw. ergänzt werden." ), "</p></div></div>";

      /*
       * submit
       */
      echo "<input type=\"submit\" class=\"btn btn-success\" name=\"", $__[ "startStopRecording" ][ "params" ][ "start" ], "\" value=\"", _ ( "Aufzeichnung starten" ), "\"></div></div>";
    }

    echo "</form>";
  }

  echo "</div>";
}

/*
 * 
 * there is an active recording
 * 
 */
else
{
  /*
   * read session infos: 1st line session id, 2nd line ip address of source, 3rd line running=0/1
   */
  $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                   "r" );
  $recordingID = trim ( fgets ( $sfile ) );
  $recordedSourceIP = trim ( fgets ( $sfile ) );
  $running = trim ( fgets ( $sfile ) );
  fclose ( $sfile );

  /*
   * recording is running (not stopped)
   */
  if ( $running )
  {
    /*
     * terminate recording
     */
    if ( array_key_exists ( $__[ "startStopRecording" ] [ "params" ] [ "stop" ],
                            $_POST ) )
    {
      /*
       * delete port redirects to sslsplit
       */
      foreach ( explode ( " ",
                          $__ssl_ports ) as $port )
      {
        system ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat  --source $recordedSourceIP  --delete PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports " . $__[ "startStopRecording" ] [ "values" ] [ "sslProxyPort" ] );
      }
      if ( $__tcp_ports == "" )
      {
        $__tcp_ports = "1:65535";
      }
      foreach ( explode ( " ",
                          $__tcp_ports ) as $port )
      {
        system ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat  --source $recordedSourceIP  --delete PREROUTING  --protocol tcp  --dport $port  --jump REDIRECT  --to-ports " . $__[ "startStopRecording" ] [ "values" ] [ "tcpProxyPort" ] );
      }

      /*
       * if necessary, deactivate masquerading
       */
      if ( $__internet_aufzeichnung )
      {
        $cfile = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                         "r" );
        fgets ( $cfile );
        $interface = trim ( substr ( fgets ( $cfile ),
                                             1 ) );
        fclose ( $cfile );
        system ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --delete POSTROUTING --source $recordedSourceIP --out-interface $interface -j MASQUERADE" );
      }

      killPids ( "$temp_dir/$recordingID" );

      /*
       * update recording meta info in db
       */
      $updateRecordingStatement = $db->prepare ( "update aufzeichnung set ende=now() where id=?" );
      $updateRecordingStatement->execute ( array (
        $recordingID ) );

      /*
       * set LED to non recording mode
       */
      flashLED ( explode ( " ",
                           $__led1 )[ 0 ],
                           explode ( " ",
                                     $__led1 )[ 1 ] );

      /*
       * recreate session file: set running=0 in 3rd line
       */
      $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                       "w" );
      fwrite ( $sfile,
               $recordingID . "\n" );
      fwrite ( $sfile,
               $recordedSourceIP . "\n" );
      fwrite ( $sfile,
               "0\n" );
      fclose ( $sfile );

      /*
       * change to no running mode
       */
      $running = 0;

      /*
       * change appearance of record button to "finalizing" mode
       */
      echo "<script>document.getElementById(\"recordButton\").innerHTML=\"", $__[ "include/topMenu" ][ "values" ][ "recordEnd" ], "\";</script>";
    }

    /*
     * cancel recording
     */
    else if ( array_key_exists ( $__[ "startStopRecording" ] [ "params" ] [ "cancel" ],
                                 $_POST ) )
    {
      $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                       "r" );
      $recordingID = trim ( fgets ( $sfile ) );
      fclose ( $sfile );

      if ( is_readable ( "$temp_dir/$recordingID/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ] ) )
      {
        $pidFileFH = fopen ( "$temp_dir/$recordingID/" . $__[ "startStopRecording" ] [ "values" ] [ "dbinserterPid" ],
                             "r" );
        $inserterPid = trim ( fgets ( $pidFileFH ) );
        fclose ( $pidFileFH );

        system ( "/bin/ps $inserterPid >/dev/null",
                 $rv );
        if ( $rv == 0 )
        {
          posix_kill ( $inserterPid,
                       SIGINT );
          $timeout = 100;
          while ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) && $timeout )
          {
            sleep ( 1 );
            $timeout--;
          }
          system ( "/bin/ps $inserterPid >/dev/null",
                   $rv );
          if ( $rv == 0 )
          {
            posix_kill ( $inserterPid,
                         SIGKILL );
          }
        }
      }

      echo "<div class=\"row\">";
      showSuccessMessage ( _ ( "Die Datenbankübernahme der <a href=\"showRecording.php?" . $__[ "showRecording" ] [ "params" ] [ "recording" ] . "=$recordingID\">Aufzeichnung</a> wurde vorzeitig beendet." ) );
      echo "</div>";
    }

    /*
     * show form to stop recording
     */
    else
    {
      titleAndHelp ( _ ( "Aufzeichnung stoppen" ),
                         _ ( "Hiermit wird die laufende Aufzeichnung gestoppt, und die restlichen Daten werden in die Datenbank übernommen." ) );

      echo "<div class=\"row\"><form class=\"form-horizontal\" method=\"post\">";

      if ( $__internet_aufzeichnung )
      {
        $sessionFileFH = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                                 "r" );
        $recordingID = trim ( fgets ( $sessionFileFH ) );
        $recordedSourceIP = trim ( fgets ( $sessionFileFH ) );
        fclose ( $sessionFileFH );
        showInfoMessage ( _ ( "Das Gerät mit der IP-Adresse $recordedSourceIP wird dadurch wieder vom Internet getrennt." ) );
      }

      livePreview ( $recordingID );

      echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Aufzeichnung" ), "</h4></div><div class=\"panel-body\"><input type=\"submit\" class=\"btn btn-primary\" name=\"", $__[ "startStopRecording" ] [ "params" ] [ "stop" ], "\" value=\"", $__[ "startStopRecording" ] [ "values" ] [ "stop" ], "\"></div></div></form></div>";
    }
  }

  /*
   * recording is not running (stopped)
   */
  if ( !$running )
  {
    titleAndHelp ( _ ( "Aufzeichnung in Datenbank übernehmen" ) );

    echo "<script> function progress() { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { if (xmlhttp.responseText != \"\") { document.getElementById(\"", $__[ "startStopRecording" ] [ "ids" ] [ "progressDiv" ], "\").innerHTML = xmlhttp.responseText + \"", jsSave ( "<a href=\"#" . $__[ "startStopRecording" ] [ "ids" ] [ "cancelModal" ] . "\" class=\"btn btn-warning\" data-toggle=\"modal\">" . $__[ "startStopRecording" ] [ "values" ] [ "cancel" ] . "</a>",
                                                                                                                                                                                                                                                                                                                                                           false ), "\"; } else { document.getElementById(\"", $__[ "startStopRecording" ] [ "ids" ] [ "progressDiv" ], "\").innerHTML = \"", jsSave ( showSuccessMessage ( _ ( "Die <a href=\"showRecording.php?" . $__[ "showRecording" ] [ "params" ] [ "recording" ] . "=$recordingID\">Aufzeichnung</a> wurde in die Datenbank übernommen." ),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                false ),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                false ), "\"; } } }; xmlhttp.open (\"GET\", \"include/showDatabaseProgress.php?", $__[ "include/showDatabaseProgress" ][ "params" ][ "id" ], "=$recordingID&", $__[ "include/showDatabaseProgress" ][ "params" ][ "start" ], "=", microNow (), "\", true); xmlhttp.send(); } progress(); var myVar = setInterval (function () { progress() }, 1000); </script>";

    echo "<form method=\"post\"><div class=\"modal fade\" id=\"", $__[ "startStopRecording" ] [ "ids" ] [ "cancelModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\">";
    echo "<div class=\"modal-header\"><div class=\"alert alert-warning\" role=\"alert\"><div class=\"msgIcon\"><span class=\"fa-stack fa-lg\"><i class=\"fa fa-database fa-stack-1x\"></i><i class=\"fa fa-ban fa-stack-2x\"></i></span></div><div class=\"msgText\"><strong>", $__[ "startStopRecording" ] [ "values" ] [ "cancel" ], "</strong></div></div></div>";
    echo "<div class=\"modal-body\"><p>", _ ( "Soll die Übername in die Datenbank tatächlich beendet werden?" ), "</p></div>";
    echo "<div class=\"modal-footer\"><input class=\"btn btn-warning\" type=\"submit\" value=\"", $__[ "startStopRecording" ] [ "values" ] [ "cancel" ], "\" name=\"", $__[ "startStopRecording" ] [ "params" ] [ "cancel" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div></form>";

    echo "<div class=\"row\" id=\"", $__[ "startStopRecording" ] [ "ids" ] [ "progressDiv" ], "\"></div>";
  }
}

include ("include/closeHTML.php");

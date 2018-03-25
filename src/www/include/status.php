<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/status.php
 * 
 * collect extended status information of a LiMiT1 system
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
 * open a new section
 * 
 * @param string $title title of the section
 */
function section ( $title )
{
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">$title</h4></div><div class=\"panel-body\">";
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */


/*
 * 
 * system properties
 * 
 */
section ( _ ( "Systemeigenschaften" ) );

echo "<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr>";
echo "<th>", _ ( "Eigenschaft" ), "</th>";
echo "<th>", _ ( "Wert" ), "</th></tr></thead><tbody>";

/*
 * platform, see http://elinux.org/RPi_HardwareHistory#Board_Revision_History
 */
$hardwareModels = array (
  "a01041" => "Raspberry Pi 2 Model B, Rev 1.1 (Sony)",
  "a21041" => "Raspberry Pi 2 Model B, Rev 1.1 (Embest)",
  "a22042" => "Raspberry Pi 2 Model B, Rev 1.2 (Embest)",
  "900092" => "Raspberry Pi Zero, Rev 1.2 (Sony)",
  "900093" => "Raspberry Pi Zero, Rev 1.3 (Sony)",
  "a02082" => "Raspberry Pi 3 Model B, Rev 1.2 (Sony)",
  "a22082" => "Raspberry Pi 3 Model B, Rev 1.2 (Embest)",
  "9000c1" => "Raspberry Pi Zero W" );
$hardwareRAM = array (
  "a01041" => 1024,
  "a21041" => 1024,
  "a22042" => 1024,
  "900092" => 512,
  "900093" => 512,
  "a02082" => 1024,
  "a22082" => 1024,
  "9000c1" => 512 );

$model = "";
$serial = "";
$hardware = "";
$ram = "";
$cores = "";

if ( file_exists ( "/proc/device-tree/model" ) )
{
  $modelInfoFH = fopen ( "/proc/device-tree/model",
                         "r" );
  $model = fgets ( $modelInfoFH );
  fclose ( $modelInfoFH );
}

$cpuInfoFH = fopen ( "/proc/cpuinfo",
                     "r" );
while ( ($line = fgets ( $cpuInfoFH )) !== false )
{
  if ( preg_match ( "/^Revision\s*:\s*(\S+)/",
                    $line,
                    $m ) )
  {
    if ( $model == "" )
    {
      $model = $hardwareModels[ $m[ 1 ] ];
    }
    if ( $ram == "" )
    {
      $ram = $hardwareRAM[ $m[ 1 ] ];
    }
  }
  if ( preg_match ( "/^Hardware\s*:\s*(\S+)/",
                    $line,
                    $m ) && $hardware == "" )
  {
    $hardware = $m[ 1 ];
  }
  if ( preg_match ( "/^Serial\s*:\s*(\S+)/",
                    $line,
                    $m ) && $serial == "" )
  {
    $serial = $m[ 1 ];
  }
}
fclose ( $cpuInfoFH );

$cores = `/usr/bin/lscpu -e | wc -l`;
if ( $cores >= 2 )
{
  $cores -= 1;
}
else
{
  $cores = "";
}
if ( $hardware != "" && $ram != "" || $cores != "" )
{
  $hardware .= " (" . ($cores != "" ? "$cores CPU, " : "") . ($ram != "" ? $ram . _ ( " MB RAM" ) : "") . ")";
}

echo "<tr><td>", _ ( "Modell" ), "</td><td>", $model == "" ? _ ( "unbekannt" ) : $model, "</td></tr>";
echo "<tr><td>", _ ( "Hardware" ), "</td><td>", $hardware == "" ? _ ( "unbekannt" ) : $hardware, "</td></tr>";
echo "<tr><td>", _ ( "Seriennummer" ), "</td><td>", $serial == "" ? _ ( "unbekannt" ) : $serial, "</td></tr>";

/*
 * MACs
 */
if ( file_exists ( "/sys/class/net/$wired_interface/address" ) )
{
  $wiredFH = fopen ( "/sys/class/net/$wired_interface/address",
                     "r" );
  $wiredMAC = fgets ( $wiredFH );
  fclose ( $wiredFH );
  echo "<tr><td>", _ ( "MAC-Adresse LAN" ), "</td><td>$wiredMAC</td></tr>";
}

if ( file_exists ( "/sys/class/net/$wireless_interface/address" ) )
{
  $wirelessFH = fopen ( "/sys/class/net/$wireless_interface/address",
                        "r" );
  $wirelessMAC = fgets ( $wirelessFH );
  fclose ( $wirelessFH );
  echo "<tr><td>", _ ( "MAC-Adresse WLAN" ), "</td><td>", $wirelessMAC, "</td></tr>";
}

/*
 * kernel
 */
echo "<tr><td>", _ ( "System" ), "</td><td>", `/usr/bin/lsb_release -is|tr -d '\n'`, " ", `/usr/bin/lsb_release -rs|tr -d '\n'`, " (", `/usr/bin/lsb_release -cs|tr -d '\n'`, "), ", _ ( "Kernel " ), `/bin/uname -r|tr -d '\n'`, " (", `/bin/uname -v|tr -d '\n'`, ")</td></tr>";

/*
 * storage
 */
list ($isUSB, $storageUsed, $storageFree, $storagePercent) = storageInformation ();
$storage = _ ( "SD-Karte" );
if ( $isUSB )
{
  $storage = _ ( "USB-Stick" );
  exec ( "/sbin/parted -ml",
         $parted,
         $returnValue );
  foreach ( $parted as $line )
  {
    $fields = explode ( ":",
                        $line );
    if ( $fields[ 0 ] == "/dev/sda" )
    {
      $storage .= " (" . $fields[ 6 ] . ", " . $fields[ 1 ] . ")";
    }
  }
}
echo "<tr><td>", _ ( "Datenspeicher" ), "</td><td>$storage</td></tr>";

/*
 * software version
 */
echo "<tr><td>", _ ( "Software-Version" ), "</td><td>", $my_version, $my_codename == "" ? "" : " (\"$my_codename\")", "</td></tr>";

echo "</tbody></table></div></div></div></div>";


/*
 * 
 * system state
 * 
 */
section ( _ ( "Systemzustand" ) );

/*
 * CPU load
 */
exec ( "/usr/bin/top -b -n 2 -d 1 | grep \"^%Cpu\"",
       $top );
if ( preg_match ( "/\s+([0-9]+),([0-9]+)\s+us.*,\s+([0-9]+),([0-9]+)\s+id/",
                  $top[ 1 ],
                  $m ) )
{
  $user = round ( $m[ 1 ] + $m[ 2 ] / 10 );
  $idle = $m[ 3 ];
  if ( $user + $idle > 100 )
  {
    $idle = 100 - $user;
    $system = 0;
  }
  else
  {
    $system = 100 - $user - $idle;
  }
  echo "<div class=\"col-md-3 col-lg-2\">", _ ( "CPU-Auslastung" ), "</div><div class=\"col-md-9 col-lg-10\"><div class=\"progress\">";
  echo "<div class=\"progress-bar progress-bar-info progress-bar-striped\" style=\"width:$system%\"></div>";
  echo "<div class=\"progress-bar progress-bar-primary progress-bar-striped\" style=\"width:$user%\"></div>";
  echo "<div class=\"progress-bar progress-bar-success progress-bar-striped\" style=\"width:$idle%\"><p style=\"color:black;font-weight:bold\">$idle % ", _ ( "Leerlauf" ), "</p></div></div></div>";
}

/*
 * RAM
 */
exec ( "/usr/bin/free -m",
       $free );
if ( preg_match ( "/([0-9]+)\s+([0-9]+)\s+([0-9]+)/",
                  $free[ 1 ],
                  $m ) )
{
  $usedMem = $m[ 2 ];
  $freeMem = $m[ 1 ] - $m[ 2 ];
  $percentUsed = round ( $m[ 2 ] * 100 / $m[ 1 ] );
  $percentFree = 100 - $percentUsed;
  echo "<div class=\"col-md-3 col-lg-2\">", _ ( "RAM" ), "</div><div class=\"col-md-9 col-lg-10\"><div class=\"progress\">";

  echo "<div class=\"progress-bar progress-bar-info progress-bar-striped\" style=\"width:$percentUsed%\"><p style=\"color:black;font-weight:bold\">$usedMem ", _ ( "kB verwendet" ), "</p></div>";
  echo "<div class=\"progress-bar progress-bar-success progress-bar-striped\" style=\"width:$percentFree%\"><p style=\"color:black;font-weight:bold\">$freeMem ", _ ( "kB frei" ), "</p></div></div></div>";
}

/*
 * CPU temperature
 */
if ( is_readable ( "/sys/class/thermal/thermal_zone0/temp" ) )
{
  $tfile = fopen ( "/sys/class/thermal/thermal_zone0/temp",
                   "r" );
  $temperature = fgets ( $tfile );
  fclose ( $tfile );
  /*
   * convert to celsius (system value is millicelsius)
   */
  $cpuTemperature = floor ( $temperature / 1000 );
  $class = $cpuTemperature < 60 ? "success" : ($cpuTemperature < 75 ? "warning" : "danger");
  echo "<div class=\"col-md-3 col-lg-2\">", _ ( "CPU Temperatur" ), "</div><div class=\"col-md-9 col-lg-10\"><div class=\"progress\">";
  echo "<div class=\"progress-bar progress-bar-$class progress-bar-striped\" style=\"width:$cpuTemperature%\"><p style=\"color:black;font-weight:bold\">$cpuTemperature&deg; C</p></div></div></div>";
}

/*
 * storage
 */
echo "<div class=\"col-md-3 col-lg-2\">", $isUSB ? _ ( "USB-Stick" ) : _ ( "SD-Karte" ), "</div><div class=\"col-md-9 col-lg-10\"><div class=\"progress\">";
echo "<div class=\"progress-bar progress-bar-info progress-bar-striped\" style=\"width:$storagePercent%\"><p style=\"color:black;font-weight:bold\">$storageUsed ", _ ( "verwendet" ), "</p></div>";
echo "<div class=\"progress-bar progress-bar-success progress-bar-striped\" style=\"width:", (100 - $storagePercent), "%\"><p style=\"color:black;font-weight:bold\">$storageFree ", _ ( "frei" ), "</p></div>";
echo "</div></div>";


echo "</div></div></div></div>";


/*
 * 
 * connected devices
 * 
 */
section ( _ ( "Mit WLAN \"$__wlan_ssid\" verbundene Geräte" ) );

echo "<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr>";
echo "<th>", _ ( "Name" ), "</th>";
echo "<th>", _ ( "IP-Adresse" ), "</th>";
echo "<th>", _ ( "MAC-Adresse" ), "</th>";
echo "<th>", _ ( "Ping" ), "</th>";
echo "<th>", _ ( "Hinweis" ), "</th></tr></thead><tbody>";

exec ( "/usr/sbin/arp -i $wireless_interface",
       $arp );
foreach ( $arp as $arpLine )
{
  if ( preg_match ( "/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}).*([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/",
                    $arpLine,
                    $m ) )
  {
    $ipAddress = $m[ 1 ];
    exec ( "/bin/ping -w 1 -c 1 -q " . $ipAddress,
           $p,
           $r );
    /*
     * ping is ok
     */
    if ( !$r )
    {
      $hn = array ();
      unset ( $name );
      exec ( "/bin/grep \"DHCP[A-Z]* on " . $ipAddress . "\" /var/log/user.log",
             $hostname );
      foreach ( $hostname as $hostLine )
      {
        if ( preg_match ( "/\((.+)\)/",
                          $hostLine,
                          $hm ) )
        {
          $name = trim ( $hm[ 1 ] );
          break;
        }
      }
      echo "<tr><td>$name</td><td>$ipAddress</td><td>", $m[ 2 ], "</td><td>", _ ( "ja" ), "</td><td>";
      if ( !isset ( $name ) )
      {
        echo _ ( "Name nicht ermittelbar" );
      }
      echo "</td></tr>";
    }
    /*
     * no ping
     */
    else
    {
      echo "<tr><td></td><td>$ipAddress</td><td>", $m[ 2 ], "</td><td>", _ ( "nein" ), "</td><td>", _ ( "Das Gerät ist möglicherweise offline" ), "</td></tr>";
    }
  }
}

echo "</tbody></table></div></div></div>";

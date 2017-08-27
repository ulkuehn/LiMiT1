<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/status.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to collect extended status information of a LiMiT1 system
//
//==============================================================================
//==============================================================================

require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");


function section ($titel)
{
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
          $titel
        </h4>
      </div>
      <div class="panel-body">
LIMIT1;
}


// Systemeigenschaften
section ("Systemeigenschaften");

echo <<<LIMIT1
        <div class="table-responsive">
          <table id="Eigenschaften" class="table table-hover">
            <thead>
              <tr>
                <th>Eigenschaft</th>
                <th>Wert</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

# Platform
$models = array ( "a01041" => "Raspberry Pi 2 Model B, Rev 1.1 (Sony)",
                  "a21041" => "Raspberry Pi 2 Model B, Rev 1.1 (Embest)",
                  "a22042" => "Raspberry Pi 2 Model B, Rev 1.2 (Embest)",
                  "900092" => "Raspberry Pi Zero, Rev 1.2 (Sony)",
                  "900093" => "Raspberry Pi Zero, Rev 1.3 (Sony)",
                  "a02082" => "Raspberry Pi 3 Model B, Rev 1.2 (Sony)",
                  "a22082" => "Raspberry Pi 3 Model B, Rev 1.2 (Embest)" );
$cpuinfo = fopen ("/proc/cpuinfo", "r");
while (($line = fgets ($cpuinfo)) !== false)
{
  if (preg_match ("/^Revision\s*:\s*(\S+)/", $line, $m))
  {
    echo "<tr><td>Modell</td><td>",$models[$m[1]],"</td></tr>";
  }
  if (preg_match ("/^Hardware\s*:\s*(\S+)/", $line, $m))
  {
    echo "<tr><td>Hardware</td><td>",$m[1],"</td></tr>";
  }
  if (preg_match ("/^Serial\s*:\s*(\S+)/", $line, $m))
  {
    echo "<tr><td>Seriennummer</td><td>",$m[1],"</td></tr>";
  }
}
fclose ($cpuinfo);

# MACs
$eth = fopen ("/sys/class/net/$wired_interface/address", "r");
$line = fgets ($eth);
fclose ($eth);
echo "<tr><td>MAC-Adresse LAN</td><td>",$line,"</td></tr>";
$eth = fopen ("/sys/class/net/$wireless_interface/address", "r");
$line = fgets ($eth);
fclose ($eth);
echo "<tr><td>MAC-Adresse WLAN</td><td>",$line,"</td></tr>";

# Software
echo "<tr><td>Software-Version</td><td>",$my_version,"</td></tr>";

echo <<<LIMIT1
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
LIMIT1;


// Systemzustand
section ("Systemzustand");

# CPU
exec ("/usr/bin/top -b -n 2 -d 1 | grep \"^%Cpu\"", $top);
if (preg_match ("/\s+([0-9]+),([0-9]+)\s+us.*,\s+([0-9]+),([0-9]+)\s+id/", $top[1], $m))
{
  $user = round ($m[1]+$m[2]/10);
  $idle = $m[3];
  if ($user+$idle>100)
  {
    $idle = 100-$user;
    $system = 0;
  }
  else
  {
    $system = 100-$user-$idle;
  }
  echo <<<LIMIT1
            <div class="col-md-3 col-lg-2">
              CPU-Auslastung
            </div>
            <div class="col-md-9 col-lg-10">
              <div class="progress">
                <div class="progress-bar progress-bar-info progress-bar-striped" style="width:$system%">
                </div>
                <div class="progress-bar progress-bar-primary progress-bar-striped" style="width:$user%">
                </div>
                <div class="progress-bar progress-bar-success progress-bar-striped" style="width:$idle%">
                  <p style="color:black;font-weight:bold">$idle % idle</p>
                </div>
              </div>
            </div>
LIMIT1;
}

# RAM
exec ("/usr/bin/free -m", $free);
if (preg_match ("/([0-9]+)\s+([0-9]+)\s+([0-9]+)/", $free[1], $m))
{
  $used = $m[2];
  $free = $m[1]-$m[2];
  $pused = round ($m[2]*100/$m[1]);
  $pfree = 100-$pused;
  echo <<<LIMIT1
            <div class="col-md-3 col-lg-2">
              RAM
            </div>
            <div class="col-md-9 col-lg-10">
              <div class="progress">
                <div class="progress-bar progress-bar-info progress-bar-striped" style="width:$pused%">
                  <p style="color:black;font-weight:bold">$used kB verwendet</p>
                </div>
                <div class="progress-bar progress-bar-success progress-bar-striped" style="width:$pfree%">
                  <p style="color:black;font-weight:bold">$free kB frei</p>
                </div>
              </div>
            </div>
LIMIT1;
}

# CPU-Temp
if (is_readable ("/sys/class/thermal/thermal_zone0/temp"))
{
  $tfile = fopen ("/sys/class/thermal/thermal_zone0/temp", "r");
  $temp = fgets ($tfile);
  fclose ($tfile);
  $cputemp = floor($temp/1000);
  $class = $cputemp<60? "success" : ($cputemp<75? "warning" : "danger");
  echo <<<LIMIT1
            <div class="col-md-3 col-lg-2">
              CPU Temperatur
            </div>
            <div class="col-md-9 col-lg-10">
              <div class="progress">
                <div class="progress-bar progress-bar-$class progress-bar-striped" style="width:$cputemp%">
                  <p style="color:black;font-weight:bold">$cputemp&deg; C</p>
                </div>
              </div>
            </div>
LIMIT1;
}

echo <<<LIMIT1
          </div>
        </div>
      </div>
    </div>
LIMIT1;


// WLAN-Geräte
section ("Mit WLAN \"$__wlan_ssid\" verbundene Geräte");

echo <<<LIMIT1
        <div class="table-responsive">
          <table id="Connected" class="table table-hover">
            <thead>
              <tr>
                <th>Name</th>
                <th>IP-Adresse</th>
                <th>MAC-Adresse</th>
                <th>Ping</th>
                <th>Hinweis</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

exec ("/usr/sbin/arp -i $wireless_interface", $arp);
foreach ($arp as $aline)
{
  if ( preg_match ("/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}).*([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/", $aline, $m) )
  {
    exec ("/bin/ping -w 1 -c 1 -q ".$m[1], $p, $r);
    // ping ok
    if (!$r)
    {
      $hn = array();
      unset ($name);
      exec ("/bin/grep \"DHCP[A-Z]* on ".$m[1]."\" /var/log/user.log", $hn);
      foreach ($hn as $hline)
      {
        if (preg_match ("/\((.+)\)/", $hline, $hm))
        {
          $name = trim($hm[1]);
          break;
        }
      }
      echo <<<LIMIT1
      <tr>
        <td>$name</td>
        <td>{$m[1]}</td>
        <td>{$m[2]}</td>
        <td>ja</td>
LIMIT1;
      if (!isset ($name))
      {
        echo "<td>Name nicht ermittelbar</td>";
      }
      else
      {
        echo "<td></td>";
      }
      echo "</tr>";
    }
    // no ping
    else
    {
      echo <<<LIMIT1
      <tr>
        <td></td>
        <td>{$m[1]}</td>
        <td>{$m[2]}</td>
        <td>nein</td>
        <td>Das Gerät ist möglicherweise offline</td>
      </tr>
LIMIT1;
    }
  }
}

echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
LIMIT1;

?>

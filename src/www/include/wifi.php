<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/wifi.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display information of available wifis within reach
//              executes a wifi scan and processes the technical info to
//              userland level
//
//==============================================================================
//==============================================================================
// Beschriftungen der Eingabefelder
$_ssid = "SSID";
$_key = "Passwort";
$_verbinden = "Verbinden";
// Ids
$_hssid = "hssid";
$_wifiname = "wifiname";

require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");

exec ( "/sbin/iw $wireless_interface scan", $lines, $erg );
if ( $erg != 0 )
{
  header ( "HTTP/1.0 501 Not Implemented" );
}
else
{
  foreach ( $lines as $line )
  {
    if ( preg_match ( "/^\s*signal\s*:\s*-([0-9]+)\.00 dBm/", $line, $ma ) )
    {
      $qual = $ma[ 1 ];
    }
    if ( preg_match ( "/^\s*SSID\s*:\s*(.*)/", $line, $ma ) )
    {
      $essid = $ma[ 1 ];
    }
    if ( preg_match ( "/^\s*WP[AS]:/", $line ) || preg_match ( "/^\s*RSN:/", $line ) )
    {
      $enc = 1;
    }
    if ( preg_match ( "/^BSS/", $line ) && isset ( $essid ) && $essid != "" && $essid != $__wlan_ssid && (!isset ( $quality[ $essid ] ) || $quality[ $essid ] > $qual) )
    {
      $quality[ $essid ] = $qual;
      $encrypt[ $essid ] = $enc;
      $essid = "";
      $enc = 0;
      $qual = 0;
    }
  }
  if ( $essid != "" && $essid != $__wlan_ssid && (!isset ( $quality[ $essid ] ) || $quality[ $essid ] > $qual) )
  {
    $quality[ $essid ] = $qual;
    $encrypt[ $essid ] = $enc;
  }

  foreach ( $quality as $key => $val )
  {
    /*
      Wireless signal strength is traditionally measured in either percentile or dBm (the power ratio in decibels of the measured power referenced to one milliwatt.) By default, CommView for WiFi displays the signal strength in dBm. The level of 100% is equivalent to the signal level of -35 dBm and higher, e.g. both -25 dBm and -15 dBm will be shown as 100%, because this level of signal is very high. The level of 1% is equivalent to the signal level of -95 dBm. Between -95 dBm and -35 dBm, the percentage scale is linear, i.e. 50% is equivalent to -65 dBm.
      -- http://www.tamos.com/htmlhelp/commwifi/understandingsignalstrength.htm

      −10 dBm 	100 µW 	Maximum received signal power of wireless network (802.11 variants)
      −100 dBm 	0.1 pW 	Minimum received signal power of wireless network (802.11 variants)
      -- https://en.wikipedia.org/wiki/DBm
     */
    if ( $val <= 35 )
    {
      $p = 100;
    }
    else if ( $val >= 95 )
    {
      $p = 0;
    }
    else
    {
      $p = 100 - floor ( ($val - 35) * 100 / 60 );
    }
    $flavor = $p < 34 ? "danger" : ($p < 67 ? "warning" : "success");

    echo <<<LIMIT1
    <div class="row">
      <div class="col-xs-2 col-md-1">
LIMIT1;
    if ( $encrypt[ $key ] )
    {
      echo "<a href=\"#passwordModal\" class=\"btn btn-xs btn-danger\" data-toggle=\"modal\" onclick=\"document.getElementById('pass').value=''; document.getElementById('wifiname').innerHTML='$key';document.getElementById('hssid').value='$key';\"><i class=\"fa fa-lock fa-fw\"></i></a>";
    }
    else
    {
      echo "<button class=\"btn btn-xs btn-success\" type=\"submit\" name=\"verbinden\" value=\"$_verbinden\" onclick=\"document.getElementById('hssid').value='$key';\"><i class=\"fa fa-unlock fa-fw\"></i></button>";
    }
    echo <<<LIMIT1
      </div>
      <div class="col-xs-10 col-md-11">
        <p><strong>$key</strong></p>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="progress">
          <div class="progress-bar progress-bar-$flavor progress-bar-striped" role="progressbar" style="width:$p%;">
LIMIT1;
    if ( $p >= 10 )
    {
      echo "<p style=\"color:black;font-weight:bold\">$p %</p>";
    }
    echo <<<LIMIT1
          </div>
        </div>
      </div>
    </div>
LIMIT1;
  }
}
?>

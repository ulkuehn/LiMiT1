<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/scanWifi.php
 * 
 * display information of available wifis within reach
 * executes a wifi scan and processes the technical info to userland level
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
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * do a wifi scan
 */
exec ( "/sbin/iw $wireless_interface scan",
       $lines,
       $returnValue );
if ( $returnValue != 0 )
{
  header ( "HTTP/1.0 501 Not Implemented" );
}
else
{
  foreach ( $lines as $line )
  {
    if ( preg_match ( "/^\s*signal\s*:\s*-([0-9]+)\.00 dBm/",
                      $line,
                      $ma ) )
    {
      $quality = $ma[ 1 ];
    }
    if ( preg_match ( "/^\s*SSID\s*:\s*(.*)/",
                      $line,
                      $ma ) )
    {
      $essid = $ma[ 1 ];
    }
    if ( preg_match ( "/^\s*(WP[AS]|RSN):/",
                      $line ) )
    {
      $encrypted = 1;
    }
    if ( preg_match ( "/^BSS/",
                      $line ) && isset ( $essid ) && $essid != "" && $essid != $__wlan_ssid && (!isset ( $quality[ $essid ] ) || $qualities[ $essid ] > $quality) )
    {
      $qualities[ $essid ] = $quality;
      $encryptions[ $essid ] = $encrypted;
      $essid = "";
      $encrypted = 0;
      $quality = 0;
    }
  }
  if ( $essid != "" && $essid != $__wlan_ssid && (!isset ( $qualities[ $essid ] ) || $qualities[ $essid ] > $quality) )
  {
    $qualities[ $essid ] = $quality;
    $encryptions[ $essid ] = $encrypted;
  }

  foreach ( $qualities as $essid => $quality )
  {
    /*
      Wireless signal strength is traditionally measured in either percentile or dBm (the power ratio in decibels of the measured power referenced to one milliwatt.) By default, CommView for WiFi displays the signal strength in dBm. The level of 100% is equivalent to the signal level of -35 dBm and higher, e.g. both -25 dBm and -15 dBm will be shown as 100%, because this level of signal is very high. The level of 1% is equivalent to the signal level of -95 dBm. Between -95 dBm and -35 dBm, the percentage scale is linear, i.e. 50% is equivalent to -65 dBm.
      -- http://www.tamos.com/htmlhelp/commwifi/understandingsignalstrength.htm

      −10 dBm 	100 µW 	Maximum received signal power of wireless network (802.11 variants)
      −100 dBm 	0.1 pW 	Minimum received signal power of wireless network (802.11 variants)
      -- https://en.wikipedia.org/wiki/DBm
     */
    if ( $quality <= 35 )
    {
      $progressBarLength = 100;
    }
    else if ( $quality >= 95 )
    {
      $progressBarLength = 0;
    }
    else
    {
      $progressBarLength = 100 - floor ( ($quality - 35) * 100 / 60 );
    }
    $flavor = $progressBarLength < 34 ? "danger" : ($progressBarLength < 67 ? "warning" : "success");

    echo "<div class=\"row\"><div class=\"col-xs-2 col-md-1\">";

    if ( $encryptions[ $essid ] )
    {
      echo "<a href=\"#", $__[ "connectWLAN" ][ "ids" ][ "passwordModal" ], "\" class=\"btn btn-xs btn-danger\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "connectWLAN" ][ "ids" ][ "password" ], "').value=''; document.getElementById('", $__[ "connectWLAN" ][ "ids" ] [ "wifiName" ], "').innerHTML='$essid'; document.getElementById('", $__[ "connectWLAN" ][ "ids" ][ "autoSSID" ], "').value='$essid';\"><i class=\"fa fa-lock fa-fw\"></i></a>";
    }
    else
    {
      echo "<button class=\"btn btn-xs btn-success\" type=\"submit\" name=\"", $__[ "connectWLAN" ][ "params" ][ "connect" ], "\" value=\"", $__[ "connectWLAN" ][ "values" ][ "connect" ], "\" onclick=\"document.getElementById('", $__[ "connectWLAN" ][ "ids" ][ "autoSSID" ], "').value='$essid';\"><i class=\"fa fa-unlock fa-fw\"></i></button>";
    }
    echo "</div><div class=\"col-xs-10 col-md-11\"><p><strong>$essid</strong></p></div></div><div class=\"row\"><div class=\"col-md-12\"><div class=\"progress\"><div class=\"progress-bar progress-bar-$flavor progress-bar-striped\" role=\"progressbar\" style=\"width:$progressBarLength%;\">";

    if ( $progressBarLength >= 10 )
    {
      echo "<p style=\"color:black;font-weight:bold\">$progressBarLength %</p>";
    }
    echo "</div></div></div></div>";
  }
}

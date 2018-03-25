<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file connectWLAN.php
 * 
 * connect a LiMiT1 system to a wifi network
 * supports manual ssid specification and scanning of visible networks 
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
require_once ("include/onlineOfflineUtility.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

// Beschriftungen der Eingabefelder
$_ssid = "SSID";
$_pass = "Passwort";


titleAndHelp ( _ ( "Internetverbindung per WLAN" ),
                   _ ( "Ist ein (weiterer) WLAN-Adapter vorhanden, kann die Internetverbindung über ein Funknetzwerk hergestellt werden.<br>Es kann sowohl ein erkanntes WLAN ausgewählt als auch eine SSID manuell angegeben werden." ) );

/*
 * check if wifi adapter present
 */
foreach ( scandir ( "/sys/class/net" ) as $interface )
{
  if ( $interface != $wired_interface && $interface != $wireless_interface )
  {
    unset ( $udev );
    exec ( "/bin/udevadm info -q property /sys/class/net/$interface",
           $udev,
           $ret );
    foreach ( $udev as $info )
    {
      if ( $info == "DEVTYPE=wlan" )
      {
        $wireless_internet = $interface;
      }
    }
  }
}

/*
 * no wifi adapter
 */
if ( $wireless_internet == "" )
{
  echo "<div class=\"row\">";
  showErrorMessage ( _ ( "Es ist kein WLAN-Stick angeschlossen." ) );
  echo "</div>";
}

/*
 * wifi adapter found
 */
else if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  echo "<div class=\"row\">";
  showErrorMessage ( _ ( "Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor die Internetverbindung von $my_name geändert werden kann." ) );
  echo "</div>";
}
else
{
  /*
   * connect to wifi
   */
  if ( isset ( $_POST[ $__[ "connectWLAN" ][ "params" ][ "connect" ] ] ) )
  {
    $connectTo = isset ( $_POST[ $__[ "connectWLAN" ][ "params" ][ "autoSSID" ] ] ) && $_POST[ $__[ "connectWLAN" ][ "params" ][ "autoSSID" ] ] != "" ? $_POST[ $__[ "connectWLAN" ][ "params" ][ "autoSSID" ] ] : $_POST[ $__[ "connectWLAN" ][ "params" ][ "manualSSID" ] ];
    $wlanPassword = $_POST[ $__[ "connectWLAN" ][ "params" ][ "password" ] ];

    if ( $connectTo != "" )
    {
      goOffline ();

      writeOnlineScript ( "/usr/bin/killall dhclient\n/bin/rm $dhclient_pidfile\n/bin/rm $dhclient_leasefile\n" . ($wlanPassword == "" ? "/sbin/iwconfig $wireless_internet essid \"$connectTo\"" : "/usr/bin/killall wpa_supplicant\n/sbin/wpa_supplicant -B -D nl80211 -i $wireless_internet -c " . $temp_dir . "/" . $__[ "connectWLAN" ][ "values" ][ "wpaSupplicantFile" ]) . "\n/sbin/dhclient -v -pf $dhclient_pidfile -lf $dhclient_leasefile $wireless_internet\n",
                          $wireless_internet );

      if ( $wlanPassword != "" )
      {
        $wpaConfigFileFH = fopen ( $temp_dir . "/" . $__[ "connectWLAN" ][ "values" ][ "wpaSupplicantFile" ],
                                   "w" );
        fwrite ( $wpaConfigFileFH,
                 "ctrl_interface=/var/run/wpa_supplicant\nctrl_interface_group=0\n" );
        exec ( "/usr/bin/wpa_passphrase \"$connectTo\" \"$wlanPassword\"",
               $lines,
               $returnValue );
        if ( $returnValue == 0 )
        {
          foreach ( $lines as $line )
          {
            fwrite ( $wpaConfigFileFH,
                     "$line\n" );
          }
        }
        fclose ( $wpaConfigFileFH );
      }

      writeOfflineScript ( "# " . _ ( "WLAN" ) . " ($connectTo)\n# $wireless_internet\n/usr/bin/killall wpa_supplicant\n/sbin/ifconfig $wireless_internet 0.0.0.0\n",
                                      $wireless_internet );

      goOnline ( _ ( "WLAN" ) );
    }
  }

  /*
   * show form (
   */
  if ( !isset ( $_POST[ $__[ "connectWLAN" ][ "params" ][ "connect" ] ] ) || $connectTo == "" )
  {
    echo "<form class=\"form-horizontal\" method=\"post\"><input type=\"hidden\" id=\"", $__[ "connectWLAN" ][ "ids" ][ "autoSSID" ], "\" name=\"", $__[ "connectWLAN" ][ "params" ][ "autoSSID" ], "\" value=\"\"><div class=\"row\">";

    if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
    {
      $offlineFH = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                           "r" );
      $line = fgets ( $offlineFH );
      fclose ( $offlineFH );
      $internet = trim ( substr ( $line,
                                  1 ) );
      showAlertMessage ( _ ( "$my_name ist bereits per $internet mit dem Internet verbunden. Diese bestehende Verbindung wird beendet, bevor eine Internetverbindung per WLAN hergestellt wird." ) );
    }

    /*
     * manual ssid
     */
    echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "SSID manuell angeben" ), "</h4></div><div class=\"panel-body\">";
    /*
     * no ssid given
     */
    if ( isset ( $_POST[ $__[ "connectWLAN" ][ "params" ][ "connect" ] ] ) )
    {
      showErrorMessage ( _ ( "SSID: Bitte einen Wert eingeben" ) );
    }
    echo "<div class=\"form-group\"><label for=\"", $__[ "connectWLAN" ][ "params" ][ "manualSSID" ], "\" class=\"control-label col-sm-2 col-md-2 col-lg-1\">", _ ( "SSID" ), "</label><div class=\"col-sm-6 col-md-7 col-lg-9\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "connectWLAN" ][ "params" ][ "manualSSID" ], "\" id=\"", $__[ "connectWLAN" ][ "params" ][ "manualSSID" ], "\" value=\"", $_POST[ $__[ "connectWLAN" ][ "params" ][ "manualSSID" ] ], "\"></div>";
    /*
     * connect to open wifi
     */
    echo "<div class=\"col-sm-4 col-md-3 col-lg-2\"><button class=\"btn btn-sm btn-success\" type=\"submit\" value=\"", $__[ "connectWLAN" ][ "values" ][ "connect" ], "\" name=\"", $__[ "connectWLAN" ][ "params" ][ "connect" ], "\" title=\"", _ ( "mit offenem WLAN verbinden" ), "\"><i class=\"fa fa-lg fa-unlock\"></i></button>";
    /*
     * connect to enycrpyted wifi
     */
    echo "<a class=\"btn btn-sm btn-danger\" href=\"#", $__[ "connectWLAN" ][ "ids" ][ "passwordModal" ], "\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "connectWLAN" ][ "ids" ][ "password" ], "').value=''; document.getElementById('", $__[ "connectWLAN" ][ "ids" ] [ "wifiName" ], "').innerHTML=document.getElementById('", $__[ "connectWLAN" ][ "params" ][ "manualSSID" ], "').value;\" title=\"", _ ( "Passwort eingeben und mit verschlüsseltem WLAN verbinden" ), "\"><i class=\"fa fa-lg fa-lock\"></i></a></div></div></div>";

    /*
     * display wifi networks within reach
     */
    echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Erkannte WLAN" ), "</h4></div><div class=\"panel-body\"><script> function scanWifi() { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { document.getElementById(\"", $__[ "connectWLAN" ] [ "ids" ] [ "wifis" ], "\").innerHTML = xmlhttp.responseText; } }; xmlhttp.open(\"GET\",\"include/scanWifi.php\",true); xmlhttp.send(); } scanWifi(); setInterval (function () { scanWifi() }, 2000); </script><div id=\"", $__[ "connectWLAN" ] [ "ids" ] [ "wifis" ], "\">", showInfoMessage ( _ ( "Die Funknetze in der Umgebung werden erkannt ..." ) ), "</div></div></div>";

    /*
     * modal to enter password for encrypted wifis
     */
    echo "<div class=\"modal fade\" id=\"", $__[ "connectWLAN" ][ "ids" ][ "passwordModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><div class=\"alert alert-info\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-lock fa-2x\"></i></div><div class=\"msgText\"><strong>", _ ( "Authentifizierung für WLAN" ), " \"<span id=\"", $__[ "connectWLAN" ][ "ids" ] [ "wifiName" ], "\"></span>\"</strong></div>";
    echo "</div></div><div class=\"modal-body\"><div class=\"form-group\"><div class=\"col-md-2\"><label for=\"", $__[ "connectWLAN" ][ "ids" ][ "password" ], "\" class=\"control-label\">", _ ( "Passwort" ), "</label></div><div class=\"col-md-10\"><div class=\"input-group\"><input type=\"password\" class=\"form-control\" name=\"", $__[ "connectWLAN" ][ "params" ][ "password" ], "\" id=\"", $__[ "connectWLAN" ][ "ids" ][ "password" ], "\" value=\"\"><span class=\"input-group-btn\"><span class=\"btn btn-default\" onmouseover=\"document.getElementById('", $__[ "connectWLAN" ][ "ids" ][ "password" ], "').type='text';\" onmouseout=\"document.getElementById('", $__[ "connectWLAN" ][ "ids" ][ "password" ], "').type='password';\"><i class=\"fa fa-eye\"></i></span>";
    echo "</span></div></div></div></div><div class=\"modal-footer\"><input class=\"btn btn-primary\" type=\"submit\" value=\"", $__[ "connectWLAN" ][ "values" ][ "connect" ], "\" name=\"", $__[ "connectWLAN" ][ "params" ][ "connect" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div></div></form>";
  }
}

include ("include/closeHTML.php");

<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file connectLAN.php
 * 
 * connect a LiMiT1 system to a LAN via ethernet
 * supports manual and dhcp configuration
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
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * show input field for manual network specification
 * 
 * @global type $okay
 * @param type $labelText text to show in label next to input
 * @param string $inputName input / param name
 * @param string $placeHolder placeholder value to show when field is empty
 */
function manInput ( $labelText,
                    $inputName,
                    $placeHolder )
{
  global $okay;

  $inputValue = array_key_exists ( $inputName,
                                   $_POST ) ? $_POST[ $inputName ] : "";

  echo "<div class=\"row form-group\"><label for=\"$inputName\" class=\"col-sm-4 col-md-3 col-lg-2 control-label\">$labelText</label><div class=\"col-sm-8 col-md-9 col-lg-10\"><input type=\"text\" class=\"form-control\" name=\"$inputName\" id=\"$inputName\" placeholder=\"$placeHolder\" value=\"$inputValue\"></div></div>";
  if ( isset ( $okay[ $inputName ] ) && !$okay[ $inputName ] )
  {
    showErrorMessage ( "$labelText: " . htmlentities ( $inputValue ) . _ ( " ist kein gültiger Wert" ) );
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

titleAndHelp ( _ ( "Internetverbindung per LAN" ),
                   _ ( "Ist ein LAN-Kabel angeschlossen, kann die Internetverbindung über das LAN hergestellt werden.<br>Es ist möglich, eine DHCP-basierte automatische Konfiguration vorzunehmen, wenn das Netzwerk dies unterstützt." ) );

/*
 * determine if lan cable is plugged in and connected
 */
$carrier = 0;
if ( file_exists ( "/sys/class/net/$wired_interface/carrier" ) )
{
  $cfile = fopen ( "/sys/class/net/$wired_interface/carrier",
                   r );
  $carrier = trim ( fgets ( $cfile ) );
  fclose ( $cfile );
}

/*
 * no lan
 */
if ( !$carrier )
{
  echo "<div class=\"row\">";
  showErrorMessage ( _ ( "Es ist kein LAN-Kabel angeschlossen." ) );
  echo "</div>";
}

/*
 * active recording 
 */
else if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  showErrorMessage ( _ ( "Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor die Internetverbindung von $my_name geändert werden kann." ) );
}

/*
 * lan connected and no recording ongoing
 */
else
{
  $showForm = true;

  if ( array_key_exists ( $__[ "connectLAN" ] [ "params" ][ "dhcp" ],
                          $_POST ) )
  {
    goOffline ();

    writeOnlineScript ( "/usr/bin/killall dhclient\n/bin/rm $dhclient_pidfile\n/bin/rm $dhclient_leasefile\n/sbin/dhclient -1 -pf $dhclient_pidfile -lf $dhclient_leasefile $wired_interface\n",
                        $wired_interface );

    writeOfflineScript ( "# " . _ ( "LAN (DHCP)" ) . "\n# $wired_interface\n/sbin/ifconfig $wired_interface 0.0.0.0\n",
                                    $wired_interface );

    goOnline ( _ ( "LAN" ) );
    $showForm = false;
  }

  if ( array_key_exists ( $__[ "connectLAN" ] [ "params" ][ "manually" ],
                          $_POST ) )
  {
    $okay[ $__[ "connectLAN" ] [ "params" ][ "address" ] ] = filter_var ( $_POST[ $__[ "connectLAN" ] [ "params" ][ "address" ] ],
                                                                          FILTER_VALIDATE_IP );
    $okay[ $__[ "connectLAN" ] [ "params" ][ "netmask" ] ] = filter_var ( $_POST[ $__[ "connectLAN" ] [ "params" ][ "netmask" ] ],
                                                                          FILTER_VALIDATE_IP );
    $okay[ $__[ "connectLAN" ] [ "params" ][ "gateway" ] ] = filter_var ( $_POST[ $__[ "connectLAN" ] [ "params" ][ "gateway" ] ],
                                                                          FILTER_VALIDATE_IP );
    $okay[ $__[ "connectLAN" ] [ "params" ][ "dns" ] ] = $_POST[ $__[ "connectLAN" ] [ "params" ][ "dns" ] ] == "" || filter_var ( $_POST[ $__[ "connectLAN" ] [ "params" ][ "dns" ] ],
                                                                                                                                   FILTER_VALIDATE_IP );

    if ( $okay[ $__[ "connectLAN" ] [ "params" ][ "address" ] ] && $okay[ $__[ "connectLAN" ] [ "params" ][ "netmask" ] ] && $okay[ $__[ "connectLAN" ] [ "params" ][ "gateway" ] ] && $okay[ $__[ "connectLAN" ] [ "params" ][ "dns" ] ] )
    {
      goOffline ();

      writeOnlineScript ( "/sbin/ifconfig $wired_interface " . $_POST[ $__[ "connectLAN" ] [ "params" ][ "address" ] ] . " netmask " . $_POST[ $__[ "connectLAN" ] [ "params" ][ "netmask" ] ] . "\n/sbin/route add default gw " . $_POST[ $__[ "connectLAN" ] [ "params" ][ "gateway" ] ] . "\n" . ($_POST[ $__[ "connectLAN" ] [ "params" ][ "dns" ] ] != "" ? "/bin/echo nameserver " . $_POST[ $__[ "connectLAN" ] [ "params" ][ "dns" ] ] . " > /etc/resolv.conf\n" : "")
        ,
                          $wired_interface );

      writeOfflineScript ( "# " . _ ( "LAN (manuell)" ) . "\n/sbin/ifconfig $wired_interface 0.0.0.0\n/sbin/ifconfig $wired_interface down\n" . ($_POST[ $__[ "connectLAN" ] [ "params" ][ "dns" ] ] != "" ? "/bin/echo nameserver 127.0.0.1 > /etc/resolv.conf\n" : "")
        ,
                                      $wired_interface );

      goOnline ( _ ( "LAN" ) );
      $showForm = false;
    }
  }

  /*
   * show form
   */
  if ( $showForm )
  {
    echo "<form class=\"form-horizontal\" method=\"post\"><div class=\"row\">";

    if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
    {
      $offlineScriptFH = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                                 "r" );
      $line = fgets ( $offlineScriptFH );
      fclose ( $offlineScriptFH );
      $internet = trim ( substr ( $line,
                                  1 ) );
      showAlertMessage ( _ ( "$my_name ist bereits per $internet mit dem Internet verbunden. Diese bestehende Verbindung wird beendet, bevor eine Internetverbindung per LAN hergestellt wird." ) );
    }

    echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Die Verbindung automatisch herstellen" ), "</h4></div><div class=\"panel-body\"><p>", _ ( "Dies setzt einen DHCP-Server im lokalen Netzwerk voraus." ), "</p><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "connectLAN" ] [ "values" ][ "dhcp" ], "\" name=\"", $__[ "connectLAN" ] [ "params" ][ "dhcp" ], "\"></div></div></div>";
    echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Die Verbindung manuell herstellen" ), "</h4></div><div class=\"panel-body\">";

    manInput ( $__[ "connectLAN" ] [ "values" ][ "address" ],
               $__[ "connectLAN" ] [ "params" ][ "address" ],
               _ ( "z.B. 10.205.3.17" ) );
    manInput ( $__[ "connectLAN" ] [ "values" ][ "netmask" ],
               $__[ "connectLAN" ] [ "params" ][ "netmask" ],
               _ ( "z.B. 255.255.0.0" ) );
    manInput ( $__[ "connectLAN" ] [ "values" ][ "gateway" ],
               $__[ "connectLAN" ] [ "params" ][ "gateway" ],
               _ ( "z.B. 10.205.1.1" ) );
    manInput ( $__[ "connectLAN" ] [ "values" ][ "dns" ],
               $__[ "connectLAN" ] [ "params" ][ "dns" ],
               _ ( "optional, z.B. 10.205.1.2" ) );

    echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "connectLAN" ] [ "values" ][ "manually" ], "\" name=\"", $__[ "connectLAN" ] [ "params" ][ "manually" ], "\"></div></div></form>";
  }
}

require ("include/closeHTML.php");

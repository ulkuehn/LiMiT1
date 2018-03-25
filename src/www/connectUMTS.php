<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file connectUMTS.php
 * 
 * connect a LiMiT1 system to the internet via UMTS
 * supports different types of UMTS sticks and PIN protection
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
$_pin = "PIN";


titleAndHelp ( _ ( "Internetverbindung per UMTS" ),
                   _ ( "Ist ein UMTS-Stick angeschlossen, kann über diesen eine mobile Internetverbindung hergestellt werden." ) );

/*
 * determine if umts adapter is present and its dev file name
 */
foreach ( scandir ( "/sys/class/net" ) as $interface )
{
  if ( $interface != $wired_interface && $interface != $wireless_interface )
  {
    unset ( $udevadmOutput );
    exec ( "/bin/udevadm info -q property /sys/class/net/$interface",
           $udevadmOutput,
           $udevadmReturnValue );
    foreach ( $udevadmOutput as $udevLine )
    {
      if ( $udevLine == "DEVTYPE=wwan" || $udevLine == "ID_USB_DRIVER=cdc_ether" )
      {
        $umtsInterface = $interface;
        /*
         * dial up type
         */
        $umtsDial = ($udevLine == "DEVTYPE=wwan");
      }
    }
  }
}

/*
 * no interface found
 */
if ( $umtsInterface == "" || !file_exists ( "/sys/class/net/$umtsInterface" ) )
{
  echo "<div class=\"row\">";
  showErrorMessage ( _ ( "Es ist kein UMTS-Stick angeschlossen." ) );
  echo "</div>";
}

/*
 * active recording
 */
else if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  echo "<div class=\"row\">";
  showErrorMessage ( _ ( "Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor die Internetverbindung von $my_name geändert werden kann." ) );
  echo "</div>";
}

/*
 * umts stick connected and no recording going on
 */
else
{
  $ok = false;
  $pinIsOk = true;

  if ( isset ( $_POST[ $__[ "connectUMTS" ] [ "params" ][ "connect" ] ] ) )
  {
    $pinIsOk = $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ] == "" || filter_var ( $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ],
                                                                                           FILTER_VALIDATE_INT,
                                                                                           array (
        "options" => array (
          "min_range" => 1000,
          "max_range" => 99999999 ) ) );

    /*
     * dial up sticks need a dialer config
     */
    if ( $umtsDial && $pinIsOk && $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "apn" ] ] != "" )
    {
      goOffline ();

      $wvdialFH = fopen ( $temp_dir . "/" . $__[ "connectUMTS" ][ "values" ][ "wvdialFile" ],
                          "w" );
      fwrite ( $wvdialFH,
               "[Dialer Defaults]\nModem Type = Analog Modem\nBaud = 460800\nNew PPPD = yes\nModem = /dev/ttyUSB0\nISDN = 0\n" );

      if ( $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ] != "" )
      {
        fwrite ( $wvdialFH,
                 "[Dialer pin]\n" );
        fwrite ( $wvdialFH,
                 "Init1 = AT+CPIN=" . $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ] . "\n" );
      }
      fwrite ( $wvdialFH,
               "[Dialer umts]\nCarrier Check = no\nPhone = *99***1#\nPassword = x\nUsername = x\nStupid Mode = 1\nInit4 = AT^NDISDUP=1,1," . $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "apn" ] ] . "\n" );
      fclose ( $wvdialFH );

      writeOnlineScript ( ($_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ] != "" ? "/usr/bin/wvdial --config=" . $temp_dir . "/" . $__[ "connectUMTS" ][ "values" ][ "wvdialFile" ] . " pin\n" : "") .
        "/usr/bin/wvdial --config=" . $temp_dir . "/" . $__[ "connectUMTS" ][ "values" ][ "wvdialFile" ] . " umts &\n",
                          $umts_dial );

      writeOfflineScript ( "# " . _ ( "UMTS" ) . "\n/usr/bin/killall -s HUP wvdial",
                                      $umts_dial );

      goOnline ( _ ( "UMTS" ) );
      $ok = true;
    }

    /*
     * no dial up needed, just PIN
     */
    if ( !$umtsDial && $pinIsOk )
    {
      goOffline ();

      writeOnlineScript ( "/sbin/ifconfig $umtsInterface 192.168.0.100 netmask 255.255.255.0\n/sbin/route add default gw 192.168.0.1\n" . ($_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ] != "" ? "/usr/bin/curl \"http://192.168.0.1/goform/goform_set_cmd_process?isTest=false&goformId=ENTER_PIN&PinNumber=" . $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ] . "\"\n" : "") . "/usr/bin/curl \"http://192.168.0.1/goform/goform_set_cmd_process?isTest=false&notCallback=true&goformId=CONNECT_NETWORK\"\n",
                          $umtsInterface );

      writeOfflineScript ( "# " . _ ( "UMTS" ) . "\n/usr/bin/curl \"http://192.168.0.1/goform/goform_set_cmd_process?isTest=false&notCallback=true&goformId=DISCONNECT_NETWORK\"\n/sbin/route del default gw 192.168.0.1\n/sbin/ifconfig $umtsInterface 0.0.0.0\n/sbin/ifconfig $umtsInterface down\n",
                                      $umtsInterface );

      goOnline ( _ ( "UMTS" ) );
      $ok = true;
    }
  }

  /*
   * either no request or errors in request 
   */
  if ( !$ok )
  {
    echo "<form class=\"form-horizontal\" method=\"post\"><div class=\"row\">";

    /*
     * warn if internet connection exists
     */
    if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
    {
      $offlineFH = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                           "r" );
      $line = fgets ( $offlineFH );
      fclose ( $offlineFH );
      $internet = trim ( substr ( $line,
                                  1 ) );
      showAlertMessage ( _ ( "$my_name ist bereits per $internet mit dem Internet verbunden. Diese bestehende Verbindung wird beendet, bevor eine Internetverbindung per UMTS hergestellt wird." ) );
    }

    echo "<div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Eigenschaften" ), "</h4></div><div class=\"panel-body\">";

    /*
     * dial up sticks need apn setting
     */
    if ( $umtsDial )
    {
      echo "<div class=\"form-group\"><label for=\"", $__[ "connectUMTS" ] [ "params" ] [ "apn" ], "\" class=\"col-sm-3 col-md-2 col-lg-1 control-label\">", _ ( "APN" ), "</label><div class=\"col-sm-9 col-md-10 col-lg-11\"><input type=\"text\" class=\"form-control\" id=\"", $__[ "connectUMTS" ] [ "params" ] [ "apn" ], "\" name=\"", $__[ "connectUMTS" ] [ "params" ] [ "apn" ], "\" value=\"$__umts_apn\"><span class=\"help-block\">", $__umts_apn != "" ? _ ( "Wert aus den Einstellungen wurde übernommen, kann aber überschrieben werden." ) : _ ( "z.B.internet.t-mobile, web.vodafone.de, pinternet.interkom.de" ), "</span></div></div>";
    }

    /*
     * PIN is needed for all sticks
     */
    echo "<div class=\"form-group\"><label for=\"", $__[ "connectUMTS" ] [ "params" ] [ "pin" ], "\" class=\"col-sm-3 col-md-2 col-lg-1 control-label\">", $__[ "connectUMTS" ] [ "values" ][ "pin" ], "</label><div class=\"col-sm-9 col-md-10 col-lg-11\"><div class=\"input-group\"><input type=\"password\" class=\"form-control\" name=\"", $__[ "connectUMTS" ] [ "params" ] [ "pin" ], "\" id=\"", $__[ "connectUMTS" ] [ "params" ] [ "pin" ], "\" value=\"", $_POST[ $__[ "connectUMTS" ] [ "params" ] [ "pin" ] ], "\"><span class=\"input-group-btn\"><span class=\"btn btn-default\" onmouseover=\"document.getElementById('", $__[ "connectUMTS" ] [ "params" ] [ "pin" ], "').type='text';\" onmouseout=\"document.getElementById('", $__[ "connectUMTS" ] [ "params" ] [ "pin" ], "').type='password';\"><i class=\"fa fa-eye\"></i></span></span></div><span class=\"help-block\">", _ ( "Optional. Vier- bis achtstelliger numerischer Code." ), "</span></div></div>";
    if ( !$pinIsOk )
    {
      echo showErrorMessage ( $__[ "connectUMTS" ] [ "values" ][ "pin" ] . ": " . _ ( "Der angegebene Wert ist nicht gültig" ) );
    }

    echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"", _ ( "Verbinden" ), "\" name=\"", $__[ "connectUMTS" ] [ "params" ][ "connect" ], "\"></div></div></div></form>";
  }
}

require ("include/closeHTML.php");

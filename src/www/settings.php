<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file settings.php
 * 
 * used to change system settings
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/connectDB.php");

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * change an item's value in configuration file
 * 
 * @global string $temp_dir path for temp files
 * @param string $itemName name of the item to change
 * @param string $value value to change item to
 */
function changeConfigurationValue ( $itemName,
                                    $value )
{
  global $temp_dir;

  $configFileName = "configuration";
  $baseDir = pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ];

  if ( is_readable ( $baseDir . "/" . $configFileName ) )
  {
    $configFile = fopen ( $baseDir . "/" . $configFileName,
                          "r" );
    $tempFile = fopen ( $temp_dir . "/" . $configFileName,
                        "w" );
    while ( ($line = fgets ( $configFile )) !== false )
    {
      if ( strstr ( $line,
                    $itemName . "=" ) )
      {
        $line = "$itemName=\"$value\"\n";
      }
      fwrite ( $tempFile,
               $line );
    }
    fclose ( $configFile );
    fclose ( $tempFile );
    rename ( $temp_dir . "/" . $configFileName,
             $baseDir . "/" . $configFileName );
  }
}


/**
 * update an item in configuration file if necessary
 * 
 * @param string $itemName name of the item to change
 * @param string $currentValue current value in configuration
 * @param string $newValue new value as html posted
 * @return string updated value
 */
function updateConfigurationValue ( $itemName,
                                    $currentValue,
                                    $newValue )
{
  if ( $currentValue != $newValue )
  {
    changeConfigurationValue ( $itemName,
                               $newValue );
    return $newValue;
  }
  else
  {
    return $currentValue;
  }
}


/**
 * start a new settings section
 * 
 * @param string $htmlId html id of the section
 * @param string $title title of the section
 */
function settingsSection ( $htmlId,
                           $title )
{
  global $__;

  /*
   * uncollapse this section if changes were applied in that section
   */
  $in = array_key_exists ( $htmlId,
                           $_REQUEST ) && $_REQUEST[ $htmlId ] == $__[ "settings" ] [ "values" ] [ "applyButton" ] ? " in" : "";

  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-target=\"#$htmlId\"><h4 class=\"panel-title\">$title</h4></div><div id=\"$htmlId\" class=\"panel-collapse collapse$in\" role=\"tabpanel\"><div class=\"panel-body\">";
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

titleAndHelp ( _ ( "Einstellungen" ),
                   _ ( "Das $my_name-System kann in vielen Aspekten konfiguriert werden. Detaillierte Informationen zu den Einstellungen und ihrer Bedeutung sind in den jeweiligen Abschnitten enthalten." ) );
echo "<div class=\"row\"><div class=\"panel-group\" role=\"tablist\">";


/*
 * 
 * ports settings
 * 
 */

$portsID = "settingsPorts";
$sslPortsID = "settingsPortsSSL";
$tcpPortsID = "settingsPortsTCP";

settingsSection ( $portsID,
                  _ ( "Ports" ) );

/*
 * ports change request
 */
if ( array_key_exists ( $portsID,
                        $_REQUEST ) && $_REQUEST[ $portsID ] == $__[ "settings" ] [ "values" ] [ "applyButton" ] )
{
  /*
   * SSL
   */

  $sslOK = true;
  if ( $_REQUEST[ $sslPortsID ] != "" && !preg_match ( "/^(( +)?[0-9]+(:[0-9]+)?)+ *$/",
                                                       $_REQUEST[ $sslPortsID ] ) )
  {
    $sslOK = false;
  }
  else
  {
    foreach ( preg_split ( "/ +/",
                           $_REQUEST[ $sslPortsID ],
                           -1,
                           PREG_SPLIT_NO_EMPTY ) as $sslPort )
    {
      /*
       * port range
       */
      if ( preg_match ( "/^([0-9]+):([0-9]+)$/",
                        $sslPort,
                        $sslPortRange ) )
      {
        if ( $sslPortRange[ 1 ] < 1 || $sslPortRange[ 2 ] < 1 || $sslPortRange[ 1 ] > 65535 || $sslPortRange[ 2 ] > 65535 || $sslPortRange[ 1 ] >= $sslPortRange[ 2 ] )
        {
          $sslOK = false;
        }
      }
      /*
       * single port
       */
      else if ( $sslPort < 1 || $sslPort > 65535 )
      {
        $sslOK = false;
      }
    }
  }
  /*
   * non valid input
   */
  if ( !$sslOK )
  {
    showErrorMessage ( _ ( "SSL-Ports" ) . ": \"" . htmlentities ( $_REQUEST[ $sslPortsID ] ) . "\"" . _ ( " ist kein gültiger Wert." ) );
  }

  /*
   * TCP (non SSL)
   */

  $tcpOK = true;
  if ( $_REQUEST[ $tcpPortsID ] != "" && !preg_match ( "/^(( +)?[0-9]+(:[0-9]+)?)+ *$/",
                                                       $_REQUEST[ $tcpPortsID ] ) )
  {
    $tcpOK = false;
  }
  else
  {
    foreach ( preg_split ( "/ +/",
                           $_REQUEST[ $tcpPortsID ],
                           -1,
                           PREG_SPLIT_NO_EMPTY ) as $tcpPort )
    {
      /*
       * port range
       */
      if ( preg_match ( "/^([0-9]+):([0-9]+)$/",
                        $tcpPort,
                        $tcpPortRange ) )
      {
        if ( $tcpPortRange[ 1 ] < 1 || $tcpPortRange[ 2 ] < 1 || $tcpPortRange[ 1 ] > 65535 || $tcpPortRange[ 2 ] > 65535 || $tcpPortRange[ 1 ] >= $tcpPortRange[ 2 ] )
        {
          $tcpOK = false;
        }
      }
      /*
       * single port
       */
      else if ( $tcpPort < 1 || $tcpPort > 65535 )
      {
        $tcpOK = false;
      }
    }
  }
  /*
   * non valid input
   */
  if ( !$tcpOK )
  {
    showErrorMessage ( _ ( "Nicht-SSL-Ports" ) . ": \"" . htmlentities ( $_REQUEST[ $tcpPortsID ] ) . "\"" . _ ( " ist kein gültiger Wert." ) );
  }

  /*
   * everything valid
   */
  if ( $sslOK && $tcpOK )
  {
    $__ssl_ports = updateConfigurationValue ( "__ssl_ports",
                                              $__ssl_ports,
                                              $_REQUEST[ $sslPortsID ] );
    $__tcp_ports = updateConfigurationValue ( "__tcp_ports",
                                              $__tcp_ports,
                                              $_REQUEST[ $tcpPortsID ] );
  }
}

/*
 * ports change form
 */
echo _ ( "<p>Hier kann eingestellt werden, welche TCP-Ports bei der Aufzeichnung von Verbindungen berücksichtigt werden. Die Angaben sind optional.</p><p>Bei den SSL-Ports sollten die Ports eingetragen werden, über die SSL-Verbindungen abgewickelt werden. Wird ein Port, über den eine SSL-Verbindung erfolgt, hier nicht angegeben, werden die Daten zwar aufgezeichnet, können aber nicht umgeschlüsselt werden. Wird ein Port eingetragen, über den <em>keine</em> SSL-Verbindung läuft, wird die Verbindung scheitern.</p><p>Wird bei den nicht-SSL Ports etwas eingetragen, wird die Aufzeichnung auf die entsprechenden Ports eingeschränkt. Erfolgt kein Eintrag, werden sämtliche Ports verwendet, die nicht bei den SSL-Ports angegeben sind.</p><p>In die Felder kann jeweils eine Liste von einzelnen Portnummern im Bereich 1-65535 oder von Portbereichen in der Form Port1:Port2 eingetragen werden (z.B. \"121 567:4005 27 40001:40009\").</p>" );

echo "<form class=\"form-horizontal\" method=\"post\">";

echo "<div class=\"form-group\">";
echo "<label for=\"$sslPortsID\" class=\"col-lg-3 control-label\">";
echo _ ( "Ports mit SSL-Verbindungen" );
echo "</label><div class=\"col-lg-9\">";
echo "<input type=\"text\" class=\"form-control\" name=\"$sslPortsID\" id=\"$sslPortsID\" placeholder=\"", _ ( "z.B. 443" ), "\" value=\"$__ssl_ports\">";
echo "</div></div>";

echo "<div class=\"form-group\">";
echo "<label for=\"$tcpPortsID\" class=\"col-lg-3 control-label\">";
echo _ ( "Ports mit Nicht-SSL-Verbindungen" );
echo "</label><div class=\"col-lg-9\">";
echo "<input type=\"text\" class=\"form-control\" name=\"$tcpPortsID\" id=\"$tcpPortsID\" placeholder=\"", _ ( "z.B. 80 (Default: 1:65535 - abzüglich der SSL-Ports)" ), "\" value=\"$__tcp_ports\">";
echo "</div></div>";

echo "<p><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "settings" ] [ "values" ] [ "applyButton" ], "\" name=\"$portsID\"></p></form>";

showInfoMessage ( _ ( "Die Änderungen werden bei der nächsten Aufzeichnung wirksam." ) );
echo "</div></div></div>";


/*
 * 
 * internet routing settings
 * 
 */

$internetID = "settingsInternet";
$internetGroup = "settingsInternetRadioGroup";
$internetGroupValueAlways = "settingsInternet1";
$internetGroupValueRecording = "settingsInternet2";
$internetUMTSProvider = "settingsInternetProvider";

settingsSection ( $internetID,
                  _ ( "Internet" ) );

/*
 * change request
 */
if ( array_key_exists ( $internetID,
                        $_REQUEST ) && $_REQUEST[ $internetID ] == $__[ "settings" ] [ "values" ] [ "applyButton" ] )
{
  $internetOnRecording = $_REQUEST[ $internetGroup ] == $internetGroupValueRecording;

  /*
   * change from recording to always
   */
  if ( $__internet_aufzeichnung && !$internetOnRecording )
  {
    if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
    {
      $cfile = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                       "r" );
      $line = fgets ( $cfile );
      $line = fgets ( $cfile );
      fclose ( $cfile );
      $interface = trim ( substr ( $line,
                                   1 ) );
      shell_exec ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --append POSTROUTING --out-interface $interface -j MASQUERADE" );
      if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
      {
        $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                         "r" );
        $line = fgets ( $sfile );
        $line = fgets ( $sfile );
        fclose ( $sfile );
        $source = trim ( $line );
        shell_exec ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --delete POSTROUTING --source $source --out-interface $interface -j MASQUERADE" );
      }
    }
  }

  /*
   * change from always to recording
   */
  if ( !$__internet_aufzeichnung && $internetOnRecording )
  {
    if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
    {
      $cfile = fopen ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ],
                       "r" );
      $line = fgets ( $cfile );
      $line = fgets ( $cfile );
      fclose ( $cfile );
      $interface = trim ( substr ( $line,
                                   1 ) );
      if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
      {
        $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                         "r" );
        $line = fgets ( $sfile );
        $line = fgets ( $sfile );
        fclose ( $sfile );
        $source = trim ( $line );
        shell_exec ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --append POSTROUTING --source $source --out-interface $interface -j MASQUERADE" );
      }
      shell_exec ( $__[ "startStopRecording" ] [ "values" ] [ "iptablesBinary" ] . " --table nat --delete POSTROUTING --out-interface $interface -j MASQUERADE" );
    }
  }

  $__internet_aufzeichnung = updateConfigurationValue ( "__internet_aufzeichnung",
                                                        $__internet_aufzeichnung,
                                                        $internetOnRecording ? 1 : 0 );

  $__umts_apn = updateConfigurationValue ( "__umts_apn",
                                       $__umts_apn,
                                       $_REQUEST[ $internetUMTSProvider ] );
}

/*
 * form
 */
echo _ ( "<p>Mit diesen Einstellungen wird der Internetzugang von $my_name angepasst.</p>" );

echo "<form class=\"form-horizontal\" method=\"post\">";

/*
 * internet accessibility
 */
echo "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">", _ ( "Durchlässigkeit" ), "</h5></div><div class=\"panel-body\">";
echo _ ( "<p>Mit dieser Option wird festgelegt, wann mit $my_name verbundenen Geräten
  die Verbindung ins Internet ermöglichen soll.</p><p>Dies kann bereits dann erfolgen, wenn die Internetverbindung für $my_name hergestellt wird. Alle angeschlossenen Geräte können dann auf das Internet zugreifen, auch wenn gerade keine Aufzeichnung erfolgt. Wird eine Aufzeichnung von einem Gerät aus gestartet, werden die von diesem Gerät erzeugten Datenströme dann zusätzlich auch aufgezeichnet.</p><p>Alternativ kann die Verbindung ins Internet erst dann erfolgen, wenn eine Aufzeichnung gestartet wird. Die Herstellung der Verbindung von $my_name mit dem Internet führt dann zunächst nicht dazu, dass angeschlossene Geräte selbst Verbindungen ins Internet aufbauen können. Vielmehr wird erst dann, wenn eine Aufzeichnung gestartet wird, für das entsprechende Gerät der Weg ins Internet freigeschaltet und am Ende der Aufzeichnung wieder blockiert.</p><p>Die Einstellung ändert lediglich den Durchgriff von angeschlossenen Geräten auf die Internetverbindung, die $my_name herstellt. $my_name selbst hat unabhängig davon immer dann Internetzugriff, wenn eine Online-Verbindung besteht. Die entsprechenden Werkzeuge (z.B. Whois) können dann in jedem Fall benutzt werden.</p>" );

echo "<div class=\"radio\"><label>";
echo "<input id=\"$internetGroupValueAlways\" type=\"radio\" name=\"$internetGroup\" value=\"$internetGroupValueAlways\"", ($__internet_aufzeichnung == 1 ? "" : " checked"), ">";
echo _ ( "Internetverbindung unabhängig von einer Aufzeichnung herstellen" );
echo "</label></div>";

echo "<div class=\"radio\"><label>";
echo "<input id=\"$internetGroupValueRecording\" type=\"radio\" name=\"$internetGroup\" value=\"$internetGroupValueRecording\"", ($__internet_aufzeichnung == 1 ? " checked" : ""), ">";
echo _ ( "Internetverbindung nur während einer Aufzeichnung herstellen" );
echo "</label></div></div></div>";

/*
 * umts provider
 */
echo "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">", _ ( "UMTS-APN" ), "</h5></div><div class=\"panel-body\">";
echo _ ( "<p>Hier kann der APN (Access Point Name, d.h. der Name des Gateways zwischen dem Mobilfunknetz und dem öffentlichen Internet) des bevorzugten UMTS-Providers eingetragen werden. Der Wert wird bei der Herstellung einer UMTS-Verbindung übernommen und muss dann nicht jedes Mal von Hand eingegeben werden.</p>" );

echo "<label for=\"$internetUMTSProvider\" class=\"col-sm-3 col-md-2 col-lg-1 control-label\">", _ ( "APN" ), "</label>";
echo "<div class=\"col-sm-9 col-md-10 col-lg-11\"><input class=\"form-control\" type=\"text\" id=\"$internetUMTSProvider\" name=\"$internetUMTSProvider\" value=\"$__umts_apn\">";
echo "<p class=\"help-block\">", _ ( "z.B.internet.t-mobile, web.vodafone.de, pinternet.interkom.de" ), "</p></div></div></div>";

/*
 * apply
 */
echo "<p></p><p><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "settings" ] [ "values" ] [ "applyButton" ], "\" name=\"$internetID\"></p></form>";

showInfoMessage ( _ ( "Die Änderungen werden sofort wirksam." ) );
echo "</div></div></div>";


/*
 * 
 * local network settings
 * 
 */
$networkID = "settingsNetwork";
$octet1ID = "settingsIP1";
$octet2ID = "settingsIP2";
$octet3ID = "settingsIP3";

settingsSection ( $networkID,
                  _ ( "Lokales Netzwerk" ) );

/*
 * change request
 */
if ( array_key_exists ( $networkID,
                        $_REQUEST ) && $_REQUEST[ $networkID ] == $__[ "settings" ] [ "values" ] [ "applyButton" ] )
{
  $__ip_ip1 = updateConfigurationValue ( "__ip_ip1",
                                         $__ip_ip1,
                                         $_REQUEST[ $octet1ID ] );
  $__ip_ip2 = updateConfigurationValue ( "__ip_ip2",
                                         $__ip_ip2,
                                         $_REQUEST[ $octet2ID ] );
  $__ip_ip3 = updateConfigurationValue ( "__ip_ip3",
                                         $__ip_ip3,
                                         $_REQUEST[ $octet3ID ] );
}

/*
 * form
 */
echo _ ( "<p>Diese Einstellungen beziehen sich auf das Subnetz, das $my_name über WLAN bereitstellt. Es wird immer ein Klasse-C-Netz gebildet, wobei $my_name über die IP-Adresse x.y.z.1 (z.B. 172.19.200.1) erreichbar ist und die Clients per DHCP Adressen ab x.y.z.2 (z.B. 172.19.200.2) zugewiesen bekommen.</p>" );

/*
 * script to set lower and upper bounds for second octet when first octet is set
 */
echo <<<LIMIT1
    <script>
    function setOctetBounds (firstOctet)
    {
      var domElement = document.getElementById("$octet2ID");
      document.getElementById("$octet3ID").value = 0;
      
      switch (firstOctet)
      {
        case "10":
          domElement.min = 0;
          domElement.max = 255;
           break;
        case "172":
          domElement.min = 16;
          domElement.max = 31;
          break;
        case "192":
          domElement.min = 168;
          domElement.max = 168;
          break;
      }
    }
    </script>
LIMIT1;

echo "<form class=\"form-horizontal\" method=\"post\">";
echo "<div class=\"form-group\">";

echo "<label for=\"$octet1ID\" class=\"col-md-3 col-lg-2 control-label\">", _ ( "Subnetz" ), "</label>";
echo "<div class=\"col-md-3 col-lg-2\">";
echo "<select class=\"form-control text-right\" name=\"$octet1ID\" id=\"$octet1ID\" onchange=\"setOctetBounds(this.value); document.getElementById('$octet2ID').value=document.getElementById('$octet2ID').min;\">";

/*
 * private IP variants 10.x.y.z, 172.x.y.z and 192.168.y.z
 */
foreach ( array (
"10",
"172",
"192" ) as $firstOctet )
{
  echo "<option", $firstOctet == $__ip_ip1 ? " selected" : "", ">$firstOctet</option>";
}
echo "</select></div>";

echo "<div class=\"col-md-3 col-lg-2\">";
echo "<input class=\"form-control text-right\" type=\"number\" name=\"$octet2ID\" id=\"$octet2ID\" value=\"$__ip_ip2\"></div>";

echo "<div class=\"col-md-3 col-lg-2\">";
echo "<input class=\"form-control text-right\" type=\"number\" min=\"0\" max=\"255\" name=\"$octet3ID\" id=\"$octet3ID\" value=\"$__ip_ip3\"></div></div>";

echo "<p><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "settings" ] [ "values" ] [ "applyButton" ], "\" name=\"$networkID\"></p></form>";

/*
 * set bounds for second octet 
 */
echo "<script>setOctetBounds('$__ip_ip1');</script>";

showInfoMessage ( _ ( "Die Änderungen werden erst nach einem Neustart wirksam." ) );
echo "</div></div></div>";


/*
 * 
 * wifi settings
 * 
 */

$wifiID = "settingsWifi";
$ssidID = "settingsWifiSSID";
$passwordID = "settingsWifiPassword";
$channelID = "settingsWifiChannel";

settingsSection ( $wifiID,
                  _ ( "WLAN" ) );

/*
 * change request
 */
if ( array_key_exists ( $wifiID,
                        $_REQUEST ) && $_REQUEST[ $wifiID ] == $__[ "settings" ] [ "values" ] [ "applyButton" ] )
{
  $ok = true;

  /*
   * check SSID
   */
  if ( !preg_match ( "/^[[:graph:]]{1,32}$/",
                     $_REQUEST[ $ssidID ] ) || preg_match ( "/\"/",
                                                            $_REQUEST[ $ssidID ] ) )
  {
    showErrorMessage ( _ ( "SSID" ) . ": \"" . htmlentities ( $_REQUEST[ $ssidID ] ) . "\" " . _ ( "ist kein gültiger Wert." ) );
    $ok = false;
  }

  /*
   * check password
   */
  if ( !preg_match ( "/^[[:graph:]]{8,63}$/",
                     $_REQUEST[ $passwordID ] ) || preg_match ( "/\"/",
                                                                $_REQUEST[ $passwordID ] ) )
  {
    showErrorMessage ( _ ( "Passwort" ) . ": \"" . htmlentities ( $_REQUEST[ $passwordID ] ) . "\" " . _ ( "ist kein gültiger Wert." ) );
    $ok = false;
  }

  if ( $ok )
  {
    $__wlan_ssid = updateConfigurationValue ( "__wlan_ssid",
                                              $__wlan_ssid,
                                              $_REQUEST[ $ssidID ] );
    $__wlan_password = updateConfigurationValue ( "__wlan_password",
                                                  $__wlan_password,
                                                  $_REQUEST[ $passwordID ] );
    $__wlan_channel = updateConfigurationValue ( "__wlan_channel",
                                                 $__wlan_channel,
                                                 $_REQUEST[ $channelID ] );
  }
}

/*
 * form
 */
echo _ ( "<p>Diese Einstellungen beziehen sich auf das WLAN, das $my_name bereitstellt und über das die Prüfgeräte ins Internet geroutet werden. Die Werte sollten so gewählt werden, dass kein Konflikt mit WLAN in der Umgebung entsteht.</p>" );

echo "<form class=\"form-horizontal\" method=\"post\">";

echo "<div class=\"form-group\">";
echo "<label for=\"$ssidID\" class=\"col-sm-3 col-md-2 col-lg-1 control-label\">", _ ( "SSID" ), "</label>";
echo "<div class=\"col-sm-9 col-md-10 col-lg-11\"><input class=\"form-control\" type=\"text\" name=\"$ssidID\" value=\"$__wlan_ssid\">";
echo "<p class=\"help-block\">", _ ( "Keine Leerzeichen; max. Länge 32 Zeichen" ), "</p></div></div>";

echo "<div class=\"form-group\">";
echo "<label for=\"$passwordID\" class=\"col-sm-3 col-md-2 col-lg-1 control-label\">", _ ( "Passwort" ), "</label>";
echo "<div class=\"col-sm-9 col-md-10 col-lg-11\"><input class=\"form-control\" type=\"text\" name=\"$passwordID\" value=\"$__wlan_password\">";
echo "<p class=\"help-block\">", _ ( "Keine Leerzeichen; mindestens 8, höchstens 63 Zeichen" ), "</p></div></div>";

echo "<div class=\"form-group\">";
echo "<label for=\"$channelID\" class=\"col-sm-3 col-md-2 col-lg-1 control-label\">", _ ( "Kanal" ), "</label>";
echo "<div class=\"col-sm-3 col-md-2 col-lg-1\"><input class=\"form-control\" type=\"number\" min=\"1\" max=\"11\" name=\"$channelID\" value=\"$__wlan_channel\"></div></div>";

echo "<p><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "settings" ] [ "values" ] [ "applyButton" ], "\" name=\"$wifiID\"></p></form>";

showInfoMessage ( _ ( "Die Änderungen werden erst nach einem Neustart wirksam." ) );
echo "</div></div></div>";


/*
 * 
 * domain name settings
 * 
 */
$dnsID = "settingsDNS";
$hostnameID = "settingsDNSHostname";
$domainnameID = "settingsDNSDomainname";

settingsSection ( $dnsID,
                  _ ( "DNS" ) );

/*
 * change request
 */
if ( array_key_exists ( $dnsID,
                        $_REQUEST ) && $_REQUEST[ $dnsID ] == $__[ "settings" ] [ "values" ] [ "applyButton" ] )
{
  $ok = true;
  /*
   * check hostname
   */
  if ( !preg_match ( "/^[a-z]{1,63}$/",
                     $_REQUEST[ $hostnameID ] ) )
  {
    showErrorMessage ( _ ( "Hostname" ) . ": \"" . htmlentities ( $_REQUEST[ $hostnameID ] ) . "\" " . _ ( "ist kein gültiger Wert" ) );
    $ok = false;
  }
  /*
   * check domain name
   */
  if ( !preg_match ( "/^[a-z][a-z0-9]{0,62}$/",
                     $_REQUEST[ $domainnameID ] ) )
  {
    showErrorMessage ( _ ( "Domainname" ) . ": \"" . htmlentities ( $_REQUEST[ $domainnameID ] ) . "\" " . _ ( "ist kein gültiger Wert" ) );
    $ok = false;
  }

  if ( $ok )
  {
    $__dns_server_name = updateConfigurationValue ( "__dns_server_name",
                                                    $__dns_server_name,
                                                    $_REQUEST[ $hostnameID ] );
    $__dns_domain_name = updateConfigurationValue ( "__dns_domain_name",
                                                    $__dns_domain_name,
                                                    $_REQUEST[ $domainnameID ] );
  }
}

/*
 * form
 */
echo _ ( "<p>Mit diesen Einstellungen wird festgelegt, unter welchem Netzwerknamen $my_name von den Prüfgeräten aus erreichbar ist. Statt über die IP-Adresse kann $my_name so mit der Adresse <samp>hostname.domainname</samp> adressiert werden. Es sollte darauf geachtet werden, keine Konflikte mit vorhandenen Internet-Domains zu erzeugen, da die enstpechenden Server dann nicht mehr erreichbar wären.</p>" );

echo "<form class=\"form-horizontal\" method=\"post\">";

echo "<div class=\"form-group\"><label for=\"$hostnameID\" class=\"col-sm-4 col-md-3 col-lg-2 control-label\">", _ ( "Hostname" ), "</label>";
echo "<div class=\"col-sm-8 col-md-9 col-lg-10\"><input class=\"form-control\" type=\"text\" name=\"$hostnameID\" value=\"$__dns_server_name\">";
echo "<p class=\"help-block\">", _ ( "Nur Buchstaben; max. Länge 63 Zeichen" ), "</p></div></div>";

echo "<div class=\"form-group\"><label for=\"$domainnameID\" class=\"col-sm-4 col-md-3 col-lg-2 control-label\">", _ ( "Domainname" ), "</label>";
echo "<div class=\"col-sm-8 col-md-9 col-lg-10\"><input class=\"form-control\" type=\"text\" name=\"$domainnameID\" value=\"$__dns_domain_name\">";
echo "<p class=\"help-block\">", _ ( "Buchstaben und Ziffern; keine einzelne Ziffer; max. Länge 63 Zeichen. Existierende TLD wie \"de\", \"org\" sollten vermieden werden" ), "</p></div></div>";

echo "<p><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "settings" ] [ "values" ] [ "applyButton" ], "\" name=\"$dnsID\"></p></form>";

showInfoMessage ( _ ( "Die Änderungen werden erst nach einem Neustart wirksam." ) );
echo "</div></div></div>";


/*
 * 
 * look and feel settings
 * 
 */
$lookAndFeelID = "settingsLaF";
$skinID = "settingsLaFSkin";
$mockupID = "settingsLaFMockup";
$searchBoxID = "settingsLaFSearchBox";
$whoisBoxID = "settingsLaFWhoisBox";
$decodeBoxID = "settingsLaFDecodeBox";
$tabsID = "settingsLaFTabs";
$rowsID = "settingsLaFRows";
$unfoldID = "settingsLaFUnfold";
$unfoldValueLeft = "onclick";
$unfoldValueRight = "oncontextmenu";
$unfoldValueDouble = "ondblclick";
$ledID1 = "settingsLED1";
$ledID2 = "settingsLED2";
$ledOffValue = "0 1";
$ledOnValue = "1 0";
$ledSlowBlinkValue = "2000 2000";
$ledSlowFlashValue = "250 3750";
$ledFastBlinkValue = "250 250";
$ledFastFlashValue = "50 450";
$debugID = "settingsLaFDebug";

settingsSection ( $lookAndFeelID,
                  _ ( "Ansicht und Verhalten" ) );

/*
 * change request
 */
if ( array_key_exists ( $lookAndFeelID,
                        $_REQUEST ) && $_REQUEST[ $lookAndFeelID ] == $__[ "settings" ] [ "values" ] [ "applyButton" ] )
{
  /*
   * table rows
   */
  if ( $_REQUEST[ $rowsID ] == "" )
  {
    $__zeilen = updateConfigurationValue ( "__zeilen",
                                           $__zeilen,
                                           "" );
  }
  else
  {
    $rows = preg_split ( "/[^0-9]+/",
                         $_REQUEST[ $rowsID ],
                         null,
                         PREG_SPLIT_NO_EMPTY );
    if ( !count ( $rows ) || array_search ( "0",
                                            $rows ) )
    {
      showErrorMessage ( _ ( "Zeilenbegrenzung" ) . ": \"" . htmlentities ( $_REQUEST[ $rowsID ] ) . "\" " . _ ( "ist kein gültiger Wert" ) );
    }
    else
    {
      $__zeilen = updateConfigurationValue ( "__zeilen",
                                             $__zeilen,
                                             implode ( " ",
                                                       $rows ) );
    }
  }

  $__klick = updateConfigurationValue ( "__klick",
                                        $__klick,
                                        $_REQUEST[ $unfoldID ] );
  $__skin = updateConfigurationValue ( "__skin",
                                       $__skin,
                                       $_REQUEST[ $skinID ] );
  $__suchbox = updateConfigurationValue ( "__suchbox",
                                          $__suchbox,
                                          isset ( $_REQUEST[ $searchBoxID ] ) ? 1 : 0 );
  $__dekodbox = updateConfigurationValue ( "__dekodbox",
                                           $__dekodbox,
                                           isset ( $_REQUEST[ $decodeBoxID ] ) ? 1 : 0 );
  $__whoisbox = updateConfigurationValue ( "__whoisbox",
                                           $__whoisbox,
                                           isset ( $_REQUEST[ $whoisBoxID ] ) ? 1 : 0 );
  $__usetabs = updateConfigurationValue ( "__usetabs",
                                          $__usetabs,
                                          isset ( $_REQUEST[ $tabsID ] ) ? 1 : 0 );
  $__debug = updateConfigurationValue ( "__debug",
                                        $__debug,
                                        isset ( $_REQUEST[ $debugID ] ) ? 1 : 0 );

  $__led1 = updateConfigurationValue ( "__led1",
                                       $__led1,
                                       $_REQUEST[ $ledID1 ] );
  $__led2 = updateConfigurationValue ( "__led2",
                                       $__led2,
                                       $_REQUEST[ $ledID2 ] );

  flashLED ( explode ( " ",
                       $__led1 )[ 0 ],
                       explode ( " ",
                                 $__led1 )[ 1 ] );

  /*
   * reload page to reflect visual changes
   */
  echo "<script>window.location = \"", $_SERVER[ 'REQUEST_URI' ], "\"</script>";
}

/*
 * form
 */
echo _ ( "<p>Mit diesen Einstellungen kann das Aussehen und Verhalten von $my_name angepasst werden.</p>" );

echo "<form class=\"form-horizontal\" method=\"post\">";

echo "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">", _ ( "Aussehen" ), "</h5></div><div class=\"panel-body\">";

/*
 * skins
 */
echo "<div class=\"form-group\"><label class=\"control-label col-sm-4 col-md-3 col-lg-2\" for=\"$skinID\" control-label>", _ ( "Skin auswählen" ), "</label>";
echo "<div class=\"col-sm-8 col-md-9 col-lg-10\"><select class=\"form-control\" name=\"$skinID\" onchange=\"document.getElementById('$mockupID').src='include/mockup.php?", $__[ "include/mockup" ][ "params" ][ "skin" ], "='+this.value;\">";
echo "<option value=\"\"", ($__skin == "" ? " selected" : ""), ">", _ ( "Standard" ), ($__skin == "" ? (" (" . _ ( "aktuell verwendet" ) . ")") : ""), "</option>";
/*
 * find all installed skins
 */
foreach ( scandir ( $lighttpd_root . "/" . $skin_dir ) as $i => $skinFileName )
{
  /*
   * skin files are like name(.min).css - extract proper name
   */
  if ( preg_match ( "/^([^.]*)(\.min)?\.css$/i",
                    $skinFileName,
                    $skinName ) )
  {
    echo "<option value=\"$skinFileName\"", ($__skin == $skinFileName ? " selected" : ""), ">", $skinName[ 1 ], ($__skin == $skinFileName ? (" (" . _ ( "aktuell verwendet" ) . ")") : ""), "</option>";
  }
}
/*
 * provide for skin preview by a mockup div / iframe which is covered by an invisible protective div to prohibit user interaction with the mockup page's controls
 */
echo "</select></div></div><p><strong>", _ ( "Vorschau" ), "</strong></p>";
echo "<div style=\"width:100%; height:300px; position:relative;\"><div style=\"background:#ffffff; opacity:0.0; width:100%; height:100%; position:absolute; top:0px; left:0px; z-index:10;\"></div>";
echo "<iframe src=\"include/mockup.php?", $__[ "include/mockup" ][ "params" ][ "skin" ], "=$__skin\" id=\"$mockupID\" scrolling=\"no\" style=\"transform:scale(0.9); transform-origin: 0 0; width:110%; height:100%; position:absolute; top:0px; left:0px; overflow:hidden;\"></iframe></div></div></div>";

/*
 * utility boxes
 */
echo "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">", _ ( "Eingabefelder in der Menüleiste" ), "</h5></div><div class=\"panel-body\"><div class=\"form-group\">";

echo "<div class=\"checkbox col-md-4\"><label><input type=\"checkbox\" name=\"$searchBoxID\"", ($__suchbox ? " checked" : ""), ">", _ ( "Suche anzeigen" ), "</label></div>";

echo "<div class=\"checkbox col-md-4\"><label><input type=\"checkbox\" name=\"$decodeBoxID\"", ($__dekodbox ? " checked" : ""), ">", _ ( "Dekodieren anzeigen" ), "</label></div>";

echo "<div class=\"checkbox col-md-4\"><label><input type=\"checkbox\" name=\"$whoisBoxID\"", ($__whoisbox ? " checked" : ""), ">", _ ( "Whois anzeigen" ), "</label></div></div>";

echo "<div class=\"form-group\"><div class=\"checkbox col-md-12\"><label><input type=\"checkbox\" name=\"$tabsID\"", ($__usetabs ? " checked" : ""), ">", _ ( "Suche, Dekodieren und Whois in eigenen Browser-Tabs anzeigen" ), "</label></div></div></div></div>";

/*
 * tables
 */
echo "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">", _ ( "Tabellen" ), "</h5></div><div class=\"panel-body\">";

echo "<div class=\"form-group\"><label class=\"control-label col-md-4 col-lg-2\">", _ ( "Zeilenbegrenzung" ), "</label>";
echo "<div class=\"col-md-8 col-lg-10\"><input type=\"text\" class=\"form-control\" name=\"$rowsID\" value=\"$__zeilen\">";
echo "<span class=\"help-block\">", _ ( "Da die meisten Tabellen aus einer großen Anzahl von Zeilen bestehen, ist es sinnvoll, diese in kleineren Blöcken (Seiten) anzuzeigen. Hier können die gewünschten Seitengrößen angegeben werden. Die erste Zahl bestimmt dabei die Standardeinstellung. Die Angabe \"20 5 50 30\" z.B. bewirkt, dass bei einer entsprechend großen Tabelle pro Seite nur 20 Zeilen angezeigt werden und dies auf 5, 30 oder 50 Zeilen umgestellt werden kann (sowie auf alle Zeilen). Wird nichts angegeben, werden Tabellen stets mit allen Zeilen angezeigt." ), "</span></div></div>";

echo "<div class=\"form-group\"><label class=\"control-label col-md-4 col-lg-2\">", _ ( "Aktion, um Tabellen zu falten oder zu entfalten" ), "</label>";
echo "<div class=\"col-md-8 col-lg-10\"><select class=\"form-control\" name=\"$unfoldID\">";
echo "<option value=\"$unfoldValueLeft\"", ($__klick == $unfoldValueLeft ? " selected" : ""), ">", _ ( "einfacher Klick" ), "</option>";
echo "<option value=\"$unfoldValueDouble\"", ($__klick == $unfoldValueDouble ? " selected" : ""), ">", _ ( "Doppelklick" ), "</option>";
echo "<option value=\"$unfoldValueRight\"", ($__klick == $unfoldValueRight ? " selected" : ""), ">", _ ( "Rechtsklick" ), "</option>";
echo "</select>";
echo "<span class=\"help-block\">", _ ( "Tabellen werden standardmäßig kompakt dargestellt, indem lange Werte abgeschnitten werden. Um den gesamten Inhalt zu sehen, kann die entsprechende Tabellenzelle angeklickt werden. Welche Art von Klick dafür nötig ist, kann hier eingestellt werden." ), "</span></div></div><div></div></div></div>";

/*
 * system (green) LED
 */
echo "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">", _ ( "LED" ), "</h5></div><div class=\"panel-body\">";
echo "<p>Hier kann eingestellt werden, ob und wie die eingebaute grüne LED in den beiden Betriebszuständen (laufende Aufzeichnung / keine Aufzeichnung) leuchten soll.</p>";
echo "<label class=\"col-md-4 col-lg-2 control-label\">", _ ( "keine Aufzeichnung" ), "</label>";
echo "<div class=\"col-md-8 col-lg-3\">";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID1\" value=\"$ledOffValue\"", ($__led1 == $ledOffValue ? " checked" : ""), "> ", _ ( "aus" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID1\" value=\"$ledOnValue\"", ($__led1 == $ledOnValue ? " checked" : ""), "> ", _ ( "an" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID1\" value=\"$ledSlowBlinkValue\"", ($__led1 == $ledSlowBlinkValue ? " checked" : ""), "> ", _ ( "langsames Blinken" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID1\" value=\"$ledSlowFlashValue\"", ($__led1 == $ledSlowFlashValue ? " checked" : ""), "> ", _ ( "langsames Blitzen" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID1\" value=\"$ledFastBlinkValue\"", ($__led1 == $ledFastBlinkValue ? " checked" : ""), "> ", _ ( "schnelles Blinken" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID1\" value=\"$ledFastFlashValue\"", ($__led1 == $ledFastFlashValue ? " checked" : ""), "> ", _ ( "schnelles Blitzen" ), "</label></div>";
echo "</div>";

echo "<label class=\"col-md-4 col-lg-2 control-label\">", _ ( "laufende Aufzeichnung" ), "</label>";
echo "<div class=\"col-md-8 col-lg-3\">";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID2\" value=\"$ledOffValue\"", ($__led2 == $ledOffValue ? " checked" : ""), "> ", _ ( "aus" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID2\" value=\"$ledOnValue\"", ($__led2 == $ledOnValue ? " checked" : ""), "> ", _ ( "an" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID2\" value=\"$ledSlowBlinkValue\"", ($__led2 == $ledSlowBlinkValue ? " checked" : ""), "> ", _ ( "langsames Blinken" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID2\" value=\"$ledSlowFlashValue\"", ($__led2 == $ledSlowFlashValue ? " checked" : ""), "> ", _ ( "langsames Blitzen" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID2\" value=\"$ledFastBlinkValue\"", ($__led2 == $ledFastBlinkValue ? " checked" : ""), "> ", _ ( "schnelles Blinken" ), "</label></div>";
echo "<div class=\"radio\"><label><input type=\"radio\" name=\"$ledID2\" value=\"$ledFastFlashValue\"", ($__led2 == $ledFastFlashValue ? " checked" : ""), "> ", _ ( "schnelles Blitzen" ), "</label></div>";
echo "</div>";

echo "</div></div>";

/*
 * misc
 */
echo "<div class=\"panel panel-info\"><div class=\"panel-heading\"><h5 class=\"panel-title\">", _ ( "Sonstiges" ), "</h5></div><div class=\"panel-body\"><div class=\"checkbox\"><label><input type=\"checkbox\" name=\"$debugID\"", ($__debug ? " checked" : ""), ">", _ ( "Debug-Informationen anzeigen" ), "</label></div></div></div>";

/*
 * apply
 */
echo "<p></p><p><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "settings" ] [ "values" ] [ "applyButton" ], "\" name=\"$lookAndFeelID\"></p></form>";

showInfoMessage ( _ ( "Die Änderungen werden sofort wirksam." ) );
echo "</div></div></div>";

echo "</div></div></div></div>";
include ("include/closeHTML.php");

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: connectLAN.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to connect a LiMiT1 system to a LAN via ethernet
//              supports manual and dhcp configuration
//
//==============================================================================
//==============================================================================

require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");

// Beschriftungen der Eingabefelder
$_automatisch = "Automatisch";
$_manuell = "Manuell";
$_ipaddress = "IP-Adresse";
$_netmask = "Netzmaske";
$_gateway = "Gateway";
$_dnsserver = "DNS-Server"; 


// Eingabefeld für manuelle Konfiguration
// $text: Beschriftung des Eingabefelds
// $name: Name des Eingabefelds
// $placeholder: Hinweistext
function manInput ($text, $name, $placeholder)
{
  global $okay;
  
  $val = array_key_exists ($name, $_POST)? $_POST[$name] : "";
  
  echo <<<LIMIT1
  <div class="row form-group">
    <label for="$name" class="col-sm-4 col-md-3 col-lg-2 control-label">$text</label>
    <div class="col-sm-8 col-md-9 col-lg-10">
      <input type="text" class="form-control" name="$name" id="$name" placeholder="$placeholder" value="$val">
    </div>
  </div>  
LIMIT1;
  if (isset($okay[$name]) && !$okay[$name])
  {
    errorMsg ("$text: ".htmlentities($val)." ist kein gültiger Wert");
  }
}


titleAndHelp ("Internetverbindung per LAN", <<<LIMIT1
Ist ein LAN-Kabel angeschlossen, kann die Internetverbindung über das LAN hergestellt werden.
<br>
Es ist möglich, eine DHCP-basierte automatische Konfiguration vorzunehmen, wenn das Netzwerk dies unterstützt.
LIMIT1
);

$carrier = 0;
if (file_exists ("/sys/class/net/$wired_interface/carrier"))
{
  $cfile = fopen ("/sys/class/net/$wired_interface/carrier", r);
  $carrier = trim (fgets ($cfile));
  fclose ($cfile);
}

if (!$carrier)
{
  echo "<div class=\"row\">";
  errorMsg ("Es ist kein LAN-Kabel angeschlossen.");
  echo "</div>";
}

else if (file_exists ($session_file))
{
  errorMsg ("Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor die Internetverbindung von $my_name geändert werden kann.");
}
else
{
  $ok = 0;

  if (array_key_exists("automatisch",$_POST))
  {
    offline();
    
    onlineScript (<<<LIMIT1
/usr/bin/killall dhclient
/bin/rm $dhclient_pidfile
/bin/rm $dhclient_leasefile
/sbin/dhclient -1 -pf $dhclient_pidfile -lf $dhclient_leasefile $wired_interface

LIMIT1
                 , $wired_interface );
                 
    offlineScript (<<<LIMIT1
# LAN (DHCP)
# $wired_interface
/sbin/ifconfig $wired_interface 0.0.0.0

LIMIT1
                 , $wired_interface );
    
    online ("LAN");
    $ok = 1;
  }

  if (array_key_exists("manuell", $_POST))
  {
    $okay["ipaddress"] = filter_var($_POST["ipaddress"], FILTER_VALIDATE_IP);
    $okay["netmask"] = filter_var($_POST["netmask"], FILTER_VALIDATE_IP);
    $okay["gateway"] = filter_var($_POST["gateway"], FILTER_VALIDATE_IP);
    $okay["dnsserver"] = $_POST["dnsserver"] == "" || filter_var($_POST["dnsserver"], FILTER_VALIDATE_IP);
    
    if ($okay["ipaddress"] && $okay["netmask"] && $okay["gateway"] && $okay["dnsserver"])
    {
      offline ();
      
      onlineScript (<<<LIMIT1
/sbin/ifconfig $wired_interface {$_POST["ipaddress"]} netmask {$_POST["netmask"]}
/sbin/route add default gw {$_POST["gateway"]}

LIMIT1
                    . ($_POST["dnsserver"] != ""? "/bin/echo nameserver {$_POST["dnsserver"]} > /etc/resolv.conf\n" : "")
                    , $wired_interface );
      
      offlineScript (<<<LIMIT1
# LAN (manuell)
/sbin/ifconfig $wired_interface 0.0.0.0
/sbin/ifconfig $wired_interface down

LIMIT1
                    . ($_POST["dnsserver"] != ""? "/bin/echo nameserver 127.0.0.1 > /etc/resolv.conf\n" : "")
                    , $wired_interface );
      
      online ("LAN");
      $ok = 1;
    }
  }

  if (!$ok)
  {
    echo <<<LIMIT1

    <form class="form-horizontal" method="post">
      <div class="row">
LIMIT1;

    if (is_readable($offline_script))
    {
      $cfile = fopen ($offline_script, "r");
      $line = fgets ($cfile); 
      fclose ($cfile);
      $internet = trim (substr ($line, 1));
      alertMsg ("$my_name ist bereits per $internet mit dem Internet verbunden. Diese bestehende Verbindung wird beendet, bevor eine Internetverbindung per LAN hergestellt wird.");
    }

    echo <<<LIMIT1
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">Die Verbindung automatisch herstellen</h4>
          </div>
          <div class="panel-body">
            <p>Dies setzt einen DHCP-Server im lokalen Netzwerk voraus.</p>
            <input type="submit" class="btn btn-primary" value="$_automatisch" name="automatisch">
          </div>
        </div>
      </div>

      <div class="row">
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">Die Verbindung manuell herstellen</h4>
          </div>
          <div class="panel-body">

LIMIT1;

    manInput ($_ipaddress, "ipaddress", "z.B. 10.205.3.17");
    manInput ($_netmask, "netmask", "z.B. 255.255.0.0");
    manInput ($_gateway, "gateway", "z.B. 10.205.1.1");
    manInput ($_dnsserver, "dnsserver", "optional, z.B. 10.205.1.2");

    echo <<<LIMIT1
          <input type="submit" class="btn btn-primary" value="$_manuell" name="manuell">
        </div>
      </div>
    </form>

LIMIT1;
  }
}

require ("include/htmlend.php");

?>

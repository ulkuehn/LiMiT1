<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: connectWLAN.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to connect a LiMiT1 system to a wifi network
//              supports manual ssid specification and scanning of 
//              visible networks 
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
$_ssid = "SSID";
$_pass = "Passwort";
$_verbinden = "Verbinden";


titelHilfe ("Internetverbindung per WLAN", <<<LIMIT1
Ist ein (weiterer) WLAN-Adapter vorhanden, kann die Internetverbindung über ein Funknetzwerk hergestellt werden.
<br>
Es kann sowohl ein erkanntes WLAN ausgewählt als auch eine SSID manuell angegeben werden.
LIMIT1
);

# wifi adapter present?
foreach (scandir("/sys/class/net") as $interface)
{
  if ($interface != $wired_interface && $interface != $wireless_interface)
  {
    unset ($udev);
    exec ("/bin/udevadm info -q property /sys/class/net/$interface", $udev, $ret);
    foreach ($udev as $info)
    {
      if ($info == "DEVTYPE=wlan")
      {
        $wireless_internet = $interface;
      }
    }
  }
}
  
if ($wireless_internet == "")
{
  echo "<div class=\"row\">";
  errorMsg ("Es ist kein WLAN-Stick angeschlossen.");
  echo "</div>";
}

else if (file_exists ($session_file))
{
  echo "<div class=\"row\">";
  errorMsg ("Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor die Internetverbindung von $my_name geändert werden kann.");
  echo "</div>";
}

else
{
  if (isset($_POST["verbinden"]))
  {
    $connectTo = isset($_POST["hssid"]) && $_POST["hssid"] != ""? $_POST["hssid"] : $_POST["ssid"];
    $pass = $_POST["pass"];

    if ($connectTo != "")
    {
      offline ();

      onlineScript (<<<LIMIT1
/usr/bin/killall dhclient
/bin/rm $dhclient_pidfile
/bin/rm $dhclient_leasefile

LIMIT1
                    . ($pass == ""? "/sbin/iwconfig $wireless_internet essid \"$connectTo\"" : "/usr/bin/killall wpa_supplicant\n/sbin/wpa_supplicant -B -D nl80211 -i $wireless_internet -c $wpa_supplicant_configfile")
                    . "\n/sbin/dhclient -v -pf $dhclient_pidfile -lf $dhclient_leasefile $wireless_internet"
                    , $wireless_internet );
              
      if ($pass != "")
      {
        $wpasup = fopen ($wpa_supplicant_configfile, "w");
        fwrite ($wpasup, <<<LIMIT1
ctrl_interface=/var/run/wpa_supplicant
ctrl_interface_group=0

LIMIT1
                );
        exec ("/usr/bin/wpa_passphrase \"$connectTo\" \"$pass\"", $lines, $erg);
        if ($erg == 0)
        {
          foreach ($lines as $line)
          {
            fwrite ($wpasup, "$line\n");
          }
        }
        fclose ($wpasup);
      }
      
      offlineScript (<<<LIMIT1
# WLAN ($connectTo)
# $wireless_internet
/usr/bin/killall wpa_supplicant
/sbin/ifconfig $wireless_internet 0.0.0.0

LIMIT1
                    , $wireless_internet );
      
      online ("WLAN");
    }
  }

  if ($connectTo == "")
  {
    echo <<<LIMIT1
    <form class="form-horizontal" method="post">
      <input type="hidden" id="hssid" name="hssid" value="">
      <div class="row">
LIMIT1;

    if (is_readable($offline_script))
    {
      $cfile = fopen ($offline_script, "r");
      $line = fgets ($cfile); 
      fclose ($cfile);
      $internet = trim (substr ($line, 1));
      alertMsg ("$my_name ist bereits per $internet mit dem Internet verbunden. Diese bestehende Verbindung wird beendet, bevor eine Internetverbindung per WLAN hergestellt wird.");
    }
    
    echo <<<LIMIT1
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">SSID manuell angeben</h4>
          </div>
          <div class="panel-body">
            <div class="form-group">
              <label for="ssid" class="control-label col-sm-2 col-md-2 col-lg-1">$_ssid</label>
              <div class="col-sm-6 col-md-7 col-lg-9">
                <input type="text" class="form-control" name="ssid" id="ssid" value="{$_POST["ssid"]}">
              </div>
LIMIT1;

    if (isset($_POST["verbinden"]) && isset($_POST["hssid"]) && $_POST["hssid"] == "" && isset($_POST["ssid"]) && $_POST["ssid"] == "")
    {
      errorMsg ("$_ssid: Bitte einen Wert eingeben");
    }

    echo <<<LIMIT1
              <div class="col-sm-4 col-md-3 col-lg-2">
                <button class="btn btn-sm btn-success" type="submit" value="$_verbinden" name="verbinden" title="mit offenem WLAN verbinden"><i class="fa fa-lg fa-unlock"></i></button>
                <a class="btn btn-sm btn-danger" href="#passwordModal" data-toggle="modal" onclick="document.getElementById('pass').value=''; document.getElementById('wifiname').innerHTML=document.getElementById('ssid').value;" title="Passwort eingeben und mit verschlüsseltem WLAN verbinden"><i class="fa fa-lg fa-lock"></i></a>
              </div>
            </div>
          </div>
        </div>

        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">Erkannte WLAN</h4>
          </div>
          <div class="panel-body">
            <script>
              function wifi()
              {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() 
                {
                  if (xmlhttp.readyState==4 && xmlhttp.status==200) 
                  {
                    document.getElementById("wifi").innerHTML = xmlhttp.responseText;
                  }
                }
                
                var d = new Date();
                var unixtime = Math.floor(d.getTime() / 1000);
                xmlhttp.open("GET","include/wifi.php?ts="+unixtime,true);
                xmlhttp.send();
              }

              wifi();
              setInterval (function () {wifi()}, 2000);            
            </script>
            <div id="wifi">
LIMIT1;
    echo infoMsg ("Die Funknetze in der Umgebung werden erkannt ...");
    echo <<<LIMIT1
            </div>
          </div>
        </div>

        <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <div class="alert alert-info" role="alert">
                  <div class="msgIcon"><i class="fa fa-lock fa-2x"></i></div>
                  <div class="msgText"><strong>Authentifizierung für WLAN "<span id="wifiname"></span>"</strong></div>
                </div>
              </div>
              <div class="modal-body">
                <div class="form-group">
                  <div class="col-md-2">
                    <label for="pass" class="control-label">$_pass</label>
                  </div>
                  <div class="col-md-10">
                    <div class="input-group">
                      <input type="password" class="form-control" name="pass" id="pass" value="">
                      <span class="input-group-btn">
                        <span class="btn btn-default" onmouseover="document.getElementById('pass').type='text';" onmouseout="document.getElementById('pass').type='password';"><i class="fa fa-eye"></i></span>
                      </span>
                    </div>
                  </div>
                </div>             
              </div>
              <div class="modal-footer">
                <input class="btn btn-primary" type="submit" value="$_verbinden" name="verbinden">
                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>

LIMIT1;
  }
}

require ("include/htmlend.php");

?>

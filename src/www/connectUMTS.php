<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: connectUMTS.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to connect a LiMiT1 system to the internet via UMTS
//              supports different types of UMTS sticks ans pin protection
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
$_verbinden = "Verbinden";
$_provider = "Provider";
$_pin = "PIN";


titelHilfe ("Internetverbindung per UMTS", <<<LIMIT1
Ist ein UMTS-Stick angeschlossen, kann über diesen eine mobile Internetverbindung hergestellt werden.
LIMIT1
);

# umts adapter present?
foreach (scandir("/sys/class/net") as $interface)
{
  if ($interface != $wired_interface && $interface != $wireless_interface)
  {
    unset ($udev);
    exec ("/bin/udevadm info -q property /sys/class/net/$interface", $udev, $ret);
    foreach ($udev as $info)
    {
      if ($info == "DEVTYPE=wwan" || $info == "ID_USB_DRIVER=cdc_ether")
      {
        $umts_interface = $interface;
        $dial = ($info == "DEVTYPE=wwan");
        #echo "<p>info = $info , if = $umts_interface , dial = $dial</p>";
      }
    }
  }
}

if ($umts_interface == "" || !file_exists ("/sys/class/net/$umts_interface"))
{
  echo "<div class=\"row\">";
  errorMsg ("Es ist kein UMTS-Stick angeschlossen.");
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
  $ok = 0;
  $pinOk = true;
  
  if (isset($_POST["verbinden"]))
  {
    $pinOk = $_POST["pin"] == "" || filter_var($_POST["pin"], FILTER_VALIDATE_INT, array("options" =>array('min_range'=>1000,'max_range'=>99999999)));

    if ($dial && $pinOk && $_POST["provider"] != "")
    {
      offline ();
      
      $wvd = fopen ($wvdial_configfile, "w");
      fwrite ($wvd, <<<LIMIT1
[Dialer Defaults]
Modem Type = Analog Modem
Baud = 460800
New PPPD = yes
Modem = /dev/ttyUSB0
ISDN = 0

LIMIT1
            );
      if ($_POST["pin"] != "")
      {
        fwrite ($wvd, "[Dialer pin]\n");
        fwrite ($wvd, "Init1 = AT+CPIN=".$_POST["pin"]."\n");
      }
      fwrite ($wvd, <<<LIMIT1
    [Dialer umts]
    Carrier Check = no
    Phone = *99***1#
    Password = x
    Username = x
    Stupid Mode = 1
    Init4 = AT^NDISDUP=1,1,{$_POST["provider"]}
LIMIT1
            );
      fclose ($wvd);
      
      onlineScript (($_POST["pin"] != ""? "/usr/bin/wvdial --config=$wvdial_configfile pin\n" : "") .
                    "/usr/bin/wvdial --config=$wvdial_configfile umts &\n", $umts_dial);
                    
      offlineScript ("/usr/bin/killall -s HUP wvdial", $umts_dial);
      
      online ("UMTS");
      $ok = 1;
    }    
    
    if (!$dial && $pinOk)
    {
      offline ();
      
      onlineScript (<<<LIMIT1
/sbin/ifconfig $umts_interface 192.168.0.100 netmask 255.255.255.0
/sbin/route add default gw 192.168.0.1

LIMIT1
                   . ($_POST["pin"] != ""? "/usr/bin/curl \"http://192.168.0.1/goform/goform_set_cmd_process?isTest=false&goformId=ENTER_PIN&PinNumber=".$_POST["pin"]."\"\n" : "")
                   . "/usr/bin/curl \"http://192.168.0.1/goform/goform_set_cmd_process?isTest=false&notCallback=true&goformId=CONNECT_NETWORK\"\n"
                   , $umts_interface);

      offlineScript (<<<LIMIT1
# UMTS
/usr/bin/curl "http://192.168.0.1/goform/goform_set_cmd_process?isTest=false&notCallback=true&goformId=DISCONNECT_NETWORK"
/sbin/route del default gw 192.168.0.1
/sbin/ifconfig $umts_interface 0.0.0.0
/sbin/ifconfig $umts_interface down

LIMIT1
                    , $umts_interface);
                    
      online ("UMTS");
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
      alertMsg ("$my_name ist bereits per $internet mit dem Internet verbunden. Diese bestehende Verbindung wird beendet, bevor eine Internetverbindung per UMTS hergestellt wird.");
    }

    echo <<<LIMIT1
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">Eigenschaften</h4>
          </div>
          <div class="panel-body">
LIMIT1;

    if ($dial)
    {
      echo <<<LIMIT1
            <div class="form-group">
              <label for="provider" class="col-sm-3 col-md-2 col-lg-1 control-label">Provider</label>
              <div class="col-sm-9 col-md-10 col-lg-11">
                <select class="form-control" name="provider">
LIMIT1;
      foreach (explode (";", $__umts) as $provider)
      {
        $pf = explode (":", $provider);
        echo "<option value=\"",$pf[1],"\"",($_POST["provider"]==$pf[1]? " selected":""),">",$pf[0],"</option>";
      }
      echo "</select></div></div>";
    }
  
    echo <<<LIMIT1
            <div class="form-group">
              <label for="pin" class="col-sm-3 col-md-2 col-lg-1 control-label">PIN</label>
              <div class="col-sm-9 col-md-10 col-lg-11">
                <div class="input-group">
                  <input type="password" class="form-control" name="pin" id="pin" value="{$_POST["pin"]}">
                  <span class="input-group-btn">
                    <span class="btn btn-default" onmouseover="document.getElementById('pin').type='text';" onmouseout="document.getElementById('pin').type='password';"><i class="fa fa-eye"></i></span>
                  </span>
                </div>
                <span class="help-block">Optional. Vier- bis achtstelliger numerischer Code.</span>
              </div>
            </div>
LIMIT1;
    if (!$pinOk)
    {
      echo errorMsg ("$_pin: Der angegebene Wert ist nicht gültig");
    }
    echo <<<LIMIT1
            <input type="submit" class="btn btn-primary" value="$_verbinden" name="verbinden">
          </div>
        </div>
      </div>
    </form>
LIMIT1;
  }
}

require ("include/htmlend.php");

?>

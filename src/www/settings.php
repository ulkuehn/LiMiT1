<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: settings.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to change the settings of the LiMiT1 system
//
//==============================================================================
//==============================================================================


$_speichern = "Ändern";

require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
include ("include/htmlstart.php");
include ("include/topmenu.php");


function update ($name,$global,$posted)
{
  if ($global != $posted)
  {
    change ($name,$posted);
    return $posted;
  }
  else
  {
    return $global;
  }
}

function change ($name,$wert)
{
  global $temp_dir;
  
  $_configuration = "configuration";
  $basedir = pathinfo($_SERVER["DOCUMENT_ROOT"])['dirname'];

  if (is_readable($basedir."/".$_configuration))
  {
    $cfile = fopen ($basedir."/".$_configuration, "r");
    $tfile = fopen ($temp_dir."/".$_configuration, "w");
    while (($line = fgets ($cfile)) !== false) 
    {
      if (strstr ($line,$name."="))
      {
        $line = "$name=\"$wert\"\n";
      }
      fwrite ($tfile,$line);
    }
    fclose ($cfile);
    fclose ($tfile);
    rename ($temp_dir."/".$_configuration, $basedir."/".$_configuration);
  }
}


function section ($id, $titel)
{
  global $_speichern;

  $in = array_key_exists($id,$_REQUEST) && $_REQUEST[$id]==$_speichern? " in":"";
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#$id">
        <h4 class="panel-title">
          $titel
        </h4>
      </div>
      <div id="$id" class="panel-collapse collapse$in" role="tabpanel">
        <div class="panel-body">
LIMIT1;
}


titleAndHelp ("Einstellungen", <<<LIMIT1
Das $my_name-System kann in vielen Aspekten konfiguriert werden.
Detaillierte Informationen zu den Einstellungen und ihrer Bedeutung sind in den jeweiligen
Abschnitten enthalten.
LIMIT1
);

echo <<<LIMIT1
<div class="row">
  <div class="panel-group" id="about" role="tablist">
LIMIT1;



// Ports

section ("ports", "Ports");

if (array_key_exists("ports",$_REQUEST) && $_REQUEST["ports"]==$_speichern)
{
  $ok1 = 1;
  if (!preg_match ("/^(( +)?[0-9]+(:[0-9]+)?)+ *$/",$_REQUEST["sslports"]))
  {
    $ok1 = 0;
  }
  else
  {
    foreach (preg_split ("/ +/",$_REQUEST["sslports"]) as $p)
    {
      if (preg_match ("/^([0-9]+):([0-9]+)$/", $p, $pp))
      {
        if ($pp[1]<1 || $pp[2]<1 || $pp[1]>65535 || $pp[2]>65535 || $pp[1]>=$pp[2])
        {
          $ok1 = 0;
        }
      }
      else if ($p<1 || $p>65535)
      {
        $ok1 = 0;
      }
    }
  }
  if (!$ok1)
  {
    errorMsg ("SSL-Ports: \"".htmlentities($_REQUEST["sslports"])."\" ist kein gültiger Wert.");
  }

  $ok2 = 1;
  if ($_REQUEST["tcpports"]=="")
  {
  }
  else if (!preg_match ("/^(( +)?[0-9]+(:[0-9]+)?)+ *$/",$_REQUEST["tcpports"]))
  {
    $ok2 = 0;
  }
  else
  {
    foreach (preg_split ("/ +/",$_REQUEST["tcpports"]) as $p)
    {
      if (preg_match ("/^([0-9]+):([0-9]+)$/", $p, $pp))
      {
        if ($pp[1]<1 || $pp[2]<1 || $pp[1]>65535 || $pp[2]>65535 || $pp[1]>=$pp[2])
        {
          $ok2 = 0;
        }
      }
      else if ($p<1 || $p>65535)
      {
        $ok2 = 0;
      }
    }
  }
  if (!$ok2)
  {
    errorMsg ("Nicht-SSL-Ports: \"".htmlentities($_REQUEST["tcpports"])."\" ist kein gültiger Wert.");
  }

  if ($ok1 && $ok2)
  {
    $__ssl_ports = $_REQUEST["sslports"];
    change ("__ssl_ports",$__ssl_ports);
    $__tcp_ports = $_REQUEST["tcpports"];
    change ("__tcp_ports",$__tcp_ports);
  }
}

echo <<<LIMIT1
  <p>Hier kann eingestellt werden, welche TCP-Ports bei der Aufzeichnung von Verbindungen berücksichtigt werden.<br>
  Bei den SSL-Ports sollten die Ports eingetragen werden, über die SSL-Verbindungen abgewickelt werden.
  Wird ein Port, über den eine SSL-Verbindung erfolgt, hier nicht angegeben, werden die Daten zwar aufgezeichnet,
  können aber nicht entschlüsselt werden. Wird ein Port eingetragen, über den <em>keine</em> SSL-Verbindung läuft, wird die Verbindung scheitern.<br>
  Der Eintrag bei den Nicht-SSL-Ports ist optional. Wird er weggelassen, werden sämtliche Ports verwendet, die nicht bei
  den SSL-Ports angegeben sind. Wird etwas eingetragen, wird die Aufzeichnung auf die entsprechenden Ports eingeschränkt.
  Ein Port, der bei den SSL-Ports angegeben ist, geht dabei der Angabe des selben Ports bei den Nicht-SSL-Ports vor und wird dort nicht berücksichtigt.<br>
  In die Felder kann jeweils eine Liste von einzelnen Portnummern im Bereich 1-65535 oder von Portbereichen in der Form Port1:Port2 eingetragen werden (z.B. "121 567:4005 27 40001:40009").</p>
  <form class="form-horizontal" method="post">
    <div class="form-group">
      <label for="sslports" class="col-lg-3 control-label">Ports mit SSL-Verbindungen</label>
      <div class="col-lg-9">
        <input type="text" class="form-control" name="sslports" id="sslports" placeholder="z.B. 443" value="$__ssl_ports">
      </div>
    </div>  
    <div class="form-group">
      <label for="tcpports" class="col-lg-3 control-label">Ports mit Nicht-SSL-Verbindungen</label>
      <div class="col-lg-9">
        <input type="text" class="form-control" name="tcpports" id="tcpports" placeholder="z.B. 80 (Default: 1:65535)" value="$__tcp_ports">
      </div>
    </div>  
    <p><input type="submit" class="btn btn-primary" value="$_speichern" name="ports"></p>
  </form>
LIMIT1;
infoMsg ("Die Änderungen werden bei der nächsten Aufzeichnung wirksam.");
echo <<<LIMIT1
        </div>
      </div>
    </div>      
LIMIT1;



// Routing

section ("internet","Internet");

if (array_key_exists("internet",$_REQUEST) && $_REQUEST["internet"]==$_speichern)
{
  $aufzeichnung = $_REQUEST["internetGroup"] == "aufzeichnung";
  
  // Wechsel zu "immer"
  if ($__internet_aufzeichnung && !$aufzeichnung)
  {
    if (is_readable($offline_script))
    {
      $cfile = fopen ($offline_script, "r");
      $line = fgets ($cfile); 
      $line = fgets ($cfile); 
      fclose ($cfile);
      $interface = trim (substr ($line, 1));
      `/sbin/iptables --table nat --append POSTROUTING --out-interface $interface -j MASQUERADE`;
      if (file_exists($session_file))
      {
        $sfile = fopen ($session_file, "r");
        $line = fgets ($sfile); 
        $line = fgets ($sfile); 
        fclose ($sfile);
        $source = trim ($line);
        `/sbin/iptables --table nat --delete POSTROUTING --source $source --out-interface $interface -j MASQUERADE`;        
      }
    }    
  }
  
  // Wechsel zu "Aufzeichnung"
  if (!$__internet_aufzeichnung && $aufzeichnung)
  {
    if (is_readable($offline_script))
    {
      $cfile = fopen ($offline_script, "r");
      $line = fgets ($cfile); 
      $line = fgets ($cfile); 
      fclose ($cfile);
      $interface = trim (substr ($line, 1));
      if (file_exists($session_file))
      {
        $sfile = fopen ($session_file, "r");
        $line = fgets ($sfile); 
        $line = fgets ($sfile); 
        fclose ($sfile);
        $source = trim ($line);
        `/sbin/iptables --table nat --append POSTROUTING --source $source --out-interface $interface -j MASQUERADE`;        
      }
      `/sbin/iptables --table nat --delete POSTROUTING --out-interface $interface -j MASQUERADE`;
    }
  }

  $__internet_aufzeichnung = $aufzeichnung? 1:0;
  change ("__internet_aufzeichnung",$__internet_aufzeichnung);
}

echo <<<LIMIT1
  <p>Mit dieser Option wird festgelegt, wann $my_name über das WLAN angeschlossenen Geräten
  die Verbindung ins Internet ermöglichen soll.</p>
  <p>Dies kann bereits dann erfolgen, wenn die Internetverbindung für $my_name hergestellt wird.
  Alle angeschlossenen Geräte können dann auf das Internet zugreifen, auch wenn gerade keine Aufzeichnung erfolgt.
  Wird eine Aufzeichnung von einem Gerät aus gestartet, werden die von diesem Gerät erzeugten Datenströme dann zusätzlich auch aufgezeichnet.<br>
  Diese Einstellung hat den Vorteil, dass nur diejenigen Datenströme aufgezeichnet werden können, die durch bestimmte Nutzeraktivitäten auf dem jeweiligen Gerät entstehen.</p>
  <p>Alternativ kann die Verbindung ins Internet erst dann erfolgen, wenn eine Aufzeichnung gestartet wird.
  Die Herstellung der Verbindung von $my_name mit dem Internet führt dann zunächst nicht dazu, dass angeschlossene Geräte selbst ins Internet können.
  Erst wenn eine Aufzeichnung gestartet wird, wird für das entsprechende Gerät der Weg ins Internet freigeschaltet und am Ende der Aufzeichnung wieder deaktiviert.<br>
  Diese Einstellung hat den Vorteil, dass sämtliche Datenströme aufgezeichnet werden können, die ein Gerät auslöst, auch unabhängig von bestimmten Nutzeraktivitäten.</p>
  <p>Diese Einstellung ändert lediglich den Durchgriff von angeschlossenen Geräten auf die Internetverbindung, die $my_name herstellt. $my_name selbst hat unabhängig davon immer dann Internetzugriff, wenn eine Online-Verbindung besteht. Die entsprechenden Werkzeuge (z.B. Whois) können dann benutzt werden.</p>
  <form method="post">
    <div class="radio">
      <label>
        <input id="internetImmer" type="radio" name="internetGroup" value="immer"
LIMIT1;
echo ($__internet_aufzeichnung==1? "":" checked"),">";
echo <<<LIMIT1
        Internetverbindung unabhängig von einer Aufzeichnung herstellen</p>
      </label>
    </div>
    <div class="radio">
      <label>
        <input id="internetAufzeichnung" type="radio" name="internetGroup" value="aufzeichnung"
LIMIT1;
echo ($__internet_aufzeichnung==1? " checked":""),">";
echo <<<LIMIT1
        Internetverbindung nur während einer Aufzeichnung herstellen
      </label>
    </div>
    <p><input type="submit" class="btn btn-primary" value="$_speichern" name="internet"></p>
  </form>
LIMIT1;
infoMsg ("Die Änderungen werden sofort wirksam.");
echo <<<LIMIT1
        </div>
      </div>
    </div>      
LIMIT1;



// Netzwerk

section ("ip","Lokales Netzwerk");

if (array_key_exists("ip",$_REQUEST) && $_REQUEST["ip"]==$_speichern)
{
  $__ip_ip1 = update ("__ip_ip1", $__ip_ip1, $_REQUEST["ip1"]);
  $__ip_ip2 = update ("__ip_ip2", $__ip_ip2, $_REQUEST["ip2"]);
  $__ip_ip3 = update ("__ip_ip3", $__ip_ip3, $_REQUEST["ip3"]);
}
   
echo <<<LIMIT1
    <script>
    function onc (v)
    {
      var ip2 = document.getElementById("ip2");
      document.getElementById("ip3").value = 0;
      
      switch (v)
      {
        case "10":
          ip2.min = 0;
          ip2.max = 255;
          ip2.value = 0;
          break;
        case "172":
          ip2.min = 16;
          ip2.max = 31;
          ip2.value = 16;
          break;
        case "192":
          ip2.min = 168;
          ip2.max = 168;
          ip2.value = 168;
          break;
      }
    }
    </script>
    
    <p>Diese Einstellungen beziehen sich auf das Subnetz, das $my_name über WLAN bereitstellt.
    Es wird immer ein Klasse-C-Netz gebildet, wobei $my_name über die IP-Adresse x.y.z.1 (z.B. 172.19.200.1) erreichbar
    ist und die Clients per DHCP Adressen ab x.y.z.2 (z.B. 172.19.200.2) zugewiesen bekommen.</p>
    
    <form class="form-horizontal" method="post">
    <div class="form-group">
      <label for="ip1" class="col-sm-3 col-md-2 col-lg-1 control-label">Subnetz</label>
      <div class="col-sm-3 col-md-2 col-lg-1">
        <select class="form-control" name="ip1" id="ip1" onchange="onc(this.value)";>
LIMIT1;

foreach (array("10","172","192") as $v)
{
  echo "<option",$v==$__ip_ip1? " selected":"",">$v</option>";
}
$ip2min = $__ip_ip1 == "10"? 0 : $__ip_ip1 == "172"? 16 : 168;
$ip2max = $__ip_ip1 == "10"? 255 : $__ip_ip1 == "172"? 31 : 168;
echo <<<LIMIT1
        </select>
      </div>
      <div class="col-sm-3 col-md-2 col-lg-1">
        <input class="form-control"  type="number" min="$ip2min" max="$ip2max" id="ip2" name="ip2" value="$__ip_ip2">
      </div>
      <div class="col-sm-3 col-md-2 col-lg-1">
        <input class="form-control"  type="number" min="0" max="255" id="ip3" name="ip3" value="$__ip_ip3">
      </div>
    </div>
    <p><input type="submit" class="btn btn-primary" value="$_speichern" name="ip"></p>
    </form>
LIMIT1;
infoMsg ("Die Änderungen werden erst nach einem Neustart wirksam.");
echo <<<LIMIT1
        </div>
      </div>
    </div>      
LIMIT1;



// WLAN

section ("wlan","WLAN");

if (array_key_exists("wlan",$_REQUEST) && $_REQUEST["wlan"]==$_speichern)
{
  $ok = 1;
  if (!preg_match ("/^[[:graph:]]{1,32}$/",$_REQUEST["ssid"]) || preg_match ("/\"/",$_REQUEST["ssid"]))
  {
    errorMsg ("SSID: \"".htmlentities($_REQUEST["ssid"])."\" ist kein gültiger Wert.");
    $ok = 0;
  }
  if (!preg_match ("/^[[:graph:]]{8,63}$/",$_REQUEST["password"]) || preg_match ("/\"/",$_REQUEST["password"]))
  {
    errorMsg ("Passwort: \"".htmlentities($_REQUEST["password"])."\" ist kein gültiger Wert");
    $ok = 0;
  }

  if ($ok)
  {
    $__wlan_ssid = update ("__wlan_ssid", $__wlan_ssid, $_REQUEST["ssid"]);
    $__wlan_password = update ("__wlan_password", $__wlan_password, $_REQUEST["password"]);
    $__wlan_channel = update ("__wlan_channel", $__wlan_channel, $_REQUEST["channel"]);
  }
}

echo <<<LIMIT1
    <p>Diese Einstellungen beziehen sich auf das WLAN, das $my_name bereitstellt und über das
    die Prüfgeräte ins Internet geroutet werden.
    Die Werte sollten so gewählt werden, dass kein Konflikt mit bestehenden WLAN in der Umgebung entsteht.</p>
  
    <form class="form-horizontal" method="post">
    <div class="form-group">
      <label for="ssid" class="col-sm-3 col-md-2 col-lg-1 control-label">SSID</label>
      <div class="col-sm-9 col-md-10 col-lg-11">
        <input class="form-control" type="text" name="ssid" value="$__wlan_ssid">
        <p class="help-block">Keine Leerzeichen; max. Länge 32 Zeichen</p>
      </div>
    </div>
    <div class="form-group">
      <label for="password" class="col-sm-3 col-md-2 col-lg-1 control-label">Passwort</label>
      <div class="col-sm-9 col-md-10 col-lg-11">
        <input class="form-control" type="text" name="password" value="$__wlan_password">
        <p class="help-block">Keine Leerzeichen; mindestens 8, höchstens 63 Zeichen</p>
      </div>
    </div>
    <div class="form-group">
      <label for="channel" class="col-sm-3 col-md-2 col-lg-1 control-label">Kanal</label>
      <div class="col-sm-3 col-md-2 col-lg-1">
        <input class="form-control" type="number" min="1" max="11" name="channel" value="$__wlan_channel">
      </div>
    </div>
    <p><input type="submit" class="btn btn-primary" value="$_speichern" name="wlan"></p>
    </form>
LIMIT1;
infoMsg ("Die Änderungen werden erst nach einem Neustart wirksam.");
echo <<<LIMIT1
        </div>
      </div>
    </div>      
LIMIT1;



// DNS

section ("dns","DNS");

if (array_key_exists("dns",$_REQUEST) && $_REQUEST["dns"]==$_speichern)
{
  $ok = 1;
  if (!preg_match ("/^[a-z]{1,63}$/",$_REQUEST["hostname"]))
  {
    errorMsg ("Hostname: \"".htmlentities($_REQUEST["hostname"])."\" ist kein gültiger Wert");
    $ok = 0;
  }

  if (!preg_match ("/^[a-z][a-z0-9]{0,62}$/",$_REQUEST["domainname"]))
  {
    errorMsg ("Domainname: \"".htmlentities($_REQUEST["domainname"])."\" ist kein gültiger Wert");
    $ok = 0;
  }

  if ($ok)
  {
    $__dns_server_name = update ("__dns_server_name", $__dns_server_name, $_REQUEST["hostname"]);
    $__dns_domain_name = update ("__dns_domain_name", $__dns_domain_name, $_REQUEST["domainname"]);
  }
}
echo <<<LIMIT1
    <p>Mit diesen Einstellungen wird festgelegt, unter welchem Netzwerknamen $my_name von den Prüfgeräten
    aus erreichbar ist. Statt über die IP-Adresse kann $my_name so mit der Adresse &lt;hostname&gt;.&lt;domainname&gt; adressiert werden.
    Es sollte darauf geachtet werden, keine Konflikte mit vorhandenen Internet-Domains zu erzeugen, da die enstpechenden Server dann
    nicht mehr erreichbar wären.</p>
  
    <form class="form-horizontal" method="post">
    <div class="form-group">
      <label for="hostname" class="col-sm-4 col-md-3 col-lg-2 control-label">Hostname</label>
      <div class="col-sm-8 col-md-9 col-lg-10">
        <input class="form-control" type="text" name="hostname" value="$__dns_server_name">
        <p class="help-block">Nur Buchstaben; max. Länge 63 Zeichen</p>
      </div>
    </div>
    <div class="form-group">
      <label for="domainname" class="col-sm-4 col-md-3 col-lg-2 control-label">Domainname</label>
      <div class="col-sm-8 col-md-9 col-lg-10">
        <input class="form-control" type="text" name="domainname" value="$__dns_domain_name">
        <p class="help-block">Buchstaben und Ziffern; keine einzelne Ziffer; max. Länge 63 Zeichen. Existierende TLD wie "de" sollten vermieden werden</p>
      </div>
    </div>
    <p><input type="submit" class="btn btn-primary" value="$_speichern" name="dns"></p>
    </form>
LIMIT1;
infoMsg ("Die Änderungen werden erst nach einem Neustart wirksam.");
echo <<<LIMIT1
        </div>
      </div>
    </div>      
LIMIT1;


// Ansicht und Verhalten

section ("ansicht", "Ansicht und Verhalten");

if (array_key_exists("ansicht",$_REQUEST) && $_REQUEST["ansicht"]==$_speichern)
{
  if ($_REQUEST["zeilen"] == "")
  {
    $__zeilen = update ("__zeilen", $__zeilen, "");
  }
  else
  {
    $zeilen = preg_split ("/[^0-9]+/", $_REQUEST["zeilen"], null, PREG_SPLIT_NO_EMPTY);
    if (!count($zeilen) || array_search("0",$zeilen))
    {
      errorMsg ("Zeilenbegrenzung: \"".htmlentities($_REQUEST["zeilen"])."\" ist kein gültiger Wert");
    }
    else
    {
      $__zeilen = update ("__zeilen", $__zeilen, implode(" ",$zeilen));
    }
  }
  
  $__skin = update ("__skin", $__skin, $_REQUEST["skin"]);
  $__klick = update ("__klick", $__klick, $_REQUEST["klick"]);
  $__suchbox = update ("__suchbox", $__suchbox, isset($_REQUEST["suchbox"])? 1:0);
  $__dekodbox = update ("__dekodbox", $__dekodbox, isset($_REQUEST["dekodbox"])? 1:0);
  $__whoisbox = update ("__whoisbox", $__whoisbox, isset($_REQUEST["whoisbox"])? 1:0);
  $__debug = update ("__debug", $__debug, isset($_REQUEST["debug"])? 1:0);
  $__usetabs = update ("__usetabs", $__usetabs, isset($_REQUEST["usetabs"])? 1:0);
  
  // reload page to reflect visual changes
  echo "<script>window.location = \"",$_SERVER['REQUEST_URI'],"\"</script>";
}

echo <<<LIMIT1
    <p>Mit diesen Einstellungen kann das Aussehen und Verhalten von $my_name angepasst werden.</p>
    <form class="form-horizontal" method="post">
      <div class="panel panel-info">
        <div class="panel-heading">
          <h5 class="panel-title">Aussehen</h5>
        </div>
        <div class="panel-body">
          <div class="form-group">
            <label class="control-label col-sm-4 col-md-3 col-lg-2" for="skin" control-label>Skin auswählen</label>
            <div class="col-sm-8 col-md-9 col-lg-10">
              <select class="form-control" name="skin" onchange="document.getElementById('mockup').src='mockup.php?skin='+this.value;">
LIMIT1;
echo "<option value=\"\"",($__skin==""? " selected":""),">Standard</option>";
foreach (scandir($lighttpd_root."/".$skin_dir) as $i=>$name)
{
  if (preg_match("/^([^.]*)(\.min)?\.css$/i",$name,$m))
  {
    echo "<option value=\"$name\"",($__skin == $name? " selected":""),">",$m[1],($__skin == $name? " (aktuell verwendet)":""),"</option>";
  }
}
echo <<<LIMIT1
              </select>
            </div>
          </div>
          <p><strong>Vorschau</strong></p>
          <div style="width:100%; height:300px; position:relative;">
            <div style="background:#ffffff; opacity:0.0; width:100%; height:100%; position:absolute; top:0px; left:0px; z-index:10;"></div>
            <iframe src="mockup.php?skin=$__skin" id="mockup" scrolling="no" style="transform:scale(0.9); transform-origin: 0 0; width:110%; height:100%; position:absolute; top:0px; left:0px; overflow:hidden;"></iframe>
          </div>
        </div>
      </div>

      <div class="panel panel-info">
        <div class="panel-heading">
          <h5 class="panel-title">Eingabefelder in der Menüleiste</h5>
        </div>
        <div class="panel-body">
          <div class="form-group">
            <div class="checkbox col-md-4">
              <label>
LIMIT1;
echo "<input type=\"checkbox\" name=\"suchbox\"",($__suchbox? " checked":""),">";
echo <<<LIMIT1
                Suche anzeigen
              </label>
            </div>
            <div class="checkbox col-md-4">
              <label>
LIMIT1;
echo "<input type=\"checkbox\" name=\"dekodbox\"",($__dekodbox? " checked":""),">";
echo <<<LIMIT1
                Dekodieren anzeigen
              </label>
            </div>
            <div class="checkbox col-md-4">
              <label>
LIMIT1;
echo "<input type=\"checkbox\" name=\"whoisbox\"",($__whoisbox? " checked":""),">";
echo <<<LIMIT1
                Whois anzeigen
              </label>
            </div>
          </div>
          <div class="form-group">
            <div class="checkbox col-md-12">
              <label>
LIMIT1;
echo "<input type=\"checkbox\" name=\"usetabs\"",($__usetabs? " checked":""),">";
echo <<<LIMIT1
                Suche, Dekodieren und Whois in eigenen Browser-Tabs anzeigen
              </label>
            </div>
          </div>
        </div>
      </div>
          
      <div class="panel panel-info">
        <div class="panel-heading">
          <h5 class="panel-title">Tabellen</h5>
        </div>
        <div class="panel-body">
          <div class="form-group">
            <label class="control-label col-md-4 col-lg-2">Zeilenbegrenzung</label>
            <div class="col-md-8 col-lg-10">
              <input type="text" class="form-control" name="zeilen" value="$__zeilen">
              <span class="help-block">
                Da die meisten Tabellen aus einer großen Anzahl von Zeilen bestehen, ist es sinnvoll, diese in
                kleineren Blöcken (Seiten) anzuzeigen. Hier können die gewünschten Seitengrößen angegeben werden.
                Die erste Zahl bestimmt dabei die Standardeinstellung.
                Die Angabe "20 5 50 30" z.B. bewirkt, dass bei einer entsprechend großen Tabelle pro Seite nur 20 Zeilen
                angezeigt werden und dies auf 5, 30 oder 50 Zeilen umgestellt werden kann (sowie auf alle Zeilen).
                Wird nichts angegeben, werden Tabellen stets mit allen Zeilen angezeigt.
              </span>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-md-4 col-lg-2">Aktion, um Tabellen zu falten oder zu entfalten</label>
            <div class="col-md-8 col-lg-10">
              <select class="form-control" name="klick">
LIMIT1;
echo "<option value=\"onclick\"",($__klick == "onclick"? " selected" : ""),">einfacher Klick</option>";
echo "<option value=\"ondblclick\"",($__klick == "ondblclick"? " selected" : ""),">Doppelklick</option>";
echo "<option value=\"oncontextmenu\"",($__klick == "oncontextmenu"? " selected" : ""),">Rechtsklick</option>";
echo <<<LIMIT1
              </select>
              <span class="help-block">
                Tabellen werden standardmäßig kompakt dargestellt, indem lange Werte abgeschnitten werden.
                Um den gesamten Inhalt zu sehen, kann die entsprechende Tabellenzelle angeklickt werden.
                Welche Art von Klick dafür nötig ist, kann hier eingestellt werden.
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="panel panel-info">
        <div class="panel-heading">
          <h5 class="panel-title">Sonstiges</h5>
        </div>
        <div class="panel-body">
          <div class="checkbox">
            <label>
LIMIT1;
echo "<input type=\"checkbox\" name=\"debug\"",($__debug? " checked":""),">";
echo <<<LIMIT1
              Debug-Informationen anzeigen
            </label>
          </div>
        </div>
      </div>
      <p></p>
      <p><input type="submit" class="btn btn-primary" value="$_speichern" name="ansicht"></p>
    </form>
LIMIT1;
infoMsg ("Die Änderungen werden sofort wirksam.");
echo <<<LIMIT1
        </div>
      </div>
    </div>      
LIMIT1;

echo "</div></div>";

include ("include/htmlend.php");

?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/topmenu.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display the top menu and utility boxes
//
//==============================================================================
//==============================================================================


$extranav = isset($extranav)? $extranav : "";

// Button State Script
echo <<<LIMIT1
<script>
function buttonState()
{
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() 
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) 
    {
      var res = xmlhttp.responseText.split (";");
      if (res[0]==1)
      {
        document.getElementById("topmenuLan").classList.remove("disabled");
      }
      else
      {
        document.getElementById("topmenuLan").classList.add("disabled");
      }
      if (res[1]==1)
      {
        document.getElementById("topmenuWlan").classList.remove("disabled");
      }
      else
      {
        document.getElementById("topmenuWlan").classList.add("disabled");
      }
      if (res[2]==1)
      {
        document.getElementById("topmenuUmts").classList.remove("disabled");
      }
      else
      {
        document.getElementById("topmenuUmts").classList.add("disabled");
      }
      if (res[3]==1)
      {
        document.getElementById("topmenuOffline").classList.remove("disabled");
      }
      else
      {
        document.getElementById("topmenuOffline").classList.add("disabled");
      }
      if (document.getElementById("topmenuRecord").innerHTML != res[4])
      {
        document.getElementById("topmenuRecord").innerHTML = res[4];
      }
    }
  }
  
  xmlhttp.open("GET","include/buttonstate.php",true);
  xmlhttp.send();
}
var myVar = setInterval (function () { buttonState() }, 500);
</script>
LIMIT1;


// Info modal
echo <<<LIMIT1
<script>
function info()
{
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() 
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) 
    {
      document.getElementById("info").innerHTML = xmlhttp.responseText;
    }
  }
  
  xmlhttp.open("GET","include/info.php",true);
  xmlhttp.send();
}
var myVar = setInterval (function () { info() }, 1000);
</script>

<div class="modal fade" id="infoModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="alert alert-info" role="alert">
          <div class="msgIcon"><i class="fa fa-info-circle fa-2x"></i></div>
          <div class="msgText"><strong>$my_name-Status</strong></div>
        </div>
      </div>
      <div class="modal-body">
        <span id="info"></span>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info" data-dismiss="modal">Schließen</button>
      </div>
    </div>
  </div>
</div>
LIMIT1;


// Navbar
echo <<<LIMIT1
<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#topmenu">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="topmenu">
      <ul class="nav navbar-nav">
LIMIT1;


// Start Stop
if (file_exists ($session_file)) // we do have a running session
{
  $sfile = fopen ($session_file, "r");
  $aufzeichnungID = trim (fgets ($sfile));
  $ip = trim (fgets ($sfile));
  $running = trim (fgets ($sfile));
  fclose ($sfile);
  $button = $running? $recordStop : $recordEnd;
}
else
{
  $button = $recordStart;
}
echo <<<LIMIT1
        <li id="topmenuRecord">$button</li>  
LIMIT1;


// Info
echo <<<LIMIT1
        <li><a href="#infoModal" data-toggle="modal"><i class="fa fa-info-circle fa-lg"></i></a></li>
LIMIT1;


// Auswertungen
echo <<<LIMIT1
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"><i class="fa fa-bars"></i> <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li class="dropdown-header"><p class="text-center">Auswerten</p></li>
            <li><a href="aufzeichnungen.php"><i class="fa fa-database fa-fw topmenu"></i> Aufzeichnungen</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="eigenschaften.php"><i class="fa fa-tablet fa-fw topmenu"></i> Eigenschaften</a></li>
            <li><a href="inhalte.php"><i class="fa fa-file-o fa-fw topmenu"></i> Inhalte</a></li>
            <li><a href="bilder.php"><i class="fa fa-picture-o fa-fw topmenu"></i> Bilder</a></li>
            <li><a href="metadaten.php"><i class="fa fa-tags fa-fw topmenu"></i> Metadaten</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="headers.php"><i class="fa fa-header fa-fw topmenu"></i> HTTP-Header</a></li>
            <li><a href="cookies.php"><i class="fa fa-birthday-cake fa-fw topmenu"></i> Cookies</a></li>
            <li><a href="verweise.php"><i class="fa fa-exchange fa-fw topmenu"></i> Verweise</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="sslsuites.php"><i class="fa fa-key fa-fw topmenu"></i> SSL-Verschlüsselung</a></li>
            <li><a href="zertifikate.php"><i class="fa fa-certificate fa-fw topmenu"></i> Zertifikate</a></li>
          </ul>
        </li>
LIMIT1;


// Internet
$eth = " class=\"disabled\"";
if (file_exists ("/sys/class/net/$wired_interface/carrier"))
{
  $cfile = fopen ("/sys/class/net/$wired_interface/carrier", r);
  if (trim (fgets ($cfile)) == "1")
  {
    $eth = "";
  }
  fclose ($cfile);
}
$wlan = file_exists ("/sys/class/net/$wireless_internet")? "":" class=\"disabled\"";
$umts = file_exists ("/sys/class/net/$wired_internet") || file_exists ("/sys/class/net/$umts_interface")? "":" class=\"disabled\"";
$offline = file_exists ($offline_script)? "":" class=\"disabled\"";
echo <<<LIMIT1
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"><i class="fa fa-globe"></i> <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li class="dropdown-header"><p class="text-center">Internet</p></li>
            <li id="topmenuLan"$eth><a href="connectLAN.php"><i class="fa fa-sitemap fa-fw topmenu"></i> LAN</a></li>
            <li id="topmenuWlan"$wlan><a href="connectWLAN.php"><i class="fa fa-wifi fa-fw topmenu"></i> WLAN</a></li>
            <li id="topmenuUmts"$umts><a href="connectUMTS.php"><i class="fa fa-signal fa-fw topmenu"></i> UMTS</a></li>
            <li role="separator" class="divider"></li>
            <li id="topmenuOffline"$offline><a href="disconnect.php"><i class="fa fa-cut fa-fw topmenu" ></i> Offline</a></li>
          </ul>
        </li>
LIMIT1;


// Werkzeuge
echo <<<LIMIT1
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"><i class="fa fa-cog"></i> <span class="caret"></span></a>
          <ul class="dropdown-menu dropdown-menu-right">
            <li class="dropdown-header"><p class="text-center">Werkzeuge</p></li>
LIMIT1;
echo "<li><a href=\"suche.php\"",($__usetabs? " target=\"${my_name}suche\"" : ""),"><i class=\"fa fa-search fa-fw topmenu\"></i> Suche</a></li>";
echo "<li><a href=\"dekodieren.php\"",($__usetabs? " target=\"${my_name}dekodieren\"" : ""),"><i class=\"fa fa-quote-right fa-fw topmenu\"></i> Dekodieren</a></li>";
echo "<li><a href=\"whois.php\"",($__usetabs? " target=\"${my_name}whois\"" : ""),"><i class=\"fa fa-institution fa-fw topmenu\"></i> Whois</a></li>";
echo <<<LIMIT1
            <li role="separator" class="divider"></li>
            <li><a href="devices.php"><i class="fa fa-tablet fa-fw topmenu"></i> Geräte verwalten</a></li>
            <li><a href="certmanage.php"><i class="fa fa-certificate fa-fw topmenu"></i> $my_name-Zertifikat</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="import.php"><i class="fa fa-download fa-fw topmenu"></i> Aufzeichnung importieren</a></li>
            <li><a href="erase.php"><i class="fa fa-trash fa-fw topmenu"></i> Datenbank leeren</a></li>
            <li role="separator" class="divider"></li>
            <li><a href="settings.php"><i class="fa fa-wrench fa-fw topmenu"></i> Einstellungen</a></li>
            <li><a href="status.php"><i class="fa fa-info fa-fw topmenu"></i> Status</a></li>
            <li><a href="about.php"><i class="fa fa-question fa-fw topmenu"></i> Über $my_name</a></li>
          </ul>
        </li>      
LIMIT1;


// Power
echo <<<LIMIT1
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"><i class="fa fa-plug"></i> <span class="caret"></span></a>
          <ul class="dropdown-menu dropdown-menu-right">
            <li class="dropdown-header"><p class="text-center">Betrieb</p></li>
            <li><a href="poweroff.php?restart=0"><i class="fa fa-power-off fa-fw topmenu"></i> Herunterfahren</a></li>
            <li><a href="poweroff.php?restart=1"><i class="fa fa-refresh fa-fw topmenu"></i> Neu starten</a></li>
          </ul>
        </li>
      </ul>
LIMIT1;


// Utility-Boxen

echo "<div class=\"visible-lg-block\">";

if ($__whoisbox)
{
  echo "<form class=\"navbar-form navbar-right\" method=\"post\" action=\"whois.php\"",($__usetabs? " target=\"${my_name}whois\"" : ""),">";
  echo <<<LIMIT1
        <input type="hidden" name="domain" id="domain" value="">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" name="xdomain" id="xdomain">
          <span class="input-group-btn">
          <button title="Whois-Abfrage" type="submit" class="btn btn-default" onclick="document.getElementById('domain').value=document.getElementById('xdomain').value; document.getElementById('xdomain').value='';">
            <i class="fa fa-institution"></i>
          </button>
          </span>
        </div>
      </form>
LIMIT1;
}

if ($__dekodbox)
{
  echo "<form class=\"navbar-form navbar-right\" method=\"post\" action=\"dekodieren.php\"",($__usetabs? " target=\"${my_name}dekodieren\"" : ""),">";
  echo <<<LIMIT1
        <input type="hidden" name="code" id="code" value="">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" name="xcode" id="xcode">
          <span class="input-group-btn">
          <button title="Dekodieren" type="submit" class="btn btn-default" onclick="document.getElementById('code').value=document.getElementById('xcode').value; document.getElementById('xcode').value='';">
            <i class="fa fa-quote-right"></i>
          </button>
          </span>
        </div>
      </form>
LIMIT1;
}
            
if ($__suchbox)
{
  echo "<form class=\"navbar-form navbar-right\" method=\"post\" action=\"suche.php\"",($__usetabs? " target=\"${my_name}suche\"" : ""),">";
  echo <<<LIMIT1
        <input type="hidden" name="caseSwitch" value="on">
        <input type="hidden" name="orte" value="alle">
        <input type="hidden" name="suche" id="suche" value="">
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" name="xsuche" id="xsuche">
          <span class="input-group-btn">
          <button title="Suche" type="submit" class="btn btn-default" onclick="document.getElementById('suche').value=document.getElementById('xsuche').value; document.getElementById('xsuche').value='';">
            <i class="fa fa-search"></i>
          </button>
          </span>
        </div>
      </form>
LIMIT1;
}

echo "</div>";


// Navbar schließen und Inhaltscontainer öffnen (wird in htmlend.php geschlossen)
echo <<<LIMIT1
    </div>
  </div>
  $extranav
</nav>

<div class="container-fluid">
LIMIT1;

?>

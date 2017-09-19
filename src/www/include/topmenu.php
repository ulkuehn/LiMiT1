<?php

/**
 * project LiMiT1
 * file include/topmenu.php
 * 
 * used to display the top menu and utility boxes
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
///////////////////////////////////////////////////////////////////////////////
// 
// definition of names and values
// 
///////////////////////////////////////////////////////////////////////////////

/**
 * id of the status modal
 */
$statusModalID = "statusModal";
/**
 * id of the status modal content span
 */
$statusModalContentID = "statusContent";
/**
 * URL of the script providing status info
 */
$statusProviderURL = "include/info.php";
/**
 * name of the JS function called for updating the status info
 */
$statusJSFunction = "status ()";
/**
 * status info update frequency (ms)
 */
$statusUpdateMS = 1000;

/**
 * URL of the script providing button state info
 */
$buttonProviderURL = "include/buttonstate.php";
/**
 * name of the JS function called for updating buttons
 */
$buttonJSFunction = "buttonState ()";
/**
 * button state info update frequency (ms)
 */
$buttonUpdateMS = 500;

/**
 * id of the navbar div
 */
$navbarID = "topmenu";
/**
 * id of the record button element
 */
$recordButtonID = "topmenuRecord";
/**
 * id of the menu item to go online via LAN
 */
$lanOnlineID = "topmenuLan";
/**
 * id of the menu item to go online via WLAN
 */
$wlanOnlineID = "topmenuWlan";
/**
 * id of the menu item to go online via UMTS
 */
$umtsOnlineID = "topmenuUmts";
/**
 * id of the menu item to go offline
 */
$offlineID = "topmenuOffline";

/**
 * parameters for utility boxes (hover text, name, icon, URL, extra parameters)
 */
$searchBox = [ _ ( "Suche" ), "search", "fa-search", "suche.php", "<input type=\"hidden\" name=\"caseSwitch\" value=\"on\"><input type=\"hidden\" name=\"orte\" value=\"alle\">" ];
$decodeBox = [ _ ( "Dekodieren" ), "decode", "fa-quote-right", "dekodieren.php", "" ];
$whoisBox = [ _ ( "Whois" ), "whois", "fa-institution", "whois.php", "" ];


///////////////////////////////////////////////////////////////////////////////
//
// helper functions
// 
///////////////////////////////////////////////////////////////////////////////

/**
 * add a dropdown menu
 * 
 * @param string $fa the name of the font awesome icon to display
 * @param string $text the text to use (possibly i18n'd)
 * 
 * @return NULL nothing
 */
function openMenu ( $fa, $text )
{
  echo <<<LIMIT1
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"><i class="fa $fa"></i> <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li class="dropdown-header">
              <p class="text-center">$text</p>
            </li>
LIMIT1;
}

/**
 * close a dropdown menu
 */
function closeMenu ()
{
  echo "</ul></li>";
}

/**
 * put a separator line in a drop down menu
 * 
 * @return NULL nothing
 */
function menuSeparator ()
{
  echo "<li role=\"separator\" class=\"divider\"></li>";
}

/**
 * add an entry to a drop down menu
 * 
 * @param string $url the (relative) url to call when this menu item is activated
 * @param string $fa the name of the font awesome icon to display
 * @param type $text the text to use (possibly i18n'd)
 * 
 * @return NULL nothing
 */
function menuEntry ( $url, $fa, $text )
{
  echo <<<LIMIT1
    <li>
      <a href="$url"><i class="fa $fa fa-fw topmenu"></i> $text</a>
    </li>
LIMIT1;
}

/**
 * create a utility box (search etc)
 * 
 * @param array $box parameters for that box (title, name, icon, URL, extra Parameters)
 *
 * @return NULL nothing
 */
function utilityBox ( $box )
{
  global $__usetabs, $my_name;
  list ($title, $name, $fa, $url, $extraParameters) = $box;

  echo "<form class=\"navbar-form navbar-right\" method=\"post\" action=\"$url\"", ($__usetabs ? " target=\"$my_name$name\"" : ""), ">";
  echo <<<LIMIT1
        <input type="hidden" name="$name" id="$name" value="">
        $extraParameters
        <div class="input-group input-group-sm">
          <input type="text" class="form-control" name="x$name" id="x$name">
          <span class="input-group-btn">
          <button title="$title" type="submit" class="btn btn-default" onclick="document.getElementById('$name').value=document.getElementById('x$name').value; document.getElementById('x$name').value='';">
            <i class="fa $fa"></i>
          </button>
          </span>
        </div>
      </form>
LIMIT1;
}

/**
 * add an utility box related entry to a drop down menu
 * 
 * @param array $box parameters for that box (title, name, icon, URL)
 *
 * @return NULL nothing
 */
function boxMenuEntry ( $box )
{
  global $__usetabs, $my_name;
  list ($title, $name, $fa, $url) = $box;

  echo "<li>";
  echo "<a href=\"$url\"", ($__usetabs ? " target=\"$my_name$name\"" : ""), ">";
  echo "<i class=\"fa $fa fa-fw topmenu\"></i> ";
  echo $title;
  echo "</a>";
  echo "</li>";
}

///////////////////////////////////////////////////////////////////////////////
//
// status info
// 
///////////////////////////////////////////////////////////////////////////////

/*
 * define modal window providing some basic status info
 */
echo <<<LIMIT1
<div class="modal fade" id="$statusModalID" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <div class="alert alert-info" role="alert">
          <div class="msgIcon"><i class="fa fa-info-circle fa-2x"></i></div>
          <div class="msgText"><strong>
LIMIT1;

echo _ ( "$my_name-Status" );

echo <<<LIMIT1
</strong></div>
        </div>
      </div>
      <div class="modal-body">
        <span id="$statusModalContentID"></span>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info" data-dismiss="modal">
LIMIT1;

echo _ ( "Schließen" );

echo <<<LIMIT1
</button>
      </div>
    </div>
  </div>
</div>

LIMIT1;

/*
 * include javascript code to periodically update content of status window
 * the called script is supposed to return proper html that can be inserted in a span-tag
 */
echo <<<LIMIT1
<script>
function $statusJSFunction
{
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() 
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) 
    {
      document.getElementById("$statusModalContentID").innerHTML = xmlhttp.responseText;
    }
  }
  
  xmlhttp.open("GET","$statusProviderURL",true);
  xmlhttp.send();
}
var myVar = setInterval (function () { $statusJSFunction }, $statusUpdateMS);
</script>
    
LIMIT1;


///////////////////////////////////////////////////////////////////////////////
//
// button updates
// 
///////////////////////////////////////////////////////////////////////////////

/*
 * include javascript code to periodically update the visual state of certain buttons, including:
 *  - buttons to go online via LAN, WLAN, UMTS (enabling only when necessary hardware is detected)
 *  - button to go offline (enabling only when online)
 *  - record button (switching between starting or stopping a recording or ongoing database update)
 *  
 * state information is provided by script which is called asynchronously
 */

echo <<<LIMIT1
<script>
function $buttonJSFunction
{
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() 
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) 
    {
      var res = xmlhttp.responseText.split (";");
      if (res[0]==1)
      {
        document.getElementById("$lanOnlineID").classList.remove("disabled");
      }
      else
      {
        document.getElementById("$lanOnlineID").classList.add("disabled");
      }
      if (res[1]==1)
      {
        document.getElementById("$wlanOnlineID").classList.remove("disabled");
      }
      else
      {
        document.getElementById("$wlanOnlineID").classList.add("disabled");
      }
      if (res[2]==1)
      {
        document.getElementById("$umtsOnlineID").classList.remove("disabled");
      }
      else
      {
        document.getElementById("$umtsOnlineID").classList.add("disabled");
      }
      if (res[3]==1)
      {
        document.getElementById("$offlineID").classList.remove("disabled");
      }
      else
      {
        document.getElementById("$offlineID").classList.add("disabled");
      }
      if (document.getElementById("$recordButtonID").innerHTML != res[4])
      {
        document.getElementById("$recordButtonID").innerHTML = res[4];
      }
    }
  }
  
  xmlhttp.open("GET","$buttonProviderURL",true);
  xmlhttp.send();
}
var myVar = setInterval (function () { $buttonJSFunction }, $buttonUpdateMS);
</script>

LIMIT1;


///////////////////////////////////////////////////////////////////////////////
//
// navbar
// 
///////////////////////////////////////////////////////////////////////////////

/*
 * open navbar container
 */

echo <<<LIMIT1
<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#$navbarID">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="$navbarID">
      <ul class="nav navbar-nav">
    
LIMIT1;


/*
 * start or stop a recording
 */

if ( file_exists ( $session_file ) ) // we do have a running session
{
  $sfile = fopen ( $session_file, "r" );
  $sessionID = trim ( fgets ( $sfile ) );
  $sessionIP = trim ( fgets ( $sfile ) );
  $isRunning = trim ( fgets ( $sfile ) );
  fclose ( $sfile );
  $button = $isRunning ? $recordStop : $recordEnd;
}
else
{
  $button = $recordStart;
}
echo "<li id=\"$recordButtonID\">$button</li>";


/*
 * status info button
 */

echo "<li>";
echo "<a href=\"#$statusModalID\" data-toggle=\"modal\">";
echo "<i class=\"fa fa-info-circle fa-lg\"></i>";
echo "</a>";
echo "</li>";


/*
 * analysis
 */

openMenu ( "fa-bars", _ ( "Auswerten" ) );

// recordings
menuEntry ( "aufzeichnungen.php", "fa-database", _ ( "Aufzeichnungen" ) );

menuSeparator ();

// properties
menuEntry ( "eigenschaften.php", "fa-tablet", _ ( "Eigenschaften" ) );

// contents
menuEntry ( "inhalte.php", "fa-file-o", _ ( "Inhalte" ) );

// images
menuEntry ( "bilder.php", "fa-picture-o", _ ( "Bilder" ) );

// metadata
menuEntry ( "metadaten.php", "fa-tags", _ ( "Metadaten" ) );

menuSeparator ();

// HTTP header
menuEntry ( "headers.php", "fa-header", _ ( "HTTP-Header" ) );

// cookies
menuEntry ( "cookies.php", "fa-birthday-cake", _ ( "Cookies" ) );

// referrals
menuEntry ( "verweise.php", "fa-exchange", _ ( "Verweise" ) );

menuSeparator ();

// SSL encryption
menuEntry ( "sslsuites.php", "fa-key", _ ( "SSL-Verschlüsselung" ) );

// certificates
menuEntry ( "zertifikate.php", "fa-certificate", _ ( "Zertifikate" ) );

closeMenu ();


/*
 * internet
 */
require_once ("hardware.php");

openMenu ( "fa-globe", _ ( "Internet" ) );

// LAN
echo "<li id=\"$lanOnlineID\"";
echo ethernetCable () ? ">" : " class=\"disabled\">";
echo "<a href=\"connectLAN.php\">";
echo "<i class=\"fa fa-sitemap fa-fw topmenu\"></i> ";
echo _ ( "LAN" );
echo "</a></li>";

list ($wifi, $umts) = hasWifiUMTS ();

// WLAN
echo "<li id=\"$wlanOnlineID\"";
echo $wifi ? ">" : " class=\"disabled\">";
echo "<a href=\"connectWLAN.php\">";
echo "<i class=\"fa fa-wifi fa-fw topmenu\"></i> ";
echo _ ( "WLAN" );
echo "</a></li>";

// UMTS
echo "<li id=\"$umtsOnlineID\"";
echo $umts ? ">" : " class=\"disabled\">";
echo "<a href=\"connectUMTS.php\">";
echo "<i class=\"fa fa-signal fa-fw topmenu\"></i> ";
echo _ ( "UMTS" );
echo "</a></li>";

menuSeparator ();

// offline
echo "<li id=\"$offlineID\"";
echo file_exists ( $offline_script ) ? ">" : " class=\"disabled\">";
echo "<a href=\"disconnect.php\">";
echo "<i class=\"fa fa-cut fa-fw topmenu\"></i> ";
echo _ ( "Offline" );
echo "</a></li>";

closeMenu ();


/*
 * Tools
 */

openMenu ( "fa-cog", _ ( "Werkzeuge" ) );

// search
boxMenuEntry ( $searchBox );

// decode
boxMenuEntry ( $decodeBox );

// whois
boxMenuEntry ( $whoisBox );

menuSeparator ();

// Manage Devices
menuEntry ( "devices.php", "fa-tablet", _ ( "Geräte verwalten" ) );

// Certificate
menuEntry ( "certmanage.php", "fa-certificate", _ ( "$my_name-Zertifikat" ) );

menuSeparator ();

// Import Recording
menuEntry ( "import.php", "fa-download", _ ( "Aufzeichnung importieren" ) );

// Clear Database
menuEntry ( "erase.php", "fa-trash", _ ( "Datenbank leeren" ) );

menuSeparator ();

// Settings
menuEntry ( "settings.php", "fa-wrench", _ ( "Einstellungen" ) );

// Status
menuEntry ( "status.php", "fa-info", _ ( "Status" ) );

// Updates
menuEntry ( "updates.php", "fa-flash", _ ( "Updates" ) );

// About
menuEntry ( "about.php", "fa-question", _ ( "Über $my_name" ) );

closeMenu ();


/*
 * Power
 */

openMenu ( "fa-plug", _ ( "Betrieb" ) );

// Shutdown
menuEntry ( "poweroff.php?restart=0", "fa-power-off", _ ( "Herunterfahren" ) );

// Restart
menuEntry ( "poweroff.php?restart=1", "fa-refresh", _ ( "Neu starten" ) );

closeMenu ();

echo "</ul>";


/*
 * utility boxes (search etc)
 */

echo "<div class=\"visible-lg-block\">";

// whois
if ( $__whoisbox )
{
  utilityBox ( $whoisBox );
}

// decode
if ( $__dekodbox )
{
  utilityBox ( $decodeBox );
}

// search
if ( $__suchbox )
{
  utilityBox ( $searchBox );
}

echo "</div>";


/*
 * close navbar 
 */
echo "</div></div>";
// hide navbar in extra tabs (search etc)
if ( $framename != "" )
{
  echo "<div class=\"navHider\"></div>";
}
echo "</nav>";


/*
 * open content div (to be closed in htmlend.php)
 */
echo "<div class=\"container-fluid\">";

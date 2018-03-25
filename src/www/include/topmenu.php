<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/topMenu.php
 *
 * display the top menu and utility boxes
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
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 *
 * FUNCTIONS
 *
 * ======================================================================== */

/**
 * add a dropdown menu
 *
 * @param string $faIcon the name of the font awesome icon to display
 * @param string $text the text to use (possibly i18n'd)
 *
 * @return NULL nothing
 */
function openMenu ( $faIcon,
                    $text )
{
  echo "<li class=\"dropdown\"><a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\"><i class=\"fa $faIcon\"></i> <span class=\"caret\"></span></a><ul class=\"dropdown-menu\"><li class=\"dropdown-header\"><p class=\"text-center\">$text</p></li>";
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
 * @param string $faIcon the name of the font awesome icon to display
 * @param type $text the text to use (possibly i18n'd)
 *
 * @return NULL nothing
 */
function menuEntry ( $url,
                     $faIcon,
                     $text )
{
  echo "<li><a href=\"$url\"><i class=\"fa $faIcon fa-fw topmenu\"></i> $text</a></li>";
}


/**
 * create a utility box (search etc)
 *
 * @param array $box parameters for that box (title, name, frame, icon, URL, extra Parameters)
 *
 * @return NULL nothing
 */
function utilityBox ( $box )
{
  global $__usetabs, $my_name, $__;
  list ($title, $name, $frame, $faIcon, $url, $extraParameters) = $box;

  echo "<form class=\"navbar-form navbar-right\" method=\"post\" action=\"$url\"", ($__usetabs ? " target=\"$my_name$frame\"" : ""), ">";
  echo "<input type=\"hidden\" name=\"$name\" id=\"", $__[ "include/topMenu" ] [ "ids" ] [ "utilityPrefix1" ], $name, "\" value=\"\">$extraParameters<div class=\"input-group input-group-sm\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "include/topMenu" ] [ "ids" ] [ "utilityPrefix2" ], $name, "\" id=\"", $__[ "include/topMenu" ] [ "ids" ] [ "utilityPrefix2" ], $name, "\"><span class=\"input-group-btn\"><button title=\"$title\" type=\"submit\" class=\"btn btn-default\" onclick=\"document.getElementById('", $__[ "include/topMenu" ] [ "ids" ] [ "utilityPrefix1" ], $name, "').value=document.getElementById('", $__[ "include/topMenu" ] [ "ids" ] [ "utilityPrefix2" ], $name, "').value; document.getElementById('", $__[ "include/topMenu" ] [ "ids" ] [ "utilityPrefix2" ], $name, "').value='';\"><i class=\"fa $faIcon\"></i></button></span></div></form>";
}


/**
 * add an utility box related entry to a drop down menu
 *
 * @param array $box parameters for that box (title, name, frame, icon, URL)
 *
 * @return NULL nothing
 */
function boxMenuEntry ( $box )
{
  global $__usetabs, $my_name;
  list ($title, $name, $frame, $fa, $url) = $box;

  echo "<li>";
  echo "<a href=\"$url\"", ($__usetabs ? " target=\"$my_name$frame\"" : ""), ">";
  echo "<i class=\"fa $fa fa-fw topmenu\"></i> ";
  echo $title;
  echo "</a>";
  echo "</li>";
}


/* ===========================================================================
 *
 * MAIN CODE
 *
 * ======================================================================== */

/**
 * parameters for utility boxes (hover text, param name, frame name, icon, URL, extra parameters)
 */
$searchBox = [
  $__[ "search" ] [ "values" ][ "search" ],
  $__[ "search" ] [ "params" ] [ "search" ],
  $__[ "search" ][ "names" ][ "frame" ],
  $__[ "search" ][ "values" ][ "icon" ],
  "search.php",
  "<input type=\"hidden\" name=\"" . $__[ "search" ][ "params" ] [ "case" ] . "\" value=\"on\"><input type=\"hidden\" name=\"" . $__[ "search" ][ "params" ] [ "areas" ] . "\" value=\"on\">" ];

$decodeBox = [
  $__[ "decode" ][ "values" ][ "decode" ],
  $__[ "decode" ][ "params" ][ "input" ],
  $__[ "decode" ][ "names" ][ "frame" ],
  $__[ "decode" ][ "values" ][ "icon" ],
  "decode.php",
  "" ];

$whoisBox = [
  $__[ "whois" ][ "values" ][ "whois" ],
  $__[ "whois" ][ "params" ][ "whois" ],
  $__[ "whois" ][ "names" ][ "frame" ],
  $__[ "whois" ][ "values" ][ "icon" ],
  "whois.php",
  "" ];


/*
 * define modal window providing some basic status info
 */
echo "<div class=\"modal fade\" id=\"", $__[ "include/topMenu" ][ "ids" ][ "statusModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><div class=\"alert alert-info\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-info-circle fa-2x\"></i></div><div class=\"msgText\"><strong>", _ ( "$my_name-Status" ), "</strong></div></div></div><div class=\"modal-body\"><span id=\"", $__[ "include/topMenu" ][ "ids" ][ "statusContent" ], "\"></span></div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-info\" data-dismiss=\"modal\">", _ ( "Schließen" ), "</button></div></div></div></div>";

/*
 * include javascript code to periodically update content of status window
 * the called script is supposed to return proper html that can be inserted in a span-tag
 */
echo "<script>function ", $__[ "include/topMenu" ][ "js" ][ "statusFunction" ], " { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) {";
echo "document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "statusContent" ], "\").innerHTML = xmlhttp.responseText; } }; xmlhttp.open(\"GET\",\"", $__[ "include/topMenu" ][ "urls" ][ "statusProvider" ], "\",true); xmlhttp.send(); } var myVar = setInterval (function () { ", $__[ "include/topMenu" ][ "js" ][ "statusFunction" ], " }, 1000); </script>";


/*
 * include javascript code to periodically update the visual state of certain buttons, including:
 *  - buttons to go online via LAN, WLAN, UMTS (enabling only when necessary hardware is detected)
 *  - button to go offline (enabling only when online)
 *  - record button (switching between starting or stopping a recording or ongoing database update)
 *
 * state information is provided by script which is called asynchronously
 */

echo "<script> function ", $__[ "include/topMenu" ][ "js" ][ "buttonFunction" ], " { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { var res = xmlhttp.responseText.split (\";\"); ";
echo "if (res[0]==2) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "lanOnline" ], "\").classList.remove(\"disabled\"); } else if (res[0]==1) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "lanOnline" ], "\").classList.add(\"disabled\"); } ";
echo "if (res[1]==1) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "wlanOnline" ], "\").classList.remove(\"disabled\"); } else { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "wlanOnline" ], "\").classList.add(\"disabled\"); } ";
echo "if (res[2]==1) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "umtsOnline" ], "\").classList.remove(\"disabled\"); } else { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "umtsOnline" ], "\").classList.add(\"disabled\"); } ";
echo "if (res[3]>0) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "memoryStick" ], "\").classList.remove(\"disabled\"); } else { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "memoryStick" ], "\").classList.add(\"disabled\"); } ";
echo "if (res[3]==2) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "memoryStick" ], "\").innerHTML='", $__[ "include/topMenu" ] [ "values" ] [ "unmountMemoryStick" ], " ", $__[ "include/topMenu" ] [ "values" ] [ "toolsUnmountMemoryStickMenuName" ], "</a>", "'; } else { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "memoryStick" ], "\").innerHTML='", $__[ "include/topMenu" ] [ "values" ] [ "mountMemoryStick" ], " ", $__[ "include/topMenu" ] [ "values" ] [ "toolsMountMemoryStickMenuName" ], "</a>", "'; } ";
echo "if (res[4]==1) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "offline" ], "\").classList.remove(\"disabled\"); } else { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "offline" ], "\").classList.add(\"disabled\"); }";
echo "if (document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "recordButton" ], "\").innerHTML != res[5]) { document.getElementById(\"", $__[ "include/topMenu" ][ "ids" ][ "recordButton" ], "\").innerHTML = res[5]; }";
echo "} }; xmlhttp.open(\"GET\",\"", $__[ "include/topMenu" ][ "urls" ][ "buttonProvider" ], "\",true);  xmlhttp.send(); } var myVar = setInterval (function () { ", $__[ "include/topMenu" ][ "js" ][ "buttonFunction" ], " }, 1000); </script>";


/*
 * open navbar container
 */

echo "<nav class=\"navbar navbar-default navbar-fixed-top\"><div class=\"container-fluid\"><div class=\"navbar-header\"><button type=\"button\" class=\"navbar-toggle collapsed\" data-toggle=\"collapse\" data-target=\"#", $__[ "include/topMenu" ][ "ids" ][ "navBar" ], "\"><span class=\"icon-bar\"></span><span class=\"icon-bar\"></span><span class=\"icon-bar\"></span></button></div><div class=\"collapse navbar-collapse\" id=\"", $__[ "include/topMenu" ][ "ids" ][ "navBar" ], "\"><ul class=\"nav navbar-nav\">";


/*
 * start or stop a recording
 */

if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
{
  $sfile = fopen ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ],
                   "r" );
  $sessionID = trim ( fgets ( $sfile ) );
  $sessionIP = trim ( fgets ( $sfile ) );
  $isRunning = trim ( fgets ( $sfile ) );
  fclose ( $sfile );
  $button = $isRunning ? $__[ "include/topMenu" ][ "values" ][ "recordStop" ] : $__[ "include/topMenu" ][ "values" ][ "recordEnd" ];
}
else
{
  $button = $__[ "include/topMenu" ][ "values" ][ "recordStart" ];
}
echo "<li id=\"", $__[ "include/topMenu" ][ "ids" ][ "recordButton" ], "\">$button</li>";


/*
 * status info button
 */

echo "<li>";
echo "<a href=\"#", $__[ "include/topMenu" ][ "ids" ][ "statusModal" ], "\" data-toggle=\"modal\">";
echo "<i class=\"fa ", $__[ "include/topMenu" ] [ "values" ] [ "statusMenuIcon" ], " fa-lg\"></i>";
echo "</a>";
echo "</li>";


/*
 * 
 * evaluations
 * 
 */

openMenu ( $__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuIcon" ],
           $__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuName" ] );

/*
 * recordings
 */
menuEntry ( "evaluateRecordings.php",
            "fa-database",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateMenuRecordingsMenuName" ] );

menuSeparator ();

/*
 * properties
 */
menuEntry ( "evaluateProperties.php",
            "fa-tablet",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluatePropertiesMenuName" ] );

/*
 * contents
 */
menuEntry ( "evaluateContents.php",
            "fa-file-o",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateContentsMenuName" ] );

/*
 * images
 */
menuEntry ( "evaluateImages.php",
            "fa-picture-o",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateImagesMenuName" ]  );

/*
 * metadata
 */
menuEntry ( "evaluateMetadata.php",
            "fa-tags",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateMetadataMenuName" ]  );

menuSeparator ();

/*
 * HTTP header
 */
menuEntry ( "evaluateHeaders.php",
            "fa-header",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateHeadersMenuName" ]  );

/*
 * cookies
 */
menuEntry ( "evaluateCookies.php",
            "fa-birthday-cake",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateCookiesMenuName" ]  );

/*
 * links
 */
menuEntry ( "evaluateLinks.php",
            "fa-exchange",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateLinksMenuName" ]  );

menuSeparator ();

/*
 * SSL encryption
 */
menuEntry ( "evaluateSslsuites.php",
            "fa-key",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateSSLMenuName" ]  );

/*
 * certificates
 */
menuEntry ( "evaluateCertificates.php",
            "fa-certificate",
            $__[ "include/topMenu" ] [ "values" ] [ "evaluateCertificatesMenuName" ]  );

closeMenu ();


/*
 * 
 * internet connection
 * 
 */
require_once ("probeHardware.php");
list ($wifi, $umts) = hasWifiUMTS ();
$ethernet = ethernetCable ();

openMenu ( "fa-globe",
           $__[ "include/topMenu" ] [ "values" ] [ "internetMenuName" ] );

/*
 * LAN
 */
if ( $ethernet )
{
  echo "<li id=\"", $__[ "include/topMenu" ][ "ids" ][ "lanOnline" ], "\"";
  echo $ethernet == 1 ? ">" : " class=\"disabled\">";
  echo "<a href=\"connectLAN.php\"><i class=\"fa ", $__[ "include/topMenu" ] [ "values" ] [ "internetLANMenuIcon" ], " fa-fw topmenu\"></i> ", $__[ "include/topMenu" ] [ "values" ] [ "internetLANMenuName" ], "</a></li>";
}

/*
 *  WLAN
 */
echo "<li id=\"", $__[ "include/topMenu" ][ "ids" ][ "wlanOnline" ], "\"";
echo $wifi ? ">" : " class=\"disabled\">";
echo "<a href=\"connectWLAN.php\"><i class=\"fa ", $__[ "include/topMenu" ] [ "values" ] [ "internetWLANMenuIcon" ], " fa-fw topmenu\"></i> ", $__[ "include/topMenu" ] [ "values" ] [ "internetWLANMenuName" ], "</a></li>";

/*
 * UMTS
 */
echo "<li id=\"", $__[ "include/topMenu" ][ "ids" ][ "umtsOnline" ], "\"";
echo $umts ? ">" : " class=\"disabled\">";
echo "<a href=\"connectUMTS.php\"><i class=\"fa ", $__[ "include/topMenu" ] [ "values" ] [ "internetUMTSMenuIcon" ], " fa-fw topmenu\"></i> ", $__[ "include/topMenu" ] [ "values" ] [ "internetUMTSMenuName" ], "</a></li>";

menuSeparator ();

/*
 * go offline
 */
echo "<li id=\"", $__[ "include/topMenu" ][ "ids" ][ "offline" ], "\"";
echo file_exists ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) ? ">" : " class=\"disabled\">";
echo "<a href=\"connectOffline.php\"><i class=\"fa ", $__[ "include/topMenu" ] [ "values" ] [ "internetOfflineMenuIcon" ], " fa-fw topmenu\"></i> ", $__[ "include/topMenu" ] [ "values" ] [ "internetOfflineMenuName" ], "</a></li>";

closeMenu ();


/*
 * 
 * tools
 * 
 */
openMenu ( "fa-cog",
           $__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ] );

/*
 * search
 */
boxMenuEntry ( $searchBox );

/*
 * decode
 */
boxMenuEntry ( $decodeBox );

/*
 * whois
 */
boxMenuEntry ( $whoisBox );

menuSeparator ();

/*
 * manage devices
 */
menuEntry ( "manageDevices.php",
            "fa-tablet",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsManageDevicesMenuName" ] );

/*
 * own certificate
 */
menuEntry ( "manageCertificate.php",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuIcon" ],
            $__[ "include/topMenu" ] [ "values" ] [ "toolsCertificateMenuName" ] );

menuSeparator ();

/*
 * import recording
 */
menuEntry ( "importRecording.php",
            "fa-download",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsImportMenuName" ] );

/*
 * clear database
 */
menuEntry ( "purgeDatabase.php",
            "fa-trash",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsPurgeMenuName" ] );


/*
 * memory stick
 */
require_once ("probeHardware.php");
$memoryStick = hasMemoryStick ();
echo "<li id=\"", $__[ "include/topMenu" ][ "ids" ][ "memoryStick" ], "\"";
echo $memoryStick ? ">" : " class=\"disabled\">";
echo $memoryStick == 2 ? ($__[ "include/topMenu" ] [ "values" ] [ "unmountMemoryStick" ] . " " . $__[ "include/topMenu" ] [ "values" ] [ "toolsUnmountMemoryStickMenuName" ] . "</a>") : ($__[ "include/topMenu" ] [ "values" ] [ "mountMemoryStick" ] . " " . $__[ "include/topMenu" ] [ "values" ] [ "toolsMountMemoryStickMenuName" ] . "</a>");

menuSeparator ();

/*
 * settings
 */
menuEntry ( "settings.php",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuIcon" ],
            $__[ "include/topMenu" ] [ "values" ] [ "toolsSettingsMenuName" ] );

/*
 * status
 */
menuEntry ( "systemStatus.php",
            "fa-info",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsStatusMenuName" ] );

/*
 * updates
 */
menuEntry ( "updates.php",
            "fa-flash",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsUpdateMenuName" ] );

/*
 * tutorial
 */
menuEntry ( "tutorial.php",
            "fa-book",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsTutorialMenuName" ] );
/*
 * about
 */
menuEntry ( "about.php",
            "fa-question",
            $__[ "include/topMenu" ] [ "values" ] [ "toolsAboutMenuName" ] . " $my_name" );

closeMenu ();


/*
 * 
 * power
 * 
 */

openMenu ( $__[ "include/topMenu" ] [ "values" ] [ "powerMenuIcon" ],
           $__[ "include/topMenu" ] [ "values" ] [ "powerMenuName" ] );

/*
 * shutdown
 */
menuEntry ( "powerOff.php?restart=0",
            "fa-power-off",
            _ ( "Herunterfahren" ) );

/*
 * restart
 */
menuEntry ( "powerOff.php?restart=1",
            "fa-refresh",
            _ ( "Neu starten" ) );

closeMenu ();

echo "</ul>";


/*
 * 
 * utility boxes (search etc)
 * 
 */

echo "<div class=\"visible-lg-block\">";

/*
 * whois
 */
if ( $__whoisbox )
{
  utilityBox ( $whoisBox );
}

/*
 * decode
 */
if ( $__dekodbox )
{
  utilityBox ( $decodeBox );
}

/*
 * search
 */
if ( $__suchbox )
{
  utilityBox ( $searchBox );
}

echo "</div>";


/*
 * close navbar
 */
echo "</div></div>";

/*
 * hide navbar in extra tabs (search etc)
 */
if ( isset ( $$__[ "include/openHTML" ][ "vars" ][ "frame" ] ) && $$__[ "include/openHTML" ][ "vars" ][ "frame" ] != "" )
{
  echo "<div class=\"navHider\"></div>";
}
echo "</nav>";


/*
 * open content div (to be closed in closeHTML.php)
 */
echo "<div class=\"container-fluid\">";

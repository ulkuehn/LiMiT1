<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file systemStatus.php
 * 
 * display the current status of a LiMiT1 system
 * the actual status information is collected by the corresponding script in include and updated by an ajax request
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
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

titleAndHelp ( _ ( "$my_name-Status" ),
                   _ ( "Auf dieser Seite werden verschiedene statische und dynamische Eigenschaften des Systems angezeigt." ) );

echo "<div class=\"row\"><div class=\"panel-group\" id=\"", $__[ "systemStatus" ][ "ids" ][ "statusPanel" ], "\" role=\"tablist\"><p class=\"text-center\"><i class=\"fa fa-circle-o-notch fa-spin fa-2x\"></i></p></div></div>";

echo "<script> function systemStatus() { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { document.getElementById(\"", $__[ "systemStatus" ][ "ids" ][ "statusPanel" ], "\").innerHTML = xmlhttp.responseText; } }; xmlhttp.open(\"GET\",\"include/status.php\",true); xmlhttp.send(); } systemStatus (); var myVar = setInterval (function () { systemStatus() }, 2500); </script>";

include ("include/closeHTML.php");

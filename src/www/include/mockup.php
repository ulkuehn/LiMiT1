<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/mockup.php
 * 
 * used to display a sample page reflecting a skin the user selects 
 * in the system settings
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

if ( isset ( $_GET[ $__[ "include/mockup" ][ "params" ][ "skin" ] ] ) )
{
  $__skin = $_GET[ $__[ "include/mockup" ][ "params" ][ "skin" ] ];
}

echo <<<LIMIT1
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
LIMIT1;

echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"", ($__skin == "" ? "/css/bootstrap.min.css" : "/$skin_dir/$__skin"), "\">";

echo <<<LIMIT1
    <link rel="stylesheet" type="text/css" href="/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="/css/limit1.css">
    <script type="text/javascript" src="/js/jquery.min.js"></script>
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>
  </head>
<body>
LIMIT1;

$__suchbox = 1;
$__dekodbox = 0;
$__whoisbox = 0;

require ("include/topMenu.php");

echo "<div class=\"row\">";
showInfoMessage ( _ ( "Dies ist eine Beispielseite" ) );
echo "</div>";

echo "<div class=\"row\">";
/*
 * sample buttons
 */
echo "<button class=\"btn btn-default\">", _ ( "Standard" ), "</button>";
echo "<button class=\"btn btn-primary\">", _ ( "Normal" ), "</button>";
echo "<button class=\"btn btn-info\">", _ ( "Info" ), "</button>";
echo "<button class=\"btn btn-success\">", _ ( "Erfolg" ), "</button>";
echo "<button class=\"btn btn-warning\">", _ ( "Warnung" ), "</button>";
echo "<button class=\"btn btn-danger\">", _ ( "Fehler" ), "</button>";
echo "</div><div class=\"row\"><p></p>";
/*
 * sample headers
 */
echo "<h1 style=\"display:inline\">", _ ( "Überschrift" ), " </h1>";
echo "<h2 style=\"display:inline\">", _ ( "Überschrift" ), " </h2>";
echo "<h3 style=\"display:inline\">", _ ( "Überschrift" ), " </h3>";
echo "<h4 style=\"display:inline\">", _ ( "Überschrift" ), " </h4>";
echo "<h5 style=\"display:inline\">", _ ( "Überschrift" ), " </h5>";
/*
 * sample text
 */
echo "</div><div class=\"row\"><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut laboreLorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. <span class=\"highlight\">Ut enimad minim veniam, quis nostrud exercitation ullamco laboris nisi utaliquip ex ea commodo consequat.</span> Duis aute irure dolor inreprehenderit in voluptate velit esse cillum dolore eu fugiat nullapariatur. Excepteur sint occaecat cupidatat non proident, sunt inculpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enimad minim veniam, quis nostrud exercitation ullamco laboris nisi utaliquip ex ea commodo consequat. Duis aute irure dolor inreprehenderit in voluptate velit esse cillum dolore eu fugiat nullapariatur. Excepteur sint occaecat cupidatat non proident, sunt inculpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enimad minim veniam, quis nostrud exercitation ullamco laboris nisi utaliquip ex ea commodo consequat. Duis aute irure dolor inreprehenderit in voluptate velit esse cillum dolore eu fugiat nullapariatur. Excepteur sint occaecat cupidatat non proident, sunt inculpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enimad minim veniam, quis nostrud exercitation ullamc</p></div>";

include ("include/closeHTML.php");

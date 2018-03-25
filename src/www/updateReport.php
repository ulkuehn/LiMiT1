<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file updateReport.php
 * 
 * report successful update
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

titleAndHelp ( _ ( "Update" ) );

$oldVersion = "";
if ( isset ( $_REQUEST[ $__[ "updateReport" ][ "params" ][ "oldVersion" ] ] ) )
{
  $oldVersion = _ ( " von Version " ) . $_REQUEST[ $_REQUEST[ $__[ "updateReport" ][ "params" ][ "oldVersion" ] ] ];
}

echo "<div class=\"row\">";
showInfoMessage ( _ ( "$my_name wurde$oldVersion auf $my_version aktualisiert." ) );
echo "</div>";

include ("include/closeHTML.php");



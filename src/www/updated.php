<?php

/**
 * project LiMiT1
 * file updated.php
 * 
 * report successful update
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");

titleAndHelp ( _ ( "Update" ) );

if ( isset ( $_REQUEST[ "old" ] ) )
{
  $old = $_REQUEST[ "old" ];
  echo "<div class=\"row\">";
  infoMsg ( _ ( "$my_name wurde von Version $old auf $my_version aktualisiert." ) );
  echo "</div>";
}

require ("include/htmlend.php");



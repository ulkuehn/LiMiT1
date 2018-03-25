<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/connectDB.php
 * 
 * used to open a database connection
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

try
{
  $db = new PDO ( "mysql:host=localhost;dbname=$database_name;charset=utf8",
                  "root",
                  "" );
} catch (PDOException $e)
{
#  header ( "Content-Type: text/html; charset=utf-8" );
#  echo "<html><body><h1>$my_name</h1><p>", _ ( "Keine Verbindung zur Datenbank \"$database_name\" möglich." ), "</p><p>", _ ( "Grund: " ), $e->getMessage (), "</p></body></html>";
#  exit;
}

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/database.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to open a database connection
//
//==============================================================================
//==============================================================================


require_once ("constants.php");
require_once ("configuration.php");

try
{
  $db = new PDO("mysql:host=localhost;dbname=$database_name;charset=utf8", "root", "");
}
catch (PDOException $e)
{
  header ("Content-Type: text/html; charset=utf-8");
  echo "<html><body><p>$my_name: Keine Verbindung zur Datenbank $database_name möglich.</p></body></html>";
  exit;
}

?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/inhalt.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display recorded content natively (as specified in its
//              MIME type)
//
//==============================================================================
//==============================================================================


require_once ("../include/constants.php");
require_once ("../include/configuration.php");
require_once ("../include/utility.php");
require_once ("../include/database.php");

$select_s = $db->prepare ("select * from ".$_REQUEST["typ"]." where id=?");
$select_s->execute (array($_REQUEST["id"]));

if ($info = $select_s->fetch())
{
  $select_s = $db->prepare ("select inhalt from inhalt where id=?");
  $select_s->execute (array($info["inhalt"]));
  if ($inhalt = $select_s->fetchColumn())
  {
    header ("Content-Type: ".$info["mime"].($info["mimeadd"]!=""? ";".$info["mimeadd"] : ""));
    echo $inhalt;
  }
}

?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/download.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to provide the download of an archived session
//
//==============================================================================
//==============================================================================


require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");
require ("../include/database.php");

$select_s = $db->prepare("select *,date_format(start,'%Y%m%d%H%i') as _start from aufzeichnung where id=?");
$select_s->execute(array($_GET["id"]));
if (($aufzeichnung = $select_s->fetch()) != false && $fd = fopen ($export_file, "r"))
{
  $fsize = filesize($export_file);
  header("Content-Type: application/zip");
  header("Content-Disposition: attachment; filename=\"".$my_name."_".$aufzeichnung["_start"].".zip\"");
  header("Content-length: $fsize");
  while(!feof($fd)) 
  {
    $buffer = fread($fd, 2048);
    echo $buffer;
  }
  fclose ($fd);
}

?>

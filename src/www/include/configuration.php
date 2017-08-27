<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/configuration.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to make available all configuration settings to php scripts
//              (configurations being values that are user editable)
//
//==============================================================================
//==============================================================================


$_configuration = "configuration";

$basedir = pathinfo($_SERVER["DOCUMENT_ROOT"])['dirname'];

// read configuration
if (is_readable($basedir."/".$_configuration))
{
  $cfile = fopen ($basedir."/".$_configuration, "r");
  while (($line = fgets ($cfile)) !== false) 
  {
    $line = trim ($line);
    if ($line != "" && substr ($line, 0, 1) != "#")
    {
      eval ("\$$line;");
    }
  }
  fclose ($cfile);
}
?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/constants.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to make available all constants settings to php scripts
//              (constants being values that are not user editable)
//
//==============================================================================
//==============================================================================


$_constants = "constants";
$basedir = pathinfo($_SERVER["DOCUMENT_ROOT"])['dirname'];

// evaluate script constants
if (is_readable($basedir."/".$_constants))
{
  $cfile = fopen ($basedir."/".$_constants, "r");
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

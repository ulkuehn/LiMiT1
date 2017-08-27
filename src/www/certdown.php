<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: certdown.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to serve the certificate file for downloading it to
//              a target platform like a smartphone
//
//==============================================================================
//==============================================================================


require_once ("include/constants.php");
require_once ("include/configuration.php");

if (file_exists($cert_file))
{
  header("Content-Disposition: attachment; filename=\"".pathinfo($cert_file)["basename"]."\"");
  header("Content-Type: application/octet-stream");
  
  $file = fopen ($cert_file,"rb");

  while (!feof($file)) 
  {
    print (fread($file, 1024));
    ob_flush();
    flush();
  }
  
  fclose ($file);
}

?>

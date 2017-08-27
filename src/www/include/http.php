<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/http.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to provide http headers for all php scripts
//
//==============================================================================
//==============================================================================


if ($__debug)
{
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $starttime = $mtime[1] + $mtime[0];
}

// HTTP
header ("Content-Type: text/html; charset=utf-8");
header ("Connection: keep-alive");
header ("Expires: 0");
?>

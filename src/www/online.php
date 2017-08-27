<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: online.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to bring the LiMiT1 system online
//              this script executes a readily configured shell script
//              that contains the commands for bringing the system online
//              the method to go online (lan, wifi, ...) is sepecified
//              beforehand in the respective scripts
//              this script verifies that online access is indeed
//              accomplished by checking the system time provided by an
//              online ntp server
//
//==============================================================================
//==============================================================================


require ("include/constants.php");
require ("include/configuration.php");

$ok = 0;

if (file_exists ($online_script))
{
  exec ("/bin/bash $online_script");
  sleep (2);

  $fp = fsockopen("udp://pool.ntp.org", 123, $errno, $errstr, 10);
  if ($fp)
  {
    fclose($fp);
    
    exec ("/usr/sbin/service ntp stop");
    exec ("/bin/date -s \"01/01/2000 00:00:00\"");
    exec ("/usr/sbin/service ntp start");
    
    $to = 60;
    while ($to && strftime ("%Y") == "2000")
    {
      sleep (1);
      $to--;
    }
    
    if (strftime ("%Y") != "2000")
    {
      $ok = 1;
    }
  }
  unlink ($online_script);
}

if (!$ok && file_exists($offline_script))
{
  exec ("/bin/bash $offline_script");
  unlink ($offline_script);
}

if ($ok)
{
  touch ($online_flag);
}
  
echo $ok;
  
?>

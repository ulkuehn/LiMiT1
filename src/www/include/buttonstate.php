<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/buttonstate.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to asynchronously change the state and appearance of 
//              several buttons depending on plugged-in hardware
//              and system state
//
//==============================================================================
//==============================================================================


require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");
require ("../include/hardware.php");

// ethernet cable?
echo ethernetCable () ? "1;" : "0;";

// wifi and umts?
list ($wifi, $umts) = hasWifiUMTS ();
echo $wifi ? "1;" : "0;";
echo $umts ? "1;" : "0;";

// online or offline?
echo file_exists ( $offline_script ) ? "1;" : "0;";

// running session?
if ( file_exists ( $session_file ) )
{
  $sfile = fopen ( $session_file, "r" );
  $aufzeichnungID = trim ( fgets ( $sfile ) );
  $ip = trim ( fgets ( $sfile ) );
  $running = trim ( fgets ( $sfile ) );
  fclose ( $sfile );
  echo $running ? $recordStop : $recordEnd;
}
else
{
  echo $recordStart;
}
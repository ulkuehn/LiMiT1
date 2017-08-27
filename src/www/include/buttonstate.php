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

# ethernet cable plugged in?
if (file_exists ("/sys/class/net/$wired_interface/carrier"))
{
  $cfile = fopen ("/sys/class/net/$wired_interface/carrier", "r");
  echo trim (fgets ($cfile)), ";";
  fclose ($cfile);
}
else
{
  echo "0;";
}

# wifi or umts adapter present?
$wifi = 0;
$umts = 0;
foreach (scandir("/sys/class/net") as $interface)
{
  if ($interface != $wired_interface && $interface != $wireless_interface)
  {
    unset ($udev);
    exec ("/bin/udevadm info -q property /sys/class/net/$interface", $udev, $ret);
    foreach ($udev as $info)
    {
      if ($info == "DEVTYPE=wlan")
      {
        $wifi = 1;
      }
      if ($info == "DEVTYPE=wwan" || $info == "ID_USB_DRIVER=cdc_ether")
      {
        $umts = 1;
      }
    }
  }
}
echo "$wifi;$umts;";

# online or offline?
echo file_exists ($offline_script)? "1;" : "0;";

# running session?
if (file_exists ($session_file))
{
  $sfile = fopen ($session_file, "r");
  $aufzeichnungID = trim (fgets ($sfile));
  $ip = trim (fgets ($sfile));
  $running = trim (fgets ($sfile));
  fclose ($sfile);
  echo $running? $recordStop : $recordEnd;
}
else
{
  echo $recordStart;
}

?>

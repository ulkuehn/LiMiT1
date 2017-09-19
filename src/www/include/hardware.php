<?php

/**
 * project LiMiT1
 * file include/hardware.php
 * 
 * define functions probing network related hardware
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

/**
 * determine if ethernet cable is plugged in
 * 
 * @return boolean TRUE if cable detected
 */
function ethernetCable ()
{
  global $wired_interface;

  $hasCable = FALSE;
  if ( file_exists ( "/sys/class/net/$wired_interface/carrier" ) )
  {
    $cfile = fopen ( "/sys/class/net/$wired_interface/carrier", "r" );
    $hasCable = trim ( fgets ( $cfile ) ) == "1";
    fclose ( $cfile );
  }
  return $hasCable;
}

/**
 * determine if wifi or umts adapter present
 * 
 * @return array wifi and umts presence as array of booleans
 */
function hasWifiUMTS ()
{
  global $wired_interface, $wireless_interface;

  $wifi = FALSE;
  $umts = FALSE;
  foreach ( scandir ( "/sys/class/net" ) as $interface )
  {
    if ( $interface != $wired_interface && $interface != $wireless_interface )
    {
      unset ( $udev );
      exec ( "/bin/udevadm info -q property /sys/class/net/$interface", $udev, $ret );
      foreach ( $udev as $info )
      {
        if ( $info == "DEVTYPE=wlan" )
        {
          $wifi = TRUE;
        }
        if ( $info == "DEVTYPE=wwan" || $info == "ID_USB_DRIVER=cdc_ether" )
        {
          $umts = TRUE;
        }
      }
    }
  }
  return array ( $wifi, $umts );
}

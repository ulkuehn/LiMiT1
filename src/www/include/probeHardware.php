<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/probeHardware.php
 * 
 * define functions probing network related hardware
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * determine if ethernet cable is plugged in
 * 
 * @return 0 if no ethernet adapter, 1 if no cable detected, 2 if cable detected
 */
function ethernetCable ()
{
  global $wired_interface;

  $returnValue = 0;
  if ( file_exists ( "/sys/class/net/$wired_interface/address" ) )
  {
    if ( file_exists ( "/sys/class/net/$wired_interface/carrier" ) )
    {
      $carrierFH = fopen ( "/sys/class/net/$wired_interface/carrier",
                           "r" );
      $returnValue = trim ( fgets ( $carrierFH ) ) == "1" ? 2 : 1;
      fclose ( $carrierFH );
    }
  }
  return $returnValue;
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
      exec ( "/bin/udevadm info -q property /sys/class/net/$interface",
             $udev,
             $ret );
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
  return array (
    $wifi,
    $umts );
}


/**
 * determine if memory stick is plugged in and mounted
 * 
 * @return 0 if no memory stick plugged in, 1 if plugged in but not mounted, 2 if mounted
 */
function hasMemoryStick ()
{
  global $data_dir;

  $returnValue = 0;
  if ( file_exists ( "/sys/class/block/sda1" ) )
  {
    /*
     * usb stick mounted on data_dir?
     */
    exec ( "/bin/findmnt $data_dir",
           $findmnt,
           $findmntRV );
    $returnValue = $findmntRV ? 1 : 2;
  }
  return $returnValue;
}


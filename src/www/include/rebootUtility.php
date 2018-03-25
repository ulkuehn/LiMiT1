<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/rebootUtility.php
 * 
 * common definitions needed for all scripts that initiate a reboot
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * wait for the system to reboot
 * 
 * inserts JS code to do the following:
 * - waits for the system to go down
 * - as soon as the system reappears redirects client to landing page $openURL
 * - while waiting a div is updated with more and more $fa icons to visualize progress
 * 
 * @param string $updateID id of the div to update
 * @param string $fontAwesomeName name of the icon to use for visual update
 * @param string $openURL URL to open after reboot (relative to webroot)
 */
function waitForReboot ( $updateID,
                         $fontAwesomeName,
                         $openURL = "" )
{
  /*
   * script that (kind of) pings system server, waits for system to first go down and then reappear later
   * once the system reappears after having been down, the browser is redirected to openURL
   */
  echo <<<LIMIT1
  <script>
    var wasDown = 0;
    function pingForReboot()
    {
      $.ajax ({
                url: window.location.protocol + "//" + window.location.hostname + "/index.php",
                timeout: 1000,
                success: function (result)
                         {
                           if (wasDown == 1)
                           {
                             window.location.assign(window.location.protocol+"//"+window.location.hostname+"$openURL");
                           }
                           else
                           {
                             setTimeout (pingForReboot,1000); 
                           }
                         },     
                error: function (result)
                       {
                         wasDown = 1;
                         setTimeout (pingForReboot,1000); 
                       }
              });
    }
    pingForReboot();
    setInterval (function () { document.getElementById("$updateID").innerHTML += "<i class=\"fa $fontAwesomeName\"></i> "; }, 2000);
    </script>
LIMIT1;
}


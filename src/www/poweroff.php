<?php

/**
 * project LiMiT1
 * file poweroff.php
 * 
 * used to power the LiMiT1 system off or reboot it
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
/**
 * form parameter for restart
 */
$restart = "restart";
/**
 * form parameter for shutdown
 */
$shutdown = "shutdown";
/**
 * id of div to update while rebooting etc
 */
$bye = "bye";

require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");

/**
 * say good bye to the user
 * 
 * @param string $title title of the good bye panel 
 * @param string $text text within the panel
 */
function bye ( $title, $text )
{
  global $bye;

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">$title</h4>
      </div>
      <div class="panel-body" id="$bye">
        <h1>$text</h1>
      </div>
    </div>
  </div> 
LIMIT1;
}

/*
 * no shutdown possible while recording is ongoing
 */
if ( file_exists ( $session_file ) )
{
  titleAndHelp ( $_REQUEST[ $restart ] == 1 ? _ ( "Neu starten" ) : _ ( "Herunterfahren" ), "" );
  errorMsg ( _ ( "Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor $my_name " ) . ($_REQUEST[ $restart ] == 1 ? _ ( "neu gestartet" ) : _ ( "heruntergefahren" )) . _ ( " werden kann." ) );
}
/*
 * no recording going on
 */
else
{
  /*
   * shutdown
   */
  if ( array_key_exists ( $shutdown, $_POST ) )
  {
    bye ( _ ( "$my_name wird beendet" ), _ ( "Tschüss und bis zum nächsten Mal !" ) );
    /*
     * script to monitor shutdown process
     */
    $powerOff = _ ( "Die Stromversorgung von $my_name kann jetzt ausgeschaltet werden." );
    echo <<<LIMIT1
  <script>
    function ping()
    {
      $.ajax ({
                url: window.location.protocol + "//" + window.location.hostname + "/index.php",
                timeout: 5000,
                success: function (result)
                         {
                           ping();
                         },     
                error: function (result)
                       {
                         clearInterval(iv);
                         document.getElementById("$bye").innerHTML += "<br><br><div class=\"alert alert-warning\" role=\"alert\"><strong>$powerOff</strong></div>";
                       }
              });
    }
    ping();
    var iv = setInterval (function () { document.getElementById("$bye").innerHTML += "<i class=\"fa fa-heart\"></i> "; }, 2000);
    </script>
LIMIT1;
  }
  /*
   * restart
   */
  else if ( array_key_exists ( $restart, $_POST ) )
  {
    bye ( _ ( "$my_name startet neu" ), _ ( "$my_name ist in Kürze zurück ..." ) );
    waitForReboot ( $bye, "fa-heart" );
  }
  /*
   * confirmation screens
   */
  else
  {
    /*
     * restart
     */
    if ( $_GET[ $restart ] == 1 )
    {
      titleAndHelp ( _ ( "Neu starten" ), _ ( "Mit dieser Funktion wird $my_name neu gestartet" ) );
      echo <<<LIMIT1
  <form method="post">
    <div class="row">
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h4 class="panel-title">
LIMIT1;
      echo _ ( "$my_name neu starten" );
      echo <<<LIMIT1
          </h4>
        </div>
        <div class="panel-body">
LIMIT1;
      infoMsg ( _ ( "Der Neustart benötigt einige Zeit. Das Browserfenster kann währenddessen geöffnet bleiben." ) );
      echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
      echo _ ( "Neu starten" );
      echo "\" name=\"$restart\">";
      echo <<<LIMIT1
        </div>
      </div>
    </div>
  </form>
LIMIT1;
    }
    /*
     * shutdown
     */
    else
    {
      titleAndHelp ( _ ( "Herunterfahren" ), _ ( "Mit dieser Funktion wird $my_name heruntergefahren" ) );
      echo <<<LIMIT1
  <form method="post">
    <div class="row">
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h4 class="panel-title">
LIMIT1;
      echo _ ( "$my_name herunterfahren" );
      echo <<<LIMIT1
          </h4>
        </div>
        <div class="panel-body">
LIMIT1;
      infoMsg ( _ ( "Nach dem Herunterfahren kann die Stromversorgung von $my_name abgeschaltet werden." ) );
      echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
      echo _ ( "Herunterfahren" );
      echo "\" name=\"$shutdown\">";
      echo <<<LIMIT1
        </div>
      </div>
    </div>
  </form>
LIMIT1;
    }
  }
}

require ("include/htmlend.php");

/*
 * system commands
 */
if ( array_key_exists ( $shutdown, $_POST ) )
{
  exec ( "/bin/umount $mount_dir" );
  exec ( "(/bin/sleep 2 && /sbin/halt) > /dev/null 2>&1 &" );
}
else if ( array_key_exists ( $restart, $_POST ) )
{
  exec ( "/bin/umount $mount_dir" );
  exec ( "(/bin/sleep 2 && /sbin/reboot) > /dev/null 2>&1 &" );
}

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: poweroff.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to power the LiMiT1 system off or reboot it
//              on reboot the script keeps its connection with the browser
//
//==============================================================================
//==============================================================================


require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");

function bye ( $titel, $text )
{
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">$titel</h4>
      </div>
      <div class="panel-body" id="bye">
        <h1>$text</h1>
      </div>
    </div>
  </div> 
LIMIT1;
}

if ( file_exists ( $session_file ) )
{
  titleAndHelp ( $_REQUEST[ "restart" ] == 1 ? "Neu starten" : "Herunterfahren", "" );
  errorMsg ( "Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor $my_name " . ($_REQUEST[ "restart" ] == 1 ? "neu gestartet" : "heruntergefahren") . " werden kann." );
}
else
{
  if ( array_key_exists ( "shutdown", $_POST ) )
  {
    bye ( "$my_name wird beendet", "Tschüss und bis zum nächsten Mal !" );
  }
  else if ( array_key_exists ( "restart", $_POST ) )
  {
    bye ( "$my_name startet neu", "$my_name ist in Kürze zurück ..." );
    waitForReboot ( "bye", "fa-heart" );
  }
  else
  {
    if ( $_GET[ "restart" ] == 1 )
    {
      titleAndHelp ( "Neu starten", "Mit dieser Funktion wird $my_name neu gestartet" );
      echo <<<LIMIT1
  <form method="post">
    <div class="row">
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h4 class="panel-title">$my_name neu starten</h4>
        </div>
        <div class="panel-body">
LIMIT1;
      infoMsg ( "Der Neustart benötigt einige Zeit. Das Browserfenster kann währenddessen geöffnet bleiben." );
      echo <<<LIMIT1
          <input type="submit" class="btn btn-primary" value="Neu starten" name="restart">
        </div>
      </div>
    </div>
  </form>
LIMIT1;
    }
    else
    {
      titleAndHelp ( "Herunterfahren", "Mit dieser Funktion wird $my_name heruntergefahren" );
      echo <<<LIMIT1
  <form method="post">
    <div class="row">
      <div class="panel panel-primary">
        <div class="panel-heading">
          <h4 class="panel-title">$my_name herunterfahren</h4>
        </div>
        <div class="panel-body">
LIMIT1;
      infoMsg ( "Nach dem Herunterfahren kann die Stromversorgung von $my_name abgeschaltet werden." );
      echo <<<LIMIT1
          <input type="submit" class="btn btn-primary" value="Herunterfahren" name="shutdown">
        </div>
      </div>
    </div>
  </form>
LIMIT1;
    }
  }
}

require ("include/htmlend.php");


if ( array_key_exists ( "shutdown", $_POST ) )
{
  exec ( "/bin/umount $mount_dir" );
  exec ( "(/bin/sleep 3 && /sbin/halt) > /dev/null 2>&1 &" );
}
else if ( array_key_exists ( "restart", $_POST ) )
{
  exec ( "/bin/umount $mount_dir" );
  exec ( "(/bin/sleep 3 && /sbin/reboot) > /dev/null 2>&1 &" );
}
?>

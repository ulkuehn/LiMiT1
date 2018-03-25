<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/openHTML.php
 * 
 * used to provide html headers etc for all php scripts
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

function errorRow ( $what,
                    $result = "" )
{
  echo "<p><strong>$what</strong>";
  if ( $result != "" )
  {
    echo "<br>$result";
  }
  echo "</p>";
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * doctype, viewport
 */
echo "<!DOCTYPE html><html lang=\"", _ ( "de" ), "\"><head><meta charset=\"utf-8\"><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">";
/*
 * bootstrap css
 */
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/bootstrap.min.css\">";
/*
 * skin css
 */
if ( $__skin != "" )
{
  echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$skin_dir$__skin\">";
}
/*
 * font awesome css
 */
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/font-awesome.min.css\"><link rel=\"stylesheet\" type=\"text/css\" href=\"css/datatables.min.css\">";
/*
 * own css
 */
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/limit1.css\">";

/*
 * jquery, datatables, bootstrap js
 */
echo "<script type=\"text/javascript\" src=\"js/jquery.min.js\"></script><script type=\"text/javascript\" src=\"js/bootstrap.min.js\"></script><script type=\"text/javascript\" src=\"js/datatables.min.js\"></script>";
/*
 * own mouseover, mouseout js for whois highlighting
 */
echo "<script type=\"text/javascript\">";
echo "function ", $__[ "include/openHTML" ] [ "js" ][ "mouseOverFunc" ], " (theDiv, theClass) { var ok = 0; $(theDiv).parent().children(\"div\").each ( function () { if (this==theDiv) { ok = 1; } if (ok) { $(this).addClass( theClass ); } }); }; ";
echo "function ", $__[ "include/openHTML" ] [ "js" ][ "mouseOutFunc" ], " (theDiv, theClass) { $(theDiv).parent().children(\"div\").each ( function () { $(this).removeClass( theClass ); }); }; ";
/*
 * frame management
 */
echo "window.name = \"$my_name", isset ( $$__[ "include/openHTML" ][ "vars" ][ "frame" ] ) ? $$__[ "include/openHTML" ][ "vars" ][ "frame" ] : "", "\"; </script>";
/*
 * title
 */
echo "<title>$my_name", isset ( $$__[ "include/openHTML" ][ "vars" ][ "title" ] ) ? $$__[ "include/openHTML" ][ "vars" ][ "title" ] : "", "</title></head><body>";

/*
 * still booting?
 */
$bootingFile = $temp_dir . "/" . $__[ "include/openHTML" ] [ "values" ] [ "bootingFile" ];
if ( file_exists ( $bootingFile ) )
{
  $bootTime = time () - stat ( $bootingFile )[ "ctime" ];
  $waitTime = 3 * 60 - $bootTime;
  if ( $waitTime > 0 )
  {
    echo "<script>setTimeout ( function() { location=location; }, 2000 );</script>";
    echo "<div class=\"container\"><div class=\"jumbotron\"><h2>", _ ( "$my_name bootet noch ..." ), "</h2><hr><p>", _ ( "bootet seit $bootTime Sekunden" ), "</p><p>", _ ( "Abbruch spätestens in $waitTime Sekunden" ), "</p></div></div></body></html>";
    exit;
  }
}

/*
 * splash modal
 */
$bootFile = $temp_dir . "/" . $__[ "include/openHTML" ] [ "values" ] [ "bootFile" ];
if ( file_exists ( $bootFile ) )
{
  $bootFileFH = fopen ( $bootFile,
                        "r" );
  $bootError = intval ( fgets ( $bootFileFH ) );
  fclose ( $bootFileFH );
  /*
   * do splash only once in a boot cycle (but do not delete file if "index.php" was called explicitely, as this is  done only during reboot to check for system coming alive again)
   */
  if ( $_SERVER[ "REQUEST_URI" ] != "/index.php" )
  {
    unlink ( $bootFile );
  }

  echo "<div class=\"modal fade\" id=\"", $__[ "include/openHTML" ][ "ids" ][ "splashModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\">";

  /*
   * everything okay
   */
  if ( !$bootError && !file_exists ( $bootingFile ) )
  {
    echo "<div class=\"modal-header\"><div class=\"alert alert-success\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-rocket fa-2x\"></i></div><div class=\"msgText\"><strong>", _ ( "Willkommen bei $my_name" ), "</strong></div></div></div>";
    echo "<div class=\"modal-body\">";
    echo _ ( "<p>$my_name Version $my_version wurde erfolgreich gestartet. Die Systemzeit ist " ), strftime ( "%d.%m.%Y, %H:%M:%S" ), ". <span id=\"", $__[ "include/openHTML" ][ "ids" ][ "timeInfo" ], "\"></span></p>";
    echo "<script>var clientServerTimeDiff=Math.abs(Math.floor(Date.now()/1000)-", time (), "); if (clientServerTimeDiff<2*60) { document.getElementById('", $__[ "include/openHTML" ][ "ids" ][ "timeInfo" ], "').innerHTML='", _ ( "Dies entspricht ungefähr der aktuellen Zeit." ), "'; } else { var now=new Date(); document.getElementById('", $__[ "include/openHTML" ][ "ids" ][ "timeInfo" ], "').innerHTML='", _ ( "Dies weicht um " ), "'+(clientServerTimeDiff<2*60?clientServerTimeDiff+'", _ ( " Sekunden" ), "':clientServerTimeDiff<2*3600?Math.floor(clientServerTimeDiff/60)+'", _ ( " Minuten" ), "':clientServerTimeDiff<2*24*3600?Math.floor(clientServerTimeDiff/3600)+'", _ ( " Stunden" ), "':Math.floor(clientServerTimeDiff/24/3600)+'", _ ( " Tage" ), "')+'", _ ( " von der aktuellen Zeit " ), "('+(now.getDate()<=9?'0':'')+now.getDate()+'.'+(now.getMonth()<9?'0':'')+(now.getMonth()+1)+'.'+now.getFullYear()+', '+(now.getHours()<=9?'0':'')+now.getHours()+':'+(now.getMinutes()<=9?'0':'')+now.getMinutes()+':'+(now.getSeconds()<=9?'0':'')+now.getSeconds()+')", _ ( " ab und wird bei der nächsten Verbindung zum Internet aktualisiert." ), "'; }</script>";
    echo "<p>", _ ( "Für erste Bedienungshinweise steht ein Tutorial zur Verfügung. Das Tutorial kann auch jederzeit über das Menü " ), "\"", $__[ "include/topMenu" ] [ "values" ] [ "toolsMenuName" ], "\"", _ ( " aufgerufen werden." ), "</p>";
    echo "</div>";
    echo "<div class=\"modal-footer\"><a class=\"btn btn-success\" href=\"tutorial.php\">", _ ( "Tutorial anzeigen" ), "</a><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Schließen" ), "</button></div></div></div></div>";
  }

  /*
   * some error(s)
   */
  else
  {
    echo "<div class=\"modal-header\"><div class=\"alert alert-danger\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-rocket fa-2x fa-flip-vertical\"></i></span></div><div class=\"msgText\"><strong>", _ ( "Beim Start von $my_name ist leider etwas schiefgegangen" ), "</strong></div></div></div>";
    echo "<div class=\"modal-body\">";

    if ( file_exists ( $bootingFile ) )
    {
      unlink ( $bootingFile );
      errorRow ( _ ( "Der Bootprozess wurde (noch) nicht abgeschlossen" ),
                     _ ( "Der Bootprozess wurde fehlerhaft abgebrochen oder dauert sehr lange (z.B. weil auf einem USB-Stick eine Datenbank eingerichtet werden muss." ) );
    }
    if ( $bootError & 1 << $errorNoSSHD )
    {
      errorRow ( _ ( "Der ssh-Server konnte nicht gestartet werden" ),
                     _ ( "Es ist kein remote-Shell-Zugriff möglich. Dieser wird für den normalen Betrieb nicht benötigt." ) );
    }
    if ( $bootError & 1 << $errorNoNamed )
    {
      errorRow ( _ ( "Der Name-Server konnte nicht gestartet werden" ),
                     _ ( "$my_name ist nur per IP-Adresse erreichbar. Domainnamen können nicht aufgelöst werden. Gespeicherte Aufzeichnungen können ausgewerten werden. Neue Aufzeichnungen sind nicht möglich." ) );
    }
    if ( $bootError & 1 << $errorNoRouting )
    {
      errorRow ( _ ( "Das Routing konnte nicht aktiviert werden" ),
                     _ ( "$my_name kann angeschlossenen Geräten keine Verbindung ins Internet vermitteln. Gespeicherte Aufzeichnungen können ausgewerten werden. Neue Aufzeichnungen sind nicht möglich." ) );
    }
    if ( $bootError & 1 << $errorNoDataDir )
    {
      errorRow ( _ ( "Es konnte kein Verzeichnis für Datenbankdateien angelegt werden" ),
                     _ ( "Es sind keine Aufzeichnungen vorhanden. Neue Aufzeichnungen sind nicht möglich." ) );
    }
    if ( $bootError & 1 << $errorNoBaseTables1 )
    {
      errorRow ( _ ( "Das Datenbanksystem konnte auf der SD-Karte nicht initialisiert werden" ),
                     _ ( "Auf der SD-Karte können keine Aufzeichnungen angelegt werden." ) );
    }
    if ( $bootError & 1 << $errorNoMysqld1 )
    {
      errorRow ( _ ( "Der Datenbankserver konnte mit den Dateien auf der SD-Karte nicht gestartet werden" ),
                     _ ( "Auf der SD-Karte ist kein Zugriff auf Aufzeichnungen möglich; neue Aufzeichnungen können nicht angelegt werden. Möglicherweise ist die SD-Karte nicht in Ordnung." ) );
    }
    if ( $bootError & 1 << $errorNoDatabase1 )
    {
      errorRow ( _ ( "Die $my_name-Datenbank konnte auf der SD-Karte nicht angelegt werden" ),
                     _ ( "Auf der SD-Karte ist kein Zugriff auf Aufzeichnungen möglich; neue Aufzeichnungen können nicht angelegt werden. Möglicherweise ist die SD-Karte nicht in Ordnung." ) );
    }
    if ( $bootError & 1 << $errorMysqld )
    {
      errorRow ( _ ( "Der Datenbankserver konnte nicht angehalten werden" ),
                     _ ( "Der eingesteckte USB-Stick wurde nicht eingebunden. Die Speicherung erfolgt auf SD-Karte." ) );
    }
    if ( $bootError & 1 << $errorNoMount )
    {
      errorRow ( _ ( "Der USB-Stick konnte nicht eingebunden werden" ),
                     _ ( "Der eingesteckte USB-Stick wurde nicht eingebunden. Die Speicherung erfolgt auf SD-Karte. Evtl. ist der USB-Stick nicht FAT32-formatiert." ) );
    }
    if ( $bootError & 1 << $errorNoBaseTables2 )
    {
      errorRow ( _ ( "Das Datenbanksystem konnte auf dem eingesteckten USB-Stick nicht initialisiert werden" ),
                     _ ( "Der eingesteckte USB-Stick wurde nicht eingebunden. Die Speicherung erfolgt auf SD-Karte." ) );
    }
    if ( $bootError & 1 << $errorNoMysqld2 )
    {
      errorRow ( _ ( "Der Datenbankserver konnte mit den Dateien auf dem eingesteckten USB-Stick nicht gestartet werden" ),
                     _ ( "Der eingesteckte USB-Stick wurde nicht eingebunden. Die Speicherung erfolgt auf SD-Karte." ) );
    }
    if ( $bootError & 1 << $errorNoDatabase2 )
    {
      errorRow ( _ ( "Die $my_name-Datenbank konnte auf dem eingesteckten USB-Stick nicht angelegt werden" ),
                     _ ( "Der eingesteckte USB-Stick wurde nicht eingebunden. Die Speicherung erfolgt auf SD-Karte." ) );
    }

    echo "</div><div class=\"modal-footer\"><div class=\"row\"><div class=\"col-sm-7 text-left\"><a class=\"btn btn-danger btn-sm\" style=\"margin-right:5px;\" href=\"powerOff.php?", $__[ "powerOff" ] [ "params" ] [ "restart" ], "=1\">", _ ( "$my_name neu starten" ), "</a><a class=\"btn btn-danger btn-sm\" href=\"powerOff.php?", $__[ "powerOff" ] [ "params" ] [ "shutdown" ], "=1\">", _ ( "$my_name ausschalten" ), "</a></div><div class=\"col-sm-5\"><button type=\"button\" class=\"btn btn-default btn-sm\" data-dismiss=\"modal\">", _ ( "Ohne Neustart fortfahren" ), "</button></div></div></div></div></div></div>";
  }
  echo "<script>$('#", $__[ "include/openHTML" ][ "ids" ][ "splashModal" ], "').modal({backdrop:'static'})</script>";
}  
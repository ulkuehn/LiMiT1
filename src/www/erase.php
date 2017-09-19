<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: erase.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to purge all recorded sessions from the database
//              of the LiMiT1 system
//              after this operation there is no more session on the system
//              aditionally all whois data and all device data can be erased
//
//==============================================================================
//==============================================================================


$_loeschen = "Datenbank leeren";

$tables = array ( "whois" => array ("whois"), // Tabellen für Whois-Informationen
                  "geraete" => array ("geraet","eigenschaft"), // Tabellen für Geräteinformationen
                  "system" => array ("cipherSuite","keyExchange","cipher","mac") // Systemtabellen, die nicht gelöscht werden dürfen
                );
   
require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");
require ("include/database.php");

titleAndHelp ("Datenbank leeren", <<<LIMIT1
Wenn sämtliche Aufzeichnungen gelöscht werden sollen, ist das Löschen der Datenbank der schnellste Weg.
Optional können auch die Geräte und die Whois-Informationen entfernt werden.
LIMIT1
);

echo "<div class=\"row\">";

if (isset($_POST["loeschen"]))
{
  echo <<<LIMIT1
  <div id="past">
  </div>
  <div id="current">
  </div>
LIMIT1;

  $db->beginTransaction ();

  $ifile = fopen ($database_initfile, "r");
  while (($line = fgets ($ifile)) !== false) 
  {
    $create .= $line;
    if (preg_match ("/;$/", trim($line)))
    {
      if ($do)
      {
        echo "<script>";
        echo "document.getElementById(\"current\").innerHTML = \"",jsSave (progressMsg ("Die Tabelle \"$tname\" wird geleert",false), false),"\";";
        echo "</script>";
        ob_flush(); flush();
        $db->query ("drop table $tname"); // drop
        $db->query ($create); // recreate
        echo "<script>";
        echo "document.getElementById(\"current\").innerHTML = '';";
        echo "document.getElementById(\"past\").innerHTML += \"",jsSave (successMsg ("Die Tabelle \"$tname\" wurde geleert",false), false),"\";";
        echo "</script>";
        ob_flush(); flush();
      }
    }
    if (preg_match ("/create table.* ([a-z0-9_]+)$/i", trim($line), $m))
    {
      $tname = $m[1];
      $create = $line;
      $do = true;
      foreach ($tables as $sect=>$names)
      {
        if ( !( (isset($_POST["whois"]) && $sect=="whois") || (isset($_POST["geraete"]) && $sect=="geraete") ) )
        {
          foreach ($names as $i=>$name)
          {
            if ($name == $tname)
            {
              $do = false;
            }
          }
        }
      }
    }
  }
  fclose ($ifile);

  $db->commit ();
  successMsg ("Die Datenbank wurde geleert");
}

else
{
  echo <<<LIMIT1
  <form method="post">
    <div class="panel panel-danger">
      <div class="panel-heading">
        <h4 class="panel-title">Einstellungen</h4>
      </div>
      <div class="panel-body">
        <div class="checkbox">
          <label>
            <input type="checkbox" name="geraete" id="geraete">
            auch die Geräte-Daten löschen
          </label>
        </div>
        <div class="checkbox">
          <label>
            <input type="checkbox" name="whois" id="whois">
            auch die Whois-Daten löschen
          </label>
        </div>
      </div>
    </div>
    <a href="#loeschenModal" class="btn btn-danger" data-toggle="modal" onclick="document.getElementById('adddel').innerHTML=''; if (document.getElementById('geraete').checked) { document.getElementById('adddel').innerHTML +='<p>Sollen auch alle Geräte-Informationen gelöscht werden?</p>'; }  if (document.getElementById('whois').checked) { document.getElementById('adddel').innerHTML +='<p>Sollen auch alle Whois-Informationen gelöscht werden?</p>'; }">$_loeschen</a>
  </div>

  <div class="modal fade" id="loeschenModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <div class="alert alert-danger" role="alert">
            <div class="msgIcon"><i class="fa fa-trash fa-2x"></i></div>
            <div class="msgText"><strong>$_loeschen</strong></div>
          </div>
        </div>
        <div class="modal-body">
          <p>Soll die Datenbank tatsächlich komplett geleert werden?</p>
          <div id="adddel"></div>
        </div>
        <div class="modal-footer">
          <input class="btn btn-danger" type="submit" value="$_loeschen" name="loeschen">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        </div>
      </div>
    </div>
  </div>
  </form>
LIMIT1;
}

echo "</div>";
require ("include/htmlend.php");

?>

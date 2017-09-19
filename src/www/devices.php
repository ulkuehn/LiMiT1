<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: devices.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to manage monitored devices and their properties
//
//==============================================================================
//==============================================================================

$_hinzu = "Gerät hinzufügen";
$_name = "Name";
$_loeschen = "Gerät löschen";
$_editieren = "Gerät bearbeiten";

require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");
require ("include/database.php");

titleAndHelp ("Geräte verwalten", <<<LIMIT1
In diesem Bereich können die Geräte verwaltet werden, deren Verkehr aufgezeichnet werden soll bzw. wurde.
Für jedes Gerät können beliebige Eigenschaften definiert werden, um diese in den aufgezeichneten Daten auffinden zu können.
<br>
So kann etwa für ein Smartphone die IMEI-Nummer, MAC-Adresse oder der Name eines auf dem Gerät verwendeten Kontos eingetragen werden.
Ob diese Daten in dem aufgezeichneten Datenstrom vorhanden sind, kann dann rasch ermittelt werden.
LIMIT1
);


// Gerät hinzufügen
if (isset($_POST["hinzu"]))
{
  $name = trim($_POST["name"]);
  if ($name != "")
  {
    $select_s = $db->prepare("select * from geraet where binary name=?");
    $select_s->execute(array($name));
    if ($select_s->fetch())
    {
      errorMsg ("Das Gerät \"" . htmlSave($name) . "\" ist bereits vorhanden.");
    }
    else
    {
      $insert_s = $db->prepare("insert into geraet set name=?, stand=now()");
      $insert_s->execute(array($name));
    }
  }
}


// Gerät löschen
if (isset($_POST["loeschen"]))
{
  $select_s = $db->prepare("select * from geraet where id=?");
  $select_s->execute(array($_POST["hid"]));
  if ($geraet = $select_s->fetch())
  {
    $select_s = $db->prepare("select * from aufzeichnung where geraet=?");
    $select_s->execute(array($_POST["hid"]));
    if ($select_s->fetch())
    {
      errorMsg ("Das Gerät \"" . htmlSave($geraet["name"]) . "\" ist mit Aufzeichnungen in der Datenbank verknüpft und kann daher nicht gelöscht werden.");
    }
    else
    {
      $delete_s = $db->prepare("delete from eigenschaft where geraet=?");
      $delete_s->execute (array($geraet["id"]));
       
      $delete_s = $db->prepare("delete from geraet where id=?");
      $delete_s->execute (array($geraet["id"]));
        
      successMsg ("Das Gerät \"" . htmlSave($geraet["name"]) . "\" wurde gelöscht.");
    }
  }
  else
  {
    errorMsg ("Das Gerät ist nicht in der Datenbank vorhanden.");
  }
}

echo <<<LIMIT1
<form method="post">
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Vorhandene Geräte</h4>
      </div>
      <div class="panel-body">
LIMIT1;

$select_s = $db->prepare("select *, unix_timestamp(stand) as _stand, date_format(stand,'%e.%c.%Y') as _standd,  date_format(stand,'%H:%i:%s') as _standt from geraet order by name");
$select_s->execute();
if (($geraet = $select_s->fetch()) == false)
{
  infoMsg ("Es sind keine Geräte definiert.");
}
else
{
  echo tableSorter ("Geraete", "columns: [ {orderable:false, searchable:false}, {}, {}, {} ], order: [ [1,'asc'] ]");
  $foldMe = tableFolder ("Geraete");

  echo <<<LIMIT1
        <input type="hidden" id="hid" name="hid" value="">
          <div class="table-responsive">
            <table id="Geraete" class="table table-hover">
              <thead>
                <tr>
                  <th>$foldMe</th>
                  <th>Name<span class="fullGeraete"> des Geräts</span></th>
                  <th>Eigenschaften<span class="fullGeraete"> des Geräts</span></th>
                  <th><span class="compactGeraete">Stand</span><span class="fullGeraete">letzte Änderung</span></th>
                </tr>
              </thead>
            <tbody>
LIMIT1;

  do
  {
    echo "<tr>";

    echo "<td><span style=\"white-space:nowrap\">";
    echo iconButton("pencil","editdevice.php?id=".$geraet["id"],$_editieren,"success");
    echo "<span class=\"fullGeraete\"> <a href=\"#loeschenModal\" title=\"$_loeschen\" class=\"btn btn-danger btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('geraetename').innerHTML='",jsSave(htmlspecialchars($geraet["name"])),"';document.getElementById('hid').value='",$geraet["id"],"';\"><i class=\"fa fa-trash\"></i></a></span>";
    echo "</span></td>";

    echo faltZelle ($geraet["name"], "Geraete");

    $eig = "";
    $id_s = $db->prepare("select * from eigenschaft where geraet=? order by name");
    $id_s->execute(array($geraet["id"]));
    while ($id = $id_s->fetch())
    {
      $eig .= $id["name"] . " = " . $id["wert"] . "\n";
    }
    echo faltZelle ($eig, "Geraete");
    
    echo "<td>",$geraet["_standd"]," <i class=\"fa fa-clock-o\"></i> ",$geraet["_standt"],"<!--",$geraet["_stand"],"--></td>";

    echo "</tr>";
  }
  while ($geraet = $select_s->fetch());

  echo "</tbody></table></div>";
}

echo <<<LIMIT1
      </div>
    </div>
  </div>
  
  <div class="row">
    <a href="#hinzuModal" class="btn btn-primary" data-toggle="modal">$_hinzu</a>
  </div>

  <div class="modal fade" id="hinzuModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <div class="alert alert-info" role="alert">
            <div class="msgIcon"><i class="fa fa-plus fa-2x"></i></div>
            <div class="msgText"><strong>$_hinzu</strong></div>
          </div>
        </div>
        <div class="modal-body">
          <div class="input-group">
            <span class="input-group-addon">$_name</span>
            <input type="text" class="form-control" name="name">
          </div>
        </div>
        <div class="modal-footer">
          <input class="btn btn-primary" type="submit" value="$_hinzu" name="hinzu">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        </div>
      </div>
    </div>
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
          <p>Soll das Gerät <strong><span id="geraetename"></span></strong> gelöscht werden?</p>
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

require ("include/htmlend.php");

?>

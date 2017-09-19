<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: editdevice.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to modify known devices and their properties
//
//==============================================================================
//==============================================================================


$_hinzu = "Eigenschaft hinzufügen";
$_name = "Name";
$_wert = "Wert";
$_loeschen = "Eigenschaft löschen";
$_editieren = "Eigenschaft bearbeiten";
$_aendern = "Gerätenamen ändern";

require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");
require ("include/database.php");

titleAndHelp ("Gerät bearbeiten", <<<LIMIT1
Ein vorhandenes Gerät kann hier editiert werden.
Dabei können bestehende Eigenschaften angepasst oder gelöscht sowie neue Eigenschaften hinzugefügt werden.
LIMIT1
);


$select_s = $db->prepare("select * from geraet where id=?");
$select_s->execute(array($_GET["id"]));
if (($geraet = $select_s->fetch()) == false)
{
  echo "<div class=\"row\">";
  errorMsg ("Das Gerät ist nicht in der Datenbank vorhanden.");
  echo "</div>";
}
else
{  
  echo <<<LIMIT1
<form class="form-horizontal" method="post">
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Name</h4>
      </div>
      <div class="panel-body">
LIMIT1;
      
  // Gerätename ändern
  if (isset($_POST["aendern"]))
  {
    $name = trim($_POST["gname"]);
    if ($name != "" && $name != $geraet["name"])
    {
      $select_s = $db->prepare("select * from geraet where id!=? and binary name=?");
      $select_s->execute(array($geraet["id"],$name));
      if (!$select_s->fetch())
      {
        $update_s = $db->prepare("update geraet set name=?, stand=now() where id=?");
        $update_s->execute(array($name,$geraet["id"]));
        $geraet["name"] = $name;
        infoMsg ("Der Gerätename wurde geändert.");
      }
      else
      {
        errorMsg ("Ein Gerät mit dem Namen \"".htmlSave($name)."\" ist bereits vorhanden.");
      }        
    }
  }
      
  $gname = htmlSave($geraet["name"]);

  echo <<<LIMIT1
        <p><input class="form-control" type="text" name="gname" value="$gname"></p>
        <input type="submit" class="btn btn-primary" name="aendern" value="$_aendern">
      </div>
    </div>
  </div>
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Eigenschaften</h4>
      </div>
      <div class="panel-body">
LIMIT1;

  // Eigenschaft hinzufügen
  if (isset($_POST["hinzu"]))
  {
    $name = trim($_POST["name"]);
    $wert = trim($_POST["wert"]);
    if ($name != "" && $wert != "")
    {
      $select_s = $db->prepare("select * from eigenschaft where geraet=? and binary name=?");
      $select_s->execute(array($geraet["id"],$name));
      if (!$select_s->fetch())
      {
        $insert_s = $db->prepare("insert into eigenschaft set geraet=?, name=?, wert=?");
        $insert_s->execute(array($geraet["id"],$name,$wert));
        $update_s = $db->prepare("update geraet set stand=now() where id=?");
        $update_s->execute(array($geraet["id"]));
      }
      else
      {
        errorMsg ("Die Eigenschaft \"".htmlSave($name)."\" ist bereits vorhanden.");
      }
    }
  }
  
  // Eigenschaft bearbeiten
  if (isset($_POST["editieren"]))
  {
    $select_s = $db->prepare("select * from eigenschaft where id=?");
    $select_s->execute(array($_POST["hid"]));
    if (!$select_s->fetch())
    {
      errorMsg ("Die Eigenschaft ist nicht in der Datenbank vorhanden.");
    }
    else
    {
      $name = trim($_POST["ename"]);
      $wert = trim($_POST["ewert"]);
      if ($name != "" && $wert != "")
      {
        $select_s = $db->prepare("select * from eigenschaft where geraet=? and binary name=? and id!=?");
        $select_s->execute(array($geraet["id"],$name,$_POST["hid"]));
        if (!$select_s->fetch())
        {
          $update_s = $db->prepare("update eigenschaft set name=?, wert=? where id=?");
          $update_s->execute(array($name,$wert,$_POST["hid"]));
          $update_s = $db->prepare("update geraet set stand=now() where id=?");
          $update_s->execute(array($geraet["id"]));
        }
        else
        {
          errorMsg ("Die Eigenschaft \"".htmlSave($name)."\" ist bereits vorhanden.");
        }
      }        
    }
  }
  
  // Eigenschaft löschen
  if (isset($_POST["loeschen"]))
  {
    $select_s = $db->prepare("select * from eigenschaft where id=?");
    $select_s->execute(array($_POST["hid"]));
    if ($eigenschaft = $select_s->fetch())
    {
      $delete_s = $db->prepare("delete from eigenschaft where id=?");
      $delete_s->execute (array($eigenschaft["id"]));          
      $update_s = $db->prepare("update geraet set stand=now() where id=?");
      $update_s->execute(array($geraet["id"]));
    }
    else
    {
      errorMsg ("Die Eigenschaft ist nicht in der Datenbank vorhanden.");
    }
  }

  $select_s = $db->prepare("select * from eigenschaft where geraet=? order by name");
  $select_s->execute(array($_GET["id"]));
  
  if (($eigenschaft = $select_s->fetch()) == false)
  {
    infoMsg ("Zu diesem Gerät ist keine Eigenschaft eingetragen.");
  }
  else
  {
    echo tableSorter ("Eigenschaften", "columns: [ {orderable:false, searchable:false}, {}, {} ], order: [ [1,'asc'] ]");
    $foldMe = tableFolder ("Eigenschaften");

    echo <<<LIMIT1
  <input type="hidden" id="hid" name="hid" value="">
  <div class="table-responsive">
    <table id="Eigenschaften" class="table table-hover">
      <thead>
        <tr>
          <th>$foldMe</th>
          <th>Eigenschaft</th>
          <th>Wert</th>
        </tr>
      </thead>
      <tbody>
LIMIT1;
 
    do
    {
      echo "<tr>";

      echo "<td><span style=\"white-space:nowrap\">";
      echo "<a href=\"#editModal\" title=\"$_editieren\" class=\"btn btn-success btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('ename').value='",jsSave($eigenschaft["name"]),"'; document.getElementById('ewert').value='",jsSave($eigenschaft["wert"]),"'; document.getElementById('hid').value='",$eigenschaft["id"],"';\"><i class=\"fa fa-pencil\"></i></a>";
      echo "<span class=\"fullEigenschaften\"> <a href=\"#loeschenModal\" title=\"$_loeschen\" class=\"btn btn-danger btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('eigenschaft').innerHTML='",jsSave(htmlspecialchars($eigenschaft["name"])),"';document.getElementById('hid').value='",$eigenschaft["id"],"';\"><i class=\"fa fa-trash\"></i></a></span>";
      echo "</span></td>";

      echo faltZelle ($eigenschaft["name"], "Eigenschaften");

      echo faltZelle ($eigenschaft["wert"], "Eigenschaften");

      echo "</tr>";
    }
    while ($eigenschaft = $select_s->fetch());
    
    echo "</tbody></table></div>";
  }   

  echo <<<LIMIT1
        <a href="#hinzuModal" class="btn btn-primary" data-toggle="modal">$_hinzu</a>
      </div>
    </div>
  </div>
  
  
  <div class="modal fade" id="hinzuModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <div class="alert alert-info" role="alert">
            <div class="msgIcon"><i class="fa fa-plus fa-2x"></i></div>
            <div class="msgText"><strong>$_hinzu</strong></div>
          </div>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="hname" class="col-md-2 control-label">$_name</label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="name" id="hname">
            </div>
          </div>  
          <div class="form-group">
            <label for="hwert" class="col-md-2 control-label">$_wert</label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="wert" id="hwert">
            </div>
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
          <p>Soll die Eigenschaft <strong><span id="eigenschaft"></span></strong> gelöscht werden?</p>
        </div>
        <div class="modal-footer">
          <input class="btn btn-danger" type="submit" value="$_loeschen" name="loeschen">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        </div>
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <div class="alert alert-success" role="alert">
            <div class="msgIcon"><i class="fa fa-pencil fa-2x"></i></div>
            <div class="msgText"><strong>$_editieren</strong></div>
          </div>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="ename" class="col-md-2 control-label">$_name</label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="ename" id="ename">
            </div>
          </div>  
          <div class="form-group">
            <label for="ewert" class="col-md-2 control-label">$_wert</label>
            <div class="col-md-10">
              <input type="text" class="form-control" name="ewert" id="ewert">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <input class="btn btn-success" type="submit" value="$_editieren" name="editieren">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        </div>
      </div>
    </div>
  </div>

</form>
LIMIT1;

}

require ("include/htmlend.php");

?>

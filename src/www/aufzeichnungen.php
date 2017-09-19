<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: aufzeichnungen.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display all available recording sessions;
//              additionally, tools to manage those sessions are provided
//              such as editing, exporting and deleting a session
//
//==============================================================================
//==============================================================================


// Standard-Filter: jede
setcookie ("filter", "jede");

require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
include ("include/htmlstart.php");
include ("include/topmenu.php");

$_ansehen = "Aufzeichnung ansehen";
$_loeschen = "Aufzeichnung löschen";
$_editieren = "Aufzeichnung bearbeiten";
$_sichern = "Aufzeichnung exportieren";


function actionInfo ($step, $tot, $text)
{
  $proz = floor ($step/$tot*100);
  echo <<<LIMIT1
  <script>
    document.getElementById('progress').style.width = "$proz%";
    document.getElementById('progresst').innerHTML = "$step / $tot";
    document.getElementById('progressm').innerHTML = "$text";
  </script>
LIMIT1;
  ob_flush(); flush();
}


// Aufzeichnung löschen
if (isset($_POST["loeschen"]))
{
  $aufzeichnungID = $_POST["hid"];
  echo "<div class=\"row\">";
  
  $select_s = $db->prepare("select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?");
  $select_s->execute(array($aufzeichnungID));
  if (($aufzeichnung = $select_s->fetch()) == false)
  {
    errorMsg ("Die Aufzeichnung ist nicht in der Datenbank vorhanden.");
  }
  else
  {
    $tot = 11;
    
    echo <<<LIMIT1
    <div class="panel panel-primary" id="panell">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
    echo "Aufzeichnung ",($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> "),"vom ".$aufzeichnung["_start"]." löschen";
    echo <<<LIMIT1
        </h4>
      </div>
      <div class="panel-body">
        <div class="progress">
          <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" id="progress" style="min-width: 5em; width:0%">
            <p style="color:black;font-weight:bold" id="progresst">0 / $tot</p>
          </div>
        </div>
LIMIT1;
    progressMsg ("<span id=\"progressm\"></span>");
    
    $db->beginTransaction ();
    
    # Verbindungen, http, https, ssltls
    $delete_s = $db->prepare("delete from verbindung where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    $delete_s = $db->prepare("delete from http where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    $delete_s = $db->prepare("delete from https where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    $delete_s = $db->prepare("delete from ssltls where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (1,$tot, "Verbindungen wurden gelöscht ...");
    
    # Requests
    $delete_s = $db->prepare("delete from request where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (2,$tot, "Requests wurden gelöscht ...");

    # Responses
    $delete_s = $db->prepare("delete from response where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (3,$tot, "Responses wurden gelöscht ...");

    # Setcookies
    $delete_s = $db->prepare("delete from setcookie where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (4,$tot, "Setcookies wurden gelöscht ...");

    # Sendcookies
    $delete_s = $db->prepare("delete from sendcookie where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (5,$tot, "Sendcookies wurden gelöscht ...");

    # Header
    $delete_s = $db->prepare("delete from header where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (6,$tot, "Header wurden gelöscht ...");

    # Inhalte
    $delete_s = $db->prepare("delete from inhalt where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (7,$tot, "Inhalte wurden gelöscht ...");

    # Metadaten
    $delete_s = $db->prepare("delete from metadaten where aufzeichnung=?");
    $delete_s->execute (array($aufzeichnungID));
    actionInfo (8,$tot, "Metadaten wurden gelöscht ...");

    # Hosts bereinigen
    $hostdel = array();
    $host_s = $db->prepare("select id from host");
    $host_s->execute();
    while ($hostID = $host_s->fetchColumn())
    {
      $count_s = $db->prepare("select count(*) from verbindung where host=?");
      $count_s->execute (array($hostID));
      if (!$count_s->fetchColumn())
      {
        $count_s = $db->prepare("select count(*) from http where host=?");
        $count_s->execute (array($hostID));
        if (!$count_s->fetchColumn())
        {
          $count_s = $db->prepare("select count(*) from https where host=?");
          $count_s->execute (array($hostID));
          if (!$count_s->fetchColumn())
          {
            array_push ($hostdel, $hostID);
          }
        }
      }
    }
    foreach ($hostdel as $hostID)
    {
      $delete_s = $db->prepare("delete from host where id=?");
      $delete_s->execute (array($hostID));
    }
    actionInfo (9,$tot, "Hosts wurden bereinigt ...");
    
    # Zertifikate bereinigen
    $zertdel = array();
    $zert_s = $db->prepare("select id from zertifikat");
    $zert_s->execute();
    while ($zertID = $zert_s->fetchColumn())
    {
      $count_s = $db->prepare("select count(*) from https where zertifikat=?");
      $count_s->execute (array($zertID));
      if (!$count_s->fetchColumn())
      {
        $count_s = $db->prepare("select count(*) from ssltls where zertifikat=?");
        $count_s->execute (array($zertID));
        if (!$count_s->fetchColumn())
        {
          array_push ($zertdel, $zertID);
        }
      }
    }
    foreach ($zertdel as $zertID)
    {
      $delete_s = $db->prepare("delete from zertifikat where id=?");
      $delete_s->execute (array($zertID));
    }
    actionInfo (10,$tot, "Zertifikate wurden bereinigt ...");
    
    # Cookies bereinigen
    $cookiedel = array ();
    $cookie_s = $db->prepare("select id from cookie");
    $cookie_s->execute();
    while ($cookieID = $cookie_s->fetchColumn())
    {
      $count_s = $db->prepare("select count(*) from setcookie where cookie=?");
      $count_s->execute (array($cookieID));
      $count = $count_s->fetchColumn();
      $count_s = $db->prepare("select count(*) from sendcookie where cookie=?");
      $count_s->execute (array($cookieID));
      $count += $count_s->fetchColumn();
      if (!$count)
      {
        array_push ($cookiedel, $cookieID);
      }
    }
    foreach ($cookiedel as $cookieID)
    {
      $delete_s = $db->prepare("delete from cookie where id=?");
      $delete_s->execute (array($cookieID));
    }
    actionInfo (11,$tot, "Cookies wurden bereinigt ...");
    
    # Aufzeichnung
    $delete_s = $db->prepare("delete from aufzeichnung where id=?");
    $delete_s->execute (array($aufzeichnungID));

    $db->commit ();
    
    echo <<<LIMIT1
      </div>
    </div>
  <script>
    document.getElementById('panell').style.display = "none";
  </script>
LIMIT1;
    ob_flush(); flush();
    successMsg ("Die Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"]." wurde gelöscht.");
  }
  echo "</div>";
}

if (isset($_POST["editieren"]))
{
  $exists = 0;
  if ($_POST["name"] != "")
  {
    $select_s = $db->prepare ("select count(*) from aufzeichnung where name=? and id!=?");
    $select_s->execute (array($_POST["name"],$_POST["hid"]));
    $exists = $select_s->fetchColumn();
  }
  if ($exists)
  {
    echo "<div class=\"row\">";
    errorMsg ("Eine Aufzeichnung mit dem Namen \"".$_POST["name"]."\" existiert bereits.");
    echo "</div>";
  }
  else
  {
    $update_s = $db->prepare("update aufzeichnung set geraet=?, name=?, info=? where id=?");
    $update_s->execute(array($_POST["geraet"], $_POST["name"], $_POST["infos"], $_POST["hid"]));
  }
}



titleAndHelp ("Aufzeichnungen", <<<LIMIT1
<p>Diese Funktion bietet einen Überblick über die vorhandenen Aufzeichnungen. Dabei sind folgende Operationen möglich:</p>
<div class="row">
  <div class="col-md-1">
    <button class="btn btn-info btn-xs center-block"><i class="fa fa-eye"></i></button>
  </div>
  <div class="col-md-11">
    <p>Jede Aufzeichnung besteht aus mehreren Verbindungen, die wiederum aus mehreren Requests bestehen können. Über diesen Button sind sämtliche Details der Aufzeichnung zugänglich.</p>
  </div>
  <div class="col-md-1">
    <button class="btn btn-success btn-xs center-block"><i class="fa fa-pencil"></i></button>
  </div>
  <div class="col-md-11">
    <p>Eigenschaften vorhandener Aufzeichnungen können nachträglich geändert bzw. ergänzt werden, wenn dies beim Start der Aufzeichnung übersprungen wurde.</p>
  </div>
  <div class="col-md-1">
    <button class="btn btn-primary btn-xs center-block"><i class="fa fa-upload"></i></button>
  </div>
  <div class="col-md-11">
    <p>Hiermit kann eine Aufzeichnung aus dem $my_name-System exportiert und auf einem anderen Computer gespeichert werden. Mit der entsprechenden Funktion im Menü "Werkzeuge" kann sie später wieder (auch auf einem anderen $my_name-Gerät) importiert werden.</p>
  </div>
  <div class="col-md-1">
    <button class="btn btn-danger btn-xs center-block"><i class="fa fa-trash"></i></button>
  </div>
  <div class="col-md-11">
    <p>Mit dieser Funktion können nicht länger benötigte Aufzeichnungen dauerhaft vom System gelöscht werden.</p>
  </div>
</div>
LIMIT1
);


echo <<<LIMIT1
<form class="form-horizontal" method="post">
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Vorhandene Aufzeichnungen</h4>
      </div>
      <div class="panel-body">
LIMIT1;

$select_s = $db->prepare("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt, timediff(ende,start) as _dauer, unix_timestamp(ende)-unix_timestamp(start) as _diff from aufzeichnung order by start desc");
$select_s->execute();

if (($aufzeichnung = $select_s->fetch()) == false)
{
  infoMsg ("Es sind keine Aufzeichnungen vorhanden.");
}
else
{
  echo tableSorter ("Aufzeichnungen", "columns: [ {orderable:false, searchable:false}, {}, {}, {type:'num'}, {}, {}, {} ], order: [ [2,'desc'] ]");
  $foldMe = tableFolder ("Aufzeichnungen");

  echo <<<LIMIT1
        <input type="hidden" id="hid" name="hid" value="">
        <div class="table-responsive">
          <table id="Aufzeichnungen" class="table table-hover">
            <thead>
              <tr>
                <th>$foldMe</th>
                <th>Bezeichnung</th>
                <th>Beginn<span class="fullAufzeichnungen"> der Aufzeichnung</span></th>
                <th>Dauer<span class="fullAufzeichnungen"> der Aufzeichnung</span></th>
                <th>Gerät</th>
                <th>Infos</th>
                <th><span class="compactAufzeichnungen">Verb.</span><span class="fullAufzeichnungen">Verbindungen</span></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

  do
  {
    echo "<tr>";
    
    echo "<td><span style=\"white-space:nowrap\">";
    echo viewButton("aufzeichnung.php?aufzeichnung=".$aufzeichnung["id"],"$_ansehen");

    echo "<span class=\"fullAufzeichnungen\"> <a href=\"#editModal\" title=\"$_editieren\" class=\"btn btn-success btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('aufzeichnungInfo1').innerHTML='",($aufzeichnung["name"]!=""? "<strong>".jsSave(htmlSave($aufzeichnung["name"]))."</strong> ":""),"vom ",$aufzeichnung['_startd'],", ",$aufzeichnung['_startt'],"'; document.getElementById('geraet",$aufzeichnung["geraet"]+0,"').selected='true'; document.getElementById('name').value='",jsSave($aufzeichnung["name"]),"'; document.getElementById('infos').value='",jsSave($aufzeichnung["info"]),"'; document.getElementById('hid').value='",$aufzeichnung['id'],"';\"><i class=\"fa fa-pencil\"></i></a>";

    echo "<span class=\"fullAufzeichnungen\"> <a href=\"#sichernModal\" title=\"$_sichern\" class=\"btn btn-primary btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('aufzeichnungInfo3').innerHTML='",($aufzeichnung["name"]!=""? "<strong>".jsSave(htmlSave($aufzeichnung["name"]))."</strong> ":""),"vom ",$aufzeichnung['_startd'],", ",$aufzeichnung['_startt'],"'; archivieren({$aufzeichnung['id']});\"><i class=\"fa fa-upload\"></i></a>";

    echo " <a href=\"#loeschenModal\" title=\"$_loeschen\" class=\"btn btn-danger btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('aufzeichnungInfo2').innerHTML='",($aufzeichnung["name"]!=""? "<strong>".jsSave(htmlSave($aufzeichnung["name"]))."</strong> ":""),"vom ",$aufzeichnung['_startd'],", ",$aufzeichnung['_startt'],"';document.getElementById('hid').value='",$aufzeichnung['id'],"';\"><i class=\"fa fa-trash\"></i></a></span>";
    echo "</span></td>";
    
    echo faltZelle ($aufzeichnung["name"], "Aufzeichnungen");
    
    echo "<td>",$aufzeichnung["_startd"]," <i class=\"fa fa-clock-o\"></i> ",$aufzeichnung["_startt"],"<!--",$aufzeichnung["start"],"--></td>";
    
    echo "<td>",zeitDauer($aufzeichnung["_diff"]),"<!--",$aufzeichnung["_diff"],"--></td>";

    $geraet_s = $db->prepare("select * from geraet where id=?");
    $geraet_s->execute(array($aufzeichnung["geraet"]));
    echo faltZelle ($geraet_s->fetch()["name"], "Aufzeichnungen");
    
    echo faltZelle ($aufzeichnung["info"], "Aufzeichnungen");

    $vcount_s = $db->prepare("select count(*) from verbindung where aufzeichnung=?");
    $vcount_s->execute(array($aufzeichnung["id"]));
    $verbindungCT = $vcount_s->fetchColumn();
    echo "<td class=\"numeric\">$verbindungCT</td>";
    
    echo "</tr>";
  }
  while ($aufzeichnung = $select_s->fetch());

  echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
LIMIT1;

  // Löschen-Modal
  echo <<<LIMIT1
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
              <p>Soll die Aufzeichnung <span id="aufzeichnungInfo2"></span> gelöscht werden?</p>
            </div>
            <div class="modal-footer">
              <input class="btn btn-danger" type="submit" value="$_loeschen" name="loeschen">
              <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            </div>
          </div>
        </div>
      </div>
LIMIT1;

  // Sichern-Modal
  echo <<<LIMIT1
      <div class="modal fade" id="sichernModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <div class="alert alert-info" role="alert">
                <div class="msgIcon"><i class="fa fa-upload fa-2x"></i></div>
                <div class="msgText"><strong>Aufzeichnung <span id="aufzeichnungInfo3"></span> exportieren</strong></div>
              </div>
            </div>
            <div class="modal-body" id="sichernBody">
LIMIT1;
  progressMsg ("Das Datei-Archiv wird erstellt");
  echo <<<LIMIT1
            </div>
            <div class="modal-footer">
              <a href="#" id="sichernButton" class="btn btn-primary disabled" onclick="setTimeout(function(){ $('#sichernModal').modal('hide');},500)">$_sichern</a>
              <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            </div>
          </div>
        </div>
      </div>
      
      <script>
        function archivieren (id)
        {
          var xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function() 
          {
            if (xmlhttp.readyState==4 && xmlhttp.status==200) 
            {
              document.getElementById("sichernBody").innerHTML = xmlhttp.responseText;
              document.getElementById("sichernButton").href = "include/download.php?id="+id;
              document.getElementById("sichernButton").classList.remove("disabled");
            }
          }
          
          xmlhttp.open("GET","include/archivieren.php?id="+id,true);
          xmlhttp.send();
        }
      </script>
LIMIT1;

  // Edit-Modal
  echo <<<LIMIT1
      <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">  
          <div class="modal-content">
            <div class="modal-header">
              <div class="alert alert-success" role="alert">
                <div class="msgIcon"><i class="fa fa-pencil fa-2x"></i></div>
                <div class="msgText">Aufzeichnung <span id="aufzeichnungInfo1"></span></div>
              </div>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="name" class="col-md-3 control-label">Bezeichnung</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="name" id="name">
                </div>
              </div>  
              <div class="form-group">
                <label for="geraet" class="col-md-3 control-label">Verwendetes Gerät</label>
                <div class="col-md-9">
LIMIT1;

  $select_s = $db->prepare("select * from geraet order by name");
  $select_s->execute();
  if ($geraet = $select_s->fetch())
  {
    echo "<select class=\"form-control\" name=\"geraet\">";
    echo "<option id=\"geraet0\" value=\"0\"></option>";
    do
    {
      echo "<option id=\"geraet",$geraet["id"],"\" value=\"",$geraet["id"],"\">",htmlSave($geraet["name"]),"</option>";
    }
    while ($geraet = $select_s->fetch());
    echo "</select>";
  }

  echo <<<LIMIT1
                </div>
              </div>  
              <div class="form-group">
                <label for="infos" class="col-md-3 control-label">Erläuterungen</label>
                <div class="col-md-9">
                  <textarea class="form-control" id="infos" name="infos" rows="3"></textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <input class="btn btn-success" type="submit" value="$_editieren" name="editieren">
              <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            </div>
          </div>
        </div>
LIMIT1;
}

echo <<<LIMIT1
      </div>  
    </div>
  </div>
</form>
LIMIT1;

include ("include/htmlend.php");

?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: metadaten.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display meta information of specific mime types
//              (e.g. comments in html content, camera information in jpeg)
//
//==============================================================================
//==============================================================================


require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
include ("include/htmlstart.php");
include ("include/topmenu.php");


function metaRows ($aufzID, $tableName)
{
  global $db, $empfangenIcon, $versandtIcon, $tableEllipsis;
  
  $_ansehen = "Request ansehen";
  $erg = "";
  
  // HTML
  
  if ($aufzID == 0)
  {
    $select_s = $db->prepare ("select * from metadaten where mime=?");
    $select_s->execute (array("text/html"));
  }
  else
  {
    $select_s = $db->prepare ("select * from metadaten where mime=? and aufzeichnung=?");
    $select_s->execute (array("text/html",$aufzID));
  }
  
  if (($meta = $select_s->fetch()) != false)
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}".($_REQUEST["show"]=="alle"? ", {}":"").", {}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="panel panel-info">
      <div class="panel-heading">
        <h5 class="panel-title">HTML-Metadaten (Titel, Kommentare etc.)</h5>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="$tableName" class="table table-hover">
            <thead>
              <tr>
                <th>$foldMe</th>
LIMIT1;
    if ($_REQUEST["show"]=="alle")
    {
      $erg .= "<th>Aufzeichnung</th>";
    }
    $erg .= <<<LIMIT1
                <th>Typ</th>
                <th>Inhalt</th>
                <th>Zeit</th>
                <th>Server</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;
    
    do
    {
      $verbindung_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?");
      $verbindung_s->execute (array($meta["verbindung"]));
      $verbindung = $verbindung_s->fetch();

      $erg .= "<tr>";
      
      $erg .= "<td>".viewButton("request.php?request=".$meta["request"],$_ansehen)."</td>";

      if ($_REQUEST["show"]=="alle")
      {
        $aufzeichnung_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
        $aufzeichnung_s->execute (array($verbindung["aufzeichnung"]));
        $aufzeichnung = $aufzeichnung_s->fetch();
        $erg .= $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],$tableName);
      }
            
      $erg .= faltZelle ($meta["feld"], $tableName);
      $erg .= faltZelle ($meta["wert"], $tableName);

      $erg .= "<td".onevent($tableName)."><span class=\"full$tableName\">".$verbindung["_zeitd"]." <i class=\"fa fa-clock-o\"></i> </span><span class=\"compact$tableName\">$tableEllipsis</span>".$verbindung["_zeitt"]."<!--".$verbindung["nr"]."--></td>"; // sort by nr
      
      if (!$verbindung["host"])
      {
        $erg .= ipHostinfo ($verbindung["ip"], $tableName);
      }
      else
      {
        $erg .= idHostinfo ($verbindung["host"], $tableName);
      }

      $erg .= "</tr>";
    }
    while ($meta = $select_s->fetch());
    
    $erg .= <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
LIMIT1;
  }


  // Bilder
  
  $tableName .= "2";
  if ($aufzID == 0)
  {
    $select_s = $db->prepare ("select * from metadaten where mime like ?");
    $select_s->execute (array("image/%"));
  }
  else
  {
    $select_s = $db->prepare ("select * from metadaten where mime like ? and aufzeichnung=?");
    $select_s->execute (array("image/%",$aufzID));
  }
  
  if (($meta = $select_s->fetch()) != false)
  {
    list ($e, $foldMe) = tableFolder ($tableName, false);
    $erg .= $e . tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}".($_REQUEST["show"]=="alle"? ", {}":"").", {}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="panel panel-info" style="margin-top:20px">
      <div class="panel-heading">
        <h5 class="panel-title">Bilder-Metadaten (Dimensionen, EXIF-Daten, Kommentare etc.)</h5>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="$tableName" class="table table-hover">
            <thead>
              <tr>
                <th>$foldMe</th>
LIMIT1;
    if ($_REQUEST["show"]=="alle")
    {
      $erg .= "<th>Aufzeichnung</th>";
    }
    $erg .= <<<LIMIT1
                <th>Typ</th>
                <th>Inhalt</th>
                <th>Zeit</th>
                <th>Server</th>
              </tr>
            </thead>
        <tbody>
LIMIT1;
    
    do
    {
      $verbindung_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?");
      $verbindung_s->execute (array($meta["verbindung"]));
      $verbindung = $verbindung_s->fetch();

      $erg .= "<tr>";
      
      $erg .= "<td>".viewButton("request.php?request=".$meta["request"],$_ansehen)."</td>";

      if ($_REQUEST["show"]=="alle")
      {
        $aufzeichnung_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
        $aufzeichnung_s->execute (array($verbindung["aufzeichnung"]));
        $aufzeichnung = $aufzeichnung_s->fetch();
        $erg .= $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],$tableName);
      }
            
      $erg .= faltZelle ($meta["feld"], $tableName);
      $erg .= faltZelle ($meta["wert"], $tableName);

      $erg .= "<td".onevent($tableName)."><span class=\"full$tableName\">".$verbindung["_zeitd"]." <i class=\"fa fa-clock-o\"></i> </span><span class=\"compact$tableName\">$tableEllipsis</span>".$verbindung["_zeitt"]."<!--".$verbindung["nr"]."--></td>"; // sort by nr
      
      if (!$verbindung["host"])
      {
        $erg .= ipHostinfo ($verbindung["ip"], $tableName);
      }
      else
      {
        $erg .= idHostinfo ($verbindung["host"], $tableName);
      }

      $erg .= "</tr>";
    }
    while ($meta = $select_s->fetch());
    
    $erg .= <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
LIMIT1;
  }
  
  if ($erg == "")
  {
    return array (false, "Es sind keine Metadaten vorhanden.");
  }
  else
  {
    return array (true, $erg);    
  }  
}


$pics = 0;

if (!isset($_REQUEST["show"]))
{
  $_REQUEST["show"] = "jede";
}

titleAndHelp ("Metadaten", <<<LIMIT1
Manche übertragenen Inhalte enthalten Meta-Daten, die bei einer Ansicht des Inhalts nicht oder nur schwer erkennbar sind.
In HTML-Dateien sind dies z.B. Kommentare, in Bildern Informationen über die verwendete Kamera oder deren Standort.
<br>
Diese Auswertung macht solche Informationen leicht und übersichtlich zugänglich.
LIMIT1
);

aufzeichnungsFilter ();

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = metaRows (0, "Metadaten");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Metadaten aller Aufzeichnungen</h4>
      </div>
      <div class="panel-body">
        $erg
      </div>
    </div>
  </div>        
LIMIT1;
}

else if ($_REQUEST["show"]=="jede")
{
  echo <<<LIMIT1
  <div class="row">
    <div class="panel-group" role="tablist">
LIMIT1;

  $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc");
  $select_s->execute();
  while ($aufzeichnung = $select_s->fetch())
  {
    list ($res, $erg) = metaRows ($aufzeichnung["id"], "Metadaten".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Metadaten der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
    echo <<<LIMIT1
          </h4>
        </div>
        <div id="auf{$aufzeichnung['id']}" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body">
            $erg
LIMIT1;
    echo <<<LIMIT1
          </div>
        </div>
      </div>
LIMIT1;
  }
  echo <<<LIMIT1
    </div>
  </div>
LIMIT1;
}

else
{
  $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?");
  $select_s->execute(array($_REQUEST["show"]));
  $aufzeichnung = $select_s->fetch();

  list ($res, $erg) = metaRows ($_REQUEST["show"], "Metadaten");

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Metadaten der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
  echo <<<LIMIT1
        </h4>
      </div>
      <div class="panel-body">
        $erg
      </div>
    </div>
  </div>        
LIMIT1;
}

include ("include/htmlend.php");

?>

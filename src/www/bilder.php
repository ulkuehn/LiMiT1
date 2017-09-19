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
// DESCRIPTION: used to display the images contained in a specific or all sessions
//              images are shown by their thumbnails and meta data such as
//              pixel dimension and image type
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


function bilderName ($uri)
{
  if (preg_match ("/\/([^\/]+?)(\?|$)/", $uri, $m))
  {
    return $m[1];
  }
  
  return $uri;
}


function picRows ($aufzID, $tableName)
{
  global $pics, $db, $tableEllipsis;
  
  $_ansehen = "Request ansehen";
  
  if ($aufzID == 0)
  {
    $select_s = $db->prepare ("select response.id as id, response.verbindung as verbindung, request, mime, inhalt.inhalt as inhalt, length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id and locate(?,mime)");
    $select_s->execute (array("image/"));
  }
  else
  {
    $select_s = $db->prepare ("select response.id as id, response.verbindung as verbindung, request, mime, inhalt.inhalt as inhalt, length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id and locate(?,mime) and response.aufzeichnung=?");
    $select_s->execute (array("image/",$aufzID));
  }
  
  if (($picture = $select_s->fetch()) == false)
  {
    return array (false, "Es wurden keine Bilder übertragen.");
  }
  else
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}, {orderable:false, searchable:false}".($_REQUEST["show"]=="alle"? ", {}":"").", {}, {}, {}, {}, {} ], order: [ [".($_REQUEST["show"]=="alle"? "5":"4").",'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="table-responsive">
      <table id="$tableName" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Ansicht</th>
LIMIT1;
    if ($_REQUEST["show"]=="alle")
    {
      $erg .= "<th>Aufzeichnung</th>";
    }
    $erg .= <<<LIMIT1
            <th>Zeit</th>
            <th>Name</th>
            <th>Bytes</th>
            <th>Typ</th>
            <th>Server</th>
          </tr>
        </thead>
        <tbody>
LIMIT1;
    
    do
    {
      $verbindung_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?");
      $verbindung_s->execute (array($picture["verbindung"]));
      $verbindung = $verbindung_s->fetch();
      $request_s = $db->prepare ("select * from request where id=?");
      $request_s->execute (array($picture["request"]));
      $request = $request_s->fetch();

      $erg .= "<tr>";
      
      $erg .= "<td>".viewButton("request.php?request=".$picture["request"],$_ansehen)."</td>";

      $erg .= "<td>";
      $erg .= "<a href=\"#pm$pics\" class=\"btn btn-default btn-lg\" data-toggle=\"modal\" onclick=\"document.getElementById('b$pics').innerHTML=document.getElementById('thn$pics').naturalWidth; document.getElementById('h$pics').innerHTML=document.getElementById('thn$pics').naturalHeight;\"><img id=\"thn$pics\" style=\"max-height:40px; max-width:150px;\" src=\"include/inhalt.php?typ=response&id=".$picture["id"]."\"> <i class=\"fa fa-search-plus\"></i></a>";

      $erg .= <<<LIMIT1
        <div class="modal fade" id="pm$pics" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <div class="alert alert-info" role="alert">
                  <div class="msgIcon"><i class="fa fa-search-plus fa-2x"></i></div>
                  <div class="msgText"><strong>Bilddetails</strong></div>
                </div>
              </div>
              <div class="modal-body">
                <table class="table table-condensed">
                  <tbody>
LIMIT1;
      $erg .= "<tr><td>Name</td><td>".bilderName($request["uri"])."</td></tr>";
      $erg .= "<tr><td>Server</td><td>".whoisify($verbindung["host"]? array_shift(idHostinfo($verbindung["host"])) : array_shift(ipHostinfo($verbindung["ip"])))."</td></tr>";
      $erg .= "<tr><td>Größe</td><td><span id=\"b$pics\"></span> &times; <span id=\"h$pics\"></span> Pixel</td></tr>";
      $erg .= "<tr><td>Typ</td><td>".array_pop(explode("/",$picture["mime"]))."</td></tr>";
      $erg .= <<<LIMIT1
                  </tbody>
                </table>
                <img class="img-responsive center-block canvas" id="p$pics" src="include/inhalt.php?typ=response&id={$picture["id"]}" onclick="this.style.backgroundColor=(this.style.backgroundColor=='#000000'||this.style.backgroundColor=='rgb(0, 0, 0)')?'#ffffff':'#000000';">
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
              </div>
            </div>
          </div>
        </div>
LIMIT1;
      $erg .= "</td>";

      if ($_REQUEST["show"]=="alle")
      {
        $aufzeichnung_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
        $aufzeichnung_s->execute (array($verbindung["aufzeichnung"]));
        $aufzeichnung = $aufzeichnung_s->fetch();
        $erg .= $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],$tableName);
      }

      $erg .= "<td".onevent($tableName)."><span class=\"full$tableName\">".$verbindung["_zeitd"]." <i class=\"fa fa-clock-o\"></i> </span><span class=\"compact$tableName\">$tableEllipsis</span>".$verbindung["_zeitt"]."<!--".$verbindung["nr"]."--></td>"; // sort by nr
      
      $erg .= faltZelle (bilderName($request["uri"]), $tableName);
      
      $erg .= "<td class=\"numeric\">".$picture["length"]."</td>";
      
      $erg .= faltZelle(array_pop(explode("/",strtolower($picture["mime"]))),$tableName);

      if (!$verbindung["host"])
      {
        $erg .= ipHostinfo ($verbindung["ip"], $tableName);
      }
      else
      {
        $erg .= idHostinfo ($verbindung["host"], $tableName);
      }

      $erg .= "</tr>";
      $pics++;
    }
    while ($picture = $select_s->fetch());
    
    $erg .= <<<LIMIT1
        </tbody>
      </table>
    </div>
LIMIT1;

    return array (true, $erg);
  }
}


$pics = 0;

if (!isset($_REQUEST["show"]))
{
  $_REQUEST["show"] = "jede";
}

titleAndHelp ("Bilder", <<<LIMIT1
Mit dieser Auswertung können die aufgezeichneten Bilder betrachtet werden.
Dabei werden alle Inhalte berücksichtigt, die den MIME-Typ "image/..." haben,
also etwa "image/jpeg" oder "image/gif".<br>
Die Bilder werden als Thumbnail angezeigt und können durch Klick in Originalgröße betrachtet werden.
LIMIT1
);

aufzeichnungsFilter ();

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = picRows (0, "Bilder");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Bilder aller Aufzeichnungen</h4>
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
    list ($res, $erg) = picRows ($aufzeichnung["id"], "Bilder".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Bilder der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
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

  list ($res, $erg) = picRows ($_REQUEST["show"], "Bilder");

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Bilder der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
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

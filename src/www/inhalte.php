<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: inhalte.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display specifics of the contents of a connection
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


function contRows ($aufzID, $tableName)
{
  global $db, $empfangenIcon, $versandtIcon, $tableEllipsis;
  
  $_ansehen = "Inhalt ansehen";
  
  if ($aufzID == 0)
  {
    $select_s = $db->prepare ("select 0 as sent,response.verbindung as verbindung,response.inhalt as inhalt,mime,length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id having length>0
                              union all
                              select 1 as sent,request.verbindung as verbindung,request.inhalt as inhalt,mime,length(inhalt.inhalt) as length from request,inhalt where request.inhalt=inhalt.id having length>0");
    $select_s->execute ();
  }
  else
  {
    $select_s = $db->prepare ("select 0 as sent,response.verbindung as verbindung,response.inhalt as inhalt,mime,length(inhalt.inhalt) as length from response,inhalt where response.inhalt=inhalt.id and response.aufzeichnung=? having length>0
                              union all
                              select 1 as sent,request.verbindung as verbindung,request.inhalt as inhalt,mime,length(inhalt.inhalt) as length from request,inhalt where request.inhalt=inhalt.id and request.aufzeichnung=? having length>0");
    $select_s->execute (array($aufzID,$aufzID));
  }
  
  if (($inhalt = $select_s->fetch()) == false)
  {
    return array (false, "Es wurden keine Inhalte übertragen.");
  }
  else
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}".($_REQUEST["show"]=="alle"? ", {}":"").", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");

    $erg .= <<<LIMIT1
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
            <th>Zeit</th>
            <th>Typ</th>
            <th></th>
            <th>Bytes</th>
            <th>Server</th>
          </tr>
        </thead>
        <tbody>
LIMIT1;
    
    do
    {
      $verbindung_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?");
      $verbindung_s->execute (array($inhalt["verbindung"]));
      $verbindung = $verbindung_s->fetch();

      $erg .= "<tr>";
      
      $erg .= "<td>".viewButton("inhalt.php?inhalt=".$inhalt["inhalt"],$_ansehen)."</td>";

      if ($_REQUEST["show"]=="alle")
      {
        $aufzeichnung_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
        $aufzeichnung_s->execute (array($verbindung["aufzeichnung"]));
        $aufzeichnung = $aufzeichnung_s->fetch();
        $erg .= $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],$tableName);
      }

      $erg .= "<td".onevent($tableName)."><span class=\"full$tableName\">".$verbindung["_zeitd"]." <i class=\"fa fa-clock-o\"></i> </span><span class=\"compact$tableName\">$tableEllipsis</span>".$verbindung["_zeitt"]."<!--".$verbindung["nr"]."--></td>"; // sort by nr
      
      $erg .= faltZelle (strtolower($inhalt["mime"]), $tableName);
      
      $erg .= "<td>".($inhalt["sent"]? $versandtIcon : $empfangenIcon)."<!--".$inhalt["sent"]."--></td>";
      
      $erg .= "<td class=\"numeric\">".$inhalt["length"]."</td>";
      
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
    while ($inhalt = $select_s->fetch());
    
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

titelHilfe ("Inhalte", <<<LIMIT1
Diese Auswertung betrachtet die per HTTP(S) übertragenen Inhalte der verschiedenen MIME-Typen.
In der Regel werden Inhalte als Teil der Response übertragen, 
aber auch im Request können Inhaltsdaten übermittelt werden.
LIMIT1
);

aufzeichnungsFilter ();

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = contRows (0, "Inhalte");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Inhalte aller Aufzeichnungen</h4>
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
    list ($res, $erg) = contRows ($aufzeichnung["id"], "Inhalte".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Inhalte der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
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

  list ($res, $erg) = contRows ($_REQUEST["show"], "Inhalte");

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Inhalte der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
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

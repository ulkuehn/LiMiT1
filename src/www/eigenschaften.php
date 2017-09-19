<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: eigenschaften.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to search recorded data for properties of defined
//              devices
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


function eigRows ($aufzID, $tableName)
{
  global $db, $nr, $my_name, $suchOrte;
  
  if ($aufzID==0)
  {
    $select_s = $db->prepare("select geraet.name as geraet,eigenschaft.name as name,eigenschaft.wert as wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet");
    $select_s->execute();
  }
  else
  {
    $select_s = $db->prepare("select geraet.name as geraet,eigenschaft.name as name,eigenschaft.wert as wert from geraet,eigenschaft,aufzeichnung where geraet.id=eigenschaft.geraet and geraet.id=aufzeichnung.geraet and aufzeichnung.id=?");
    $select_s->execute(array($aufzID));
  }

  if (($eigenschaft = $select_s->fetch()) == false)
  {
    return array (false, "Es wurden keine Eigenschaften definiert.");
  }
  else
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="table-responsive">
      <table id="$tableName" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Gerät</th>
            <th>Name</th>
            <th>Wert</th>
            <th>Funde</th>
          </tr>
        </thead>
        <tbody>
LIMIT1;

    $totfunde = 0;
    do
    {
      $funde = 0;
      $nadel = $eigenschaft["wert"];
      foreach ($suchOrte as $ort => $info)
      {
        if ($ort == "Inhalte")
        {
          if (!$aufzID)
          {
            $fund_s = $db->prepare ($info[3]);
            $fund_s->execute (array($nadel,$nadel));
          }
          else
          {
            $fund_s = $db->prepare ($info[3]." and aufzeichnung=?");
            $fund_s->execute (array($nadel,$nadel,$aufzID));
          }
        }
        else
        {
          if (!$aufzID)
          {
            $fund_s = $db->prepare ($info[3]);
            $fund_s->execute (array($nadel));
          }
          else
          {
            $fund_s = $db->prepare ($info[3]." and aufzeichnung=?");
            $fund_s->execute (array($nadel,$aufzID));
          }
        }
        $funde += $fund_s->fetchColumn();
      }
      
      if ($funde)
      {
        $erg .= "<tr>";

        $nr++;
        $show = $aufzID? $aufzID : "alle";
        $erg .= <<<LIMIT1
              <td>
                <form method="post" action="suche.php" id="suche$nr" target="${my_name}suche">
                  <input type="hidden" name="caseSwitch" value="on">
                  <input type="hidden" name="orte" value="alle">
                  <input type="hidden" name="show" value="$show">
                  <input type="hidden" name="suche" value="{$eigenschaft["wert"]}">
                  <a class="btn btn-info btn-xs" href="suche.php" onclick="document.getElementById('suche$nr').submit(); return false;" title="Wert dieser Eigenschaft suchen"><i class="fa fa-search fa-lg"></i></a>
                </form>
              </td>
LIMIT1;

        $erg .= faltZelle ($eigenschaft["geraet"], $tableName);

        $erg .= faltZelle ($eigenschaft["name"], $tableName);
        
        $erg .= faltZelle ($eigenschaft["wert"], $tableName);
        
        $erg .= "<td class=\"numeric\">$funde</td>";
        
        $erg .= "</tr>";
        
        $totfunde += $funde;
      }
    }
    while ($eigenschaft = $select_s->fetch());
    
    $erg .= <<<LIMIT1
        </tbody>
      </table>
    </div>
LIMIT1;

    return array ($totfunde>0, $totfunde>0? $erg : "Keine der definierten Geräteeigenschaften wurden gefunden");    
  }
}


if (!isset($_REQUEST["show"]))
{
  $_REQUEST["show"] = "jede";
}

titleAndHelp ("Geräteeigenschaften", <<<LIMIT1
<p>Mit dieser Auswertung können gezielt Werte gesucht werden, die als Eigenschaften der verwalteten Geräte definiert wurden.</p>
<p>Es werden sämtliche Geräteeigenschaften aufgelistet und jeweils angegeben, ob das Gerät bzw. die Eigenschaft in einer der vorhandenen Aufzeichnungen verwendet wird.</p>
LIMIT1
);

aufzeichnungsFilter ();
$nr = 0;

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = eigRows (0, "Eigenschaften");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Geräteeigenschaften aller Aufzeichnungen</h4>
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
    list ($res, $erg) = eigRows ($aufzeichnung["id"], "Header".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Geräteeigenschaften der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
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

  list ($res, $erg) = eigRows ($_REQUEST["show"], "Header");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Geräteeigenschaften der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
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

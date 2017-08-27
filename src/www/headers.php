<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: headers.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display http headers found in recorded data
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


function headRows ($aufzID, $tableName)
{
  global $db, $empfangenIcon, $versandtIcon;
  
  $_ansehen = "Details ansehen";
  $erg = "";

  if ($aufzID==0)
  {
    $select_s = $db->prepare("select feld,response from header group by feld,response");
    $select_s->execute();
  }
  else
  {
    $select_s = $db->prepare("select feld,response from header where aufzeichnung=? group by feld,response");
    $select_s->execute(array($aufzID));
  }

  if (($header = $select_s->fetch()) == false)
  {
    return array (false, "Es sind keine Header vorhanden.");
  }
  else
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}, {}, {}, {} ], order: [ [1,'asc'], [2,'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="table-responsive">
      <table id="$tableName" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Header</th>
            <th>Werte</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
LIMIT1;

    do
    {
      if ($aufzID==0)
      {
        $header_s = $db->prepare("select distinct wert from header where feld=? and response=?");
        $header_s->execute(array($header["feld"],$header["response"]));
      }
      else
      {
        $header_s = $db->prepare("select distinct wert from header where feld=? and response=? and aufzeichnung=?");
        $header_s->execute(array($header["feld"],$header["response"],$aufzID));
      }
      $werte = $header_s->fetchAll(PDO::FETCH_COLUMN, 0);
      asort ($werte,SORT_NATURAL|SORT_FLAG_CASE);
      
      $erg .= "<tr>";
      
      $erg .= "<td>".viewButton("header.php?feld=".urlencode($header["feld"])."&response=".$header["response"]."&aufzeichnung=$aufzID",$_ansehen)."</td>";
      
      $erg .= faltZelle ($header["feld"],$tableName);
      
      $erg .= faltZelle (implode("\n",array_values($werte)), $tableName);
      
      $erg .= "<td><div style=\"white-space: nowrap;\"><i class=\"fa " . ($header["response"]? "fa-globe":"fa-home") . "\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa " . ($header["response"]? "fa-home":"fa-globe") . "\"></i></div><!--" . ($header["response"]? 1:0) ."--></td>";
      
      $erg .= "</tr>";
    }
    while ($header = $select_s->fetch());
    
    $erg .= <<<LIMIT1
        </tbody>
      </table>
    </div>
LIMIT1;

    return array (true, $erg);    
  }
}


if (!isset($_REQUEST["show"]))
{
  $_REQUEST["show"] = "jede";
}


titelHilfe ("HTTP-Header", <<<LIMIT1
In dieser Auswertung werden sämtliche übertragenen HTTP-Header und die dabei übermittleten Werte aufgelistet.
Es werden sowohl Request- als auch Response-Header berücksichtigt.<br>
Für jeden Header-Typ kann im Drill-Down das Vorkommen der verschiedenen Werte analysiert werden.
LIMIT1
);

aufzeichnungsFilter ();

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = headRows (0, "Header");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Header aller Aufzeichnungen</h4>
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
    list ($res, $erg) = headRows ($aufzeichnung["id"], "Header".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Header der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
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

  list ($res, $erg) = headRows ($_REQUEST["show"], "Header");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Header der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
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

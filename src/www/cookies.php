<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: cookies.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display all cookies recorded in a specific or all
//              sessions
//              covers cookies received by the monitored device as well as
//              cookies sent by the device
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


function cookieRows ($aufzID, $tableName)
{
  global $db, $empfangenIcon, $versandtIcon;
  
  $_ansehen = "Cookie ansehen";

  $rows = "";
  $cookie_s = $db->prepare("select * from cookie");
  $cookie_s->execute();
  while ($cookie = $cookie_s->fetch())
  {
    if ($aufzID==0)
    {
      $setcookie_s = $db->prepare("select count(*) from setcookie where cookie=?");
      $setcookie_s->execute (array($cookie["id"]));
      $set = $setcookie_s->fetchColumn();
      $sendcookie_s = $db->prepare("select count(*) from sendcookie where cookie=?");
      $sendcookie_s->execute (array($cookie["id"]));
      $sent = $sendcookie_s->fetchColumn();
    }
    else
    {
      $setcookie_s = $db->prepare("select count(*) from setcookie where cookie=? and aufzeichnung=?");
      $setcookie_s->execute (array($cookie["id"],$aufzID));
      $set = $setcookie_s->fetchColumn();
      $sendcookie_s = $db->prepare("select count(*) from sendcookie where cookie=? and aufzeichnung=?");
      $sendcookie_s->execute (array($cookie["id"],$aufzID));
      $sent = $sendcookie_s->fetchColumn();
    }
    
    if ($set || $sent)
    {
      $rows .= "<tr>";
      $rows .= "<td>" . viewButton("cookie.php?cookie=".$cookie["id"]."&aufzeichnung=$aufzID",$_ansehen) . "</td>";
      $rows .= faltZelle ($cookie["name"], $tableName);

      $siteex = explode(".",$cookie["site"]);
      $tld = array_pop ($siteex);
      $domain = array_pop ($siteex);
      array_push ($siteex, $domain . "." . $tld);
      $nhi = nameHostinfo ($cookie["site"],$tableName);
      $rows .= $nhi==false? faltSortZelle ($cookie["site"], $tableName, true, implode(" ",array_reverse($siteex))) : $nhi;
      
      $rows .= "<td class=\"numeric\">$set mal<!--$set--></td>";
      $rows .= "<td class=\"numeric\">$sent mal<!--$sent--></td>";      
      $rows .= "</tr>";
    }
  }

  if ($rows == "")
  {
    return array (false, "Es wurden weder Cookies empfangen noch versandt.");
  }
  else
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}, {}, {}, {type:'num'}, {type:'num'} ], order: [ [1,'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="table-responsive">
      <table id="$tableName" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Name</th>
            <th>Site</th>
            <th><span class="compact$tableName">$empfangenIcon</span><span class="full$tableName">empfangen</span></th>
            <th><span class="compact$tableName">$versandtIcon</span><span class="full$tableName">versandt</span></th>
          </tr>
        </thead>
        <tbody>
          $rows
        </tbody>
      </table>
    </div>
LIMIT1;

    return array (true, $erg);
  }
}


titleAndHelp ("Cookies", <<<LIMIT1
Mit dieser Auswertung lassen sich die Cookies erkennen, die in den aufgezeichneten Verbindungen enthalten sind.
<br>
Dabei werden sowohl empfangene Cookies (d.h. solche, die von Internet-Servern stammen) berücksichtigt als auch
versandte Cookies (d.h. solche, die an Internet-Server zurückübermittelt wurden).
<br>
Sind mehrere Aufzeichnungen vorhanden, kann die Auswertung auf einzelne Aufzeichnungen begrenzt werden oder die Cookies
sämtlicher Aufzeichnungen in eine gemeinsamen Tabelle dargestellt werden.
LIMIT1
);

aufzeichnungsFilter ();


if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = cookieRows (0, "Cookies");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Cookies aller Aufzeichnungen</h4>
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
    list ($res, $erg) = cookieRows ($aufzeichnung["id"], "Cookies".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Cookies der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
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

  list ($res, $erg) = cookieRows ($_REQUEST["show"], "Cookies");

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Cookies der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
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

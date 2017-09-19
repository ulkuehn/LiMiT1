<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: verweise.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display sites that are linked by referrers or other
//              means, possibly indicating some connection between the site
//              operators (more details provided by drilldown script
//              verweis.php)
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


function refRows ($aufzID, $tableName)
{
  global $db, $foldLength, $tableEllipsis;

  $_ansehen = "Verweisdetails ansehen";
  $connect = array ();
  
  /*
    Referer: Client gibt im Request-Header an verbundenen Host (zu) den Host an, der die Verbindung auslöst (von)
    Origin: wie Referer
    Location: Server (von) gibt im Response-Header verbundenen Host (zu) an
  */

  // Host <-- Referer oder Origin
  if ($aufzID==0)
  {
    $select_s = $db->prepare ("select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and (feld=? or feld=?)");
    $select_s->execute (array("Referer", "Origin"));
  }
  else
  {
    $select_s = $db->prepare ("select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and (feld=? or feld=?) and header.aufzeichnung=?");
    $select_s->execute (array("Referer", "Origin", $aufzID));
  }

  while ($zusammen = $select_s->fetch())
  {
    if ($zusammen["host"])
    {
      $zu = $zusammen["host"];
    }
    else
    {
      $host_s = $db->prepare ("select id from host where ip=?");
      $host_s->execute (array($zusammen["ip"]));
      $zu = $host_s->fetchColumn();
    }
    $zuname = array_shift (idHostinfo($zu));
    
    if (($vonname = parse_url ($zusammen["wert"])["host"]) != false || ($vonname = parse_url ("http://".$zusammen["wert"])["host"]) != false)
    {
      $host_s = $db->prepare ("select id from host where name=?");
      $host_s->execute (array($vonname));
      if ($von = $host_s->fetchColumn())
      {
        $vonname = array_shift (idHostinfo($von));
      }
      if ($vonname != $zuname)
      {
        $connect[$vonname][$zuname] = array();
      }
    }
  }
  

  // Host --> Location
  if ($aufzID==0)
  {
    $select_s = $db->prepare ("select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and feld=?");
    $select_s->execute (array("Location"));
  }
  else
  {
    $select_s = $db->prepare ("select wert,host,ip from header,verbindung where header.verbindung=verbindung.id and feld=? and header.aufzeichnung=?");
    $select_s->execute (array("Location", $aufzID));
  }
  
  while ($zusammen = $select_s->fetch())
  {
    if ($zusammen["host"])
    {
      $von = $zusammen["host"];
    }
    else
    {
      $host_s = $db->prepare ("select id from host where ip=?");
      $host_s->execute (array($zusammen["ip"]));
      $von = $host_s->fetchColumn();
    }
    $vonname = array_shift (idHostinfo($von));
    
    if (($zuname = parse_url ($zusammen["wert"])["host"]) != false || ($zuname = parse_url ("http://".$zusammen["wert"])["host"]) != false)
    {
      $host_s = $db->prepare ("select id from host where name=?");
      $host_s->execute (array($zuname));
      if ($zu = $host_s->fetchColumn())
      {
        $zuname = array_shift (idHostinfo($zu));
      }
      if ($vonname != $zuname)
      {
        $connect[$vonname][$zuname] = array();
      }
    }
  }  
  
  // indirekte Verbindungen ergänzen, d.h. wenn a -> b und b -> c auch a -> c (außer wenn a == c)
  do
  {
    $plus = 0;
    foreach ($connect as $von1 => $zus1)
    {
      foreach ($zus1 as $zu1 => $zus)
      {
        foreach ($connect[$von1][$zu1] as $zu2 => $zus)
        {        
          if ($zu2 != $von1 && !array_key_exists($zu2, $connect[$von1]))
          {
            $connect[$von1][$zu2] = array_merge ($connect[$von1][$zu1],array($zu1),$connect[$zu1][$zu2]);
            $plus++;
            #echo "<p>indirekt $von1 $zu2</p>";
          }
        }
      }
    }
  }
  while ($plus);


  if (!count($connect))
  {
    return array (false, "Es sind keine Verweise vorhanden.");
  }
  else
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}, {}, {}, {} ], order: [ [1,'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="table-responsive">
      <table id="$tableName" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>von Server</th>
            <th>auf Server</th>
            <th>über</th>
          </tr>
        </thead>
        <tbody>
LIMIT1;

    foreach ($connect as $von => $zus)
    {
      foreach ($zus as $zu => $uebers)
      {
        $erg .= "<tr>";

        $erg .= "<td>".viewButton("verweis.php?von=$von&zu=$zu&aufzeichnung=$aufzID",$_ansehen)."</td>";

        if ($h = nameHostinfo ($von, $tableName))
        {
          $erg .= $h;
        }
        else
        {
          $erg .= faltZelle ($von, $tableName);
        }
        
        if ($h = nameHostinfo ($zu, $tableName))
        {
          $erg .= $h;
        }
        else
        {
          $erg .= faltZelle ($zu, $tableName);
        }
        
        $uebs = array ();
        $erg .= "<td".onevent($tableName).">";
        foreach ($uebers as $ueber)
        {
          if ($h = nameHostinfo ($ueber))
          {
            $auth = array_shift ($h);
            array_push ($uebs, whoisify($auth) . "<span class=\"full$tableName\"><br>(" . implode(" * ", array_map("whoisify",$h)) . ")</span>");
          }
          else
          {
            array_push ($uebs, whoisify($ueber));
          }          
        }
        $erg .= implode("<br>",$uebs)."<!--".(count($uebers)+1)."--></td>";
        $erg .= "</tr>";
      }
    }
    $erg .= <<<LIMIT1
        </tbody>
      </table>
    </div>
LIMIT1;

    return array (true, $erg);
  }
}


titleAndHelp ("Verweise", <<<LIMIT1
Diese Auswertung betrachtet Verbindungen zwischen Servern, die durch explizite Verweise hergestellt werden.
Solche Verweise können durch Referer-Angaben, Origin- oder Location-Header hergestellt werden.
<br>
Referer-Verbindungen entstehen, wenn in eine HTML-Seite Elemente eines anderen Servers eingebunden sind, etwa ein Bild oder ein iFrame.
<br>
Origin- und Location-Header deuten auf eine Weiterleitung von einem Server zu einem anderen hin.
<br>
Neben den direkten Verbindungen über diese Elemente sind auch die indirekten aufgelistet (wenn a&rarr;b und b&rarr;c, dann auch a&rarr;c).
In der Spalte "über" ist erkennbar, über welche anderen Server diese Verbindung hergestellt wird.
LIMIT1
);

aufzeichnungsFilter ();

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = refRows (0, "Verweise");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Verweise aller Aufzeichnungen</h4>
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
    list ($res, $erg) = refRows ($aufzeichnung["id"], "Verweise".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Verweise der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
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

  list ($res, $erg) = refRows ($_REQUEST["show"], "Verweise");

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Verweise der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
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

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: verweis.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display details of site-to-site links
//              (drilldown for verweise.php)
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

$_ansehen = "Request ansehen";

titleAndHelp ("Verweisdetails", <<<LIMIT1
LIMIT1
);

$foldMe = tableFolder ("Verweis");

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Verweis</h4>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="Verweis" class="table table-hover">
            <tbody>
LIMIT1;
echo "<tr><td>von Server</td><td>";
if ($h = nameHostinfo ($_REQUEST["von"], "Verweis"))
{
  echo $h;
}
else
{
  echo faltZelle ($_REQUEST["von"], "Verweis");
}
echo "</td></tr>";
echo "<tr><td>zu Server</td><td>";
if ($h = nameHostinfo ($_REQUEST["zu"], "Verweis"))
{
  echo $h;
}
else
{
  echo faltZelle ($_REQUEST["zu"], "Verweis");
}
echo "</td></tr>";
echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
LIMIT1;


aufBegrenzt ($_REQUEST["aufzeichnung"]);

if ($_REQUEST["aufzeichnung"]==0)
{
  $select_s = $db->prepare ("select wert,host,ip,request,feld from header,verbindung where header.verbindung=verbindung.id and (feld=? or feld=?)");
  $select_s->execute (array("Referer", "Origin"));
}
else
{
  $select_s = $db->prepare ("select wert,host,ip,request,feld from header,verbindung where header.verbindung=verbindung.id and (feld=? or feld=?) and header.aufzeichnung=?");
  $select_s->execute (array("Referer", "Origin", $_REQUEST["aufzeichnung"]));
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
      if (!isset($connect[$vonname][$zuname]))
      {
        $connect[$vonname][$zuname] = array(array($vonname,$zuname));
        $requests[$vonname][$zuname] = array();
      }
      array_push ($requests[$vonname][$zuname], array($zusammen["request"],$zusammen["feld"],$zusammen["wert"]));
    }
  }
}
  

if ($_REQUEST["aufzeichnung"]==0)
{
  $select_s = $db->prepare ("select wert,host,ip,request,feld from header,verbindung where header.verbindung=verbindung.id and feld=?");
  $select_s->execute (array("Location"));
}
else
{
  $select_s = $db->prepare ("select wert,host,ip,request,feld from header,verbindung where header.verbindung=verbindung.id and feld=? and header.aufzeichnung=?");
  $select_s->execute (array("Location", $_REQUEST["aufzeichnung"]));
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
      if (!isset($connect[$vonname][$zuname]))
      {
        $connect[$vonname][$zuname] = array(array($vonname,$zuname));
        $requests[$vonname][$zuname] = array();
      }
      array_push ($requests[$vonname][$zuname], array($zusammen["request"],$zusammen["feld"],$zusammen["wert"]));
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
        #echo "<p>indirekt? $von1 $zu1 /// $von2 $zu2</p>";
        if ($zu2 != $von1 && !array_key_exists($zu2, $connect[$von1]))
        {
          $connect[$von1][$zu2] = array_merge ($connect[$von1][$zu1],$connect[$zu1][$zu2]);
          $plus++;
          #echo "<p>indirekt $von1 $zu2</p>";
        }
      }
    }
  }
}
while ($plus);


$tCnt = 0;
foreach ($connect[$_REQUEST["von"]][$_REQUEST["zu"]] as $vonzu)
{
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
LIMIT1;
  echo "<h4 class=\"panel-title\">",whoisify($vonzu[0])," &rarr; ",whoisify($vonzu[1]),"</h4>";
  echo <<<LIMIT1
      </div>
      <div class="panel-body">
LIMIT1;

  $tName = "t$tCnt";
  $tCnt++;
  
  echo tableSorter ($tName, "columns: [ {orderable:false, searchable:false}".(!$_REQUEST["aufzeichnung"]? ", {}":"").", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");
  $foldMe = tableFolder ($tName);

  echo <<<LIMIT1
        <div class="table-responsive">
          <table id="$tName" class="table table-hover">
            <thead>
              <tr>
                <th>$foldMe</th>
LIMIT1;
  
  if (!$_REQUEST["aufzeichnung"])
  {
    echo "<th>Aufzeichnung</th>";
  }
  echo <<<LIMIT1
        <th>Zeit</th>
        <th>Header</th>
        <th>Header-Wert</th>
        <th></th>
        <th>Request</th>
      </tr>
    </thead>
    <tbody>
LIMIT1;

  foreach ($requests[$vonzu[0]][$vonzu[1]] as $rhd)
  {
    $verbindung_s = $db->prepare ("select *,date_format(verbindung.zeit,'%e.%c.%Y') as _zeitd, date_format(verbindung.zeit,'%H:%i') as _zeitt from verbindung,request where request.verbindung=verbindung.id and request.id=?");
    $verbindung_s->execute (array($rhd[0]));
    $verbindung = $verbindung_s->fetch();
    
    echo "<tr>";
    
    echo "<td>",viewButton("request.php?request=".$rhd[0],$_ansehen),"</td>";
    
    if (!$_REQUEST["aufzeichnung"])
    {
      $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
      $select_s->execute (array($verbindung["aufzeichnung"]));
      $aufzeichnung = $select_s->fetch();
      echo $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],$tName);
    }

    echo "<td>",$verbindung["_zeitd"]," <i class=\"fa fa-clock-o\"></i> ",$verbindung["_zeitt"],"<!--",$verbindung["zeit"],"--></td>"; // sort by timestamp
    
    echo faltZelle ($rhd[1],$tName);
    
    echo faltZelle ($rhd[2],$tName);
    
    echo "<td>",($verbindung["typ"]=="https"||$verbindung["typ"]=="ssl")?"<i class=\"fa fa-key\"></i>":"","</td>";
    
    echo faltZelle ($verbindung["methode"]." ".$verbindung["uri"],$tName);
    
    echo "</tr>";
  }
  
  echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
LIMIT1;
}
  
include ("include/htmlend.php");

?>

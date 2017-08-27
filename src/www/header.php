<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: header.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display details of a http header (drill down script
//              of headers.php)
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

titelHilfe ("Headerdetails", <<<LIMIT1
LIMIT1
);

$foldMe = tableFolder ("Header");

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Header</h4>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="Header" class="table table-hover">
            <tbody>
              <tr><td>Name</td><td>{$_REQUEST["feld"]}</td></tr>
LIMIT1;
echo "<tr><td>Versand</td><td><i class=\"fa ",($_REQUEST["response"]? "fa-globe":"fa-home"),"\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa ",($_REQUEST["response"]? "fa-home":"fa-globe"),"\"></i> (",($_REQUEST["response"]? "Response":"Request"),"-Header)</td></tr>";
echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
LIMIT1;

aufBegrenzt ($_REQUEST["aufzeichnung"]);

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Verwendungen</h4>
      </div>
      <div class="panel-body">
LIMIT1;

if ($_REQUEST["aufzeichnung"]==0)
{
  $header_s = $db->prepare("select * from header where feld=? and response=?");
  $header_s->execute (array($_REQUEST["feld"],$_REQUEST["response"]));
}
else
{
  $header_s = $db->prepare("select * from header where feld=? and response=? and aufzeichnung=?");
  $header_s->execute (array($_REQUEST["feld"],$_REQUEST["response"],$_REQUEST["aufzeichnung"]));
}

if (($header = $header_s->fetch()) == false)
{
  echo "Der Header wurde nicht verwendet.";  
}
else
{
  echo tableSorter ("Headers", "columns: [ {orderable:false, searchable:false}".(!$_REQUEST["aufzeichnung"]? ", {}":"").", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");
  $foldMe = tableFolder ("Headers");

  echo <<<LIMIT1
        <div class="table-responsive">
          <table id="Headers" class="table table-hover">
            <thead>
              <tr>
                <th>$foldMe</th>
LIMIT1;
  if (!$_REQUEST["aufzeichnung"])
  {
    echo "<th>Aufzeichnung</th>";
  }
  echo <<<LIMIT1
                <th>Wert</th>
                <th>Server</th>
                <th>Zeit</th>
                <th></th>
                <th>Request</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

  do
  {
    $verbindung_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?");
    $verbindung_s->execute (array($header["verbindung"]));
    $verbindung = $verbindung_s->fetch();

    $request_s = $db->prepare ("select * from request where id=?");
    $request_s->execute (array($header["request"]));
    $request = $request_s->fetch();

    echo "<tr>";
    
    echo "<td>",viewButton("request.php?request=".$header["request"],$_ansehen),"</td>";
    
    if (!$_REQUEST["aufzeichnung"])
    {
      $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
      $select_s->execute (array($verbindung["aufzeichnung"]));
      $aufzeichnung = $select_s->fetch();
      echo $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],"Setcookies");
    }

    echo faltZelle ($header["wert"],"Headers");
    
    if (!$verbindung["host"])
    {
      echo ipHostinfo ($verbindung["ip"], "Headers");
    }
    else
    {
      echo idHostinfo ($verbindung["host"], "Headers");
    }

    echo "<td>",$verbindung["_zeitd"]," <i class=\"fa fa-clock-o\"></i> ",$verbindung["_zeitt"],"</td>";
    
    echo "<td>",($verbindung["typ"]=="https"||$verbindung["typ"]=="ssl"? "<i class=\"fa fa-key\"></i>" : ""),"</td>";

    echo faltZelle ($request["methode"]." ".$request["uri"],"Headers");
    
    echo "</tr>";
  }
  while ($header = $header_s->fetch());
  
  echo <<<LIMIT1
            </tbody>
          </table>
        </div>
LIMIT1;
}

echo <<<LIMIT1
      </div>
    </div>
  </div>
LIMIT1;

include ("include/htmlend.php");

?>


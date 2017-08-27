<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/suche.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to provide a specific occurance of a search item within
//              a source
//              the occurance index is given by parameter 'limit'
//
//==============================================================================
//==============================================================================


require_once ("constants.php");
require_once ("configuration.php");
require_once ("utility.php");
require_once ("database.php");

$_ansehen = "Details ansehen";

if ($_POST["ort"] == "Inhalte")
{
  if ($_POST["show"] == "alle")
  {
    $fund_s = $db->prepare ($suchOrte[$_POST["ort"]][2]." limit ".$_POST["limit"].",1");
    $fund_s->execute(array($_POST["nadel"],$_POST["nadel"]));
  }
  else
  {
    $fund_s = $db->prepare ($suchOrte[$_POST["ort"]][2]." and aufzeichnung=? limit ".$_POST["limit"].",1");
    $fund_s->execute (array($_POST["nadel"],$_POST["nadel"],$_REQUEST["show"]));
  }
}
else
{
  if ($_POST["show"] == "alle")
  {
    $fund_s = $db->prepare ($suchOrte[$_POST["ort"]][2]." limit ".$_POST["limit"].",1");
    $fund_s->execute (array($_POST["nadel"]));
  }
  else
  {
    $fund_s = $db->prepare ($suchOrte[$_POST["ort"]][2]." and aufzeichnung=? limit ".$_POST["limit"].",1");
    $fund_s->execute (array($_POST["nadel"],$_REQUEST["show"]));
  }
}
$fund = $fund_s->fetch();

$select_s = $db->prepare ("select *, date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?");
$select_s->execute (array($fund["verbindung"]));
$verbindung = $select_s->fetch();

list ($res, $weitere, $pos) = suchMarkierung ($_POST["ort"], $fund["inhalt"], $_POST["nadel"], $_POST["isReg"], $_POST["isCase"], $_POST["pos"]);

if ($res == "")
{
  echo "";
}
else
{
  if ($_POST["ort"]!="Inhalte")
  {
    echo "<td>" . viewButton("request.php?request=".$fund["request"], $_ansehen) . "</td>";
  }
  else
  {
    if ($fund["typ"]=="request" || $fund["typ"]=="response")
    {
      echo "<td>" . viewButton("request.php?request=".$fund["referenz"], $_ansehen) . "</td>";
    }
    else
    {
      echo "<td>" . viewButton("verbindung.php?verbindung=".$fund["referenz"], $_ansehen) . "</td>";
    }
  }

  if ($_POST["show"]=="alle")
  {
    $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
    $select_s->execute (array($verbindung["aufzeichnung"]));
    $aufzeichnung = $select_s->fetch();
    echo $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."</td>" : faltZelle($aufzeichnung["name"],"Fundstellen".$_POST["ort"]);
  }
  
  echo "<td>" . $verbindung["_zeitd"]. " <i class=\"fa fa-clock-o\"></i> ". $verbindung["_zeitt"] . "<!--" . $verbindung["zeit"] . "--></td>";

  if (!$verbindung["host"])
  {
    echo ipHostinfo ($verbindung["ip"], "Fundstellen".$_POST["ort"]);
  }
  else
  {
    echo idHostinfo ($verbindung["host"], "Fundstellen".$_POST["ort"]);
  }  

  $srvc = getservbyport ($verbindung["anport"],$verbindung["typ"]=="udp"? "udp":"tcp");
  echo "<td class=\"numeric\">",$verbindung["anport"],$srvc!=""? " ($srvc)":"","<!--",$verbindung["anport"],"--></td>";

  if ($_POST["ort"]=="HTTP_Requests" || ($_POST["ort"]=="HTTP_Header" && !$fund["response"]) || ($_POST["ort"]=="Inhalte" && ($fund["typ"]=="request" || $fund["typ"]=="udpsend")))
  {
    echo "<td><div style=\"white-space: nowrap;\"><i class=\"fa fa-home\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-globe\"></i></div></td>";
  }
  else if ($_POST["ort"]=="HTTP_Responses" || ($_POST["ort"]=="HTTP_Header" && $fund["response"]) || ($_POST["ort"]=="Inhalte" && ($fund["typ"]=="response" || $fund["typ"]=="udprcv")))
  {
    echo "<td><div style=\"white-space: nowrap;\"><i class=\"fa fa-globe\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-home\"></i></div></td>";
  }
  else
  {
    echo "<td><div style=\"white-space: nowrap;\"><i class=\"fa fa-home\"></i> <i class=\"fa fa-arrows-h\"></i> <i class=\"fa fa-globe\"></i></div></td>";
  }

  echo "<td>" . ($verbindung["typ"]=="https"||$verbindung["typ"]=="ssl"? "<i class=\"fa fa-key\"></i>" : "") . "</td>";

  if ($_POST["prepos"] == "")
  {
    echo "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-left\"></i></button></td>";
  }
  else
  {
    $pp = explode (",",$_POST["prepos"]);
    $ps = array_pop ($pp);
    $prepos = implode (",",$pp);
    
    echo "<td><button class=\"btn btn-info btn-xs\" title=\"vorherige Fundstelle\" onclick=\"suchErgebnis('{$_POST["id"]}','{$_POST["show"]}','{$_POST["ort"]}',{$_POST["limit"]},'",jsSave($_POST["nadel"]),"',{$_POST["isReg"]},{$_POST["isCase"]},'$prepos',$ps);\"><i class=\"fa fa-chevron-left\"></i></button></td>";
  }

  echo "<td class=\"break\"",onevent("Fundstellen".$_POST["ort"]),">$res</td>";

  if (!$weitere)
  {
    echo "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-right\"></i></button></td>";
  }
  else
  {
    echo "<td><button class=\"btn btn-info btn-xs\" title=\"nächste Fundstelle\" onclick=\"suchErgebnis('{$_POST["id"]}','{$_POST["show"]}','{$_POST["ort"]}',{$_POST["limit"]},'",jsSave($_POST["nadel"]),"',{$_POST["isReg"]},{$_POST["isCase"]},'{$_POST["prepos"]},{$_POST["pos"]}',$pos);\"><i class=\"fa fa-chevron-right\"></i></button></td>";
  }
}

?>

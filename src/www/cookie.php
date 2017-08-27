<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: cookie.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display the specifics of one cookie which has been
//              recorded in a session
//              the cookie is identified by its id in the respective 
//              database table
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

$cookie_s = $db->prepare("select * from cookie where id=?");
$cookie_s->execute (array($_REQUEST["cookie"]));
$cookie = $cookie_s->fetch();

titelHilfe ("Cookiedetails", <<<LIMIT1
In dieser Auswertung sind sämtliche Übertragungen des Cookies <strong>{$cookie["name"]}</strong> von der Site <strong>{$cookie["site"]}</strong> berücksichtigt.
"Site" bezeichnet dabei den Domain-Parameter des Set-Cookie-Headers, mit dem das Cookie gesetzt wird.
Er kann aus einer Domain- oder einer Server-Angabe bestehen.
<br>
Ein Cookie wird typischerweise nur einmal empfangen. Allerdings kann dies auch öfter erfolgen, wenn der Wert oder die
Gültigkeitsdauer des Cookies geändert werden soll. Ausnahmsweise ist es auch möglich, dass ein Cookie im Rahmen der Aufzeichnung
überhaupt nicht empfangen wurde. Dies kann daran liegen, dass das Cookie bereits im Browser vorhanden war oder dass es nicht
über einen Set-Cookie-Header, sondern per Javascript erzeugt wird.
LIMIT1
);

$foldMe = tableFolder ("Cookie");

$site = whoisify ($cookie["site"]);
echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Cookie</h4>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="Cookie" class="table table-hover">
            <tbody>
              <tr><td>Name</td><td>{$cookie["name"]}</td></tr>
              <tr><td>Site</td><td>$site</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
LIMIT1;

aufBegrenzt ($_REQUEST["aufzeichnung"]);


// Cookie-Empfang

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">$empfangenIcon Empfangen</h4>
      </div>
      <div class="panel-body">
LIMIT1;

if ($_REQUEST["aufzeichnung"]==0)
{
  $setcookie_s = $db->prepare("select *,date_format(expires,'%e.%c.%Y %H:%i') as _expires from setcookie where cookie=?");
  $setcookie_s->execute (array($cookie["id"]));
}
else
{
  $setcookie_s = $db->prepare("select *,date_format(expires,'%e.%c.%Y %H:%i') as _expires from setcookie where cookie=? and aufzeichnung=?");
  $setcookie_s->execute (array($cookie["id"],$_REQUEST["aufzeichnung"]));
}

if (($setcookie = $setcookie_s->fetch()) == false)
{
  echo "Das Cookie wurde nicht empfangen.";  
}
else
{
 echo tableSorter ("Setcookies", "columns: [ {orderable:false, searchable:false}".(!$_REQUEST["aufzeichnung"]? ", {}":"").", {}, {}, {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");
  $foldMe = tableFolder ("Setcookies");

  echo <<<LIMIT1
        <div class="table-responsive">
          <table id="Setcookies" class="table table-hover">
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
                <th>Wert</th>
                <th>von Server</th>
                <th></th>
                <th>Request</th>
                <th>Pfad</th>
                <th>Verfall</th>
                <th><span class="compactSetcookies">Dauer</span><span class="fullSetcookies">Speicherdauer</span></th>
                <th><span class="compactSetcookies">Eig.</span><span class="fullSetcookies">Eigenschaften</span></th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

  do
  {
    $verbindung_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung,request where request.verbindung=verbindung.id and request.id=?");
    $verbindung_s->execute (array($setcookie["request"]));
    $verbindung = $verbindung_s->fetch();
    
    echo "<tr>";
    
    echo "<td>",viewButton("request.php?request=".$setcookie["request"],$_ansehen),"</td>";
    
    if (!$_REQUEST["aufzeichnung"])
    {
      $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
      $select_s->execute (array($verbindung["aufzeichnung"]));
      $aufzeichnung = $select_s->fetch();
      echo $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],"Setcookies");
    }
  
    echo "<td".onevent("Setcookies")."><span class=\"fullSetcookies\">",$verbindung["_zeitd"]," <i class=\"fa fa-clock-o\"></i> ","</span><span class=\"compactSetcookies\">$tableEllipsis</span>",$verbindung["_zeitt"],"<!--",$verbindung["nr"],"--></td>"; // sort by nr
        
    echo faltZelle($setcookie["wert"],"Setcookies");
    
    if (!$verbindung["host"])
    {
      echo ipHostinfo ($verbindung["ip"], "Setcookies");
    }
    else
    {
      echo idHostinfo ($verbindung["host"], "Setcookies");
    }
    
    echo "<td>",($verbindung["typ"]=="https"||$verbindung["typ"]=="ssl"? "<i class=\"fa fa-key\"></i>" : ""),"</td>";
    
    echo faltZelle($verbindung["methode"]." ".$verbindung["uri"],"Setcookies");
    
    echo "<td>",$setcookie["domain"],$setcookie["path"],"</td>";
    
    if ($setcookie["expires"] < 0)
    {
      echo "<td>?</td>";
    }
    else if ($setcookie["expires"] == 0)
    {
      echo "<td></td>";
    }
    else
    {
      echo "<td".onevent("Setcookies")."><span class=\"compactSetcookies\">",explode(" ",$setcookie["_expires"])[0],"$tableEllipsis</span><span class=\"fullSetcookies\">",$setcookie["_expires"],"</span><!--",$setcookie["expires"],"--></td>";
    }
    
    if ($setcookie["valid"] < 0)
    {
      echo "<td>?<!--0--></td>";
    }
    else if ($setcookie["valid"] == 0)
    {
      echo "<td>Session<!--0--></td>";
    }
    else
    {
      $span = timeSpan($setcookie["valid"]);
      echo "<td".onevent("Setcookies")."><span class=\"compactSetcookies\">",explode(",",$span)[0],"$tableEllipsis</span><span class=\"fullSetcookies\">$span</span><!--",$setcookie["valid"],"--></td>";
    }
    
    echo "<td>";
    echo $setcookie["httponly"]? "<span class=\"compactSetcookies\"><i class=\"fa fa-code\" title=\"httponly\"></i></span><span class=\"fullSetcookies\">für Skripte nicht zugänglich (\"httponly\")</span><br>" : "";
    echo $setcookie["secure"]? "<span class=\"compactSetcookies\"><i class=\"fa fa-key\" title=\"secure\"></i></span><span class=\"fullSetcookies\">nur verschlüsselter Versand (\"secure\")</span><br>" : "";
    echo $setcookie["comment"]? "<span class=\"compactSetcookies\"><i class=\"fa fa-commenting-o\" title=\"comment\"></i></span><span class=\"fullSetcookies\">Kommentar: \"".$setcookie["comment"]."\"</span>" : "";
    echo "</td>";
    
    echo "</tr>";
  }
  while ($setcookie = $setcookie_s->fetch());
  
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


// Cookie-Versand

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">$versandtIcon Versandt</h4>
      </div>
      <div class="panel-body">
LIMIT1;

if ($_REQUEST["aufzeichnung"]==0)
{
  $sendcookie_s = $db->prepare("select * from sendcookie where cookie=?");
  $sendcookie_s->execute(array($cookie["id"]));
}
else
{
  $sendcookie_s = $db->prepare("select * from sendcookie where cookie=? and aufzeichnung=?");
  $sendcookie_s->execute(array($cookie["id"],$_REQUEST["aufzeichnung"]));
}

if (($sendcookie = $sendcookie_s->fetch()) == false)
{
  echo "Das Cookie wurde nicht versandt.";  
}
else
{
 echo tableSorter ("Sendcookies", "columns: [ {orderable:false, searchable:false}".(!$_REQUEST["aufzeichnung"]? ", {}":"").", {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");
  $foldMe = tableFolder ("Sendcookies");

  echo <<<LIMIT1
        <div class="table-responsive">
          <table id="Sendcookies" class="table table-hover">
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
                <th>Wert</th>
                <th>an Server</th>
                <th></th>
                <th>Request</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;
    
  do
  {
    $verbindung_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung,request where request.verbindung=verbindung.id and request.id=?");
    $verbindung_s->execute (array($sendcookie["request"]));
    $verbindung = $verbindung_s->fetch();
    
    echo "<tr>";
    
    echo "<td>",viewButton("request.php?request=".$sendcookie["request"],$_ansehen),"</td>";
    
    if (!$_REQUEST["aufzeichnung"])
    {
      $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
      $select_s->execute (array($verbindung["aufzeichnung"]));
      $aufzeichnung = $select_s->fetch();
      echo $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],"Sendcookies");
    }

    echo "<td".onevent("Sendcookies")."><span class=\"fullSendcookies\">",$verbindung["_zeitd"]," <i class=\"fa fa-clock-o\"></i> ","</span><span class=\"compactSendcookies\">$tableEllipsis</span>",$verbindung["_zeitt"],"<!--",$verbindung["nr"],"--></td>"; // sort by nr
    
    echo faltZelle ($sendcookie["wert"],"Sendcookies");
    
    if (!$verbindung["host"])
    {
      echo ipHostinfo ($verbindung["ip"], "Sendcookies");
    }
    else
    {
      echo idHostinfo ($verbindung["host"], "Sendcookies");
    }

    echo "<td>",($verbindung["typ"]=="https"||$verbindung["typ"]=="ssl"? "<i class=\"fa fa-key\"></i>" : ""),"</td>";
    
    echo faltZelle($verbindung["methode"]." ".$verbindung["uri"],"Sendcookies");
    
    echo "</tr>";
  }
  while ($sendcookie = $sendcookie_s->fetch());
  
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

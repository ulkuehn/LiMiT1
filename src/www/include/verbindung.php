<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/verbindung.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to show the currently selected connection and to provide 
//              access to all available connections of the currently selected
//              session
//
//==============================================================================
//==============================================================================


$_ansehen = "Verbindung ansehen";

if (isset($_GET["verbindung"]))
{
  $verbindungId = $_GET["verbindung"];
}
else if (isset($_GET["request"]))
{
  $select_s = $db->prepare ("select verbindung from request where id=?");
  $select_s->execute (array($_GET["request"]));
  $verbindungId = $select_s->fetchColumn();
}

if (isset($verbindungId))
{
  $select_s = $db->prepare ("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?");
  $select_s->execute (array($verbindungId));
  if ($verbindung = $select_s->fetch())
  {    
    $select_s = $db->prepare ("select id from verbindung where nr<? and aufzeichnung=? order by nr desc limit 1");
    $select_s->execute (array($verbindung["nr"], $verbindung["aufzeichnung"]));
    $zurueck = $zurueckInaktiv;
    if ($id = $select_s->fetchColumn())
    {
      $zurueck = "<a href=\"verbindung.php?verbindung=$id\">$zurueckButton</a>";
    }
    $select_s = $db->prepare ("select id from verbindung where nr>? and aufzeichnung=? order by nr asc limit 1");
    $select_s->execute (array($verbindung["nr"], $verbindung["aufzeichnung"]));
    $vor = $vorInaktiv;
    if ($id = $select_s->fetchColumn())
    {
      $vor = "<a href=\"verbindung.php?verbindung=$id\">$vorButton</a>";
    }

    $foldMe = tableFolder ("Verbindung");

    echo <<<LIMIT1
<div class="row nestedPanel">
  <div class="panel panel-primary">
    <div class="panel-heading">
      <h4 class="panel-title">$zurueck $vor Verbindung</h4>
    </div>
    <div class="panel-body">
      <div class="table-responsive">
        <table id="Aufzeichnung" class="table">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Zeit</th>
            <th>Typ</th>
            <th>User-Agent</th>
            <th>von Port</th>
            <th>zu Server</th>
            <th>zu Port</th>
            <th>Länge<span class="fullVerbindung"> (Bytes)</span></th>
          </tr>
        </thead>
        <tbody>
LIMIT1;
 
    echo "<tr>";
    echo "<td>",viewButton ("verbindung.php?verbindung=".$verbindung["id"],$_ansehen),"</td>";
    echo "<td",onevent("Verbindung"),"><span class=\"fullVerbindung\">",$verbindung["_zeitd"]," <i class=\"fa fa-clock-o\"></i> ","</span><span class=\"compactVerbindung\">$tableEllipsis</span>",$verbindung["_zeitt"],"<!--",$verbindung["nr"],"--></td>"; // sort by nr

    echo "<td",onevent("Verbindung"),">",$verbindung["typ"],"</td>";
    
    $ua = "";
    if (substr($verbindung["typ"],0,4) == "http")
    {
      $ua_s = $db->prepare ("select useragent from {$verbindung["typ"]} where verbindung=?");
      $ua_s->execute (array($verbindung["id"]));
      $ua = $ua_s->fetchColumn();
    }
    echo faltZelle ($ua, "Verbindung");

    $srvc = getservbyport ($verbindung["vonport"],$verbindung["typ"]=="udp"? "udp":"tcp");
    echo "<td class=\"numeric\"",onevent("Verbindung"),">",$verbindung["vonport"],$srvc!=""? " ($srvc)":"","<!--",$verbindung["vonport"],"--></td>";
      
    if (!$verbindung["host"])
    {
      echo ipHostinfo ($verbindung["ip"], "Verbindung");
    }
    else
    {
      echo idHostinfo ($verbindung["host"], "Verbindung");
    }
    
    $srvc = getservbyport ($verbindung["anport"],$verbindung["typ"]=="udp"? "udp":"tcp");
    echo "<td class=\"numeric\"",onevent("Verbindung"),">",$verbindung["anport"],$srvc!=""? " ($srvc)":"","<!--",$verbindung["anport"],"--></td>";
    echo "<td class=\"numeric\"",onevent("Verbindung"),">",$verbindung["laenge"],"</td>";

    echo <<<LIMIT1
          </tr>
        </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
LIMIT1;
  }
}

?>

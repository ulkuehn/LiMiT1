<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/aufzeichnung.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to show the currently selected session and to provide 
//              access to all available sessions
//
//==============================================================================
//==============================================================================


$_ansehen = "Aufzeichnung ansehen";


if (isset($_GET["aufzeichnung"]))
{
  $aufzeichnungId = $_GET["aufzeichnung"];
}
else if (isset($_GET["verbindung"]))
{
  $select_s = $db->prepare("select aufzeichnung from verbindung where id=?");
  $select_s->execute(array($_GET["verbindung"]));
  $aufzeichnungId = $select_s->fetchColumn();
}
else if (isset($_GET["request"]))
{
  $select_s = $db->prepare("select aufzeichnung from request where id=?");
  $select_s->execute(array($_GET["request"]));
  $aufzeichnungId = $select_s->fetchColumn();
}

if (isset($aufzeichnungId))
{
  $select_s = $db->prepare("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt, timediff(ende,start) as _dauer, unix_timestamp(ende)-unix_timestamp(start) as _diff from aufzeichnung where id=?");
  $select_s->execute(array($aufzeichnungId));
  if ($aufzeichnung = $select_s->fetch())
  {
    $select_s = $db->prepare("select id from aufzeichnung where id<? order by id desc limit 1");
    $select_s->execute(array($aufzeichnung["id"]));
    $zurueck = $zurueckInaktiv;
    if ($id = $select_s->fetchColumn())
    {
      $zurueck = "<a href=\"aufzeichnung.php?aufzeichnung=$id\">$zurueckButton</a>";
    }
    $select_s = $db->prepare("select id from aufzeichnung where id>? order by id asc limit 1");
    $select_s->execute(array($aufzeichnung["id"]));
    $vor = $vorInaktiv;
    if ($id = $select_s->fetchColumn())
    {
      $vor = "<a href=\"aufzeichnung.php?aufzeichnung=$id\">$vorButton</a>";
    }
    
    $foldMe = tableFolder ("Aufzeichnung");

    echo <<<LIMIT1
<div class="row nestedPanel">
  <div class="panel panel-primary">
    <div class="panel-heading">
      <h4 class="panel-title">$zurueck $vor Aufzeichnung</h4>
    </div>
    <div class="panel-body">
      <div class="table-responsive">
        <table id="Aufzeichnung" class="table">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Bezeichnung</th>
            <th>Beginn<span class="fullAufzeichnung"> der Aufzeichnung</span></th>
            <th>Dauer<span class="fullAufzeichnung"> der Aufzeichnung</span></th>
            <th>Gerät</th>
            <th>Infos</th>
            <th><span class="compactAufzeichnung">Verb.</span><span class="fullAufzeichnung">Verbindungen</span></th>
          </tr>
        </thead>
        <tbody>
LIMIT1;

    echo "<tr>";
    
    echo "<td>",viewButton("aufzeichnung.php?aufzeichnung=".$aufzeichnung["id"],"$_ansehen"),"</td>";
    
    echo faltZelle ($aufzeichnung["name"], "Aufzeichnung");
    
    echo "<td",onevent("Aufzeichnung"),">",$aufzeichnung["_startd"]," <i class=\"fa fa-clock-o\"></i> ",$aufzeichnung["_startt"],"</td>";

    echo "<td",onevent("Aufzeichnung"),">",zeitDauer($aufzeichnung["_diff"]),"</td>";

    $geraet_s = $db->prepare("select * from geraet where id=?");
    $geraet_s->execute(array($aufzeichnung["geraet"]));
    echo faltZelle ($geraet_s->fetch()["name"], "Aufzeichnung");
    
    echo faltZelle ($aufzeichnung["info"], "Aufzeichnung");

    $vcount_s = $db->prepare("select count(*) from verbindung where aufzeichnung=?");
    $vcount_s->execute(array($aufzeichnung["id"]));
    $verbindungCT = $vcount_s->fetchColumn();
    echo "<td class=\"numeric\"",onevent("Aufzeichnung"),">$verbindungCT</td>";
      
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

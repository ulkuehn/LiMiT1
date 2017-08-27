<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/request.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to show the currently selected request and to provide 
//              access to all available requests of the currently selected
//              connection
//
//==============================================================================
//==============================================================================


$_ansehen = "Request ansehen";

if (isset($_GET["request"]))
{
  $select_s = $db->prepare("select * from request where id=?");
  $select_s->execute(array($_GET["request"]));
  if ($request = $select_s->fetch())
  {
    $select_s = $db->prepare("select id from request where id<? and verbindung=? order by id desc limit 1");
    $select_s->execute(array($request["id"],$request["verbindung"]));
    $zurueck = $zurueckInaktiv;
    if ($id = $select_s->fetchColumn())
    {
      $zurueck = "<a href=\"request.php?request=$id\">$zurueckButton</a>";
    }
    $select_s = $db->prepare("select id from request where id>? and verbindung=? order by id asc limit 1");
    $select_s->execute(array($request["id"],$request["verbindung"]));
    $vor = $vorInaktiv;
    if ($id = $select_s->fetchColumn())
    {
      $vor = "<a href=\"request.php?request=$id\">$vorButton</a>";
    }
  
    $foldMe = tableFolder ("Request");

    echo <<<LIMIT1
<div class="row nestedPanel">
  <div class="panel panel-primary">
    <div class="panel-heading">
      <h4 class="panel-title">$zurueck $vor Request</h4>
    </div>
    <div class="panel-body">
      <div class="table-responsive">
        <table id="Request" class="table">
          <thead>
            <tr>
              <th>$foldMe</th>
              <th>Methode</th>
              <th>URI</th>
              <th>Response</th>
            </tr>
          </thead>
          <tbody>
LIMIT1;

    $response_s = $db->prepare ("select * from response where request=?");
    $response_s->execute (array($request["id"]));
    $response = $response_s->fetch();
    
    echo "<tr>";
    echo "<td>",viewButton("request.php?request=".$request["id"],$_ansehen),"</td>";
    echo "<td>",$request["methode"],"</td>";
    echo faltZelle ($request["uri"], "Request");
    echo "<td>",statusBadge($response["status"])," ",$response["status"]," ",$response["statustext"],"</td>";

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

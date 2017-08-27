<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: verbindung.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display details of a connection
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

titelHilfe ("Inhalte einer Verbindung", <<<LIMIT1
<p>In dieser Ansicht ist es möglich, schnell durch die vorhandenen Aufzeichnungen und durch deren Verbindungen zu blättern und dabei die Eigenschaften und die Inhalte der Verbindung zu betrachten.</p>
LIMIT1
);

echo <<<LIMIT1
<div class="row">
  <div class="panel-group" id="about" role="tablist">
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#navi">
        <h4 class="panel-title">
          Navigation
        </h4>
      </div>
      <div id="navi" class="panel-collapse collapse in" role="tabpanel">
        <div class="panel-body">
LIMIT1;

include ("include/aufzeichnung.php");
include ("include/verbindung.php");

echo <<<LIMIT1
        </div>
      </div>
    </div> 
LIMIT1;


// Verschlüsselung
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#crypt">
        <h4 class="panel-title">
LIMIT1;

if ($verbindung["typ"]=="https" || $verbindung["typ"]=="ssl")
{
  echo <<<LIMIT1
          Verschlüsselung
        </h4>
      </div>
      <div id="crypt" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
LIMIT1;
  
  include ("include/verschluesselung.php");
}
else
{
  echo <<<LIMIT1
          <span class="emptyPanel">Verschlüsselung</span>
        </h4>
      </div>
      <div id="crypt" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <p>Die Verbindung ist nicht verschlüsselt.</p>
LIMIT1;
}
echo <<<LIMIT1
        </div>
      </div>
    </div> 
LIMIT1;


// Zertifikat
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#cert">
        <h4 class="panel-title">
LIMIT1;

if ($verbindung["typ"]=="https" || $verbindung["typ"]=="ssl")
{
  echo <<<LIMIT1
          Zertifikat
        </h4>
      </div>
      <div id="cert" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
LIMIT1;
  
  include ("include/zertifikat.php");
}
else
{
  echo <<<LIMIT1
          <span class="emptyPanel">Zertifikat</span>
        </h4>
      </div>
      <div id="cert" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <p>Die Verbindung ist nicht verschlüsselt, daher ist kein Zertifikat vorhanden.</p>
LIMIT1;
}
echo <<<LIMIT1
        </div>
      </div>
    </div> 
LIMIT1;


if ($verbindung["typ"]=="http" || $verbindung["typ"]=="https")
{
  // gesetzte Cookies
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#setcookies">
        <h4 class="panel-title">
LIMIT1;

  $select_s = $db->prepare("select count(*) from setcookie,cookie where setcookie.cookie=cookie.id and setcookie.verbindung=?");
  $select_s->execute(array($_GET["verbindung"]));
  $set = $select_s->fetchColumn();
  
  if (!$set)
  {
    echo <<<LIMIT1
          <span class="emptyPanel">Empfangene Cookies</span>
        </h4>
      </div>
      <div id="setcookies" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>Es wurden keine Cookies empfangen.</p>
LIMIT1;
  }
  else
  {
    echo <<<LIMIT1
          Empfangene Cookies
        </h4>
      </div>
      <div id="setcookies" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
LIMIT1;

    include ("include/setcookies.php");
  }
  echo <<<LIMIT1
        </div>
      </div>
    </div>
LIMIT1;
    

  // versandte Cookies
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#sentcookies">
        <h4 class="panel-title">
LIMIT1;
    
  $select_s = $db->prepare("select count(*) from sendcookie,cookie where sendcookie.cookie=cookie.id and sendcookie.verbindung=?");
  $select_s->execute(array($_GET["verbindung"]));
  $sent = $select_s->fetchColumn();

  if (!$sent)
  {
    echo <<<LIMIT1
          <span class="emptyPanel">Versandte Cookies</span>
        </h4>
      </div>
      <div id="sentcookies" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>Es wurden keine Cookies versandt.</p>
LIMIT1;
  }
  else
  {
    echo <<<LIMIT1
          Versandte Cookies
        </h4>
      </div>
      <div id="sentcookies" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
LIMIT1;
    include ("include/sentcookies.php");
  }
  echo <<<LIMIT1
        </div>
      </div>
    </div> 
LIMIT1;


  // Requests
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#req">
        <h4 class="panel-title">
LIMIT1;

  $select_s = $db->prepare ("select * from request where verbindung=? order by id");
  $select_s->execute(array($_GET["verbindung"]));
  if (($request = $select_s->fetch()) == false)
  {
    echo <<<LIMIT1
          <span class="emptyPanel">Requests</span>
        </h4>
      </div>
      <div id="req" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>In dieser Verbindung sind keine Requests enthalten.</p>
LIMIT1;
  }
  else
  {
    $_ansehen = "Request ansehen";
    
    echo tableSorter ("Requests", "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {} ], order: []");
    $foldMe = tableFolder ("Requests");
    
    echo <<<LIMIT1
          Requests
        </h4>
      </div>
      <div id="req" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <div class="table-responsive">
            <table id="Requests" class="table table-hover">
              <thead>
                <tr>
                  <th>$foldMe</th>
                  <th>Methode</th>
                  <th>URI</th>
                  <th>Response</th>
                  <th>Typ</th>
                </tr>
              </thead>
              <tbody>
LIMIT1;

    do
    {
      $response_s = $db->prepare ("select * from response where request=?");
      $response_s->execute (array($request["id"]));
      $response = $response_s->fetch();
      
      echo "<tr>";

      echo "<td>",viewButton("request.php?request=".$request["id"],$_ansehen),"</td>";

      echo "<td>",$request["methode"],"</td>";

      echo faltZelle ($request["uri"], "Requests");

      echo "<td>",statusBadge($response["status"])," ",$response["status"]," ",$response["statustext"],"</td>";

      echo faltZelle ($response["mime"], "Requests");

      echo "</tr>";
    }
    while ($request = $select_s->fetch());

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
}


// Inhalt
if ($verbindung["laenge"])
{
  $select_s = $db->prepare ("select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?");
  $select_s->execute (array($aufzeichnung["geraet"]));
  $eigenschaften = $select_s->fetchAll(PDO::FETCH_COLUMN, 0);  
}

# UDP (zwei Panels)
if ($verbindung["typ"]=="udp")
{
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#sent">
        <h4 class="panel-title">
LIMIT1;

  $select_s = $db->prepare ("select * from inhalt where typ='udpsend' and referenz=?");
  $select_s->execute (array($_GET["verbindung"]));
  $versandt = $select_s->fetch();

  if ($versandt["inhalt"]=="")
  {
    echo <<<LIMIT1
            <span class="emptyPanel">Versandte Daten</span>
          </h4>
        </div>
        <div id="sent" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body">
          <p>Es wurden keine Daten versandt.</p>
LIMIT1;
  }
  else
  {
    echo <<<LIMIT1
            Versandte Daten
          </h4>
        </div>
        <div id="sent" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body" id="sentc">
LIMIT1;

    if (strlen($versandt["inhalt"])>$inhaltTrunc*$truncScale)
    {
      zeigeInhalt (0, substr($versandt["inhalt"],0,$inhaltTrunc), $eigenschaften);
      zeigeTrunc ($inhaltTrunc, strlen($versandt["inhalt"]), 0, "sentc", $versandt["id"]);
    }
    else
    {
      zeigeInhalt (0, $versandt["inhalt"], $eigenschaften);
    }
  }
  echo <<<LIMIT1
        </div>
      </div>
    </div> 
LIMIT1;
  
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#rcvd">
        <h4 class="panel-title">
LIMIT1;

  $select_s = $db->prepare ("select * from inhalt where typ='udprcv' and referenz=?");
  $select_s->execute (array($_GET["verbindung"]));
  $empfangen = $select_s->fetch();

  if ($empfangen["inhalt"]=="")
  {
    echo <<<LIMIT1
            <span class="emptyPanel">Empfangene Daten</span>
          </h4>
        </div>
        <div id="rcvd" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body">
          <p>Es wurden keine Daten empfangen.</p>
LIMIT1;
  }
  else
  {
    echo <<<LIMIT1
            Empfangene Daten
          </h4>
        </div>
        <div id="rcvd" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body" id="rcvdc">
LIMIT1;

    if (strlen($empfangen["inhalt"])>$inhaltTrunc*$truncScale)
    {
      zeigeInhalt (1, substr($empfangen["inhalt"],0,$inhaltTrunc), $eigenschaften);
      zeigeTrunc ($inhaltTrunc, strlen($empfangen["inhalt"]), 1, "rcvdc", $empfangen["id"]);
    }
    else
    {
      zeigeInhalt (1, $empfangen["inhalt"], $eigenschaften);
    }
  }
  echo <<<LIMIT1
        </div>
      </div>
    </div> 
LIMIT1;
}

else # non-UDP
{
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#cont">
        <h4 class="panel-title">
LIMIT1;

  if (!$verbindung["laenge"])
  {
    echo <<<LIMIT1
            <span class="emptyPanel">Inhalt</span>
          </h4>
        </div>
        <div id="cont" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body">
          <p>Der Inhalt dieser Verbindung ist leer.</p>
LIMIT1;
  }
  else
  {
    $active = isset($_GET["inhalt"]) && $_GET["inhalt"]? " in":"";

    echo <<<LIMIT1
            Inhalt
          </h4>
        </div>
        <div id="cont" class="panel-collapse collapse$active" role="tabpanel">
          <div class="panel-body" id="verbc">
LIMIT1;

    $select_s = $db->prepare ("select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?");
    $select_s->execute (array($aufzeichnung["geraet"]));
    $eigenschaften = $select_s->fetchAll(PDO::FETCH_COLUMN, 0);  
    
    # TCP/SSL (non HTTP)
    if ($verbindung["typ"] == "tcp" || $verbindung["typ"] == "ssl")
    {
      $select_s = $db->prepare ("select * from inhalt where typ='tcp' and referenz=?");
      $select_s->execute (array($_GET["verbindung"]));
      $inhalt = $select_s->fetch();

      if ((!isset($_GET["inhalt"]) || !$_GET["inhalt"]) && strlen($inhalt["inhalt"])>$inhaltTrunc*$truncScale)
      {
        zeigeInhalt (0, substr($inhalt["inhalt"],0,$inhaltTrunc), $eigenschaften);
        zeigeTrunc ($inhaltTrunc, strlen($inhalt["inhalt"]), 0, "verbc", $inhalt["id"]);
      }
      else
      {
        zeigeInhalt (0, $inhalt["inhalt"], $eigenschaften);
      }    
    }
    
    # HTTP(S)
    else
    {
      $inhalt = "";
      $select_s = $db->prepare ("select * from request where verbindung=? order by id");
      $select_s->execute (array($_GET["verbindung"]));
      while ($request = $select_s->fetch())
      {
        $inhalt .= $request["methode"]." ".$request["uri"]." ".$request["version"]."\n";
        $header_s = $db->prepare ("select feld,wert from header where request=? and not response order by id");
        $header_s->execute (array($request["id"]));
        while ($header = $header_s->fetch())
        {
          $inhalt .= $header["feld"].": ".$header["wert"]."\n";
        }
        $inhalt .= "\n";
        if ($request["inhaltroh"] || $request["inhalt"])
        {
          $inhalt_s = $db->prepare ("select inhalt from inhalt where id=?");
          $inhalt_s->execute (array($request["inhaltroh"]? $request["inhaltroh"]:$request["inhalt"]));
          $inhalt .= $inhalt_s->fetchColumn();
        }

        $response_s = $db->prepare ("select * from response where request=?");
        $response_s->execute (array($request["id"]));
        $response = $response_s->fetch();
        $inhalt .= $response["version"]." ".$response["status"]." ".$response["statustext"]."\n";
        $header_s = $db->prepare ("select feld,wert from header where request=? and response order by id");
        $header_s->execute (array($request["id"]));
        while ($header = $header_s->fetch())
        {
          $inhalt .= $header["feld"].": ".$header["wert"]."\n";
        }
        $inhalt .= "\n";      
        if ($response["inhaltroh"] || $response["inhalt"])
        {
          $inhalt_s = $db->prepare ("select inhalt from inhalt where id=?");
          $inhalt_s->execute (array($response["inhaltroh"]? $response["inhaltroh"]:$response["inhalt"]));
          $inhalt .= $inhalt_s->fetchColumn();
        }
      }
      
      if ((!isset($_GET["inhalt"]) || !$_GET["inhalt"]) && strlen($inhalt)>$inhaltTrunc*$truncScale)
      {
        zeigeInhalt (0, substr($inhalt,0,$inhaltTrunc), $eigenschaften);
        zeigeTrunc ($inhaltTrunc, strlen($inhalt), 0, "verbc", -$_GET["verbindung"]);
      }
      else
      {
        zeigeInhalt (0, $inhalt, $eigenschaften);
      }
    }
  }
}
  
echo <<<LIMIT1
        </div>
      </div>
    </div>
  </div>
</div>
LIMIT1;

include ("include/htmlend.php");

?>

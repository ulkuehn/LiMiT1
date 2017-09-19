<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: request.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display details of a http request and navigate
//              through all requests of a recorded connection
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

titleAndHelp ("Details eines Requests", <<<LIMIT1
In dieser Ansicht ist es möglich, schnell durch die vorhandenen Aufzeichnungen, deren Verbindungen und deren Requests zu blättern und dabei die Eigenschaften und die Inhalte des Requests zu betrachten.
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
include ("include/request.php");

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


// gesetzte Cookies
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#setcookies">
        <h4 class="panel-title">
LIMIT1;

$select_s = $db->prepare("select count(*) from setcookie,cookie where setcookie.cookie=cookie.id and setcookie.request=?");
$select_s->execute(array($_GET["request"]));
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
  
$select_s = $db->prepare("select count(*) from sendcookie,cookie where sendcookie.cookie=cookie.id and sendcookie.request=?");
$select_s->execute(array($_GET["request"]));
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


// Request-Header
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#reqh">
        <h4 class="panel-title">
LIMIT1;

$select_s = $db->prepare ("select * from header where response=0 and request=?");
$select_s->execute (array($request["id"]));
if (($header = $select_s->fetch()) == false)
{
  echo <<<LIMIT1
          <span class="emptyPanel">Request-Header</span>
        </h4>
      </div>
      <div id="reqh" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>Der Request enthält keine Header.</p>
LIMIT1;
}
else
{
  echo tableSorter ("RequestHeader", "order: []");
  $foldMe = tableFolder ("RequestHeader");
  
  echo <<<LIMIT1
          Request-Header
        </h4>
      </div>
      <div id="reqh" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <div class="table-responsive">
            <table id="RequestHeader" class="table table-hover">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Wert</th>
                </tr>
              </thead>
              <tbody>
LIMIT1;

  do
  {
    echo "<tr>";
    echo "<td>",$header["feld"],"</td>";
    echo faltZelle ($header["wert"], "RequestHeader");
    echo "</tr>";
  }
  while ($header = $select_s->fetch());
  
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


$select_s = $db->prepare("select * from inhalt where id=?");
$select_s->execute(array($request["inhalt"]));
$reqInhalt = $select_s->fetch();

$select_s = $db->prepare("select * from inhalt where id=?");
$select_s->execute(array($response["inhalt"]));
$respInhalt = $select_s->fetch();

// Geräteeigenschaften auslesen
$eigenschaften = array ();
if ($reqInhalt["inhalt"] != "" || $respInhalt["inhalt"] != "")
{
  $select_s = $db->prepare("select aufzeichnung from verbindung,request where verbindung.id=request.verbindung and request.id=?");
  $select_s->execute(array($_GET["request"]));
  if ($aufzeichnungId = $select_s->fetchColumn())
  {
    $select_s = $db->prepare("select * from aufzeichnung where id=?");
    $select_s->execute(array($verbindung["aufzeichnung"]));
    if ($aufzeichnung = $select_s->fetch())
    {
      $select_s = $db->prepare ("select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?");
      $select_s->execute(array($aufzeichnung["geraet"]));
      $eigenschaften = $select_s->fetchAll(PDO::FETCH_COLUMN, 0);
    }
  }
}


// Request-Parameter
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#reqp">
        <h4 class="panel-title">
LIMIT1;

$post = $reqInhalt["inhalt"];
$get = parse_url ($request["uri"], PHP_URL_QUERY);

if ($post=="" && $get=="")
{
  echo <<<LIMIT1
          <span class="emptyPanel">Request-Parameter</span>
        </h4>
      </div>
      <div id="reqp" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>Es wurden keine Parameter übertragen.</p>
LIMIT1;
}
else
{
  echo tableSorter ("RequestParameter", "order: []");
  $foldMe = tableFolder ("RequestParameter");
  
  echo <<<LIMIT1
          Request-Parameter
        </h4>
      </div>
      <div id="reqp" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <div class="table-responsive">
            <table id="RequestParameter" class="table table-hover">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Wert</th>
                  <th>Quelle</th>
                </tr>
              </thead>
              <tbody>
LIMIT1;

  if ($post != "")
  {
    foreach (preg_split("/[&;]/", $post) as $par)
    {
      list ($key,$val) = explode ("=", $par, 2);
      echo "<tr>";
      echo faltZelle (urldecode($key), "RequestParameter");
      echo faltZelle (urldecode($val), "RequestParameter");
      echo "<td>",($request["methode"]=="POST"? $goodSign:$mehSign)," Inhalt (Body)</td>";
      echo "</tr>";
    }
  }
  
  if ($get != "")
  {
    foreach (preg_split("/[&;]/", $get) as $par)
    {
      list ($key,$val) = explode ("=", $par, 2);
      echo "<tr>";
      echo faltZelle (urldecode($key), "RequestParameter");
      echo faltZelle (urldecode($val), "RequestParameter");
      echo "<td>",($request["methode"]=="GET"? $goodSign:$mehSign)," URL (Query-String)</td>";
      echo "</tr>";
    }
  }
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


// Request-Inhalt
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#reqc">
        <h4 class="panel-title">
LIMIT1;

if ($reqInhalt["inhalt"]=="")
{
  echo <<<LIMIT1
          <span class="emptyPanel">Request-Inhalt</span>
        </h4>
      </div>
      <div id="reqc" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>Der Inhalt dieses Requests ist leer.</p>
LIMIT1;
}
else
{  
  echo <<<LIMIT1
          Request-Inhalt
        </h4>
      </div>
      <div id="reqc" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body" id="reqcc">
LIMIT1;

  if (strlen($reqInhalt["inhalt"])>$inhaltTrunc*$truncScale)
  {
    zeigeInhalt (0, substr($reqInhalt["inhalt"],0,$inhaltTrunc), $eigenschaften, $request["mime"]);
    zeigeTrunc ($inhaltTrunc, strlen($reqInhalt["inhalt"]), 0, "reqcc", $request["inhalt"]);
  }
  else
  {
    zeigeInhalt (0, $reqInhalt["inhalt"], $eigenschaften, $request["mime"]);
  }
}
echo <<<LIMIT1
        </div>
      </div>
    </div>
LIMIT1;


// Response-Header
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#resph">
        <h4 class="panel-title">
LIMIT1;

$select_s = $db->prepare ("select * from header where response=1 and request=?");
$select_s->execute (array($request["id"]));

if (($header = $select_s->fetch()) == false)
{
  echo <<<LIMIT1
          <span class="emptyPanel">Response-Header</span>
        </h4>
      </div>
      <div id="resph" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>Die Response enthält keine Header.</p>
LIMIT1;
}
else
{
  echo tableSorter ("ResponseHeader", "order: []");
  $foldMe = tableFolder ("ResponseHeader");
  
  echo <<<LIMIT1
          Response-Header
        </h4>
      </div>
      <div id="resph" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <div class="table-responsive">
            <table id="ResponseHeader" class="table table-hover">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Wert</th>
                </tr>
              </thead>
              <tbody>
LIMIT1;

  do
  {
    echo "<tr>";
    echo "<td>",$header["feld"],"</td>";
    echo faltZelle ($header["wert"], "ResponseHeader");
    echo "</tr>";
  }
  while ($header = $select_s->fetch());

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


// Response-Inhalt
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#respc">
        <h4 class="panel-title">
LIMIT1;

if ($respInhalt["inhalt"] == "")
{
  echo <<<LIMIT1
          <span class="emptyPanel">Response-Inhalt</span>
        </h4>
      </div>
      <div id="respc" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
        <p>Der Inhalt dieser Response ist leer.</p>
LIMIT1;
}
else
{
  echo <<<LIMIT1
          Response-Inhalt
        </h4>
      </div>
      <div id="respc" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body" id="respcc">
LIMIT1;

  if (strlen($respInhalt["inhalt"])>$inhaltTrunc*$truncScale)
  {
    zeigeInhalt (1, substr($respInhalt["inhalt"],0,$inhaltTrunc), $eigenschaften, $response["mime"]);
    zeigeTrunc ($inhaltTrunc, strlen($respInhalt["inhalt"]), 1, "respcc", $response["inhalt"]);
  }
  else
  {
    zeigeInhalt (1, $respInhalt["inhalt"], $eigenschaften, $response["mime"]);
  }
}
echo <<<LIMIT1
        </div>
      </div>
    </div>
LIMIT1;


// Response-Darstellung
if ($respInhalt["inhalt"] != "")
{
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#respd">
        <h4 class="panel-title">
LIMIT1;

  $dar = "";
  $cat = explode ("/", $response["mime"]);
  switch ($cat[0])
  {
    case "text":
      switch ($cat[1])
      {
        case "html":
          $dar = "<div class=\"iframer\"><iframe src=\"include/inhalt.php?typ=response&id=".$response["id"]."\"></iframe></div>";
          break;
      }
      break;
    case "image":
      $dar = "<img class=\"img-responsive center-block canvas\" src=\"include/inhalt.php?typ=response&id=".$response["id"]."\" onclick=\"this.style.backgroundColor=(this.style.backgroundColor=='#000000'||this.style.backgroundColor=='rgb(0, 0, 0)')?'#ffffff':'#000000';\">";
      break;
  }
  
  if ($dar == "")
  {
    echo <<<LIMIT1
          <span class="emptyPanel">Response-Darstellung</span>
        </h4>
      </div>
      <div id="respd" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <p>Der MIME-Typ "{$response["mime"]}" kann nur als Text dargestellt werden.</p>
LIMIT1;
  }
  else
  {
    echo <<<LIMIT1
          Response-Darstellung
        </h4>
      </div>
      <div id="respd" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          $dar
LIMIT1;
  }
  echo <<<LIMIT1
        </div>
      </div>
    </div>
LIMIT1;
}


// Meta-Daten
echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#meta">
        <h4 class="panel-title">
LIMIT1;

$select_s = $db->prepare ("select * from metadaten where request=? and response=1");
$select_s->execute(array($_GET["request"]));
if (($meta = $select_s->fetch()) == false)
{
  echo <<<LIMIT1
          <span class="emptyPanel">Meta-Daten</span>
        </h4>
      </div>
      <div id="meta" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <p>Es wurden keine Meta-Daten gefunden.</p>
LIMIT1;
}
else
{
  echo <<<LIMIT1
          Meta-Daten
        </h4>
      </div>
      <div id="meta" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
LIMIT1;
  echo tableSorter ("Metadaten", "order: [ [0,'asc'] ]");
  $foldMe = tableFolder ("Metadaten");
  echo <<<LIMIT1
          <div class="table-responsive">
            <table id="Metadaten" class="table table-hover">
              <thead>
                <tr>
                  <th>Feld</th>
                  <th>Wert</th>
                </tr>
              </thead>
            <tbody>
LIMIT1;

  do
  {
    echo "<tr>";
    echo faltZelle ($meta["feld"], "Metadaten");
    echo faltZelle ($meta["wert"], "Metadaten");
    echo "</tr>";
  }
  while ($meta = $select_s->fetch());
  
  echo "</tbody></table></div>";
}
echo <<<LIMIT1
        </div>
      </div>
    </div>
LIMIT1;

echo "</div></div>";

require ("include/htmlend.php");

?>

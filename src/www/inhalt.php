<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: inhalt.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display the sepcifics of the contents of a connection
//              (drilldown for inhalte.php)
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

$_aansehen = "Aufzeichnung ansehen";
$_ransehen = "Request ansehen";

$select_s = $db->prepare ( "select * from inhalt where id=?" );
$select_s->execute ( array ( $_REQUEST[ "inhalt" ] ) );
$inhalt = $select_s->fetch ();

if ( $inhalt[ "typ" ] == "request" )
{
  $select_s = $db->prepare ( "select * from request where id=?" );
  $select_s->execute ( array ( $inhalt[ "referenz" ] ) );
  $referenz = $select_s->fetch ();
}
else if ( $inhalt[ "typ" ] == "response" )
{
  $select_s = $db->prepare ( "select * from response where request=?" );
  $select_s->execute ( array ( $inhalt[ "referenz" ] ) );
  $referenz = $select_s->fetch ();
}

$verbindung_s = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt from verbindung where id=?" );
$verbindung_s->execute ( array ( $inhalt[ "verbindung" ] ) );
$verbindung = $verbindung_s->fetch ();

$aufzeichnung_s = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
$aufzeichnung_s->execute ( array ( $inhalt[ "aufzeichnung" ] ) );
$aufzeichnung = $aufzeichnung_s->fetch ();

$select_s = $db->prepare ( "select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?" );
$select_s->execute ( array ( $aufzeichnung[ "geraet" ] ) );
$eigenschaften = $select_s->fetchAll ( PDO::FETCH_COLUMN, 0 );

titleAndHelp ( "Inhaltdetails", <<<LIMIT1
LIMIT1
);


$foldMe = tableFolder ( "Inhalt" );

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Eigenschaften</h4>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="Inhalt" class="table table-hover">
            <tbody>
LIMIT1;
echo "<tr><td>", viewButton ( "aufzeichnung.php?aufzeichnung=" . $aufzeichnung[ "id" ], $_aansehen ), " Aufzeichnung</td>", ($aufzeichnung[ "name" ] == "" ? "<td>" . $aufzeichnung[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $aufzeichnung[ "_startt" ] . "</td>" : faltZelle ( $aufzeichnung[ "name" ], "Inhalt" )), "</tr>";
echo "<tr><td>", viewButton ( "request.php?request=" . $inhalt[ "referenz" ], $_ransehen ), " Request</td><td>", $verbindung[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", $verbindung[ "_zeitt" ], "</td></tr>";
echo "<tr><td>Typ</td><td>", strtolower ( $referenz[ "mime" ] ), "</td></tr>";
echo "<tr><td>Kommunikation</td><td>";
switch ( $inhalt[ "typ" ] )
{
  case "request":
  case "requestroh":
  case "udpsend":
    echo $versandtIcon;
    break;
  case "response":
  case "responseroh":
  case "udprcv":
    echo $empfangenIcon;
    break;
}
echo "</td></tr>";
echo "<tr><td>Bytes</td><td>", strlen ( $inhalt[ "inhalt" ] ), "</td></tr>";
echo "<tr><td>Server</td>";
if ( !$verbindung[ "host" ] )
{
  echo ipHostinfo ( $verbindung[ "ip" ], "Inhalt" );
}
else
{
  echo idHostinfo ( $verbindung[ "host" ], "Inhalt" );
}
echo "</tr>";

echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
LIMIT1;


// Inhalt
echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#cont">
        <h4 class="panel-title">
          Text
        </h4>
      </div>
      <div id="cont" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body" id="contc">
LIMIT1;

if ( strlen ( $inhalt[ "inhalt" ] ) > $inhaltTrunc * $truncScale )
{
  zeigeInhalt ( 0, substr ( $inhalt[ "inhalt" ], 0, $inhaltTrunc ), $eigenschaften, $referenz[ "mime" ] );
  zeigeTrunc ( $inhaltTrunc, strlen ( $inhalt[ "inhalt" ] ), 0, "contc", $inhalt[ "id" ] );
}
else
{
  zeigeInhalt ( 0, $inhalt[ "inhalt" ], $eigenschaften, $referenz[ "mime" ] );
}

echo <<<LIMIT1
        </div>
      </div>
    </div>
  </div>
LIMIT1;


// Darstellung
echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#respd">
        <h4 class="panel-title">
LIMIT1;

$dar = "";
$cat = explode ( "/", $referenz[ "mime" ] );
switch ( $cat[ 0 ] )
{
  case "text":
    switch ( $cat[ 1 ] )
    {
      case "html":
        $dar = "<div class=\"iframer\"><iframe src=\"include/inhalt.php?typ=" . $inhalt[ "typ" ] . "&id=" . ($inhalt[ "typ" ] == "response" ? $referenz[ "id" ] : $inhalt[ "referenz" ]) . "\"></iframe></div>";
        break;
    }
    break;
  case "image":
    $dar = "<img class=\"img-responsive center-block canvas\" src=\"include/inhalt.php?typ=" . $inhalt[ "typ" ] . "&id=" . ($inhalt[ "typ" ] == "response" ? $referenz[ "id" ] : $inhalt[ "referenz" ]) . "\" onclick=\"this.style.backgroundColor=(this.style.backgroundColor=='#000000'||this.style.backgroundColor=='rgb(0, 0, 0)')?'#ffffff':'#000000';\">";
    break;
}

if ( $dar == "" )
{
  echo <<<LIMIT1
          <span class="emptyPanel">Darstellung</span>
        </h4>
      </div>
      <div id="respd" class="panel-collapse collapse" role="tabpanel">
        <div class="panel-body">
          <p>Der MIME-Typ "{$referenz[ "mime" ]}" kann nur als Text dargestellt werden.</p>
LIMIT1;
}
else
{
  echo <<<LIMIT1
          Darstellung
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
  </div>
LIMIT1;


// Meta-Daten
echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#meta">
        <h4 class="panel-title">
LIMIT1;

$select_s = $db->prepare ( "select * from metadaten where request=? and response=1" );
$select_s->execute ( array ( $inhalt[ "referenz" ] ) );
if ( ($meta = $select_s->fetch ()) == false )
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
  echo tableSorter ( "Metadaten", "order: [ [0,'asc'] ]" );
  $foldMe = tableFolder ( "Metadaten" );
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
    echo faltZelle ( $meta[ "feld" ], "Metadaten" );
    echo faltZelle ( $meta[ "wert" ], "Metadaten" );
    echo "</tr>";
  }
  while ( $meta = $select_s->fetch () );

  echo "</tbody></table></div>";
}
echo <<<LIMIT1
        </div>
      </div>
    </div>
  </div>
LIMIT1;

include ("include/htmlend.php");
?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: aufzeichnung.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display the details of one recording session;
//              the session displayed is referred to by its sequence
//              number in the database via get parameter 'aufzeichnung'
//
//==============================================================================
//==============================================================================
// Filter auf angezeigte Aufzeichnung
if ( isset ( $_REQUEST[ "aufzeichnung" ] ) )
{
  setcookie ( "filter", $_REQUEST[ "aufzeichnung" ] );
}

require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
include ("include/htmlstart.php");
include ("include/topmenu.php");


titleAndHelp ( "Verbindungen einer Aufzeichnung", <<<LIMIT1
<p>In dieser Ansicht ist es möglich, schnell durch die vorhandenen Aufzeichnungen zu blättern und die Verbindungen zu sehen, die während der ausgewählten Aufzeichnung hergestellt wurden.</p>
<p>Dabei ist z.B. erkennbar, zu welchem Server und zu welchem Dienst oder Port die Verbindung bestand. Bei http(s)-Verbindungen wird auch der User-Agent angegeben, der beim Verbindungsaufbau übertragen wurde. Über diese Angabe lassen sich häufig zusammengehörige Verbindungen zuordnen, da sie vom selben Programm (Browser, Client, App) stammen.</p>
<p>Es werden folgende Verbindungstypen unterschieden:</p>
<dl class="dl-horizontal">
  <dt>http</dt><dd>unverschlüsselte HTTP-Verbindung</dd>
  <dt>https</dt><dd>SSL/TLS-verschlüsselte HTTP-Verbindung</dd>
  <dt>tcp</dt><dd>unverschlüsselte TCP-Verbindung</dd>
  <dt>ssl</dt><dd>SSL/TLS-verschlüsselte TCP-Verbindung</dd>
  <dt>udp</dt><dd>UDP-Datenaustausch</dd>
</dl>
LIMIT1
);

include ("include/aufzeichnung.php");

$_ansehen = "Verbindung ansehen";

echo <<<LIMIT1
<form class="form-horizontal" method="post">
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Verbindungen</h4>
      </div>
      <div class="panel-body">
LIMIT1;

$select_s = $db->prepare ( "select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where aufzeichnung=? order by nr" );
$select_s->execute ( array ( $_GET[ "aufzeichnung" ] ) );

if ( ($verbindung = $select_s->fetch ()) == false )
{
  infoMsg ( "Es sind keine Verbindungen vorhanden." );
}
else
{
  echo tableSorter ( "Verbindungen", "columns: [ {orderable:false, searchable:false}, {type:'num'}, {}, {}, {}, {}, {type:'num'}, {} ], order: [ [2,'asc'] ]" );
  $foldMe = tableFolder ( "Verbindungen" );

  echo <<<LIMIT1
        <table id="Verbindungen" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Zeit</th>
            <th>Typ</th>
            <th>User-Agent</th>
            <th>von Port</th>
            <th>zu Server</th>
            <th>zu Port</th>
            <th>Länge<span class="fullVerbindungen"> (Bytes)</span></th>
          </tr>
        </thead>
        <tbody>
LIMIT1;


  do
  {
    echo "<tr>";
    echo "<td>", viewButton ( "verbindung.php?verbindung=" . $verbindung[ "id" ], $_ansehen ), "</td>";
    echo "<td", onevent ( "Verbindungen" ), "><span class=\"fullVerbindungen\">", $verbindung[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", "</span><span class=\"compactVerbindungen\">$tableEllipsis</span>", $verbindung[ "_zeitt" ], "<!--", $verbindung[ "nr" ], "--></td>"; // sort by nr

    echo "<td>", $verbindung[ "typ" ], "</td>";

    $ua = "";
    if ( substr ( $verbindung[ "typ" ], 0, 4 ) == "http" )
    {
      $ua_s = $db->prepare ( "select useragent from {$verbindung[ "typ" ]} where verbindung=?" );
      $ua_s->execute ( array ( $verbindung[ "id" ] ) );
      $ua = $ua_s->fetchColumn ();
    }
    echo faltZelle ( $ua, "Verbindungen" );

    $srvc = getservbyport ( $verbindung[ "vonport" ], $verbindung[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\">", $verbindung[ "vonport" ], $srvc != "" ? " ($srvc)" : "", "<!--", $verbindung[ "vonport" ], "--></td>";

    if ( !$verbindung[ "host" ] )
    {
      echo ipHostinfo ( $verbindung[ "ip" ], "Verbindungen" );
    }
    else
    {
      echo idHostinfo ( $verbindung[ "host" ], "Verbindungen" );
    }

    $srvc = getservbyport ( $verbindung[ "anport" ], $verbindung[ "typ" ] == "udp" ? "udp" : "tcp" );
    echo "<td class=\"numeric\">", $verbindung[ "anport" ], $srvc != "" ? " ($srvc)" : "", "<!--", $verbindung[ "anport" ], "--></td>";
    echo "<td class=\"numeric\">", $verbindung[ "laenge" ], "</td>";
    echo "</tr>";
  }
  while ( $verbindung = $select_s->fetch () );

  echo "</tbody></table>";
}

echo "</div></div></div></form>";

include ("include/htmlend.php");
?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: sslsuite.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display ssl related details of an encrypted connection
//              (drilldown for sslsuites.php)
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

$_ansehen = "Verbindung ansehen";

$suite_s = $db->prepare ("select * from cipherSuite where id=?");
$suite_s->execute (array($_REQUEST["suite"]));
$suite = $suite_s->fetch();
$cipher_s = $db->prepare ("select * from cipher where id=?");
$cipher_s->execute (array($suite["cipher"]));
$cipher = $cipher_s->fetch();
$key_s = $db->prepare ("select * from keyExchange where id=?");
$key_s->execute (array($suite["keyExchange"]));
$key = $key_s->fetch();
$mac_s = $db->prepare ("select * from mac where id=?");
$mac_s->execute (array($suite["mac"]));
$mac = $mac_s->fetch();


titelHilfe ("Verschlüsselungsdetails", <<<LIMIT1
LIMIT1
);


$foldMe = tableFolder ("Suite");

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Eigenschaften</h4>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="Suite" class="table table-hover">
            <thead>
              <tr>
                <th>Eigenschaft</th>
                <th>Wert</th>
                <th>Erläuterung</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

if ($cipher["secure"])
{
  $sign = $goodSign;
  $erl = "Das Verschlüsselungsverfahren ".$cipher["shortName"]." gilt als sicher.";
}
else
{
  $sign = $badSign;
  $erl = "Das Verschlüsselungsverfahren ".$cipher["shortName"]." gilt als unsicher.";
}
echo "<tr",onevent("Suite"),"><td>Verschlüsselungsverfahren</td><td>",$cipher["longName"],"</td><td><span class=\"compactSuite\">$sign$tableEllipsis</span><span class=\"fullSuite\">$sign $erl</span></td></tr>";

if ($cipher["bits"]<128)
{
  $sign = $badSign;
  $erl = "Verschlüsselungen mit einer Schlüssellänge von unter 128 Bit sind unsicher.";
}
else if ($cipher["bits"]<256)
{
  $sign = $mehSign;
  $erl = "Schlüssellängen mit 128 Bit und mehr gelten als sicher. Optimal wären Schlüssel ab 256 Bit.";
}
else
{
  $sign = $goodSign;
  $erl = "Verfahren mit Schlüsseln ab 256 Bit Länge sind optimal.";
}
echo "<tr",onevent("Suite"),"><td>Schlüssellänge</td><td>",$cipher["bits"]," Bits</td><td><span class=\"compactSuite\">$sign$tableEllipsis</span><span class=\"fullSuite\">$sign $erl</span></td></tr>";

if ($key["secure"])
{
  $sign = $goodSign;
  $erl = "Das Schlüsselaustauschverfahren ".$key["shortName"]." gilt als sicher.";
}
else
{
  $sign = $badSign;
  $erl = "Das Schlüsselaustauschverfahren ".$key["shortName"]." gilt als unsicher.";
}
echo "<tr",onevent("Suite"),"><td>Schlüsselaustauschverfahren</td><td>",$key["longName"],"</td><td><span class=\"compactSuite\">$sign$tableEllipsis</span><span class=\"fullSuite\">$sign $erl</span></td></tr>";

if ($key["forwardSecrecy"])
{
  $sign = $goodSign;
  $erl = "Forward Secrecy stellt sicher, dass eine aufgezeichnete verschlüsselte Kommunikation nicht nachträglich entschlüsselt werden kann.";
}
else
{
  $sign = $mehSign;
  $erl = "Ohne Forward Secrecy besteht das Risiko, dass aufgezeichnete Kommunikationsströme entschlüsselt werden können, wenn das Verschlüsselungsverfahren gebrochen wird.";
}
echo "<tr",onevent("Suite"),"><td>Forward Secrecy</td><td>",$key["forwardSecrecy"]?"ja":"nein","</td><td><span class=\"compactSuite\">$sign$tableEllipsis</span><span class=\"fullSuite\">$sign $erl</span></td></tr>";

if ($mac["secure"])
{
  $sign = $goodSign;
  $erl = "Das Hashverfahren ".$mac["shortName"]." gilt als sicher.";
}
else
{
  $sign = $badSign;
  $erl = "Das Hashverfahren ".$mac["shortName"]." gilt als unsicher.";
}
echo "<tr",onevent("Suite"),"><td>Hashverfahren</td><td>",$mac["longName"]," (",$mac["bits"]," Bits)</td><td><span class=\"compactSuite\">$sign$tableEllipsis</span><span class=\"fullSuite\">$sign $erl</span></td></tr>";

echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
LIMIT1;


echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Verbindungen</h4>
      </div>
      <div class="panel-body">
LIMIT1;

if ($_REQUEST["aufzeichnung"]==0)
{
  $verb_s = $db->prepare("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from
                          (
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,ssltls where verbindung.id=ssltls.verbindung and ciphersuite=?
                            union all
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,https where verbindung.id=https.verbindung and ciphersuite=?
                          ) t
                          group by id");
  $verb_s->execute (array($suite["id"],$suite["id"]));
}
else
{
  aufBegrenzt ($_REQUEST["aufzeichnung"]);

  $verb_s = $db->prepare("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from
                          (
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ,sslversion from verbindung,ssltls where verbindung.id=ssltls.verbindung and ciphersuite=? and ssltls.aufzeichnung=?
                            union all
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ,sslversion from verbindung,https where verbindung.id=https.verbindung and ciphersuite=? and https.aufzeichnung=?
                          ) t
                          group by id");
  $verb_s->execute (array($suite["id"],$_REQUEST["aufzeichnung"],$suite["id"],$_REQUEST["aufzeichnung"]));
}

if (($verbindung = $verb_s->fetch()) == false)
{
  echo "Die Verschlüsselung wurde nicht verwendet.";
}
else
{
  echo tableSorter ("Verbindungen", "columns: [ {orderable:false, searchable:false}".(!$_REQUEST["aufzeichnung"]? ", {}":"").", {}, {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");
  $foldMe = tableFolder ("Verbindungen");

  echo <<<LIMIT1
        <div class="table-responsive">
          <table id="Verbindungen" class="table table-hover">
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
                <th>Version</th>
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

  $hostinfo = array();

  do
  {
    echo "<tr>";
    echo "<td>",viewButton ("verbindung.php?verbindung=".$verbindung["id"],$_ansehen),"</td>";
    
    if (!$_REQUEST["aufzeichnung"])
    {
      $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
      $select_s->execute (array($verbindung["aufzeichnung"]));
      $aufzeichnung = $select_s->fetch();
      echo $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."<!--".$aufzeichnung["start"]."--></td>" : faltZelle($aufzeichnung["name"],"Verbindungen");
    }
    
    echo "<td".onevent("Verbindungen").">";
    echo "<span class=\"fullVerbindungen\">",$verbindung["_zeitd"]," <i class=\"fa fa-clock-o\"></i> </span><span class=\"compactVerbindungen\">$tableEllipsis</span>",$verbindung["_zeitt"],"</span>";
    echo "<!--",$verbindung["nr"],"--></td>"; // sort by nr

    echo "<td".onevent("Verbindungen").">",$verbindung["sslversion"],"</td>";

    echo "<td".onevent("Verbindungen").">",$verbindung["typ"],"</td>";
    
    $ua = "";
    if (substr($verbindung["typ"],0,4) == "http")
    {
      $ua_s = $db->prepare ("select useragent from {$verbindung["typ"]} where verbindung=?");
      $ua_s->execute (array($verbindung["id"]));
      $ua = $ua_s->fetchColumn();
    }
    echo faltZelle ($ua, "Verbindungen");

    $srvc = getservbyport ($verbindung["vonport"],$verbindung["typ"]=="udp"? "udp":"tcp");
    echo "<td class=\"numeric\"".onevent("Verbindungen").">",$verbindung["vonport"],$srvc!=""? " ($srvc)":"","<!--",$verbindung["vonport"],"--></td>";
    
  
    if (!$verbindung["host"])
    {
      $hnames = ipHostinfo ($verbindung["ip"]);
    }
    else
    {
      $hnames = idHostinfo ($verbindung["host"]);
    }    
    $auth = array_shift($hnames);
    $hostex = explode(".",$auth);
    $tld = array_pop ($hostex);
    $domain = array_pop ($hostex);
    array_push ($hostex, $domain . "." . $tld);    
    echo "<td class=\"break\"".onevent("Verbindungen").">", whoisify($auth),
          "<span class=\"compactVerbindungen\">$tableEllipsis</span>",
          "<span class=\"fullVerbindungen\">", 
          "<br>(" . implode(" * ", array_map("whoisify",$hnames)) . ")</span>",
          "<!--".implode(" ",array_reverse($hostex))." --></td>";

    $srvc = getservbyport ($verbindung["anport"],$verbindung["typ"]=="udp"? "udp":"tcp");
    echo "<td class=\"numeric\"".onevent("Verbindungen").">",$verbindung["anport"],$srvc!=""? " ($srvc)":"","<!--",$verbindung["anport"],"--></td>";
    echo "<td class=\"numeric\"".onevent("Verbindungen").">",$verbindung["laenge"],"</td>";
    echo "</tr>";
  }
  while ($verbindung = $verb_s->fetch());

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

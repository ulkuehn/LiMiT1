<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: zertifikat.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display details of ssl certificates
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

$select_s = $db->prepare ("select *, date_format(notbefore, '%T') as _notbeforetime, date_format(notbefore, '%e.%c.%Y') as _notbeforedate, date_format(notafter, '%T') as _notaftertime, date_format(notafter, '%e.%c.%Y') as _notafterdate, datediff(notafter,notbefore) as _tage from zertifikat where id=?");
$select_s->execute (array($_REQUEST["zertifikat"]));
$zertifikat = $select_s->fetch();

if (preg_match ("/O=([^=,]*)/",$zertifikat["issuer"],$m))
{
  $issuer = $m[1];
}
else
{
  $issuer = $zertifikat["issuer"];
}

if (preg_match ("/O=([^=,]*)/",$zertifikat["subject"],$m))
{
  $subject = $m[1];
}
else
{
  $subject = $zertifikat["subject"];
}

titelHilfe ("Zertifikatdetails", <<<LIMIT1
LIMIT1
);


$foldMe = tableFolder ("Zertifikat");

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Zertifikat</h4>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table id="Zertifikat" class="table table-hover">
            <thead>
              <tr>
                <th>Eigenschaft</th>
                <th>Wert</th>
                <th>Erläuterung</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

echo "<tr><td>Seriennummer</td><td>",$zertifikat["serial"],"</td><td><span class=\"compactZertifikat\">$tableEllipsis</span><span class=\"fullZertifikat\">Die Seriennummer dient zur Identifikation des Zertifikats; sie ist eindeutig für jede Zertifizierungsstelle</span></td></tr>";

echo "<tr".onevent("Zertifikat")."><td><span class=\"compactZertifikat\">Gültigkeit</span><span class=\"fullZertifikat\">Gültigkeit (GMT)</span></td><td><span class=\"compactZertifikat\">",$zertifikat["_notbeforedate"]," &nbsp;bis&nbsp; ",$zertifikat["_notafterdate"],"</span><span class=\"fullZertifikat\">",$zertifikat["_notbeforedate"]," <i class=\"fa fa-clock-o\"></i> ",$zertifikat["_notbeforetime"]," &nbsp;bis&nbsp; ",$zertifikat["_notafterdate"]," <i class=\"fa fa-clock-o\"></i> ",$zertifikat["_notaftertime"],"<br>(= ",$zertifikat["_tage"]," Tage)</span></td><td></td></tr>";
#echo "<tr><td><span class=\"compactZertifikat\">Gültigkeit</span><span class=\"fullZertifikat\">Gültigkeit (GMT)</span></td><td><span class=\"compactZertifikat\">",$zertifikat["_notbeforedate"]," &nbsp;bis&nbsp; ",$zertifikat["_notafterdate"],"</span><span class=\"fullZertifikat\">",$zertifikat["_notbefore"]," &nbsp;bis&nbsp; ",$zertifikat["_notafter"],"</span></td><td></td></tr>";

rdnInfo ("ausgestellt für", "", $zertifikat["subject"]);
rdnInfo ("ausgestellt von", $zertifikat["subject"]==$zertifikat["issuer"]? "Es handelt sich um ein selbst signiertes Zertifikat (Aussteller und Inhaber sind identisch)":"", $zertifikat["issuer"]);

$names = explode(",",$zertifikat["names"]);
echo "<tr><td>Domains</td>",faltZelle (implode(" ",$names), "Zertifikat"),"<td></td></tr>";

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
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,ssltls where verbindung.id=ssltls.verbindung and zertifikat=?
                            union all
                            select verbindung.id,verbindung.aufzeichnung,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,https where verbindung.id=https.verbindung and zertifikat=?
                          ) t
                          group by id");
  $verb_s->execute (array($zertifikat["id"],$zertifikat["id"]));
}
else
{
  aufBegrenzt ($_REQUEST["aufzeichnung"]);

  $verb_s = $db->prepare("select *,date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from
                          (
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,ssltls where verbindung.id=ssltls.verbindung and zertifikat=? and ssltls.aufzeichnung=?
                            union all
                            select verbindung.id,nr,zeit,vonport,anport,ip,verbindung.host,laenge,typ from verbindung,https where verbindung.id=https.verbindung and zertifikat=? and https.aufzeichnung=?
                          ) t
                          group by id");
  $verb_s->execute (array($zertifikat["id"],$_REQUEST["aufzeichnung"],$zertifikat["id"],$_REQUEST["aufzeichnung"]));
}

if (($verbindung = $verb_s->fetch()) == false)
{
  echo "Das Zertifikat wurde nicht verwendet.";
}
else
{
  echo tableSorter ("Verbindungen", "columns: [ {orderable:false, searchable:false}".(!$_REQUEST["aufzeichnung"]? ", {}":"").", {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");
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

    $select_s = $db->prepare ("select ?<? as early, ?>? as late");
    $select_s->execute(array($verbindung["zeit"],$zertifikat["notbefore"], $verbindung["zeit"],$zertifikat["notafter"]));
    $zertEL = $select_s->fetch();
    $sign = $goodSign;
    $erl = "Das Zertifikat war zum Zeitpunkt der Verbindung gültig.";
    if ($zertEL["early"])
    {
      $sign = $badSign;
      $erl = "Das Zertifikat war zum Zeitpunkt der Verbindung noch nicht gültig.";
    }
    else if ($zertEL["late"])
    {
      $sign = $badSign;
      $erl = "Das Zertifikat war zum Zeitpunkt der Verbindung nicht mehr gültig.";
    }
    
    echo "<td".onevent("Verbindungen").">";
    echo "<span class=\"fullVerbindungen\">",$verbindung["_zeitd"]," <i class=\"fa fa-clock-o\"></i> </span><span class=\"compactVerbindungen\">$tableEllipsis</span>",$verbindung["_zeitt"],"</span>";
    echo "<span class=\"compactVerbindungen\"> $sign</span><span class=\"fullVerbindungen\"><br>$sign $erl</span>";
    echo "<!--",$verbindung["nr"],"--></td>"; // sort by nr

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
    
  
    $names = explode(",",$zertifikat["names"]);
    if (!$verbindung["host"])
    {
      $hnames = ipHostinfo ($verbindung["ip"]);
    }
    else
    {
      $hnames = idHostinfo ($verbindung["host"]);
    }
    $sign = $mehSign;
    if (count($names)==1)
    {
      $erl = "Die Domain, für die das Zertifikat ausgestellt ist, passt nicht zum Servernamen";
    }
    else
    {
      $erl = "Keine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen";
    }

    foreach ($names as $name)
    {
      foreach ($hnames as $hname)
      {
        if ($name==$hname || (substr($name,0,1)=="*" && (preg_match("/.".str_replace(".","\\.",$name)."/",$hname) || preg_match("/.".str_replace(".","\\.",$name)."/",".$hname")))) // "||"-preg ermöglicht auch match von "*.a.b" für "a.b" (nicht nur "c.a.b" etc.); dies scheint üblich zu sein, wenn auch nicht RFC 2595 gemäß ?
        {
          $sign = $goodSign;
          if (count($names)==1)
          {
            $erl = "Die Domain, für die das Zertifikat ausgestellt ist, passt zum Servernamen";
          }
          else
          {
            $erl = "Mindestens eine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen";
          }
          break;
        }
      }
    }
    
    $auth = array_shift($hnames);
    $hostex = explode(".",$auth);
    $tld = array_pop ($hostex);
    $domain = array_pop ($hostex);
    array_push ($hostex, $domain . "." . $tld);    
    echo "<td class=\"break\"".onevent("Verbindungen").">", whoisify($auth),
          "<span class=\"compactVerbindungen\"> $sign $tableEllipsis</span>",
          "<span class=\"fullVerbindungen\">", 
          "<br>(" . implode(" * ", array_map("whoisify",$hnames)) . ")<br>$sign $erl</span>",
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

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/zertifikat.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display the certificate specifics of an encrypted
//              connection
//
//==============================================================================
//==============================================================================

unset ($verbindung);

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
  $select_s = $db->prepare ("select * from verbindung where id=?");
  $select_s->execute (array($verbindungId));
  $verbindung = $select_s->fetch();
}

if (isset($verbindung) && ($verbindung["typ"]=="https" || $verbindung["typ"]=="ssl"))
{
  $select_s = $db->prepare ("select zertifikat from ".($verbindung["typ"]=="https"? "https":"ssltls")." where verbindung=?");
  $select_s->execute (array($verbindung["id"]));
  $zID = $select_s->fetchColumn();

  if (!$zID)
  {
    echo "<p>Für diese Verbindung ist kein Zertifikat vorhanden.</p>";
  }
  else
  {
    $foldMe = tableFolder ("Zertifikat");
    
    echo <<<LIMIT1
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
      
    $select_s = $db->prepare ("select *, date_format(notbefore, '%T') as _notbeforetime, date_format(notbefore, '%e.%c.%Y') as _notbeforedate, date_format(notafter, '%T') as _notaftertime, date_format(notafter, '%e.%c.%Y') as _notafterdate, datediff(notafter,notbefore) as _tage from zertifikat where id=?");
    $select_s->execute (array($zID));
    $zertifikat = $select_s->fetch();
    $select_s = $db->prepare ("select ?<? as early, ?>? as late");
    $select_s->execute (array($verbindung["zeit"],$zertifikat["notbefore"], $verbindung["zeit"],$zertifikat["notafter"]));
    $zertEL = $select_s->fetch();

    echo "<tr",onevent("Zertifikat"),"><td>Seriennummer</td><td>",$zertifikat["serial"],"</td><td><span class=\"compactZertifikat\">$tableEllipsis</span><span class=\"fullZertifikat\">Die Seriennummer dient zur Identifikation des Zertifikats; sie ist eindeutig für jede Zertifizierungsstelle</span></td></tr>";
    
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
    else
    {
      $sign = $goodSign;
      $erl = "Das Zertifikat war zum Zeitpunkt der Verbindung gültig.";
    }
    echo "<tr",onevent("Zertifikat"),"><td><span class=\"compactZertifikat\">Gültigkeit</span><span class=\"fullZertifikat\">Gültigkeit (GMT)</span></td><td><span class=\"compactZertifikat\">",$zertifikat["_notbeforedate"]," &nbsp;bis&nbsp; ",$zertifikat["_notafterdate"],"</span><span class=\"fullZertifikat\">",$zertifikat["_notbeforedate"]," <i class=\"fa fa-clock-o\"></i> ",$zertifikat["_notbeforetime"]," &nbsp;bis&nbsp; ",$zertifikat["_notafterdate"]," <i class=\"fa fa-clock-o\"></i> ",$zertifikat["_notaftertime"],"<br>(= ",$zertifikat["_tage"]," Tage)</span></td><td><span class=\"compactZertifikat\">$sign$tableEllipsis</span><span class=\"fullZertifikat\">$sign $erl</span></td></tr>";
    
    rdnInfo ("ausgestellt für", "", $zertifikat["subject"]);
    rdnInfo ("ausgestellt von", $zertifikat["subject"]==$zertifikat["issuer"]? "Es handelt sich um ein selbst signiertes Zertifikat (Aussteller und Inhaber sind identisch)":"", $zertifikat["issuer"]);
    

    $sign = $mehSign;
    $names = array();
    $dom = "";
    if (!$verbindung["host"])
    {
      $hnames = ipHostinfo ($verbindung["ip"]);
    }
    else
    {
      $hnames = idHostinfo ($verbindung["host"]);
    }

    foreach (explode(",",$zertifikat["names"]) as $name)
    {
      $show = $name;
      foreach ($hnames as $hname)
      {
        if ($name==$hname || (substr($name,0,1)=="*" && (preg_match("/.".str_replace(".","\\.",$name)."/",$hname) || preg_match("/.".str_replace(".","\\.",$name)."/",".$hname")))) // "||"-preg ermöglicht auch match von "*.a.b" für "a.b" (nicht nur "c.a.b" etc.); dies scheint üblich zu sein, wenn auch nicht RFC 2595 gemäß ?
        {
          $sign = $goodSign;
          $show = "<strong>$name</strong>";
          $dom .= "$name ";
        }
      }
      array_push ($names, $show);
    }
    
    if ($sign==$goodSign)
    {
      if (count($names)==1)
      {
        $wert = $dom;
        $erl = "Die Domain, für die das Zertifikat ausgestellt ist, passt zum Servernamen";
      }
      else
      {
        $wert = implode("<br>",$names);#"<span class=\"compactZertifikat\">$dom</span><span class=\"fullZertifikat\">".implode(" &nbsp; ",$names)."</span>";
        $erl = "Mindestens eine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen. Sie ist/sind hervorgehoben";
      }
    }
    else
    {
      $wert = implode("<br>",$names);
      if (count($names)==1)
      {
        $erl = "Die Domain, für die das Zertifikat ausgestellt ist, passt nicht zum Servernamen";
      }
      else
      {
        $erl = "Keine der Domains, für die das Zertifikat ausgestellt ist, passt zum Servernamen";
      }
    }

    echo "<tr",onevent("Zertifikat"),"><td>Domain(s)</td><td>$wert</td><td>$sign <span class=\"compactZertifikat\">$tableEllipsis</span><span class=\"fullZertifikat\">$erl</span></td></tr>";

    echo <<<LIMIT1
          </tbody>
        </table>
      </div>
LIMIT1;
  }
  
}

?>

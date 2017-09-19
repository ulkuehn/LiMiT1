<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: zertifikate.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display ssl certificates that have been recorded
//              ((more details in script zertifikat.php)
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


function certRows ($aufzID, $tableName)
{
  global $db;
  
  $_ansehen = "Zertifikat ansehen";

  if ($aufzID == 0)
  {
    $select_s = $db->prepare ("select zertifikat,sum(v) as verbindungen
                              from
                              (
                                select zertifikat,count(*) as v from https group by zertifikat
                                union all
                                select zertifikat,count(*) as v from ssltls group by zertifikat
                              ) t
                              group by zertifikat");
    $select_s->execute ();
  }
  else
  {
    $select_s = $db->prepare ("select zertifikat,sum(v) as verbindungen
                              from
                              (
                                select zertifikat,count(*) as v from https where aufzeichnung=? group by zertifikat
                                union all
                                select zertifikat,count(*) as v from ssltls where aufzeichnung=? group by zertifikat
                              ) t
                              group by zertifikat");
    $select_s->execute (array($aufzID,$aufzID));
  }
  
  if (($certs = $select_s->fetch()) == false)
  {
    return array (false, "Es wurden keine Zertifikate verwendet.");    
  }
  else
  {
    list ($erg, $foldMe) = tableFolder ($tableName, false);
    $erg .= tableSorter ($tableName, "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {}, {}, {}, {} ], order: [ [1,'asc'] ]");

    $erg .= <<<LIMIT1
    <div class="table-responsive">
      <table id="$tableName" class="table table-hover">
        <thead>
          <tr>
            <th>$foldMe</th>
            <th>Eigentümer</th>
            <th>Aussteller</th>
            <th><span class="full$tableName">gültig </span>ab</th>
            <th><span class="full$tableName">gültig </span>bis</th>
            <th>Tage</th>
            <th><span class="full$tableName">verwendet von </span>Hosts</th>
            <th>Verb<span class="compact$tableName">.</span><span class="full$tableName">indungen</span></th>
          </tr>
        </thead>
        <tbody>
LIMIT1;

    do
    {
      $cert_s = $db->prepare ("select *, date_format(notbefore, '%T') as _notbeforetime, date_format(notbefore, '%e.%c.%Y') as _notbeforedate, date_format(notafter, '%T') as _notaftertime, date_format(notafter, '%e.%c.%Y') as _notafterdate, datediff(notafter,notbefore) as _tage from zertifikat where id=?");
      $cert_s->execute (array($certs["zertifikat"]));
      $cert = $cert_s->fetch();
      
      $hostscompact = array();
      $hostsfull = array();
      $verb_s = $db->prepare ("select host,ip from
                              (
                                select verbindung.host,ip from verbindung,ssltls where verbindung.id=ssltls.verbindung and zertifikat=?
                                union all
                                select verbindung.host,ip from verbindung,https where verbindung.id=https.verbindung and zertifikat=?
                              ) t
                              group by ip");
      $verb_s->execute (array($cert["id"],$cert["id"]));
      while ($verb = $verb_s->fetch())
      {
        if (!$verb["host"])
        {
          list ($ip) = ipHostinfo ($verb["ip"]);
          array_push ($hostscompact, whoisify($ip));
          array_push ($hostsfull, whoisify($ip));
        }
        else
        {
          $names = idHostinfo ($verb["host"]);
          $auth = array_shift ($names);
          array_push ($hostscompact, whoisify($auth));
          array_push ($hostsfull, whoisify($auth)." (".implode(" * ",array_map("whoisify",$names)).")");
        }
      }
              
      if (preg_match ("/O=([^=,]*)/",$cert["issuer"],$m))
      {
        $issuer = "<td".onevent($tableName)."><span class=\"compact$tableName\">$m[1]</span><span class=\"full$tableName\">" . $cert["issuer"] . "</span><!--" . $m[1] . "--></td>";
      }
      else
      {
        $issuer = faltZelle ($cert["issuer"],$tableName);
      }

      if (preg_match ("/O=([^=,]*)/",$cert["subject"],$m))
      {
        $subject = "<td".onevent($tableName)."><span class=\"compact$tableName\">$m[1]</span><span class=\"full$tableName\">" . $cert["subject"] . "</span><!--" . $m[1] . "--></td>";
      }
      else
      {
        $subject = faltZelle ($cert["subject"],$tableName);
      }

      $erg .= "<tr>";
      
      $erg .= "<td>" . viewButton ("zertifikat.php?zertifikat=".$cert["id"]."&aufzeichnung=$aufzID",$_ansehen) . "</td>";
      
      $erg .= $subject;
      
      $erg .= $issuer;
      
      $erg .= "<td class=\"numeric\"".onevent($tableName)."><span class=\"compact$tableName\">".$cert["_notbeforedate"]."</span><span class=\"full$tableName\">".$cert["_notbeforedate"]." <i class=\"fa fa-clock-o\"></i> ".$cert["_notbeforetime"]."</span><!--".$cert["notbefore"]."--></td>";
      
      $erg .= "<td class=\"numeric\"".onevent($tableName)."><span class=\"compact$tableName\">".$cert["_notafterdate"]."</span><span class=\"full$tableName\">".$cert["_notafterdate"]." <i class=\"fa fa-clock-o\"></i> ".$cert["_notaftertime"]."</span><!--".$cert["notafter"]."--></td>";
      
      $erg .= "<td class=\"numeric\">".$cert["_tage"]."</td>";
      
      $erg .= "<td".onevent($tableName)."><span class=\"compact$tableName\">".implode(",<br>",$hostscompact)."</span><span class=\"full$tableName\">".implode(",<br>",$hostsfull)."</span></td>";
      
      $erg .= "<td class=\"numeric\">".$certs["verbindungen"]."</td>";
      $erg .= "</tr>";
    }
    while ($certs = $select_s->fetch());
    
    $erg .= <<<LIMIT1
        </tbody>
      </table>
    </div>
LIMIT1;

    return array (true, $erg);
  }
}


if (!isset($_REQUEST["show"]))
{
  $_REQUEST["show"] = "jede";
}

titleAndHelp ("Zertifikate", <<<LIMIT1
Diese Auswertung betrachtet die SSL-Zertifikate, die aufgezeichnet wurden.
LIMIT1
);

aufzeichnungsFilter ();

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = certRows (0, "Zertifikate");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Zertifikate aller Aufzeichnungen</h4>
      </div>
      <div class="panel-body">
        $erg
      </div>
    </div>
  </div>        
LIMIT1;
}

else if ($_REQUEST["show"]=="jede")
{
  echo <<<LIMIT1
  <div class="row">
    <div class="panel-group" role="tablist">
LIMIT1;
  
  $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc");
  $select_s->execute();
  while ($aufzeichnung = $select_s->fetch())
  {
    list ($res, $erg) = certRows ($aufzeichnung["id"], "Zertifikate".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Zertifikate der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
    echo <<<LIMIT1
          </h4>
        </div>
        <div id="auf{$aufzeichnung['id']}" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body">
            $erg
LIMIT1;
    echo <<<LIMIT1
          </div>
        </div>
      </div>
LIMIT1;
  }
  echo <<<LIMIT1
    </div>
  </div>
LIMIT1;
}

else
{
  $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung where id=?");
  $select_s->execute(array($_REQUEST["show"]));
  $aufzeichnung = $select_s->fetch();

  list ($res, $erg) = certRows ($_REQUEST["show"], "Zertifikate");

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Zertifikate der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
  echo <<<LIMIT1
        </h4>
      </div>
      <div class="panel-body">
        $erg
      </div>
    </div>
  </div>        
LIMIT1;
}

include ("include/htmlend.php");

?>

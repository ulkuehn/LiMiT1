<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: sslsuites.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display ssl connections with some of their specifics
//              more details can be obtained with drilldown script sslsuite.php
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


function sslRows ($aufzID, $tableName)
{
  global $db;
  
  $_ansehen = "SSL-Verschlüsselung ansehen";

  if ($aufzID == 0)
  {
    $select_s = $db->prepare ("select ciphersuite,sum(v) as verbindungen
                              from
                              (
                                select ciphersuite,count(*) as v from https group by ciphersuite
                                union all
                                select ciphersuite,count(*) as v from ssltls group by ciphersuite
                              ) t
                              group by ciphersuite");
    $select_s->execute ();
  }
  else
  {
    $select_s = $db->prepare ("select ciphersuite,sum(v) as verbindungen
                              from
                              (
                                select ciphersuite,count(*) as v from https where aufzeichnung=? group by ciphersuite
                                union all
                                select ciphersuite,count(*) as v from ssltls where aufzeichnung=? group by ciphersuite
                              ) t
                              group by ciphersuite");
    $select_s->execute (array($aufzID,$aufzID));
  }
  
  if (($ssls = $select_s->fetch()) == false)
  {
    return array (false, "Es wurde keine Verschlüsselung verwendet.");    
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
            <th><span class="compact$tableName">Verschl.</span><span class="full$tableName">Ver-<br>schlüsselung</span></th>
            <th><span class="compact$tableName">Bits</span><span class="full$tableName">Schlüssel-<br>länge</span></th>
            <th><span class="compact$tableName">Austausch</span><span class="full$tableName">Schlüssel-<br>austausch</span></th>
            <th><span class="compact$tableName">PFS</span><span class="full$tableName">Forward<br>Secrecy</span></th>
            <th>Hash<span class="full$tableName">-<br>Verfahren</span></th>
            <th><span class="full$tableName">verwendet von </span>Hosts</th>
            <th>Verb<span class="compact$tableName">.</span><span class="full$tableName">indungen</span></th>
          </tr>
        </thead>
        <tbody>
LIMIT1;

    do
    {
      $suite_s = $db->prepare ("select * from cipherSuite where id=?");
      $suite_s->execute (array($ssls["ciphersuite"]));
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

      $hostscompact = array();
      $hostsfull = array();
      $verb_s = $db->prepare ("select host,ip from
                              (
                                select verbindung.host,ip from verbindung,ssltls where verbindung.id=ssltls.verbindung and ciphersuite=?
                                union all
                                select verbindung.host,ip from verbindung,https where verbindung.id=https.verbindung and ciphersuite=?
                              ) t
                              group by ip");
      $verb_s->execute (array($ssls["ciphersuite"],$ssls["ciphersuite"]));
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

      $erg .= "<tr>";
      
      $erg .= "<td>" . viewButton ("sslsuite.php?suite=".$ssls["ciphersuite"]."&aufzeichnung=$aufzID",$_ansehen) . "</td>";
      
      $erg .= "<td".onevent($tableName)."><span class=\"compact$tableName\">".$cipher["shortName"]."</span><span class=\"full$tableName\">".$cipher["longName"]."</td>";
      
      $erg .= "<td class=\"numeric\">".$cipher["bits"]."</td>";

      $erg .= "<td".onevent($tableName)."><span class=\"compact$tableName\">".$key["shortName"]."</span><span class=\"full$tableName\">".$key["longName"]."</td>";

      $erg .= "<td>".($key["forwardSecrecy"]? "<i class=\"fa fa-check\"></i>":"")."</td>";

      $erg .= "<td".onevent($tableName)."><span class=\"compact$tableName\">".$mac["shortName"]."</span><span class=\"full$tableName\">".$mac["longName"]."</td>";

      $erg .= "<td".onevent($tableName)."><span class=\"compact$tableName\">".implode(",<br>",$hostscompact)."</span><span class=\"full$tableName\">".implode(",<br>",$hostsfull)."</span></td>";
      
      $erg .= "<td class=\"numeric\">".$ssls["verbindungen"]."</td>";
      $erg .= "</tr>";
    }
    while ($ssls = $select_s->fetch());
    
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

titelHilfe ("SSL-Verschlüsselung", <<<LIMIT1
Soweit SSL- oder HTTPS-Verbindungen aufgezeichnet wurden, 
können hier die verwendeten Verschlüsselungstypen (SSL-Suite) analysiert werden.
Im Drill-Down können die einzelnen Verbindungen identifiziert werden, die die entsprechende Suite verwenden.
LIMIT1
);

aufzeichnungsFilter ();

if ($_REQUEST["show"]=="alle")
{
  list ($res, $erg) = sslRows (0, "SSL");
  
  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Verschlüsselungen aller Aufzeichnungen</h4>
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
    list ($res, $erg) = sslRows ($aufzeichnung["id"], "SSL".$aufzeichnung["id"]);

    echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#auf{$aufzeichnung['id']}">
          <h4 class="panel-title">
LIMIT1;
    echo (!$res? "<span class=\"emptyPanel\">":""), "Verschlüsselungen der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"], (!$res? "</span>":"");
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

  list ($res, $erg) = sslRows ($_REQUEST["show"], "SSL");

  echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
  echo "Verschlüsselungen der Aufzeichnung ".($aufzeichnung["name"]==""? "" : "<strong>".htmlSave($aufzeichnung["name"])."</strong> ")."vom ".$aufzeichnung["_start"];
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

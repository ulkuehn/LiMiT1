<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: suche.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to search recorded data for specific strings or patterns
//              the search provides for the following:
//              - simple text search (case sensitive or not)
//              - regexp search
//              - filter for different data categories (header, content etc)
//              - limit to specific sessions
//              - forward/backward between occurances of search string
//                if multiple occurances are found in one search item  
//
//==============================================================================
//==============================================================================


require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
$extratitel = " - Suche";
$framename = $__usetabs ? "suche" : "";
include ("include/htmlstart.php");
$extranav = $__usetabs ? "<div class=\"navHider\"></div>" : "";
include ("include/topmenu.php");

$_ansehen = "Details ansehen";


$suchValue = array_key_exists("suche", $_REQUEST)? htmlspecialchars($_REQUEST["suche"]) : "";

titelHilfe ("Freitextsuche", <<<LIMIT1
<p>Mit dieser Funktion können Zeichenketten in den aufgezeichneten Daten aufgespürt werden.
<br>
Die Suche kann auf bestimmte Bereiche (z.B. nur HTTP-Header) begrenzt werden, indem die nicht relevanten
Bereiche ausgeschaltet werden.
<br>
Komplexe Suchanfragen sind mit aktivierten "Musterzeichen" möglich. Der Suchstring wird dann als regulärer
Ausdruck interpretiert. Dann haben u.a. folgende Zeichen eine besondere Bedeutung:</p>
<table class="table table-condensed">
  <tbody>
    <tr>
      <td><strong>.</strong></td>
      <td>beliebiges Zeichen</td>
    </tr>
    <tr>
      <td><strong>?</strong></td>
      <td>vorheriges Zeichen kommt nicht oder einmal vor</td>
    </tr>
    <tr>
      <td><strong>*</strong></td>
      <td>vorheriges Zeichen kommt nicht, ein- oder mehrmals vor</td>
    </tr>
    <tr>
      <td><strong>+</strong></td>
      <td>vorheriges Zeichen kommt ein- oder mehrmals vor</td>
    </tr>
    <tr>
      <td><strong>^</strong></td>
      <td>Textanfang</td>
    </tr>
    <tr>
      <td><strong>$</strong></td>
      <td>Textende</td>
    </tr>
    <tr>
      <td><strong>|</strong></td>
      <td>Oder-Verknüpfung</td>
    </tr>
  </tbody>
</table>
LIMIT1
);


echo <<<LIMIT1
<form method="post" class="form-horizontal">
  <div class="row" style="margin-bottom:20px">
    <div class="input-group">
      <span class="input-group-btn">
        <input type="submit" class="btn btn-primary" value="Suche">
      </span>
      <input class="form-control" type="search" id="isuche" name="suche" value="$suchValue">
      <span class="input-group-btn">
        <button type="button" class="btn btn-default" onclick="document.getElementById('isuche').value='';">&times;</button>
      </span>
    </div>
  </div>
  
  <div class="row">  
    <div class="panel panel-primary">
      <div class="panel-heading" role="tab">
        <h4 class="panel-title">Einstellungen</h4>
      </div>
      <div class="panel-body">
LIMIT1;

$select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc");
$select_s->execute();
$aufzeichnungen = $select_s->fetchAll();
if (count($aufzeichnungen) < 2)
{
  $show = "alle";
}
else
{
  if (array_key_exists("show",$_REQUEST) && $_REQUEST["show"] != "jede")
  {
    $show = $_REQUEST["show"];
  }
  else
  {
    if (array_key_exists("filter",$_COOKIE) && $_COOKIE["filter"] != "jede")
    {
      $show = $_COOKIE["filter"];
    }
    else
    {
      $show = "alle";
    }
  }

  echo <<<LIMIT1
        <select class="form-control" name="show">
LIMIT1;
  echo "<option value=\"alle\"",($show=="alle"? " selected":""),">Alle Aufzeichnungen berücksichtigen</option>";
  foreach ($aufzeichnungen as $aufzeichnung)
  {
    echo "<option value=\"",$aufzeichnung["id"],"\"",($show==$aufzeichnung["id"]? " selected":""),">Nur Aufzeichnung",($aufzeichnung["name"]==""? "" : " &nbsp; &nbsp;".htmlSave(strlen($aufzeichnung["name"])>50? substr($aufzeichnung["name"],0,47)."..." : $aufzeichnung["name"])."&nbsp; &nbsp;")," vom ",$aufzeichnung["_start"]," berücksichtigen</option>";
  }
  echo <<<LIMIT1
        </select>
LIMIT1;
}

if (count($_REQUEST)==0 || (array_key_exists("caseSwitch",$_REQUEST) && $_REQUEST["caseSwitch"] == "on"))
{
  $caseChecked = " checked";
}
else
{
  $caseChecked = "";
}

if (array_key_exists("regSwitch",$_REQUEST) && $_REQUEST["regSwitch"] == "on")
{
  $regChecked = " checked";
}
else
{
  $regChecked = "";
}

echo <<<LIMIT1
        <div class="col-md-6">
          <div class="checkbox">
            <label>
              <input type="checkbox" id="caseSwitch" name="caseSwitch"$caseChecked>
              Groß-/Kleinschreibung
            </label>
          </div> 
        </div>
        <div class="col-md-6">
          <div class="checkbox">
            <label>
              <input type="checkbox" id="regSwitch" name="regSwitch"$regChecked>
              Musterzeichen
            </label>
          </div> 
        </div>
LIMIT1;

foreach ($suchOrte as $ort => $info)
{
  $checked = (count($_REQUEST)==0 || array_key_exists($ort,$_REQUEST) || array_key_exists("orte",$_REQUEST))? "checked" : "";
  echo <<<LIMIT1
        <div class="col-md-3">
          <div class="checkbox">
            <label>
              <input type="checkbox" id="$ort" name="$ort" $checked>
              {$info[1]}
            </label>
          </div> 
        </div>
LIMIT1;
}

echo <<<LIMIT1
      </div>
    </div>
  </div>
</form>

<script type="text/javascript">

function suchErgebnis (id, show, ort, limit, nadel, isReg, isCase, prepos, pos)
{
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() 
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) 
    {
      if (xmlhttp.responseText != "")
      {
        document.getElementById(id).innerHTML = xmlhttp.responseText;
      }
      document.getElementById(id).style.opacity="1";
    }
  }
  
  document.getElementById(id).style.opacity="0.3";
  
  xmlhttp.open ("POST","include/suche.php",true);
  xmlhttp.setRequestHeader ("Content-type", "application/x-www-form-urlencoded");
  xmlhttp.send ("id=" + id + "&show=" + show + "&ort=" + ort + "&limit=" + limit + "&nadel=" + nadel + "&isReg=" + isReg + "&isCase=" +isCase + "&prepos=" + prepos + "&pos=" + pos);  
}

</script>

LIMIT1;

if (array_key_exists("suche",$_REQUEST))
{
  if (array_key_exists("regSwitch",$_REQUEST) && $_REQUEST["regSwitch"] != "on")
  {
    $nadel = preg_quote ($_REQUEST["suche"]);
  }
  else
  {
    $nadel = $_REQUEST["suche"];
  }

  $funde = array();
  $ergebnis = array();

  foreach ($suchOrte as $ort => $info)
  {
    $ergebnis[$ort] = "";
    $funde[$ort] = 0;
    
    if (!array_key_exists($ort,$_REQUEST) && !array_key_exists("orte",$_REQUEST))
    {
      continue;
    }
    if ($ort == "Inhalte")
    {
      if ($show == "alle")
      {
        $fund_s = $db->prepare ($info[2]);
        $fund_s->execute (array($nadel,$nadel));
      }
      else
      {
        $fund_s = $db->prepare ($info[2]." and aufzeichnung=?");
        $fund_s->execute (array($nadel,$nadel,$show));
      }
    }
    else
    {
      if ($show == "alle")
      {
        $fund_s = $db->prepare ($info[2]);
        $fund_s->execute (array($nadel));
      }
      else
      {
        $fund_s = $db->prepare ($info[2]." and aufzeichnung=?");
        $fund_s->execute (array($nadel,$show));
      }
    }
    $limit = 0;
    
    if ($fund = $fund_s->fetch())
    {
      $ergebnis[$ort] .= tableSorter ("Fundstellen$ort", "columns: [ {orderable:false, searchable:false}".($show=="alle"? ", {}":"").", {}, {}, {type:'num'}, {}, {}, {orderable:false, searchable:false}, {orderable:false, searchable:false}, {orderable:false, searchable:false} ], order: [ [1,'asc'] ]");
      $foldMe = tableFolder ("Fundstellen$ort");

      $ergebnis[$ort] .= <<<LIMIT1
        <table id="Fundstellen$ort" class="table table-hover">
          <thead>
            <tr>
              <th>$foldMe</th>
LIMIT1;
      if ($show == "alle")
      {
        $ergebnis[$ort] .= "<th>Aufzeichnung</th>";
      }
      $ergebnis[$ort] .= <<<LIMIT1
              <th>Zeit</th>
              <th>Server</th>
              <th>Port</th>
              <th></th>
              <th></th>
              <th></th>
              <th>{$info[0]}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
LIMIT1;

      do
      {
        $select_s = $db->prepare ("select *, date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?");
        $select_s->execute (array($fund["verbindung"]));
        $verbindung = $select_s->fetch();

        list ($res, $weitere, $pos) = suchMarkierung ($ort, $fund["inhalt"], $_REQUEST["suche"], array_key_exists("regSwitch",$_REQUEST) && $_REQUEST["regSwitch"]=="on", array_key_exists("caseSwitch",$_REQUEST) && $_REQUEST["caseSwitch"]=="on", 0);

        if ($res != "")
        {
          $funde[$ort]++;          
          $id = $fund["id"].$ort;
          $ergebnis[$ort] .= "<tr id=\"$id\">";
          
          if ($ort!="Inhalte")
          {
            $ergebnis[$ort] .= "<td>" . viewButton("request.php?request=".$fund["request"], $_ansehen) . "</td>";
          }
          else
          {
            if ($fund["typ"]=="request" || $fund["typ"]=="response")
            {
              $ergebnis[$ort] .= "<td>" . viewButton("request.php?request=".$fund["referenz"], $_ansehen) . "</td>";
            }
            else
            {
              $ergebnis[$ort] .= "<td>" . viewButton("verbindung.php?verbindung=".$fund["referenz"], $_ansehen) . "</td>";
            }
          }
          
          if ($show == "alle")
          {
            $select_s = $db->prepare ("select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?");
            $select_s->execute (array($verbindung["aufzeichnung"]));
            $aufzeichnung = $select_s->fetch();
            $ergebnis[$ort] .= $aufzeichnung["name"]==""? "<td>".$aufzeichnung["_startd"]." <i class=\"fa fa-clock-o\"></i> ".$aufzeichnung["_startt"]."</td>" : faltZelle($aufzeichnung["name"],"Fundstellen$ort");
          }
          
          $ergebnis[$ort] .= "<td>".$verbindung["_zeitd"]." <i class=\"fa fa-clock-o\"></i> ".$verbindung["_zeitt"]. "<!--" . $verbindung["zeit"] . "--></td>";
          
          if (!$verbindung["host"])
          {
            $ergebnis[$ort] .= ipHostinfo ($verbindung["ip"], "Fundstellen$ort");
          }
          else
          {
            $ergebnis[$ort] .=  idHostinfo ($verbindung["host"], "Fundstellen$ort");
          }
          
          $srvc = getservbyport ($verbindung["anport"],$verbindung["typ"]=="udp"? "udp":"tcp");
          $ergebnis[$ort] .= "<td class=\"numeric\">".($verbindung["typ"]=="udp"? "udp ":"").$verbindung["anport"].($srvc!=""? " ($srvc)":"")."<!--".$verbindung["anport"]."--></td>";
          
          if ($ort=="HTTP_Requests" || ($ort=="HTTP_Header" && !$fund["response"]) || ($ort=="Inhalte" && ($fund["typ"]=="request" || $fund["typ"]=="udpsend")))
          {
            $ergebnis[$ort] .= "<td><div style=\"white-space: nowrap;\"><i class=\"fa fa-home\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-globe\"></i></div></td>";
          }
          else if ($ort=="HTTP_Responses" || ($ort=="HTTP_Header" && $fund["response"]) || ($ort=="Inhalte" && ($fund["typ"]=="response" || $fund["typ"]=="udprcv")))
          {
            $ergebnis[$ort] .= "<td><div style=\"white-space: nowrap;\"><i class=\"fa fa-globe\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-home\"></i></div></td>";
          }
          else
          {
            $ergebnis[$ort] .= "<td><div style=\"white-space: nowrap;\"><i class=\"fa fa-home\"></i> <i class=\"fa fa-arrows-h\"></i> <i class=\"fa fa-globe\"></i></div></td>";
          }
                    
          $ergebnis[$ort] .= "<td>" . ($verbindung["typ"]=="https"||$verbindung["typ"]=="ssl"? "<i class=\"fa fa-key\"></i>" : "") . "</td>";
          
          $ergebnis[$ort] .= "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-left\"></i></button></td>";
          $ergebnis[$ort] .= "<td  style=\"width:30% !important\" class=\"break\"".onevent("Fundstellen$ort").">$res</td>";
          if (!$weitere)
          {
            $ergebnis[$ort] .= "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-right\"></i></button></td>";
          }
          else
          {
            $ergebnis[$ort] .= "<td><button class=\"btn btn-info btn-xs\" title=\"nächste Fundstelle\" onclick=\"suchErgebnis('$id','" . $show . "','$ort',$limit,'" . jsSave($nadel) . "'," . (array_key_exists("regSwitch",$_REQUEST) && $_REQUEST["regSwitch"]=="on"? 1 : 0) . "," . (array_key_exists("caseSwitch",$_REQUEST) && $_REQUEST["caseSwitch"]=="on"? 1 : 0) . ",'0',$pos);\"><i class=\"fa fa-chevron-right\"></i></button></td>";            
          }

          $ergebnis[$ort] .= "</tr>";
        }        
        $limit++;
      }
      while ($fund = $fund_s->fetch());
      
      $ergebnis[$ort] .= <<<LIMIT1
          </tbody>
        </table>
LIMIT1;
    }
  }


  if (!count($funde))
  {
    echo <<<LIMIT1
  <div class="row">  
    <div class="panel panel-primary">
      <div class="panel-heading" role="tab">
        <h4 class="panel-title">Fundstellen</h4>
      </div>
      <div class="panel-body">
LIMIT1;
    echo "<p><strong>",htmlspecialchars($_REQUEST["suche"]),"</strong> wurde nicht gefunden.</p>";
    echo <<<LIMIT1
      </div>
    </div>
  </div>
LIMIT1;
  }
  
  else
  {
    echo <<<LIMIT1
  <div class="row">
    <div class="panel-group" id="about" role="tablist">
LIMIT1;

    foreach ($suchOrte as $ort => $info)
    {
      if (array_key_exists($ort,$_REQUEST) || array_key_exists("orte",$_REQUEST))
      {
        echo <<<LIMIT1
      <div class="panel panel-primary">
        <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#fund$ort">
          <h4 class="panel-title">
LIMIT1;

        if (!array_key_exists ($ort, $funde) || $funde[$ort]==0)
        {
          echo "<span class=\"emptyPanel\">es wurden keine ",$suchOrte[$ort][1]," gefunden</span>";
        }
        else if (array_key_exists ($ort, $funde))
        {
          if ($funde[$ort]==1)
          {
            echo "es wurde 1 ",$suchOrte[$ort][0], " gefunden";
          }
          else
          {
            echo "es wurden ",$funde[$ort]," ",$suchOrte[$ort][1]," gefunden";
          }
        }
        
        echo <<<LIMIT1
          </h4>
        </div>
        <div id="fund$ort" class="panel-collapse collapse" role="tabpanel">
          <div class="panel-body">
LIMIT1;
        if (!array_key_exists ($ort, $funde) || $funde[$ort]==0)
        {
          echo "Keine Ergebnisse.";
        }
        else
        {
          echo $ergebnis[$ort];
        }

        echo <<<LIMIT1
          </div>
        </div>
      </div>
LIMIT1;
      }
    }
    
  echo <<<LIMIT1
    </div>
  </div>
LIMIT1;
  }
}

include ("include/htmlend.php");

?>

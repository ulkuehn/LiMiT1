<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/setcookies.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display a table of all cookies that were received
//              in a specific request or connection
//
//==============================================================================
//==============================================================================

$_ansehen = "Cookie ansehen";

if (isset($_GET["verbindung"]) || isset($_GET["request"]))
{
  if (isset($_GET["verbindung"]))
  {
    $select_s = $db->prepare("select *,date_format(setcookie.expires,'%e.%c.%Y %H:%i') as _expires from setcookie,cookie where setcookie.cookie=cookie.id and setcookie.verbindung=?");
    $select_s->execute(array($_GET["verbindung"]));
  }
  else
  {
    $select_s = $db->prepare("select *,date_format(setcookie.expires,'%e.%c.%Y %H:%i') as _expires from setcookie,cookie where setcookie.cookie=cookie.id and setcookie.request=?");
    $select_s->execute(array($_GET["request"]));
  }
  
  if (($setcookie = $select_s->fetch()) == false)
  {
    echo "<p>Es wurden keine Cookies empfangen.</p>";
  }
  else
  {
    echo tableSorter ("Cookies", "columns: [ {orderable:false, searchable:false}, {}, {}, {}, {}, {type:'date'}, {type:'num'}, {}" . (isset($_GET["verbindung"])? ", {type:'num'}":"") . " ], order: [ [1,'asc'] ]");
    
    $foldMe = tableFolder ("Cookies");
    echo <<<LIMIT1
    <div class="table-responsive">
      <table id="Cookies" class="table table-hover">
      <thead>
        <tr>
          <th>$foldMe</th>
          <th>Name</th>
          <th>Wert</th>
          <th>Site</th>
          <th>Pfad</th>
          <th>Verfall</th>
          <th><span class="compactCookies">Dauer</span><span class="fullCookies">Speicherdauer</span></th>
          <th><span class="compactCookies">Eig.</span><span class="fullCookies">Eigenschaften</span></th>
LIMIT1;
    if (isset($_GET["verbindung"]))
    {
      echo "<th>versandt</th>";
    }
    echo <<<LIMIT1
        </tr>
      </thead>
      <tbody>
LIMIT1;

    do
    {
      echo "<tr>";
      echo "<td>",viewButton("cookie.php?cookie=".$setcookie["id"]."&aufzeichnung=$aufzeichnungId",$_ansehen),"</td>";      
      echo faltZelle ($setcookie["name"], "Cookies");
      echo faltZelle ($setcookie["wert"], "Cookies");
      echo faltZelle ($setcookie["site"], "Cookies", true);
      echo "<td>",$setcookie["path"],"</td>";
      
      if ($setcookie["expires"]=="" || $setcookie["expires"]==0)
      {
        echo "<td><!--0--></td>";
      }
      else
      {
        echo "<td",onevent("Cookies"),"><span class=\"compactCookies\">",explode(" ",$setcookie["_expires"])[0],"$tableEllipsis</span><span class=\"fullCookies\">",$setcookie["_expires"],"</span><!--",$setcookie["expires"],"--></td>";
      }
      
      if ($setcookie["valid"] == 0)
      {
        echo "<td>Session<!--0--></td>";
      }
      else
      {
        $span = timeSpan($setcookie["valid"]);
        echo "<td",onevent("Cookies"),"><span class=\"compactCookies\">",explode(",",$span)[0],"$tableEllipsis</span><span class=\"fullCookies\">$span</span><!--",$setcookie["valid"],"--></td>";
      }
      
      echo "<td",onevent("Cookies"),">";
      echo $setcookie["httponly"]? "<span class=\"compactCookies\"><i class=\"fa fa-code\" title=\"httponly\"></i></span><span class=\"fullCookies\">für Skripte nicht zugänglich (\"httponly\")</span><br>" : "";
      echo $setcookie["secure"]? "<span class=\"compactCookies\"><i class=\"fa fa-key\" title=\"secure\"></i></span><span class=\"fullCookies\">nur verschlüsselter Versand (\"secure\")</span><br>" : "";
      echo $setcookie["comment"]? "<span class=\"compactCookies\"><i class=\"fa fa-commenting-o\" title=\"comment\"></i></span><span class=\"fullCookies\">Kommentar: \"".$setcookie["comment"]."\"</span>" : "";
      echo "</td>";
      
      if (isset($_GET["verbindung"]))
      {
        $sendcookie_s = $db->prepare("select count(*) from sendcookie where cookie=? and wert=? and verbindung=?");
        $sendcookie_s->execute(array($setcookie["cookie"],$setcookie["wert"],$_GET["verbindung"]));
        echo "<td class=\"numeric\">",$sendcookie_s->fetchColumn()," mal</td>";
      }    
    }
    while ($setcookie = $select_s->fetch());
        
    echo "</tbody></table></div>";
  }
}

?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/sentcookies.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display a table of all cookies that were sent in
//              a specific request or connection
//
//==============================================================================
//==============================================================================


$_ansehen = "Cookie ansehen";

if (isset($_GET["verbindung"]) || isset($_GET["request"]))
{
  if (isset($_GET["verbindung"]))
  {
    $select_s = $db->prepare("select *,count(sendcookie.request) as _requests from sendcookie,cookie where sendcookie.cookie=cookie.id and sendcookie.verbindung=? group by sendcookie.wert,sendcookie.cookie");
    $select_s->execute(array($_GET["verbindung"]));
  }
  else
  {
    $select_s = $db->prepare("select *,count(sendcookie.request) as _requests from sendcookie,cookie where sendcookie.cookie=cookie.id and sendcookie.request=? group by sendcookie.wert,sendcookie.cookie");
    $select_s->execute(array($_GET["request"]));
  }
  
  if (($sendcookie = $select_s->fetch()) == false)
  {
    echo "<p>Es wurden keine Cookies versandt.</p>";
  }
  else
  {
    echo tableSorter ("Sendcookies", "columns: [ {orderable:false, searchable:false}, {}, {}, {}" . (isset($_GET["verbindung"])? ", {type:'num'}, {}":"") . " ], order: [ [1,'asc'] ]");
    
    $foldMe = tableFolder ("Sendcookies");
    echo <<<LIMIT1
    <div class="table-responsive">
      <table id="Sendcookies" class="table table-hover">
      <thead>
        <tr>
          <th>$foldMe</th>
          <th>Name</th>
          <th>Wert</th>
          <th>Site</th>
LIMIT1;
    if (isset($_GET["verbindung"]))
    {
      echo <<<LIMIT1
          <th>versandt</th>
          <th>empfangen</th>
LIMIT1;
    }
    echo <<<C4
        </tr>
      </thead>
      <tbody>
C4;

    do
    {
      echo "<tr>";
      echo "<td>",viewButton("cookie.php?cookie=".$sendcookie["id"]."&aufzeichnung=$aufzeichnungId",$_ansehen),"</td>";
      echo faltZelle ($sendcookie["name"], "Sendcookies");
      echo faltZelle ($sendcookie["wert"], "Sendcookies");
      echo faltZelle ($sendcookie["site"], "Sendcookies", true);
      
      if (isset($_GET["verbindung"]))
      {
        echo "<td class=\"numeric\">",$sendcookie["_requests"]," mal</td>";
        $setcookie_s = $db->prepare("select count(*) from setcookie where cookie=? and wert=? and verbindung=?");
        $setcookie_s->execute(array($sendcookie["cookie"],$sendcookie["wert"],$_GET["verbindung"])); 
        echo "<td>",$setcookie_s->fetchColumn()? "ja":"nein","</td>";
      }

      echo "</tr>";      
    }
    while ($sendcookie = $select_s->fetch());
        
    echo "</tbody></table></div>";
  }
}

?>

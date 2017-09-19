<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: status.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display the current status of a LiMiT1 system
//              the actual status information is collected by the
//              corresponding script in include and updated by an ajax request
//
//==============================================================================
//==============================================================================


require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");


titleAndHelp ("$my_name-Status", <<<LIMIT1
Auf dieser Seite werden verschiedene statische und dynamische Eigenschaften des Systems angezeigt.
LIMIT1
);

echo <<<LIMIT1
<div class="row">
  <div class="panel-group" id="status" role="tablist">
    <p class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-lg"></i></p>
  </div>
</div>

<script>
function status()
{
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() 
  {
    if (xmlhttp.readyState==4 && xmlhttp.status==200) 
    {
      document.getElementById("status").innerHTML = xmlhttp.responseText;
    }
  }
  
  xmlhttp.open("GET","include/status.php",true);
  xmlhttp.send();
}
status ();
var myVar = setInterval (function () { status() }, 2500);
</script>

LIMIT1;
      
require ("include/htmlend.php");

?>

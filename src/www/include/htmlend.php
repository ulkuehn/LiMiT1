<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/htmlend.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to properly close html tags left open by tmlstart.php
//              if debugging is configured, a bunch of extra information is
//              presented to the user
//
//==============================================================================
//==============================================================================


function cmd ($cmd)
{
  echo "<pre>$cmd\n\n",htmlspecialchars (system ($cmd)),"</pre>";
}
  
if ($__debug && isset($starttime))
{
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $endtime = $mtime;
  $totaltime = " &middot;//&middot; ".strftime ("%d.%m.%Y, %H:%M:%S")." &middot; ".floor(($endtime - $starttime)*1000)." ms";
}
else
{
  $totaltime = "";
}

echo <<<LIMIT1
  <p class="text-right small"><em>$my_name &middot; Version $my_version<span id=\"totaltime\">$totaltime</span></em></p>
LIMIT1;

if ($__debug)
{
  $globals = array (
                     '$_REQUEST' => $_REQUEST, '$_GET' => $_GET, '$_POST' => $_POST, '$_COOKIE' => $_COOKIE,
                     '$_SERVER' => $_SERVER, '$_ENV' => $_ENV, '$_FILES' => $_FILES
                   );  

  echo "<div class=\"row\">";
  foreach ($globals as $g=>$v)
  {
    echo "<pre>$g:\n",htmlspecialchars(print_r($v,true)),"</pre>";
  }
  echo "</div>";
}

if ($__debug)
{
  echo "<div class=\"row\">";
  cmd ("/usr/bin/free -m");
  cmd ("/bin/df -h");
  cmd ("/sbin/ifconfig");
  cmd ("/sbin/iwconfig");
  cmd ("/bin/ps au -N --pid 2 --ppid 2");
  echo "</div>";
}


// Inhaltscontainer-div schließen und HTML beenden
echo <<<LIMIT1
    </div>
  </body>
</html>
LIMIT1;

?>

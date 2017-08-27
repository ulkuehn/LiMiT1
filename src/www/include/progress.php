<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/progress.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to show the progress of processing raw recorded data to
//              structured data in the database
//              works by counting the connections that are processed vs those
//              that have been recorded
//
//==============================================================================
//==============================================================================


require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");
require ("../include/database.php");

$aufzeichnungID = $_GET["id"];
if ($__debug && isset($_GET["start"]))
{
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $endtime = $mtime;
  $totaltime = " &middot;//&middot; ".strftime ("%d.%m.%Y, %H:%M:%S")." &middot; ".floor(($endtime - $_GET["start"])*1000)." ms";
}
else
{
  $totaltime = "";
}

if (file_exists($session_file))
{
  $con = 0 + `/bin/ls -1 "$data_dir/$aufzeichnungID/$connection_dir" | wc -l`;
  $conDone = 0 + `/bin/ls -1 "$temp_dir/$aufzeichnungID/$connection_dir" | wc -l`;
  $width = floor($conDone*100/($con==0? 1:$con));
  
  echo <<<LIMIT1
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Fortschritt</h4>
      </div>
      <div class="panel-body">
        <div class="progress">
          <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" style="min-width: 5em; width: $width%">
            <p style="color:black;font-weight:bold">$conDone / $con</p>
          </div>
        </div>
LIMIT1;
  progressMsg ("Restliche Verbindungen in Datenbank übernehmen (noch ".($con-$conDone)." übrig)$totaltime");
  echo <<<LIMIT1
      </div>
    </div>
LIMIT1;
}
else
{
  echo "";
}

?>

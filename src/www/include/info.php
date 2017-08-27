<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/info.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to provide several system and state information for the
//              top menu's info button (see include/topmenu.php)
//
//==============================================================================
//==============================================================================


require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");
require ("../include/database.php");


function row ($left, $right, $warning=false)
{
  $class = $warning? " bg-warning" : "";
  $sign = $warning? "<span class=\"text-danger\"><i class=\"fa fa-warning fa-lg\"></i></span>" : "";
  echo <<<LIMIT1
    <div class="row$class">
      <div class="col-xs-5"><p class="text-right">$left</p></div>
      <div class="col-xs-1"><p class="text-center">$sign</p></div>
      <div class="col-xs-6"><p><strong>$right</strong></p></div>
    </div>
LIMIT1;
}


row ( "Datum", strftime ("%d.%m.%Y"), strftime("%Y")<2016 || !file_exists($online_flag) );
row ( "Uhrzeit", strftime ("%H:%M:%S"), strftime ("%Y")<2016 || !file_exists($online_flag) );
row ( "Betriebsdauer", zeitDauer(time() - strtotime (`/usr/bin/uptime -s`)) );
row ( "", "");

$memused = `/bin/df -h | /bin/grep $data_dir | /usr/bin/tr -s ' ' | /usr/bin/cut -d ' ' -f5 | /usr/bin/cut -d '%' -f1`;
row ( "USB-Speicher&nbsp;voll","$memused %", $memused>90 );
row ( "", "");

// are we online and if so, how?
$internet = "Offline";
$warn = true;
if (is_readable($offline_script))
{
  $cfile = fopen ($offline_script, "r");
  $line = fgets ($cfile); 
  fclose ($cfile);
  $internet = trim (substr ($line, 1));
  $warn = false;
}
row ( "Internet", $internet, $warn );
if (!$warn)
{
  row ( "Online", $__internet_aufzeichnung? "Nur während einer Aufzeichnung":"Dauerhaft");
}


if (!file_exists ($session_file))
{
  row ( "Aufzeichnung", "nein" );
}
else
{
  # read contents
  $sfile = fopen ($session_file, "r");
  $aufzeichnungID = trim (fgets ($sfile));
  $deviceip = trim (fgets ($sfile));
  $running = trim (fgets ($sfile));
  fclose ($sfile);

  if ($running)
  {
    row ("Aufzeichnung läuft für IP", $deviceip);

    # stat info file
    $since = stat ($session_file)["mtime"];
    row ( "Aufzeichnungsdauer", gmdate("H:i:s", time()-$since) );
  }
  else
  {
    row ( "Aufzeichnung", "wird beendet" );
  }
  
  $con = 0 + `/bin/ls -1 "$data_dir/$aufzeichnungID/$connection_dir" | wc -l`;
  $conDone = 0 + `/bin/ls -1 "$temp_dir/$aufzeichnungID/$connection_dir" | wc -l`;
  row ( "Verbindungen (davon in DB)", "$con ($conDone)" );
}

?>

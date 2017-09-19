<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: disconnect.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to disconnect the LiMiT1 system from the internet
//
//==============================================================================
//==============================================================================

require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");


titleAndHelp ("Offline", <<<LIMIT1
Die bestehende Internetverbindung trennen. Eine Aufzeichnung ist nur bei bestehender Internetverbindung möglich.
LIMIT1
);

echo "<div class=\"row\">";

if (file_exists ($session_file))
{
  errorMsg ("Im Moment läuft eine Aufzeichnung. Diese muss beendet werden, bevor $my_name vom Internet getrennt werden kann.");
}

else
{
  if (is_readable($offline_script))
  {
    $cfile = fopen ($offline_script, "r");
    $line = fgets ($cfile); 
    fclose ($cfile);
    $internet = trim (substr ($line, 1));
  }

  if (array_key_exists("offline", $_REQUEST))
  {
    offline ();

    echo <<<LIMIT1
  <script>
    document.getElementById("topmenuOffline").classList.add("disabled");
  </script>
LIMIT1;
    successMsg ("Die $internet-Verbindung wurde getrennt. $my_name ist jetzt offline.");
  }

  else
  {
    echo <<<LIMIT1
  <form method="post">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Die per <strong>$internet</strong> bestehende Internetverbindung trennen</h4>
      </div>
      <div class="panel-body">
LIMIT1;
    alertMsg ("Nach Trennen der Internetverbindung ist keine Aufzeichnung mehr möglich.");
    if (!$__internet_aufzeichnung)
    {
      infoMsg ("Die angeschlossenen Geräte werden dadurch ebenfalls vom Internet getrennt.");
    }
    echo <<<LIMIT1
        <input type="submit" class="btn btn-primary" value="Trennen" name="offline">
      </div>
    </div>
  </form>
LIMIT1;
  }
}

echo "</div>";

require ("include/htmlend.php");

?>

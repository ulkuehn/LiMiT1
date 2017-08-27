<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: mockup.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display a sample page reflecting a skin the user
//              selects in the system settings
//              this scripts thus provides for a preview of that selection
//
//==============================================================================
//==============================================================================


require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

include ("include/http.php");

if (isset($_GET["skin"]))
{
  $__skin = $_GET["skin"];
}

echo <<<LIMIT1
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
LIMIT1;

echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"",($__skin == ""? "css/bootstrap.min.css" : "$skin_dir/$__skin"),"\">";


echo <<<LIMIT1
    <link rel="stylesheet" type="text/css" href="css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="css/datatables.min.css">
    <link rel="stylesheet" type="text/css" href="css/limit1.css">

    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/datatables.min.js"></script>
    <script type="text/javascript">
      var whoclasses = [ "text-black bg-info", "text-black bg-danger", "text-black bg-success" ];
      function whoover (div,pm)
      {
        var ok = 0;
        $(div).parent().children("div").each (
          function () 
          { 
            if (this==div) 
            { 
              ok = 1; 
            }
            if (ok)
            {
              $(this).addClass( whoclasses[pm] );
            }
          });
      }
          
      function whoout (div,pm)
      {
        $(div).parent().children("div").each (
          function () 
          {
            $(this).removeClass( whoclasses[pm] ); 
          });
      }
    </script>
    
    <title>$my_name</title>
  </head>
<body>
LIMIT1;

$__suchbox=1;
$__dekodbox=0;
$__whoisbox=0;

require ("include/topmenu.php");

echo "<div class=\"row\">";
infoMsg ("Dies ist eine Beispielseite");
echo "</div>";

echo <<<LIMIT1
<div class="row">
  <button class="btn btn-default">Standard</button>
  <button class="btn btn-primary">Standard</button>
  <button class="btn btn-info">Info</button>
  <button class="btn btn-success">Erfolg</button>
  <button class="btn btn-warning">Warnung</button>
  <button class="btn btn-danger">Fehler</button>
</div>
<div class="row">
  <p></p>
  <h1 style="display:inline">Überschrift </h1>
  <h2 style="display:inline"> Überschrift </h2>
  <h3 style="display:inline"> Überschrift </h3>
  <h4 style="display:inline"> Überschrift </h4>
  <h5 style="display:inline"> Überschrift</h5>
</div>
<div class="row">
  <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut laboreLorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enimad minim veniam, quis nostrud exercitation ullamco laboris nisi utaliquip ex ea commodo consequat. Duis aute irure dolor inreprehenderit in voluptate velit esse cillum dolore eu fugiat nullapariatur. Excepteur sint occaecat cupidatat non proident, sunt inculpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enimad minim veniam, quis nostrud exercitation ullamco laboris nisi utaliquip ex ea commodo consequat. Duis aute irure dolor inreprehenderit in voluptate velit esse cillum dolore eu fugiat nullapariatur. Excepteur sint occaecat cupidatat non proident, sunt inculpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enimad minim veniam, quis nostrud exercitation ullamco laboris nisi utaliquip ex ea commodo consequat. Duis aute irure dolor inreprehenderit in voluptate velit esse cillum dolore eu fugiat nullapariatur. Excepteur sint occaecat cupidatat non proident, sunt inculpa qui officia deserunt mollit anim id est laborum.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed doeiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enimad minim veniam, quis nostrud exercitation ullamc</p>
</div> 
LIMIT1;

require ("include/htmlend.php");

?>

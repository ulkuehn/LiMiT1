<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/htmlstart.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to provide html headers etc for all php scripts
//
//==============================================================================
//==============================================================================


$extratitel = isset ( $extratitel ) ? $extratitel : "";
$framename = isset ( $framename ) ? $framename : "";

echo <<<LIMIT1
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
LIMIT1;

if ( $__skin != "" )
{
  echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$skin_dir$__skin\">";
}

echo <<<LIMIT1
    <link rel="stylesheet" type="text/css" href="css/font-awesome.min.css">
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
      
      window.name = "$my_name$framename";
    </script>
    
    <title>$my_name$extratitel</title>
  </head>
<body>
LIMIT1;

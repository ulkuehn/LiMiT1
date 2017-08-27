<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: dekodieren.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to decode encoded data of several flavors
//              the script tries to cover all possible combinations of
//              encodings to possibly produce the correct decoding, while
//              producing a multitude of meaningless decodings
//
//==============================================================================
//==============================================================================

require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
$extratitel = " - Dekodieren";
$framename = $__usetabs ? "dekodieren" : "";
include ("include/htmlstart.php");
$extranav = $__usetabs ? "<div class=\"navHider\"></div>" : "";
include ("include/topmenu.php");

$codeValue = array_key_exists("code", $_POST)? htmlspecialchars($_POST["code"]) : "";

titelHilfe ("Dekodieren", <<<LIMIT1
Mit dieser Hilfsfunktion können kodierte Zeichenketten erkannt und ihr Inhalt sichtbar gemacht werden.
<br>
Dabei werden gängige Kodierungen wie Base64 und URL-Encode berücksichtigt. Um Mehrfachkodierungen aufzulösen,
werden auch deren Kombinationen ausprobiert. Dadurch werden häufig keine sinnvollen Ergebnisse angezeigt.
Die richtige Dekodierung lässt sich jedoch meist schnell finden.
LIMIT1
);

echo <<<LIMIT1
<form method="post">
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading" role="tab">
        <h4 class="panel-title">Kodierte Zeichen</h4>
      </div>
      <div class="panel-body">
        <textarea class="form-control" id="icode" name="code" rows=3 style="resize:vertical">$codeValue</textarea>
        <div class="pull-left">
          <input type="submit" class="btn btn-primary" value="Dekodieren">
        </div>
        <div class="pull-right">
          <button type="button" class="btn btn-default float-right" onclick="document.getElementById('icode').value='';">&times;</button>
        </div>
      </div>
    </div>
  </div>
</form>
LIMIT1;



$pieces["Wert"] = array(array_key_exists("code", $_POST)? $_POST["code"] : "");
$convert["Wert"] = array(false);
$done["Wert"] = false;
$seq = array ("Wert");

do
{
  $vorher = count($pieces);
  foreach ($seq as $kodierung)
  {    
    if ($done[$kodierung])
    {
      continue;
    }
    $done[$kodierung] = true;
    
    // URL
    $kod = "URL ($kodierung)";
    $wert = implode ("",$pieces[$kodierung]);
    $epieces = array ();
    $econvert = array ();
    $okay = false;
    while ($wert != "" && preg_match ("/((?:%[0-9a-f][0-9a-f])+)/i", $wert, $matches, PREG_OFFSET_CAPTURE))
    {
      $okay = true;
      array_push ($epieces, substr ($wert, 0, $matches[1][1]));
      array_push ($econvert, 0);
      array_push ($epieces, urldecode($matches[1][0]));
      array_push ($econvert, 1);      
      $wert = substr ($wert, $matches[1][1]+strlen($matches[1][0]));
    }
    array_push ($epieces, $wert);
    array_push ($econvert, 0);
    if ($okay && implode ("",$pieces[$kodierung]) !=  implode ("",$epieces))
    {
      $pieces[$kod] = $epieces;
      $convert[$kod] = $econvert;
      $done[$kod] = false;
      array_push ($seq, $kod);
    }


    // Base16
    $kod = "Base16 ($kodierung)";
    $wert = implode ("",$pieces[$kodierung]);
    $epieces = array ();
    $econvert = array ();
    $okay = false;
    while ($wert != "" && preg_match ("/(?:0x)?([0-9a-f]{2}+)/i", $wert, $matches, PREG_OFFSET_CAPTURE))
    {
      $okay = true;
      array_push ($epieces, substr ($wert, 0, $matches[1][1]));
      array_push ($econvert, 0);
      $bin = "";
      for ($i=0; $i<strlen($matches[1][0]); $i+=2)
      {
        $bin .= chr (base_convert(substr($matches[1][0], $i, 2), 16, 10));
      }
      array_push ($epieces, $bin);
      array_push ($econvert, 1);      
      $wert = substr ($wert, $matches[1][1]+strlen($matches[1][0]));
    }
    array_push ($epieces, $wert);
    array_push ($econvert, 0);
    if ($okay && implode ("",$pieces[$kodierung]) !=  implode ("",$epieces))
    {
      $pieces[$kod] = $epieces;
      $convert[$kod] = $econvert;
      $done[$kod] = false;
      array_push ($seq, $kod);
    }


    // Base64
    $kod = "Base64 ($kodierung)";
    $wert = implode ("",$pieces[$kodierung]);
    $epieces = array ();
    $econvert = array ();
    $okay = false;
    while ($wert != "" && preg_match ("/([a-zA-Z0-9\+\/=]{4}+)/i", $wert, $matches, PREG_OFFSET_CAPTURE))
    {
      array_push ($epieces, substr ($wert, 0, $matches[1][1]));
      array_push ($econvert, 0);
      if ( ($bin = base64_decode($matches[1][0], true )) != false)
      {
        $okay = true;
        array_push ($epieces, $bin);
        array_push ($econvert, 1);      
      }
      else
      {
        array_push ($epieces, $matches[1][0]);
        array_push ($econvert, 0);      
      }
      $wert = substr ($wert, $matches[1][1]+strlen($matches[1][0]));
    }
    array_push ($epieces, $wert);
    array_push ($econvert, 0);
    if ($okay && implode ("",$pieces[$kodierung]) !=  implode ("",$epieces))
    {
      $pieces[$kod] = $epieces;
      $convert[$kod] = $econvert;
      $done[$kod] = false;
      array_push ($seq, $kod);      
    }


    // Unixtime
    $kod = "strftime ($kodierung)";
    $wert = implode ("",$pieces[$kodierung]);
    $epieces = array ();
    $econvert = array ();
    $okay = false;
    while ($wert != "" && preg_match ("/([0-9]+)/", $wert, $matches, PREG_OFFSET_CAPTURE))
    {
      array_push ($epieces, substr ($wert, 0, $matches[1][1]));
      array_push ($econvert, 0);
      $min_ts = strtotime ("2000-01-01 00:00:00");
      $max_ts = 2147483648;
      $erg = "";
      if ($min_ts <= $matches[1][0] && $matches[1][0] <= $max_ts)
      {
        $erg = strftime ("%d.%m.%Y %T", $matches[1][0]);
      }
      else if ($min_ts <= $matches[1][0]/1000 && $matches[1][0]/1000 <= $max_ts)
      {
        $erg = strftime ("%d.%m.%Y %T", $matches[1][0]/1000) . "," . $matches[1][0]%1000;
      }
      else if ($min_ts <= $matches[1][0]/1000000 && $matches[1][0]/1000000 <= $max_ts)
      {
        $erg = strftime ("%d.%m.%Y %T", $matches[1][0]/1000000);
      }
      if ($erg != "")
      {
        array_push ($epieces, $erg);
        array_push ($econvert, 1);
        $okay = true;
      }
      else
      {
        array_push ($epieces, $matches[1][0]);
        array_push ($econvert, 0);      
      }
      $wert = substr ($wert, $matches[1][1]+strlen($matches[1][0]));
    }
    array_push ($epieces, $wert);
    array_push ($econvert, 0);
    if ($okay && implode ("",$pieces[$kodierung]) !=  implode ("",$epieces))
    {
      $pieces[$kod] = $epieces;
      $convert[$kod] = $econvert;
      $done[$kod] = true;
      array_push ($seq, $kod);
    }
    
    
    // MIME header
    $kod = "MIME ($kodierung)";
    $wert = implode ("",$pieces[$kodierung]);
    $epieces = array ();
    $econvert = array ();
    $okay = false;
    while ($wert != "" && preg_match ("/(=\?.*\?=)/", $wert, $matches, PREG_OFFSET_CAPTURE))
    {
      array_push ($epieces, substr ($wert, 0, $matches[1][1]));
      array_push ($econvert, 0);
      if ( ($mime = iconv_mime_decode ($matches[1][0], 1, "UTF-8" )) != false)
      {
        array_push ($epieces, $mime);
        array_push ($econvert, 1);
        $okay = true;
      }
      else
      {
        array_push ($epieces, $matches[1][0]);
        array_push ($econvert, 0);      
      }
      $wert = substr ($wert, $matches[1][1]+strlen($matches[1][0]));
    }
    array_push ($epieces, $wert);
    array_push ($econvert, 0);
    if ($okay && implode ("",$pieces[$kodierung]) != implode ("",$epieces))
    {
      $pieces[$kod] = $epieces;
      $convert[$kod] = $econvert;
      $done[$kod] = true;
      array_push ($seq, $kod);
    }
  
  }
}
while (count($pieces) != $vorher);
#unset ($pieces["Wert"]);

#echo "<p><pre>",print_r($pieces),"</pre></p>";
#echo "<p><pre>",print_r($convert),"</pre></p>";


if (count($pieces))
{
  echo <<<LIMIT1
<div class="row">
  <div class="panel panel-primary">
    <div class="panel-heading" role="tab">
      <h4 class="panel-title">Mögliche Kodierungen</h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
          <table id="Kodierungen" class="table table-hover">
            <thead>
              <tr>
                <th>Kodierung</th>
                <th>Dekodierte Zeichen</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;

  foreach ($seq as $kodierung)
  {
    $tr = "<tr><td>$kodierung</td><td class=\"break\"><pre>";
    $pl = 0;
    while ($pieces[$kodierung])
    {
      $piece = array_shift($pieces[$kodierung]);
      $convt = array_shift($convert[$kodierung]);
      $pl += strlen ($piece);
      $tr .= $convt? "<span class=\"highlight\">":"";
      $tr .= mb_convert_encoding($piece, "UTF-8", mb_detect_encoding($piece, "UTF-8, ISO-8859-1, ISO-8859-15", true));
      $tr .= $convt? "</span>":"";
    }
    $tr .= "</pre></td></tr>";
    if ($pl)
    {
      echo $tr;
    }
  }
  
  echo <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
LIMIT1;
}
   
include ("include/htmlend.php");

?>

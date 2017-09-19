<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: certmanage.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to manage the certificate of a LiMiT1 system needed for
//              interception of ssl traffic
//
//==============================================================================
//==============================================================================

require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
include ("include/htmlstart.php");
include ("include/topmenu.php");


$_download = "Zertifikat herunterladen";
$_erzeugen = "Zertifikat erzeugen";

function cField ($name, $text, $ph)
{
  global $_erzeugen;
  
  $val = array_key_exists ($name, $_REQUEST)? $_REQUEST[$name] : "";
  $hval = htmlspecialchars ($val);
  
  echo <<<LIMIT1
        <div class="form-group">
          <label for="$name" class="col-md-3 control-label">$text</label>
          <div class="col-md-9">
            <input type="text" class="form-control" name="$name" id="$name" placeholder="$ph" value="$hval">
          </div>
        </div>
LIMIT1;
  if (array_key_exists("erzeugen",$_REQUEST) && $val!="" && preg_match ("/=|\//",$val))
  {
    errorMsg ("\"$hval\" ist kein gültiger Wert für $text (Zeichen \"=\" und \"/\" sind nicht erlaubt)");
    return 1;
  }
  
  return 0;
}



titleAndHelp ("Zertifikatsverwaltung", <<<LIMIT1
Das $my_name-Zertifikat dient dazu, per SSL verschlüsselten Datenverkehr analysieren zu können.
Dazu tauscht $my_name in bestehenden Verbindungen das Zertifikat des Servers im Internet gegen das
eigene aus. Da für dieses Zertifikat auch der private Schlüssel existiert, können die von dem
Gerät verschlüsselten Daten durch $my_name entschlüsselt werden.<br>
Das Zertifikat muss auf dem jeweiligen Gerät installiert sein. 
Ist das Zertifikat nicht installiert, können auf dem Gerät über $my_name keine SSL-Verbindungen
hergestellt und keine verschlüsselten Inhalte analysiert werden.
Die Analyse beschränkt sich dann auf unverschlüsselte Verbindungen.
LIMIT1
);


// neues Zertifikat

$err = 0;
$hc = array_key_exists ("C", $_REQUEST)? htmlspecialchars($_REQUEST["C"]) : "";
echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading" role="tab">
        <h4 class="panel-title">Neues Zertifikat erzeugen</h4>
      </div>
      <div class="panel-body">
      <form class="form-horizontal" method="post">
        <div class="form-group">
          <label for="C" class="col-md-3 control-label">Länderkennung</label>
          <div class="col-md-9">
            <input type="text" class="form-control" name="C" id="C" placeholder="z.B. DE" value="$hc">
          </div>
        </div>
LIMIT1;

if (array_key_exists("erzeugen",$_REQUEST) && $_REQUEST["C"]!="" && !preg_match ("/^[a-z]{2}$/i",$_REQUEST["C"]))
{
  errorMsg ("\"$hc\" ist kein gültiger Wert für die Länderkennung");
  $err = 1;
}

$err += cField ("ST", "Staat oder Region", "z.B. Hamburg");
$err += cField ("L", "Stadt", "z.B. St. Pauli");
$err += cField ("O", "Organisation", "z.B. $my_name");
$err += cField ("OU", "Organisationseinheit", "z.B. $__dns_server_name.$__dns_domain_name");
$err += cField ("CN", "Name", "z.B. $my_name");

echo <<<LIMIT1
        <div class="form-group">
          <label for="tage" class="col-md-3 control-label">Gültigkeitsdauer in Tagen</label>
          <div class="col-md-9">
            <input type="number" class="form-control" name="tage" id="tage"  value="{$_REQUEST["tage"]}">
          </div>
        </div>
LIMIT1;
if (array_key_exists("erzeugen",$_REQUEST) && ($_REQUEST["tage"]=="" || !preg_match ("/^[0-9]+$/",$_REQUEST["tage"]) || $_REQUEST["tage"]<1 || $_REQUEST["tage"]>999999))
{
  errorMsg ("\"".$_REQUEST["tage"]."\" ist kein gültiger Wert für die Gültigkeitsdauer");
  $err = 1;
}

echo <<<LIMIT1
        <input type="submit" class="btn btn-primary" value="$_erzeugen" name="erzeugen">
      </form>
    </div>
  </div>
</div>
LIMIT1;

if (array_key_exists("erzeugen",$_REQUEST) && !$err)
{
  $subj = "/";
  foreach (array("C","ST","L","O","OU","CN") as $k)
  {
    if ($_REQUEST[$k]!="")
    {
      $subj .= "$k=".$_REQUEST[$k]."/";
    }
  }
  
  // cd to /tmp first, so that .rnd file is not written to directory where script lies
  exec ("cd /tmp; /usr/bin/openssl req -x509 -rand /dev/urandom -nodes -newkey rsa:2048 -keyout $key_file -out $cert_file -days {$_REQUEST["tage"]} -subj \"$subj\"");
}


// vorhandenes Zertifikat

echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading" role="tab">
        <h4 class="panel-title">Vorhandendes Zertifikat</h4>
      </div>
      <div class="panel-body">
LIMIT1;

if (file_exists($cert_file))
{
  $serial = trim ( explode ("=", exec ("/usr/bin/openssl x509 -in $cert_file -noout -serial"), 2)[1] );
  $serial = implode (":", preg_split ("/(..)/", $serial, NULL, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE));
  $issuer = trim ( explode ("=", exec ("/usr/bin/openssl x509 -in $cert_file -noout -issuer"), 2)[1] );
  $subject = trim ( explode ("=", exec ("/usr/bin/openssl x509 -in $cert_file -noout -subject"), 2)[1] );
  $notbefore = trim ( explode ("=", exec ("/usr/bin/openssl x509 -in $cert_file -noout -startdate"), 2)[1] );
  $notafter = trim ( explode ("=", exec ("/usr/bin/openssl x509 -in $cert_file -noout -enddate"), 2)[1] );
  
  echo <<<LIMIT1
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr><th>Feld</th><th>Wert</th></tr>
            </thead>
            <tbody>
LIMIT1;

  echo "<tr><td>Erzeugt</td><td>",date("d.m.Y, H:i:s",filectime($cert_file)),"</td></tr>";
  echo "<tr><td>Seriennummer</td><td>$serial</td></tr>";
  echo "<tr><td>Aussteller</td><td>$issuer</td></tr>";
  echo "<tr><td>Inhaber</td><td>$subject</td></tr>";
  $nb = date_parse_from_format ("F d G:i:s Y e",$notbefore);
  $na = date_parse_from_format ("F d G:i:s Y e",$notafter);
  echo "<tr><td>gültig</td><td>{$nb["day"]}.{$nb["month"]}.{$nb["year"]} &hellip; {$na["day"]}.{$na["month"]}.{$na["year"]}</td></tr>";

  echo <<<LIMIT1
            </tbody>
          </table>
        </div>
        <a class="btn btn-primary" href="certdown.php">$_download</a>
LIMIT1;
}

else
{
  alertMsg ("Es ist kein Zertifikat vorhanden.");
}

echo <<<LIMIT1
    </div>
  </div>
</div>
LIMIT1;

require ("include/htmlend.php");

?>

<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: whois.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display and obtain whois data for a domain
//              whois data which has been retreived on the internet is
//              kept in the LiMiT1 database for future use, so that not for each
//              whois-query an online connection must be set up
//
//==============================================================================
//==============================================================================


function whois ( $domain, $check )
{
  global $db, $offline_script, $my_name;

  $erg = "";
  $checking = false;
  $aktuelle = 0;

  $select_s = $db->prepare ( "select *, datediff(now(),stand) as _diffd, timediff(now(),stand) as _difft, date_format(stand,'%e.%c.%Y') as _standd, date_format(stand,'%H:%i:%s') as _standt from whois where domain=? order by stand desc" );
  $select_s->execute ( array ( $check ) );
  if ( ($whois = $select_s->fetch ()) == false || (isset ( $_POST[ "aktualisieren" ] ) && $_POST[ "aktualisieren" ] == $check) )
  {
    $checking = true;
    if ( is_readable ( $offline_script ) )
    {
      // whois Version 5.2.7 return value gives no indication if query was succesful (?)
      exec ( "/usr/bin/whois -H $check", $who, $ret );
      $who = implode ( "\n", $who );
      $insert_s = $db->prepare ( "insert into whois set domain=?, whois=?, stand=now(), okay=?" );
      // see above, so $ret bears no information
      $insert_s->execute ( array ( $check, $who, TRUE ) ); //$ret == 0 ) );
      $aktuelle = $db->lastInsertId ();

      $select_s = $db->prepare ( "select *, datediff(now(),stand) as _diffd, timediff(now(),stand) as _difft, date_format(stand,'%e.%c.%Y') as _standd, date_format(stand,'%H:%i:%s') as _standt from whois where domain=? order by stand desc" );
      $select_s->execute ( array ( $check ) );
      $whois = $select_s->fetch ();
    }
  }

  do
  {
    if ( $whois[ "id" ] == $aktuelle )
    {
      $tinfo = "(aktuelle Abfrage)";
    }
    else
    {
      $tinfo = "(gespeicherter Stand vom " . $whois[ "_standd" ] . " <i class=\"fa fa-clock-o\"></i> " . $whois[ "_standt" ] . " = vor ";
      if ( $whois[ "_diffd" ] > 2 )
      {
        $tinfo .= $whois[ "_diffd" ] . " Tagen)";
      }
      else
      {
        $tinfo .= $whois[ "_difft" ] . " Stunden)";
      }
    }

    $in = $whois[ "id" ] == $aktuelle ? " in" : "";
    $erg .= <<<LIMIT1
  <div class="panel panel-primary">
    <div class="panel-heading panelCollapse" role="tab" data-toggle="collapse" data-target="#{$whois[ "id" ]}">
      <h4 class="panel-title">$check $tinfo</h4>
    </div>
    <div id="{$whois[ "id" ]}" class="panel-collapse collapse$in" role="tabpanel">
      <div class="panel-body">
LIMIT1;
    if ( !$whois[ "okay" ] )
    {
      $erg .= alertMsg ( "Die Whois-Abfrage von \"$check\" war nicht erfolgreich", false );
    }

    $erg .= tableSorter ( "tab" . $whois[ "id" ], "columns: [ {}, {} ], order: [ [0,'asc'] ]" );

    $erg .= <<<LIMIT1
        <form method="post" class="form-horizontal">
          <input type="hidden" name="domain" value="$domain">
          <div class="form-group">
            <div class="col-sm-6">
              <button class="btn btn-info btn-sm" title="Textansicht" onclick="document.getElementById('ta{$whois[ "id" ]}').style.display='none';document.getElementById('te{$whois[ "id" ]}').style.display='block';return false;"><i class="fa fa-align-left"></i></button>
              <button class="btn btn-info btn-sm" title="Tabellenansicht" onclick="document.getElementById('te{$whois[ "id" ]}').style.display='none';document.getElementById('ta{$whois[ "id" ]}').style.display='block';return false;"><i class="fa fa-table"></i></button>
            </div>
            <div class="col-sm-6">
              <p class="text-right"><button type="submit" class="btn btn-success btn-sm" title="Aktualisieren" name="aktualisieren" value="$check">$check erneut abfragen <i class="fa fa-refresh"></i></button></p>
            </div>
          </div>
        </form>
        <div id="te{$whois[ "id" ]}">
          <pre>{$whois[ "whois" ]}</pre>
        </div>
        <div class="table-responsive" id="ta{$whois[ "id" ]}" style="display:none">
          <table id="tab{$whois[ "id" ]}" class="table table-hover">
            <thead>
              <tr>
                <th>Feld</th>
                <th>Wert</th>
              </tr>
            </thead>
            <tbody>
LIMIT1;
    $lines = array ();
    foreach ( explode ( PHP_EOL, $whois[ "whois" ] ) as $line )
    {
      if ( preg_match ( "/^([^:]+):\s+(.*)/", $line, $m ) )
      {
        if ( !array_key_exists ( $m[ 1 ] . $m[ 2 ], $lines ) )
        {
          $erg .= "<tr><td>" . $m[ 1 ] . "</td><td>" . $m[ 2 ] . "</td></tr>";
          $lines[ $m[ 1 ] . $m[ 2 ] ] = TRUE;
        }
      }
    }
    $erg .= <<<LIMIT1
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
LIMIT1;
  }
  while ( $whois = $select_s->fetch () );

  return array ( $erg, $checking );
}

require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
$extratitel = " - Whois";
$framename = $__usetabs ? "whois" : "";
include ("include/htmlstart.php");
$extranav = $__usetabs ? "<div class=\"navHider\"></div>" : "";
include ("include/topmenu.php");

$domain = array_key_exists ( "whois", $_REQUEST ) ? strtolower ( trim ( $_REQUEST[ "whois" ] ) ) : "";
$domainValue = htmlspecialchars ( $domain );

titleAndHelp ( "Whois-Abfrage", <<<LIMIT1
Die Whois-Informationen zu einer Domain enthalten viele hilfreiche Hinweise.
Bei Bedarf kann mit dieser Funktion eine entsprechende Abfrage durchgeführt werden.
Die über das Internet erhaltenen Informationen werden in der Datenbank gespeichert,
so dass sie nicht jedes Mal erneut abgerufen werden müssen.
<br>
Werden Domainnamen in Auswertungen angegeben, ist durch farbige Hinterlegung der Domainteile
erkennbar, ob bereits entsprechende Whois-Informationen in der Datenbank vorhanden sind.
LIMIT1
);

echo <<<LIMIT1
<form method="post" class="form-horizontal">
  <div class="row" style="margin-bottom:20px">
    <div class="input-group">
      <span class="input-group-btn">
        <input type="submit" class="btn btn-primary" value="Domain">
      </span>
      <input class="form-control" type="text" id="idomain" name="whois" value="$domainValue">
      <span class="input-group-btn">
        <button type="button" class="btn btn-default" onclick="document.getElementById('idomain').value='';">&times;</button>
      </span>
    </div>
  </div>
</form>
<div class="row">
  <div class="panel-group" id="about" role="tablist">
LIMIT1;

if ( ( preg_match ( "/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain ) &&
    preg_match ( "/^.{1,253}$/", $domain ) &&
    preg_match ( "/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain ) ) || ip2long ( $domain ) != false )
{
  $erg = "";
  $checking = false;

  list ($e, $c) = whois ( $domain, $domain );
  $erg .= $e;
  $checking |= $c;

  if ( ip2long ( $domain ) == false )
  {
    $parts = explode ( ".", $domain );
    while ( count ( $parts ) > 2 )
    {
      array_shift ( $parts );
      list ($e, $c) = whois ( $domain, implode ( ".", $parts ) );
      $erg .= $e;
      $checking |= $c;
    }
  }

  if ( !is_readable ( $offline_script ) && $checking )
  {
    errorMsg ( "$my_name ist offline. Eine Whois-Abfrage ist nur bei bestehender Internetverbindung möglich." );
  }
  echo $erg;
}
else if ( array_key_exists ( "whois", $_REQUEST ) )
{
  errorMsg ( "\"$domainValue\" ist kein gültiger Domainname" );
}

echo "</div></div>";

include ("include/htmlend.php");
?>

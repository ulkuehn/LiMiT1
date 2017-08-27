<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/verschluesselung.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to display the ssl specifics of an encrypted connection
//
//==============================================================================
//==============================================================================


unset ($verbindung);

if (isset($_GET["verbindung"]))
{
  $verbindungId = $_GET["verbindung"];
}
else if (isset($_GET["request"]))
{
  $select_s = $db->prepare("select verbindung from request where id=?");
  $select_s->execute(array($_GET["request"]));
  $verbindungId = $select_s->fetchColumn();
}
if (isset($verbindungId))
{
  $select_s = $db->prepare ("select * from verbindung where id=?");
  $select_s->execute (array($verbindungId));
  $verbindung = $select_s->fetch();
}

if (isset($verbindung) && ($verbindung["typ"]=="https" || $verbindung["typ"]=="ssl"))
{
  $select_s = $db->prepare ("select * from ".($verbindung["typ"]=="https"? "https":"ssltls")." where verbindung=?");
  $select_s->execute (array($verbindung["id"]));
  $crypt = $select_s->fetch();

  $foldMe = tableFolder ("Verschluesselung");

  echo <<<LIMIT1
      <div class="table-responsive">
        <table id="Aufzeichnung" class="table table-hover">
          <thead>
            <tr>
              <th>Eigenschaft</th>
              <th>Wert</th>
              <th>Erläuterung</th>
            </tr>
          </thead>
          <tbody>
LIMIT1;

  $select_s = $db->prepare ("select * from cipherSuite where id=?");
  $select_s->execute (array($crypt["ciphersuite"]));
  $cipherSuite = $select_s->fetch();
  $select_s = $db->prepare ("select * from cipher where id=?");
  $select_s->execute (array($cipherSuite["cipher"]));
  $cipher = $select_s->fetch();
  $select_s = $db->prepare ("select * from keyExchange where id=?");
  $select_s->execute (array($cipherSuite["keyExchange"]));
  $keyExchange = $select_s->fetch();
  $select_s = $db->prepare ("select * from mac where id=?");
  $select_s->execute (array($cipherSuite["mac"]));
  $mac = $select_s->fetch();

  switch (strtolower($crypt["sslversion"]))
  {
    case "sslv2":
    case "sslv3":
      $sign = $badSign;
      $erl = "SSLv2 und SSLv3 sind unsicher und sollten nicht mehr verwendet werden.";
      break;
    case "tlsv1.0":
    case "tlsv1.1":
      $sign = $mehSign;
      $erl = "TLS ist eine Verbesserung der veralteten SSL-Protokolls. Es sollte jedoch die aktuelle Version TLSv1.2 verwendet werden.";
      break;
    case "tlsv1.2":
      $sign = $goodSign;
      $erl = "TLSv1.2 ist die aktuelle Version.";
      break;
    default:
      $sign = $questSign;
      $erl = "Unbekannte Version.";
  }
  echo "<tr",onevent("Verschluesselung"),"><td>TLS-Version</td><td>",$crypt["sslversion"],"</td><td><span class=\"compactVerschluesselung\">$sign$tableEllipsis</span><span class=\"fullVerschluesselung\">$sign $erl</span></td></tr>";


  if ($crypt["effBits"]<128)
  {
    $sign = $badSign;
    $erl = "Verschlüsselungen mit einer Schlüssellänge von unter 128 Bit sind unsicher.";
  }
  else if ($crypt["effBits"]<256)
  {
    $sign = $mehSign;
    $erl = "Schlüssellängen mit 128 Bit und mehr gelten als sicher. Optimal wären Schlüssel ab 256 Bit.";
  }
  else
  {
    $sign = $goodSign;
    $erl = "Verfahren mit Schlüsseln ab 256 Bit Länge sind optimal.";
  }
  echo "<tr",onevent("Verschluesselung"),"><td>Schlüssellänge</td><td>",$crypt["effBits"]," Bits</td><td><span class=\"compactVerschluesselung\">$sign$tableEllipsis</span><span class=\"fullVerschluesselung\">$sign $erl</span></td></tr>";


  if ($keyExchange["forwardSecrecy"])
  {
    $sign = $goodSign;
    $erl = "Forward Secrecy stellt sicher, dass eine aufgezeichnete verschlüsselte Kommunikation nicht nachträglich entschlüsselt werden kann.";
  }
  else
  {
    $sign = $mehSign;
    $erl = "Ohne Forward Secrecy besteht das Risiko, dass aufgezeichnete Kommunikationsströme entschlüsselt werden können, wenn das Verschlüsselungsverfahren gebrochen wird.";
  }
  echo "<tr",onevent("Verschluesselung"),"><td>Forward Secrecy</td><td>",$keyExchange["forwardSecrecy"]?"ja":"nein","</td><td><span class=\"compactVerschluesselung\">$sign$tableEllipsis</span><span class=\"fullVerschluesselung\">$sign $erl</span></td></tr>";


  if ($keyExchange["secure"])
  {
    $sign = $goodSign;
    $erl = "Das Schlüsselaustauschverfahren ".$keyExchange["shortName"]." gilt als sicher.";
  }
  else
  {
    $sign = $badSign;
    $erl = "Das Schlüsselaustauschverfahren ".$keyExchange["shortName"]." gilt als unsicher.";
  }
  echo "<tr",onevent("Verschluesselung"),"><td>Schlüsselaustauschverfahren</td><td>",$keyExchange["longName"],"</td><td><span class=\"compactVerschluesselung\">$sign$tableEllipsis</span><span class=\"fullVerschluesselung\">$sign $erl</span></td></tr>";
  
  
  if ($cipher["secure"])
  {
    $sign = $goodSign;
    $erl = "Das Verschlüsselungsverfahren ".$cipher["shortName"]." gilt als sicher.";
  }
  else
  {
    $sign = $badSign;
    $erl = "Das Verschlüsselungsverfahren ".$cipher["shortName"]." gilt als unsicher.";
  }
  echo "<tr",onevent("Verschluesselung"),"><td>Verschlüsselungsverfahren</td><td>",$cipher["longName"]," (",$cipher["bits"]," Bits)</td><td><span class=\"compactVerschluesselung\">$sign$tableEllipsis</span><span class=\"fullVerschluesselung\">$sign $erl</span></td></tr>";
  
  
  if ($mac["secure"])
  {
    $sign = $goodSign;
    $erl = "Das Hashverfahren ".$mac["shortName"]." gilt als sicher.";
  }
  else
  {
    $sign = $badSign;
    $erl = "Das Hashverfahren ".$mac["shortName"]." gilt als unsicher.";
  }
  echo "<tr",onevent("Verschluesselung"),"><td>Hashverfahren</td><td>",$mac["longName"]," (",$mac["bits"]," Bits)</td><td><span class=\"compactVerschluesselung\">$sign$tableEllipsis</span><span class=\"fullVerschluesselung\">$sign $erl</span></td></tr>";

  echo <<<LIMIT1
          </tbody>
        </table>
      </div>
LIMIT1;

}

?>

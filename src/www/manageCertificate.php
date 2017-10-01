<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file manageCertificate.php
 * 
 * used to manage the certificate of a LiMiT1 system 
 * the certificate is needed for interception of ssl traffic
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/database.php");

include ("include/http.php");
include ("include/htmlstart.php");
include ("include/topmenu.php");


$createCertificateButtonText = _ ( "Zertifikat erzeugen" );
$createCertificateID = "createCertificate";
$certificateFields = [
    "country"      => "C",
    "state"        => "ST",
    "location"     => "L",
    "organization" => "O",
    "unit"         => "OU",
    "commonName"   => "CN"
];
$certificateDays = "days";

/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * display one field of certificate specification and process posted value
 * 
 * @param type $fieldID html id of the field
 * @param type $fieldText human readable caption of the field
 * @param type $placeHolder placeholder information
 * @return int errorcount (>0 if error)
 */
function certificateField ( $fieldID,
                            $fieldText,
                            $placeHolder )
{
  global $createCertificateID;

  $fieldValue = array_key_exists ( $fieldID,
                                   $_REQUEST ) ? $_REQUEST[ $fieldID ] : "";
  $showValue = htmlspecialchars ( $fieldValue );

  echo "<div class=\"form-group\"><label for=\"$fieldID\" class=\"col-md-3 control-label\">$fieldText</label>";
  echo "<div class=\"col-md-9\"><input type=\"text\" class=\"form-control\" name=\"$fieldID\" id=\"$fieldID\" placeholder=\"$placeHolder\" value=\"$showValue\"></div></div>";

  if ( array_key_exists ( $createCertificateID,
                          $_REQUEST ) && $fieldValue != "" && preg_match ( "/=|\//",
                                                                           $fieldValue ) )
  {
    errorMsg ( _ ( "\"$showValue\" ist kein gültiger Wert für $fieldText (Zeichen \"=\" und \"/\" sind nicht erlaubt)" ) );
    return 1;
  }

  return 0;
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

titleAndHelp ( "Zertifikatsverwaltung",
               _ ( "Das $my_name-Zertifikat dient dazu, per SSL verschlüsselten Datenverkehr analysieren zu können. Dazu tauscht $my_name in bestehenden Verbindungen das Zertifikat des Servers im Internet gegen das eigene aus. Da für dieses Zertifikat auch der private Schlüssel existiert, können die von dem Gerät verschlüsselten Daten durch $my_name entschlüsselt werden.<br>Das Zertifikat muss auf dem jeweiligen Gerät installiert sein. Ist das Zertifikat nicht installiert, können auf dem Gerät über $my_name keine SSL-Verbindungen hergestellt und keine verschlüsselten Inhalte analysiert werden. Die Analyse beschränkt sich dann auf unverschlüsselte Verbindungen." ) );


/*
 * new certificate
 */
$errors = 0;
echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\" role=\"tab\"><h4 class=\"panel-title\">", _ ( "Neues Zertifikat erzeugen" ), "</h4></div><div class=\"panel-body\"><form class=\"form-horizontal\" method=\"post\">";

$errors += certificateField ( $certificateFields[ "country" ],
                              _ ( "Länderkennung" ),
                                  _ ( "z.B. DE" ) );
/*
 * country value needs extra check
 */
if ( array_key_exists ( $createCertificateID,
                        $_REQUEST ) && $_REQUEST[ $certificateFields[ "country" ] ] != "" && !preg_match ( "/^[a-z]{2}$/i",
                                                                                                           $_REQUEST[ $certificateFields[ "country" ] ] ) )
{
  errorMsg ( "\"" . htmlspecialchars ( $_REQUEST[ $certificateFields[ "country" ] ] ) . "\" " . _ ( "ist kein gültiger Wert für die Länderkennung" ) );
  $errors++;
}

$errors += certificateField ( $certificateFields[ "state" ],
                              _ ( "Staat oder Region" ),
                                  _ ( "z.B. Hamburg" ) );

$errors += certificateField ( $certificateFields[ "location" ],
                              _ ( "Stadt" ),
                                  _ ( "z.B. St. Pauli" ) );

$errors += certificateField ( $certificateFields[ "organisation" ],
                              _ ( "Organisation" ),
                                  _ ( "z.B. $my_name" ) );

$errors += certificateField ( $certificateFields[ "unit" ],
                              _ ( "Organisationseinheit" ),
                                  _ ( "z.B. $__dns_server_name.$__dns_domain_name" ) );

$errors += certificateField ( $certificateFields[ "commonName" ],
                              _ ( "Name" ),
                                  _ ( "z.B. $my_name" ) );

$daysValid = array_key_exists ( $certificateDays,
                                $_REQUEST ) ? $_REQUEST[ $certificateDays ] : "";
echo "<div class = \"form-group\"><label for=\"$certificateDays\" class=\"col-md-3 control-label\">", _ ( "Gültigkeitsdauer in Tagen" ), "</label>";
echo "<div class=\"col-md-9\"><input type=\"number\" class=\"form-control\" name=\"$certificateDays\" id=\"$certificateDays\" value=\"$daysValid\"></div></div>";
if ( array_key_exists ( $createCertificateID,
                        $_REQUEST ) && (!preg_match ( "/^[0-9]+$/",
                                                      $daysValid ) || $daysValid < 1 || $daysValid > 999999) )
{
  errorMsg ( "\"" . $daysValid . "\" " . _ ( "ist kein gültiger Wert für die Gültigkeitsdauer" ) );
  $errors++;
}

echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"$createCertificateButtonText\" name=\"$createCertificateID\"></form></div></div></div>";

if ( array_key_exists ( $createCertificateID,
                        $_REQUEST ) && !$errors )
{
  $subject = "/";
  foreach ( $certificateFields as $fieldName => $fieldID )
  {
    if ( $_REQUEST[ $fieldID ] != "" )
    {
      $subject .= "$fieldID=" . $_REQUEST[ $fieldID ] . "/";
    }
  }

  // cd to /tmp first, so that .rnd file is not written to directory where script lies
  exec ( "cd /tmp; /usr/bin/openssl req -x509 -rand /dev/urandom -nodes -newkey rsa:2048 -keyout $key_file -out $cert_file -days $daysValid -subj \"$subject\"" );
}


/*
 * existing certificate
 */

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\" role=\"tab\"><h4 class=\"panel-title\">", _ ( "Vorhandendes Zertifikat" ), "</h4></div><div class=\"panel-body\">";

if ( file_exists ( $cert_file ) )
{
  $serial = trim ( explode ( "=",
                             exec ( "/usr/bin/openssl x509 -in $cert_file -noout -serial" ),
                                    2 )[ 1 ] );
  /*
   * insert colons for better readability
   */
  $serial = implode ( ":",
                      preg_split ( "/(..)/",
                                   $serial,
                                   NULL,
                                   PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE ) );

  $issuer = trim ( explode ( "=",
                             exec ( "/usr/bin/openssl x509 -in $cert_file -noout -issuer" ),
                                    2 )[ 1 ] );
  $subject = trim ( explode ( "=",
                              exec ( "/usr/bin/openssl x509 -in $cert_file -noout -subject" ),
                                     2 )[ 1 ] );
  $notbefore = trim ( explode ( "=",
                                exec ( "/usr/bin/openssl x509 -in $cert_file -noout -startdate" ),
                                       2 )[ 1 ] );
  $notafter = trim ( explode ( "=",
                               exec ( "/usr/bin/openssl x509 -in $cert_file -noout -enddate" ),
                                      2 )[ 1 ] );

  echo "<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr><th>", _ ( "Feld" ), "</th><th>", _ ( "Wert" ), "</th></tr></thead><tbody>";

  echo "<tr><td>", _ ( "Erzeugt" ), "</td><td>", date ( "d.m.Y, H:i:s",
                                                        filemtime ( $cert_file ) ), "</td></tr>";
  echo "<tr><td>", _ ( "Seriennummer" ), "</td><td>$serial</td></tr>";
  echo "<tr><td>", _ ( "Aussteller" ), "</td><td>$issuer</td></tr>";
  echo "<tr><td>", _ ( "Inhaber" ), "</td><td>$subject</td></tr>";
  $nb = date_parse_from_format ( "F d G:i:s Y e",
                                 $notbefore );
  $na = date_parse_from_format ( "F d G:i:s Y e",
                                 $notafter );
  echo "<tr><td>", _ ( "gültig" ), "</td><td>{$nb[ "day" ]}.{$nb[ "month" ]}.{$nb[ "year" ]} &hellip; {$na[ "day" ]}.{$na[ "month" ]}.{$na[ "year" ]}</td></tr>";

  echo "</tbody></table></div><a class=\"btn btn-primary\" href=\"downloadCertificate.php\">", _ ( "Zertifikat herunterladen" ), "</a>";
}
else
{
  alertMsg ( _ ( "Es ist kein Zertifikat vorhanden." ) );
}

echo "</div></div></div>";

require ("include/htmlend.php");

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
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/connectDB.php");

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");


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
  global $__;

  $fieldValue = array_key_exists ( $fieldID,
                                   $_REQUEST ) ? $_REQUEST[ $fieldID ] : "";
  $showValue = htmlspecialchars ( $fieldValue );

  echo "<div class=\"form-group\"><label for=\"$fieldID\" class=\"col-md-3 control-label\">$fieldText</label>";
  echo "<div class=\"col-md-9\"><input type=\"text\" class=\"form-control\" name=\"$fieldID\" id=\"$fieldID\" placeholder=\"$placeHolder\" value=\"$showValue\"></div></div>";

  if ( array_key_exists ( $__[ "manageCertificate" ][ "params" ] [ "create" ],
                          $_REQUEST ) && $fieldValue != "" && preg_match ( "/=|\//",
                                                                           $fieldValue ) )
  {
    showErrorMessage ( _ ( "\"$showValue\" ist kein gültiger Wert für $fieldText (Zeichen \"=\" und \"/\" sind nicht erlaubt)" ) );
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

$errors += certificateField ( $__[ "manageCertificate" ] [ "values" ] [ "country" ],
                              _ ( "Länderkennung" ),
                                  _ ( "z.B. DE" ) );
/*
 * country value needs extra check
 */
if ( array_key_exists ( $__[ "manageCertificate" ][ "params" ] [ "create" ],
                        $_REQUEST ) && $_REQUEST[ $__[ "manageCertificate" ] [ "values" ][ "country" ] ] != "" && !preg_match ( "/^[a-z]{2}$/i",
                                                                                                                                $_REQUEST[ $__[ "manageCertificate" ] [ "values" ][ "country" ] ] ) )
{
  showErrorMessage ( "\"" . htmlspecialchars ( $_REQUEST[ $__[ "manageCertificate" ] [ "values" ][ "country" ] ] ) . "\" " . _ ( "ist kein gültiger Wert für die Länderkennung" ) );
  $errors++;
}

$errors += certificateField ( $__[ "manageCertificate" ] [ "values" ][ "state" ],
                              _ ( "Staat oder Region" ),
                                  _ ( "z.B. Hamburg" ) );

$errors += certificateField ( $__[ "manageCertificate" ] [ "values" ][ "location" ],
                              _ ( "Stadt" ),
                                  _ ( "z.B. St. Pauli" ) );

$errors += certificateField ( $__[ "manageCertificate" ] [ "values" ][ "organisation" ],
                              _ ( "Organisation" ),
                                  _ ( "z.B. $my_name" ) );

$errors += certificateField ( $__[ "manageCertificate" ] [ "values" ][ "unit" ],
                              _ ( "Organisationseinheit" ),
                                  _ ( "z.B. $__dns_server_name.$__dns_domain_name" ) );

$errors += certificateField ( $__[ "manageCertificate" ] [ "values" ][ "commonName" ],
                              _ ( "Name" ),
                                  _ ( "z.B. $my_name" ) );

$daysValid = array_key_exists ( $__[ "manageCertificate" ][ "params" ] [ "days" ],
                                $_REQUEST ) ? $_REQUEST[ $__[ "manageCertificate" ][ "params" ] [ "days" ] ] : "";
echo "<div class = \"form-group\"><label for=\"", $__[ "manageCertificate" ][ "params" ] [ "days" ], "\" class=\"col-md-3 control-label\">", _ ( "Gültigkeitsdauer in Tagen" ), "</label>";
echo "<div class=\"col-md-9\"><input type=\"number\" class=\"form-control\" name=\"", $__[ "manageCertificate" ][ "params" ] [ "days" ], "\" id=\"", $__[ "manageCertificate" ][ "params" ] [ "days" ], "\" value=\"$daysValid\"></div></div>";
if ( array_key_exists ( $__[ "manageCertificate" ][ "params" ] [ "create" ],
                        $_REQUEST ) && (!preg_match ( "/^[0-9]+$/",
                                                      $daysValid ) || $daysValid < 1 || $daysValid > 999999) )
{
  showErrorMessage ( "\"" . $daysValid . "\" " . _ ( "ist kein gültiger Wert für die Gültigkeitsdauer" ) );
  $errors++;
}

echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"", _ ( "Zertifikat erzeugen" ), "\" name=\"", $__[ "manageCertificate" ][ "params" ] [ "create" ], "\"></form></div></div></div>";

if ( array_key_exists ( $__[ "manageCertificate" ][ "params" ] [ "create" ],
                        $_REQUEST ) && !$errors )
{
  $subject = "/";
  foreach ( $__[ "manageCertificate" ] [ "values" ] as $fieldName => $fieldID )
  {
    if ( $_REQUEST[ $fieldID ] != "" )
    {
      $subject .= "$fieldID=" . $_REQUEST[ $fieldID ] . "/";
    }
  }

  // cd to /tmp first, so that .rnd file is not written to directory where script lies
  exec ( "cd /tmp; /usr/bin/openssl req -x509 -rand /dev/urandom -nodes -newkey rsa:2048 -keyout $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "keyFile" ] . " -out $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] . " -days $daysValid -subj \"$subject\"" );
}


/*
 * existing certificate
 */

echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\" role=\"tab\"><h4 class=\"panel-title\">", _ ( "Vorhandendes Zertifikat" ), "</h4></div><div class=\"panel-body\">";

if ( file_exists ( $base_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] ) )
{
  $serial = trim ( explode ( "=",
                             exec ( "/usr/bin/openssl x509 -in $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] . " -noout -serial" ),
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
                             exec ( "/usr/bin/openssl x509 -in $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] . " -noout -issuer" ),
                                    2 )[ 1 ] );
  $subject = trim ( explode ( "=",
                              exec ( "/usr/bin/openssl x509 -in $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] . " -noout -subject" ),
                                     2 )[ 1 ] );
  $notbefore = trim ( explode ( "=",
                                exec ( "/usr/bin/openssl x509 -in $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] . " -noout -startdate" ),
                                       2 )[ 1 ] );
  $notafter = trim ( explode ( "=",
                               exec ( "/usr/bin/openssl x509 -in $base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] . " -noout -enddate" ),
                                      2 )[ 1 ] );

  echo "<div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr><th>", _ ( "Feld" ), "</th><th>", _ ( "Wert" ), "</th></tr></thead><tbody>";

  echo "<tr><td>", _ ( "Erzeugt" ), "</td><td>", date ( "d.m.Y, H:i:s",
                                                        filemtime ( "$base_dir/" . $__[ "startStopRecording" ] [ "values" ] [ "certFile" ] ) ), "</td></tr>";
  echo "<tr><td>", _ ( "Seriennummer" ), "</td><td>$serial</td></tr>";
  echo "<tr><td>", _ ( "Aussteller" ), "</td><td>$issuer</td></tr>";
  echo "<tr><td>", _ ( "Inhaber" ), "</td><td>$subject</td></tr>";
  $nb = date_parse_from_format ( "F d G:i:s Y e",
                                 $notbefore );
  $na = date_parse_from_format ( "F d G:i:s Y e",
                                 $notafter );
  echo "<tr><td>", _ ( "gültig" ), "</td><td>", $nb[ "day" ], $nb[ "month" ], $nb[ "year" ], " &hellip; ", $na[ "day" ], $na[ "month" ], $na[ "year" ], "</td></tr>";

  echo "</tbody></table></div><a class=\"btn btn-primary\" href=\"downloadCertificate.php\">", _ ( "Zertifikat herunterladen" ), "</a>";
}
else
{
  showAlertMessage ( _ ( "Es ist kein Zertifikat vorhanden." ) );
}

echo "</div></div></div>";

require ("include/closeHTML.php");

<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file decode.php
 * 
 * used to decode encoded data of several flavors
 * the script tries to cover all possible combinations of encodings to possibly produce the correct decoding, thereby albeit producing a multitude of meaningless decodings
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");

$$__[ "include/openHTML" ][ "vars" ][ "title" ] = " - " . _ ( "Dekodieren" );
$$__[ "include/openHTML" ][ "vars" ][ "frame" ] = $__usetabs ? $__[ "decode" ][ "names" ][ "frame" ] : "";
include ("include/openHTML.php");

include ("include/topMenu.php");


titleAndHelp ( _ ( "Dekodieren" ),
                   _ ( "Mit dieser Hilfsfunktion können kodierte Zeichenketten erkannt und ihr Inhalt sichtbar gemacht werden.<br>Dabei werden gängige Kodierungen wie Base64 und URL-Encode berücksichtigt. Um Mehrfachkodierungen aufzulösen, werden auch deren Kombinationen ausprobiert. Dadurch werden häufig keine sinnvollen Ergebnisse angezeigt. Die richtige Dekodierung lässt sich jedoch meist schnell finden." ) );


$codeValue = array_key_exists ( $__[ "decode" ][ "params" ][ "input" ],
                                $_POST ) ? htmlspecialchars ( $_POST[ $__[ "decode" ][ "params" ][ "input" ] ] ) : "";

echo "<form method=\"post\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\" role=\"tab\"><h4 class=\"panel-title\">", _ ( "Kodierte Zeichen" ), "</h4></div><div class=\"panel-body\"><textarea class=\"form-control\" id=\"", $__[ "decode" ] [ "ids" ][ "input" ], "\" name=\"", $__[ "decode" ][ "params" ][ "input" ], "\" rows=3 style=\"resize:vertical\">$codeValue</textarea><div class=\"pull-left\"><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "decode" ][ "values" ][ "decode" ], "\"></div><div class=\"pull-right\"><button type=\"button\" class=\"btn btn-default float-right\" onclick=\"document.getElementById('", $__[ "decode" ] [ "ids" ][ "input" ], "').value='';\">&times;</button></div></div></div></div></form>";

/*
 * start with zero decoding
 */
/**
 * decodedParts holds for each decoding variant a list of strings of which some were sucessfully decoded, others not
 */
$decodedParts[ $__[ "decode" ][ "values" ] [ "codingNone" ] ] = [
  array_key_exists ( $__[ "decode" ][ "params" ][ "input" ],
                     $_POST ) ? $_POST[ $__[ "decode" ][ "params" ][ "input" ] ] : "" ];
/**
 * partIsDecoded flags for every string in the list of each decoding variant if decoding was succesful or not
 */
$partIsDecoded[ $__[ "decode" ][ "values" ] [ "codingNone" ] ] = [
  false ];
/**
 * decodingFullyProcessed will be set true if all decodings for that value were tried and none was successful
 */
$decodingFullyProcessed[ $__[ "decode" ][ "values" ] [ "codingNone" ] ] = false;
/**
 * the overall sequence of decoding variants
 */
$sequenceOfDecodings = [
  $__[ "decode" ][ "values" ] [ "codingNone" ] ];

do
{
  $previousDecodings = count ( $decodedParts );
  foreach ( $sequenceOfDecodings as $decoding )
  {
    /*
     * skip decodings whose values cannot be further decoded
     */
    if ( $decodingFullyProcessed[ $decoding ] )
    {
      continue;
    }
    /*
     * set fully processed as default (any successful decoding will change this)
     */
    $decodingFullyProcessed[ $decoding ] = true;

    /*
     * 
     * URL decoding
     * 
     */
    $urlDecoding = $__[ "decode" ][ "values" ] [ "codingURL" ] . " ($decoding)";
    $encodedString = implode ( "",
                               $decodedParts[ $decoding ] );
    $urlDecodedParts = [
      ];
    $urlPartIsDecoded = [
      ];
    $didDecode = false;

    /*
     * walk through encoded value and try to decode bits
     */
    while ( $encodedString != "" && preg_match ( "/((?:%[0-9a-f][0-9a-f])+)/i",
                                                 $encodedString,
                                                 $matches,
                                                 PREG_OFFSET_CAPTURE ) )
    {
      $didDecode = true;

      array_push ( $urlDecodedParts,
                   substr ( $encodedString,
                            0,
                            $matches[ 1 ][ 1 ] ) );
      array_push ( $urlPartIsDecoded,
                   false );

      array_push ( $urlDecodedParts,
                   urldecode ( $matches[ 1 ][ 0 ] ) );
      array_push ( $urlPartIsDecoded,
                   true );

      $encodedString = substr ( $encodedString,
                                $matches[ 1 ][ 1 ] + strlen ( $matches[ 1 ][ 0 ] ) );
    }
    array_push ( $urlDecodedParts,
                 $encodedString );
    array_push ( $urlPartIsDecoded,
                 false );

    /*
     * something was decoded and decoded value is not same as encoded value
     */
    if ( $didDecode && implode ( "",
                                 $decodedParts[ $decoding ] ) != implode ( "",
                                                                           $urlDecodedParts ) )
    {
      $decodedParts[ $urlDecoding ] = $urlDecodedParts;
      $partIsDecoded[ $urlDecoding ] = $urlPartIsDecoded;
      $decodingFullyProcessed[ $urlDecoding ] = false;
      array_push ( $sequenceOfDecodings,
                   $urlDecoding );
    }


    /*
     * 
     * Base16 (hex) decoding
     * 
     */
    $base16Decoding = $__[ "decode" ][ "values" ] [ "codingBase16" ] . " ($decoding)";
    $encodedString = implode ( "",
                               $decodedParts[ $decoding ] );
    $base16DecodedParts = [
      ];
    $base16PartIsDecoded = [
      ];
    $didDecode = false;

    while ( $encodedString != "" && preg_match ( "/(?:0x)?([0-9a-f]{2}+)/i",
                                                 $encodedString,
                                                 $matches,
                                                 PREG_OFFSET_CAPTURE ) )
    {
      $didDecode = true;
      array_push ( $base16DecodedParts,
                   substr ( $encodedString,
                            0,
                            $matches[ 1 ][ 1 ] ) );
      array_push ( $base16PartIsDecoded,
                   false );
      $decodedPart = "";
      for ( $i = 0; $i < strlen ( $matches[ 1 ][ 0 ] ); $i += 2 )
      {
        $decodedPart .= chr ( base_convert ( substr ( $matches[ 1 ][ 0 ],
                                                      $i,
                                                      2 ),
                                                      16,
                                                      10 ) );
      }
      array_push ( $base16DecodedParts,
                   $decodedPart );
      array_push ( $base16PartIsDecoded,
                   true );
      $encodedString = substr ( $encodedString,
                                $matches[ 1 ][ 1 ] + strlen ( $matches[ 1 ][ 0 ] ) );
    }
    array_push ( $base16DecodedParts,
                 $encodedString );
    array_push ( $base16PartIsDecoded,
                 false );

    if ( $didDecode && implode ( "",
                                 $decodedParts[ $decoding ] ) != implode ( "",
                                                                           $base16DecodedParts ) )
    {
      $decodedParts[ $base16Decoding ] = $base16DecodedParts;
      $partIsDecoded[ $base16Decoding ] = $base16PartIsDecoded;
      $decodingFullyProcessed[ $base16Decoding ] = false;
      array_push ( $sequenceOfDecodings,
                   $base16Decoding );
    }


    /*
     * 
     * Base64
     * 
     */
    $base64Decoding = $__[ "decode" ][ "values" ] [ "codingBase64" ] . " ($decoding)";
    $encodedString = implode ( "",
                               $decodedParts[ $decoding ] );
    $base64DecodedParts = [
      ];
    $base64PartIsDecoded = [
      ];
    $didDecode = false;
    while ( $encodedString != "" && preg_match ( "/([a-zA-Z0-9\+\/=]{4}+)/i",
                                                 $encodedString,
                                                 $matches,
                                                 PREG_OFFSET_CAPTURE ) )
    {
      array_push ( $base64DecodedParts,
                   substr ( $encodedString,
                            0,
                            $matches[ 1 ][ 1 ] ) );
      array_push ( $base64PartIsDecoded,
                   false );
      if ( ($decodedPart = base64_decode ( $matches[ 1 ][ 0 ],
                                           true )) != false )
      {
        $didDecode = true;
        array_push ( $base64DecodedParts,
                     $decodedPart );
        array_push ( $base64PartIsDecoded,
                     true );
      }
      else
      {
        array_push ( $base64DecodedParts,
                     $matches[ 1 ][ 0 ] );
        array_push ( $base64PartIsDecoded,
                     false );
      }
      $encodedString = substr ( $encodedString,
                                $matches[ 1 ][ 1 ] + strlen ( $matches[ 1 ][ 0 ] ) );
    }
    array_push ( $base64DecodedParts,
                 $encodedString );
    array_push ( $base64PartIsDecoded,
                 false );
    if ( $didDecode && implode ( "",
                                 $decodedParts[ $decoding ] ) != implode ( "",
                                                                           $base64DecodedParts ) )
    {
      $decodedParts[ $base64Decoding ] = $base64DecodedParts;
      $partIsDecoded[ $base64Decoding ] = $base64PartIsDecoded;
      $decodingFullyProcessed[ $base64Decoding ] = false;
      array_push ( $sequenceOfDecodings,
                   $base64Decoding );
    }


    /*
     * 
     * Unixtime
     * 
     */
    $unixtimeDecoding = $__[ "decode" ][ "values" ] [ "codingUnixtime" ] . " ($decoding)";
    $encodedString = implode ( "",
                               $decodedParts[ $decoding ] );
    $unixtimeDecodedParts = [
      ];
    $unixtimePartIsDecoded = [
      ];
    $didDecode = false;
    while ( $encodedString != "" && preg_match ( "/([0-9]+)/",
                                                 $encodedString,
                                                 $matches,
                                                 PREG_OFFSET_CAPTURE ) )
    {
      array_push ( $unixtimeDecodedParts,
                   substr ( $encodedString,
                            0,
                            $matches[ 1 ][ 1 ] ) );
      array_push ( $unixtimePartIsDecoded,
                   false );

      $minimumEpochValue = strtotime ( "2000-01-01 00:00:00" );
      $maximumEpochValue = 2147483648;
      $result = "";

      /*
       * try integral seconds
       */
      if ( $minimumEpochValue <= $matches[ 1 ][ 0 ] && $matches[ 1 ][ 0 ] <= $maximumEpochValue )
      {
        $result = strftime ( "%d.%m.%Y %T",
                             $matches[ 1 ][ 0 ] );
      }
      /*
       * try millisconds
       */
      else if ( $minimumEpochValue <= $matches[ 1 ][ 0 ] / 1000 && $matches[ 1 ][ 0 ] / 1000 <= $maximumEpochValue )
      {
        $result = strftime ( "%d.%m.%Y %T",
                             $matches[ 1 ][ 0 ] / 1000 ) . "," . $matches[ 1 ][ 0 ] % 1000;
      }
      /*
       * try microseconds
       */
      else if ( $minimumEpochValue <= $matches[ 1 ][ 0 ] / 1000000 && $matches[ 1 ][ 0 ] / 1000000 <= $maximumEpochValue )
      {
        $result = strftime ( "%d.%m.%Y %T",
                             $matches[ 1 ][ 0 ] / 1000000 );
      }

      if ( $result != "" )
      {
        array_push ( $unixtimeDecodedParts,
                     $result );
        array_push ( $unixtimePartIsDecoded,
                     true );
        $didDecode = true;
      }
      else
      {
        array_push ( $unixtimeDecodedParts,
                     $matches[ 1 ][ 0 ] );
        array_push ( $unixtimePartIsDecoded,
                     false );
      }
      $encodedString = substr ( $encodedString,
                                $matches[ 1 ][ 1 ] + strlen ( $matches[ 1 ][ 0 ] ) );
    }
    array_push ( $unixtimeDecodedParts,
                 $encodedString );
    array_push ( $unixtimePartIsDecoded,
                 false );
    if ( $didDecode && implode ( "",
                                 $decodedParts[ $decoding ] ) != implode ( "",
                                                                           $unixtimeDecodedParts ) )
    {
      $decodedParts[ $unixtimeDecoding ] = $unixtimeDecodedParts;
      $partIsDecoded[ $unixtimeDecoding ] = $unixtimePartIsDecoded;
      $decodingFullyProcessed[ $unixtimeDecoding ] = true;
      array_push ( $sequenceOfDecodings,
                   $unixtimeDecoding );
    }


    /*
     * 
     * MIME header
     * 
     */
    $mimeDecoding = $__[ "decode" ][ "values" ] [ "codingMIME" ] . " ($decoding)";
    $encodedString = implode ( "",
                               $decodedParts[ $decoding ] );
    $mimeDecodedParts = [
      ];
    $mimePartIsDecoded = [
      ];
    $didDecode = false;
    while ( $encodedString != "" && preg_match ( "/(=\?.*\?=)/",
                                                 $encodedString,
                                                 $matches,
                                                 PREG_OFFSET_CAPTURE ) )
    {
      array_push ( $mimeDecodedParts,
                   substr ( $encodedString,
                            0,
                            $matches[ 1 ][ 1 ] ) );
      array_push ( $mimePartIsDecoded,
                   false );
      if ( ($mime = iconv_mime_decode ( $matches[ 1 ][ 0 ],
                                        1,
                                        "UTF-8" )) != false )
      {
        array_push ( $mimeDecodedParts,
                     $mime );
        array_push ( $mimePartIsDecoded,
                     true );
        $didDecode = true;
      }
      else
      {
        array_push ( $mimeDecodedParts,
                     $matches[ 1 ][ 0 ] );
        array_push ( $mimePartIsDecoded,
                     false );
      }
      $encodedString = substr ( $encodedString,
                                $matches[ 1 ][ 1 ] + strlen ( $matches[ 1 ][ 0 ] ) );
    }
    array_push ( $mimeDecodedParts,
                 $encodedString );
    array_push ( $mimePartIsDecoded,
                 false );
    if ( $didDecode && implode ( "",
                                 $decodedParts[ $decoding ] ) != implode ( "",
                                                                           $mimeDecodedParts ) )
    {
      $decodedParts[ $mimeDecoding ] = $mimeDecodedParts;
      $partIsDecoded[ $mimeDecoding ] = $mimePartIsDecoded;
      $decodingFullyProcessed[ $mimeDecoding ] = true;
      array_push ( $sequenceOfDecodings,
                   $mimeDecoding );
    }
  }
}
/*
 * continue to apply decodings if in this loop cycle at least one decoding was added
 */
while ( count ( $decodedParts ) != $previousDecodings );

/*
 * uncomment to exclude zero decoding from table
 * unset ($pieces["Wert"]);
 */


/*
 * there are decodings (which should always be true if zero decoding is not unset)
 */
if ( count ( $decodedParts ) )
{
  echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\" role=\"tab\"><h4 class=\"panel-title\">", _ ( "Mögliche Kodierungen" ), "</h4></div><div class=\"panel-body\"><div class=\"table-responsive\"><table class=\"table table-hover\"><thead><tr>";
  echo "<th>", _ ( "Kodierung" ), "</th>";
  echo "<th>", _ ( "Dekodierte Zeichen" ), "</th></tr></thead><tbody>";

  foreach ( $sequenceOfDecodings as $decoding )
  {
    $decodingRow = "<tr><td>$decoding</td><td class=\"break\"><pre>";
    $lengthAllParts = 0;
    while ( $decodedParts[ $decoding ] )
    {
      $part = array_shift ( $decodedParts[ $decoding ] );
      $isDecoded = array_shift ( $partIsDecoded[ $decoding ] );
      $lengthAllParts += strlen ( $part );
      $decodingRow .= $isDecoded ? "<span class=\"highlight\">" : "";
      $decodingRow .= mb_convert_encoding ( $part,
                                            "UTF-8",
                                            mb_detect_encoding ( $part,
                                                                 "UTF-8, ISO-8859-1, ISO-8859-15",
                                                                 true ) );
      $decodingRow .= $isDecoded ? "</span>" : "";
    }
    $decodingRow .= "</pre></td></tr>";
    if ( $lengthAllParts )
    {
      echo $decodingRow;
    }
  }

  echo "</tbody></table></div></div></div></div>";
}

include ("include/closeHTML.php");

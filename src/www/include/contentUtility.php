<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/contentUtility.php
 * 
 * common definitions needed for all scripts displaying content
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
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * show some content in different viewing modes as appropriate
 * 
 * @global int $markCounter counter needed by callback function in matching
 * @global string $contentName name needed by callback function
 * @param string $uniqueIdentifier identifier for this content (must be unique within a web page)
 * @param string $content content to show
 * @param array $properties list of property strings
 * @param string $mime mime type of the content
 */
function showContent ( $uniqueIdentifier,
                       $content,
                       $properties,
                       $mime = "" )
{
  global $markCounter, $contentName, $__;

  $contentName = "_$uniqueIdentifier";
  $markPattern = "|[&<>]|([^[:print:]|^[:space:]])";

  /*
   * MIME types that can be pretty printed (mimestring => prettyprinter)
   */
  $prettyPrintableMimes = array (
    "html"       => "html",
    "xml"        => "xml",
    "css"        => "css",
    "protobuf"   => "protobuf",
    "javascript" => "javascript",
    "json"       => "javascript" );

  /*
   * can we pretty print this content?
   */
  $prettyPrinter = "";
  foreach ( $prettyPrintableMimes as $mimepart => $style )
  {
    if ( stristr ( $mime,
                   $mimepart ) )
    {
      $prettyPrinter = $style;
    }
  }

  /*
   * utf encode if necessary
   */
  if ( !mb_check_encoding ( $content,
                            "UTF-8" ) )
  {
    $content = utf8_encode ( $content );
  }

  /*
   * unmarked content: just substitute nonprintable chars
   */
  $contentUnmarked = preg_replace_callback ( "/[^[:print:]|^[:space:]]/u",
                                             function ($matchesArray)
  {
    global $__;
    return $__[ "include/contentUtility" ][ "values" ][ "nonprintableCharacterSubstitute" ];
  },
                                             htmlspecialchars ( $content ) );

  /*
   * content with words marked: add css class to everything that counts as a word
   */
  $markCounter = 0;
  $wordPattern = "(?:(?<=\PL)|(?<=\PN)|(?<=^))(?:\pL|[-_]){2,}(?!\pN)(?!\pL)(?![-_])";
  $contentMarkedWords = preg_replace_callback ( "/$wordPattern$markPattern/u",
                                                "highLighter",
                                                $content );
  $wordsMarked = $markCounter;

  /*
   * content with numbers marked: add css class to everything that counts as a number
   */
  $markCounter = 0;
  $numberPattern = "(?<=\PL)(?<=\P{Nd})\p{Nd}{2,}(?=\PL)(?=\P{Nd})";
  $contentMarkedNumber = preg_replace_callback ( "/$numberPattern$markPattern/u",
                                                 "highLighter",
                                                 $content );
  $numbersMarked = $markCounter;

  /*
   * content with URLs marked: add css class to everything that counts as a URL
   */
  $urlPattern = "(?i)(?:[a-z](?:[a-z0-9+-.])*:\/\/(?:[^\s'\"\\\\<>])+)(?-i)";
  $markCounter = 0;
  $contentMarkedUrl = preg_replace_callback ( "/$urlPattern$markPattern/u",
                                              "highLighterURL",
                                              $content );
  $urlsMarked = $markCounter;

  /*
   * content with email addresses marked: add css class to everything that counts as an email address
   */
  $emailPattern = "(?i)[A-Z0-9._%+-]{1,64}@(?:[A-Z0-9-]{1,63}\.){1,125}[A-Z]{2,63}(?-i)";
  $markCounter = 0;
  $contentMarkedEmail = preg_replace_callback ( "/$emailPattern$markPattern/u",
                                                "highLighter",
                                                $content );
  $emailsMarked = $markCounter;

  /*
   * content with device properties marked: add css class to everything that is a device property
   */
  $propertiesMarked = 0;
  $propertiesPattern = "";

  /*
   * build pattern by or-ing all properties
   */
  foreach ( $properties as $propertyName => $propertyString )
  {
    $propertiesPattern .= ($propertiesPattern == "" ? "" : "|") . preg_quote ( $propertyString,
                                                                               "/" );
  }

  if ( $propertiesPattern != "" )
  {
    $markCounter = 0;
    $contentMarkedProperties = preg_replace_callback ( "/(?i)(?:$propertiesPattern)(?-i)$markPattern/u",
                                                       "highLighter",
                                                       $content );
    $propertiesMarked = $markCounter;
  }

  /*
   * if anything was marked or content is pretty printable, we need some buttons to switch viewing modes
   */
  if ( $wordsMarked || $numbersMarked || $urlsMarked || $emailsMarked || $propertiesMarked || $prettyPrinter != "" )
  {
    echo "<p>";

    /*
     * remove all highlights
     */
    viewingModeButton ( $contentName,
                        $__[ "include/contentUtility" ] [ "ids" ] [ "unmarkedPrefix" ],
                        _ ( "Nichts hervorheben" ),
                            "<i class=\"fa fa-ban\"></i>" );

    /*
     * show pretty printed form
     */
    if ( $prettyPrinter != "" )
    {
      viewingModeButton ( $contentName,
                          $__[ "include/contentUtility" ] [ "ids" ] [ "prettyPrintPrefix" ],
                          _ ( "Syntax hervorheben" ),
                              "<i class=\"fa fa-paint-brush\"></i>" );
    }

    /*
     * highlight words
     */
    if ( $wordsMarked )
    {
      viewingModeButton ( $contentName,
                          $__[ "include/contentUtility" ] [ "ids" ] [ "wordsMarkedPrefix" ],
                          $wordsMarked . _ ( " Wörter hervorheben" ),
                                             "a .. Z" );
    }

    /*
     * highlight numbers
     */
    if ( $numbersMarked )
    {
      viewingModeButton ( $contentName,
                          $__[ "include/contentUtility" ] [ "ids" ] [ "numbersMarkedPrefix" ],
                          $numbersMarked . _ ( " Zahlen hervorheben" ),
                                               "0 .. 9" );
    }

    /*
     * highlight URLs
     */
    if ( $urlsMarked )
    {
      viewingModeButton ( $contentName,
                          $__[ "include/contentUtility" ] [ "ids" ] [ "urlsMarkedPrefix" ],
                          $urlsMarked . _ ( " Webadressen hervorheben" ),
                                            ": //" );
    }

    /*
     * highlight email addresses
     */
    if ( $emailsMarked )
    {
      viewingModeButton ( $contentName,
                          $__[ "include/contentUtility" ] [ "ids" ] [ "emailAddressesMarkedPrefix" ],
                          $emailsMarked . _ ( " Mailadressen hervorheben" ),
                                              "@" );
    }

    /*
     * highlight device properties
     */
    if ( $propertiesMarked )
    {
      viewingModeButton ( $contentName,
                          $__[ "include/contentUtility" ] [ "ids" ] [ "propertiesMarkedPrefix" ],
                          $propertiesMarked . _ ( " Geräteeigenschaften hervorheben" ),
                                                  "X = abc" );
    }

    echo "</p>";
  }


  /*
   * insert unmarked content
   */
  insertContent ( $contentName,
                  $__[ "include/contentUtility" ] [ "ids" ] [ "unmarkedPrefix" ],
                  $contentUnmarked );

  /*
   * insert unmarked content with pretty printer on
   */
  if ( $prettyPrinter != "" )
  {
    insertContent ( $contentName,
                    $__[ "include/contentUtility" ] [ "ids" ] [ "prettyPrintPrefix" ],
                    $contentUnmarked,
                    " class=\"syntax $prettyPrinter\" " );
  }

  /*
   * insert content with words marked
   */
  if ( $wordsMarked )
  {
    insertContent ( $contentName,
                    $__[ "include/contentUtility" ] [ "ids" ] [ "wordsMarkedPrefix" ],
                    $contentMarkedWords );
  }

  /*
   * insert content with numbers marked
   */
  if ( $numbersMarked )
  {
    insertContent ( $contentName,
                    $__[ "include/contentUtility" ] [ "ids" ] [ "numbersMarkedPrefix" ],
                    $contentMarkedNumber );
  }

  /*
   * insert content with URLs marked
   */
  if ( $urlsMarked )
  {
    insertContent ( $contentName,
                    $__[ "include/contentUtility" ] [ "ids" ] [ "urlsMarkedPrefix" ],
                    $contentMarkedUrl );
  }

  /*
   * insert content with email addresses marked
   */
  if ( $emailsMarked )
  {
    insertContent ( $contentName,
                    $__[ "include/contentUtility" ] [ "ids" ] [ "emailAddressesMarkedPrefix" ],
                    $contentMarkedEmail );
  }

  /*
   * insert content with properties marked
   */
  if ( $propertiesMarked )
  {
    insertContent ( $contentName,
                    $__[ "include/contentUtility" ] [ "ids" ] [ "propertiesMarkedPrefix" ],
                    $contentMarkedProperties );
  }

  /*
   * script implementing style changes on view button clicks
   */
  echo "<script src=\"js/jquery-syntax/jquery.syntax.min.js\" type=\"text/javascript\"></script>";
  echo "<script type=\"text/javascript\"> jQuery ( function($) { $.syntax ( { blockLayout: \"plain\", tabWidth: 8 } ); } ); ";
  echo "function unstyle$contentName() { while (styleSheet$contentName.cssRules.length) { styleSheet$contentName.deleteRule(0); }} ";
  echo "function $contentName(viewMode) { unstyle$contentName(); styleSheet$contentName.insertRule (\".$contentName { font-weight: bold; color: #ff0000; background-color: #ffff00; }\", 0); for (i=0; i<viewModes$contentName.length; i++) { styleSheet$contentName.insertRule (\"#\" + viewModes", $contentName, "[i]  + \" { display: \" + (viewModes", $contentName, "[i]==viewMode? \"inline\":\"none\") + \"; }\", 0); } ";
  echo "for (i=0; i<viewModes$contentName.length; i++) { var btn = document.getElementById(\"", $__[ "include/contentUtility" ] [ "ids" ] [ "buttonPrefix" ], "\"+viewModes", $contentName, "[i]); if (btn != null) { if (viewModes", $contentName, "[i]==viewMode) { btn.classList.add (\"active\"); } else { btn.classList.remove (\"active\"); } } } } ";
  echo "var style = document.createElement(\"style\"); style.type = \"text/css\"; document.head.appendChild(style); var styleSheet$contentName = style.sheet; ";
  echo "var viewModes$contentName = [\"", $__[ "include/contentUtility" ] [ "ids" ] [ "unmarkedPrefix" ], "$contentName\", \"", $__[ "include/contentUtility" ] [ "ids" ] [ "prettyPrintPrefix" ], "$contentName\", \"", $__[ "include/contentUtility" ] [ "ids" ] [ "wordsMarkedPrefix" ], "$contentName\", \"", $__[ "include/contentUtility" ] [ "ids" ] [ "numbersMarkedPrefix" ], "$contentName\", \"", $__[ "include/contentUtility" ] [ "ids" ] [ "urlsMarkedPrefix" ], "$contentName\", \"", $__[ "include/contentUtility" ] [ "ids" ] [ "emailAddressesMarkedPrefix" ], "$contentName\", \"", $__[ "include/contentUtility" ] [ "ids" ] [ "propertiesMarkedPrefix" ], "$contentName\"]; ";
  echo "$contentName (\"", $__[ "include/contentUtility" ] [ "ids" ] [ "unmarkedPrefix" ], "$contentName\"); </script>";
}


/**
 * insert a button to change the viewing mode of some content
 * 
 * @param string $uniqueName  identifier for the contentt hat this button refers to
 * @param string $viewingMode viewing mode name
 * @param string $buttonTitle html title of the button
 * @param string $buttonText text to display on the button
 */
function viewingModeButton ( $uniqueName,
                             $viewingMode,
                             $buttonTitle,
                             $buttonText )
{
  global $__;

  echo "<span id=\"", $__[ "include/contentUtility" ] [ "ids" ] [ "buttonPrefix" ], $viewingMode, $uniqueName, "\" class=\"btn btn-xs btn-info\" style=\"margin-right:5px\" onclick=\"$uniqueName ('$viewingMode$uniqueName')\" title=\"$buttonTitle\">$buttonText</span>";
}


/**
 * insert some content in a div
 * 
 * @param string $name
 * @param string $namePrefix prefix to make id unique
 * @param string $content content to display
 * @param string $extraClass additional style class
 */
function insertContent ( $name,
                         $namePrefix,
                         $content,
                         $extraClass = "" )
{
  echo "<div id=\"$namePrefix$name\"><pre$extraClass>$content</pre></div>";
}


/**
 * callback function for preg_replace_callback that inserts css tags used to highlight the matched string
 * 
 * @global type $markCounter
 * @global type $contentName
 * @global type $__
 * @param array $matchesArray result of the regexp match that uses this callback
 * @param boolean $isURL match pattern is of type URL
 * @return string substitution text
 */
function highLighter ( $matchesArray,
                       $isURL = false )
{
  global $markCounter, $contentName, $__;

  /*
   * if nonprintable char found, return substitution text
   */
  if ( array_key_exists ( 1,
                          $matchesArray ) && $matchesArray[ 1 ] != "" )
  {
    return $__[ "include/contentUtility" ][ "values" ][ "nonprintableCharacterSubstitute" ];
  }

  /*
   * if html special char is found, return escaped
   */
  switch ( $matchesArray[ 0 ] )
  {
    case "<": return "&lt;";
    case ">": return "&gt;";
    case "&": return "&amp;";
  }

  /*
   * count the highlighting
   */
  $markCounter++;

  if ( $isURL )
  {
    /*
     * change to link that opens in new window / frame
     */
    return "<a href=\"" . $matchesArray[ 0 ] . "\" target=\"_blank\"><span class=\"$contentName\" style=\"border-bottom:1px solid black;\">" . $matchesArray[ 0 ] . "</span></a>";
  }
  else
  {
    /*
     * add a highlight span
     */
    return "<span class=\"$contentName\">" . $matchesArray[ 0 ] . "</span>";
  }
}


/**
 * callback function like highLighter but for URLs
 */
function highLighterURL ( $matchesArray )
{
  return highLighter ( $matchesArray,
                       true );
}


/**
 * if only partial content was shown, make the rest available via an extra user control and reload it on click
 * 
 * @param int $showLength number of chars shown so far
 * @param int $totalLength total number of chars in content
 * @param string $uniqueIdentifier identifier for this content (must be unique within a web page and correspond to value in showContent)
 * @param string $contentID html id of the element to fill content in
 * @param int $key key to content (if positive) or connection (if negative) table
 */
function loadFullContent ( $showLength,
                           $totalLength,
                           $uniqueIdentifier,
                           $contentID,
                           $key )
{
  global $__;

  echo "<div id=\"", $__[ "include/contentUtility" ] [ "ids" ] [ "loadAlertPrefix" ], $contentID, "\">";
  showAlertMessage ( "<button type=\"button\" class=\"btn btn-link\" onclick=\"loadFullContent('$contentID');\" style=\"text-align:left\">" .
    _ ( "Es werden nur die ersten $showLength Zeichen angezeigt. Bitte hier klicken, um die gesamten " ) . readableByteSize ( $totalLength ) . _ ( " anzuzeigen." ) . ($totalLength < $__[ "include/contentUtility" ][ "values" ][ "hugeContentLength" ] ? "" : (" <br><strong>" . _ ( "Es handelt sich um eine große Datenmenge. Das Nachladen kann erhebliche Zeit dauern!" ) . "</strong>")) . "</button>" );
  echo "</div>";

  echo "<div id=\"", $__[ "include/contentUtility" ] [ "ids" ] [ "loadProgressPrefix" ], $contentID, "\" style=\"display: none\">";
  showWaitMessage ( _ ( "Die restlichen " ) . readableByteSize ( $totalLength - $showLength ) . _ ( " werden nachgeladen ..." ) );
  echo "</div>";

  /*
   * script to load full content
   */
  echo "<script> function loadFullContent(contentID) { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { document.getElementById(contentID).innerHTML = xmlhttp.responseText; jQuery ( function($) { $.syntax ( { blockLayout: \"plain\", tabWidth: 8 } ); } ); } }; document.getElementById(\"", $__[ "include/contentUtility" ] [ "ids" ] [ "loadAlertPrefix" ], "\"+contentID).style.display=\"none\"; document.getElementById(\"", $__[ "include/contentUtility" ] [ "ids" ] [ "loadProgressPrefix" ], "\"+contentID).style.display=\"block\"; xmlhttp.open(\"GET\",\"include/showFullContent.php?", $__[ "include/showFullContent" ][ "params" ][ "index" ], "=$uniqueIdentifier&", $__[ "include/showFullContent" ][ "params" ][ "key" ], "=$key\",true); xmlhttp.send(); }</script>";
}


/**
 * make large byte sizes more readable
 * 
 * @param int $bytes bytes to be made human readable
 * @return string bytes in kilo, mega, giga etc 
 */
function readableByteSize ( $bytes )
{
  if ( $bytes < 1024 )
  {
    return $bytes . _ ( " Bytes" );
  }
  if ( $bytes < 1024 * 1024 )
  {
    return sprintf ( "%.1f",
                     $bytes / 1024 ) . _ ( " kB" );
  }
  if ( $bytes < 1024 * 1024 * 1024 )
  {
    return sprintf ( "%.1f",
                     $bytes / 1024 / 1024 ) . _ ( " MB" );
  }
  return sprintf ( "%.1f",
                   $bytes / 1024 / 1024 / 1024 ) . _ ( " GB" );
}


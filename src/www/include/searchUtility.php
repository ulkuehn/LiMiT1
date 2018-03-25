<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/searchUtility.php
 * 
 * common definitions needed for all search related scripts
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * find 'needle' in 'haystack' starting from 'position'
 * 
 * @param string $haystack
 * @param string $needle
 * @param boolean $isRegexp
 * @param boolean $isCaseSensitive
 * @param int $position
 * 
 * @return array(string,boolean,int): html text, true if more results, position where to continue search for more results
 */
function highlightSearchResult ( $haystack,
                                 $needle,
                                 $isRegexp,
                                 $isCaseSensitive,
                                 $position )
{
  global $__;

  /*
   * number of chars left and right of search result in folded table view
   */
  $foldedCharsToShow = 30;
  /*
   * number of chars left and right of search result in unfolded table view
   */
  $unfoldedCharsToShow = 200;

  /*
   * if doing a search with regexp qualifiers, escape only certain characters
   */
  if ( $isRegexp )
  {
    $needle = str_replace ( "(",
                            "(?:",
                            $needle );
    $needle = str_replace ( "/",
                            "\\/",
                            $needle );
  }
  /*
   * if doing a search without regexp qualifiers, do a full regexp quote
   */
  else
  {
    $needle = preg_quote ( $needle,
                           "/" );
  }

  /*
   * haystack up to position
   */
  $haystackPrefix = substr ( $haystack,
                             0,
                             $position );
  /*
   * cut off prefix
   */
  $haystack = substr ( $haystack,
                       $position );

  /*
   * if necessary, do utf encoding
   */
  if ( !mb_check_encoding ( $haystack,
                            "UTF-8" ) )
  {
    $haystack = utf8_encode ( $haystack );
  }

  /*
   * needle not found: return empty values
   */
  if ( !preg_match ( "/$needle/Uu" . ($isCaseSensitive ? "" : "i"),
                     $haystack,
                     $match,
                     PREG_OFFSET_CAPTURE ) )
  {
    return [
      "",
      false,
      0 ];
  }

  $leftOfResult = $haystackPrefix . substr ( $haystack,
                                             0,
                                             $match[ 0 ][ 1 ] );
  $rightOfResult = substr ( $haystack,
                            $match[ 0 ][ 1 ] + strlen ( $match[ 0 ][ 0 ] ) );

  if ( strlen ( $leftOfResult ) > $foldedCharsToShow )
  {
    $foldedResult = $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . substituteNonprintableCharacters ( htmlSave ( substr ( $leftOfResult,
                                                                                                                                           -$foldedCharsToShow ) ) );
  }
  else
  {
    $foldedResult = substituteNonprintableCharacters ( htmlSave ( $leftOfResult ) );
  }

  if ( strlen ( $leftOfResult ) > $unfoldedCharsToShow )
  {
    $unfoldedResult = $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . substituteNonprintableCharacters ( htmlSave ( substr ( $leftOfResult,
                                                                                                                                             -$unfoldedCharsToShow ) ) );
  }
  else
  {
    $unfoldedResult = substituteNonprintableCharacters ( htmlSave ( $leftOfResult ) );
  }

  $foldedResult .= "<span class=\"highlight\">" . substituteNonprintableCharacters ( htmlSave ( $match[ 0 ][ 0 ] ) ) . "</span>";
  $unfoldedResult .= "<span class=\"highlight\">" . substituteNonprintableCharacters ( htmlSave ( $match[ 0 ][ 0 ] ) ) . "</span>";

  if ( strlen ( $rightOfResult ) > $foldedCharsToShow )
  {
    $foldedResult .= substituteNonprintableCharacters ( htmlSave ( substr ( $rightOfResult,
                                                                            0,
                                                                            $foldedCharsToShow ) ) ) . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ];
  }
  else
  {
    $foldedResult .= substituteNonprintableCharacters ( htmlSave ( $rightOfResult ) );
  }
  if ( strlen ( $rightOfResult ) > $unfoldedCharsToShow )
  {
    $unfoldedResult .= substituteNonprintableCharacters ( htmlSave ( substr ( $rightOfResult,
                                                                              0,
                                                                              $unfoldedCharsToShow ) ) ) . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ];
  }
  else
  {
    $unfoldedResult .= substituteNonprintableCharacters ( htmlSave ( $rightOfResult ) );
  }

  return [
    "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $__[ "search" ][ "ids" ][ "tables" ][ "searchResult" ] . "\">$foldedResult</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $__[ "search" ][ "ids" ][ "tables" ][ "searchResult" ] . "\">$unfoldedResult</span>",
    preg_match ( "/$needle/Uu" . ($isCaseSensitive ? "" : "i"),
                 $rightOfResult ),
    $position + $match[ 0 ][ 1 ] + strlen ( $match[ 0 ][ 0 ] ) ];
}


/**
 * replace nonprintable characters in a (binary) string with a replacement character / html code
 * 
 * @param string $inputString (binary) string
 * @param bookean $isUnicode true if unicode, false if latin
 * 
 * @return string input with nonprintable chars replaced
 */
function substituteNonprintableCharacters ( $inputString,
                                            $isUnicode = false )
{
  return preg_replace_callback ( "/[^[:print:]|^[:space:]]/" . ($isUnicode ? "u" : ""),
                                 function ($matchesArray)
  {
    global $__;
    return $__[ "include/contentUtility" ][ "values" ][ "nonprintableCharacterSubstitute" ];
  },
                                 $inputString );
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * define the areas to do searches in
 * each area is composed of the following fields:
 *  - area name (singular)
 *  - area name (plural)
 *  - sql query to do actual search
 *  - sql query to do search count
 */
$searchAreas0 = [
  $__[ "include/searchUtility" ][ "values" ][ "httpRequests" ]
  =>
  [
    _ ( "HTTP-Request" ),
    _ ( "HTTP-Requests" ),
    "select *, id as request, concat (methode, ' ', uri, ' ', version) as inhalt from request having inhalt regexp ?",
    "select count(*) from request where binary concat (methode, ' ', uri, ' ', version) regexp ?"
  ],
  $__[ "include/searchUtility" ][ "values" ][ "httpResponses" ]
  =>
  [
    _ ( "HTTP-Response" ),
    _ ( "HTTP-Responses" ),
    "select *, concat (version, ' ', status, ' ', statustext) as inhalt from response having inhalt regexp ?",
    "select count(*) from response where binary concat (methode, ' ', uri, ' ', version) regexp ?"
  ],
  $__[ "include/searchUtility" ][ "values" ][ "httpHeader" ]
    =>
  [
    _ ( "HTTP-Header" ),
    _ ( "HTTP-Header" ),
    "select *, concat(feld, ' ', wert) as inhalt from header having inhalt regexp ?",
    "select count(*) from header where binary concat(feld, ' ', wert) regexp ?"
  ],
  $__[ "include/searchUtility" ][ "values" ][ "content" ]
       =>
  [
    _ ( "Inhalt" ),
    _ ( "Inhalte" ),
    "select * from inhalt where (convert(inhalt using utf8) regexp convert(? using utf8) or convert(inhalt using latin1) regexp convert(? using latin1))",
    "select count(*) from inhalt where (binary convert(inhalt using utf8) regexp convert(? using utf8) or binary convert(inhalt using latin1) regexp convert(? using latin1))"
  ]
];

/**
 * area =>
 * [0] area name (singular)
 * [1] area name (plural)
 * [2] additional sql query to limit search to specific recording
 * [3] list of search items:
 *      item name =>
 *      [0] explanation / example string
 *      [1] 0=search sql with 1 placeholder, 1=search sql with 2 placeholders (content searches)
 *      [2] sql query to do actual search
 *      [3] sql query to do search count
 */
$searchAreas = [
  /*
   * requests
   */
  $__[ "include/searchUtility" ][ "values" ][ "httpRequests" ]
  =>
  [
    _ ( "HTTP-Request" ),
    _ ( "HTTP-Requests" ),
    " and request.aufzeichnung=?",
    [
      _ ( "URL" )     => [
        "z.B. \"/layout/css/mystyle.css\"",
        0,
        "select *, id as request, uri as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from request having " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " regexp ?",
        "select count(*) from request where uri regexp ?" ],
      _ ( "MIME" )    => [
        "z.B. \"application/x-www-form-urlencoded\"",
        0,
        "select *, id as request, concat (mime,' ',mimeadd) as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from request having " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " regexp ?",
        "select count(*) from request where concat (mime,' ',mimeadd) regexp ?" ],
      _ ( "Methode" ) => [
        "z.B. \"GET\"",
        0,
        "select *, id as request, methode as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from request having " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " regexp ?",
        "select count(*) from request where methode regexp ?" ],
      _ ( "Header" )  => [
        "z.B. \"Accept-Language: de-DE,en-US;q=0.9\"",
        0,
        "select *, request.id as request, concat(header.feld,': ',header.wert) as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from request,header having header.request=request.id and header.response=0 and " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " regexp ?",
        "select count(distinct header.id) from request,header where header.request=request.id and header.response=0 and concat(header.feld,': ',header.wert) regexp ?" ],
      _ ( "Inhalt" )  => [
        "sämtliche im Request-Body übertragenen Daten",
        1,
        "select *, request.id as request, inhalt.inhalt as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from request,inhalt having convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using utf8) regexp convert(? using utf8) or convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using latin1) regexp convert(? using latin1) where request.inhalt=inhalt.id",
        "select count(distinct inhalt.id) from request,inhalt where request.inhalt=inhalt.id and ( convert(inhalt.inhalt using utf8) regexp convert(? using utf8) or convert(inhalt.inhalt using latin1) regexp convert(? using latin1) )" ]
    ] ],
  /*
   * responses
   */
  $__[ "include/searchUtility" ][ "values" ][ "httpResponses" ]
  =>
  [
    _ ( "HTTP-Response" ),
    _ ( "HTTP-Responses" ),
    " and response.aufzeichnung=?",
    [
      _ ( "MIME" )   => [
        "z.B. \"text/html;charset=UTF-8\"",
        0,
        "select *, concat (mime,';',mimeadd) as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from response having " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " regexp ?",
        "select count(*) from response where concat (mime,';',mimeadd) regexp ?" ],
      _ ( "Status" ) => [
        "z.B. \"204 No Content\"",
        0,
        "select *, concat (status, ' ', statustext) as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from response having " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " regexp ?",
        "select count(*) from response where concat (status, ' ', statustext) regexp ?" ],
      _ ( "Header" ) => [
        "z.B. \"X-Robots-Tag: noindex, nofollow, noarchive\"",
        0,
        "select *, concat(header.feld,': ',header.wert) as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from response,header having header.request=response.request and header.response=1 and " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " regexp ?",
        "select count(distinct header.id) from response,header where header.request=response.request and header.response=1 and concat(header.feld,': ',header.wert) regexp ?" ],
      _ ( "Inhalt" ) => [
        "sämtliche im Response-Body übertragenen Daten",
        1,
        "select *, inhalt.inhalt as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from response,inhalt having response.inhalt=inhalt.id and ( convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using utf8) regexp convert(? using utf8) or convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using latin1) regexp convert(? using latin1) )",
        "select count(distinct inhalt.id) from response,inhalt where response.inhalt=inhalt.id and ( convert(inhalt.inhalt using utf8) regexp convert(? using utf8) or convert(inhalt.inhalt using latin1) regexp convert(? using latin1) )" ]
    ] ],
  /*
   * non http content
   */
  $__[ "include/searchUtility" ][ "values" ][ "content" ]
       =>
  [
    _ ( "Inhalt" ),
    _ ( "Inhalte" ),
    " and aufzeichnung=?",
    [
      _ ( "TCP ohne HTTP/S" )       => [
        "sämtliche per TCP übertragenen Daten, soweit nicht im Rahmen eines http-Requests bzw. -Response",
        1,
        "select *, inhalt as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from inhalt having typ=\"tcp\" and ( convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using utf8) regexp convert(? using utf8) or convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using latin1) regexp convert(? using latin1) )",
        "select count(*) from inhalt where typ=\"tcp\" and ( convert(inhalt using utf8) regexp convert(? using utf8) or convert(inhalt using latin1) regexp convert(? using latin1) )"
      ],
      _ ( "versandte UDP-Pakete" )  => [
        "sämtliche über UDP verschickten Daten",
        1,
        "select *, inhalt as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from inhalt having typ=\"udpsend\" and ( convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using utf8) regexp convert(? using utf8) or convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using latin1) regexp convert(? using latin1) )",
        "select count(*) from inhalt where typ=\"udpsend\" and ( convert(inhalt using utf8) regexp convert(? using utf8) or convert(inhalt using latin1) regexp convert(? using latin1) )"
      ],
      _ ( "empfangene UDP-Pakete" ) => [
        "sämtliche per UDP erhaltenen Daten",
        1,
        "select *, inhalt as " . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " from inhalt having typ=\"udprcv\" and ( convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using utf8) regexp convert(? using utf8) or convert(" . $__[ "include/searchUtility" ][ "values" ][ "matchField" ] . " using latin1) regexp convert(? using latin1) )",
        "select count(*) from inhalt where typ=\"udprcv\" and ( convert(inhalt using utf8) regexp convert(? using utf8) or convert(inhalt using latin1) regexp convert(? using latin1) )"
      ] ] ]
];

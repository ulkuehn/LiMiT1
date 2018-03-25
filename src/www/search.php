<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file search.php
 * 
 * used to search recorded data for specific strings or patterns
 * search provides for the following:
 *  - simple text search (case sensitive or not)
 *  - regexp search
 *  - filter for different data categories (header, content etc)
 *  - limit to specific recordings
 *  - forward/backward between occurances of search string if multiple occurances are found in one search item  
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
require_once ("include/tableUtility.php");
require_once ("include/searchUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");

$$__[ "include/openHTML" ][ "vars" ][ "title" ] = " - " . _ ( "Suche" );
$$__[ "include/openHTML" ][ "vars" ][ "frame" ] = $__usetabs ? $__[ "search" ][ "names" ][ "frame" ] : "";
include ("include/openHTML.php");

include ("include/topMenu.php");


$searchValue = array_key_exists ( $__[ "search" ] [ "params" ] [ "search" ],
                                  $_REQUEST ) ? htmlspecialchars ( $_REQUEST[ $__[ "search" ] [ "params" ] [ "search" ] ] ) : "";

titleAndHelp ( _ ( "Freitextsuche" ),
                   _ ( "<p>Mit dieser Funktion können Zeichenketten in den aufgezeichneten Daten aufgespürt werden.<br>Die Suche bezieht sich auf verschiedene Suchfelder, die standardmäßig alle aktiviert sind. Soll nur in bestimmten Feldern gesucht werden, müssen die nicht relevanten Felder deaktiviert werden.<br>Komplexe Suchanfragen sind mit aktivierten \"Musterzeichen\" möglich. Der Suchstring wird dann als regulärer Ausdruck interpretiert. Dann haben u.a. folgende Zeichen eine besondere Bedeutung:</p>" ) . "<table class=\"table table-condensed\"><tbody>" . "<tr><td><strong>.</strong></td><td>" . _ ( "beliebiges Zeichen" ) . "</td></tr>" . "<tr><td><strong>?</strong></td><td>" . _ ( "vorheriges Zeichen kommt nicht oder einmal vor" ) . "</td></tr>" . "<tr><td><strong>*</strong></td><td>" . _ ( "vorheriges Zeichen kommt nicht, ein- oder mehrmals vor" ) . "</td></tr>" . "<tr><td><strong>+</strong></td><td>" . _ ( "vorheriges Zeichen kommt ein- oder mehrmals vor" ) . "</td></tr>" . "<tr><td><strong>^</strong></td><td>" . _ ( "Textanfang" ) . "</td></tr>" . "<tr><td><strong>$</strong></td><td>" . _ ( "Textende" ) . "</td></tr>" . "<tr><td><strong>|</strong></td><td>" . _ ( "Oder-Verknüpfung" ) . "</td></tr></tbody></table>" );

/*
 * search item
 */
echo "<form method=\"post\" class=\"form-horizontal\"><div class=\"row\" style=\"margin-bottom:20px\"><div class=\"input-group\"><span class=\"input-group-btn\"><input type=\"submit\" class=\"btn btn-primary\" value=\"", $__[ "search" ] [ "values" ][ "search" ], "\"></span><input class=\"form-control\" type=\"search\" id=\"", $__[ "search" ] [ "ids" ][ "search" ], "\" name=\"", $__[ "search" ] [ "params" ] [ "search" ], "\" value=\"", (array_key_exists ( $__[ "search" ] [ "params" ] [ "search" ],
                                                                                                                                                                                                                                                                                                                                                                                                                                                                           $_REQUEST ) ? htmlspecialchars ( $_REQUEST[ $__[ "search" ] [ "params" ] [ "search" ] ] ) : ""), "\"><span class=\"input-group-btn\"><button type=\"button\" class=\"btn btn-default\" onclick=\"document.getElementById('", $__[ "search" ] [ "ids" ][ "search" ], "').value='';\">&times;</button></span></div></div>";

echo "<div class=\"row\"><div class=\"panel-group\" id=\"", $__[ "search" ] [ "ids" ] [ "panels" ], "\" role=\"tablist\">";

/*
 * settings
 */
echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-parent=\"#", $__[ "search" ] [ "ids" ] [ "panels" ], "\" data-target=\"#settings\"><h4 class=\"panel-title\">", _ ( "Einstellungen" ), "</h4></div><div id=\"settings\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";

/*
 * recordings
 */
$selectRecordingsStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y, %H:%i') as _start from aufzeichnung order by start desc" );
$selectRecordingsStatement->execute ();
$recordings = $selectRecordingsStatement->fetchAll ();
if ( count ( $recordings ) < 2 )
{
  $showRecordings = $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ];
}
else
{
  if ( array_key_exists ( $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ],
                          $_REQUEST ) && $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ] != $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ] )
  {
    $showRecordings = $_REQUEST[ $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ] ];
  }
  else
  {
    if ( array_key_exists ( $__[ "include/filterUtility" ][ "names" ][ "cookie" ],
                            $_COOKIE ) && $_COOKIE[ $__[ "include/filterUtility" ][ "names" ][ "cookie" ] ] != $__[ "include/filterUtility" ] [ "values" ] [ "eachRecording" ] )
    {
      $showRecordings = $_COOKIE[ $__[ "include/filterUtility" ][ "names" ][ "cookie" ] ];
    }
    else
    {
      $showRecordings = $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ];
    }
  }

  echo "<select class=\"form-control\" name=\"", $__[ "include/filterUtility" ] [ "params" ] [ "showRecording" ], "\">";
  echo "<option value=\"", $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ], "\"", ($showRecordings == $__[ "include/filterUtility" ] [ "values" ] [ "allRecordings" ] ? " selected" : ""), ">", _ ( "Alle Aufzeichnungen berücksichtigen" ), "</option>";

  foreach ( $recordings as $recording )
  {
    echo "<option value=\"", $recording[ "id" ], "\"", ($showRecordings == $recording[ "id" ] ? " selected" : ""), ">", _ ( "Nur Aufzeichnung" ), ($recording[ "name" ] == "" ? "" : " &nbsp; &nbsp;" . htmlSave ( strlen ( $recording[ "name" ] ) > 50 ? substr ( $recording[ "name" ],
                                                                                                                                                                                                                                                                   0,
                                                                                                                                                                                                                                                                   47 ) . "..." : $recording[ "name" ]  ) . "&nbsp; &nbsp;"), _ ( " vom " ), $recording[ "_start" ], " ", _ ( "berücksichtigen" ), "</option>";
  }

  echo "</select>";
}

/*
 * case switch
 */
echo "<div class=\"col-md-6\"><div class=\"checkbox\"><label><input type=\"checkbox\" name=\"", $__[ "search" ][ "params" ] [ "case" ], "\"", ( count ( $_REQUEST ) == 0 || (array_key_exists ( $__[ "search" ][ "params" ] [ "case" ],
                                                                                                                                                                                                $_REQUEST ) && $_REQUEST[ $__[ "search" ][ "params" ] [ "case" ] ] == "on") ? " checked" : "" ), ">", _ ( "Groß-/Kleinschreibung" ), "</label></div></div>";

/*
 * regexp switch
 */
echo "<div class=\"col-md-6\"><div class=\"checkbox\"><label><input type=\"checkbox\" name=\"", $__[ "search" ][ "params" ] [ "regexp" ], "\"", ( array_key_exists ( $__[ "search" ][ "params" ] [ "regexp" ],
                                                                                                                                                                     $_REQUEST ) && $_REQUEST[ $__[ "search" ][ "params" ] [ "regexp" ] ] == "on" ? " checked" : "" ), ">", _ ( "Musterzeichen" ), "</label></div></div>";

echo "</div></div></div>";

/*
 * search areas
 */
$item = -1;
foreach ( $searchAreas as $searchArea => $searchAreaInfo )
{
  echo "<div class=\"panel panel-primary\"><div class=\"panel-heading panelCollapse\" role=\"tab\" data-toggle=\"collapse\" data-parent=\"#", $__[ "search" ] [ "ids" ] [ "panels" ], "\" data-target=\"#$searchArea\"><h4 class=\"panel-title\">", _ ( "Suchfelder: " ), $searchAreaInfo[ 1 ], "</h4></div><div id=\"$searchArea\" class=\"panel-collapse collapse\" role=\"tabpanel\"><div class=\"panel-body\">";

  foreach ( $searchAreaInfo[ 3 ] as $searchItem => $searches )
  {
    $item++;
    echo "<div class=\"checkbox\"><label><input type=\"checkbox\" name=\"", $__[ "search" ][ "params" ] [ "itemPrefix" ], $item, "\"", (count ( $_REQUEST ) == 0 || array_key_exists ( $__[ "search" ][ "params" ] [ "areas" ],
                                                                                                                                                                                       $_REQUEST ) || array_key_exists ( $__[ "search" ][ "params" ] [ "itemPrefix" ] . $item,
                                                                                                                                                                                                                         $_REQUEST ) ? " checked" : ""), "><strong>$searchItem</strong> (", $searches[ 0 ], ")</label></div>";
  }

  echo "</div></div></div>";
}

echo "</div></div></form>";

/*
 * script to switch to next / previous search result
 * alert('", $__[ "search" ][ "js" ] [ "searchFunctionName" ], "('+htmlID+'; '+recordingFilter+'; '+searchArea+'; '+searchIndex+'; ... pp='+previousPositions+'; ap='+actualPosition+')'); 
 */
echo "<script>function ", $__[ "search" ][ "js" ] [ "searchFunctionName" ], " (htmlID, recordingFilter, searchArea, searchIndex, whatToSearch, isRegexp, isCaseSensitive, previousPositions, actualPosition) { var xmlhttp = new XMLHttpRequest(); xmlhttp.onreadystatechange = function() { if (xmlhttp.readyState==4 && xmlhttp.status==200) { if (xmlhttp.responseText != \"\") { document.getElementById(htmlID).innerHTML = xmlhttp.responseText; } document.getElementById(htmlID).style.opacity=\"1\"; } }; document.getElementById(htmlID).style.opacity=\"0.3\"; xmlhttp.open (\"POST\",\"include/searchResult.php\",true); xmlhttp.setRequestHeader (\"Content-type\", \"application/x-www-form-urlencoded\"); xmlhttp.send (\"", $__[ "include/searchResult" ][ "params" ] [ "htmlID" ], "=\" + htmlID + \"&", $__[ "include/searchResult" ][ "params" ] [ "recordingFilter" ], "=\" + recordingFilter + \"&", $__[ "include/searchResult" ][ "params" ] [ "searchArea" ], "=\" + searchArea + \"&", $__[ "include/searchResult" ][ "params" ] [ "searchIndex" ], "=\" + searchIndex + \"&", $__[ "include/searchResult" ][ "params" ] [ "whatToSearch" ], "=\" + whatToSearch + \"&", $__[ "include/searchResult" ][ "params" ] [ "isRegexp" ], "=\" + isRegexp + \"&", $__[ "include/searchResult" ][ "params" ] [ "isCaseSensitive" ], "=\" +isCaseSensitive + \"&", $__[ "include/searchResult" ][ "params" ] [ "previousPositions" ], "=\" + previousPositions + \"&", $__[ "include/searchResult" ][ "params" ] [ "actualPosition" ], "=\" + actualPosition); }</script>";

/*
 * do actual search
 */
if ( array_key_exists ( $__[ "search" ] [ "params" ] [ "search" ],
                        $_REQUEST ) && $_REQUEST[ $__[ "search" ] [ "params" ] [ "search" ] ] != "" )
{
  /*
   * compile search string
   */
  if ( array_key_exists ( $__[ "search" ][ "params" ] [ "regexp" ],
                          $_REQUEST ) && $_REQUEST[ $__[ "search" ][ "params" ] [ "regexp" ] ] != "on" )
  {
    /*
     * if regExp switch is off, quote all special chars
     */
    $searchString = preg_quote ( $_REQUEST[ $__[ "search" ] [ "params" ] [ "search" ] ] );
  }
  else
  {
    $searchString = $_REQUEST[ $__[ "search" ] [ "params" ] [ "search" ] ];
  }

  echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\" role=\"tab\"><h4 class=\"panel-title\">", _ ( "Fundstellen" ), "</h4></div><div class=\"panel-body\">";

  $foldUnfoldButton = tableFolder ( $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ] );

  echo tableSorter ( $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ],
                     "columns: [ {orderable:false, searchable:false}" . ($showRecordings == $__[ "include/filterUtility" ][ "values" ] [ "allRecordings" ] ? ", {}" : "") . ", {}, {}, {}, {type:'num'}, {}, {orderable:false, searchable:false}, {orderable:false, searchable:false}, {orderable:false, searchable:false} ], order: [ [1,'asc'] ]" );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ], "\" class=\"table table-hover\"><thead><tr>";
  echo "<th>$foldUnfoldButton</th>";

  if ( $showRecordings == $__[ "include/filterUtility" ][ "values" ] [ "allRecordings" ] )
  {
    echo "<th>", _ ( "Aufzeichnung" ), "</th>";
  }
  echo "<th>", _ ( "Suchfeld" ), "</th>";
  echo "<th>", _ ( "Zeit" ), "</th>";
  echo "<th>", _ ( "Server" ), "</th>";
  echo "<th>", _ ( "Port" ), "</th>";
  echo "<th></th>";
  echo "<th></th>";
  echo "<th>", _ ( "Fundstelle" ), "</th>";
  echo "<th></th></tr></thead><tbody>";

  $item = -1;
  $searchID = 0;

  foreach ( $searchAreas as $area => $areaInfo )
  {
    foreach ( $areaInfo[ 3 ] as $searchItem => $searches )
    {
      $item++;

      /*
       * search not selected
       */
      if ( !array_key_exists ( $__[ "search" ][ "params" ] [ "areas" ],
                               $_REQUEST ) && !array_key_exists ( $__[ "search" ][ "params" ] [ "itemPrefix" ] . $item,
                                                                  $_REQUEST ) )
      {
        continue;
      }

      /*
       * compile sql query string and array
       */
      $searchQueryString = $searches[ 2 ];
      $searchQueryArray = array (
        $searchString );
      /*
       * for content related queries we must provide search string twice
       */
      if ( $searches[ 1 ] )
      {
        array_push ( $searchQueryArray,
                     $searchString );
      }
      /*
       * if search is limited to specific recording we must provide additional query and recording id
       */
      if ( $showRecordings != $__[ "include/filterUtility" ][ "values" ] [ "allRecordings" ] )
      {
        $searchQueryString .= $areaInfo[ 2 ];
        array_push ( $searchQueryArray,
                     $showRecordings );
      }

      /*
       * do query
       */
      $selectResultStatement = $db->prepare ( $searchQueryString );
      $selectResultStatement->execute ( $searchQueryArray );
      #echo "<p><pre>\$searchQueryString\n", var_dump ( $searchQueryString ), "</pre></p>";
      #echo "<p><pre>\$searchQueryArray\n", var_dump ( $searchQueryArray ), "</pre></p>";

      /*
       * results?
       */
      if ( $result = $selectResultStatement->fetch () )
      {
        $searchLimit = -1;
        /*
         * show results as table rows
         */
        do
        {
          $searchLimit++;
          $selectConnectionStatement = $db->prepare ( "select *, date_format(zeit,'%e.%c.%Y') as _zeitd, date_format(zeit,'%H:%i:%s') as _zeitt, inet_ntoa(ip) as _ip from verbindung where id=?" );
          $selectConnectionStatement->execute ( array (
            $result[ "verbindung" ] ) );
          $connection = $selectConnectionStatement->fetch ();

          list ($htmlResult, $moreResults, $positionToContinueSearch) = highlightSearchResult ( $result[ $__[ "include/searchUtility" ][ "values" ][ "matchField" ] ],
                                                                                                $_REQUEST[ $__[ "search" ] [ "params" ] [ "search" ] ],
                                                                                                array_key_exists ( $__[ "search" ][ "params" ] [ "regexp" ],
                                                                                                                   $_REQUEST ) && $_REQUEST[ $__[ "search" ][ "params" ] [ "regexp" ] ] == "on",
                                                                                                                   array_key_exists ( $__[ "search" ][ "params" ] [ "case" ],
                                                                                                                                      $_REQUEST ) && $_REQUEST[ $__[ "search" ][ "params" ] [ "case" ] ] == "on",
                                                                                                                                      0 );

          if ( $htmlResult != "" )
          {
            $searchID++;
            echo "<tr id=\"", $__[ "search" ] [ "ids" ][ "resultPrefix" ], $searchID, "\">";

            /*
             * button
             */
            if ( array_key_exists ( "request",
                                    $result ) )
            {
              echo "<td>", showViewButton ( "showRequest.php?" . $__[ "showRecording" ][ "params" ][ "request" ] . "=" . $result[ "request" ],
                                            $__[ "search" ] [ "titles" ][ "viewDetails" ] ), "</td>";
            }
            elseif ( array_key_exists ( "verbindung",
                                        $result ) )
            {
              echo "<td>", showViewButton ( "showConnection.php?" . $__[ "showRecording" ] [ "params" ] [ "connection" ] . "=" . $result[ "verbindung" ],
                                            $__[ "search" ] [ "titles" ][ "viewDetails" ] ), "</td>";
            }
            else
            {
              echo "<td></td>";
            }

            /*
             * recording
             */
            if ( $showRecordings == $__[ "include/filterUtility" ][ "values" ] [ "allRecordings" ] )
            {
              $selectRecordingStatement = $db->prepare ( "select *,date_format(start,'%e.%c.%Y') as _startd, date_format(start,'%H:%i') as _startt from aufzeichnung where id=?" );
              $selectRecordingStatement->execute ( array (
                $connection[ "aufzeichnung" ] ) );
              $recording = $selectRecordingStatement->fetch ();
              echo $recording[ "name" ] == "" ? "<td>" . $recording[ "_startd" ] . " <i class=\"fa fa-clock-o\"></i> " . $recording[ "_startt" ] . "</td>" : foldableTableCell ( $recording[ "name" ],
                                                                                                                                                                                 $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ] );
            }

            /*
             * search item
             */
            echo "<td>$searchItem (", $areaInfo[ 0 ], ")</td>";

            /*
             * time stamp
             */
            echo "<td>", $connection[ "_zeitd" ], " <i class=\"fa fa-clock-o\"></i> ", $connection[ "_zeitt" ] . "<!--", $connection[ "zeit" ], "--></td>";

            /*
             * server
             */
            if ( !$connection[ "host" ] )
            {
              echo ipHostinfo ( $connection[ "ip" ],
                                $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ] );
            }
            else
            {
              echo idHostinfo ( $connection[ "host" ],
                                $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ] );
            }

            /*
             * port
             */
            $srvc = getservbyport ( $connection[ "anport" ],
                                    $connection[ "typ" ] == "udp" ? "udp" : "tcp" );
            echo "<td class=\"numeric\">", ($connection[ "typ" ] == "udp" ? "udp " : ""), $connection[ "anport" ], ($srvc != "" ? " ($srvc)" : ""), "<!--", $connection[ "anport" ], "--></td>";

            /*
             * crypted?
             */
            echo "<td>", ($connection[ "typ" ] == "https" || $connection[ "typ" ] == "ssl" ? "<i class=\"fa fa-key\"></i>" : ""), "</td>";

            /*
             * previous search result button
             */
            echo "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-left\"></i></button></td>";

            /*
             * search result
             */
            echo "<td style=\"width:30% !important\" class=\"break\"", onTableToggleEvent ( $__[ "search" ] [ "ids" ] [ "tables" ] [ "searchResult" ] ), ">$htmlResult</td>";

            /*
             * next search result button
             */
            if ( !$moreResults )
            {
              echo "<td><button class=\"btn btn-info btn-xs\" style=\"visibility:hidden;\"><i class=\"fa fa-chevron-right\"></i></button></td>";
            }
            else
            {
              echo "<td><button class=\"btn btn-info btn-xs\" title=\"", _ ( "nächste Fundstelle" ), "\" onclick=\"", $__[ "search" ][ "js" ] [ "searchFunctionName" ], "('", $__[ "search" ] [ "ids" ][ "resultPrefix" ], "$searchID','$showRecordings',$item,$searchLimit,'", jsSave ( $searchString ), "',", (array_key_exists ( $__[ "search" ][ "params" ] [ "regexp" ],
                                                                                                                                                                                                                                                                                                                                    $_REQUEST ) && $_REQUEST[ $__[ "search" ][ "params" ] [ "regexp" ] ] == "on" ? 1 : 0), ",", (array_key_exists ( $__[ "search" ][ "params" ] [ "case" ],
                                                                                                                                                                                                                                                                                                                                                                                                                                                    $_REQUEST ) && $_REQUEST[ $__[ "search" ][ "params" ] [ "case" ] ] == "on" ? 1 : 0), ",'0',$positionToContinueSearch);\"><i class=\"fa fa-chevron-right\"></i></button></td>";
            }

            echo "</tr>";
          }
        }
        while ( $result = $selectResultStatement->fetch () );
      }
    }
  }

  echo "</tbody></table></div></div></div></div>";
}

include ("include/closeHTML.php");

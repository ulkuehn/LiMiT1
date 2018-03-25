<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/tableUtility.php
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
 * implement folding of a table and buttons to trigger (un)folding
 * 
 * @param string $tableID html id of table to fold
 * @param boolean $echo if true, echo js code and html, else return as an array
 * @return array (js code, html text)
 */
function tableFolder ( $tableID,
                       $echo = true )
{
  global $__;

  $jsCode = "<script type=\"text/javascript\"> function tableUnstyle_$tableID() { while (tableStyleSheet_$tableID.cssRules.length) { tableStyleSheet_$tableID.deleteRule(0); } } ";

  $jsCode .= "function " . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "() { tableUnstyle_$tableID(); tableStyleSheet_$tableID.insertRule(\"." . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . " { display: inline }\",0); tableStyleSheet_$tableID.insertRule(\"." . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . " { display: none }\",0); $tableID = 0; } ";

  $jsCode .= "function " . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "() { tableUnstyle_$tableID(); tableStyleSheet_$tableID.insertRule(\"." . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . " { display: none }\",0); tableStyleSheet_$tableID.insertRule(\"." . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . " { display: inline }\",0); $tableID = 1; } ";

  $jsCode .= "function tableToggleFolding$tableID() { if ($tableID==1) { " . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "(); } else { " . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "(); } } ";

  $jsCode .= "var sheet = document.createElement(\"style\"); sheet.type = \"text/css\"; document.head.appendChild(sheet); var tableStyleSheet_$tableID = sheet.sheet; var $tableID = 0; " . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "(); </script>";


  $htmlText = "<span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\"><a class=\"btn btn-xs btn-success\" onclick=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "();\" title=\"" . _ ( "ausführliche Ansicht" ) . "\"><i class=\"fa " . $__[ "include/tableUtility" ][ "values" ][ "foldedIcon" ] . "\"></i></a></span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\"><a class=\"btn btn-xs btn-warning\" onclick=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "();\" title=\"" . _ ( "kompakte Ansicht" ) . "\"><i class=\"fa " . $__[ "include/tableUtility" ][ "values" ][ "unfoldedIcon" ] . "\"></i></a></span>";

  if ( $echo )
  {
    echo $jsCode;
    return $htmlText;
  }
  else
  {
    return array (
      $jsCode,
      $htmlText );
  }
}


/**
 * implement js code for a sortable table
 * 
 * @global type $__zeilen
 * @param string $tableID html id of table to sort
 * @param string $columnDefinition table Sorter definition of columns and of their ordering
 * @return string js / html code for table sorting
 */
function tableSorter ( $tableID,
                       $columnDefinition )
{
  global $__, $__zeilen;

  $result = "<script type=\"text/javascript\"> $(document).ready( function() { $(\"#$tableID\").DataTable ( { $columnDefinition, \"dom\":  \"<'row'<'pull-left'l><'pull-right'f>><'row'<'col-md-12'tr>><'row'<'pull-left'i><'pull-right'p>>\", ";

  if ( $__zeilen == "" )
  {
    $result .= "\"paging\": false, ";
  }
  else
  {
    $pageLengths = explode ( " ",
                             $__zeilen );
    $result .= "\"pagingType\": \"full_numbers\", ";
    $result .= "\"pageLength\": " . $pageLengths[ 0 ] . ", ";
    sort ( $pageLengths,
           SORT_NUMERIC );
    $result .= "\"lengthMenu\": [ [" . implode ( ",",
                                                 array_merge ( $pageLengths,
                                                               array (
        "-1" ) ) ) . "], [" . implode ( ",",
                                        array_merge ( $pageLengths,
                                                      array (
        "\"Alle\"" ) ) ) . "] ], ";
  }

  $result .= "\"language\": { \"emptyTable\": \"" . _ ( "Die Tabelle enthält keine Zeilen" ) . "\", \"info\": \"" . _ ( "zeige Zeile _START_ - _END_ von _TOTAL_ Zeilen" ) . "\", \"infoEmpty\": \"" . _ ( "Keine Zeilen" ) . "\", \"infoFiltered\": \"" . _ ( " &ndash; ausgewählt aus insgesamt _MAX_ Zeilen" ) . "\", \"lengthMenu\": \"" . ("zeige _MENU_ Zeilen") . "\", \"search\": \"" . _ ( "Suchfilter" ) . " <i class='fa " . $__[ "include/tableUtility" ][ "values" ][ "filterIcon" ] . "'></i> \", \"zeroRecords\": \"" . _ ( "Keine passende Zeile gefunden" ) . "\", \"paginate\": { \"first\": \"<i class='fa fa-arrow-left'></i><i class='fa fa-arrow-left'></i>\", \"previous\": \"<i class='fa fa-arrow-left'></i>\", \"next\": \"<i class='fa fa-arrow-right'></i>\", \"last\": \"<i class='fa fa-arrow-right'></i><i class='fa fa-arrow-right'></i>\" } }, ";
  $result .= "\"columnDefs\": [ { targets: \"_all\", \"render\": function ( data, type, full, meta ) { if (type==\"sort\") { var m = data.match(/<!--.*-->/); if (m) { return m[0].substr(4,m[0].length-7).trim(); } else { return data; } } else { return data; } } } ]  } ); } ); </script>";

  return $result;
}


/**
 * return js code implementing click operation to (un)fold table
 * 
 * @global string $__klick setting which click operation should toggle table folding (js event name)
 * @param string $tableID html id of table to (un)fold
 */
function onTableToggleEvent ( $tableID )
{
  global $__klick;

  return " $__klick=\"tableToggleFolding$tableID();return false;\" ";
}


/**
 * create html code for a table cell in a foldable and sortable table
 * 
 * @param string $cellContents contents to put in table cell
 * @param string $tableID html id of table to put cell in
 * @param boolean $leftTruncation if true cell contents is truncated left hand side (i.e. 'abcd' -> '...cd' rather than 'ab...')
 * @param string $sortContents contents that will be used for table sorting
 * @return string html code
 */
function foldableTableCellSorting ( $cellContents,
                                    $tableID,
                                    $leftTruncation,
                                    $sortContents )
{
  global $__;

  if ( strlen ( $cellContents ) <= $__[ "include/tableUtility" ][ "values" ][ "maxLengthFolded" ] )
  {
    return "<td>" . htmlSave ( $cellContents ) . "<!--$sortContents--></td>";
  }
  else
  {
    if ( $leftTruncation )
    {
      return "<td class=\"break\"" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . htmlSave ( mb_substr ( $cellContents,
                                                                                                                                                                                                                                                                -$__[ "include/tableUtility" ][ "values" ][ "maxLengthFolded" ] ) ) . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . htmlSave ( $cellContents ) . "</span><!--$sortContents--></td>";
    }
    else
    {
      return "<td class=\"break\"" . onTableToggleEvent ( $tableID ) . "><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ] . $tableID . "\">" . htmlSave ( mb_substr ( $cellContents,
                                                                                                                                                                                                0,
                                                                                                                                                                                                $__[ "include/tableUtility" ][ "values" ][ "maxLengthFolded" ] ) ) . $__[ "include/tableUtility" ][ "values" ][ "foldedEllipses" ] . "</span><span class=\"" . $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ] . $tableID . "\">" . htmlSave ( $cellContents ) . "</span><!--$sortContents--></td>";
    }
  }
}


/**
 * create html code for a table cell in a foldable and sortable table where sorting is done on content proper
 * 
 * @param string $cellContents contents to put in table cell
 * @param string $tableID html id of table to put cell in
 * @param boolean $leftTruncation if true cell contents is truncated left hand side (i.e. 'abcd' -> '...cd' rather than 'ab...')
 */
function foldableTableCell ( $cellContents,
                             $tableID,
                             $leftTruncation = false )
{
  return foldableTableCellSorting ( $cellContents,
                                    $tableID,
                                    $leftTruncation,
                                    $cellContents );
}


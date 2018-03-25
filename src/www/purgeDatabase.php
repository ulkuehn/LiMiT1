<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file purgeDatabase.php
 * 
 * purge all recordings or whois data or device data from the database of the LiMiT1 system
 * after this operation there are no more recordings (whois data, device data) on the system
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
 * MAIN CODE
 * 
 * ======================================================================== */

include ("include/httpHeaders.php");
include ("include/openHTML.php");
include ("include/topMenu.php");

/**
 * tables to exclude from deletion
 */
$tablesNotToDelete = [
  /* whois related tables */
  $__[ "purgeDatabase" ][ "namesIds" ][ "whoisOption" ]  => [
    "whois" ],
  /* device related tables */
  $__[ "purgeDatabase" ][ "namesIds" ][ "deviceOption" ] => [
    "geraet",
    "eigenschaft" ],
  /* system tables that should never be deleted */
  "system"                                               => [
    "cipherSuite",
    "keyExchange",
    "cipher",
    "mac" ]
];


titleAndHelp ( _ ( "Datenbank leeren" ),
                   _ ( "Wenn keine der gespeicherten Aufzeichnungen mehr benötigt wird, ist das Leeren der Datenbank der schnellste Weg. Optional können auch (nur) die Geräte und die Whois-Informationen entfernt werden." ) );

echo "<div class=\"row\">";

/*
 * do the deletions
 */
$deletedSomething = false;
if ( isset ( $_POST[ $__[ "purgeDatabase" ][ "params" ] [ "delete" ] ] ) )
{
  echo "<div id=\"", $__[ "purgeDatabase" ] [ "ids" ][ "deletionDone" ], "\"></div><div id=\"", $__[ "purgeDatabase" ] [ "ids" ][ "deletionDoing" ], "\"></div>";

  $db->beginTransaction ();

  $initFileFH = fopen ( $database_initfile,
                        "r" );
  while ( ($line = fgets ( $initFileFH )) !== false )
  {
    $createTableSQLCommand .= $line;
    /*
     * reached end of table creation statement
     */
    if ( preg_match ( "/;$/",
                      trim ( $line ) ) )
    {
      if ( $doDelete )
      {
		$deletedSomething = true;
        echo "<script>document.getElementById(\"", $__[ "purgeDatabase" ] [ "ids" ][ "deletionDoing" ], "\").innerHTML = \"", jsSave ( showWaitMessage ( _ ( "Die Tabelle \"$tableName\" wird geleert" ),
                                                                                                                                                             false ),
                                                                                                                                                             false ), "\";</script>";
        ob_flush ();
        flush ();

        /*
         * drop table
         */
        #$db->query ( "drop table $tableName" );
        /*
         * recreate table
         */
        #$db->query ( $createTableSQLCommand );

        echo "<script>document.getElementById(\"", $__[ "purgeDatabase" ] [ "ids" ][ "deletionDoing" ], "\").innerHTML = ''; document.getElementById(\"", $__[ "purgeDatabase" ] [ "ids" ][ "deletionDone" ], "\").innerHTML += \"", jsSave ( showSuccessMessage ( _ ( "Die Tabelle \"$tableName\" wurde geleert" ),
                                                                                                                                                                                                                                                                       false ),
                                                                                                                                                                                                                                                                       false ), "\";</script>";
        ob_flush ();
        flush ();
      }
    }

    if ( preg_match ( "/create table.* ([a-z0-9_]+)$/i",
                      trim ( $line ),
                             $match ) )
    {
      $tableName = $match[ 1 ];
      $createTableSQLCommand = $line;
      $doDelete = true;
	  $recordingsTable = true;
      foreach ( $tablesNotToDelete as $section => $tableNamesNotToDelete )
      {
          foreach ( $tableNamesNotToDelete as $i => $tableNameNotToDelete )
          {
            if ( $tableNameNotToDelete == $tableName )
            {
				       if ( !( (isset ( $_POST[ $__[ "purgeDatabase" ][ "namesIds" ][ "whoisOption" ] ] ) && $section == $__[ "purgeDatabase" ][ "namesIds" ][ "whoisOption" ]) || (isset ( $_POST[ $__[ "purgeDatabase" ][ "namesIds" ][ "deviceOption" ] ] ) && $section == $__[ "purgeDatabase" ][ "namesIds" ][ "deviceOption" ]) ) )
        {
			$doDelete = false;
		}
		$recordingsTable = false;
            }
        }
      }
	  if ($recordingsTable && !isset($_POST[ $__[ "purgeDatabase" ][ "namesIds" ][ "recordingsOption" ] ]))
		  {
			  $doDelete=false;
		  }


    }
  }
  fclose ( $initFileFH );

  $db->commit ();
  if ($deletedSomething)
  {	  
  showSuccessMessage ( _ ( "Die Datenbank wurde geleert" ) );
  }
  else
  {
	  showAlertMessage(_("Es wurde nichts ausgewählt und daher nichts gelöscht"));
  }
}

/*
 * show deletion form
 */
else
{
  echo "<form method=\"post\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Einstellungen" ), "</h4></div><div class=\"panel-body\">";
  /*
   * delete recordings option
   */
  echo "<div class=\"checkbox\"><label><input type=\"checkbox\" name=\"", $__[ "purgeDatabase" ][ "namesIds" ][ "recordingsOption" ], "\" id=\"", $__[ "purgeDatabase" ][ "namesIds" ][ "recordingsOption" ], "\">", _ ( "sämtliche Aufzeichnungen löschen" ), "</label></div>";
  /*
   * delete device data option
   */
  echo "<div class=\"checkbox\"><label><input type=\"checkbox\" name=\"", $__[ "purgeDatabase" ][ "namesIds" ][ "deviceOption" ], "\" id=\"", $__[ "purgeDatabase" ][ "namesIds" ][ "deviceOption" ], "\">", _ ( "sämtliche Geräte-Daten löschen" ), "</label></div>";
  /*
   * delete whois data option
   */
  echo "<div class=\"checkbox\"><label><input type=\"checkbox\" name=\"", $__[ "purgeDatabase" ][ "namesIds" ][ "whoisOption" ], "\" id=\"", $__[ "purgeDatabase" ][ "namesIds" ][ "whoisOption" ], "\">", _ ( "sämtliche Whois-Daten löschen" ), "</label></div></div></div>";
  /*
   * delete button
   */
  echo "<a href=\"#", $__[ "purgeDatabase" ] [ "ids" ][ "confirmationModal" ], "\" class=\"btn btn-danger\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteItems" ], "').innerHTML=''; if (document.getElementById('", $__[ "purgeDatabase" ][ "namesIds" ][ "recordingsOption" ], "').checked) { document.getElementById('", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteItems" ], "').innerHTML +='<p>", _ ( "Sollen alle Aufzeichnungen gelöscht werden?" ), "</p>'; }  if (document.getElementById('", $__[ "purgeDatabase" ][ "namesIds" ][ "deviceOption" ], "').checked) { document.getElementById('", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteItems" ], "').innerHTML +='<p>", _ ( "Sollen alle Geräte-Informationen gelöscht werden?" ), "</p>'; }  if (document.getElementById('", $__[ "purgeDatabase" ][ "namesIds" ][ "whoisOption" ], "').checked) { document.getElementById('", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteItems" ], "').innerHTML +='<p>", _ ( "Sollen alle Whois-Informationen gelöscht werden?" ), "</p>'; } if (document.getElementById('", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteItems" ], "').innerHTML=='') {document.getElementById('", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteItems" ], "').innerHTML='<p>",_("Es wurde nichts ausgewählt"),"</p>'; } \">", $__[ "purgeDatabase" ][ "values" ] [ "delete" ], "</a></div>";
  /*
   * confirmation modal
   */
  echo "<div class=\"modal fade\" id=\"", $__[ "purgeDatabase" ] [ "ids" ][ "confirmationModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\">";
  echo "<div class=\"modal-header\"><div class=\"alert alert-danger\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-trash fa-2x\"></i></div><div class=\"msgText\"><strong>", $__[ "purgeDatabase" ][ "values" ] [ "delete" ], "</strong></div></div></div>";
  echo "<div class=\"modal-body\"><div id=\"", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteItems" ], "\"></div></div>";
  echo "<div class=\"modal-footer\"><input id=\"", $__[ "purgeDatabase" ] [ "ids" ] [ "deleteButton" ], "\" class=\"btn btn-danger\" type=\"submit\" value=\"", $__[ "purgeDatabase" ][ "values" ] [ "delete" ], "\" name=\"", $__[ "purgeDatabase" ][ "params" ] [ "delete" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div></form>";
}

echo "</div>";

include ("include/closeHTML.php");

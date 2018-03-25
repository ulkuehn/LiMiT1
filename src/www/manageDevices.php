<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file manageDevices.php
 * 
 * manage monitored devices and their properties
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

titleAndHelp ( _ ( "Geräte verwalten" ),
                   _ ( "In diesem Bereich können die Geräte verwaltet werden, deren Verkehr aufgezeichnet werden soll bzw. wurde.
Für jedes Gerät können beliebige Eigenschaften definiert werden, um diese in den aufgezeichneten Daten auffinden zu können.<br>So kann etwa für ein Smartphone die IMEI-Nummer, MAC-Adresse oder der Name eines auf dem Gerät verwendeten Kontos eingetragen werden. Ob diese Daten in dem aufgezeichneten Datenstrom vorhanden sind, kann dann rasch ermittelt werden." ) );

/*
 * add device
 */
if ( isset ( $_POST[ $__[ "manageDevices" ][ "params" ][ "add" ] ] ) )
{
  $deviceName = trim ( $_POST[ $__[ "manageDevices" ][ "params" ][ "name" ] ] );
  if ( $deviceName != "" )
  {
    $selectDeviceStatement = $db->prepare ( "select * from geraet where binary name=?" );
    $selectDeviceStatement->execute ( array (
      $deviceName ) );
    if ( $selectDeviceStatement->fetch () )
    {
      $deviceName = htmlSave ( $deviceName );
      showErrorMessage ( _ ( "Das Gerät \"$deviceName\" ist bereits vorhanden." ) );
    }
    else
    {
      $insertDeviceStatement = $db->prepare ( "insert into geraet set name=?, stand=now()" );
      $insertDeviceStatement->execute ( array (
        $deviceName ) );
    }
  }
}


/*
 * delete device
 */
if ( isset ( $_POST[ $__[ "manageDevices" ][ "params" ][ "delete" ] ] ) )
{
  $selectDeviceStatement = $db->prepare ( "select * from geraet where id=?" );
  $selectDeviceStatement->execute ( array (
    $_POST[ $__[ "manageDevices" ][ "ids" ][ "device" ] ] ) );
  /*
   * device exists
   */
  if ( $device = $selectDeviceStatement->fetch () )
  {
    $deviceName = htmlSave ( $device[ "name" ] );
    $selectRecordingsStatement = $db->prepare ( "select * from aufzeichnung where geraet=?" );
    $selectRecordingsStatement->execute ( array (
      $_POST[ $__[ "manageDevices" ][ "ids" ][ "device" ] ] ) );
    /*
     * device still connected 
     */
    if ( $selectRecordingsStatement->fetch () )
    {
      showErrorMessage ( _ ( "Das Gerät \"$deviceName\" ist mit Aufzeichnungen in der Datenbank verknüpft und kann daher nicht gelöscht werden." ) );
    }
    /*
     * really delete device 
     */
    else
    {
      $deletePropertyStatement = $db->prepare ( "delete from eigenschaft where geraet=?" );
      $deletePropertyStatement->execute ( array (
        $device[ "id" ] ) );

      $deleteDeviceStatement = $db->prepare ( "delete from geraet where id=?" );
      $deleteDeviceStatement->execute ( array (
        $device[ "id" ] ) );

      showSuccessMessage ( _ ( "Das Gerät \"$deviceName\" wurde gelöscht." ) );
    }
  }
  else
  {
    showErrorMessage ( _ ( "Das Gerät ist nicht in der Datenbank vorhanden." ) );
  }
}

/*
 * show form
 */
echo "<form method=\"post\"><input type=\"hidden\" id=\"", $__[ "manageDevices" ][ "ids" ][ "device" ], "\" name=\"", $__[ "manageDevices" ][ "ids" ][ "device" ], "\" value=\"\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Vorhandene Geräte" ), "</h4></div><div class=\"panel-body\">";

$selectDeviceStatement = $db->prepare ( "select *, unix_timestamp(stand) as _stand, date_format(stand,'%e.%c.%Y') as _standd,  date_format(stand,'%H:%i:%s') as _standt from geraet order by name" );
$selectDeviceStatement->execute ();
if ( ($device = $selectDeviceStatement->fetch ()) == false )
{
  showInfoMessage ( _ ( "Es sind keine Geräte definiert." ) );
}
else
{
  echo tableSorter ( $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ],
                     "columns: [ {orderable:false, searchable:false}, {}, {}, {} ], order: [ [1,'asc'] ]" );
  $foldUnfoldButton = tableFolder ( $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ] );

  echo "<div class=\"table-responsive\"><table id=\"", $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ], "\" class=\"table table-hover\"><thead><tr>";
  /*
   * edit button
   */
  echo "<th>$foldUnfoldButton</th>";
  /*
   * name
   */
  echo "<th>", _ ( "Name" ), "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ], "\">", _ ( " des Geräts" ), "</span></th>";
  /*
   * properties
   */
  echo "<th>", _ ( "Eigenschaften" ), "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ], "\">", _ ( " des Geräts" ), "</span></th>";
  /*
   * time of last edit
   */
  echo "<th><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "foldedPrefix" ], $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ], "\">", _ ( "Stand" ), "</span><span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ], "\">", _ ( "letzte Änderung" ), "</span></th></tr></thead><tbody>";

  /*
   * tabulate devices
   */
  do
  {
    echo "<tr>";

    /*
     * edit device
     */
    echo "<td><span style=\"white-space:nowrap\">";
    echo showIconButton ( "fa fa-pencil",
                      "editDevice.php?" . $__[ "editDevice" ][ "params" ][ "device" ] . "=" . $device[ "id" ],
                      _ ( "Gerät bearbeiten" ),
                          "success" );
    echo "<span class=\"", $__[ "include/tableUtility" ][ "ids" ][ "unfoldedPrefix" ], $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ], "\"> <a href=\"#", $__[ "manageDevices" ][ "ids" ][ "deleteModal" ], "\" title=\"", $__[ "manageDevices" ][ "values" ][ "deleteDevice" ], "\" class=\"btn btn-danger btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "manageDevices" ] [ "ids" ] [ "name" ], "').innerHTML='", jsSave ( htmlspecialchars ( $device[ "name" ] ) ), "';document.getElementById('", $__[ "manageDevices" ][ "ids" ][ "device" ], "').value='", $device[ "id" ], "';\"><i class=\"fa fa-trash\"></i></a></span>";
    echo "</span></td>";

    /*
     * name
     */
    echo foldableTableCell ( $device[ "name" ],
                             $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ] );

    /*
     * properties
     */
    $properties = "";
    $selectPropertyStatement = $db->prepare ( "select * from eigenschaft where geraet=? order by name" );
    $selectPropertyStatement->execute ( array (
      $device[ "id" ] ) );
    while ( $property = $selectPropertyStatement->fetch () )
    {
      $properties .= $property[ "name" ] . " = " . $property[ "wert" ] . "\n";
    }
    echo foldableTableCell ( $properties,
                             $__[ "manageDevices" ][ "ids" ][ "tables" ][ "devices" ] );

    /*
     * edit time
     */
    echo "<td>", $device[ "_standd" ], " <i class=\"fa fa-clock-o\"></i> ", $device[ "_standt" ], "<!--", $device[ "_stand" ], "--></td>";

    echo "</tr>";
  }
  while ( $device = $selectDeviceStatement->fetch () );

  echo "</tbody></table></div>";
}

/*
 * add device button
 */
echo "</div></div></div><div class=\"row\"><a href=\"#", $__[ "manageDevices" ][ "ids" ][ "addModal" ], "\" class=\"btn btn-primary\" data-toggle=\"modal\">", $__[ "manageDevices" ][ "values" ][ "addDevice" ], "</a></div>";

/*
 * add device modal
 */
echo "<div class=\"modal fade\" id=\"", $__[ "manageDevices" ][ "ids" ][ "addModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\">";
echo "<div class=\"modal-header\"><div class=\"alert alert-info\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-plus fa-2x\"></i></div><div class=\"msgText\"><strong>", $__[ "manageDevices" ][ "values" ][ "addDevice" ], "</strong></div></div></div>";
echo "<div class=\"modal-body\"><div class=\"input-group\"><span class=\"input-group-addon\">", _ ( "Name" ), "</span><input type=\"text\" class=\"form-control\" name=\"", $__[ "manageDevices" ][ "params" ][ "name" ], "\"></div></div>";
echo "<div class=\"modal-footer\"><input class=\"btn btn-primary\" type=\"submit\" value=\"", $__[ "manageDevices" ][ "values" ][ "addDevice" ], "\" name=\"", $__[ "manageDevices" ][ "params" ][ "add" ], "\"> <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div>";

/*
 * delete device modal
 */
echo "<div class=\"modal fade\" id=\"", $__[ "manageDevices" ][ "ids" ][ "deleteModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\">";
echo "<div class=\"modal-header\"><div class=\"alert alert-danger\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-trash fa-2x\"></i></div><div class=\"msgText\"><strong>", $__[ "manageDevices" ][ "values" ][ "deleteDevice" ], "</strong></div></div></div>";
echo "<div class=\"modal-body\"><p>", _ ( "Soll das Gerät <strong><span id=\"",
                                          $__[ "manageDevices" ] [ "ids" ] [ "name" ],
                                          "\"></span></strong> gelöscht werden?" ), "</p></div>";
echo "<div class=\"modal-footer\"><input class=\"btn btn-danger\" type=\"submit\" value=\"", $__[ "manageDevices" ][ "values" ][ "deleteDevice" ], "\" name=\"", $__[ "manageDevices" ][ "params" ][ "delete" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div></form>";

include ("include/closeHTML.php");

<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file editDevice.php
 * 
 * modify known devices and their properties
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

titleAndHelp ( _ ( "Gerät bearbeiten" ),
                   _ ( "Ein vorhandenes Gerät kann hier editiert werden. Dabei können bestehende Eigenschaften angepasst oder gelöscht sowie neue Eigenschaften hinzugefügt werden.<br>Beim Wert einer Eigenschaft wird nicht zwischen Groß- und Kleinschreibung unterschieden. Eine Eigenschaft etwa mit dem Wert \"limit1\" würde daher auch gefunden, wenn der String \"LiMiT1\" übertragen wurde." ) );


$selectDeviceStatement = $db->prepare ( "select * from geraet where id=?" );
$selectDeviceStatement->execute ( array (
  $_GET[ $__[ "editDevice" ][ "params" ][ "device" ] ] ) );

/*
 * device doesn't exist
 */
if ( ($device = $selectDeviceStatement->fetch ()) == false )
{
  echo "<div class=\"row\">";
  showErrorMessage ( _ ( "Das Gerät ist nicht in der Datenbank vorhanden." ) );
  echo "</div>";
}

/*
 * device ok
 */
else
{
  echo "<form class=\"form-horizontal\" method=\"post\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Name" ), "</h4></div><div class=\"panel-body\">";

  /*
   * change device name
   */
  if ( isset ( $_POST[ $__[ "editDevice" ][ "params" ][ "changeName" ] ] ) )
  {
    $newDeviceName = trim ( $_POST[ $__[ "editDevice" ][ "params" ][ "deviceName" ] ] );
    if ( $newDeviceName != "" && $newDeviceName != $device[ "name" ] )
    {
      $selectDeviceStatement = $db->prepare ( "select * from geraet where id!=? and binary name=?" );
      $selectDeviceStatement->execute ( array (
        $device[ "id" ],
        $newDeviceName ) );
      if ( !$selectDeviceStatement->fetch () )
      {
        $updateDeviceStatement = $db->prepare ( "update geraet set name=?, stand=now() where id=?" );
        $updateDeviceStatement->execute ( array (
          $newDeviceName,
          $device[ "id" ] ) );
        $device[ "name" ] = $newDeviceName;
        showInfoMessage ( _ ( "Der Gerätename wurde geändert." ) );
      }
      else
      {
        $newDeviceName = htmlSave ( $newDeviceName );
        showErrorMessage ( _ ( "Ein Gerät mit dem Namen \"$newDeviceName\" ist bereits vorhanden." ) );
      }
    }
  }

  echo "<p><input class=\"form-control\" type=\"text\" name=\"", $__[ "editDevice" ][ "params" ][ "deviceName" ], "\" value=\"", htmlSave ( $device[ "name" ] ), "\"></p><input type=\"submit\" class=\"btn btn-primary\" name=\"", $__[ "editDevice" ][ "params" ][ "changeName" ], "\" value=\"", _ ( "Gerätenamen ändern" ), "\"></div></div></div><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">", _ ( "Eigenschaften" ), "</h4></div><div class=\"panel-body\">";

  /*
   * add property
   */
  if ( isset ( $_POST[ $__[ "editDevice" ][ "params" ][ "addProperty" ] ] ) )
  {
    $propertyName = trim ( $_POST[ $__[ "editDevice" ][ "params" ][ "propertyName" ] ] );
    $propertyValue = trim ( $_POST[ $__[ "editDevice" ][ "params" ][ "propertyValue" ] ] );
    if ( $propertyName != "" && $propertyValue != "" )
    {
      $selectPropertyStatement = $db->prepare ( "select * from eigenschaft where geraet=? and binary name=?" );
      $selectPropertyStatement->execute ( array (
        $device[ "id" ],
        $propertyName ) );
      if ( $selectPropertyStatement->fetch () )
      {
        $propertyName = htmlSave ( $propertyName );
        showErrorMessage ( _ ( "Die Eigenschaft \"$propertyName\" ist bereits vorhanden." ) );
      }
      else
      {
        $insertPropertyStatement = $db->prepare ( "insert into eigenschaft set geraet=?, name=?, wert=?" );
        $insertPropertyStatement->execute ( array (
          $device[ "id" ],
          $propertyName,
          $propertyValue ) );
        $updateDeviceStatement = $db->prepare ( "update geraet set stand=now() where id=?" );
        $updateDeviceStatement->execute ( array (
          $device[ "id" ] ) );
      }
    }
  }

  /*
   * edit property
   */
  if ( isset ( $_POST[ $__[ "editDevice" ][ "params" ][ "editProperty" ] ] ) )
  {
    $selectPropertyStatement = $db->prepare ( "select * from eigenschaft where id=?" );
    $selectPropertyStatement->execute ( array (
      $_POST[ $__[ "editDevice" ][ "params" ][ "property" ] ] ) );
    if ( !$selectPropertyStatement->fetch () )
    {
      showErrorMessage ( _ ( "Die Eigenschaft ist nicht in der Datenbank vorhanden." ) );
    }
    else
    {
      $propertyName = trim ( $_POST[ $__[ "editDevice" ] [ "params" ][ "editPropertyName" ] ] );
      $propertyValue = trim ( $_POST[ $__[ "editDevice" ] [ "params" ][ "editPropertyValue" ] ] );
      if ( $propertyName != "" && $propertyValue != "" )
      {
        $selectPropertyStatement = $db->prepare ( "select * from eigenschaft where geraet=? and binary name=? and id!=?" );
        $selectPropertyStatement->execute ( array (
          $device[ "id" ],
          $propertyName,
          $_POST[ $__[ "editDevice" ][ "params" ][ "property" ] ] ) );
        if ( !$selectPropertyStatement->fetch () )
        {
          $updatePropertyStatement = $db->prepare ( "update eigenschaft set name=?, wert=? where id=?" );
          $updatePropertyStatement->execute ( array (
            $propertyName,
            $propertyValue,
            $_POST[ $__[ "editDevice" ][ "params" ][ "property" ] ] ) );
          $updateDeviceStatement = $db->prepare ( "update geraet set stand=now() where id=?" );
          $updateDeviceStatement->execute ( array (
            $device[ "id" ] ) );
        }
        else
        {
          $propertyName = htmlSave ( $propertyName );
          showErrorMessage ( _ ( "Die Eigenschaft \"$propertyName\" ist bereits vorhanden." ) );
        }
      }
    }
  }

  /*
   * delete property
   */
  if ( isset ( $_POST[ $__[ "editDevice" ][ "params" ][ "deleteProperty" ] ] ) )
  {
    $selectPropertyStatement = $db->prepare ( "select * from eigenschaft where id=?" );
    $selectPropertyStatement->execute ( array (
      $_POST[ $__[ "editDevice" ][ "params" ][ "property" ] ] ) );
    if ( $property = $selectPropertyStatement->fetch () )
    {
      $deletePropertyStatement = $db->prepare ( "delete from eigenschaft where id=?" );
      $deletePropertyStatement->execute ( array (
        $property[ "id" ] ) );
      $updateDeviceStatement = $db->prepare ( "update geraet set stand=now() where id=?" );
      $updateDeviceStatement->execute ( array (
        $device[ "id" ] ) );
    }
    else
    {
      showErrorMessage ( _ ( "Die Eigenschaft ist nicht in der Datenbank vorhanden." ) );
    }
  }


  /*
   * tabulate properties
   */
  $selectPropertyStatement = $db->prepare ( "select * from eigenschaft where geraet=? order by name" );
  $selectPropertyStatement->execute ( array (
    $_GET[ $__[ "editDevice" ][ "params" ][ "device" ] ] ) );

  if ( ($property = $selectPropertyStatement->fetch ()) == false )
  {
    showInfoMessage ( _ ( "Zu diesem Gerät ist keine Eigenschaft eingetragen." ) );
  }
  else
  {
    echo tableSorter ( $__[ "editDevice" ][ "ids" ][ "tables" ][ "properties" ],
                       "columns: [ {orderable:false, searchable:false}, {}, {} ], order: [ [1,'asc'] ]" );
    $foldMe = tableFolder ( $__[ "editDevice" ][ "ids" ][ "tables" ][ "properties" ] );

    echo "<input type=\"hidden\" id=\"", $__[ "editDevice" ][ "params" ][ "property" ], "\" name=\"", $__[ "editDevice" ][ "params" ][ "property" ], "\" value=\"\"><div class=\"table-responsive\"><table id=\"", $__[ "editDevice" ][ "ids" ][ "tables" ][ "properties" ], "\" class=\"table table-hover\"><thead><tr>";
    /*
     * edit button etc
     */
    echo "<th>$foldMe</th>";
    /*
     * property name
     */
    echo "<th>", _ ( "Eigenschaft" ), "</th>";
    /*
     * property value
     */
    echo "<th>", _ ( "Wert" ), "</th></tr></thead><tbody>";

    do
    {
      echo "<tr>";

      /*
       * buttons
       */
      echo "<td><span style=\"white-space:nowrap\">";
      echo "<a href=\"#", $__[ "editDevice" ] [ "ids" ] [ "editModal" ], "\" title=\"", $__[ "editDevice" ][ "values" ][ "editProperty" ], "\" class=\"btn btn-success btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "editDevice" ] [ "params" ][ "editPropertyName" ], "').value='", jsSave ( $property[ "name" ] ), "'; document.getElementById('", $__[ "editDevice" ] [ "params" ][ "editPropertyValue" ], "').value='", jsSave ( $property[ "wert" ] ), "'; document.getElementById('", $__[ "editDevice" ][ "params" ][ "property" ], "').value='", $property[ "id" ], "';\"><i class=\"fa fa-pencil\"></i></a>";
      echo "<span class=\"", $__[ "include/tableUtility" ][ "ids" ] [ "unfoldedPrefix" ], $__[ "editDevice" ][ "ids" ][ "tables" ][ "properties" ], "\"> <a href=\"#", $__[ "editDevice" ] [ "ids" ] [ "deleteModal" ], "\" title=\"", $__[ "editDevice" ][ "values" ][ "deleteProperty" ], "\" class=\"btn btn-danger btn-xs\" data-toggle=\"modal\" onclick=\"document.getElementById('", $__[ "editDevice" ] [ "ids" ] [ "property" ], "').innerHTML='", jsSave ( htmlspecialchars ( $property[ "name" ] ) ), "';document.getElementById('", $__[ "editDevice" ][ "params" ][ "property" ], "').value='", $property[ "id" ], "';\"><i class=\"fa fa-trash\"></i></a></span>";
      echo "</span></td>";

      /*
       * name
       */
      echo foldableTableCell ( $property[ "name" ],
                               $__[ "editDevice" ][ "ids" ][ "tables" ][ "properties" ] );

      /*
       * value
       */
      echo foldableTableCell ( $property[ "wert" ],
                               $__[ "editDevice" ][ "ids" ][ "tables" ][ "properties" ] );

      echo "</tr>";
    }
    while ( $property = $selectPropertyStatement->fetch () );

    echo "</tbody></table></div>";
  }

  /*
   * add property button
   */
  echo "<a href=\"#", $__[ "editDevice" ] [ "ids" ] [ "addModal" ], "\" class=\"btn btn-primary\" data-toggle=\"modal\">", $__[ "editDevice" ][ "values" ][ "addProperty" ], "</a></div></div></div>";

  /*
   * add property modal
   */
  echo "<div class=\"modal fade\" id=\"", $__[ "editDevice" ] [ "ids" ] [ "addModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\">";
  echo "<div class=\"modal-header\"><div class=\"alert alert-info\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-plus fa-2x\"></i></div><div class=\"msgText\"><strong>", $__[ "editDevice" ][ "values" ][ "addProperty" ], "</strong></div></div></div>";
  echo "<div class=\"modal-body\"><div class=\"form-group\"><label for=\"", $__[ "editDevice" ][ "params" ][ "propertyName" ], "\" class=\"col-md-2 control-label\">", _ ( "Name" ), "</label><div class=\"col-md-10\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "editDevice" ][ "params" ][ "propertyName" ], "\" id=\"", $__[ "editDevice" ][ "params" ][ "propertyName" ], "\"></div></div><div class=\"form-group\"><label for=\"", $__[ "editDevice" ][ "params" ][ "propertyValue" ], "\" class=\"col-md-2 control-label\">", _ ( "Wert" ), "</label><div class=\"col-md-10\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "editDevice" ][ "params" ][ "propertyValue" ], "\" id=\"", $__[ "editDevice" ][ "params" ][ "propertyValue" ], "\"></div></div></div>";
  echo "<div class=\"modal-footer\"><input class=\"btn btn-primary\" type=\"submit\" value=\"", $__[ "editDevice" ][ "values" ][ "addProperty" ], "\" name=\"", $__[ "editDevice" ][ "params" ][ "addProperty" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div>";

  /*
   * delete property modal
   */
  echo "<div class=\"modal fade\" id=\"", $__[ "editDevice" ] [ "ids" ] [ "deleteModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\">";
  echo "<div class=\"modal-header\"><div class=\"alert alert-danger\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-trash fa-2x\"></i></div><div class=\"msgText\"><strong>", $__[ "editDevice" ][ "values" ][ "deleteProperty" ], "</strong></div></div></div>";
  echo "<div class=\"modal-body\"><p>", _ ( "Soll die Eigenschaft" ), " <strong><span id=\"", $__[ "editDevice" ] [ "ids" ] [ "property" ], "\"></span></strong> ", _ ( "gelöscht werden?" ), "</p></div>";
  echo "<div class=\"modal-footer\"><input class=\"btn btn-danger\" type=\"submit\" value=\"", $__[ "editDevice" ][ "values" ][ "deleteProperty" ], "\" name=\"", $__[ "editDevice" ][ "params" ][ "deleteProperty" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">", _ ( "Abbrechen" ), "</button></div></div></div></div>";

  /*
   * edit property modal
   */
  echo "<div class=\"modal fade\" id=\"", $__[ "editDevice" ] [ "ids" ] [ "editModal" ], "\" tabindex=\"-1\" role=\"dialog\"><div class=\"modal-dialog modal-lg\" role=\"document\"><div class=\"modal-content\">";
  echo "<div class=\"modal-header\"><div class=\"alert alert-success\" role=\"alert\"><div class=\"msgIcon\"><i class=\"fa fa-pencil fa-2x\"></i></div><div class=\"msgText\"><strong>", $__[ "editDevice" ][ "values" ][ "editProperty" ], "</strong></div></div></div>";
  echo "<div class=\"modal-body\"><div class=\"form-group\"><label for=\"", $__[ "editDevice" ] [ "params" ][ "editPropertyName" ], "\" class=\"col-md-2 control-label\">", _ ( "Name" ), "</label><div class=\"col-md-10\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "editDevice" ] [ "params" ][ "editPropertyName" ], "\" id=\"", $__[ "editDevice" ] [ "params" ][ "editPropertyName" ], "\"></div></div><div class=\"form-group\"><label for=\"", $__[ "editDevice" ] [ "params" ][ "editPropertyValue" ], "\" class=\"col-md-2 control-label\">", _ ( "Wert" ), "</label><div class=\"col-md-10\"><input type=\"text\" class=\"form-control\" name=\"", $__[ "editDevice" ] [ "params" ][ "editPropertyValue" ], "\" id=\"", $__[ "editDevice" ] [ "params" ][ "editPropertyValue" ], "\"></div></div></div>";
  echo "<div class=\"modal-footer\"><input class=\"btn btn-success\" type=\"submit\" value=\"", $__[ "editDevice" ][ "values" ][ "editProperty" ], "\" name=\"", $__[ "editDevice" ][ "params" ][ "editProperty" ], "\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Abbrechen</button></div></div></div></div></form>";
}

include ("include/closeHTML.php");

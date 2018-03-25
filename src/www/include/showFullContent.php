<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/showFullContent.php
 * 
 * used to reload and redisplay of content thas was only partially shown due to its length
 * content of exceeding length is only shown in part, leaving it up to the user to reload the possibly very long rest
 * if the user chooses to do so, this script is in charge
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
require_once ("include/contentUtility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * positive key values are indexes for content table
 */
if ( $_GET[ $__[ "include/showFullContent" ][ "params" ][ "key" ] ] > 0 )
{
  $selectContentStatement = $db->prepare ( "select * from inhalt where id=?" );
  $selectContentStatement->execute ( array (
    $_GET[ $__[ "include/showFullContent" ][ "params" ][ "key" ] ] ) );
  $content = $selectContentStatement->fetch ();

  if ( $content[ "typ" ] == "request" )
  {
    $selectRequestStatement = $db->prepare ( "select * from request where id=?" );
    $selectRequestStatement->execute ( array (
      $content[ "referenz" ] ) );
    $reference = $selectRequestStatement->fetch ();
  }
  else if ( $content[ "typ" ] == "response" )
  {
    $selectResponseStatement = $db->prepare ( "select * from response where request=?" );
    $selectResponseStatement->execute ( array (
      $content[ "referenz" ] ) );
    $reference = $selectResponseStatement->fetch ();
  }

  $selectRecordingStatement = $db->prepare ( "select * from aufzeichnung where id=?" );
  $selectRecordingStatement->execute ( array (
    $content[ "aufzeichnung" ] ) );
  if ( $recording = $selectRecordingStatement->fetch () )
  {
    $selectPropertiesStatement = $db->prepare ( "select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?" );
    $selectPropertiesStatement->execute ( array (
      $recording[ "geraet" ] ) );
    $properties = $selectPropertiesStatement->fetchAll ( PDO::FETCH_COLUMN,
                                                         0 );
  }

  showContent ( $_GET[ $__[ "include/showFullContent" ][ "params" ][ "index" ] ],
                $content[ "inhalt" ],
                $properties,
                $reference[ "mime" ] );
}

/*
 * negative key values are indexes for connection table
 */
else
{
  $content = "";
  $selectRequestStatement = $db->prepare ( "select * from request where verbindung=? order by id" );
  $selectRequestStatement->execute ( array (
    - $_GET[ $__[ "include/showFullContent" ][ "params" ][ "key" ] ] ) );

  while ( $request = $selectRequestStatement->fetch () )
  {
    $content .= $request[ "methode" ] . " " . $request[ "uri" ] . " " . $request[ "version" ] . "\n";

    $selectHeaderStatement = $db->prepare ( "select feld,wert from header where request=? and not response order by id" );
    $selectHeaderStatement->execute ( array (
      $request[ "id" ] ) );
    while ( $header = $selectHeaderStatement->fetch () )
    {
      $content .= $header[ "feld" ] . ": " . $header[ "wert" ] . "\n";
    }
    $content .= "\n";

    if ( $request[ "inhaltroh" ] || $request[ "inhalt" ] )
    {
      $selectContentStatement = $db->prepare ( "select inhalt from inhalt where id=?" );
      $selectContentStatement->execute ( array (
        $request[ "inhaltroh" ] ? $request[ "inhaltroh" ] : $request[ "inhalt" ] ) );
      $content .= $selectContentStatement->fetchColumn ();
    }

    $selectResponseStatement = $db->prepare ( "select * from response where request=?" );
    $selectResponseStatement->execute ( array (
      $request[ "id" ] ) );
    $response = $selectResponseStatement->fetch ();
    $content .= $response[ "version" ] . " " . $response[ "status" ] . " " . $response[ "statustext" ] . "\n";

    $selectHeaderStatement = $db->prepare ( "select feld,wert from header where request=? and response order by id" );
    $selectHeaderStatement->execute ( array (
      $request[ "id" ] ) );
    while ( $header = $selectHeaderStatement->fetch () )
    {
      $content .= $header[ "feld" ] . ": " . $header[ "wert" ] . "\n";
    }
    $content .= "\n";

    if ( $response[ "inhaltroh" ] || $response[ "inhalt" ] )
    {
      $selectContentStatement = $db->prepare ( "select inhalt from inhalt where id=?" );
      $selectContentStatement->execute ( array (
        $response[ "inhaltroh" ] ? $response[ "inhaltroh" ] : $response[ "inhalt" ] ) );
      $content .= $selectContentStatement->fetchColumn ();
    }
  }

  $selectDeviceIDStatement = $db->prepare ( "select geraet from aufzeichnung,verbindung where aufzeichnung.id=verbindung.aufzeichnung and verbindung.id=?" );
  $selectDeviceIDStatement->execute ( array (
    -$_GET[ $__[ "include/showFullContent" ][ "params" ][ "key" ] ] ) );
  $deviceID = $selectDeviceIDStatement->fetchColumn ();

  $selectPropertiesStatement = $db->prepare ( "select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?" );
  $selectPropertiesStatement->execute ( array (
    $deviceID ) );
  $properties = $selectPropertiesStatement->fetchAll ( PDO::FETCH_COLUMN,
                                                       0 );

  showContent ( $_GET[ $__[ "include/showFullContent" ][ "params" ][ "index" ] ],
                $content,
                $properties );
}

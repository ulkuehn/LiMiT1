<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/showMIMEContent.php
 * 
 * used to display recorded content natively (as specified in its MIME type)
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

/*
 * first access the element (response or request) linking to the content table
 */
if ( $_REQUEST[ $__[ "include/showMIMEContent" ][ "params" ][ "type" ] ] == $__[ "include/showMIMEContent" ][ "values" ][ "request" ] )
{
  $selectRequestResponseStatement = $db->prepare ( "select * from request where id=?" );
}
else
{
  $selectRequestResponseStatement = $db->prepare ( "select * from response where id=?" );
}
$selectRequestResponseStatement->execute ( array (
  $_REQUEST[ $__[ "include/showMIMEContent" ][ "params" ][ "id" ] ] ) );

/*
 * then get the content proper and serve it
 */
if ( $requestResponse = $selectRequestResponseStatement->fetch () )
{
  $selectContentStatement = $db->prepare ( "select inhalt from inhalt where id=?" );
  $selectContentStatement->execute ( array (
    $requestResponse[ "inhalt" ] ) );
  if ( $content = $selectContentStatement->fetchColumn () )
  {
    header ( "Content-Type: " . $requestResponse[ "mime" ] . ($requestResponse[ "mimeadd" ] != "" ? ";" . $requestResponse[ "mimeadd" ] : "") );
    echo $content;
  }
}
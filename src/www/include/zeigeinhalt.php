<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/zeigeinhalt.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to reload and redisplay of content thas was only partially
//              shown due to its length
//              content of exceeding length is only shown by its beginning,
//              leaving it up to the user to reload the possibly very long rest
//              if the user chooses to do so, this script is in charge
//
//==============================================================================
//==============================================================================


require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");
require ("../include/database.php");

if ($_GET["id"]>0) # id is primary key for table inhalt
{
  $select_s = $db->prepare("select * from inhalt where id=?");
  $select_s->execute(array($_GET["id"]));
  $inhalt = $select_s->fetch();

  if ($inhalt["typ"]=="request")
  {
    $select_s = $db->prepare ("select * from request where id=?");
    $select_s->execute (array($inhalt["referenz"]));
    $referenz = $select_s->fetch();
  }
  else if ($inhalt["typ"]=="response")
  {
    $select_s = $db->prepare ("select * from response where request=?");
    $select_s->execute (array($inhalt["referenz"]));
    $referenz = $select_s->fetch();
  }

  $select_s = $db->prepare("select * from aufzeichnung where id=?");
  $select_s->execute(array($inhalt["aufzeichnung"]));
  if ($aufzeichnung = $select_s->fetch())
  {
    $select_s = $db->prepare ("select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?");
    $select_s->execute(array($aufzeichnung["geraet"]));
    $eigenschaften = $select_s->fetchAll(PDO::FETCH_COLUMN, 0);
  }
  
  zeigeInhalt ($_GET["nr"], $inhalt["inhalt"], $eigenschaften, $referenz["mime"]);
}

else # id is primary key for table verbindung
{
  $inhalt = "";
  $select_s = $db->prepare ("select * from request where verbindung=? order by id");
  $select_s->execute (array(-$_GET["id"]));
  while ($request = $select_s->fetch())
  {
    $inhalt .= $request["methode"]." ".$request["uri"]." ".$request["version"]."\n";
    $header_s = $db->prepare ("select feld,wert from header where request=? and not response order by id");
    $header_s->execute (array($request["id"]));
    while ($header = $header_s->fetch())
    {
      $inhalt .= $header["feld"].": ".$header["wert"]."\n";
    }
    $inhalt .= "\n";
    if ($request["inhaltroh"] || $request["inhalt"])
    {
      $inhalt_s = $db->prepare ("select inhalt from inhalt where id=?");
      $inhalt_s->execute (array($request["inhaltroh"]? $request["inhaltroh"]:$request["inhalt"]));
      $inhalt .= $inhalt_s->fetchColumn();
    }

    $response_s = $db->prepare ("select * from response where request=?");
    $response_s->execute (array($request["id"]));
    $response = $response_s->fetch();
    $inhalt .= $response["version"]." ".$response["status"]." ".$response["statustext"]."\n";
    $header_s = $db->prepare ("select feld,wert from header where request=? and response order by id");
    $header_s->execute (array($request["id"]));
    while ($header = $header_s->fetch())
    {
      $inhalt .= $header["feld"].": ".$header["wert"]."\n";
    }
    $inhalt .= "\n";      
    if ($response["inhaltroh"] || $response["inhalt"])
    {
      $inhalt_s = $db->prepare ("select inhalt from inhalt where id=?");
      $inhalt_s->execute (array($response["inhaltroh"]? $response["inhaltroh"]:$response["inhalt"]));
      $inhalt .= $inhalt_s->fetchColumn();
    }
  }

  $select_s = $db->prepare("select geraet from aufzeichnung,verbindung where aufzeichnung.id=verbindung.aufzeichnung and verbindung.id=?");
  $select_s->execute(array(-$_GET["id"]));
  $geraetID = $select_s->fetchColumn();
  $select_s = $db->prepare ("select eigenschaft.wert from geraet,eigenschaft where geraet.id=eigenschaft.geraet and geraet.id=?");
  $select_s->execute(array($geraetID));
  $eigenschaften = $select_s->fetchAll(PDO::FETCH_COLUMN, 0);

  zeigeInhalt ($_GET["nr"], $inhalt, $eigenschaften);
}


?>

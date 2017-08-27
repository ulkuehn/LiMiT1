<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: include/archivieren.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to asynchronously archive data recorded in a session
//              the script generates a zip file containing
//              - a meta.xml file describing the session
//              - all data contained in the database table 'datei' (being
//                a collection of the paths and contents of all raw files
//                collected in a recording)
//              thus, the archive consists of the raw data produced by sslsplit,
//              tcpdump etc, meaning that a reimport must reprocess the data
//              while this reprocessing is time consuming, it guarantees that
//              the processed data is compatible with the database structure at
//              import time
//
//==============================================================================
//==============================================================================


require ("../include/constants.php");
require ("../include/configuration.php");
require ("../include/utility.php");
require ("../include/database.php");

$select_s = $db->prepare ("select * from aufzeichnung where id=?");
$select_s->execute (array($_GET["id"]));
if (($aufzeichnung = $select_s->fetch(PDO::FETCH_ASSOC)) == false)
{
  errorMsg ("Diese Aufzeichnung ist nicht vorhanden");
}
else
{
  $zip = new ZipArchive;
  if ($zip->open($export_file, file_exists($export_file)? ZipArchive::OVERWRITE : ZipArchive::CREATE) == false)
  {
    errorMsg ("Die Archivdatei konnte nicht geöffnet werden");
  }
  else
  {
    $xml = new SimpleXMLElement ("<meta/>");
    $xmlA = $xml->addChild("aufzeichnung");
    foreach ($aufzeichnung as $k => $v)
    { 
      $xmlA->addChild(htmlspecialchars($k), htmlspecialchars($v));
    }
    $select_s = $db->prepare ("select * from geraet where id=?");
    $select_s->execute (array($aufzeichnung["geraet"]));
    if (($geraet = $select_s->fetch(PDO::FETCH_ASSOC)) != false)
    {
      $xmlG = $xml->addChild("geraet");
      foreach ($geraet as $k => $v)
      { 
        $xmlG->addChild(htmlspecialchars($k), htmlspecialchars($v));
      }      
      $select_s = $db->prepare ("select * from eigenschaft where geraet=?");
      $select_s->execute (array($aufzeichnung["geraet"]));
      while ($eigenschaft = $select_s->fetch(PDO::FETCH_ASSOC))
      {
        $xmlE = $xml->addChild("eigenschaft");
        foreach ($eigenschaft as $k => $v)
        { 
          $xmlE->addChild(htmlspecialchars($k), htmlspecialchars($v));
        }      
      }
    }
    $zip->addFromString ($meta_file, $xml->asXML()); 

    $zip->addEmptyDir ($certificate_dir);
    $zip->addEmptyDir ($connection_dir);
    $select_s = $db->prepare ("select * from datei where aufzeichnung=?");
    $select_s->execute (array($_GET["id"]));
    while ($datei = $select_s->fetch())
    {
      if (substr ($datei["name"],0,1) == "/")
      {
        $datei["name"] = substr ($datei["name"], 1);
      }
      $zip->addFromString ($datei["name"], $datei["inhalt"]);
    }
    if ($zip->close() == false)
    {
      errorMsg ("Das Archiv konnte nicht erstellt werden");
    }
    else
    {
      echo "<p>Das Archiv wurde erstellt und kann jetzt exportiert werden.</p>";
    }
  }
}

?>

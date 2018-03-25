<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: ciphersuites.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kuehn
//
//       USAGE: php5 ciphersuites.php <dir> <ciphersuites>
//
// DESCRIPTION: this script converts a plain text file of ciphersuite 
//              information into corresponding data base table entries
//              ciphersuite info comes as csv with fields
//                Value, Description, DTLS-OK, Reference
//              source of ciphersuite info is
//              https://www.iana.org/assignments/tls-parameters/tls-parameters-4.csv
//              see also
//              https://www.iana.org/assignments/tls-parameters/tls-parameters.xhtml
//
//==============================================================================
//==============================================================================


$base = $argv[1];
$suites = $argv[2];

$_SERVER["DOCUMENT_ROOT"] = "$base/www"; 
require_once ($_SERVER["DOCUMENT_ROOT"]."/include/constants.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/include/configuration.php");
require_once ($_SERVER["DOCUMENT_ROOT"]."/include/connectDB.php");

$db->beginTransaction ();

$suites = fopen ("$base/$suites","r");

while (($line = fgets ($suites)) !== false)
{
  // Value,Description,DTLS-OK,Reference
  $line = trim ($line);

  if (preg_match("/^\"0x(..),0x(..)\",([A-Z0-9_]+),([YN]),\[RFC([0-9]+)\]/", $line, $m))
  {
    $id = hexdec ($m[1].$m[2]);
    $fullname = $m[3];
    $name = $m[3];
    $dtls = $m[4];
    $rfc = $m[5];
    
    $keyEx = NULL;
    $cipher = NULL;
    $mac = NULL;
    
    $select_s = $db->prepare("select * from keyExchange order by length(shortName) desc");
    $select_s->execute();
    while ($key = $select_s->fetch())
    {
      if (preg_match("/".$key["shortName"]."_WITH_(.*)/", $name, $m))
      {
        $keyEx = $key["id"];
        //echo "keyEx: ",$key["shortName"]," ($name)\n";
        $name = $m[1];
        break;
      }
    }
    
    $select_s = $db->prepare("select * from cipher order by length(shortName) desc");
    $select_s->execute();
    while ($ciph = $select_s->fetch())
    {
      if (preg_match("/".$ciph["shortName"]."(?:_(.*))?/", $name, $m))
      {
        $cipher = $ciph["id"];
        //echo "cipher: ",$ciph["shortName"]," ($name)\n";
        if (array_key_exists(1, $m))
        {
          $name = $m[1];
        }
        else
        {
          $name = "AEAD";
        }
        break;
      }
    }

    $select_s = $db->prepare("select * from mac");
    $select_s->execute();
    while ($ma = $select_s->fetch())
    {
      if (preg_match("/".$ma["shortName"]."$/", $name, $m))
      {
        $mac = $ma["id"];
        //echo "mac: ",$ma["shortName"]," ($name)\n";
        break;
      }
    }

    if (!is_null($keyEx) && !is_null($cipher) && !is_null($mac))
    {
      $insert_s = $db->prepare("insert into cipherSuite set id=?, name=?, rfc=?, dtls=?, keyExchange=?, cipher=?, mac=?");
      $insert_s->execute(array($id, $fullname, $rfc, $dtls, $keyEx, $cipher, $mac));
    }
    else
    {
      echo "??? $fullname: no",(is_null($keyEx)? " keyEx":""),(is_null($cipher)? " cipher":""),(is_null($mac)? " mac":""),"\n";
    }
  }
}

fclose ($suites);

$db->commit ();

?>

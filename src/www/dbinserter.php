<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: dbinserter.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to insert recorded data into the database
//              data flows that are intercepted by a LiMiT1 system are
//              recorded in regular files by the sniffing software
//              those files contain http logs, certificate and encryption
//              data and non-http logs (tcpdump pcap files) 
//              this script extracts structured information out of the
//              files and adds this to the appropriate database tables
//              the script is invoked when a new recording is started
//              by a user
//
//==============================================================================
//==============================================================================

# dies ist notwendig, da constants.php etc auf den $_SERVER-Wert aufsetzt
$_SERVER["DOCUMENT_ROOT"] = pathinfo(__FILE__)["dirname"]; 
require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");
require ("include/database.php");

function sigintHandler($signal)
{
  global $db, $data_dir, $aufzeichnungID, $session_file;
  
  logit ("Abbruchsignal erhalten");
  try
  {
    $db->commit ();
  }
  catch (Exception $e) 
  {
    logit ($e->getMessage());
  }

  logit ("vor beginTransaction");
  $db->beginTransaction ();
  dir2db ("$data_dir/$aufzeichnungID","");
  $db->commit ();
  logit ("nach commit");

  // delete workdir
  exec ("/bin/rm -rf $data_dir/$aufzeichnungID");

  // delete session file
  unlink ($session_file);
  
  exit;
}
declare(ticks = 1);
pcntl_signal(SIGINT, "sigintHandler");


$mtime = microtime();
$mtime = explode(" ",$mtime);
$starttime = $mtime[1] + $mtime[0];

$aufzeichnungID = $argv[1];
$localIP = $argv[2];

logit ("Start aufzeichnungID=$aufzeichnungID localIP=$localIP");


if (!is_dir ("$data_dir/$aufzeichnungID"))
{
  logit ("kein passendes Verzeichnis \"$data_dir/$aufzeichnungID\" gefunden");
  exit;
}


// Kommentare aus HTML-Dom extrahieren

function extractComments ($res, $node)
{
  if (is_null($node))
  {
    return $res;
  }
  
  if ($node->nodeType == XML_COMMENT_NODE && $node->nodeValue != "")
  {
    array_push ($res, trim($node->nodeValue));
  }
  if ($node->childNodes)
  {
    foreach ($node->childNodes as $child)
    {
      $res = array_merge ($res, extractComments (array(), $child));
    }
  }
  return $res;
}


// neuen Host einfügen oder ID eines bestehenden Hosts ermitteln

function insertHost ($name, $ip)
{
  global $db,$hostTab;

  $ipermittelt = false;
  $nameermittelt = false;
  
  if ($name == "" && $ip == "")
  {
    logit ("??? insertHost ohne name und ohne IP");
    return 0;
  }
  
  if ($name == "")
  {
    if (ip2long($ip) == false)
    {
      logit ("??? insertHost '$ip' keine IP-Adresse");
      return 0;
    }

    $name = gethostbyaddr($ip);
    $nameermittelt = true;
    
    if ($name == $ip || ip2long($name) != false)
    {
      logit ("kein Hostname für '$ip' ermittelbar");
      return 0;
    }
    
  }

  if ($ip == "" || ip2long($ip) == false)
  {
    $ip = gethostbyname ($name);
    $ipermittelt = true;
    
    if (ip2long($ip) == false)
    {
      logit ("keine IP für '$name' ermittelbar");
      return 0;
    }
  }
  
  if (isset($hostTab[$name][$ip]))
  {
    return $hostTab[$name][$ip];
  }
  else
  {
    $insert_s = $db->prepare ("insert into host set name=?, ip=inet_aton(?), ipermittelt=?, nameermittelt=?");
    $insert_s->execute (array($name, $ip, $ipermittelt, $nameermittelt));
    $hostID = $db->lastInsertId();
    $hostTab[$name][$ip] = $hostID;
    return $hostID;
  }    
}


// neues Zertifikat einfügen oder ID eines bestehenden Zertifikats ermitteln

function insertCert ($certinfos)
{
  global $db,$zertifikatTab;

  if (!isset ($certinfos["fingerprint"]) || is_null($certinfos["fingerprint"]))
  {
    return 0;
  }
  
  if (isset($zertifikatTab[$certinfos["fingerprint"]]))
  {
    return $zertifikatTab[$certinfos["fingerprint"]];
  }
  else
  {
    $insert_s = $db->prepare("insert into zertifikat set fingerprint=?, serial=?, issuer=?, subject=?, notbefore=str_to_date(?,'%M %e %T %Y'), notafter=str_to_date(?,'%M %e %T %Y'), names=?");
    $insert_s->execute(array($certinfos["fingerprint"], $certinfos["serial"], $certinfos["issuer"], $certinfos["subject"], $certinfos["notbefore"], $certinfos["notafter"], $certinfos["names"]));
    $certID = $db->lastInsertId();
    logit ("certID = $certID");
    $zertifikatTab[$certinfos["fingerprint"]] = $certID;
    return $certID;
  }    
}


// passende SSL-Infos aus Log-Datei lesen

function getSSLInfo ($aID,$src,$dst)
{
  global $data_dir,$connection_log;
  
  $sd = 0;
  $ssllog = fopen ("$data_dir/$aID/$connection_log", "r");
  while (($line = fgets ($ssllog)) !== false)
  {
    if ($line == "Source: $src\n")
    {
      $sd = 1;
    }
    else if ($line == "Destination: $dst\n")
    {
      if ($sd == 1)
      {
        $sd = 2;
        break;
      }
    }
    else
    {
      $sd = 0;
    }
  }
  
  if ($sd == 2)
  {
    $sslinfo = array ();
    while (($line = fgets ($ssllog)) !== false)
    {
      if (preg_match("/^([^:]*): ?(.*)/", $line, $m))
      {
        switch ($m[1])
        {
          case 'Version':
            $sslinfo["version"] = $m[2];
          break;
          case 'CipherID':
            $sslinfo["cipher"] = hexdec($m[2]);
          break;
          case 'Number of bits really used':
            $sslinfo["effBits"] = $m[2];
          break;
          case 'Number of bits for algorithm':
            $sslinfo["maxBits"] = $m[2];
          break;
          case 'SHA256 Fingerprint':
            $sslinfo["fingerprint"] = $m[2];
          break;
          case 'Certificate':
            $names = array ();

            while (($line = fgets ($ssllog)) !== false && preg_match("/^ +([^:]*):? ?(.*)/", $line, $mm))
            {
              switch ($mm[1])
              {
                case 'Serial Number':
                  if ($mm[2] != "")
                  {
                    $sslinfo["serial"] = $mm[2];
                  }
                  else
                  {
                    $sslinfo["serial"] = trim (fgets ($ssllog));
                  }
                break;
                case 'Issuer':
                  $sslinfo["issuer"] = $mm[2];
                break;
                case 'Subject':
                  $sslinfo["subject"] = $mm[2];
                  if (preg_match("/CN=(.+)/", $mm[2], $mmm))
                  {
                    $names[$mmm[1]] = 1;
                  }
                break;
                case 'Not Before':
                  $sslinfo["notbefore"] = $mm[2];
                break;
                case 'Not After ':
                  $sslinfo["notafter"] = $mm[2];
                break;
                case 'X509v3 Subject Alternative Name':
                  foreach (explode (",", trim (fgets ($ssllog))) as $dns)
                  {
                    if (preg_match("/DNS:(.+)/", $dns, $mmm))
                    {
                      $names[$mmm[1]] = 1;
                    }
                  }
                break;
              }
            }
            
            $sslinfo["names"] = implode (",", array_keys($names));
            fclose ($ssllog);
            return $sslinfo;
          break;
        }
      }
    }
  }

  fclose ($ssllog);
  return NULL;
}


// Verbindungsnummer ermitteln

function verbindungsNummer ($zeit, $aufzeichnungID)
{
  global $db;
  
  $select_s = $db->prepare ("select nr from verbindung where zeit<=? and aufzeichnung=? order by nr desc limit 1");
  $select_s->execute(array($zeit,$aufzeichnungID));
  if (($nr = $select_s->fetchColumn()) == false)
  {
    $nr = 0;
  }
  $nr++;
  logit ("Nummer für $zeit = $nr");  

  $update_s = $db->prepare ("update verbindung set nr=nr+1 where nr>=? and aufzeichnung=?");
  $update_s->execute (array($nr,$aufzeichnungID));

  return $nr;
}


// neue Verbindung in DB einfügen

function insertVerbindung ($verbindung, $aufzeichnungID)
{
  global $db, $temp_dir, $data_dir, $connection_dir, $image_file, $cookieTab;
  
  # format is: yyyymmddThhmmssZ-sip1.sip2.sip3.sip4,sport-dip1.dip2.dip3.dip4,dport.log
  #               1 2 3  4 5 6                    7     8                   9    10
  preg_match ("/^([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2})([0-9]{2})([0-9]{2})Z-([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}),([0-9]{1,5})-([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}),([0-9]{1,5})\.log$/", $verbindung, $f);
  
  $zeit = strftime ("%Y-%m-%d %H:%M:%S", strtotime("$f[1]-$f[2]-$f[3] $f[4]:$f[5]:$f[6] GMT"));
  $inhalt = file_get_contents ("$data_dir/$aufzeichnungID/$connection_dir/$verbindung");
  $dstip = $f[9];
  
  # is this http(s) or some other protocol?
  $http = false;
  foreach (explode(PHP_EOL, $inhalt) as $line)
  {
    # http request line = method SP request-target SP HTTP-version CRLF
    if (preg_match ("/^([A-Z]+)[[:space:]]+([[:graph:]]+)[[:space:]]+(HTTP\/[0-9]\.[0-9])$/i", trim($line)))
    {
      $http = true;
      break;
    }
  }
  
  # get SSL infos (NULL if none)
  $sslinfo = getSSLInfo ($aufzeichnungID, $f[7]."_".$f[8], $f[9]."_".$f[10]);

  $vTyp = $http? (is_null($sslinfo)? "http":"https") : (is_null($sslinfo)? "tcp":"ssl");
  $insert_s = $db->prepare ("insert into verbindung set nr=?, aufzeichnung=?, zeit=?, vonport=?, host=?, ip=inet_aton(?), anport=?, laenge=?, typ=?");
  $insert_s->execute (array(verbindungsNummer ($zeit,$aufzeichnungID), $aufzeichnungID, $zeit, $f[8], insertHost("",$dstip), $dstip, $f[10], strlen($inhalt), $vTyp));
  $verbindungID = $db->lastInsertId();
  logit ("$verbindung als Typ $vTyp mit ID $verbindungID eingefuegt");
  
  
  # process http(s)
  if ($http)
  {
    $isRequest = false;
    $isResponse = false;
    $requestID = 0;
    $contentLength = -1;
    $contentEncoding = "";  
    $contentType = "";
    $contentTypeAdd = "";
    $date = "";
    $host = "";
    $useragent = "";
    $chunked = false;

    $daten = fopen ("$data_dir/$aufzeichnungID/$connection_dir/$verbindung", "r");  
    while (($line = fgets ($daten)) !== false)           
    {
      $line = trim ($line);
      
      # request line = method SP request-target SP HTTP-version CRLF
      if (preg_match ("/^([A-Z]+)[[:space:]]+([[:graph:]]+)[[:space:]]+(HTTP\/[0-9]\.[0-9])$/i", $line, $rline))
      {
        $isRequest = true;
        # inhalt, mime, mimeadd werden nachgeliefert
        $insert_s = $db->prepare ("insert into request set verbindung=?, aufzeichnung=?, methode=?, uri=?, version=?");
        $insert_s->execute (array($verbindungID, $aufzeichnungID, $rline[1], $rline[2], $rline[3]));
        $requestID = $db->lastInsertId();
        continue;
      }
      
      # status-line = HTTP-version SP status-code SP reason-phrase CRLF
      if (preg_match ("/^(HTTP\/[0-9]\.[0-9])[[:space:]]+([0-9]+)[[:space:]]+(.+)$/i", $line, $sline))
      {
        $isResponse = true;
        if (!$requestID)
        {
          logit ("??? Response ohne Request");
        }
        else
        {
          # inhalt, mime, mimeadd werden nachgeliefert
          $insert_s = $db->prepare ("insert into response set request=?, verbindung=?, aufzeichnung=?, version=?, status=?, statustext=?");
          $insert_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $sline[1], $sline[2], $sline[3]));
          $responseID = $db->lastInsertId();
        }
        continue;
      }
      
      if (!$isRequest && !$isResponse)
      {
        logit ("??? weder Request noch Response erkannt");
        continue;
      }
      
      # header-field = field-name ":" *( SP / HTAB ) field-value *( SP / HTAB )
      if (preg_match ("/^([[:graph:]]+)[[:space:]]*:[[:space:]]*(.*)$/", $line, $hline))
      {
        $fname = $hline[1];
        $fwert = trim($hline[2]);
        $insert_s = $db->prepare ("insert into header set request=?, verbindung=?, aufzeichnung=?, response=?, feld=?, wert=?");
        $insert_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $isResponse, $fname, $fwert));
        
        switch (strtolower($fname))
        {
          case "host":
            $host = explode (":", $fwert)[0]; // there might be a port specification
            break;
            
          case "user-agent":
            if ($useragent != "" && $useragent != $fwert)
            {
              logit ("??? differierende User-Agents '$useragent' und '$fwert'");
            }
            else
            {
              $useragent = $fwert;
            }
            break;
            
          case "date":
            $date = strtotime ($fwert);
            break;
            
          case "content-length":
            $contentLength = $fwert;
            break;
            
          case "content-type":
            $ct = explode (";", $fwert, 2);
            $contentType = $ct[0];
            if (count($ct)>1)
            {
              $contentTypeAdd = trim($ct[1]);
            }
            break;
            
          case "content-encoding":
            $contentEncoding = strtolower($fwert);
            break;
            
          case "transfer-encoding":
            if (strtolower($fwert) == "chunked")
            {
              $chunked = true;
            }
            break;
            
          case "cookie":
            foreach (explode (";", $fwert) as $c)
            {
              if (preg_match ("/^([^=]+)=(.*)$/", trim($c), $nv))
              {
                $cookieID = NULL;
                if (array_key_exists($nv[1],$cookieTab))
                {
                  foreach ($cookieTab[$nv[1]] as $site => $cid)
                  {
                    if (stripos($host."#",$site."#") !== false)
                    {
                      $cookieID = $cid;
                      break;
                    }
                  }
                }
                if (is_null($cookieID))
                {
                  $insert_s = $db->prepare("insert into cookie set name=?, site=?");
                  $insert_s->execute(array($nv[1], $host));
                  $cookieID = $db->lastInsertId();
                  $cookieTab[$nv[1]][$host] = $cookieID;
                }
                $insert_s = $db->prepare("insert into sendcookie set cookie=?, request=?, verbindung=?, aufzeichnung=?, wert=?");
                $insert_s->execute(array($cookieID, $requestID, $verbindungID, $aufzeichnungID, $nv[2]));
              }
            }
            break;
            
          case "set-cookie":
          case "set-cookie2":
            $httponly = 0;
            $secure = 0;
            $name = "";
            $domain = "";
            $path = "";
            $comment = "";
            $expires = NULL;
            $valid = NULL;
            foreach (explode (";", $fwert) as $c)
            {
              $c = trim($c);                    
              switch (strtolower($c))
              {
                case "":
                  break;
                  
                case "secure":
                  $secure = 1;
                  break;
                  
                case "httponly":
                  $httponly = 1;
                  break;
                  
                default:
                  preg_match ("/^([^=]+)=(.*)$/", $c, $nv);
                  //echo "\t$c $nv[0], $nv[1] $nv[2]\n";
                  if ($name == "")
                  {
                    $name = $nv[1];
                    $wert = $nv[2];
                  }
                  else
                  {
                    switch (strtolower($nv[1]))
                    {
                      case "domain":
                        $domain = $nv[2];
                        break;
                        
                      case "path":
                        $path = $nv[2];
                        break;
                        
                      case "comment":
                        $comment = $nv[2];
                        break;
                        
                      case "expires":
                        $expires = strftime ("%Y-%m-%d %H:%M:%S", strtotime ($nv[2]));
                        break;
                        
                      case "max-age":
                        $valid = $nv[2];
                        break;
                    }
                  }
              }
            }
            
            $cookieSite = $domain==""? $host : $domain;
            
            $cookieID = NULL;
            if (array_key_exists($name,$cookieTab))
            {
              foreach ($cookieTab[$name] as $site => $cid)
              {
                if (stripos($host."#",$site."#") !== false)
                {
                  $cookieID = $cid;
                  if (stripos($site."#",$cookieSite."#") != false)
                  {
                    $update_s = $db->prepare ("update cookie set site=? where id=?");
                    $update_s->execute (array($cookieSite,$cookieID));
                    unset ($cookieTab[$name][$site]);
                    $cookieTab[$name][$cookieSite] = $cookieID;
                  }
                  break;
                }
              }
            }
            if (is_null($cookieID))
            {
              $insert_s = $db->prepare("insert into cookie set name=?, site=?");
              $insert_s->execute(array($name, $cookieSite));
              $cookieID = $db->lastInsertId();
              $cookieTab[$name][$cookieSite] = $cookieID;
            }

            $insert_s = $db->prepare("insert into setcookie set cookie=?, request=?, verbindung=?, aufzeichnung=?, wert=?, secure=?, httponly=?, domain=?, path=?, comment=?, expires=?, valid=?");
            $insert_s->execute(array($cookieID, $requestID, $verbindungID, $aufzeichnungID, $wert, $secure, $httponly, $domain, $path, $comment, $expires, $valid));                  
            break;
        
        } # switch

        continue;
      } # header match
    
      # process message body
      if ($line == "")
      {
        $inhaltroh = "";
        $inhalt = "";
        $inhaltrohID = 0;
        $inhaltID = 0;
        
        if (!$chunked)
        {
          if ($contentLength > 0)
          {
            $inhaltroh = fread ($daten, $contentLength);
          }
          else if ($contentLength == -1 && $contentType != "" && $isResponse)
          {
            do
            {
              $inhaltroh .= fread ($daten, 1000);
            }
            while (! feof ($daten));
          }
          $inhalt = $inhaltroh;
        }
        else
        {
          $contentLength = 0;
          do
          {
            $iamhere = ftell ($daten);
            $line = fgets ($daten);
            $inhaltroh .= $line;         
            $line = trim ($line);
            if (preg_match ("/^([0-9a-f]+)$/i", $line, $chl))
            {
              $chunkLength = hexdec ($chl[1]);
              if ($chunkLength > 0)
              {
                $chunk = fread ($daten, $chunkLength);
                $inhaltroh .= $chunk;
                $inhalt .= $chunk;
                $contentLength += $chunkLength;
              }
              $line = fgets ($daten);  // read CR LF
              $inhaltroh .= $line;
            }
            else
            {
              $chunkLength = 0;
              fseek ($daten, $iamhere); // undo the last fgets
            }
          }
          while ($chunkLength > 0 && !feof($daten));
        }
        
        if ($inhalt != "")
        {
          switch (strtolower($contentEncoding))
          {
            case "gzip":
              if (($unzip = gzdecode ($inhalt)) != false)
              {
                $inhalt = $unzip;
              }
              break;
              
            case "deflate":
              if (($inflate = gzcompress ($inhalt)) != false)
              {
                $inhalt = $inflate;
              }
              break;
          }
          
          if (preg_match ("/^application.*proto/i", $contentType))
          {
            $tname = "/tmp/".getmypid();
            $tfile = fopen ($tname, "w");
            fwrite ($tfile, $inhalt);
            fclose ($tfile);
            $protoc = "";
            exec ("/bin/cat $tname | /usr/bin/protoc --decode_raw 2>/dev/null", $protoc, $rv);
            unlink ($tname);
            if ($rv == 0)
            {
              $inhalt = implode ("\n",$protoc);
            }
          }

          else if (preg_match ("/\/json/i", $contentType))
          {
            $inhalt = json_encode (json_decode($inhalt), JSON_PRETTY_PRINT);
          }
          
          else if (preg_match ("/utf-8/i", $contentTypeAdd) && ! mb_check_encoding ($inhalt,"UTF-8"))
          {
            $inhalt = utf8_encode ($inhalt);
          }
        }
        
        if ($inhaltroh != "" && $inhalt != $inhaltroh)
        {
          $insert_s = $db->prepare ("insert into inhalt set typ=?, referenz=?, verbindung=?, aufzeichnung=?, inhalt=?");              
          $insert_s->execute (array($isRequest? "requestroh":"responseroh", $requestID, $verbindungID, $aufzeichnungID, $inhaltroh));
          $inhaltrohID = $db->lastInsertId();
        }
        if ($inhalt != "")
        {
          $insert_s = $db->prepare ("insert into inhalt set typ=?, referenz=?, verbindung=?, aufzeichnung=?, inhalt=?");              
          $insert_s->execute (array($isRequest? "request":"response", $requestID, $verbindungID, $aufzeichnungID, $inhalt));
          $inhaltID = $db->lastInsertId();
        }
        
        if ($isRequest)
        {
          $update_s = $db->prepare ("update request set inhaltroh=?, inhalt=?, mime=?, mimeadd=? where id=?");
          $update_s->execute (array($inhaltrohID, $inhaltID, $contentType, $contentTypeAdd, $requestID));
        }
        if ($isResponse)
        {
          $update_s = $db->prepare ("update response set inhaltroh=?, inhalt=?, mime=?, mimeadd=? where id=?");
          $update_s->execute (array($inhaltrohID, $inhaltID, $contentType, $contentTypeAdd, $responseID));
        }
        
        # Metadaten
        switch (strtolower($contentType))
        {
          case "image/jpg":
          case "image/jpeg":
          case "image/png":
          case "image/gif":
          case "image/tiff":
          case "image/bmp":
            if ($inhalt != "")
            {
              $ifile = fopen ($image_file, "w");
              fwrite ($ifile, $inhalt);
              fclose ($ifile);
              
              $exiv2 = array();
              exec ("/usr/bin/exiv2 -ps pr $image_file 2>/dev/null", $exiv2, $rv);
              foreach ($exiv2 as $line)
              {
                if (preg_match ("/Image Size *: *([0-9]+) *x *([0-9]+)/i", $line, $m))
                {
                  $meta_s = $db->prepare ("insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?");
                  $meta_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $isResponse, strtolower($contentType), "Dimension", $m[1]." x ".$m[2]));
                }
              }

              $exiv2 = array();
              exec ("/usr/bin/exiv2 -Pkt pr $image_file 2>/dev/null", $exiv2, $rv);
              foreach ($exiv2 as $line)
              {
                if (preg_match ("/^ *([[:graph:]]+) +(.+)$/", $line, $m))
                {
                  $meta_s = $db->prepare ("insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?");
                  $meta_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $isResponse, strtolower($contentType), $m[1], trim($m[2])));
                }
              }
              
              $exiv2 = array();
              $comment = array();
              exec ("/usr/bin/exiv2 -pc pr $image_file 2>/dev/null", $exiv2, $rv);
              foreach ($exiv2 as $line)
              {
                if (preg_match ("/^JPEG comment:(.+)$/i", $line, $m))
                {
                  array_push ($comment, trim($m[1]));
                }
              }
              if (count($comment))
              {
                $meta_s = $db->prepare ("insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?");
                $meta_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $isResponse, strtolower($contentType), "JPEG-Kommentare", implode("\n",$comment)));
              }
            }
            break;
            
          case "text/html":          
            $doc = new DOMDocument();
            $doc->loadHTML ($inhalt);
            
            # Titel
            $titel = array();
            foreach ($doc->getElementsByTagName("title") as $wert)
            {
              if ($wert->nodeValue != "")
              {
                array_push ($titel, $wert->nodeValue);
              }
            }
            if (count($titel))
            {
              $meta_s = $db->prepare ("insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?");
              $meta_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $isResponse, strtolower($contentType), "Titel", implode("\n",$titel)));
            }
            
            # Kommentare
            $comment = extractComments (array(), $doc->documentElement);
            if (count($comment))
            {
              $meta_s = $db->prepare ("insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?");
              $meta_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $isResponse, strtolower($contentType), "Kommentare", implode("\n",$comment)));
            }
            
            # Meta-Tags
            $meta = array();
            foreach ($doc->getElementsByTagName("meta") as $wert)
            {
              if ($wert->attributes)
              {
                $name = "";
                $content = "";
                foreach ($wert->attributes as $aname => $attr)
                {
                  if ($aname == "content")
                  {
                    $content = $attr->nodeValue;
                  }
                  else
                  {
                    $name = $attr->nodeValue;
                  }
                }
                if ($name != "")
                {
                  array_push ($meta, $name." = ".$content);
                }
              }
            }
            if (count($meta))
            {
              $meta_s = $db->prepare ("insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?");
              $meta_s->execute (array($requestID, $verbindungID, $aufzeichnungID, $isResponse, strtolower($contentType), "Meta-Tags", implode("\n",$meta)));
            }
            
            break;
        }
          
        $isRequest = false;
        $isResponse = false;              
        $contentLength = -1;
        $contentType = "";
        $contentTypeAdd = "";
        $contentEncoding = "";
        $chunked = false;
      } # message body
      
    } # while $line = fgets...

    if (is_null($sslinfo))
    {
      $insert_s = $db->prepare ("insert into http set aufzeichnung=?, verbindung=?, useragent=?, host=?");
      $insert_s->execute (array($aufzeichnungID, $verbindungID, $useragent, insertHost($host,$dstip)));
    }
    else
    {
      $insert_s = $db->prepare ("insert into https set aufzeichnung=?, verbindung=?, useragent=?, host=?, sslversion=?, ciphersuite=?, effBits=?, maxBits=?, zertifikat=?");
      $insert_s->execute (array($aufzeichnungID, $verbindungID, $useragent, insertHost($host,$dstip), $sslinfo["version"], $sslinfo["cipher"], $sslinfo["effBits"], $sslinfo["maxBits"], insertCert ($sslinfo)));
    }
    
    fclose ($daten);

    # update setcookie with date header info
    $select_s = $db->prepare ("select setcookie.id as id, setcookie.expires as expires, setcookie.valid as valid from setcookie,request where request.id=setcookie.request and request.verbindung=?");
    $select_s->execute (array($verbindungID));
    while ($setcookie = $select_s->fetch())
    {
      if (is_null($setcookie["valid"]) && !is_null($setcookie["expires"]))
      {
        $date_s = $db->prepare ("select header.wert as date from header,request where header.feld='date' and request.id=header.request and request.verbindung=?");
        $date_s->execute (array($verbindungID));
        if ($date = $date_s->fetch())
        {
          $update_s = $db->prepare ("update setcookie set valid=? where id=?");
          $update_s->execute (array(strtotime($setcookie["expires"])-strtotime($date["date"]),$setcookie["id"]));
        }
      }
    }

  } # if $http
  
  else
  {
    if ($inhalt != "")
    {
      $insert_s = $db->prepare ("insert into inhalt set typ='tcp', referenz=?, verbindung=?, aufzeichnung=?, inhalt=?");              
      $insert_s->execute (array($verbindungID, $verbindungID, $aufzeichnungID, $inhalt));
    }

    if (!is_null($sslinfo))
    {
      $insert_s = $db->prepare ("insert into ssltls set aufzeichnung=?, verbindung=?, sslversion=?, ciphersuite=?, effBits=?, maxBits=?, zertifikat=?");
      $insert_s->execute (array($aufzeichnungID, $verbindungID, $sslinfo["version"], $sslinfo["cipher"], $sslinfo["effBits"], $sslinfo["maxBits"], insertCert ($sslinfo)));
    }    
  }

  return $verbindungID;
}


function logit ($logthis)
{
  global $starttime;
  
  if (!$develop_mode)
  {
    return;
  }
  
  static $pretime = NULL;
  
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $endtime = $mtime;
  $totaltime = floor(($endtime - $starttime)*1000);

  if ($pretime != NULL)
  {
    $difftime = floor(($endtime - $pretime)*1000);
  }
  else
  {
    $difftime = "";
  }
  $pretime = $endtime;
  
  $totaltime = substr ("           $totaltime ms",-10);
  $difftime = substr ("           $difftime ms",-10);
  echo "$totaltime$difftime -- $logthis\n";
}


// wichtige DB-Tabellen zur Beschleunigung einlesen
$verbindungTab = array ();
/*
$select_s = $db->prepare ("select nr,zeit from verbindung where aufzeichnung=?");
$select_s->execute (array($aufzeichnungID));
while ($v = $select_s->fetch())
{
  $verbindungTab[$v["zeit"]][$v["vonport"]] = $v["id"];
}
*/
$hostTab = array();
$select_s = $db->prepare ("select id,name,inet_ntoa(ip) as _ip from host");
$select_s->execute ();
while ($h = $select_s->fetch())
{
  $hostTab[$h["name"]][$h["_ip"]] = $h["id"];
}

$zertifikatTab = array();
$select_s = $db->prepare ("select id,fingerprint from zertifikat");
$select_s->execute ();
while ($z = $select_s->fetch())
{
  $zertifikatTab[$z["fingerprint"]] = $z["id"];
}

$cookieTab = array();
$select_s = $db->prepare ("select id,name,site from cookie");
$select_s->execute ();
while ($c = $select_s->fetch())
{
  $cookieTab[$c["name"]][$c["site"]] = $c["id"];
}


// Verbindungen übernehmen, solange Aufzeichnung nicht beendet
do
{
  $select_s = $db->prepare ("select * from aufzeichnung where id=?");
  $select_s->execute(array($aufzeichnungID));

  if (($aufzeichnung = $select_s->fetch()) == true)
  {
    if (!is_dir ("$temp_dir/$aufzeichnungID"))
    {
      mkdir ("$temp_dir/$aufzeichnungID");
    }
    if (!is_dir ("$temp_dir/$aufzeichnungID/$connection_dir"))
    {
      mkdir ("$temp_dir/$aufzeichnungID/$connection_dir");
    }
    
    logit ("vor beginTransaction");
    $db->beginTransaction ();

    if (! $aufzeichnung["ende"])
    {
      logit ("Aufzeichnung $aufzeichnungID ist offen");
    }
    
    do
    {
      $added = 0;
      foreach (scandir("$data_dir/$aufzeichnungID/$connection_dir") as $verbindung)
      {
        if (!is_file("$data_dir/$aufzeichnungID/$connection_dir/$verbindung"))
        {
          logit ("$verbindung ist keine Datei (Verzeichnis?)");
          continue;
        }
        
        if (array_key_exists($verbindung,$verbindungTab))
        {
          logit ("$verbindung bereits in Aufzeichnung $aufzeichnungID");
          continue;    
        }
        
        system ("/usr/bin/lsof $data_dir/$aufzeichnungID/$connection_dir/$verbindung", $lsof);
        if (!$lsof)
        {
          logit ("$verbindung ist noch geoeffnet");
          continue;
        }
        
        logit ("$verbindung nicht in Aufzeichnung $aufzeichnungID");
        $added++;
        
        $verbindungID = insertVerbindung ($verbindung, $aufzeichnungID);
        $verbindungTab[$verbindung] = $verbindungID;
        $done = fopen ("$temp_dir/$aufzeichnungID/$connection_dir/$verbindung", "w");
        fclose ($done);
      }
    }
    while ($added);
    
    $db->commit ();
    logit ("nach commit");
  }
  
  sleep (2);
}
while (! $aufzeichnung["ende"]);



// process tcpdump of udp protocol packets

if (is_readable("$data_dir/$aufzeichnungID/$tcpdump_pcap"))
{
  logit ("vor beginTransaction");
  $db->beginTransaction ();
  
  $dump = fopen ("$data_dir/$aufzeichnungID/$tcpdump_pcap","rb");
  $timestamp = array ();
  $datas = array ();
  
  # global header: 24 Bytes, irrelevant here
  fread ($dump, 24);
  
  # packets
  while (!feof($dump) && ($packetHeader = fread ($dump, 16)))
  { 
    # packet header: 16 Bytes, timestamp, snarf len
    $ph = unpack ("VtsSec/VtsUsec/VsnarfLen", $packetHeader);
    $zeit = strftime("%Y-%m-%d %H:%M:%S",$ph["tsSec"]);
    $toRead = $ph["snarfLen"];
    
    # mac header: 14 Bytes, irrelevant here
    $macHeader =  fread ($dump, 14);
    $toRead -= 14;
    
    # ip header: 20 Bytes, header length, protocol, source ip, dest ip
    $ipHeader = fread ($dump, 20);
    $ih = unpack ("CipVhl/C/v/v/v/C/CipProto/v/NsrcIp/NdstIp", $ipHeader);
    $srcip = long2ip($ih['srcIp']);
    $dstip = long2ip($ih['dstIp']);
    $iphl = ($ih["ipVhl"]&15)*4;
    if ($iphl>20)
    {
      # skip read options, if any
      $ipOptions = fread ($dump, $iphl-20);
    }
    $toRead -= $iphl;
    logit ("Paket $srcip --> $dstip");
    
    if ($ih["ipProto"] == 17) # UDP
    {
      $udpHeader = fread ($dump, 8);
      $toRead -= 8;
      $u = unpack ("nsrcport/ndstport/nudplen", $udpHeader);
      $srcport = $u["srcport"];
      $dstport = $u["dstport"];
      logit ("UDP-Paket $srcip:$srcport --> $dstip:$dstport");
      
      $data = "";      
      if ($toRead)
      {
        $data = fread ($dump, $toRead);
      }
      
      $key = "";
      if ($srcip == $localIP)
      {
        $key = "$srcport:$dstip:$dstport";
        $out = true;
      }
      if ($dstip == $localIP)
      {
        $key = "$dstport:$srcip:$srcport";
        $out = false;
      }
      if ($key != "")
      {
        if (!array_key_exists($key,$timestamp))
        {
          $timestamp[$key] = $zeit;
        }
        if (!array_key_exists($key,$datas))
        {
          $datas[$key][true] = "";
          $datas[$key][false] = "";
        }
        $datas[$key][$out] .= $data;
      }
    }
    else
    {
      if ($toRead)
      {
        fread ($dump, $toRead);
      }
      logit ("??? unerwartetes Paket mit Protokollnummer '{$ih["ipProto"]}' gefunden");
    }
  }
  
  fclose ($dump);


  foreach ($timestamp as $key=>$zeit)
  {
    list ($srcport,$dstip,$dstport) = explode (":",$key);
    
    $insert_s = $db->prepare ("insert into verbindung set nr=?, aufzeichnung=?, zeit=?, vonport=?, anport=?, ip=inet_aton(?), host=?, laenge=?, typ='udp'");
    $insert_s->execute (array(verbindungsNummer($zeit,$aufzeichnungID), $aufzeichnungID, $zeit, $srcport, $dstport, $dstip, insertHost("",$dstip), strlen($datas[$key][true])+strlen($datas[$key][false]) ));
    $verbindungID = $db->lastInsertId();
    
    if ($datas[$key][true] != "")
    {
      $insert_s = $db->prepare ("insert into inhalt set typ='udpsend', referenz=?, verbindung=?, aufzeichnung=?, inhalt=?");
      $insert_s->execute (array($verbindungID, $verbindungID, $aufzeichnungID, $datas[$key][true]));
    }

    if ($datas[$key][false] != "")
    {
      $insert_s = $db->prepare ("insert into inhalt set typ='udprcv', referenz=?, verbindung=?, aufzeichnung=?, inhalt=?");
      $insert_s->execute (array($verbindungID, $verbindungID, $aufzeichnungID, $datas[$key][false]));
    }
  }
  
  $db->commit ();
  logit ("nach commit");
}


// Dateien in Datenbank übernehmen

function dir2db ($prefix,$dir)
{
  global $db,$aufzeichnungID;
  
  logit ("dir2b ($prefix,$dir)");
  foreach (scandir("$prefix$dir") as $k => $name)
  {
    if ($name != "." && $name != "..")
    {
      if (is_dir("$prefix$dir/$name"))
      {
        dir2db ($prefix,"$dir/$name");
      }
      else
      {
        $insert_s = $db->prepare ("insert into datei set aufzeichnung=?, name=?, inhalt=?");
        $insert_s->execute (array($aufzeichnungID, "$dir/$name", file_get_contents("$prefix$dir/$name")));
        logit ("Datei $prefix$dir/$name eingefügt");
      }
    }
  }
}

logit ("vor beginTransaction");
$db->beginTransaction ();
dir2db ("$data_dir/$aufzeichnungID","");
$db->commit ();
logit ("nach commit");

// delete workdir
exec ("/bin/rm -rf $data_dir/$aufzeichnungID");

// delete session file
unlink ($session_file);


?>

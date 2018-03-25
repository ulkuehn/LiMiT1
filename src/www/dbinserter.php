<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file dbinserter.php
 * 
 * used to insert recorded data into the database:
 * data flows that are intercepted by a LiMiT1 system are recorded in regular files by the sniffing software
 * those files contain http logs, certificate and encryption data and non-http logs (tcpdump pcap files)
 * this script extracts structured information out of the files and adds this to the appropriate database tables
 * the script is invoked when a new recording is started by a user  
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
/*
 * we need this assignment, as some included scripts rely on a correctly set document root
 * but as this script is called by system, not web server the property is not set autmatically
 */
$_SERVER[ "DOCUMENT_ROOT" ] = pathinfo ( __FILE__ )[ "dirname" ];

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
 * do some final stuff before exiting
 */
function doFinalThingsAndCleanup ()
{
  global $db, $__, $data_dir, $recordingID, $temp_dir;

  /*
   * save raw file data for possible later reimport
   */
  logThis ( _ ( "Rohdaten sichern: vor beginTransaction" ) );
  $db->beginTransaction ();
  insertFiles ( "$data_dir/$recordingID",
                "" );
  $db->commit ();
  logThis ( _ ( "Rohdaten sichern: nach commit" ) );

  /*
   * delete working dir
   */
  exec ( "/bin/rm -rf $data_dir/$recordingID" );

  /*
   * delete session file
   */
  unlink ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] );
}


/**
 * interrupt handler function needed for graceful termination required by user
 * such a termination is not due to an error but done on purpose and thus must take care that the db is filled correctly
 * 
 * @param int $signalNumber POSIX number of signal received
 */
function sigintHandler ( $signalNumber )
{
  global $db;

  logThis ( _ ( "Abbruchsignal erhalten" ) );

  /*
   * maybe we are within a transaction, so we try to commit (and will get an exception if no transaction started)
   */
  try
  {
    $db->commit ();
  } catch (Exception $e)
  {
    logThis ( _ ( "commit exception: " ) . $e->getMessage () );
  }

  doFinalThingsAndCleanup ();
  exit;
}


/**
 * insert all regular files in a dir tree into database
 * 
 * @param string $fileSystemPrefix file system prefix relevant for accessing files in file system but not relevant for db name
 * @param type $dirName dir name relevant for file system an db nama alike
 */
function insertFiles ( $fileSystemPrefix,
                       $dirName )
{
  global $db, $recordingID;

  /*
   * walk through dir content
   */
  foreach ( scandir ( "$fileSystemPrefix$dirName" ) as $k => $fileName )
  {
    if ( $fileName != "." && $fileName != ".." )
    {
      /*
       * for dirs in dir do a recursive call
       */
      if ( is_dir ( "$fileSystemPrefix$dirName/$fileName" ) )
      {
        insertFiles ( $fileSystemPrefix,
                      "$dirName/$fileName" );
      }
      /*
       * for regular files do an insert
       */
      else
      {
        $insertFileStatement = $db->prepare ( "insert into datei set aufzeichnung=?, name=?, inhalt=?" );
        $insertFileStatement->execute ( array (
          $recordingID,
          "$dirName/$fileName",
          file_get_contents ( "$fileSystemPrefix$dirName/$fileName" ) ) );
        logThis ( _ ( "Datei in DB eingefügt: " ) . $fileSystemPrefix . $dirName . "/" . $fileName );
      }
    }
  }
}


/**
 * extract comments from HTML DOM
 * 
 * @param array $commentValues list of comment node values where comments of $node node should be added
 * @param DOMNode $node node to recursively extract comments from
 * @return array updated list of comment node values
 */
function extractCommentsFromDOM ( $commentValues,
                                  $node )
{
  if ( is_null ( $node ) )
  {
    return $commentValues;
  }

  /*
   * (non empty) comment node right away: add value to list
   */
  if ( $node->nodeType == XML_COMMENT_NODE && $node->nodeValue != "" )
  {
    array_push ( $commentValues,
                 trim ( $node->nodeValue ) );
  }

  /*
   * walk down along DOM structure
   */
  foreach ( $node->childNodes as $childNode )
  {
    $commentValues = array_merge ( $commentValues,
                                   extractCommentsFromDOM ( array (),
                                                            $childNode ) );
  }

  return $commentValues;
}


/**
 * insert a host into db if not yet there or just return its id if cached
 * 
 * @param string $hostName name of the host
 * @param string $hostIP ip address of host
 * @return int db id of host
 */
function insertHost ( $hostName,
                      $hostIP )
{
  global $db, $hostTableCache;

  $ipNotGivenButResolved = false;
  $nameNotGivenButResolved = false;

  /*
   * we need at least a name or an ip
   */
  if ( $hostName == "" && $hostIP == "" )
  {
    logThis ( _ ( "??? insertHost ohne Name und ohne IP" ) );
    return 0;
  }

  /*
   * just IP given
   */
  if ( $hostName == "" )
  {
    /*
     * IP not valid
     */
    if ( ip2long ( $hostIP ) == false )
    {
      logThis ( _ ( "??? insertHost \"$hostIP\" keine IP-Adresse" ) );
      return 0;
    }

    /*
     * do name lookup
     */
    $hostName = gethostbyaddr ( $hostIP );
    $nameNotGivenButResolved = true;

    /*
     * name lookup failed
     */
    if ( $hostName == $hostIP || ip2long ( $hostName ) != false )
    {
      logThis ( _ ( "kein Hostname für \"$hostIP\" ermittelbar (Ergebnis ist \"$hostName\")" ) );
      return 0;
    }
  }

  /*
   * no IP or invalid IP given
   */
  if ( $hostIP == "" || ip2long ( $hostIP ) == false )
  {
    /*
     * do IP resolve
     */
    $hostIP = gethostbyname ( $hostName );
    $ipNotGivenButResolved = true;

    /*
     * IP resolve failed
     */
    if ( ip2long ( $hostIP ) == false )
    {
      logThis ( _ ( "keine IP für \"$hostName\" ermittelbar (Ergebnis ist \"$hostIP\")" ) );
      return 0;
    }
  }

  /*
   * cached
   */
  if ( isset ( $hostTableCache[ $hostName ][ $hostIP ] ) )
  {
    return $hostTableCache[ $hostName ][ $hostIP ];
  }
  /*
   * not cached
   */
  else
  {
    $insertHostStatement = $db->prepare ( "insert into host set name=?, ip=inet_aton(?), ipermittelt=?, nameermittelt=?" );
    $insertHostStatement->execute ( array (
      $hostName,
      $hostIP,
      $ipNotGivenButResolved,
      $nameNotGivenButResolved ) );
    $hostID = $db->lastInsertId ();
    logThis ( _ ( "neuer Host eingefügt: $hostName / $hostIP = $hostID" ) );
    $hostTableCache[ $hostName ][ $hostIP ] = $hostID;
    return $hostID;
  }
}


/**
 * insert a certificate into db if not yet there or just return its id if cached
 * 
 * @param array $certificateInfos ssl information as provided by function getSSLAndCertificateInformation
 * @return int id of certificate
 */
function insertCertificate ( $certificateInfos )
{
  global $db, $certificateTableCache;

  /*
   * fingerprint is needed as primvary key
   */
  if ( !isset ( $certificateInfos[ "fingerprint" ] ) || is_null ( $certificateInfos[ "fingerprint" ] ) )
  {
    return 0;
  }

  /*
   * cached
   */
  if ( isset ( $certificateTableCache[ $certificateInfos[ "fingerprint" ] ] ) )
  {
    return $certificateTableCache[ $certificateInfos[ "fingerprint" ] ];
  }
  /*
   * not cached
   */
  else
  {
    $insertCertificateStatement = $db->prepare ( "insert into zertifikat set fingerprint=?, serial=?, issuer=?, subject=?, notbefore=str_to_date(?,'%M %e %T %Y'), notafter=str_to_date(?,'%M %e %T %Y'), names=?" );
    $insertCertificateStatement->execute ( array (
      $certificateInfos[ "fingerprint" ],
      $certificateInfos[ "serial" ],
      $certificateInfos[ "issuer" ],
      $certificateInfos[ "subject" ],
      $certificateInfos[ "notbefore" ],
      $certificateInfos[ "notafter" ],
      $certificateInfos[ "names" ] ) );
    $certID = $db->lastInsertId ();
    logThis ( _ ( "neues Zertifikat eingefügt: " ) . $certificateInfos[ "fingerprint" ] . " = $certID" );
    $certificateTableCache[ $certificateInfos[ "fingerprint" ] ] = $certID;
    return $certID;
  }
}


/**
 * extract SSL information from sslsplit connection log
 * *our* sslsplit connection log for each connection has the following lines:
 *  - a timestamp line (e.g. "2017-10-10 17:40:27 UTC")
 *  - source socket information (e.g. "Source: 172.16.0.2_52140")
 *  - destination socket info (e.g. "Destination: 162.254.193.47_443")
 *  - cipher information lines (e.g. "Version: TLSv1.2 | VersionID: 03 | Cipher: ECDHE-RSA-AES256-GCM-SHA384 | CipherID: c030 | Number of bits really used: 256 | Number of bits for algorithm: 256")
 *  - fingerprint (e.g. "SHA256 Fingerprint: 5f:b8:64:e9:...")
 *  - certificate infos formatted like openssl x509 -text command (e.g. 
 *    "Certificate:
 *         Data:
 *            Version: 3 (0x2)
 *            Serial Number:
 *                07:31:64:e5:...
 *        Signature Algorithm: sha256WithRSAEncryption
 *            Issuer: C=US, ...")
 * 
 * @param int $recordingID id of the recording the SSL infos are wanted of
 * @param string $sourceSocket IP and port of connection source
 * @param string $destinationSocket IP and port of connection destination
 * @return type
 */
function getSSLAndCertificateInformation ( $recordingID,
                                           $sourceSocket,
                                           $destinationSocket )
{
  global $data_dir, $__;

  $connectionFound = 0;
  $connectionLogFH = fopen ( "$data_dir/$recordingID/" . $__[ "startStopRecording" ] [ "values" ] [ "connectionLog" ],
                             "r" );

  /*
   * walk log by line and find entry for this connection
   */
  while ( ($connectionLogLine = fgets ( $connectionLogFH )) !== false )
  {
    if ( $connectionLogLine == "Source: $sourceSocket\n" )
    {
      $connectionFound = 1;
    }
    else if ( $connectionLogLine == "Destination: $destinationSocket\n" )
    {
      if ( $connectionFound == 1 )
      {
        $connectionFound = 2;
        break;
      }
    }
    else
    {
      $connectionFound = 0;
    }
  }

  /*
   * found the correct entry
   */
  if ( $connectionFound == 2 )
  {
    $sslInformation = array ();

    /*
     * walk log file by line
     */
    while ( ($connectionLogLine = fgets ( $connectionLogFH )) !== false )
    {
      /*
       * lines are like "keyword: content"
       */
      if ( preg_match ( "/^([^:]*): ?(.*)/",
                        $connectionLogLine,
                        $match ) )
      {
        switch ( $match[ 1 ] )
        {
          /*
           * version is string
           */
          case "Version":
            $sslInformation[ "version" ] = $match[ 2 ];
            break;
          /*
           * ciper id is 16 bit hex value
           */
          case "CipherID":
            $sslInformation[ "cipher" ] = hexdec ( $match[ 2 ] );
            break;
          /*
           * integer
           */
          case "Number of bits really used":
            $sslInformation[ "effBits" ] = $match[ 2 ];
            break;
          case "Number of bits for algorithm":
            $sslInformation[ "maxBits" ] = $match[ 2 ];
            break;
          /*
           * fp is string
           */
          case "SHA256 Fingerprint":
            $sslInformation[ "fingerprint" ] = $match[ 2 ];
            break;
          /*
           * certificate info is complex and needs to be taken apart
           */
          case "Certificate":
            $subjectNames = array ();

            /*
             * again, we walk the log by line (as long as they are like "keyword: value" as in "Public-Key: (2048 bit)" or just "keyword" as in "Validity")
             */
            while ( ($connectionLogLine = fgets ( $connectionLogFH )) !== false && preg_match ( "/^ +([^:]*):? ?(.*)/",
                                                                                                $connectionLogLine,
                                                                                                $certificateMatch ) )
            {
              switch ( $certificateMatch[ 1 ] )
              {
                /*
                 * serial number is string directly following keyword or in next line
                 */
                case "Serial Number":
                  if ( $certificateMatch[ 2 ] != "" )
                  {
                    $sslInformation[ "serial" ] = $certificateMatch[ 2 ];
                  }
                  else
                  {
                    $sslInformation[ "serial" ] = trim ( fgets ( $connectionLogFH ) );
                  }
                  break;
                /*
                 * issuer, subject is distinguished name (comma seperated)
                 */
                case "Issuer":
                  $sslInformation[ "issuer" ] = $certificateMatch[ 2 ];
                  break;
                case "Subject":
                  $sslInformation[ "subject" ] = $certificateMatch[ 2 ];
                  /*
                   * add subject common name to list of domains the certificate is valid for
                   */
                  if ( preg_match ( "/CN=(.+)/",
                                    $certificateMatch[ 2 ],
                                    $subjectMatch ) )
                  {
                    $subjectNames[ $subjectMatch[ 1 ] ] = true;
                  }
                  break;
                /*
                 * timestamps are strings like "May 10 00:00:00 2017 GMT"
                 */
                case "Not Before":
                  $sslInformation[ "notbefore" ] = $certificateMatch[ 2 ];
                  break;
                case "Not After ":
                  $sslInformation[ "notafter" ] = $certificateMatch[ 2 ];
                  break;
                /*
                 * additional subject names (comma sepd list of "DNS:name")
                 */
                case "X509v3 Subject Alternative Name":
                  foreach ( explode ( ",",
                                      trim ( fgets ( $connectionLogFH ) ) ) as $dnsList )
                  {
                    if ( preg_match ( "/DNS:(.+)/",
                                      $dnsList,
                                      $dnsMatch ) )
                    {
                      $subjectNames[ $dnsMatch[ 1 ] ] = true;
                    }
                  }
                  break;
              }
            }

            $sslInformation[ "names" ] = implode ( ",",
                                                   array_keys ( $subjectNames ) );

            /*
             * once we processed certificate lines we are done and the log file can be closed
             */
            fclose ( $connectionLogFH );
            return $sslInformation;
            break;
        }
      }
    }
  }

  /*
   * if connection not found or no certificate infos processed, return NULL
   */
  fclose ( $connectionLogFH );
  return NULL;
}


/**
 * find the db id a new connection can be inserted by, preserving a timestamp related order
 * 
 * @param string $timeStamp formatted time stamp (e.g. in format strftime ("%Y-%m-%d %H:%M:%S"))
 * @param int $recordingID id of recording the connection belongs to
 * @return int id new connection can be inserted by
 */
function newConnectionID ( $timeStamp,
                           $recordingID )
{
  global $db;

  /*
   * get largest id of all connections with timestamp not later than this one
   */
  $selectConnectionStatement = $db->prepare ( "select nr from verbindung where zeit<=? and aufzeichnung=? order by nr desc limit 1" );
  $selectConnectionStatement->execute ( array (
    $timeStamp,
    $recordingID ) );
  /*
   * if no such connection found start at 0
   */
  if ( ($connectionID = $selectConnectionStatement->fetchColumn ()) == false )
  {
    $connectionID = 0;
  }

  /*
   * id for new connection must be one higher
   */
  $connectionID++;

  /*
   * increase all ids higher than the one we found to make place for new connection
   */
  $updateConnectionStatement = $db->prepare ( "update verbindung set nr=nr+1 where nr>=? and aufzeichnung=?" );
  $updateConnectionStatement->execute ( array (
    $connectionID,
    $recordingID ) );

  return $connectionID;
}


/**
 * insert connection given by file name into db
 * 
 * @param string $connection name of connection file to process
 * @param int $recordingID id of recording connection belongs to
 * @return int id of inserted connection
 */
function insertNewConnection ( $connection,
                               $recordingID )
{
  global $db, $temp_dir, $data_dir, $__, $cookieTableCache;

  /*
   * process connection file name
   * format is:           yyyymmddThhmmssZ-sip1.sip2.sip3.sip4,sport-dip1.dip2.dip3.dip4,dport.log
   * match field numbers: \_1/\2\3 \4\5\6  \________7________/ \_8_/ \________9________/ \_10/
   */
  preg_match ( "/^([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2})([0-9]{2})([0-9]{2})Z-([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}),([0-9]{1,5})-([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}),([0-9]{1,5})\.log$/",
               $connection,
               $connectionMatch );

  /*
   * reformat timestamp
   */
  $timeStamp = strftime ( "%Y-%m-%d %H:%M:%S",
                          strtotime ( $connectionMatch[ 1 ] . "-" . $connectionMatch[ 2 ] . "-" . $connectionMatch[ 3 ] . " " . $connectionMatch[ 4 ] . ":" . $connectionMatch[ 5 ] . ":" . $connectionMatch[ 6 ] . " GMT" ) );

  $content = file_get_contents ( "$data_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/$connection" );
  $destinationIP = $connectionMatch[ 9 ];

  /*
   * http(s) or some other protocol?
   */
  $protocolIsHttp = false;
  foreach ( explode ( PHP_EOL,
                      $content ) as $connectionLine )
  {
    /*
     * look for http request line = method SP request-target SP HTTP-version CRLF
     */
    if ( preg_match ( "/^([A-Z]+)[[:space:]]+([[:graph:]]+)[[:space:]]+(HTTP\/[0-9]\.[0-9])$/i",
                      trim ( $connectionLine ) ) )
    {
      $protocolIsHttp = true;
      break;
    }
  }

  /*
   * get SSL infos (NULL if none)
   */
  $sslInformation = getSSLAndCertificateInformation ( $recordingID,
                                                      $connectionMatch[ 7 ] . "_" . $connectionMatch[ 8 ],
                                                      $connectionMatch[ 9 ] . "_" . $connectionMatch[ 10 ] );

  $connectionType = $protocolIsHttp ? (is_null ( $sslInformation ) ? "http" : "https") : (is_null ( $sslInformation ) ? "tcp" : "ssl");

  /*
   * do db insert
   */
  $insertConnectionStatement = $db->prepare ( "insert into verbindung set nr=?, aufzeichnung=?, zeit=?, vonport=?, host=?, ip=inet_aton(?), anport=?, laenge=?, typ=?" );
  $insertConnectionStatement->execute ( array (
    newConnectionID ( $timeStamp,
                      $recordingID ),
    $recordingID,
    $timeStamp,
    $connectionMatch[ 8 ],
    insertHost ( "",
                 $destinationIP ),
    $destinationIP,
    $connectionMatch[ 10 ],
    strlen ( $content ),
    $connectionType ) );

  $connectionID = $db->lastInsertId ();
  logThis ( _ ( "neue Verbindung mit ID $connectionID eingefuegt (Typ $connectionType) aus Datei $connection" ) );


  /*
   * process http(s) in detail
   */
  if ( $protocolIsHttp )
  {
    $isRequest = false;
    $isResponse = false;
    $requestID = 0;
    $contentLengthHeaderValue = -1;
    $contentEncodingHeaderValue = "";
    $contentTypeHeaderValue = "";
    $contentTypeParamHeaderValue = "";
    $dateHeaderValue = "";
    $hostHeaderValue = "";
    $userAgentHeaderValue = "";
    $isChunked = false;

    /*
     * open file for decomposing
     */
    $connectionContentFH = fopen ( "$data_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/$connection",
                                   "r" );
    /*
     * walk by line
     */
    while ( ($connectionLine = fgets ( $connectionContentFH )) !== false )
    {
      $connectionLine = trim ( $connectionLine );

      /*
       * request line = method SP request-target SP HTTP-version CRLF
       * match fields:  \__1_/    \______2_____/    \____3_____/
       */
      if ( preg_match ( "/^([A-Z]+)[[:space:]]+([[:graph:]]+)[[:space:]]+(HTTP\/[0-9]\.[0-9])$/i",
                        $connectionLine,
                        $requestLineMatch ) )
      {
        $isRequest = true;
        /*
         * do a partial insert; additional fields are added later
         */
        $insertRequestStatement = $db->prepare ( "insert into request set verbindung=?, aufzeichnung=?, methode=?, uri=?, version=?" );
        $insertRequestStatement->execute ( array (
          $connectionID,
          $recordingID,
          $requestLineMatch[ 1 ],
          $requestLineMatch[ 2 ],
          $requestLineMatch[ 3 ] ) );
        $requestID = $db->lastInsertId ();
        /*
         * get next line
         */
        continue;
      }

      /*
       * status line = HTTP-version SP status-code SP reason-phrase CRLF
       * match fields: \_____1____/    \_____2___/    \_____3_____/
       */
      if ( preg_match ( "/^(HTTP\/[0-9]\.[0-9])[[:space:]]+([0-9]+)[[:space:]]+(.+)$/i",
                        $connectionLine,
                        $statusLineMatch ) )
      {
        $isResponse = true;
        if ( !$requestID )
        {
          logThis ( _ ( "??? Response ohne Request" ) );
        }
        else
        {
          /*
           * do a partial insert; additional fields are added later
           */
          $insertResponseStatement = $db->prepare ( "insert into response set request=?, verbindung=?, aufzeichnung=?, version=?, status=?, statustext=?" );
          $insertResponseStatement->execute ( array (
            $requestID,
            $connectionID,
            $recordingID,
            $statusLineMatch[ 1 ],
            $statusLineMatch[ 2 ],
            $statusLineMatch[ 3 ] ) );
          $responseID = $db->lastInsertId ();
        }
        /*
         * get next line
         */
        continue;
      }

      if ( !$isRequest && !$isResponse )
      {
        logThis ( _ ( "??? weder Request noch Response erkannt" ) );
        continue;
      }

      /*
       * header line = field-name ":" *( SP / HTAB ) field-value *( SP / HTAB )
       * match fields: \____1___/                    \____2____/
       */
      if ( preg_match ( "/^([[:graph:]]+)[[:space:]]*:[[:space:]]*(.*)$/",
                        $connectionLine,
                        $headerLineMatch ) )
      {
        $headerFieldName = $headerLineMatch[ 1 ];
        $headerFieldValue = trim ( $headerLineMatch[ 2 ] );
        $insertHeaderStatement = $db->prepare ( "insert into header set request=?, verbindung=?, aufzeichnung=?, response=?, feld=?, wert=?" );
        $insertHeaderStatement->execute ( array (
          $requestID,
          $connectionID,
          $recordingID,
          $isResponse,
          $headerFieldName,
          $headerFieldValue ) );

        /*
         * different headers give different information
         */
        switch ( strtolower ( $headerFieldName ) )
        {
          case "host":
            /*
             * host header might have a port spec which we explode off
             */
            $hostHeaderValue = explode ( ":",
                                         $headerFieldValue )[ 0 ];
            break;

          case "user-agent":
            /*
             * multiple user agent headers shouldn't show up
             */
            if ( $userAgentHeaderValue != "" && $userAgentHeaderValue != $headerFieldValue )
            {
              logThis ( _ ( "??? differierende User-Agents: " ) . $userAgentHeaderValue . " <-> " . $headerFieldValue );
            }
            else
            {
              $userAgentHeaderValue = $headerFieldValue;
            }
            break;

          case "date":
            $dateHeaderValue = strtotime ( $headerFieldValue );
            break;

          case "content-length":
            $contentLengthHeaderValue = $headerFieldValue;
            break;

          case "content-type":
            /*
             * content type is MIME possibly followed by additional param (e.g. "Content-Type: text/html; charset=ISO-8859-4")
             */
            $contentTypeFields = explode ( ";",
                                           $headerFieldValue,
                                           2 );
            $contentTypeHeaderValue = $contentTypeFields[ 0 ];
            if ( count ( $contentTypeFields ) > 1 )
            {
              $contentTypeParamHeaderValue = trim ( $contentTypeFields[ 1 ] );
            }
            break;

          case "content-encoding":
            $contentEncodingHeaderValue = strtolower ( $headerFieldValue );
            break;

          case "transfer-encoding":
            if ( strtolower ( $headerFieldValue ) == "chunked" )
            {
              $isChunked = true;
            }
            break;

          /*
           * cookies sent in request
           */
          case "cookie":
            foreach ( explode ( ";",
                                $headerFieldValue ) as $cookie )
            {
              if ( preg_match ( "/^([^=]+)=(.*)$/",
                                trim ( $cookie ),
                                       $cookieMatch ) )
              {
                /*
                 * see if cookie is cached
                 */
                $cookieID = NULL;
                if ( array_key_exists ( $cookieMatch[ 1 ],
                                        $cookieTableCache ) )
                {
                  foreach ( $cookieTableCache[ $cookieMatch[ 1 ] ] as $host => $cID )
                  {
                    if ( stripos ( $hostHeaderValue . "#",
                                   $host . "#" ) !== false )
                    {
                      $cookieID = $cID;
                      break;
                    }
                  }
                }
                /*
                 * not cached
                 */
                if ( is_null ( $cookieID ) )
                {
                  $insertCookieStatement = $db->prepare ( "insert into cookie set name=?, site=?" );
                  $insertCookieStatement->execute ( array (
                    $cookieMatch[ 1 ],
                    $hostHeaderValue ) );
                  $cookieID = $db->lastInsertId ();
                  $cookieTableCache[ $cookieMatch[ 1 ] ][ $hostHeaderValue ] = $cookieID;
                }

                $insertSendcookieStatement = $db->prepare ( "insert into sendcookie set cookie=?, request=?, verbindung=?, aufzeichnung=?, wert=?" );
                $insertSendcookieStatement->execute ( array (
                  $cookieID,
                  $requestID,
                  $connectionID,
                  $recordingID,
                  $cookieMatch[ 2 ] ) );
              }
            }
            break;  /* case "cookie": */

          /*
           * cookies received in response
           */
          case "set-cookie":
          case "set-cookie2":
            $httpOnlyCookie = 0;
            $secureCookie = 0;
            $cookieName = "";
            $cookieDomain = "";
            $cookiePath = "";
            $cookieComment = "";
            $cookieExpires = NULL;
            $cookieValid = NULL;

            foreach ( explode ( ";",
                                $headerFieldValue ) as $cookie )
            {
              $cookie = trim ( $cookie );
              switch ( strtolower ( $cookie ) )
              {
                case "":
                  break;

                case "secure":
                  $secureCookie = 1;
                  break;

                case "httponly":
                  $httpOnlyCookie = 1;
                  break;

                default:
                  preg_match ( "/^([^=]+)=(.*)$/",
                               $cookie,
                               $cookieMatch );

                  if ( $cookieName == "" )
                  {
                    $cookieName = $cookieMatch[ 1 ];
                    $cookieValue = $cookieMatch[ 2 ];
                  }
                  else
                  {
                    switch ( strtolower ( $cookieMatch[ 1 ] ) )
                    {
                      case "domain":
                        $cookieDomain = $cookieMatch[ 2 ];
                        break;

                      case "path":
                        $cookiePath = $cookieMatch[ 2 ];
                        break;

                      case "comment":
                        $cookieComment = $cookieMatch[ 2 ];
                        break;

                      case "expires":
                        $cookieExpires = strftime ( "%Y-%m-%d %H:%M:%S",
                                                    strtotime ( $cookieMatch[ 2 ] ) );
                        break;

                      case "max-age":
                        $cookieValid = $cookieMatch[ 2 ];
                        break;
                    }
                  }
              }
            }

            /*
             * if no domain value is given, use header host value as cookie site
             */
            $cookieSite = $cookieDomain == "" ? $hostHeaderValue : $cookieDomain;

            /*
             * see if cookie is cached
             */
            $cookieID = NULL;
            if ( array_key_exists ( $cookieName,
                                    $cookieTableCache ) )
            {
              foreach ( $cookieTableCache[ $cookieName ] as $host => $cID )
              {
                /*
                 * cached ("#" is appended to right align values)
                 */
                if ( stripos ( $hostHeaderValue . "#",
                               $host . "#" ) !== false )
                {
                  $cookieID = $cID;
                  /*
                   * if actual cookieSite value is more general than the cached host value, replace the cached one
                   */
                  if ( stripos ( $host . "#",
                                 $cookieSite . "#" ) !== false )
                  {
                    $updateCookieStatement = $db->prepare ( "update cookie set site=? where id=?" );
                    $updateCookieStatement->execute ( array (
                      $cookieSite,
                      $cookieID ) );
                    unset ( $cookieTableCache[ $cookieName ][ $host ] );
                    $cookieTableCache[ $cookieName ][ $cookieSite ] = $cookieID;
                  }
                  break;
                }
              }
            }
            /*
             * not cached
             */
            if ( is_null ( $cookieID ) )
            {
              $insertCookieStatement = $db->prepare ( "insert into cookie set name=?, site=?" );
              $insertCookieStatement->execute ( array (
                $cookieName,
                $cookieSite ) );
              $cookieID = $db->lastInsertId ();
              $cookieTableCache[ $cookieName ][ $cookieSite ] = $cookieID;
            }

            $insertSetcookieStatement = $db->prepare ( "insert into setcookie set cookie=?, request=?, verbindung=?, aufzeichnung=?, wert=?, secure=?, httponly=?, domain=?, path=?, comment=?, expires=?, valid=?" );
            $insertSetcookieStatement->execute ( array (
              $cookieID,
              $requestID,
              $connectionID,
              $recordingID,
              $cookieValue,
              $secureCookie,
              $httpOnlyCookie,
              $cookieDomain,
              $cookiePath,
              $cookieComment,
              $cookieExpires,
              $cookieValid ) );

            break; /* case "set-cookie": */
        } /* switch ( strtolower ( $headerFieldName ) ) */

        /*
         * get next line
         */
        continue;
      } /* if ( preg_match ( ... */

      /*
       * message body follows after empty line
       */
      if ( $connectionLine == "" )
      {
        $rawContent = "";
        $cookedContent = "";
        $rawContentID = 0;
        $cookedContentID = 0;

        /*
         * unchunked content
         */
        if ( !$isChunked )
        {
          if ( $contentLengthHeaderValue > 0 )
          {
            /*
             * this may fail for large values of $contentLength !
             */
            $rawContent = fread ( $connectionContentFH,
                                  $contentLengthHeaderValue );
          }
          else if ( $contentLengthHeaderValue == -1 && $contentTypeHeaderValue != "" && $isResponse )
          {
            do
            {
              $rawContent .= fread ( $connectionContentFH,
                                     1000 );
            }
            while ( !feof ( $connectionContentFH ) );
          }
          $cookedContent = $rawContent;
        }
        /*
         * chunked
         */
        else
        {
          $contentLengthHeaderValue = 0;
          do
          {
            $iAmHere = ftell ( $connectionContentFH );
            $connectionLine = fgets ( $connectionContentFH );
            $rawContent .= $connectionLine;
            $connectionLine = trim ( $connectionLine );
            /*
             * chunk length value
             */
            if ( preg_match ( "/^([0-9a-f]+)$/i",
                              $connectionLine,
                              $chunkMatch ) )
            {
              $chunkLength = hexdec ( $chunkMatch[ 1 ] );
              if ( $chunkLength > 0 )
              {
                $chunk = fread ( $connectionContentFH,
                                 $chunkLength );
                $rawContent .= $chunk;
                $cookedContent .= $chunk;
                $contentLengthHeaderValue += $chunkLength;
              }
              /*
               * read CR LF
               */
              $connectionLine = fgets ( $connectionContentFH );
              $rawContent .= $connectionLine;
            }
            /*
             * not a chunk length value
             */
            else
            {
              $chunkLength = 0;
              /*
               * undo the last fgets
               */
              fseek ( $connectionContentFH,
                      $iAmHere );
            }
          }
          while ( $chunkLength > 0 && !feof ( $connectionContentFH ) );
        }

        /*
         * undo compressions
         */
        if ( $cookedContent != "" )
        {
          switch ( strtolower ( $contentEncodingHeaderValue ) )
          {
            case "gzip":
              if ( ($unzippedContent = gzdecode ( $cookedContent )) != false )
              {
                $cookedContent = $unzippedContent;
              }
              break;

            case "deflate":
              if ( ($uncompressedContent = gzuncompress ( $cookedContent )) != false )
              {
                $cookedContent = $uncompressedContent;
              }
              break;
          }

          /*
           * decode protocol buffer data
           */
          if ( preg_match ( "/^application.*proto/i",
                            $contentTypeHeaderValue ) )
          {
            $tempFileName = "$temp_dir/" . getmypid ();
            if ( ($tempFileFH = fopen ( $tempFileName,
                                        "w" )) !== false )
            {
              fwrite ( $tempFileFH,
                       $cookedContent );
              fclose ( $tempFileFH );

              $protocOutput = "";
              /*
               * do external decoding
               */
              exec ( "/bin/cat $tempFileName | /usr/bin/protoc --decode_raw 2>/dev/null",
                     $protocOutput,
                     $returnValue );
              if ( $returnValue == 0 )
              {
                $cookedContent = implode ( "\n",
                                           $protocOutput );
              }
              unlink ( $tempFileName );
            }
          }

          /*
           * pretty print JSON
           */
          else if ( preg_match ( "/\/json/i",
                                 $contentTypeHeaderValue ) )
          {
            $cookedContent = json_encode ( json_decode ( $cookedContent ),
                                                         JSON_PRETTY_PRINT );
          }

          /*
           * do UTF8 encoding if necessary
           */
          else if ( preg_match ( "/utf-8/i",
                                 $contentTypeParamHeaderValue ) && !mb_check_encoding ( $cookedContent,
                                                                                        "UTF-8" ) )
          {
            $cookedContent = utf8_encode ( $cookedContent );
          }
        }

        /*
         * put raw content into db only if it differs from processed content
         */
        if ( $rawContent != "" && $cookedContent != $rawContent )
        {
          $insertContentStatement = $db->prepare ( "insert into inhalt set typ=?, referenz=?, verbindung=?, aufzeichnung=?, inhalt=?" );
          $insertContentStatement->execute ( array (
            $isRequest ? "requestroh" : "responseroh",
            $requestID,
            $connectionID,
            $recordingID,
            $rawContent ) );
          $rawContentID = $db->lastInsertId ();
        }
        /*
         * put content into db only if not empty
         */
        if ( $cookedContent != "" )
        {
          $insertContentStatement = $db->prepare ( "insert into inhalt set typ=?, referenz=?, verbindung=?, aufzeichnung=?, inhalt=?" );
          $insertContentStatement->execute ( array (
            $isRequest ? "request" : "response",
            $requestID,
            $connectionID,
            $recordingID,
            $cookedContent ) );
          $cookedContentID = $db->lastInsertId ();
        }

        /*
         * add missing fields to request record
         */
        if ( $isRequest )
        {
          $updateRequestStatement = $db->prepare ( "update request set inhaltroh=?, inhalt=?, mime=?, mimeadd=? where id=?" );
          $updateRequestStatement->execute ( array (
            $rawContentID,
            $cookedContentID,
            $contentTypeHeaderValue,
            $contentTypeParamHeaderValue,
            $requestID ) );
        }

        /*
         * add missing fields to response record
         */
        if ( $isResponse )
        {
          $updateResponseStatement = $db->prepare ( "update response set inhaltroh=?, inhalt=?, mime=?, mimeadd=? where id=?" );
          $updateResponseStatement->execute ( array (
            $rawContentID,
            $cookedContentID,
            $contentTypeHeaderValue,
            $contentTypeParamHeaderValue,
            $responseID ) );
        }

        /*
         * extract meta data from content
         */
        if ( $cookedContent != "" )
        {
          switch ( strtolower ( $contentTypeHeaderValue ) )
          {
            /*
             * images (image dimensions, exiv values etc)
             */
            case "image/jpg":
            case "image/jpeg":
            case "image/png":
            case "image/gif":
            case "image/tiff":
            case "image/bmp":
              $tempFileName = tempnam ( $temp_dir,
                                        "dbinserter" );
              if ( ($tempFileFH = fopen ( $tempFileName,
                                          "w" )) !== false )
              {
                fwrite ( $tempFileFH,
                         $cookedContent );
                fclose ( $tempFileFH );

                /*
                 * get image dimensions
                 */
                $exiv2Output = array ();
                exec ( "/usr/bin/exiv2 -ps pr $tempFileName 2>/dev/null",
                       $exiv2Output,
                       $returnValue );

                foreach ( $exiv2Output as $exivLine )
                {
                  if ( preg_match ( "/Image Size *: *([0-9]+) *x *([0-9]+)/i",
                                    $exivLine,
                                    $exivMatch ) )
                  {
                    $insertMetadataStatement = $db->prepare ( "insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?" );
                    $insertMetadataStatement->execute ( array (
                      $requestID,
                      $connectionID,
                      $recordingID,
                      $isResponse,
                      strtolower ( $contentTypeHeaderValue ),
                      _ ( "Dimension" ),
                      $exivMatch[ 1 ] . " x " . $exivMatch[ 2 ] ) );
                  }
                }

                /*
                 * get key value pairs
                 */
                $exiv2Output = array ();
                exec ( "/usr/bin/exiv2 -Pkt pr $tempFileName 2>/dev/null",
                       $exiv2Output,
                       $returnValue );
                foreach ( $exiv2Output as $exivLine )
                {
                  if ( preg_match ( "/^ *([[:graph:]]+) +(.+)$/",
                                    $exivLine,
                                    $exivMatch ) )
                  {
                    $insertMetadataStatement = $db->prepare ( "insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?" );
                    $insertMetadataStatement->execute ( array (
                      $requestID,
                      $connectionID,
                      $recordingID,
                      $isResponse,
                      strtolower ( $contentTypeHeaderValue ),
                      $exivMatch[ 1 ],
                      trim ( $exivMatch[ 2 ] ) ) );
                  }
                }

                /*
                 * get jpeg comments
                 */
                $exiv2Output = array ();
                $jpegComments = array ();
                exec ( "/usr/bin/exiv2 -pc pr $tempFileName 2>/dev/null",
                       $exiv2Output,
                       $returnValue );
                foreach ( $exiv2Output as $exivLine )
                {
                  if ( preg_match ( "/^JPEG comment:(.+)$/i",
                                    $exivLine,
                                    $exivMatch ) )
                  {
                    array_push ( $jpegComments,
                                 trim ( $exivMatch[ 1 ] ) );
                  }
                }

                if ( count ( $jpegComments ) )
                {
                  $insertMetadataStatement = $db->prepare ( "insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?" );
                  $insertMetadataStatement->execute ( array (
                    $requestID,
                    $connectionID,
                    $recordingID,
                    $isResponse,
                    strtolower ( $contentTypeHeaderValue ),
                    _ ( "JPEG-Kommentare" ),
                    implode ( "\n",
                              $jpegComments ) ) );
                }

                unlink ( $tempFileName );
              }
              break; /* images */

            /*
             * html (title, comments etc)
             */
            case "text/html":
              $document = new DOMDocument();
              if ( ($document->loadHTML ( $cookedContent )) !== false )
              {
                /*
                 * document title
                 */
                $documentTitle = array ();
                foreach ( $document->getElementsByTagName ( "title" ) as $node )
                {
                  if ( $node->nodeValue != "" )
                  {
                    array_push ( $documentTitle,
                                 $node->nodeValue );
                  }
                }
                if ( count ( $documentTitle ) )
                {
                  $insertMetadataStatement = $db->prepare ( "insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?" );
                  $insertMetadataStatement->execute ( array (
                    $requestID,
                    $connectionID,
                    $recordingID,
                    $isResponse,
                    strtolower ( $contentTypeHeaderValue ),
                    _ ( "Titel" ),
                    implode ( "\n",
                              $documentTitle ) ) );
                }

                /*
                 * html comments
                 */
                $documentComments = extractCommentsFromDOM ( array (),
                                                             $document->documentElement );
                if ( count ( $documentComments ) )
                {
                  $insertMetadataStatement = $db->prepare ( "insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?" );
                  $insertMetadataStatement->execute ( array (
                    $requestID,
                    $connectionID,
                    $recordingID,
                    $isResponse,
                    strtolower ( $contentTypeHeaderValue ),
                    _ ( "Kommentare" ),
                    implode ( "\n",
                              $documentComments ) ) );
                }

                /*
                 * meta tags
                 */
                $metaTags = array ();
                foreach ( $document->getElementsByTagName ( "meta" ) as $node )
                {
                  if ( $node->attributes )
                  {
                    $metaName = "";
                    $metaContent = "";
                    foreach ( $node->attributes as $attributeName => $attributeNode )
                    {
                      if ( $attributeName == "content" )
                      {
                        $metaContent = $attributeNode->nodeValue;
                      }
                      else
                      {
                        $metaName = $attributeNode->nodeValue;
                      }
                    }
                    if ( $metaName != "" )
                    {
                      array_push ( $metaTags,
                                   $metaName . " = " . $metaContent );
                    }
                  }
                }
                if ( count ( $metaTags ) )
                {
                  $insertMetadataStatement = $db->prepare ( "insert into metadaten set request=?, verbindung=?, aufzeichnung=?, response=?, mime=?, feld=?, wert=?" );
                  $insertMetadataStatement->execute ( array (
                    $requestID,
                    $connectionID,
                    $recordingID,
                    $isResponse,
                    strtolower ( $contentTypeHeaderValue ),
                    _ ( "Meta-Tags" ),
                    implode ( "\n",
                              $metaTags ) ) );
                }
              }

              break; /* case "text/html": */
          }
        }

        /*
         * prepare for next request / response
         */
        $isRequest = false;
        $isResponse = false;
        $contentLengthHeaderValue = -1;
        $contentTypeHeaderValue = "";
        $contentTypeParamHeaderValue = "";
        $contentEncodingHeaderValue = "";
        $isChunked = false;
      } /* if ( $connectionLine == "" ) */
    } /* while ( ($connectionLine = fgets ( $connectionContentFH )) !== false ) */

    if ( is_null ( $sslInformation ) )
    {
      $insert_s = $db->prepare ( "insert into http set aufzeichnung=?, verbindung=?, useragent=?, host=?" );
      $insert_s->execute ( array (
        $recordingID,
        $connectionID,
        $userAgentHeaderValue,
        insertHost ( $hostHeaderValue,
                     $destinationIP ) ) );
    }
    else
    {
      $insert_s = $db->prepare ( "insert into https set aufzeichnung=?, verbindung=?, useragent=?, host=?, sslversion=?, ciphersuite=?, effBits=?, maxBits=?, zertifikat=?" );
      $insert_s->execute ( array (
        $recordingID,
        $connectionID,
        $userAgentHeaderValue,
        insertHost ( $hostHeaderValue,
                     $destinationIP ),
        $sslInformation[ "version" ],
        $sslInformation[ "cipher" ],
        $sslInformation[ "effBits" ],
        $sslInformation[ "maxBits" ],
        insertCertificate ( $sslInformation ) ) );
    }

    fclose ( $connectionContentFH );

    # update setcookie with date header info
    $select_s = $db->prepare ( "select setcookie.id as id, setcookie.expires as expires, setcookie.valid as valid from setcookie,request where request.id=setcookie.request and request.verbindung=?" );
    $select_s->execute ( array (
      $connectionID ) );
    while ( $setcookie = $select_s->fetch () )
    {
      if ( is_null ( $setcookie[ "valid" ] ) && !is_null ( $setcookie[ "expires" ] ) )
      {
        $date_s = $db->prepare ( "select header.wert as date from header,request where header.feld='date' and request.id=header.request and request.verbindung=?" );
        $date_s->execute ( array (
          $connectionID ) );
        if ( $dateHeaderValue = $date_s->fetch () )
        {
          $update_s = $db->prepare ( "update setcookie set valid=? where id=?" );
          $update_s->execute ( array (
            strtotime ( $setcookie[ "expires" ] ) - strtotime ( $dateHeaderValue[ "date" ] ),
            $setcookie[ "id" ] ) );
        }
      }
    }
  }

  /*
   * not http(s)
   */
  else
  {
    if ( $content != "" )
    {
      $insertContentStatement = $db->prepare ( "insert into inhalt set typ='tcp', referenz=?, verbindung=?, aufzeichnung=?, inhalt=?" );
      $insertContentStatement->execute ( array (
        $connectionID,
        $connectionID,
        $recordingID,
        $content ) );
    }

    if ( !is_null ( $sslInformation ) )
    {
      $insertSSLStatement = $db->prepare ( "insert into ssltls set aufzeichnung=?, verbindung=?, sslversion=?, ciphersuite=?, effBits=?, maxBits=?, zertifikat=?" );
      $insertSSLStatement->execute ( array (
        $recordingID,
        $connectionID,
        $sslInformation[ "version" ],
        $sslInformation[ "cipher" ],
        $sslInformation[ "effBits" ],
        $sslInformation[ "maxBits" ],
        insertCertificate ( $sslInformation ) ) );
    }
  }

  return $connectionID;
}


/**
 * logging-echo function for this script (logging is only done in development mode for performance reasons)
 * 
 * @staticvar type $pretime
 * @param string $logLine info to log
 */
function logThis ( $logLine )
{
  global $scriptStartTime, $develop_mode;

  if ( $develop_mode )
  {
    static $previousLogTime = NULL;

    $now = microNow ();
    $msSinceScriptStart = floor ( ($now - $scriptStartTime) * 1000 );

    if ( $previousLogTime != NULL )
    {
      $msSinceLastLog = floor ( ($now - $previousLogTime) * 1000 );
    }
    else
    {
      $msSinceLastLog = "";
    }
    $previousLogTime = $now;

    /*
     * pretty print logline
     */
    $msSinceScriptStart = substr ( "           $msSinceScriptStart ms",
                                   -10 );
    $msSinceLastLog = substr ( "           $msSinceLastLog ms",
                               -10 );
    echo "$msSinceScriptStart$msSinceLastLog -- $logLine\n";
  }
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

/*
 * set interrupt handler
 * ticks must be declared, see https://secure.php.net/manual/en/function.pcntl-signal.php
 * we set ticks to a larger value - while this extends reaction time to an interrupt, it reduces performance overhead
 */
declare(ticks = 100);
pcntl_signal ( SIGINT,
               "sigintHandler" );


$scriptStartTime = microNow ();

/*
 * script is called with shell arguments (1) recordingID, (2) ip address of recorded device
 */
logThis ( __FILE__ . _ ( " aufgerufen mit Parameter " ) . $argv[ 1 ] . ", " . $argv[ 2 ] );
$recordingID = $argv[ 1 ];
$recordedIP = $argv[ 2 ];

/*
 * check for directory presence
 */
if ( !is_dir ( "$data_dir/$recordingID" ) )
{
  logThis ( _ ( "kein passendes Verzeichnis \"$data_dir/$recordingID\" gefunden" ) );
  exit;
}

/*
 * read heavily used db tables into memory
 */
$connectionTableCache = array ();
/*
  $select_s = $db->prepare ("select nr,zeit from verbindung where aufzeichnung=?");
  $select_s->execute (array($aufzeichnungID));
  while ($v = $select_s->fetch())
  {
  $verbindungTab[$v["zeit"]][$v["vonport"]] = $v["id"];
  }
 */

$hostTableCache = array ();
$selectHostStatement = $db->prepare ( "select id,name,inet_ntoa(ip) as _ip from host" );
$selectHostStatement->execute ();
while ( $host = $selectHostStatement->fetch () )
{
  $hostTableCache[ $host[ "name" ] ][ $host[ "_ip" ] ] = $host[ "id" ];
}

$certificateTableCache = array ();
$selectCertificateStatement = $db->prepare ( "select id,fingerprint from zertifikat" );
$selectCertificateStatement->execute ();
while ( $certificate = $selectCertificateStatement->fetch () )
{
  $certificateTableCache[ $certificate[ "fingerprint" ] ] = $certificate[ "id" ];
}

$cookieTableCache = array ();
$selectCookieStatement = $db->prepare ( "select id,name,site from cookie" );
$selectCookieStatement->execute ();
while ( $cookie = $selectCookieStatement->fetch () )
{
  $cookieTableCache[ $cookie[ "name" ] ][ $cookie[ "site" ] ] = $cookie[ "id" ];
}


/*
 * insert connections while recording is active
 */
do
{
  $selectRecordingStatement = $db->prepare ( "select * from aufzeichnung where id=?" );
  $selectRecordingStatement->execute ( array (
    $recordingID ) );

  if ( ($recording = $selectRecordingStatement->fetch ()) == true )
  {
    if ( !is_dir ( "$temp_dir/$recordingID" ) )
    {
      mkdir ( "$temp_dir/$recordingID" );
    }
    if ( !is_dir ( "$temp_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] ) )
    {
      mkdir ( "$temp_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] );
    }

    logThis ( _ ( "Verbindungen übernehmen: vor beginTransaction" ) );

    /*
     * put all db ops in a transaction to speed up things
     */
    $db->beginTransaction ();

    if ( !$recording[ "ende" ] )
    {
      logThis ( _ ( "Aufzeichnung $recordingID ist noch nicht beendet" ) );
    }

    /*
     * add connections that are terminated
     */
    do
    {
      $connectionsAdded = 0;

      /*
       * walk through all files in the connection dir (each one being a connection to insert into db)
       */
      foreach ( scandir ( "$data_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] ) as $connection )
      {
        /*
         * not a regular file
         */
        if ( !is_file ( "$data_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/$connection" ) )
        {
          logThis ( _ ( "$connection ist keine Datei (sondern Verzeichnis?)" ) );
          continue;
        }

        /*
         * already inserted this connection
         */
        if ( array_key_exists ( $connection,
                                $connectionTableCache ) )
        {
          logThis ( _ ( "$connection bereits in die Aufzeichnung $recordingID übernommen" ) );
          continue;
        }

        /*
         * see if processes are still connected to that file
         */
        system ( "/usr/bin/lsof $data_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/$connection >/dev/null 2>&1",
                 $connectedProcesses );
        /*
         * lsof returns 0 if some processes connect, 1 if no process is connected
         */
        if ( !$connectedProcesses )
        {
          logThis ( _ ( "$connection ist noch geöffnet" ) );
          continue;
        }

        logThis ( "$connection noch nicht in Aufzeichnung $recordingID übernommen" );
        $connectionsAdded++;

        $connectionID = insertNewConnection ( $connection,
                                              $recordingID );

        $connectionTableCache[ $connection ] = $connectionID;

        /*
         * create a touch like file as flag
         */
        $connectionDoneFH = fopen ( "$temp_dir/$recordingID/" . $__[ "startStopRecording" ][ "values" ] [ "connectionDir" ] . "/$connection",
                                    "w" );
        fclose ( $connectionDoneFH );
      }
    }
    /*
     * leave loop only when no new terminated connection found
     */
    while ( $connectionsAdded );

    /*
     * commit all db ops
     */
    $db->commit ();
    logThis ( _ ( "Verbindungen übernehmen: nach commit" ) );
  }

  /*
   * wait a little for more connections to end
   */
  sleep ( 2 );
}
/*
 * leave loop only when recording is not active any more
 */
while ( !$recording[ "ende" ] );


/*
 * process tcpdump of udp protocol packets
 */
if ( is_readable ( "$data_dir/$recordingID/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPcap" ] ) )
{
  logThis ( "tcpdump auswerten: vor beginTransaction" );
  $db->beginTransaction ();

  $tcpDump = fopen ( "$data_dir/$recordingID/" . $__[ "startStopRecording" ] [ "values" ] [ "tcpdumpPcap" ],
                     "rb" );
  $packetTimeStamps = array ();
  $packetDatas = array ();

  /*
   * global header: 24 Bytes, irrelevant here
   */
  fread ( $tcpDump,
          24 );

  /*
   * process packets
   */
  while ( !feof ( $tcpDump ) && ($packetHeaderData = fread ( $tcpDump,
                                                             16 )) )
  {
    /*
     * packet header: 16 Bytes
     */
    $packetHeader = unpack ( "VtsSec/VtsUsec/VinclLen",
                             $packetHeaderData );
    /*
     * timestamp, full seconds
     */
    $timeStamp = strftime ( "%Y-%m-%d %H:%M:%S",
                            $packetHeader[ "tsSec" ] );
    /*
     * packet length
     */
    $bytesToRead = $packetHeader[ "inclLen" ];

    /*
     * mac header: 14 Bytes, irrelevant here
     */
    $macHeader = fread ( $tcpDump,
                         14 );
    $bytesToRead -= 14;

    /*
     * ip header: 20 Bytes (header length, protocol, source ip, dest ip)
     */
    $ipHeaderData = fread ( $tcpDump,
                            20 );
    $ipHeader = unpack ( "CipVhl/C/v/v/v/C/CipProto/v/NsrcIp/NdstIp",
                         $ipHeaderData );
    $ipSourceIP = long2ip ( $ipHeader[ 'srcIp' ] );
    $ipDestinationIP = long2ip ( $ipHeader[ 'dstIp' ] );
    $ipHeaderLength = ($ipHeader[ "ipVhl" ] & 15) * 4;
    if ( $ipHeaderLength > 20 )
    {
      /*
       * read options, if any, irrelevant here
       */
      $ipOptions = fread ( $tcpDump,
                           $ipHeaderLength - 20 );
    }
    $bytesToRead -= $ipHeaderLength;
    logThis ( _ ( "IP Paket $ipSourceIP --> $ipDestinationIP" ) );

    /*
     * UDP packet
     */
    if ( $ipHeader[ "ipProto" ] == 17 )
    {
      $udpHeaderData = fread ( $tcpDump,
                               8 );
      $bytesToRead -= 8;
      $udpHeader = unpack ( "nsrcport/ndstport/nudplen",
                            $udpHeaderData );
      $udpSourcePort = $udpHeader[ "srcport" ];
      $udpDestinationPort = $udpHeader[ "dstport" ];
      logThis ( _ ( "UDP-Paket $ipSourceIP:$udpSourcePort --> $ipDestinationIP:$udpDestinationPort" ) );

      $udpData = "";
      if ( $bytesToRead )
      {
        $udpData = fread ( $tcpDump,
                           $bytesToRead );
      }

      /*
       * collect timestamp and raw data for each socket
       */
      $key = "";
      if ( $ipSourceIP == $recordedIP )
      {
        $key = "$udpSourcePort:$ipDestinationIP:$udpDestinationPort";
        $outBoundPacket = true;
      }
      if ( $ipDestinationIP == $recordedIP )
      {
        $key = "$udpDestinationPort:$ipSourceIP:$udpSourcePort";
        $outBoundPacket = false;
      }
      if ( $key != "" )
      {
        if ( !array_key_exists ( $key,
                                 $packetTimeStamps ) )
        {
          $packetTimeStamps[ $key ] = $timeStamp;
        }
        if ( !array_key_exists ( $key,
                                 $packetDatas ) )
        {
          $packetDatas[ $key ][ true ] = "";
          $packetDatas[ $key ][ false ] = "";
        }
        $packetDatas[ $key ][ $outBoundPacket ] .= $udpData;
      }
    }

    /*
     * not UDP
     */
    else
    {
      if ( $bytesToRead )
      {
        fread ( $tcpDump,
                $bytesToRead );
      }
      logThis ( _ ( "unerwartetes Paket mit Protokollnummer " ) . $ipHeader[ "ipProto" ] . _ ( " gefunden" ) );
    }
  }
  fclose ( $tcpDump );

  /*
   * now process collected packets
   */
  foreach ( $packetTimeStamps as $key => $timeStamp )
  {
    list ($udpSourcePort, $destinationIP, $udpDestinationPort) = explode ( ":",
                                                                           $key );

    $insertConnectionStatement = $db->prepare ( "insert into verbindung set nr=?, aufzeichnung=?, zeit=?, vonport=?, anport=?, ip=inet_aton(?), host=?, laenge=?, typ='udp'" );
    $insertConnectionStatement->execute ( array (
      newConnectionID ( $timeStamp,
                        $recordingID ),
      $recordingID,
      $timeStamp,
      $udpSourcePort,
      $udpDestinationPort,
      $destinationIP,
      insertHost ( "",
                   $destinationIP ),
      strlen ( $packetDatas[ $key ][ true ] ) + strlen ( $packetDatas[ $key ][ false ] ) ) );
    $connectionID = $db->lastInsertId ();

    /*
     * outbound data
     */
    if ( $packetDatas[ $key ][ true ] != "" )
    {
      $insertContentStatement = $db->prepare ( "insert into inhalt set typ='udpsend', referenz=?, verbindung=?, aufzeichnung=?, inhalt=?" );
      $insertContentStatement->execute ( array (
        $connectionID,
        $connectionID,
        $recordingID,
        $packetDatas[ $key ][ true ] ) );
    }

    /*
     * inbound data
     */
    if ( $packetDatas[ $key ][ false ] != "" )
    {
      $insertContentStatement = $db->prepare ( "insert into inhalt set typ='udprcv', referenz=?, verbindung=?, aufzeichnung=?, inhalt=?" );
      $insertContentStatement->execute ( array (
        $connectionID,
        $connectionID,
        $recordingID,
        $packetDatas[ $key ][ false ] ) );
    }
  }

  /*
   * commit all udp data inserts
   */
  $db->commit ();
  logThis ( _ ( "tcpdump auswerten: nach commit" ) );
}

doFinalThingsAndCleanup ();

<?php

/* ===========================================================================
 *
 * PREAMBLE
 *
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/globalNames.php
 *
 * used to define names and values used in all scripts so they can be used consistently within the project
 *
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
$__ = [
  /*
   * about.php
   */
  "about"                        => [
    "ids" => [
      "panelGroup" => "aboutPanelGroup" ] ],
  /*
   * connectLAN.php
   */
  "connectLAN"                   => [
    "params" => [
      "dhcp"     => "auto",
      "manually" => "manu",
      "address"  => "address",
      "netmask"  => "netmask",
      "gateway"  => "gateway",
      "dns"      => "dns" ],
    "values" => [
      "dhcp"     => _ ( "Automatisch" ),
      "manually" => _ ( "Manuell" ),
      "address"  => _ ( "IP-Adresse" ),
      "netmask"  => _ ( "Netzmaske" ),
      "gateway"  => _ ( "Gateway" ),
      "dns"      => _ ( "DNS-Server" ) ] ],
  /*
   * connectOffline.php
   */
  "connectOffline"               => [
    "params" => [
      "offline" => "offline" ],
    "values" => [
      "offline" => _ ( "Trennen" ) ] ],
  /*
   * connectUMTS.php
   */
  "connectUMTS"                  => [
    "values" => [
      "pin"        => _ ( "PIN" ),
      "wvdialFile" => "connectUMTS.tmp" // relative to temp_dir
    ],
    "params" => [
      "apn"     => "umtsAPN",
      "pin"     => "umtsPIN",
      "connect" => "umtsConnect" ] ],
  /*
   * connectWLAN.php
   */
  "connectWLAN"                  => [
    "values" => [
      "connect"           => _ ( "Verbinden" ),
      "wpaSupplicantFile" => "connectWLAN.tmp" // relative to temp_dir
    ],
    "ids"    => [
      "passwordModal" => "connectWLANPasswordModal",
      "wifiName"      => "connectWLANWifiName",
      "password"      => "connectWLANPassword",
      "autoSSID"      => "connectWLANAutoSSID",
      "wifis"         => "connectWLANScan" ],
    "params" => [
      "password"   => "connectWLANPassword",
      "autoSSID"   => "connectWLANAutoSSID",
      "manualSSID" => "connectWLANManualSSID",
      "connect"    => "connectWLANConnect" ] ],
  /*
   * dbinserter.php
   */
  "dbinserter"                   => [
  ],
  /*
   * decode.php
   */
  "decode"                       => [
    "ids"    => [
      "input" => "decodeInput" ],
    "params" => [
      "input" => "decodeInput" ],
    "names"  => [
      "frame" => "decodeFrame" ],
    "values" => [
      "decode"         => _ ( "Dekodieren" ),
      "codingNone"     => _ ( "Wert" ),
      "codingURL"      => _ ( "URL" ),
      "codingBase16"   => _ ( "Hex " ),
      "codingBase64"   => _ ( "Base64" ),
      "codingUnixtime" => _ ( "Unixtime" ),
      "codingMIME"     => _ ( "MIME" ),
      "icon"           => "fa-quote-right" ] ],
  /*
   * downloadCertificate.php
   */
  "downloadCertificate"          => [
  ],
  /*
   * editDevice.php
   */
  "editDevice"                   => [
    "params" => [
      "device"            => "deviceID",
      "deviceName"        => "deviceName",
      "changeName"        => "changeName",
      "property"          => "propertyID",
      "addProperty"       => "addProperty",
      "editProperty"      => "editProperty",
      "deleteProperty"    => "deleteProperty",
      "editPropertyName"  => "editPropertyName",
      "editPropertyValue" => "editPropertyValue",
      "propertyName"      => "propertyName",
      "propertyValue"     => "propertyValue",
    ],
    "ids"    => [
      "property"    => "property",
      "deleteModal" => "deleteModal",
      "addModal"    => "addModal",
      "editModal"   => "editModal",
      "tables"      => [
        "properties" => "propertiesTable" ] ],
    "values" => [
      "editProperty"   => _ ( "Eigenschaft bearbeiten" ),
      "deleteProperty" => _ ( "Eigenschaft löschen" ),
      "addProperty"    => _ ( "Eigenschaft hinzufügen" ) ] ],
  /*
   * evaluateCertificates.php
   */
  "evaluateCertificates"         => [
    "ids" => [
      "tables"               => [
        "certificates" => "certificatesTable" ],
      "recordingPanelPrefix" => "certificatesRecording" ]
  ],
  /*
   * evaluateContents.php
   */
  "evaluateContents"             => [
    "ids"    => [
      "tables"               => [
        "contents" => "contentsTable" ],
      "recordingPanelPrefix" => "contentsRecording" ],
    "values" => [
      "incomingIcon" => "<div style=\"white-space:nowrap; display:inline;\"><i class=\"fa fa-globe\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-home\"></i></div>",
      "outgoingIcon" => "<div style=\"white-space:nowrap; display:inline;\"><i class=\"fa fa-home\"></i> <i class=\"fa fa-long-arrow-right\"></i> <i class=\"fa fa-globe\"></i></div>" ] ],
  /*
   * evaluateCookies.php
   */
  "evaluateCookies"              => [
    "ids"    => [
      "tables"               => [
        "cookies" => "evaluateCookiesTable" ],
      "recordingPanelPrefix" => "cookiesRecording" ],
    "titles" => [
      "viewCookie" => _ ( "Cookie ansehen" ) ] ],
  /*
   * evaluateHeaders.php
   */
  "evaluateHeaders"              => [
    "titles" => [
      "viewDetails" => _ ( "Details ansehen" ) ],
    "ids"    => [
      "recordingPanelPrefix" => "headersRecording",
      "tables"               => [
        "headers" => "headersTable" ] ] ],
  /*
   * evaluateImages.php
   */
  "evaluateImages"               => [
    "ids" => [
      "recordingPanelPrefix" => "imagesRecording",
      "thumbnailPrefix"      => "imagesThumb",
      "modalPrefix"          => "imagesModal",
      "heightPrefix"         => "imagesHeight",
      "widthPrefix"          => "imagesWidth",
      "tables"               => [
        "images" => "imagesTable" ] ] ],
  /*
   * evaluateLinks.php
   */
  "evaluateLinks"                => [
    "ids" => [
      "recordingPanelPrefix" => "linksRecording",
      "tables"               => [
        "links" => "linksTable" ] ] ],
  /*
   * evaluateMetadata.php
   */
  "evaluateMetadata"             => [
    "ids" => [
      "recordingPanelPrefix" => "metadataRecording",
      "tables"               => [
        "metadata" => "metadataTable" ] ] ],
  /*
   * evaluateProperties.php
   */
  "evaluateProperties"           => [
    "ids" => [
      "recordingPanelPrefix" => "propertiesRecording",
      "searchPrefix"         => "searchProperty",
      "tables"               => [
        "properties" => "propertiesTable" ] ] ],
  /*
   * evaluateRecordings.php
   */
  "evaluateRecordings"           => [
    "values" => [
      "export" => _ ( "Aufzeichnung exportieren" ),
      "delete" => _ ( "Aufzeichnung löschen" ),
      "edit"   => _ ( "Aufzeichnung bearbeiten" ) ],
    "params" => [
      "recording" => "recording",
      "delete"    => "delete",
      "edit"      => "edit",
      "name"      => "recordingName",
      "device"    => "recordingDevice",
      "info"      => "recordingInfo" ],
    "ids"    => [
      "hiddenInput"           => "hiddenInput",
      "exportModal"           => "exportModal",
      "exportRecordingInfo"   => "exportRecordingInfo",
      "exportModalBody"       => "exportModalBody",
      "exportModalExport"     => "exportModalExport",
      "deleteModal"           => "deleteModal",
      "deleteRecordingInfo"   => "deleteRecordingInfo",
      "deleteProgress"        => "deleteProgress",
      "deleteProgressBar"     => "deleteProgressBar",
      "deleteProgressMessage" => "deleteProgressMessage",
      "deleteProgressText"    => "deleteProgressText",
      "editModal"             => "editModal",
      "editRecordingInfo"     => "editRecordingInfo",
      "editName"              => "editName",
      "editDevice"            => "editDevice",
      "editInfo"              => "editInfo",
      "tables"                => [
        "recordings" => "recordingsTable" ] ],
    "titles" => [
      "viewRecording" => _ ( "Aufzeichnung ansehen" ) ] ],
  /*
   * evaluateSslsuites.php
   */
  "evaluateSslsuites"            => [
    "ids" => [
      "tables" => [
        "encryptions"          => "encryptionsTable",
        "recordingPanelPrefix" => "sslsuitesRecording",
      ] ] ],
  /*
   * importRecording.php
   */
  "importRecording"              => [
    "files"  => [
      "uploadName" => "upload",
      "metaFile"   => "meta.xml" ],
    "params" => [
      "upload" => "upload",
      "abort"  => "abort" ],
    "ids"    => [
      "progressDiv" => "progress",
      "abortModal"  => "abort" ],
    "values" => [
      "upload" => _ ( "Aufzeichnung importieren" ),
      "abort"  => _ ( "Import vorzeitig beenden" ) ] ],
  /*
   * index.php
   */
  "index"                        => [
  ],
  /*
   * manageCertificate.php
   */
  "manageCertificate"            => [
    "values" => [
      "country"      => "C",
      "state"        => "ST",
      "location"     => "L",
      "organization" => "O",
      "unit"         => "OU",
      "commonName"   => "CN"
    ],
    "params" => [
      "create" => "createCertificate",
      "days"   => "certificateDays" ]
  ],
  /*
   * manageDevices.php
   */
  "manageDevices"                => [
    "params" => [
      "add"    => "addDevice",
      "delete" => "deleteDevice",
      "name"   => "deviceName" ],
    "ids"    => [
      "tables"      => [
        "devices" => "manageDevicesDevices" ],
      "deleteModal" => "manageDevicesDeleteModal",
      "addModal"    => "manageDevicesAddModal",
      "device"      => "deviceID",
      "name"        => "deviceName" ],
    "values" => [
      "deleteDevice" => _ ( "Gerät löschen" ),
      "addDevice"    => _ ( "Gerät hinzufügen" ) ] ],
  /*
   * mountMemoryStick.php
   */
  "mountMemoryStick"             => [
    "params" => [
      "mount" => "mount" ] ],
  /*
   * powerOff.php
   */
  "powerOff"                     => [
    "params" => [
      "restart"  => "restart",
      "shutdown" => "shutdown" ],
    "ids"    => [
      "bye" => "powerOffBye" ] ],
  /*
   * purgeDatabase.php
   */
  "purgeDatabase"                => [
    "ids"      => [
      "confirmationModal" => "purgeDatabaseModal",
      "deletionDoing"     => "current",
      "deletionDone"      => "past",
      "deleteItems"       => "purgeItems",
      "deleteButton"      => "deleteButton" ],
    "namesIds" => [
      "recordingsOption" => "purgeRecordings",
      "deviceOption"     => "purgeDevices",
      "whoisOption"      => "purgeWhois" ],
    "params"   => [
      "delete" => "delete" ],
    "values"   => [
      "delete" => _ ( "Datenbank leeren" ) ] ],
  /*
   * search.php
   */
  "search"                       => [
    "names"  => [
      "frame" => "searchFrame" ],
    "titles" => [
      "viewDetails" => _ ( "Details ansehen" ) ],
    "params" => [
      "search"     => "searchSearchString",
      "case"       => "searchSearchCase",
      "regexp"     => "searchSearchRegexp",
      "areas"      => "searchSearchAreas",
      "itemPrefix" => "searchItem" ],
    "values" => [
      "search" => _ ( "Suche" ),
      "icon"   => "fa-search" ],
    "ids"    => [
      "panels"       => "searchPanels",
      "search"       => "searchInput",
      "resultPrefix" => "searchResult",
      "tables"       => [
        "searchResult" => "searchResultTable" ] ],
    "js"     => [
      "searchFunctionName" => "suchErgebnis" ]
  ],
  /*
   * settings.php
   */
  "settings"                     => [
    "values" => [
      "applyButton" => _ ( "Ändern" ) ]
  ],
  /*
   * showCertificate.php
   */
  "showCertificate"              => [
    "params" => [
      "certificateID" => "certificateID",
      "recordingID"   => "recordingID" ],
    "ids"    => [
      "tables" => [
        "certificate" => "certificateCertificateTable",
        "connection"  => "certificateConnectionTable" ] ] ],
  /*
   * showConnection.php
   */
  "showConnection"               => [
    "ids" => [
      "requests"        => "connectionRequestsPanel",
      "udpSent"         => "connectionUDPSentPanel",
      "udpSentBody"     => "connectionUDPSentBodyPanel",
      "udpReceived"     => "connectionUDPReceivedPanel",
      "udpReceivedBody" => "connectionUDPReceivedBodyPanel",
      "content"         => "connectionContentPanel",
      "contentBody"     => "connectionContentBodyPanel",
      "navigation"      => "connectionNavigationPanel",
      "tables"          => [
        "requests" => "connectionRequestsTable" ] ]
  ],
  /*
   * showContents.php
   */
  "showContents"                 => [
    "params" => [
      "contents" => "contentsContents" ],
    "ids"    => [
      "tables"      => [
        "properties" => "contentsPropertiesTable",
        "metadata"   => "contentsMetadataTable" ],
      "metadata"    => "contentsMetadata",
      "native"      => "contentsNativeContent",
      "content"     => "contentsContent",
      "contentBody" => "contentsContentBody" ] ],
  /*
   * showCookie.php
   */
  "showCookie"                   => [
    "params" => [
      "cookie"    => "cookieID",
      "recording" => "recordingID"
    ],
    "ids"    => [
      "tables" => [
        "cookie"     => "cookieTable",
        "setcookie"  => "setcookieTable",
        "sendcookie" => "sendcookieTable" ] ]
  ],
  /*
   * showHeader.php
   */
  "showHeader"                   => [
    "params" => [
      "recording" => "recordingID",
      "response"  => "isResponse",
      "value"     => "headerValue" ],
    "ids"    => [
      "tables" => [
        "details" => "headerDetailsTable",
        "usage"   => "headerUsageTable"
      ] ] ],
  /*
   * showLink.php
   */
  "showLink"                     => [
    "params" => [
      "source"    => "linkSource",
      "target"    => "linkTarget",
      "recording" => "recordingID" ],
    "ids"    => [
      "tables" => [
        "link"          => "linkTable",
        "requestPrefix" => "requestTable" ] ] ],
  /*
   * showRecording.php
   */
  "showRecording"                => [
    "params" => [
      "recording"  => "aufzeichnung",
      "connection" => "verbindung",
      "request"    => "request" ],
    "ids"    => [
      "tables" => [
        "connections" => "recordingConnectionsTable" ] ],
    "titles" => [
      "viewConnection" => _ ( "Verbindung ansehen" ) ] ],
  /*
   * showRequest.php
   */
  "showRequest"                  => [
    "params" => [
      "request" => "requestID" ],
    "ids"    => [
      "navigation"          => "navigationPanel",
      "requestHeader"       => "requestHeaderPanel",
      "requestParameter"    => "requestParameterPanel",
      "requestContent"      => "requestContentPanel",
      "requestContentBody"  => "requestContentPanelBody",
      "responseHeader"      => "responseHeaderPanel",
      "responseContent"     => "responseContentPanel",
      "responseContentBody" => "responseContentPanelBody",
      "responseView"        => "responseViewPanel",
      "metaData"            => "metaDataPanel",
      "tables"              => [
        "requestHeader"    => "requestHeaderTable",
        "requestParameter" => "requestParameterTable",
        "responseHeader"   => "responseHeaderTable",
        "metaData"         => "metaDataTable" ] ],
    "titles" => [
      "viewRequest" => _ ( "Request ansehen" ) ] ],
  /*
   * showSslsuite.pho
   */
  "showSslsuite"                 => [
    "params" => [
      "cipherSuite" => "suite" ],
    "ids"    => [
      "tables" => [
        "suite"       => "sslsuiteSuiteTable",
        "connections" => "sslsuiteConnectionsTable" ] ] ],
  /*
   * startStopRecording.php
   */
  "startStopRecording"           => [
    "params" => [
      "name"    => "recordingName",
      "infos"   => "infos",
      "source"  => "recordingSource",
      "sources" => "recordingSourceList",
      "device"  => "managedDevice",
      "start"   => "startRecording",
      "stop"    => "stopRecording",
      "cancel"  => "cancelRecording" ],
    "ids"    => [
      "progressDiv" => "progress",
      "cancelModal" => "cancel",
      "name"        => "recordingName",
      "infos"       => "infos",
      "source"      => "recordingSource",
      "device"      => "managedDevice",
      "connection"  => "connectionTableBody"
    ],
    "values" => [
      "stop"                  => _ ( "Aufzeichnung beenden" ),
      "cancel"                => _ ( "Datenübernahme vorzeitig beenden" ),
      "sessionFile"           => "activeRecording", // relative to temp_dir
      "connectionDir"         => "connections",
      "certificateDir"        => "certificates",
      "connectionLog"         => "connection.log",
      "php5Binary"            => "/usr/bin/php5",
      "pidFileExtension"      => ".pid",
      "dbinserterScript"      => "dbinserter.php",
      "dbinserterOutput"      => "dbinserter.output",
      "dbinserterPid"         => "dbinserter.pid",
      "sslsplitBinary"        => "sslsplit", // relative to base_dir
      "sslsplitOutput"        => "sslsplit.output",
      "sslsplitPid"           => "sslsplit.pid",
      "tcpdumpBinary"         => "/usr/sbin/tcpdump",
      "tcpdumpPcap"           => "tcpdump.pcap",
      "tcpdumpOutput"         => "tcpdump.output",
      "tcpdumpPid"            => "tcpdump.pid",
      "tcpdumpInternalPrefix" => "internal_",
      "tcpdumpExternalPrefix" => "external_",
      "iptablesBinary"        => "/sbin/iptables",
      "keyFile"               => "cert.key", // relative to base_dir
      "certFile"              => "cert.crt", // relative to base_dir
      "tcpProxyPort"          => 65534, // sslsplit relay port for non ssl traffic 
      "sslProxyPort"          => 65535, // sslsplit relay port for ssl traffic 
      "connectionLogMaxItems" => 10, // max number of latest connections to show in live preview
      "connectionLogUpdateMS" => 2000
    ] ],
  /*
   * systemStatus.php
   */
  "systemStatus"                 => [
    "ids" => [
      "statusPanel" => "systemStatus" ] ],
  /*
   * tutorial.php
   */
  "tutorial"                     => [
    "ids" => [
      "panelGroup" => "tutorialPanels" ] ],
  /*
   * unmountMemoryStick.php
   */
  "unmountMemoryStick"           => [
  ],
  /*
   * updateReport.php
   */
  "updateReport"                 => [
    "params" => [
      "oldVersion" => "old" ] ],
  /*
   * updates.php
   */
  "updates"                      => [
    "params" => [
      "checkUpdate"   => "check",
      "installUpdate" => "install",
      "installURL"    => "url" ],
    "values" => [
      "gitVersionField" => "tag_name" /* version number JSON field name; git specific */,
      "gitTarballField" => "tarball_url" /* JSON field name of where to find latest release tarball; git specific */,
      "tarDir"          => "/tmp" /* working directory to untar files in */,
      "tarFile"         => "updates.tgz" /* name of the local tar file */ ],
    "ids"    => [
      "updateScreen" => "updateScreen",
      "bye"          => "byePanel" ],
    "urls"   => [
      "changeLog"     => "https://api.github.com/repos/ulkuehn/LiMiT1/contents/CHANGELOG.md", /* changelog of the project */
      "latestRelease" => "https://api.github.com/repos/ulkuehn/LiMiT1/releases/latest", /* where to find JSON information about the latest release */
      "allReleases"   => "https://api.github.com/repos/ulkuehn/LiMiT1/releases" /* where to find JSON array of all releases */ ]
  ],
  /*
   * whois.php
   */
  "whois"                        => [
    "ids"    => [
      "textViewPrefix"  => "textView",
      "tableViewPrefix" => "tableView",
      "tables"          => [
        "whois" => "whoisTable" ] ],
    "params" => [
      "whois"   => "whois",
      "refresh" => "refresh",
      "regexp"  => "regexp" ],
    "titles" => [
      "viewWhois" => _ ( "detaillierte Abfrage" ) ],
    "values" => [
      "whois" => _ ( "Whois" ),
      "icon"  => "fa-institution" ],
    "names"  => [
      "frame" => "whoisFrame" ] ],
  /*
   * 
   * includes
   * 
   */
  /*
   * include/archiveRecording.php
   */
  "include/archiveRecording"     => [
    "values" => [
      "archiveName" => "archive.zip" // relative to data_dir
    ],
    "params" => [
      "recording" => "recording" ] ],
  /*
   * include/buttonState.php
   */
  "include/buttonState"          => [
  ],
  /*
   * include/certificateUtility.php
   */
  "include/certificateUtility"   => [
  ],
  /*
   * include/closeHTML.php
   */
  "include/closeHTML"            => [
  ],
  /*
   * include/configuration.php
   */
  "include/configuration"        => [
    "files" => [
      "configuration" => "configuration" ] /* name of configuration file in base dir */ ],
  /*
   * include/connectDB.php
   */
  "include/connectDB"            => [
  ],
  /*
   * include/connectionBrowser.php
   */
  "include/connectionBrowser"    => [
    "ids"  => [
      "tables" => [
        "connection" => "connectionBrowserConnectionTable" ] ],
    "vars" => [
      "connection" => "connectionBrowserConnection" ] ],
  /*
   * include/connections.php
   */
  "include/connections"          => [
    "params" => [
      "recording" => "id",
      "timeStamp" => "ts",
      "maxItems"  => "mi" ] ],
  /*
   * include/constants.php
   */
  "include/constants"            => [
    "files" => [
      "constants" => "constants" ] /* name of constants file in base dir */ ],
  /*
   * include/contentUtility.php
   */
  "include/contentUtility"       => [
    "values" => [
      "nonprintableCharacterSubstitute" => " &middot; ", /* replace each nonprintable character with this text/html */
      "longContentLength"               => 1000, /* long content is truncated to this manycharacters on first display */
      "longContentGraceFactor"          => 150 / 100, /* overlong content is only truncated if length exceeds longContentLength*longContentGraceFactor */
      "hugeContentLength"               => 1025 * 500 /* length where user is warned of processing time when trying to view /load whole content */ ],
    "ids"    => [
      "unmarkedPrefix"             => "unmarked_",
      "prettyPrintPrefix"          => "pp_",
      "wordsMarkedPrefix"          => "words_",
      "numbersMarkedPrefix"        => "numbers_",
      "urlsMarkedPrefix"           => "urls_",
      "emailAddressesMarkedPrefix" => "emails_",
      "propertiesMarkedPrefix"     => "properties_",
      "buttonPrefix"               => "button_",
      "loadAlertPrefix"            => "loadAlert",
      "loadProgressPrefix"         => "loadProgress"
    ] ],
  /*
   * include/downloadArchive.php
   */
  "include/downloadArchive"      => [
    "params" => [
      "recording" => "id" ] ],
  /*
   * include/filterUtility.php
   */
  "include/filterUtility"        => [
    "ids"    => [
      "form"   => "filterForm",
      "select" => "filterSelect" ],
    "names"  => [
      "cookie" => "filter" ],
    "params" => [
      "showRecording" => "show"
    ],
    "values" => [
      "allRecordings"          => "alle",
      "eachRecording"          => "jede",
      "noRecordings"           => "keine",
      "maxRecordingNameLength" => 50,
      "reordingNameEllipses"   => "..." ] ],
  /*
   * include/goOnline.php
   */
  "include/goOnline"             => [
    "values"    => [
      "onlineFlag" => "online_flag" // relative to temp_dir
    ],
    "responses" => [
      "success" => "1",
      "failure" => "0" ] ],
  /*
   * include/httpHeaders.php
   */
  "include/httpHeaders"          => [
    "vars" => [
      "starttime" => "httpHeadersStartTime" ] ],
  /*
   * include/httpStatusUtility.php
   */
  "include/httpStatusUtility"    => [
  ],
  /*
   * memoryStickUtility.php
   */
  "memoryStickUtility"           => [
  ],
  /*
   * include/mockup.php
   */
  "include/mockup"               => [
    "params" => [
      "skin" => "skin" ] ],
  /*
   * include/onlineOfflineUtility.php
   */
  "include/onlineOfflineUtility" => [
    "values" => [
      "onlineScript"  => "online.sh", // relative to temp_dir
      "offlineScript" => "offline.sh", // relative to temp_dir
      "donePostfix"   => ".executed"
    ],
    "ids"    => [
      "progress" => "onlineProgress",
      "success"  => "onlineSuccess",
      "failure"  => "onlineFailure"
    ]
  ],
  /*
   * include/openHTML.php
   */
  "include/openHTML"             => [
    "js"     => [
      "mouseOverFunc" => "whoisOver",
      "mouseOutFunc"  => "whoisOut" ],
    "values" => [
      "neutral"     => "whois-neutral", // corresponds to styles as defined in $my_name.css
      "unknown"     => "whois-unknown",
      "known"       => "whois-known",
      "bootFile"    => "boot", // relative to temp_dir
      "bootingFile" => "booting" // relative to temp_dir
    ],
    "vars"   => [
      "title" => "openHTMLTitle",
      "frame" => "openHTMLFrame" ],
    "ids"    => [
      "splashModal" => "openHTMLSplash",
      "timeInfo"    => "openHTMLtimeInfo" ] ],
  /*
   * include/probeHardware.php
   */
  "include/probeHardware"        => [
  ],
  /*
   * include/processUtility.php
   */
  "include/processUtility"       => [
  ],
  /*
   * include/rebootUtility.php
   */
  "include/rebootUtility"        => [
  ],
  /*
   * include/recordingBrowser.php
   */
  "include/recordingBrowser"     => [
    "ids"    => [
      "tables" => [
        "recording" => "recordingBrowserRecordingTable" ] ],
    "vars"   => [
      "recording" => "recordingBrowserRecording" ],
    "values" => [
      "backButtonInactive"    => "<span class=\"btn btn-primary btn-xs\" disabled=\"disabled\"><i class=\"fa fa-arrow-left\"></i></span>",
      "forwardButtonInactive" => "<span class=\"btn btn-primary btn-xs\" disabled=\"disabled\"><i class=\"fa fa-arrow-right\"></i></span>",
      "backButton"            => "<span class=\"btn btn-primary btn-xs\"><i class=\"fa fa-arrow-left\"></i></span>",
      "forwardButton"         => "<span class=\"btn btn-primary btn-xs\"><i class=\"fa fa-arrow-right\"></i></span>" ] ],
  /*
   * include/requestBroswer.php
   */
  "include/requestBrowser"       => [
    "vars" => [
      "request"  => "requestBrowserRequest",
      "response" => "requestBrowserResponse" ],
    "ids"  => [
      "tables" => [
        "request" => "requestBroswerRequestTable" ] ] ],
  /*
   * include/scanWifi.php
   */
  "include/scanWifi"             => [
    "values" => [
      "connect" => _ ( "Verbinden" ) ] ],
  /*
   * include/searchResult.php
   */
  "include/searchResult"         => [
    "titles" => [
      "previousSearchResult" => _ ( "vorherige Fundstelle" ),
      "nextSearchResult"     => _ ( "nächste Fundstelle" ) ],
    "params" => [
      "htmlID"            => "id",
      "recordingFilter"   => "show",
      "searchArea"        => "ort",
      "searchIndex"       => "limit",
      "whatToSearch"      => "nadel",
      "isRegexp"          => "isReg",
      "isCaseSensitive"   => "isCase",
      "previousPositions" => "prepos",
      "actualPosition"    => "pos" ] ],
  /*
   * include/searchUtility.php
   */
  "include/searchUtility"        => [
    "values" => [
      "httpRequests"  => "requests",
      "httpResponses" => "responses",
      "httpHeader"    => "header",
      "content"       => "content",
      "matchField"    => "_regTest"
    ] ],
  /*
   * include/showCertificate.php
   */
  "include/showCertificate"      => [
    "ids" => [
      "certificate" => "showCertificatePanel",
      "tables"      => [
        "certificate" => "showCertificateTable" ] ] ],
  /*
   * include/showDatabaseProgress.php
   */
  "include/showDatabaseProgress" => [
    "params" => [
      "id"    => "id",
      "start" => "start" ] ],
  /*
   * include/showEncryption.php
   */
  "include/showEncryption"       => [
    "ids" => [
      "encryption" => "showEncryptionPanel",
      "tables"     => [
        "encryption" => "showEncryptionTable" ] ] ],
  /*
   * include/showFullContent.php
   */
  "include/showFullContent"      => [
    "params" => [
      "key"   => "id",
      "index" => "nr" ] ],
  /*
   * include/showMIMEContent.php
   */
  "include/showMIMEContent"      => [
    "params" => [
      "type" => "type",
      "id"   => "id" ],
    "values" => [
      "request"  => "request",
      "response" => "response" ] ],
  /*
   * include/showReceivedCookies.php
   */
  "include/showReceivedCookies"  => [
    "vars" => [
      "statement" => "showReceivedCookiesStatement" ],
    "ids"  => [
      "cookies" => "showReceivedCookiesPanel",
      "tables"  => [
        "cookies" => "showReceivedCookiesTable" ] ] ],
  /*
   * include/showSentCookies.php
   */
  "include/showSentCookies"      => [
    "vars" => [
      "statement" => "showSentCookiesStatement" ],
    "ids"  => [
      "cookies" => "showSentCookiesPanel",
      "tables"  => [
        "cookies" => "showSentCookiesCookieTable" ] ] ],
  /*
   * include/status.php
   */
  "include/status"               => [
  ],
  /*
   * include/statusInfo.php
   */
  "include/statusInfo"           => [
  ],
  /*
   * include/tableUtility.php
   */
  "include/tableUtility"         => [
    "ids"    => [
      "foldedPrefix"   => "compact",
      "unfoldedPrefix" => "full" ],
    "values" => [
      "maxLengthFolded" => 20,
      "foldedEllipses"  => "&nbsp;<strong><span style=\"white-space: nowrap;\">&middot;&middot;&middot;</span></strong>&nbsp;",
      "foldedIcon"      => "fa-chevron-down",
      "unfoldedIcon"    => "fa-chevron-up",
      "filterIcon"      => "fa-filter"
    ] ],
  /*
   * include/timeUtility.php
   */
  "include/timeUtility"          => [
  ],
  /*
   * include/topMenu.php
   */
  "include/topMenu"              => [
    "ids"    => [
      "statusModal"     => "topMenuStatusModal",
      "statusContent"   => "topMenuStatusContent",
      "navBar"          => "topMenuNavBar",
      "recordButton"    => "topMenuRecordButton",
      "lanOnline"       => "topMenuLan",
      "wlanOnline"      => "topMenuWlan",
      "umtsOnline"      => "topMenuUmts",
      "offline"         => "topMenuOffline",
      "memoryStick"     => "topMenuMemoryStick",
      "memoryStickIcon" => "topMenuMemoryStickIcon",
      "utilityPrefix1"  => "_",
      "utilityPrefix2"  => "__" ],
    "js"     => [
      "statusFunction" => "topMenuStatus ()",
      "buttonFunction" => "buttonState ()"
    ],
    "urls"   => [
      "statusProvider" => "include/statusInfo.php",
      "buttonProvider" => "include/buttonState.php"
    ],
    "values" => [
      "recordStart"                     => "<a href=\"startStopRecording.php\"><span class=\"text-success\"><i class=\"fa fa-dot-circle-o fa-lg\"></i></span></a>",
      "recordStop"                      => "<a href=\"startStopRecording.php\"><span class=\"text-danger\"><i class=\"fa fa-stop fa-lg flash\"></i></span></a>",
      "recordEnd"                       => "<a href=\"startStopRecording.php\"><span class=\"text-warning\"><i class=\"fa fa-database fa-lg flash\"></i></span></a>",
      "mountMemoryStick"                => "<a href=\"mountMemoryStick.php\"><i class=\"fa fa-chain fa-fw topmenu\"></i>",
      "unmountMemoryStick"              => "<a href=\"unmountMemoryStick.php\"><i class=\"fa fa-chain-broken fa-fw topmenu\"></i>",
      "statusMenuIcon"                  => "fa-info-circle",
      "internetMenuName"                => _ ( "Internet" ),
      "internetLANMenuName"             => _ ( "LAN" ),
      "internetLANMenuIcon"             => "fa-sitemap",
      "internetWLANMenuName"            => _ ( "WLAN" ),
      "internetWLANMenuIcon"            => "fa-wifi",
      "internetUMTSMenuName"            => _ ( "UMTS" ),
      "internetUMTSMenuIcon"            => "fa-signal",
      "internetOfflineMenuName"         => _ ( "Offline" ),
      "internetOfflineMenuIcon"         => "fa-cut",
      "evaluateMenuIcon"                => "fa-bars",
      "evaluateMenuName"                => _ ( "Auswerten" ),
      "evaluateMenuRecordingsMenuName"  => _ ( "Aufzeichnungen" ),
      "evaluatePropertiesMenuName"      => _ ( "Eigenschaften" ),
      "evaluateContentsMenuName"        => _ ( "Inhalte" ),
      "evaluateImagesMenuName"          => _ ( "Bilder" ),
      "evaluateMetadataMenuName"        => _ ( "Metadaten" ),
      "evaluateHeadersMenuName"         => _ ( "HTTP-Header" ),
      "evaluateCookiesMenuName"         => _ ( "Cookies" ),
      "evaluateLinksMenuName"           => _ ( "Verweise" ),
      "evaluateSSLMenuName"             => _ ( "SSL-Verschlüsselung" ),
      "evaluateCertificatesMenuName"    => _ ( "Zertifikate" ),
      "toolsMenuName"                   => _ ( "Werkzeuge" ),
      "toolsSearchMenuName"             => _ ( "Suche" ),
      "toolsDecodeMenuName"             => _ ( "Dekodieren" ),
      "toolsWhoisMenuName"              => _ ( "Whois" ),
      "toolsManageDevicesMenuName"      => _ ( "Geräte verwalten" ),
      "toolsCertificateMenuName"        => _ ( "Eigenes Zertifikat" ),
      "toolsCertificateMenuIcon"        => "fa-certificate",
      "toolsImportMenuName"             => _ ( "Aufzeichnung importieren" ),
      "toolsPurgeMenuName"              => _ ( "Datenbank leeren" ),
      "toolsSettingsMenuName"           => _ ( "Einstellungen" ),
      "toolsSettingsMenuIcon"           => "fa-wrench",
      "toolsTutorialMenuName"           => _ ( "Tutorial" ),
      "toolsUpdateMenuName"             => _ ( "Updates" ),
      "toolsStatusMenuName"             => _ ( "Status" ),
      "toolsAboutMenuName"              => _ ( "Über" ),
      "toolsMountMemoryStickMenuName"   => _ ( "USB-Stick verbinden" ),
      "toolsUnmountMemoryStickMenuName" => _ ( "USB-Stick lösen" ),
      "powerMenuName"                   => _ ( "Betrieb" ),
      "powerMenuIcon"                   => "fa-plug" ] ],
  /*
   * include/utility.php
   */
  "include/utility"              => [
    "ids"    => [
      "helpModal" => "helpModal" ],
    "values" => [
      "badSign"   => "<span class=\"btn btn-danger btn-xs\"><i class=\"fa fa-close fa-fw\"></i></span>",
      "mehSign"   => "<span class=\"btn btn-warning btn-xs\"><i class=\"fa fa-exclamation fa-fw\"></i></span>",
      "goodSign"  => "<span class=\"btn btn-success btn-xs\"><i class=\"fa fa-check fa-fw\"></i></span>",
      "questSign" => "<span class=\"btn btn-info btn-xs\"><i class=\"fa fa-question fa-fw\"></i></span>",
      "helpSign"  => "fa-question-circle"
    ] ]
];

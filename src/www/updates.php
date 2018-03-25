<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file updates.php
 * 
 * check for updates and install them
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");
require ("include/rebootUtility.php");

/**
 * id of the html element to send update info to
 */
$updateelt = "updateinfo";


require ("include/httpHeaders.php");
require ("include/openHTML.php");
require ("include/topMenu.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * load some JSON information from the web and decode it
 * 
 * @param string $url the URL where the JSON data can be retreived
 * @return string decoded JSON or empty string if an error occured
 */
function getJSON ( $url )
{
  global $my_name;

  try
  {
    $json = file_get_contents ( $url,
                                false,
                                stream_context_create ( array (
      "http" => array (
        "user_agent" => $my_name ) ) ) );

    if ( $json === false )
    {
      return "";
    }
    else
    {
      $jsonDecoded = json_decode ( $json,
                                   TRUE );
      if ( $jsonDecoded == NULL )
      {
        return "";
      }
      else
      {
        return $jsonDecoded;
      }
    }
  } catch (Exception $e)
  {
    return "";
  }
}


function installFrom ( $newVersion,
                       $tarBall )
{
  global $__, $my_name;

  echo "<form class=\"form-horizontal\" method=\"post\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
  echo _ ( "Update installieren" );
  echo "</h4></div><div class=\"panel-body\">";
  showInfoMessage ( _ ( "Es ist eine neue Version verfügbar ($newVersion)" ) );
  showAlertMessage ( _ ( "Das Update benötigt einige Zeit und führt anschließend einen Neustart durch. Das Browserfenster kann währenddessen geöffnet bleiben." ) );
  echo "<input type=\"hidden\" name=\"", $__[ "updates" ][ "params" ][ "installURL" ], "\" value=\"$tarBall\">";
  echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
  echo _ ( "Neue Version herunterladen und installieren" );
  echo "\" name=\"", $__[ "updates" ][ "params" ][ "installUpdate" ], "\">";
  echo "</div></div></div></form>";

  try
  {
    $changelog = file_get_contents ( $__[ "updates" ][ "urls" ][ "changeLog" ],
                                     false,
                                     stream_context_create ( array (
      "http" => array (
        "user_agent" => $my_name ) ) ) );

    if ( $changelog !== false )
    {
      $changelogDecoded = json_decode ( $changelog,
                                        TRUE );
      if ( $changelogDecoded != NULL )
      {
        echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
        echo _ ( "Änderungsverlauf" );
        echo "</h4></div><div class=\"panel-body\">";

        require ("include/Parsedown.php");
        $Parsedown = new Parsedown();

        echo $Parsedown->text ( base64_decode ( $changelogDecoded[ "content" ] ) );
        echo "</div></div></div>";
      }
    }
  } catch (Exception $e)
  {
    
  }
}


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

titleAndHelp ( _ ( "Updates" ),
                   _ ( "Wenn $my_name online ist, kann geprüft werden, ob eine neue Version verfügbar ist und das System ggf. aktualisiert werden." ) );

/*
 * do actual update
 */
if ( isset ( $_REQUEST[ $__[ "updates" ][ "params" ][ "installUpdate" ] ] ) )
{
  $error = FALSE;

  if ( isset ( $_REQUEST[ $__[ "updates" ][ "params" ][ "installURL" ] ] ) )
  {
    try
    {
      $tar = file_get_contents ( $_REQUEST[ $__[ "updates" ][ "params" ][ "installURL" ] ],
                                 false,
                                 stream_context_create ( array (
        "http" => array (
          "user_agent" => $my_name ) ) ) );
      if ( $tar === false )
      {
        $error = TRUE;
      }
      else
      {
        $fp = fopen ( $__[ "updates" ][ "values" ][ "tarDir" ] . "/" . $__[ "updates" ][ "values" ][ "tarFile" ],
                      "w" );
        if ( $fp == FALSE )
        {
          $error = TRUE;
        }
        else
        {
          if ( fwrite ( $fp,
                        $tar ) == FALSE )
          {
            $error = TRUE;
            fclose ( $fp );
          }
          else
          {
            fclose ( $fp );
            system ( "/bin/tar xzf " . $__[ "updates" ][ "values" ][ "tarDir" ] . "/" . $__[ "updates" ][ "values" ][ "tarFile" ] . " -C " . $__[ "updates" ][ "values" ][ "tarDir" ] . " --wildcards \"*limitify.sh\" \"*limit1.tar.bz2\" --strip=1",
                     $r );
            if ( $r != 0 )
            {
              $error = TRUE;
            }
            else
            {
              echo "<pre class=\"pre-scrollable\" id=\"", $__[ "updates" ][ "ids" ][ "updateScreen" ], "\"></pre>";
              echo "<script> function update (text) { var div = document.getElementById(\"", $__[ "updates" ][ "ids" ][ "updateScreen" ], "\"); div.innerHTML += text; div.scrollTop = div.scrollHeight; }</script>";

              $phandle = popen ( "cd " . $__[ "updates" ][ "values" ][ "tarDir" ] . "; /bin/bash limitify.sh 2>&1",
                                 "r" );
              while ( ($line = fgets ( $phandle )) != false )
              {
                echo "<script>update(\"", jsSave ( $line ), "\");</script>";
                flush ();
                ob_flush ();
              }
              if ( pclose ( $phandle ) != 0 )
              {
                // update process terminated non-sucessfully
                showErrorMessage ( _ ( "Das Update war nicht erfolgreich" ) );
              }
              else
              {
                echo "<div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
                echo _ ( "Die neueste Version wurde erfolgreich installiert" );
                echo"</h4></div><div class=\"panel-body\" id=\"", $__[ "updates" ] [ "ids" ] [ "bye" ], "\"><h1>";
                echo _ ( "$my_name ist in Kürze zurück ..." );
                echo "</h1></div></div></div>";
                exec ( "(/bin/sleep 3 && /sbin/reboot) > /dev/null 2>&1 &" );
                waitForReboot ( $__[ "updates" ] [ "ids" ] [ "bye" ],
                                "fa-clock-o fa-2x",
                                "/updateReport.php?" . $__[ "updateReport" ][ "params" ][ "oldVersion" ] . "=$my_version" );
              }
            }
          }
        }
      }
    } catch (Exception $e)
    {
      $error = TRUE;
    }
  }
  else
  {
    $error = TRUE;
  }
  if ( $error )
  {
    echo "<div class=\"row\">";
    showErrorMessage ( _ ( "Die aktuelle Version konnte nicht heruntergeladen werden." ) );
    echo "</div>";
  }
}

/*
 * do actual check for update
 */
elseif ( isset ( $_REQUEST[ $__[ "updates" ][ "params" ][ "checkUpdate" ] ] ) )
{
  $jsonDecoded = getJSON ( $__[ "updates" ][ "urls" ][ "latestRelease" ] );
  if ( $jsonDecoded != "" )
  {
    $newVersion = $jsonDecoded[ $__[ "updates" ][ "values" ][ "gitVersionField" ] ];
    preg_match ( "/([0-9]+)\.([0-9]+)\.([0-9]+)$/",
                 $my_version,
                 $installed );
    preg_match ( "/([0-9]+)\.([0-9]+)\.([0-9]+)$/",
                 $newVersion,
                 $available );
    /*
     * major release change: no automatic update (newer kernel used)
     */
    if ( $installed[ 1 ] < $available[ 1 ] )
    {
      echo "<div class=\"row\">";
      showAlertMessage ( _ ( "Die aktuelle Version $newVersion stellt gegenüber der installierten Version $my_version einen Hauptversionssprung dar. Ein Update auf die aktuelle Version kann nicht automatisch, sondern nur manuell durchgeführt werden, indem das $my_name-System neu aufgebaut wird. Dies wird dringend empfohlen!" ) );
      echo "</div>";

      $jsonDecoded = getJSON ( $__[ "updates" ][ "urls" ][ "allReleases" ] );
      if ( $jsonDecoded != "" )
      {
        /*
         * search for a minor release update
         */
        $tarBall = "";
        $newVersion = "";
        foreach ( $jsonDecoded as $i => $release )
        {
          $version = $release[ $__[ "updates" ][ "values" ][ "gitVersionField" ] ];
          preg_match ( "/([0-9]+)\.([0-9]+)\.([0-9]+)$/",
                       $version,
                       $available );
          /*
           * newer release within that major version
           */
          if ( $installed[ 1 ] == $available[ 1 ] && ($installed[ 2 ] < $available[ 2 ] || ($installed[ 2 ] == $available[ 2 ] && $installed[ 3 ] < $available[ 3 ] ) ) )
          {
            $tarBall = $release[ $__[ "updates" ][ "values" ][ "gitTarballField" ] ];
            $newVersion = $version;
            $installed = $available;
          }
        }
        if ( $tarBall != "" )
        {
          installFrom ( $newVersion,
                        $tarBall );
        }
      }
    }

    /*
     * minor release change: automatic update possible
     */
    else if ( ($installed[ 1 ] == $available[ 1 ] && $installed[ 2 ] < $available[ 2 ]) ||
      ($installed[ 1 ] == $available[ 1 ] && $installed[ 2 ] == $available[ 2 ] && $installed[ 3 ] < $available[ 3 ]) )
    {
      installFrom ( $newVersion,
                    $jsonDecoded[ $__[ "updates" ][ "values" ][ "gitTarballField" ] ] );
    }
    /*
     * no update
     */
    else
    {
      echo "<div class=\"row\">";
      showInfoMessage ( _ ( "Die installierte Version $my_version ist aktuell" ) );
      echo "</div>";
    }
  }
  else
  {
    echo "<div class=\"row\">";
    showErrorMessage ( _ ( "Die aktuell verfügbare Version konnte nicht ermittelt werden." ) );
    echo "</div>";
  }
}

/*
 * provide for update check
 */
else
{
  echo "<form class=\"form-horizontal\" method=\"post\"><div class=\"row\"><div class=\"panel panel-primary\"><div class=\"panel-heading\"><h4 class=\"panel-title\">";
  echo _ ( "Auf Updates prüfen" );
  echo "</h4></div><div class=\"panel-body\">";

  if ( is_readable ( $temp_dir . "/" . $__[ "include/onlineOfflineUtility" ] [ "values" ] [ "offlineScript" ] ) )
  {
    if ( file_exists ( $temp_dir . "/" . $__[ "startStopRecording" ] [ "values" ] [ "sessionFile" ] ) )
    {
      showErrorMessage ( _ ( "Aktuell erfolgt eine Aufzeichnung. Diese vor einem Update bitte beenden." ) );
    }
    else
    {
      echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
      echo _ ( "Aktuell verfügbare Version prüfen" );
      echo "\" name=\"", $__[ "updates" ][ "params" ][ "checkUpdate" ], "\">";
    }
  }
  else
  {
    showErrorMessage ( _ ( "$my_name ist nicht online. Die Update-Prüfung setzt eine Internetverbindung voraus." ) );
  }
  echo "</div></div></div></form>";
}

require ("include/closeHTML.php");

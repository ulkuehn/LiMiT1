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
 * used to check for updates and install them
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");

/**
 * form parameter to check for updates
 */
$checkUpdate = "check";
/**
 * form parameter to install update
 */
$installUpdate = "install";
/**
 * form parameter for tarball URL
 */
$installURL = "url";
/**
 * where to find JSON information about the latest release
 */
$latestReleaseURL = "https://api.github.com/repos/ulkuehn/LiMiT1/releases/latest";
/**
 * version number JSON field name
 */
$versionField = "tag_name";
/**
 * JSON field name of where to find latest release tarball
 */
$tarballField = "tarball_url";
/**
 * working directory to untar files in
 */
$tarDir = "/tmp";
/**
 * name of the local tar file
 */
$tarFile = "$my_name.tgz";
/**
 * id of the html element to send update info to
 */
$updateelt = "updateinfo";


require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");


/* ===========================================================================
 * 
 * MAIN CODE
 * 
 * ======================================================================== */

titleAndHelp ( _ ( "Updates" ), _ ( "Wenn $my_name online ist, kann geprüft werden, ob eine neue Version verfügbar ist und das System ggf. aktualisiert werden." ) );

/*
 * do actual update
 */
if ( isset ( $_REQUEST[ $installUpdate ] ) )
{
  $error = FALSE;

  if ( isset ( $_REQUEST[ $installURL ] ) )
  {
    try
    {
      $tar = file_get_contents ( $_REQUEST[ $installURL ], false, stream_context_create ( array ( "http" => array ( "user_agent" => $my_name ) ) ) );
      if ( $tar === false )
      {
        $error = TRUE;
      }
      else
      {
        $fp = fopen ( "$tarDir/$tarFile", "w" );
        if ( $fp == FALSE )
        {
          $error = TRUE;
        }
        else
        {
          if ( fwrite ( $fp, $tar ) == FALSE )
          {
            $error = TRUE;
            fclose ( $fp );
          }
          else
          {
            fclose ( $fp );
            system ( "/bin/tar xzf $tarDir/$tarFile -C $tarDir --wildcards \"*limitify.sh\" \"*limit1.tar.bz2\" --strip=1", $r );
            if ( $r != 0 )
            {
              $error = TRUE;
            }
            else
            {
              echo <<<LIMIT1
<pre class="pre-scrollable" id="$updateelt"></pre>
<script>
function update (text)
{
  var div = document.getElementById("$updateelt");
  div.innerHTML += text;
  div.scrollTop = div.scrollHeight;
}
</script>
LIMIT1;
              $phandle = popen ( "cd $tarDir; /bin/bash limitify.sh 2>&1", "r" );
              while ( ($line = fgets ( $phandle )) != false )
              {
                echo "<script>update(\"", jsSave ( $line ), "\");</script>";
                flush ();
                ob_flush ();
              }
              if ( pclose ( $phandle ) != 0 )
              {
                // update process terminated non-sucessfully
                errorMsg ( _ ( "Das Update war nicht erfolgreich" ) );
              }
              else
              {
                echo <<<LIMIT1
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">
LIMIT1;
                echo _ ( "Die neueste Version wurde erfolgreich installiert" );
                echo <<<LIMIT1
</h4>
      </div>
      <div class="panel-body" id="bye">
        <h1>
LIMIT1;
                echo _ ( "$my_name ist in Kürze zurück ..." );
                echo <<<LIMIT1
</h1>
      </div>
    </div>
  </div> 
LIMIT1;
                exec ( "(/bin/sleep 3 && /sbin/reboot) > /dev/null 2>&1 &" );
                waitForReboot ( "bye", "fa-bolt", "/updated.php?old=$my_version" );
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
    errorMsg ( _ ( "Die aktuelle Version konnte nicht heruntergeladen werden." ) );
    echo "</div>";
  }
}

/*
 * do actual check for update
 */
elseif ( isset ( $_REQUEST[ $checkUpdate ] ) )
{
  $error = FALSE;

  try
  {
    $json = file_get_contents ( $latestReleaseURL, false, stream_context_create ( array ( "http" => array ( "user_agent" => $my_name ) ) ) );

    if ( $json === false )
    {
      $error = TRUE;
    }
    else
    {
      $jsonDecoded = json_decode ( $json, TRUE );
      if ( $jsonDecoded == NULL )
      {
        $error = TRUE;
      }
      else
      {
        $newVersion = $jsonDecoded[ $versionField ];
        preg_match ( "/([0-9]+)\.([0-9]+)\.([0-9]+)$/", $my_version, $installed );
        preg_match ( "/([0-9]+)\.([0-9]+)\.([0-9]+)$/", $newVersion, $available );
        if ( $installed[ 1 ] < $available[ 1 ] ||
            ($installed[ 1 ] == $available[ 1 ] && $installed[ 2 ] < $available[ 2 ]) ||
            ($installed[ 1 ] == $available[ 1 ] && $installed[ 2 ] == $available[ 2 ] && $installed[ 3 ] < $available[ 3 ]) )
        {
          echo <<<LIMIT1
    <form class="form-horizontal" method="post">
      <div class="row">
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">
LIMIT1;
          echo _ ( "Update installieren" );
          echo <<<LIMIT1
</h4>
          </div>
          <div class="panel-body">
LIMIT1;
          infoMsg ( _ ( "Es ist eine neue Version verfügbar ($newVersion)" ) );
          alertMsg ( _ ( "Das Update benötigt einige Zeit und führt anschließend einen Neustart durch. Das Browserfenster kann währenddessen geöffnet bleiben." ) );
          echo "<input type=\"hidden\" name=\"$installURL\" value=\"", $jsonDecoded[ $tarballField ], "\">";
          echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
          echo _ ( "Neue Version herunterladen und installieren" );
          echo "\" name=\"$installUpdate\">";
          echo <<<LIMIT1
          </div>
        </div>
      </div>
    </form>
LIMIT1;

          $changelog = file_get_contents ( "https://api.github.com/repos/ulkuehn/LiMiT1/contents/CHANGELOG.md", false, stream_context_create ( array ( "http" => array ( "user_agent" => $my_name ) ) ) );

          if ( $changelog !== false )
          {
            $changelogDecoded = json_decode ( $changelog, TRUE );
            if ( $changelogDecoded != NULL )
            {
              echo <<<LIMIT1
      <div class="row">
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">
LIMIT1;
              echo _ ( "Änderungsverlauf" );
              echo <<<LIMIT1
</h4>
          </div>
          <div class="panel-body">
LIMIT1;
              require ("include/Parsedown.php");
              $Parsedown = new Parsedown();
              echo $Parsedown->text ( base64_decode ( $changelogDecoded[ "content" ] ) );
              echo <<<LIMIT1
          </div>
        </div>
      </div>
LIMIT1;
            }
          }
        }
        else
        {
          echo "<div class=\"row\">";
          infoMsg ( _ ( "Die installierte Version $my_version ist aktuell" ) );
          echo "</div>";
        }
      }
    }
  } catch (Exception $e)
  {
    $error = TRUE;
  }
  if ( $error )
  {
    echo "<div class=\"row\">";
    errorMsg ( _ ( "Die aktuell verfügbare Version konnte nicht ermittelt werden." ) );
    echo "</div>";
  }
}

/*
 * provide for update check
 */
else
{
  echo <<<LIMIT1
    <form class="form-horizontal" method="post">
      <div class="row">
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h4 class="panel-title">
LIMIT1;
  echo _ ( "Auf Updates prüfen" );
  echo <<<LIMIT1
</h4>
          </div>
          <div class="panel-body">
LIMIT1;

  if ( is_readable ( $offline_script ) )
  {
    if ( file_exists ( $session_file ) )
    {
      errorMsg ( _ ( "Aktuell erfolgt eine Aufzeichnung. Diese vor einem Update bitte beenden." ) );
    }
    else
    {
      echo "<input type=\"submit\" class=\"btn btn-primary\" value=\"";
      echo _ ( "Aktuell verfügbare Version prüfen" );
      echo "\" name=\"$checkUpdate\">";
    }
  }
  else
  {
    errorMsg ( _ ( "$my_name ist nicht online. Die Update-Prüfung setzt eine Internetverbindung voraus." ) );
  }
  echo <<<LIMIT1
          </div>
        </div>
      </div>
    </form>
LIMIT1;
}

require ("include/htmlend.php");

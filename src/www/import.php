<?php

//==============================================================================
//==============================================================================
//
//     PROJECT: LiMiT1
//        FILE: import.php
//         SEE: https://github.com/ulkuehn/LiMiT1
//      AUTHOR: Ulrich Kühn
//
//       USAGE: by web server
//
// DESCRIPTION: used to import a session that was exported beforehand
//              the export might origin from the same LiMiT1 system or from
//              some other system, thus enabling the transfer of sessions
//              from one system to another
//
//==============================================================================
//==============================================================================


require ("include/constants.php");
require ("include/configuration.php");
require ("include/utility.php");
require ("include/database.php");

require ("include/http.php");
require ("include/htmlstart.php");
require ("include/topmenu.php");

$_upload = "Aufzeichnung importieren";
$_vorzeitig = "Import vorzeitig beenden";

titelHilfe ("Aufzeichnung importieren", <<<LIMIT1
Eine zuvor auf diesem oder einem anderen $my_name-Gerät exportierte Aufzeichnung kann hier in die Datenbank importiert werden.
LIMIT1
);

if (isset($_POST["upload"]))
{
  echo "<div class=\"row\">";

  if (file_exists ($session_file))
  {
    errorMsg ("Während einer Aufzeichnung ist der Import einer anderen Aufzeichnung nicht möglich.");
  } 
  else if (move_uploaded_file ($_FILES["archiv"]["tmp_name"], $export_file) == false)
  {
    errorMsg ("Die Datei \"".$_FILES["archiv"]["name"]."\" konnte nicht hochgeladen werden");
  }
  else
  {
    $zip = new ZipArchive;
    if ($zip->open ($export_file) !== true)
    {
      errorMsg ("Die Datei \"".$_FILES["archiv"]["name"]."\" ist kein Archiv oder konnte nicht geöffnet werden");
    }
    else
    {
      if ($zip->locateName($connection_dir."/")!==false && 
          $zip->locateName($certificate_dir."/")!==false && 
          $zip->locateName($meta_file)!==false)
      {
        if (($xml = simplexml_load_string ($zip->getFromName($meta_file))) === false)
        {
          errorMsg ("Die Metadaten konnten nicht eingelesen werden");
        }
        else
        {
          $neu = true;
          $geraet = $xml->geraet[0];
          $select_s = $db->prepare ("select id from geraet where name=?");
          $select_s->execute (array($geraet->name->__toString()));
          if (($geraetID = $select_s->fetchColumn()) != false)
          {
            $select_s = $db->prepare ("select name,wert from eigenschaft where geraet=?");
            $select_s->execute (array($geraetID));
            while ($eigenschaft = $select_s->fetch())
            {
              $a1[$eigenschaft["name"]] = $eigenschaft["wert"];
            }
            foreach ($xml->eigenschaft as $eigenschaft)
            {
              $a2[$eigenschaft->name->__toString()] = $eigenschaft->wert->__toString();
            }
            $neu = ($a1 != $a2);
          }
          if ($neu)
          {
            $insert_s = $db->prepare ("insert into geraet set name=?, stand=?");
            $insert_s->execute (array($geraet->name->__toString(),$geraet->stand->__toString()));
            $geraetID = $db->lastInsertId();
            foreach ($xml->eigenschaft as $eigenschaft)
            {
              $insert_s = $db->prepare ("insert into eigenschaft set geraet=?, name=?, wert=?");
              $insert_s->execute (array($geraetID,$eigenschaft->name->__toString(),$eigenschaft->wert->__toString()));
            }
          }
          
          $aufzeichnung = $xml->aufzeichnung[0];
          $insert_s = $db->prepare ("insert into aufzeichnung set start=?, ende=?, name=?, info=?, geraet=?, ip=?");
          $insert_s->execute (array($aufzeichnung->start->__toString(),$aufzeichnung->ende->__toString(),$aufzeichnung->name->__toString(),$aufzeichnung->info->__toString(),$geraetID,$aufzeichnung->ip->__toString()));
          $aufzeichnungID = $db->lastInsertId();

          if (!$aufzeichnungID)
          {
            errorMsg ("Die Aufzeichnung konnte nicht angelegt werden");
          }
          else
          {
            $workdir = "$data_dir/$aufzeichnungID";
            $tempdir = "$temp_dir/$aufzeichnungID";
            
            if (is_dir($workdir))
            {
              exec ("/bin/rm -rf $workdir");
            }
            mkdir ($workdir);
            $zip->extractTo($workdir);
            
            if (is_dir($tempdir))
            {
              exec ("/bin/rm -rf $tempdir");
            }
            mkdir ($tempdir);
            
            $cmd = "$php5_bin ".$_SERVER["DOCUMENT_ROOT"]."/$dbinserter $aufzeichnungID ".long2ip($aufzeichnung->ip->__toString());
            exec (sprintf ("%s >%s 2>&1 & echo $! > %s", $cmd, "$tempdir/$dbinserter_output", "$tempdir/$dbinserter_pid"));
            if (!processPid("$tempdir/$dbinserter_pid"))
            {
              errorMsg ("$dbinserter konnte nicht gestartet werden");
            }
            else
            { 
              // create session file: 1st line session name, 2nd line ip address of monitored device, 3rd line running=0
              $sfile = fopen ($session_file, "w");
              fwrite ($sfile, $aufzeichnungID."\n");
              fwrite ($sfile, long2ip($aufzeichnung->ip->__toString())."\n");
              fwrite ($sfile, "0\n");
              fclose ($sfile);
                       
              successMsg ("Das Archiv wurde extrahiert");

              $sm = jsSave (successMsg ("Die <a href=\"aufzeichnung.php?aufzeichnung=$aufzeichnungID\">Aufzeichnung</a> wurde in die Datenbank übernommen.", false), false);
              $vz = jsSave ("<a href=\"#cancelModal\" class=\"btn btn-warning\" data-toggle=\"modal\">$_vorzeitig</a>", false); 
              echo <<<LIMIT1
  </div>
  <script>
  function progress()
  {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() 
    {
      if (xmlhttp.readyState==4 && xmlhttp.status==200) 
      {
        if (xmlhttp.responseText != "")
        {
          document.getElementById("progress").innerHTML = xmlhttp.responseText + "$vz";
        }
        else
        {
          document.getElementById("progress").innerHTML = "$sm";
        }
      }
    }
    
    xmlhttp.open("GET","include/progress.php?id=$aufzeichnungID&start=$starttime",true);
    xmlhttp.send();
  }
  progress();
  var myVar = setInterval (function () { progress() }, 1000);
  </script>    

  <form method="post">
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <div class="alert alert-warning" role="alert">
              <div class="msgIcon">
                <span class="fa-stack fa-lg">
                  <i class="fa fa-download fa-stack-1x"></i>
                  <i class="fa fa-ban fa-stack-2x"></i>
                </span>
              </div>
              <div class="msgText">
                <strong>$_vorzeitig</strong>
              </div>
            </div>
          </div>
          <div class="modal-body">
            <p>Soll der Import der Aufzeichnung tatächlich beendet werden?</p>
          </div>
          <div class="modal-footer">
            <input class="btn btn-warning" type="submit" value="$_vorzeitig" name="vorzeitig">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          </div>
        </div>
      </div>
    </div>
  </form>
  
  <div class="row" id="progress">
LIMIT1;
            }
          }
        }
      }
      else
      {
        errorMsg ("Es handelt sich nicht um ein $my_name-Archiv");
      }
      $zip->close();
    }
    
  }
  echo "</div>";
}

else if (isset($_POST["vorzeitig"]))
{
  $sfile = fopen ($session_file, "r");
  $aufzeichnungID = trim (fgets ($sfile));
  fclose ($sfile);

  if (is_readable("$temp_dir/$aufzeichnungID/$dbinserter_pid"))
  {
    $pf = fopen ("$temp_dir/$aufzeichnungID/$dbinserter_pid", "r");
    $pid = trim (fgets ($pf));
    fclose ($pf);

    system ("/bin/ps $pid >/dev/null", $rv);
    if ($rv==0)
    {
      posix_kill ($pid, SIGINT);
      while (file_exists($session_file))
      {
        sleep (1);
      }
      system ("/bin/ps $pid >/dev/null", $rv);
      if ($rv==0)
      {
        posix_kill ($pid, SIGKILL);
      }
    }
  }
  
  echo "<div class=\"row\">";
  successMsg ("Der Import der <a href=\"aufzeichnung.php?aufzeichnung=$aufzeichnungID\">Aufzeichnung</a> wurde vorzeitig beendet.");
  echo "</div>"; 
}

else
{
  if (file_exists ($session_file))
  {
    echo "<div class=\"row\">";
    errorMsg ("Während einer Aufzeichnung ist der Import einer anderen Aufzeichnung nicht möglich.");
    echo "</div>";
  }
  else
  {
    if (!is_readable($offline_script))
    {
      echo "<div class=\"row\">";
      alertMsg ("$my_name ist offline. Für einen fehlerfreien Import sollte $my_name mit dem Internet verbunden sein.");
      echo "</div>";
    }
    
    echo <<<LIMIT1
<form class="form-horizontal" method="post" enctype="multipart/form-data">
  <div class="row">
    <div class="panel panel-primary">
      <div class="panel-heading">
        <h4 class="panel-title">Archivdatei</h4>
      </div>
      <div class="panel-body">
        <div class="form-group">
          <div class="col-md-2">
            <div class="fileUpload btn btn-primary">
              <span>Datei auswählen</span>
              <input id="file" type="file" name="archiv" class="upload" onchange="document.getElementById('name').value = this.value">
            </div>
          </div>
          <div class="col-md-9 col-md-offset-1">
            <input id="name" class="form-control" placeholder="keine Datei ausgewählt" disabled="disabled">
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <input type="submit" class="btn btn-primary" value="$_upload" name="upload">
  </div>

</form>
LIMIT1;
  }
}

require ("include/htmlend.php");

?>
